<?php
// Variations API migrated from Node backend to PHP.

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
    
    if (str_starts_with($path, $scriptName)) {
        $path = substr($path, strlen($scriptName));
    } else {
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
    if ($method === 'GET') {
        $stmt = $pdo->query(
            'SELECT va.id AS attribute_id, va.name AS attribute_name,
                    vv.id AS value_id, vv.value
             FROM variation_attributes va
             LEFT JOIN variation_values vv ON vv.variation_attribute_id = va.id
             ORDER BY va.name, vv.value'
        );
        $rows = $stmt->fetchAll();

        $attributes = [];
        $grouped = [];
        foreach ($rows as $row) {
            $attributeId = (int) $row['attribute_id'];
            if (!isset($grouped[$attributeId])) {
                $entry = ['id' => $attributeId, 'name' => $row['attribute_name'], 'values' => []];
                $grouped[$attributeId] = $entry;
                $attributes[] = &$grouped[$attributeId];
            }
            if (!empty($row['value_id'])) {
                $grouped[$attributeId]['values'][] = ['id' => (int) $row['value_id'], 'value' => $row['value']];
            }
        }

        respond_json($attributes);
    }

    require_admin($pdo);

    if ($method === 'POST' && count($segments) === 1 && $segments[0] === 'attributes') {
        $name = $input['name'] ?? null;
        if (!$name) {
            respond_json(['message' => 'Variation attribute name is required'], 400);
        }

        $stmt = $pdo->prepare('INSERT INTO variation_attributes (name) VALUES (?)');
        $stmt->execute([$name]);
        respond_json(['id' => (int) $pdo->lastInsertId(), 'name' => $name], 201);
    }

    if ($method === 'POST' && count($segments) === 3 && $segments[0] === 'attributes' && $segments[2] === 'values') {
        $attributeId = (int) $segments[1];
        $value = $input['value'] ?? null;
        if (!$value) {
            respond_json(['message' => 'Variation value is required'], 400);
        }

        $stmt = $pdo->prepare('INSERT INTO variation_values (variation_attribute_id, value) VALUES (?, ?)');
        $stmt->execute([$attributeId, $value]);
        respond_json(['id' => (int) $pdo->lastInsertId(), 'value' => $value, 'variation_attribute_id' => $attributeId], 201);
    }

    respond_json(['message' => 'Not found'], 404);
} catch (Throwable $err) {
    respond_json(['message' => 'Server error', 'detail' => $err->getMessage()], 500);
}
