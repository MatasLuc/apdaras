<?php
// db.php - Prisijungimas ir automatinis DB struktūros taisymas

declare(strict_types=1);

// Rodyti klaidas (kol vystoma)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

function load_env_from_file(): void
{
    static $loaded = false;
    if ($loaded) return;

    $paths = [__DIR__ . '/../.env', __DIR__ . '/../backend/.env'];

    foreach ($paths as $path) {
        if (!is_readable($path)) continue;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) continue;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);
            if ($key === '' || getenv($key) !== false) continue;
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
        $loaded = true;
        break;
    }
}

function env_value(string $key, string $default): string
{
    load_env_from_file();
    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}

function get_db_connection(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $host = env_value('DB_HOST', 'localhost');
    $port = env_value('DB_PORT', '3306');
    $user = env_value('DB_USER', 'apdarasl_apdaras');
    $password = env_value('DB_PASSWORD', 'Kosmosas420!');
    $database = env_value('DB_NAME', 'apdarasl_apdaras');

    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $e) {
        die('<h1>DB Prisijungimo Klaida:</h1> ' . $e->getMessage());
    }

    // Paleidžiame struktūros patikrinimą
    ensure_schema($pdo);

    return $pdo;
}

function ensure_column(PDO $pdo, string $table, string $columnDef): void
{
    try {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$columnDef}");
    } catch (PDOException $e) {
        // Ignoruojame klaidą, jei stulpelis jau yra
    }
}

function ensure_schema(PDO $pdo): void
{
    // 1. Sukuriame lenteles, jei jų nėra
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(150) NOT NULL,
            role ENUM('customer', 'admin') DEFAULT 'customer',
            profile_image VARCHAR(255) DEFAULT NULL,
            birthdate DATE DEFAULT NULL,
            address TEXT DEFAULT NULL,
            gender ENUM('male', 'female', 'unspecified') DEFAULT 'unspecified',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS subcategories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS product_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            is_primary TINYINT(1) DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS variation_attributes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS variation_values (
            id INT AUTO_INCREMENT PRIMARY KEY,
            variation_attribute_id INT NOT NULL,
            value VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS product_variations (
            product_id INT NOT NULL,
            variation_value_id INT NOT NULL,
            PRIMARY KEY (product_id, variation_value_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS product_categories (
            product_id INT NOT NULL,
            category_id INT NOT NULL,
            PRIMARY KEY (product_id, category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS product_subcategories (
            product_id INT NOT NULL,
            subcategory_id INT NOT NULL,
            PRIMARY KEY (product_id, subcategory_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS product_related (
            product_id INT NOT NULL,
            related_product_id INT NOT NULL,
            PRIMARY KEY (product_id, related_product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS carts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(255) NOT NULL,
            user_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS cart_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cart_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT DEFAULT 1,
            variation_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            total_price DECIMAL(10, 2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10, 2) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
        "CREATE TABLE IF NOT EXISTS coupons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            discount_type ENUM('percent', 'fixed') NOT NULL,
            discount_value DECIMAL(10, 2) NOT NULL,
            expires_at DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS shipping_methods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            estimated_days VARCHAR(50)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];

    foreach ($tables as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $e) {}
    }

    // 2. TAISYMAS: Panaikiname klaidingą stulpelį 'user_id' iš 'cart_items'
    // Jei nepavyksta ištrinti, bent padarome jį nullable, kad netrukdytų
    try {
        $pdo->exec("ALTER TABLE cart_items DROP COLUMN user_id");
    } catch (PDOException $e) {
        try {
            $pdo->exec("ALTER TABLE cart_items MODIFY COLUMN user_id INT NULL DEFAULT NULL");
        } catch (PDOException $e2) {}
    }

    // 3. Užtikriname, kad kiti stulpeliai egzistuoja
    ensure_column($pdo, 'products', "subtitle VARCHAR(255)");
    ensure_column($pdo, 'products', "ribbon VARCHAR(50)");
    ensure_column($pdo, 'products', "summary TEXT");
    ensure_column($pdo, 'products', "description TEXT");
    ensure_column($pdo, 'products', "discount_price DECIMAL(10, 2) DEFAULT NULL");
    ensure_column($pdo, 'products', "stock INT DEFAULT 0");
    ensure_column($pdo, 'products', "tags VARCHAR(255)");
    ensure_column($pdo, 'products', "weight_kg DECIMAL(10, 3)");
    ensure_column($pdo, 'products', "allow_personalization TINYINT(1) DEFAULT 0");
    ensure_column($pdo, 'products', "category_id INT DEFAULT NULL");
    ensure_column($pdo, 'products', "subcategory_id INT DEFAULT NULL");

    ensure_column($pdo, 'cart_items', "cart_id INT NOT NULL");
    ensure_column($pdo, 'cart_items', "variation_id INT DEFAULT NULL");
    
    ensure_column($pdo, 'carts', "user_id INT DEFAULT NULL");
    ensure_column($pdo, 'carts', "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

    ensure_column($pdo, 'orders', "user_id INT DEFAULT NULL");
    ensure_column($pdo, 'orders', "guest_name VARCHAR(255)");
    ensure_column($pdo, 'orders', "guest_email VARCHAR(255)");
    ensure_column($pdo, 'orders', "guest_address TEXT");
    ensure_column($pdo, 'orders', "status ENUM('new', 'paid', 'shipped', 'completed', 'cancelled') DEFAULT 'new'");

    ensure_column($pdo, 'order_items', "product_id INT DEFAULT NULL");
    ensure_column($pdo, 'order_items', "variation_info VARCHAR(255) DEFAULT NULL");
    
    ensure_column($pdo, 'coupons', "usage_limit INT DEFAULT NULL");
    ensure_column($pdo, 'coupons', "times_used INT DEFAULT 0");
}

// ... Toliau funkcijos find_user_by_email, create_user ir t.t. lieka tos pačios ...
function find_user_by_email(PDO $pdo, string $email): ?array {
    $stmt = $pdo->prepare('SELECT id, email, password_hash, name, role, profile_image, birthdate, address, gender FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}
function find_user_by_id(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare('SELECT id, email, password_hash, name, role, profile_image, birthdate, address, gender FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}
function create_user(PDO $pdo, string $name, string $email, string $password): int {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, $hash]);
    return (int) $pdo->lastInsertId();
}
function update_user_profile(PDO $pdo, int $id, string $name, string $email, ?string $birthdate, ?string $address, string $gender, ?string $profileImage): void {
    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, birthdate = ?, address = ?, gender = ?, profile_image = COALESCE(?, profile_image) WHERE id = ?');
    $stmt->execute([$name, $email, $birthdate, $address, $gender, $profileImage, $id]);
}
function update_user_password(PDO $pdo, int $id, string $password): void {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([$hash, $id]);
}
?>
