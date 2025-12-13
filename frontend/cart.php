<?php
// cart.php - MySQL krepšelis ir katalogas

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

function ensure_cart_initialized(): void
{
    ensure_session();
}

// --- Katalogo funkcijos (kad veiktų parduotuvė) ---

function format_product_categories(?string $raw): array
{
    if (!$raw) return [];
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
    if ($cachedCatalog !== null) return $cachedCatalog;

    $pdo = get_db_connection();

    $sql = "SELECT p.id, p.title, p.price, p.discount_price, p.stock, p.ribbon, p.tags, p.summary, p.subtitle,
                   (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as image_url,
                   GROUP_CONCAT(DISTINCT CONCAT(c.id, ':', c.name) ORDER BY c.name SEPARATOR '|') AS categories
            FROM products p
            LEFT JOIN product_categories pc ON pc.product_id = p.id
            LEFT JOIN categories c ON pc.category_id = c.id
            GROUP BY p.id
            ORDER BY p.id DESC"; // Rikiuojame pagal ID

    $stmt = $pdo->query($sql);
    $products = $stmt ? $stmt->fetchAll() : [];
    $catalog = [];

    foreach ($products as $product) {
        if (!isset($product['id'], $product['title'], $product['price'])) continue;

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

// --- Krepšelio funkcijos ---

function get_active_cart_id(PDO $pdo): int
{
    ensure_session();
    $sessionId = session_id();
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId) {
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE session_id = ? AND user_id IS NULL ORDER BY id DESC LIMIT 1");
        $stmt->execute([$sessionId]);
    }
    
    $cartId = $stmt->fetchColumn();

    if (!$cartId) {
        $stmt = $pdo->prepare("INSERT INTO carts (session_id, user_id) VALUES (?, ?)");
        $stmt->execute([$sessionId, $userId]);
        $cartId = (int)$pdo->lastInsertId();
    }

    return (int)$cartId;
}

function cart_items(): array
{
    $pdo = get_db_connection();
    $cartId = get_active_cart_id($pdo);

    // Naudojame ci.id DESC, kad išvengtume klaidų jei nėra created_at
    $sql = "
        SELECT ci.id as item_id, ci.quantity, ci.variation_id,
               p.id as product_id, p.title as name, p.price, p.discount_price, p.stock,
               (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as image_url,
               (SELECT value FROM variation_values WHERE id = ci.variation_id) as variation_name,
               (SELECT name FROM variation_attributes va JOIN variation_values vv ON vv.variation_attribute_id = va.id WHERE vv.id = ci.variation_id) as attribute_name
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = ?
        ORDER BY ci.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cartId]);
    $rows = $stmt->fetchAll();

    $items = [];
    foreach ($rows as $row) {
        $price = $row['discount_price'] ?: $row['price'];
        $items[] = [
            'item_id' => $row['item_id'],
            'id' => $row['product_id'],
            'name' => $row['name'],
            'image_url' => $row['image_url'],
            'price' => (float)$price,
            'qty' => (int)$row['quantity'],
            'variation_id' => $row['variation_id'],
            'variation_text' => $row['variation_name'] ? "{$row['attribute_name']}: {$row['variation_name']}" : null,
            'stock' => (int)$row['stock']
        ];
    }

    return $items;
}

function add_cart_item(string $productId, int $quantity = 1, ?int $variationId = null): bool
{
    $pdo = get_db_connection();
    $cartId = get_active_cart_id($pdo);
    
    $sqlCheck = "SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?";
    $params = [$cartId, $productId];
    
    if ($variationId) {
        $sqlCheck .= " AND variation_id = ?";
        $params[] = $variationId;
    } else {
        $sqlCheck .= " AND variation_id IS NULL";
    }

    $stmt = $pdo->prepare($sqlCheck);
    $stmt->execute($params);
    $existing = $stmt->fetch();

    if ($existing) {
        $newQty = $existing['quantity'] + $quantity;
        $stmtUpd = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        return $stmtUpd->execute([$newQty, $existing['id']]);
    } else {
        // Įterpiame be user_id, nes jis yra carts lentelėje
        $stmtIns = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, variation_id, quantity) VALUES (?, ?, ?, ?)");
        return $stmtIns->execute([$cartId, $productId, $variationId, $quantity]);
    }
}

function update_cart_item(int $itemId, int $quantity): void
{
    $pdo = get_db_connection();
    if ($quantity <= 0) {
        remove_cart_item($itemId);
        return;
    }
    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $stmt->execute([$quantity, $itemId]);
}

function remove_cart_item(int $itemId): void
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ?");
    $stmt->execute([$itemId]);
}

function clear_cart(): void
{
    $pdo = get_db_connection();
    $cartId = get_active_cart_id($pdo);
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cartId]);
}

function cart_count(): int
{
    $items = cart_items();
    $count = 0;
    foreach ($items as $item) {
        $count += $item['qty'];
    }
    return $count;
}

function cart_subtotal(): float
{
    $items = cart_items();
    $total = 0.0;
    foreach ($items as $item) {
        $total += $item['qty'] * $item['price'];
    }
    return $total;
}

// PATAISYMAS: Jei nėra prekių, nėra ir mokesčio
function cart_shipping_fee(): float
{
    if (cart_count() === 0) return 0.0;
    return cart_subtotal() > 70 ? 0.0 : 4.90;
}

function cart_total(): float
{
    if (cart_count() === 0) return 0.0;
    return cart_subtotal() + cart_shipping_fee();
}

function create_order_from_cart(array $guestInfo = []): ?int
{
    $pdo = get_db_connection();
    $cartId = get_active_cart_id($pdo);
    $items = cart_items();

    if (empty($items)) {
        return null;
    }

    $totalPrice = cart_total();
    $userId = $_SESSION['user_id'] ?? null;

    try {
        $pdo->beginTransaction();

        $stmtOrder = $pdo->prepare("
            INSERT INTO orders (user_id, guest_name, guest_email, guest_address, total_price, status)
            VALUES (?, ?, ?, ?, ?, 'new')
        ");
        $stmtOrder->execute([
            $userId,
            $guestInfo['name'] ?? null,
            $guestInfo['email'] ?? null,
            $guestInfo['address'] ?? null,
            $totalPrice
        ]);
        $orderId = (int)$pdo->lastInsertId();

        $stmtItem = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, variation_info, quantity, price)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($items as $item) {
            $stmtItem->execute([
                $orderId,
                $item['id'],
                $item['name'],
                $item['variation_text'],
                $item['qty'],
                $item['price']
            ]);
            
            $stmtStock = $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");
            $stmtStock->execute([$item['qty'], $item['id']]);
        }

        $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cartId]);

        $pdo->commit();
        return $orderId;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order creation failed: " . $e->getMessage());
        return null;
    }
}
?>
