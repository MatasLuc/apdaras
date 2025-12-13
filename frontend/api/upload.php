<?php
// Upload API migrated from Node backend to PHP.

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

function require_admin(PDO $pdo): array
{
    $user = require_login($pdo);
    if (($user['role'] ?? '') !== 'admin') {
        respond_json(['message' => 'Forbidden'], 403);
    }

    return $user;
}

function detect_scheme(): string
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return 'https';
    }

    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $proto = strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']);
        if ($proto === 'https' || $proto === 'http') {
            return $proto;
        }
    }

    return 'http';
}

$pdo = get_db_connection();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$input = json_decode(file_get_contents('php://input') ?: 'null', true);
$input = is_array($input) ? $input : [];

if ($method !== 'POST') {
    respond_json(['message' => 'Method Not Allowed'], 405);
}

require_admin($pdo);

$fileName = $input['fileName'] ?? null;
$dataUrl = $input['dataUrl'] ?? null;

if (!$fileName || !$dataUrl) {
    respond_json(['message' => 'fileName ir dataUrl yra privalomi'], 400);
}

if (!preg_match('/^data:(.*?);base64,(.*)$/', (string) $dataUrl, $matches)) {
    respond_json(['message' => 'Neteisingas failo formatas'], 400);
}

$mime = $matches[1] !== '' ? $matches[1] : 'application/octet-stream';
$base64 = $matches[2];
$buffer = base64_decode($base64, true);

if ($buffer === false) {
    respond_json(['message' => 'Neteisingas failo formatas'], 400);
}

$uploadDir = dirname(__DIR__) . '/upload';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        respond_json(['message' => 'Nepavyko sukurti aplanko'], 500);
    }
}

$safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', (string) $fileName);
$finalName = time() . '-' . $safeName;
$targetPath = $uploadDir . '/' . $finalName;

if (file_put_contents($targetPath, $buffer) === false) {
    respond_json(['message' => 'Nepavyko įrašyti failo'], 500);
}

$scheme = detect_scheme();
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$absoluteUrl = $scheme . '://' . $host . '/upload/' . $finalName;

respond_json([
    'url' => $absoluteUrl,
    'size' => strlen($buffer),
    'mime' => $mime,
], 201);
