<?php
// db.php - Database connection and automatic table creation

declare(strict_types=1);

// Įjungiame klaidų rodymą, kol viskas veiks stabiliai
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
        die('<h1>DB Klaida:</h1> ' . $e->getMessage());
    }

    // Automatiškai užtikriname, kad lentelės egzistuoja
    ensure_all_tables($pdo);

    return $pdo;
}

function ensure_all_tables(PDO $pdo): void
{
    $queries = [
        // 1. Users
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

        // 2. Categories
        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // 3. Subcategories
        "CREATE TABLE IF NOT EXISTS subcategories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // 4. Products
        "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            subtitle VARCHAR(255),
            ribbon VARCHAR(50),
            summary TEXT,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            discount_price DECIMAL(10, 2) DEFAULT NULL,
            stock INT DEFAULT 0,
            tags VARCHAR(255),
            weight_kg DECIMAL(10, 3),
            allow_personalization TINYINT(1) DEFAULT 0,
            category_id INT DEFAULT NULL,
            subcategory_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // 5-7. Product Relations
        "CREATE TABLE IF NOT EXISTS product_categories (
            product_id INT NOT NULL,
            category_id INT NOT NULL,
            PRIMARY KEY (product_id, category_id),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS product_subcategories (
            product_id INT NOT NULL,
            subcategory_id INT NOT NULL,
            PRIMARY KEY (product_id, subcategory_id),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS product_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            is_primary TINYINT(1) DEFAULT 0,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // 8-10. Variations
        "CREATE TABLE IF NOT EXISTS variation_attributes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS variation_values (
            id INT AUTO_INCREMENT PRIMARY KEY,
            variation_attribute_id INT NOT NULL,
            value VARCHAR(100) NOT NULL,
            FOREIGN KEY (variation_attribute_id) REFERENCES variation_attributes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS product_variations (
            product_id INT NOT NULL,
            variation_value_id INT NOT NULL,
            PRIMARY KEY (product_id, variation_value_id),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (variation_value_id) REFERENCES variation_values(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS product_related (
            product_id INT NOT NULL,
            related_product_id INT NOT NULL,
            PRIMARY KEY (product_id, related_product_id),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (related_product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        // 11-14. Carts & Orders
        "CREATE TABLE IF NOT EXISTS carts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(255) NOT NULL,
            user_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (session_id),
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS cart_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cart_id INT NOT NULL,
            product_id INT NOT NULL,
            variation_id INT DEFAULT NULL,
            quantity INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            guest_name VARCHAR(255),
            guest_email VARCHAR(255),
            guest_address TEXT,
            total_price DECIMAL(10, 2) NOT NULL,
            status ENUM('new', 'paid', 'shipped', 'completed', 'cancelled') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT DEFAULT NULL,
            product_name VARCHAR(255) NOT NULL,
            variation_info VARCHAR(255) DEFAULT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
        "CREATE TABLE IF NOT EXISTS coupons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            discount_type ENUM('percent', 'fixed') NOT NULL,
            discount_value DECIMAL(10, 2) NOT NULL,
            expires_at DATETIME DEFAULT NULL,
            usage_limit INT DEFAULT NULL,
            times_used INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS shipping_methods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            estimated_days VARCHAR(50)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];

    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
        } catch (PDOException $e) {
            die("<h1>Lentelės kūrimo klaida</h1><p>Nepavyko įvykdyti užklausos:</p><pre>$query</pre><p>Klaida: " . $e->getMessage() . "</p>");
        }
    }

    // --- SVARBU: Atnaujiname senas lenteles (ALTER TABLE) ---
    try {
        // Users
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('customer', 'admin') DEFAULT 'customer'");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS birthdate DATE DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS gender ENUM('male', 'female', 'unspecified') DEFAULT 'unspecified'");
        
        // Cart Items (Jūsų klaidos sprendimas)
        $pdo->exec("ALTER TABLE cart_items ADD COLUMN IF NOT EXISTS variation_id INT DEFAULT NULL");
        
        // Order Items (Prevencija)
        $pdo->exec("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS variation_info VARCHAR(255) DEFAULT NULL");
        
    } catch (PDOException $e) {
        // Ignoruojame klaidas, jei stulpeliai jau yra, arba duomenų bazė nepalaiko 'IF NOT EXISTS' sintaksės ALTER komandoje
        // (MariaDB 10.2+ ir MySQL 8.0+ palaiko)
    }
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
