<?php
// Session-backed cart helpers and demo catalog.

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

function format_product_categories(?string $raw): array
{
    if (!$raw) {
        return [];
    }

    $parts = array_filter(array_map('trim', explode('|', $raw)));
    $names = [];

    foreach ($parts as $part) {
        $segments = explode(':', $part, 2);
        if (count($segments) === 2 && $segments[1] !== '') {
            $names[] = $segments[1];
        }
    }

    return $names;
}

function cart_catalog(): array
{
    static $cachedCatalog = null;

    if ($cachedCatalog !== null) {
        return $cachedCatalog;
    }

    $pdo = get_db_connection();

    $sql = "SELECT p.id, p.title, p.price, p.discount_price, p.stock, p.ribbon, p.tags, p.summary, p.subtitle,
                   (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as image_url,
                   GROUP_CONCAT(DISTINCT CONCAT(c.id, ':', c.name) ORDER BY c.name SEPARATOR '|') AS categories
            FROM products p
            LEFT JOIN product_categories pc ON pc.product_id = p.id
            LEFT JOIN categories c ON pc.category_id = c.id
            GROUP BY p.id
            ORDER BY p.created_at DESC";

    $stmt = $pdo->query($sql);

    $products = $stmt ? $stmt->fetchAll() : [];
    $catalog = [];

    foreach ($products as $product) {
        if (!isset($product['id'], $product['title'], $product['price'])) {
            continue;
        }

        $categories = format_product_categories($product['categories'] ?? '');
        $tag = trim((string) ($product['ribbon'] ?? ''));

        if (!$tag && !empty($product['tags'])) {
            $tagParts = array_filter(array_map('trim', explode(',', (string) $product['tags'])));
            $tag = $tagParts[0] ?? '';
        }

        $discount = $product['discount_price'] ?? null;
        $finalPrice = $discount !== null && $discount !== '' ? (float) $discount : (float) $product['price'];

        $catalog[] = [
            'id' => (string) $product['id'],
            'name' => (string) $product['title'],
            'image_url' => $product['image_url'] ?? null,
            'price' => $finalPrice,
            'full_price' => (float) $product['price'],
            'discount_price' => $discount !== null && $discount !== '' ? (float) $discount : null,
            'category' => $categories[0] ?? 'Kategorija',
            'tag' => $tag ?: 'Nauja',
            'summary' => (string) ($product['summary'] ?? ($product['subtitle'] ?? '')),
            'stock' => isset($product['stock']) ? (int) $product['stock'] : null,
        ];
    }

    $cachedCatalog = $catalog;
    return $cachedCatalog;
}

function ensure_cart_initialized(): void
{
    ensure_session();

    if (!isset($_SESSION['cart_items']) || !is_array($_SESSION['cart_items'])) {
        $_SESSION['cart_items'] = [];
    }

    if (!isset($_SESSION['cart_count'])) {
        $_SESSION['cart_count'] = 0;
    }
}

function cart_items(): array
{
    ensure_cart_initialized();

    return $_SESSION['cart_items'];
}

function cart_count(): int
{
    ensure_cart_initialized();

    return (int) ($_SESSION['cart_count'] ?? 0);
}

function recalc_cart_count(): void
{
    $count = 0;
    foreach (cart_items() as $item) {
        $count += (int) ($item['qty'] ?? 0);
    }

    $_SESSION['cart_count'] = max(0, $count);
}

function find_catalog_product(string $productId): ?array
{
    foreach (cart_catalog() as $product) {
        if ($product['id'] === $productId) {
            return $product;
        }
    }

    return null;
}

function add_cart_item(string $productId, int $quantity = 1): ?array
{
    $product = find_catalog_product($productId);
    if (!$product) {
        return null;
    }

    ensure_cart_initialized();
    $items = cart_items();

    $quantity = max(1, min($quantity, 20));

    if (isset($product['stock'])) {
        $available = max(0, (int) $product['stock']);
        $existingQty = isset($items[$productId]) ? (int) ($items[$productId]['qty'] ?? 0) : 0;
        $quantity = min($quantity, max(0, $available - $existingQty));

        if ($quantity === 0) {
            return null;
        }
    }

    if (isset($items[$productId])) {
        $items[$productId]['qty'] += $quantity;
    } else {
        $items[$productId] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'category' => $product['category'] ?? null,
            'tag' => $product['tag'] ?? null,
            'qty' => $quantity,
        ];
    }

    $_SESSION['cart_items'] = $items;
    recalc_cart_count();

    return $items[$productId];
}

function update_cart_item(string $productId, int $quantity): void
{
    ensure_cart_initialized();
    $items = cart_items();

    if (!isset($items[$productId])) {
        return;
    }

    $quantity = max(0, min($quantity, 20));

    $product = find_catalog_product($productId);
    if ($product && isset($product['stock'])) {
        $quantity = min($quantity, max(0, (int) $product['stock']));
    }

    if ($quantity === 0) {
        unset($items[$productId]);
    } else {
        $items[$productId]['qty'] = $quantity;
    }

    $_SESSION['cart_items'] = $items;
    recalc_cart_count();
}

function remove_cart_item(string $productId): void
{
    ensure_cart_initialized();
    $items = cart_items();

    if (isset($items[$productId])) {
        unset($items[$productId]);
    }

    $_SESSION['cart_items'] = $items;
    recalc_cart_count();
}

function clear_cart(): void
{
    ensure_cart_initialized();
    $_SESSION['cart_items'] = [];
    $_SESSION['cart_count'] = 0;
}

function cart_subtotal(): float
{
    $total = 0.0;

    foreach (cart_items() as $item) {
        $qty = max(0, (int) ($item['qty'] ?? 0));
        $total += $qty * (float) ($item['price'] ?? 0);
    }

    return $total;
}

function cart_shipping_fee(): float
{
    return cart_count() > 0 ? 4.90 : 0.0;
}

function cart_total(): float
{
    return cart_subtotal() + cart_shipping_fee();
}
