<?php
// Session-backed cart helpers and demo catalog.

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function cart_catalog(): array
{
    return [
        ['id' => 'tee-urban', 'name' => 'Urban marškinėliai', 'price' => 24.00, 'category' => 'Marškinėliai', 'tag' => 'Nauja'],
        ['id' => 'tee-oversize', 'name' => 'Oversize marškinėliai', 'price' => 27.00, 'category' => 'Marškinėliai', 'tag' => 'Bestseleris'],
        ['id' => 'hoodie-core', 'name' => 'Core džemperis', 'price' => 39.00, 'category' => 'Džemperiai', 'tag' => 'Populiaru'],
        ['id' => 'hoodie-zip', 'name' => 'Zip hoodie', 'price' => 42.00, 'category' => 'Džemperiai', 'tag' => 'Nauja'],
        ['id' => 'cap-minimal', 'name' => 'Minimalistinė kepuraitė', 'price' => 19.00, 'category' => 'Aksesuarai', 'tag' => 'Ribotas kiekis'],
        ['id' => 'bag-city', 'name' => 'City kuprinė', 'price' => 58.00, 'category' => 'Aksesuarai', 'tag' => 'Top pasirinkimas'],
    ];
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
