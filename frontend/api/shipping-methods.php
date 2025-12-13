<?php
// Shipping methods API migrated from Node backend to PHP.

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');

function respond_json($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function parse_path_segments(): array
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    
    // Jei kelias prasideda pilnu skripto pavadinimu (pvz., /api/categories.php), pašaliname jį visą
    if (str_starts_with($path, $scriptName)) {
        $path = substr($path, strlen($scriptName));
    } else {
        // Kitu atveju šaliname tik aplanką (jei naudojamas URL perrašymas)
        $scriptDir = rtrim(dirname($scriptName), '/');
        if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }
    }

    $path = ltrim($path, '/');

    if (isset($_SERVER['PATH_INFO'])) {
        $path = ltrim((string) $_SERVER['PATH_INFO'], '/');
    }

    return array_values(array_filter(explode('/', $path), 'strlen'));
}

function require_admin(PDO $pdo): array
{
    $user = require_login($pdo);
    if (($user['role'] ?? '') !== 'admin') {
        respond_json(['message' => 'Forbidden'], 403);
    }

    return $user;
}

$pdo = get_db_connection();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$segments = parse_path_segments();
$input = json_decode(file_get_contents('php://input') ?: 'null', true);
$input = is_array($input) ? $input : [];

try {
    if ($method === 'GET' && count($segments) === 0) {
        $stmt = $pdo->query('SELECT * FROM shipping_methods ORDER BY id');
        $rows = $stmt->fetchAll();
        respond_json($rows);
    }

    require_admin($pdo);

    if ($method === 'POST' && count($segments) === 0) {
        $name = $input['name'] ?? null;
        $price = $input['price'] ?? null;
        $estimatedDays = $input['estimated_days'] ?? null;
        if ($name === null || $price === null) {
            respond_json(['message' => 'Name and price are required'], 400);
        }

        $stmt = $pdo->prepare('INSERT INTO shipping_methods (name, price, estimated_days) VALUES (?, ?, ?)');
        $stmt->execute([$name, $price, $estimatedDays ?: null]);
        respond_json(['id' => (int) $pdo->lastInsertId()], 201);
    }

    if ($method === 'PUT' && count($segments) === 1) {
        $id = (int) $segments[0];
        $name = $input['name'] ?? null;
        $price = $input['price'] ?? null;
        $estimatedDays = $input['estimated_days'] ?? null;

        $stmt = $pdo->prepare('UPDATE shipping_methods SET name = ?, price = ?, estimated_days = ? WHERE id = ?');
        $stmt->execute([$name, $price, $estimatedDays ?: null, $id]);
        if ($stmt->rowCount() === 0) {
            respond_json(['message' => 'Shipping method not found'], 404);
        }

        respond_json(['success' => true]);
    }

    if ($method === 'DELETE' && count($segments) === 1) {
        $id = (int) $segments[0];
        $stmt = $pdo->prepare('DELETE FROM shipping_methods WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            respond_json(['message' => 'Shipping method not found'], 404);
        }

        respond_json(['success' => true]);
    }

    respond_json(['message' => 'Not found'], 404);
} catch (Throwable $err) {
    respond_json(['message' => 'Server error', 'detail' => $err->getMessage()], 500);
}
