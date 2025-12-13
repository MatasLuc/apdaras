<?php
// Categories API migrated from Node backend to PHP.

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
            'SELECT c.id, c.name, c.slug,
                    sc.id AS subcategory_id, sc.name AS subcategory_name, sc.slug AS subcategory_slug
             FROM categories c
             LEFT JOIN subcategories sc ON sc.category_id = c.id
             ORDER BY c.name, sc.name'
        );
        $rows = $stmt->fetchAll();
        respond_json($rows);
    }

    require_admin($pdo);

    if ($method === 'POST' && count($segments) === 2 && $segments[1] === 'subcategories') {
        [$categoryId] = array_map('intval', $segments);
        $name = $input['name'] ?? null;
        $slug = $input['slug'] ?? null;
        if (!$name || !$slug) {
            respond_json(['message' => 'Subcategory name and slug are required'], 400);
        }

        $stmt = $pdo->prepare('INSERT INTO subcategories (category_id, name, slug) VALUES (?, ?, ?)');
        $stmt->execute([$categoryId, $name, $slug]);
        respond_json(['id' => (int) $pdo->lastInsertId(), 'name' => $name, 'slug' => $slug, 'category_id' => $categoryId], 201);
    }

    if ($method === 'POST' && count($segments) === 0) {
        $name = $input['name'] ?? null;
        $slug = $input['slug'] ?? null;
        if (!$name || !$slug) {
            respond_json(['message' => 'Category name and slug are required'], 400);
        }

        $stmt = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)');
        $stmt->execute([$name, $slug]);
        respond_json(['id' => (int) $pdo->lastInsertId(), 'name' => $name, 'slug' => $slug], 201);
    }

    if ($method === 'PUT' && count($segments) === 1) {
        $categoryId = (int) $segments[0];
        $name = $input['name'] ?? null;
        $slug = $input['slug'] ?? null;
        if (!$name || !$slug) {
            respond_json(['message' => 'Category name and slug are required'], 400);
        }

        $stmt = $pdo->prepare('UPDATE categories SET name = ?, slug = ? WHERE id = ?');
        $stmt->execute([$name, $slug, $categoryId]);
        if ($stmt->rowCount() === 0) {
            respond_json(['message' => 'Category not found'], 404);
        }

        respond_json(['id' => $categoryId, 'name' => $name, 'slug' => $slug]);
    }

    if ($method === 'PUT' && count($segments) === 3 && $segments[1] === 'subcategories') {
        $categoryId = (int) $segments[0];
        $subcategoryId = (int) $segments[2];
        $name = $input['name'] ?? null;
        $slug = $input['slug'] ?? null;
        if (!$name || !$slug) {
            respond_json(['message' => 'Subcategory name and slug are required'], 400);
        }

        $stmt = $pdo->prepare('UPDATE subcategories SET name = ?, slug = ?, category_id = ? WHERE id = ?');
        $stmt->execute([$name, $slug, $categoryId, $subcategoryId]);
        if ($stmt->rowCount() === 0) {
            respond_json(['message' => 'Subcategory not found'], 404);
        }

        respond_json(['id' => $subcategoryId, 'name' => $name, 'slug' => $slug, 'category_id' => $categoryId]);
    }

    if ($method === 'DELETE' && count($segments) === 1) {
        $categoryId = (int) $segments[0];
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$categoryId]);
        respond_json(['id' => $categoryId, 'deleted' => true]);
    }

    if ($method === 'DELETE' && count($segments) === 3 && $segments[1] === 'subcategories') {
        $subcategoryId = (int) $segments[2];
        $stmt = $pdo->prepare('DELETE FROM subcategories WHERE id = ?');
        $stmt->execute([$subcategoryId]);
        respond_json(['id' => $subcategoryId, 'deleted' => true]);
    }

    respond_json(['message' => 'Not found'], 404);
} catch (Throwable $err) {
    respond_json(['message' => 'Server error', 'detail' => $err->getMessage()], 500);
}
