import mysql from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config();

const DEFAULT_DB_CONFIG = {
  DB_HOST: 'localhost',
  DB_PORT: '3306',
  DB_USER: 'root',
  DB_PASSWORD: '',
  DB_NAME: 'apdaras'
};

const TABLE_QUERIES = [
  `CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(150) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    birthdate DATE DEFAULT NULL,
    address TEXT DEFAULT NULL,
    gender ENUM('male', 'female', 'unspecified') DEFAULT 'unspecified',
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )`,
  `CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE
  )`,
  `CREATE TABLE IF NOT EXISTS subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
  )`,
  `CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    subtitle VARCHAR(255) DEFAULT '',
    ribbon VARCHAR(120) DEFAULT '',
    summary VARCHAR(300) DEFAULT '',
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    stock INT DEFAULT 0,
    tags VARCHAR(255) DEFAULT '',
    weight_kg DECIMAL(8,3) DEFAULT NULL,
    allow_personalization TINYINT(1) DEFAULT 0,
    category_id INT NULL,
    subcategory_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL
  )`,
  `CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
  )`,
  `CREATE TABLE IF NOT EXISTS product_categories (
    product_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (product_id, category_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
  )`,
  `CREATE TABLE IF NOT EXISTS product_subcategories (
    product_id INT NOT NULL,
    subcategory_id INT NOT NULL,
    PRIMARY KEY (product_id, subcategory_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
  )`,
  `CREATE TABLE IF NOT EXISTS variation_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
  )`,
  `CREATE TABLE IF NOT EXISTS variation_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variation_attribute_id INT NOT NULL,
    value VARCHAR(120) NOT NULL,
    UNIQUE KEY unique_value (variation_attribute_id, value),
    FOREIGN KEY (variation_attribute_id) REFERENCES variation_attributes(id) ON DELETE CASCADE
  )`,
  `CREATE TABLE IF NOT EXISTS product_variations (
    product_id INT NOT NULL,
    variation_value_id INT NOT NULL,
    PRIMARY KEY (product_id, variation_value_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variation_value_id) REFERENCES variation_values(id) ON DELETE CASCADE
  )`,
  `CREATE TABLE IF NOT EXISTS product_related (
    product_id INT NOT NULL,
    related_product_id INT NOT NULL,
    PRIMARY KEY (product_id, related_product_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (related_product_id) REFERENCES products(id) ON DELETE CASCADE
  )`,
  `CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(80) NOT NULL UNIQUE,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    expires_at DATETIME NULL,
    usage_limit INT NULL,
    times_used INT DEFAULT 0
  )`,
  `CREATE TABLE IF NOT EXISTS shipping_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    estimated_days INT NULL
  )`,
  `CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    UNIQUE KEY unique_item (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
  )`
];

const MIGRATIONS = [
  `ALTER TABLE products ADD COLUMN IF NOT EXISTS subtitle VARCHAR(255) DEFAULT '' AFTER slug`,
  `ALTER TABLE products ADD COLUMN IF NOT EXISTS ribbon VARCHAR(120) DEFAULT '' AFTER subtitle`,
  `ALTER TABLE products ADD COLUMN IF NOT EXISTS discount_price DECIMAL(10,2) DEFAULT NULL AFTER price`,
  `ALTER TABLE products ADD COLUMN IF NOT EXISTS weight_kg DECIMAL(8,3) DEFAULT NULL AFTER tags`,
  `ALTER TABLE products ADD COLUMN IF NOT EXISTS allow_personalization TINYINT(1) DEFAULT 0 AFTER weight_kg`,
  `ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL AFTER role`,
  `ALTER TABLE users ADD COLUMN IF NOT EXISTS birthdate DATE DEFAULT NULL AFTER profile_image`,
  `ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL AFTER birthdate`,
  `ALTER TABLE users ADD COLUMN IF NOT EXISTS gender ENUM('male', 'female', 'unspecified') DEFAULT 'unspecified' AFTER address`,
  `INSERT IGNORE INTO product_categories (product_id, category_id)
   SELECT id, category_id FROM products WHERE category_id IS NOT NULL`,
  `INSERT IGNORE INTO product_subcategories (product_id, subcategory_id)
   SELECT id, subcategory_id FROM products WHERE subcategory_id IS NOT NULL`
];

let pool;

function resolveConfig() {
  const config = { ...DEFAULT_DB_CONFIG };

  Object.keys(DEFAULT_DB_CONFIG).forEach((key) => {
    const value = process.env[key];
    if (typeof value === 'string' && value !== '') {
      config[key] = value;
    }
  });

  return config;
}

export async function initDb() {
  if (pool) return pool;
  const { DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME } = resolveConfig();
  let connection;
  try {
    connection = await mysql.createConnection({
      host: DB_HOST,
      port: DB_PORT,
      user: DB_USER,
      password: DB_PASSWORD
    });
  } catch (err) {
    throw new Error(
      `Nepavyko prisijungti prie duomenų bazės (${DB_HOST}:${DB_PORT}) naudotoju ${DB_USER}: ${err.message}`
    );
  }

  await connection.query(
    `CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`
  );
  await connection.query(`USE \`${DB_NAME}\``);

  for (const statement of TABLE_QUERIES) {
    await connection.query(statement);
  }

  for (const migration of MIGRATIONS) {
    await connection.query(migration);
  }

  await connection.end();

  pool = mysql.createPool({
    host: DB_HOST,
    port: DB_PORT,
    user: DB_USER,
    password: DB_PASSWORD,
    database: DB_NAME,
    connectionLimit: 10
  });

  return pool;
}

export function getPool() {
  if (!pool) {
    throw new Error('Duomenų bazė dar neinicijuota. Paleiskite initDb() prieš užklausas.');
  }
  return pool;
}

