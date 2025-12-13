<?php
// orders.php - API užsakymams gauti

declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');

function respond_json($data, int $status = 200): void {
    http_response_code($status); echo json_encode($data); exit;
}

$pdo = get_db_connection();
$user = require_login($pdo);

if (($user['role'] ?? '') !== 'admin') {
    respond_json(['message' => 'Forbidden'], 403);
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Gauname visus užsakymus su vartotojo info
        $sql = "
            SELECT o.*, 
                   COALESCE(u.email, o.guest_email) as contact_email,
                   COALESCE(u.name, o.guest_name) as contact_name
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
        ";
        $orders = $pdo->query($sql)->fetchAll();
        
        // Gauname prekes kiekvienam užsakymui (galima optimizuoti, bet pradžiai gerai)
        foreach ($orders as &$ord) {
            $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmtItems->execute([$ord['id']]);
            $ord['items'] = $stmtItems->fetchAll();
        }
        
        respond_json($orders);
    }
} catch (Exception $e) {
    respond_json(['message' => $e->getMessage()], 500);
}
