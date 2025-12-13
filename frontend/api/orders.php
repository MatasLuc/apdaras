<?php
// orders.php - API užsakymams gauti ir redaguoti

declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');

function respond_json($data, int $status = 200): void {
    http_response_code($status); echo json_encode($data); exit;
}

// Skaitome input'ą JSON formatu (PUT užklausoms)
$input = json_decode(file_get_contents('php://input') ?: 'null', true);
$input = is_array($input) ? $input : [];

$pdo = get_db_connection();
$user = require_login($pdo);

if (($user['role'] ?? '') !== 'admin') {
    respond_json(['message' => 'Forbidden'], 403);
}

// Analizuojame kelią, kad gautume ID (pvz., /orders/1)
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
// Paprastas būdas gauti ID: jei kelias baigiasi skaičiumi
$segments = explode('/', trim($path, '/'));
$orderId = end($segments);
if (!ctype_digit($orderId)) {
    $orderId = null;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // ... (GET logika lieka ta pati) ...
        $sql = "
            SELECT o.*, 
                   COALESCE(u.email, o.guest_email) as contact_email,
                   COALESCE(u.name, o.guest_name) as contact_name
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
        ";
        $orders = $pdo->query($sql)->fetchAll();
        
        foreach ($orders as &$ord) {
            $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmtItems->execute([$ord['id']]);
            $ord['items'] = $stmtItems->fetchAll();
        }
        
        respond_json($orders);
    }

    // NAUJAS: PUT metodas statusui atnaujinti
    if ($method === 'PUT' && $orderId) {
        $newStatus = $input['status'] ?? null;
        $allowedStatuses = ['new', 'paid', 'shipped', 'completed', 'cancelled'];

        if (!$newStatus || !in_array($newStatus, $allowedStatuses)) {
            respond_json(['message' => 'Neteisingas statusas'], 400);
        }

        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);

        respond_json(['success' => true, 'id' => $orderId, 'status' => $newStatus]);
    }

} catch (Exception $e) {
    respond_json(['message' => $e->getMessage()], 500);
}
