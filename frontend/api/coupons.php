<?php
// Coupons API migrated from Node backend to PHP.

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
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($scriptDir !== '' && $scriptDir !== '/') {
        if (str_starts_with($path, $scriptDir)) {
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
        $stmt = $pdo->query('SELECT * FROM coupons ORDER BY id DESC');
        $rows = $stmt->fetchAll();
        respond_json($rows);
    }

    require_admin($pdo);

    if ($method === 'POST' && count($segments) === 0) {
        $code = $input['code'] ?? null;
        $discountType = $input['discount_type'] ?? null;
        $discountValue = $input['discount_value'] ?? null;
        $expiresAt = $input['expires_at'] ?? null;
        $usageLimit = $input['usage_limit'] ?? null;
        if (!$code || !$discountType || !$discountValue) {
            respond_json(['message' => 'Code, discount_type and discount_value are required'], 400);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO coupons (code, discount_type, discount_value, expires_at, usage_limit) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$code, $discountType, $discountValue, $expiresAt ?: null, $usageLimit ?: null]);
        respond_json(['id' => (int) $pdo->lastInsertId()], 201);
    }

    if ($method === 'PUT' && count($segments) === 1) {
        $id = (int) $segments[0];
        $code = $input['code'] ?? null;
        $discountType = $input['discount_type'] ?? null;
        $discountValue = $input['discount_value'] ?? null;
        $expiresAt = $input['expires_at'] ?? null;
        $usageLimit = $input['usage_limit'] ?? null;
        $timesUsed = $input['times_used'] ?? 0;

        $stmt = $pdo->prepare(
            'UPDATE coupons SET code = ?, discount_type = ?, discount_value = ?, expires_at = ?, usage_limit = ?, times_used = ? WHERE id = ?'
        );
        $stmt->execute([$code, $discountType, $discountValue, $expiresAt, $usageLimit, $timesUsed ?: 0, $id]);
        if ($stmt->rowCount() === 0) {
            respond_json(['message' => 'Coupon not found'], 404);
        }

        respond_json(['success' => true]);
    }

    if ($method === 'DELETE' && count($segments) === 1) {
        $id = (int) $segments[0];
        $stmt = $pdo->prepare('DELETE FROM coupons WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            respond_json(['message' => 'Coupon not found'], 404);
        }

        respond_json(['success' => true]);
    }

    respond_json(['message' => 'Not found'], 404);
} catch (Throwable $err) {
    respond_json(['message' => 'Server error', 'detail' => $err->getMessage()], 500);
}
