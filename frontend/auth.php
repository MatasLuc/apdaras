<?php
// Session helpers for authentication-aware pages.

declare(strict_types=1);

function ensure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 7, // 7 days
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function current_user(PDO $pdo): ?array
{
    ensure_session();

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function require_login(PDO $pdo): array
{
    $user = current_user($pdo);
    if (!$user) {
        header('Location: prisijungimas.php');
        exit;
    }

    return $user;
}

function logout_and_redirect(): void
{
    ensure_session();
    session_unset();
    session_destroy();
    header('Location: prisijungimas.php');
    exit;
}
