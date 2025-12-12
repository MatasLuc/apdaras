<?php
// Common MySQL helpers for the PHP front-end pages.

declare(strict_types=1);

function env_value(string $key, string $default): string
{
    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}

function get_db_connection(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env_value('DB_HOST', 'localhost');
    $port = env_value('DB_PORT', '3306');
    $user = env_value('DB_USER', 'root');
    $password = env_value('DB_PASSWORD', 'slaptazodis');
    $database = env_value('DB_NAME', 'apdaras');

    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $pdo->exec(
        "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
    $pdo->exec("USE `{$database}`");

    ensure_users_table($pdo);

    return $pdo;
}

function ensure_users_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(150) NOT NULL,
            role ENUM('customer', 'admin') DEFAULT 'customer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function find_user_by_email(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT id, email, password_hash, name, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function create_user(PDO $pdo, string $name, string $email, string $password): int
{
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => password_cost()]);

    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, $hash]);

    return (int) $pdo->lastInsertId();
}

function password_cost(): int
{
    $rounds = env_value('BCRYPT_ROUNDS', '10');
    $cost = is_numeric($rounds) ? (int) $rounds : 10;

    return max(4, min($cost, 31));
}
