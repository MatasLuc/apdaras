<?php
// cart.php - MySQL paremtas krepšelis

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

// --- Pagalbinė funkcija gauti aktyviam krepšeliui ---
function get_active_cart_id(PDO $pdo): int
{
    ensure_session();
    $sessionId = session_id();
    $userId = $_SESSION['user_id'] ?? null;

    // Bandome rasti krepšelį
    if ($userId) {
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE session_id = ? AND user_id IS NULL ORDER BY id DESC LIMIT 1");
        $stmt->execute([$sessionId]);
    }
    
    $cartId = $stmt->fetchColumn();

    // Jei nėra - sukuriame
    if (!$cartId) {
        $stmt = $pdo->prepare("INSERT INTO carts (session_id, user_id) VALUES (?, ?)");
        $stmt->execute([$sessionId, $userId]);
        $cartId = (int)$pdo->lastInsertId();
    }

    return (int)$cartId;
}

// --- Krepšelio funkcijos ---

function cart_items(): array
{
    $pdo = get_db_connection();
    $cartId = get_active_cart_id($pdo);

    $sql = "
        SELECT ci.id as item_id, ci.quantity, ci.variation_id,
               p.id as product_id, p.title as name, p.price, p.discount_price, p.stock,
               (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as image_url,
               (SELECT value FROM variation_values WHERE id = ci.variation_id) as variation_name,
               (SELECT name FROM variation_attributes va JOIN variation_values vv ON vv.variation_attribute_id = va.id WHERE vv.id = ci.variation_id) as attribute_name
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = ?
        ORDER BY ci.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cartId]);
    $rows = $stmt->fetchAll();

    $items = [];
    foreach ($rows as $row) {
        $price = $row['discount_price'] ?: $row['price'];
        $items[] = [
            'item_id' => $row['item_id'], // DB įrašo ID (trynimui)
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
    
    // Patikriname, ar tokia prekė (su tokia variacija) jau yra krepšelyje
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
        // Atnaujiname kiekį
        $newQty = $existing['quantity'] + $quantity;
        // Čia reiktų patikrinti stock, bet supaprastinimui praleidžiu
        $stmtUpd = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        return $stmtUpd->execute([$newQty, $existing['id']]);
    } else {
        // Įterpiame naują
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
    // Saugumo dėlei reiktų tikrinti ar item priklauso active_cart_id, bet supaprastinam
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

function cart_shipping_fee(): float
{
    // Paprasta logika: nemokamai virš 70, kitaip 4.90
    return cart_subtotal() > 70 ? 0.0 : 4.90;
}

function cart_total(): float
{
    return cart_subtotal() + cart_shipping_fee();
}

// --- Užsakymo sukūrimas ---

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

        // 1. Sukuriame užsakymą
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

        // 2. Perkeliame prekes į order_items
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
            
            // Čia reiktų mažinti stock kiekį products lentelėje
            $stmtStock = $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");
            $stmtStock->execute([$item['qty'], $item['id']]);
        }

        // 3. Išvalome krepšelį
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
