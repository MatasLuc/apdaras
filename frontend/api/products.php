<?php
// Products API migrated from Node backend to PHP.

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

function parseDelimited(?string $raw, string $separator = ','): array
{
    if ($raw === null || $raw === '') {
        return [];
    }

    return array_values(
        array_filter(
            array_map(static fn($part) => trim((string) $part), explode($separator, (string) $raw)),
            'strlen'
        )
    );
}

function parseIdNamePairs(?string $raw): array
{
    return array_map(
        static function ($pair) {
            [$id, $name] = array_pad(explode(':', $pair, 2), 2, '');
            return ['id' => (int) $id, 'name' => $name];
        },
        parseDelimited($raw, '|')
    );
}

function parseImageMeta(?string $rawMeta, ?string $rawUrls): array
{
    $urls = parseDelimited($rawUrls);
    $meta = array_map(
        static function ($entry) {
            [$id, $isPrimary] = array_pad(explode(':', $entry, 2), 2, '');
            return ['id' => (int) $id, 'is_primary' => $isPrimary === '1'];
        },
        parseDelimited($rawMeta)
    );

    $images = [];
    foreach ($urls as $index => $url) {
        $images[] = [
            'id' => $meta[$index]['id'] ?? null,
            'image_url' => $url,
            'is_primary' => $meta[$index]['is_primary'] ?? ($index === 0),
        ];
    }

    return $images;
}

function normalizeProduct(array $row): array
{
    $variationValueIds = array_map('intval', parseDelimited($row['variation_value_ids_raw'] ?? ''));
    $relatedProductIds = array_map('intval', parseDelimited($row['related_products'] ?? ''));

    return array_merge($row, [
        'categories_list' => parseIdNamePairs($row['categories'] ?? null),
        'subcategories_list' => parseIdNamePairs($row['subcategories'] ?? null),
        'variation_value_ids' => $variationValueIds,
        'images_list' => parseImageMeta($row['image_meta'] ?? null, $row['images'] ?? null),
        'related_product_ids' => $relatedProductIds,
    ]);
}

function toNumericArray($value): array
{
    if (!is_array($value)) {
        return [];
    }

    $result = [];
    foreach ($value as $item) {
        $num = (int) $item;
        if ($num > 0) {
            $result[] = $num;
        }
    }

    return $result;
}

function resetLinks(PDO $pdo, string $table, string $column, int $productId, array $values): void
{
    $stmt = $pdo->prepare("DELETE FROM {$table} WHERE product_id = ?");
    $stmt->execute([$productId]);

    if (!$values) {
        return;
    }

    $insert = $pdo->prepare("INSERT IGNORE INTO {$table} (product_id, {$column}) VALUES (?, ?)");
    foreach ($values as $value) {
        $insert->execute([$productId, $value]);
    }
}

function insertImages(PDO $pdo, int $productId, array $images = []): void
{
    if (!$images) {
        return;
    }

    $hasPrimary = false;
    foreach ($images as $image) {
        if (is_array($image) && !empty($image['is_primary'])) {
            $hasPrimary = true;
            break;
        }
    }

    $primarySet = false;
    $insert = $pdo->prepare('INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)');
    $resetPrimary = $pdo->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = ?');

    foreach ($images as $index => $image) {
        if (!is_array($image) || empty($image['image_url'])) {
            continue;
        }

        $isPrimary = (!empty($image['is_primary'])) || (!$hasPrimary && $index === 0);
        if ($isPrimary && !$primarySet) {
            $resetPrimary->execute([$productId]);
            $primarySet = true;
        }

        $insert->execute([$productId, $image['image_url'], $isPrimary ? 1 : 0]);
    }
}

function require_admin(PDO $pdo): array
{
    $user = current_user($pdo);
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        respond_json(['message' => 'Forbidden'], 403);
    }

    return $user;
}

$pdo = get_db_connection();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$segments = parse_path_segments();
$input = json_decode(file_get_contents('php://input') ?: 'null', true);
$input = is_array($input) ? $input : [];

$baseSelect = <<<SQL
  SELECT p.id, p.title, p.slug, p.subtitle, p.ribbon, p.summary, p.description,
         p.price, p.discount_price, p.stock, p.tags, p.weight_kg, p.allow_personalization,
         p.category_id, p.subcategory_id,
         GROUP_CONCAT(DISTINCT CONCAT(c.id, ':', c.name) ORDER BY c.name SEPARATOR '|') AS categories,
         GROUP_CONCAT(DISTINCT CONCAT(sc.id, ':', sc.name) ORDER BY sc.name SEPARATOR '|') AS subcategories,
         GROUP_CONCAT(DISTINCT CONCAT(va.name, ':', vv.value) ORDER BY va.name, vv.value SEPARATOR '|') AS variations,
         GROUP_CONCAT(DISTINCT vv.id ORDER BY vv.id SEPARATOR ',') AS variation_value_ids_raw,
         GROUP_CONCAT(DISTINCT CONCAT(pi.id, ':', pi.is_primary) ORDER BY pi.is_primary DESC, pi.id ASC SEPARATOR ',') AS image_meta,
         GROUP_CONCAT(DISTINCT pi.image_url ORDER BY pi.is_primary DESC, pi.id ASC SEPARATOR ',') AS images,
         GROUP_CONCAT(DISTINCT pr.related_product_id ORDER BY pr.related_product_id SEPARATOR ',') AS related_products
  FROM products p
  LEFT JOIN product_categories pc ON pc.product_id = p.id
  LEFT JOIN categories c ON pc.category_id = c.id
  LEFT JOIN product_subcategories psc ON psc.product_id = p.id
  LEFT JOIN subcategories sc ON psc.subcategory_id = sc.id
  LEFT JOIN product_images pi ON pi.product_id = p.id
  LEFT JOIN product_variations pv ON pv.product_id = p.id
  LEFT JOIN variation_values vv ON pv.variation_value_id = vv.id
  LEFT JOIN variation_attributes va ON vv.variation_attribute_id = va.id
  LEFT JOIN product_related pr ON pr.product_id = p.id
SQL;

try {
    if ($method === 'GET' && count($segments) === 0) {
        $filters = [];
        $params = [];

        if (!empty($_GET['category'])) {
            $filters[] = 'EXISTS (SELECT 1 FROM product_categories pc_filter WHERE pc_filter.product_id = p.id AND pc_filter.category_id = ?)';
            $params[] = $_GET['category'];
        }

        if (!empty($_GET['subcategory'])) {
            $filters[] = 'EXISTS (SELECT 1 FROM product_subcategories psc_filter WHERE psc_filter.product_id = p.id AND psc_filter.subcategory_id = ?)';
            $params[] = $_GET['subcategory'];
        }

        if (!empty($_GET['search'])) {
            $filters[] = '(p.title LIKE ? OR p.tags LIKE ?)';
            $params[] = '%' . $_GET['search'] . '%';
            $params[] = '%' . $_GET['search'] . '%';
        }

        $where = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

        $stmt = $pdo->prepare("{$baseSelect} {$where} GROUP BY p.id ORDER BY p.created_at DESC");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        respond_json(array_map('normalizeProduct', $rows));
    }

    if ($method === 'GET' && count($segments) === 1 && ctype_digit($segments[0])) {
        $stmt = $pdo->prepare("{$baseSelect} WHERE p.id = ? GROUP BY p.id");
        $stmt->execute([(int) $segments[0]]);
        $rows = $stmt->fetchAll();
        if (!$rows) {
            respond_json(['message' => 'Product not found'], 404);
        }
        respond_json(normalizeProduct($rows[0]));
    }

    if ($method === 'POST' && count($segments) === 0) {
        require_admin($pdo);

        $title = $input['title'] ?? null;
        $slug = $input['slug'] ?? null;
        $price = $input['price'] ?? null;

        if (!$title || !$slug || $price === null) {
            respond_json(['message' => 'Title, slug and price are required'], 400);
        }

        $subtitle = $input['subtitle'] ?? '';
        $ribbon = $input['ribbon'] ?? '';
        $summary = $input['summary'] ?? '';
        $description = $input['description'] ?? '';
        $discountPrice = $input['discount_price'] ?? null;
        $stock = $input['stock'] ?? 0;
        $tags = $input['tags'] ?? '';
        $weightKg = $input['weight_kg'] ?? null;
        $allowPersonalization = !empty($input['allow_personalization']);
        $categoryList = toNumericArray(isset($input['categories']) ? $input['categories'] : (isset($input['category_id']) ? [$input['category_id']] : []));
        $subcategoryList = toNumericArray(isset($input['subcategories']) ? $input['subcategories'] : (isset($input['subcategory_id']) ? [$input['subcategory_id']] : []));
        $variationList = toNumericArray($input['variation_value_ids'] ?? []);
        $relatedList = toNumericArray($input['related_product_ids'] ?? []);
        $images = is_array($input['images'] ?? null) ? $input['images'] : [];

        $pdo->beginTransaction();

        $insert = $pdo->prepare(
            'INSERT INTO products (title, slug, subtitle, ribbon, summary, description, price, discount_price, stock, tags, weight_kg, allow_personalization, category_id, subcategory_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $insert->execute([
            $title,
            $slug,
            $subtitle,
            $ribbon,
            $summary,
            $description,
            $price,
            $discountPrice !== '' ? $discountPrice : null,
            $stock,
            $tags,
            $weightKg !== '' ? $weightKg : null,
            $allowPersonalization ? 1 : 0,
            $categoryList[0] ?? null,
            $subcategoryList[0] ?? null,
        ]);

        $productId = (int) $pdo->lastInsertId();

        if ($categoryList) {
            resetLinks($pdo, 'product_categories', 'category_id', $productId, $categoryList);
        }
        if ($subcategoryList) {
            resetLinks($pdo, 'product_subcategories', 'subcategory_id', $productId, $subcategoryList);
        }
        if ($variationList) {
            resetLinks($pdo, 'product_variations', 'variation_value_id', $productId, $variationList);
        }
        if ($relatedList) {
            $filtered = array_values(array_filter($relatedList, static fn($id) => (int) $id !== $productId));
            if ($filtered) {
                resetLinks($pdo, 'product_related', 'related_product_id', $productId, $filtered);
            }
        }
        if ($images) {
            insertImages($pdo, $productId, $images);
        }

        $pdo->commit();

        respond_json(['id' => $productId], 201);
    }

    if ($method === 'PUT' && count($segments) === 1 && ctype_digit($segments[0])) {
        require_admin($pdo);

        $title = $input['title'] ?? null;
        $slug = $input['slug'] ?? null;
        $price = $input['price'] ?? null;

        if (!$title || !$slug || $price === null) {
            respond_json(['message' => 'Title, slug and price are required'], 400);
        }

        $subtitle = $input['subtitle'] ?? '';
        $ribbon = $input['ribbon'] ?? '';
        $summary = $input['summary'] ?? '';
        $description = $input['description'] ?? '';
        $discountPrice = $input['discount_price'] ?? null;
        $stock = $input['stock'] ?? 0;
        $tags = $input['tags'] ?? '';
        $weightKg = $input['weight_kg'] ?? null;
        $allowPersonalization = !empty($input['allow_personalization']);
        $categoryList = toNumericArray(isset($input['categories']) ? $input['categories'] : (isset($input['category_id']) ? [$input['category_id']] : []));
        $subcategoryList = toNumericArray(isset($input['subcategories']) ? $input['subcategories'] : (isset($input['subcategory_id']) ? [$input['subcategory_id']] : []));
        $variationList = toNumericArray($input['variation_value_ids'] ?? []);
        $relatedList = toNumericArray($input['related_product_ids'] ?? []);
        $images = is_array($input['images'] ?? null) ? $input['images'] : [];

        $productId = (int) $segments[0];

        $pdo->beginTransaction();

        $check = $pdo->prepare('SELECT id FROM products WHERE id = ?');
        $check->execute([$productId]);
        if (!$check->fetch()) {
            $pdo->rollBack();
            respond_json(['message' => 'Product not found'], 404);
        }

        $update = $pdo->prepare(
            'UPDATE products SET
                title = ?, slug = ?, subtitle = ?, ribbon = ?, summary = ?, description = ?,
                price = ?, discount_price = ?, stock = ?, tags = ?, weight_kg = ?, allow_personalization = ?,
                category_id = ?, subcategory_id = ?
             WHERE id = ?'
        );

        $update->execute([
            $title,
            $slug,
            $subtitle,
            $ribbon,
            $summary,
            $description,
            $price,
            $discountPrice !== '' ? $discountPrice : null,
            $stock,
            $tags,
            $weightKg !== '' ? $weightKg : null,
            $allowPersonalization ? 1 : 0,
            $categoryList[0] ?? null,
            $subcategoryList[0] ?? null,
            $productId,
        ]);

        resetLinks($pdo, 'product_categories', 'category_id', $productId, $categoryList);
        resetLinks($pdo, 'product_subcategories', 'subcategory_id', $productId, $subcategoryList);
        resetLinks($pdo, 'product_variations', 'variation_value_id', $productId, $variationList);

        $filteredRelated = array_values(array_filter($relatedList, static fn($id) => (int) $id !== $productId));
        resetLinks($pdo, 'product_related', 'related_product_id', $productId, $filteredRelated);

        if (is_array($input['images'] ?? null)) {
            $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$productId]);
            insertImages($pdo, $productId, $images);
        }

        $pdo->commit();

        respond_json(['id' => $productId, 'updated' => true]);
    }

    if ($method === 'DELETE' && count($segments) === 1 && ctype_digit($segments[0])) {
        require_admin($pdo);

        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([(int) $segments[0]]);
        if ($stmt->rowCount() === 0) {
            respond_json(['message' => 'Product not found'], 404);
        }

        respond_json(['success' => true]);
    }

    if ($method === 'POST' && count($segments) === 2 && ctype_digit($segments[0]) && $segments[1] === 'images') {
        require_admin($pdo);

        $productId = (int) $segments[0];
        $imageUrl = $input['image_url'] ?? null;
        $isPrimary = !empty($input['is_primary']);

        if (!$imageUrl) {
            respond_json(['message' => 'Image URL is required'], 400);
        }

        if ($isPrimary) {
            $pdo->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = ?')->execute([$productId]);
        }

        $stmt = $pdo->prepare('INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)');
        $stmt->execute([$productId, $imageUrl, $isPrimary ? 1 : 0]);

        respond_json(['id' => (int) $pdo->lastInsertId()], 201);
    }

    if ($method === 'DELETE' && count($segments) === 3 && ctype_digit($segments[0]) && $segments[1] === 'images' && ctype_digit($segments[2])) {
        require_admin($pdo);

        $stmt = $pdo->prepare('DELETE FROM product_images WHERE product_id = ? AND id = ?');
        $stmt->execute([(int) $segments[0], (int) $segments[2]]);
        if ($stmt->rowCount() === 0) {
            respond_json(['message' => 'Image not found'], 404);
        }

        respond_json(['success' => true]);
    }

    respond_json(['message' => 'Not Found'], 404);
} catch (Throwable $e) {
    error_log('Products API error: ' . $e->getMessage());
    respond_json(['message' => 'Server error', 'detail' => $e->getMessage()], 500);
}
