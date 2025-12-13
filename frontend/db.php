<?php
// db.php - Prisijungimas ir automatinis struktūros užtikrinimas (Auto-migration)

declare(strict_types=1);

// Rodyti klaidas tik vystymo metu
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

// Pagalbinė funkcija stulpeliui pridėti, jei jo nėra
function ensure_column(PDO $pdo, string $table, string $columnDef): void
{
    try {
        // Bandome pridėti. Jei stulpelis yra, MySQL išmes klaidą "Duplicate column name", kurią mes pagausime ir ignoruosime.
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$columnDef}");
    } catch (PDOException $e) {
        // Ignoruojame klaidą 42S21 (Column already exists)
        if ($e->errorInfo[1] != 1060) {
            // Jei klaida ne apie egzistuojantį stulpelį, galbūt verta ją paminėti loguose, bet dažniausiai tai saugu praleisti
            // error_log("Schema update notice for $table: " . $e->getMessage());
        }
    }
}

function ensure_schema(PDO $pdo): void
{
    // 1. Sukuriame lenteles, jei jų nėra (CREATE TABLE IF NOT EXISTS)
    $createQueries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(150) NOT NULL,
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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

        "CREATE TABLE IF NOT EXISTS product_related (
            product_id INT NOT NULL,
            related_product_id INT NOT NULL,
            PRIMARY KEY (product_id, related_product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS carts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS cart_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cart_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT DEFAULT 1,
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

    foreach ($createQueries as $query) {
        $pdo->exec($query);
    }

    // 2. Užtikriname, kad visi reikalingi stulpeliai egzistuoja (ALTER TABLE ADD COLUMN)
    // Tai ištaisys "Unknown column" klaidas, jei lentelės buvo sukurtos anksčiau be šių laukų.

    // USERS
    ensure_column($pdo, 'users', "role ENUM('customer', 'admin') DEFAULT 'customer'");
    ensure_column($pdo, 'users', "profile_image VARCHAR(255) DEFAULT NULL");
    ensure_column($pdo, 'users', "birthdate DATE DEFAULT NULL");
    ensure_column($pdo, 'users', "address TEXT DEFAULT NULL");
    ensure_column($pdo, 'users', "gender ENUM('male', 'female', 'unspecified') DEFAULT 'unspecified'");

    // PRODUCTS
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

    // CART_ITEMS (Čia buvo jūsų klaida!)
    ensure_column($pdo, 'cart_items', "cart_id INT NOT NULL");
    ensure_column($pdo, 'cart_items', "product_id INT NOT NULL");
    ensure_column($pdo, 'cart_items', "variation_id INT DEFAULT NULL");
    
    // CARTS
    ensure_column($pdo, 'carts', "user_id INT DEFAULT NULL");
    ensure_column($pdo, 'carts', "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

    // ORDERS
    ensure_column($pdo, 'orders', "user_id INT DEFAULT NULL");
    ensure_column($pdo, 'orders', "guest_name VARCHAR(255)");
    ensure_column($pdo, 'orders', "guest_email VARCHAR(255)");
    ensure_column($pdo, 'orders', "guest_address TEXT");
    ensure_column($pdo, 'orders', "status ENUM('new', 'paid', 'shipped', 'completed', 'cancelled') DEFAULT 'new'");

    // ORDER_ITEMS
    ensure_column($pdo, 'order_items', "product_id INT DEFAULT NULL");
    ensure_column($pdo, 'order_items', "variation_info VARCHAR(255) DEFAULT NULL");

    // COUPONS
    ensure_column($pdo, 'coupons', "usage_limit INT DEFAULT NULL");
    ensure_column($pdo, 'coupons', "times_used INT DEFAULT 0");
}

function find_user_by_email(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT id, email, password_hash, name, role, profile_image, birthdate, address, gender FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function find_user_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, email, password_hash, name, role, profile_image, birthdate, address, gender FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
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

function update_user_profile(
    PDO $pdo, int $id, string $name, string $email, ?string $birthdate, ?string $address, string $gender, ?string $profileImage
): void {
    $stmt = $pdo->prepare(
        'UPDATE users SET name = ?, email = ?, birthdate = ?, address = ?, gender = ?, profile_image = COALESCE(?, profile_image) WHERE id = ?'
    );
    $stmt->execute([$name, $email, $birthdate, $address, $gender, $profileImage, $id]);
}

function update_user_password(PDO $pdo, int $id, string $password): void
{
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => password_cost()]);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([$hash, $id]);
}
?>
