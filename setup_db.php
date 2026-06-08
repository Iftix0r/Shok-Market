<?php
require_once 'config.php';
require_once 'db.php';

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db_name`");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id BIGINT PRIMARY KEY,
        first_name VARCHAR(255),
        username VARCHAR(255),
        photo_url TEXT,
        is_banned TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // is_banned ustunini qo'shish (eski jadval uchun)
    try { $pdo->exec("ALTER TABLE users ADD COLUMN is_banned TINYINT DEFAULT 0 AFTER photo_url"); } catch (PDOException $e) {}

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price INT NOT NULL,
        unit VARCHAR(50) NOT NULL,
        category VARCHAR(50) NOT NULL,
        image TEXT NOT NULL,
        status TINYINT DEFAULT 1
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT,
        total_price INT NOT NULL,
        phone VARCHAR(50) NULL,
        address TEXT NULL,
        status VARCHAR(50) DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    try { $pdo->exec("ALTER TABLE orders ADD COLUMN phone VARCHAR(50) NULL AFTER total_price"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN address TEXT NULL AFTER phone"); } catch (PDOException $e) {}

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        product_id INT,
        quantity INT NOT NULL,
        price INT NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");

    // Uploads papkasini yaratish
    $uploadsDir = __DIR__ . '/uploads';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
        file_put_contents($uploadsDir . '/.htaccess', "Options -Indexes\n");
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO products (name, price, unit, category, image) VALUES
            ('Sariq Banan', 14500, 'so\\'m/kg', 'fruits', 'https://images.unsplash.com/photo-1603833665858-e61d17a86224?auto=format&fit=crop&q=80&w=200&h=200'),
            ('Qizil Pomidor', 9500, 'so\\'m/kg', 'vegetables', 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?auto=format&fit=crop&q=80&w=200&h=200'),
            ('Yangi Sut 1L', 12000, 'so\\'m/dona', 'dairy', 'https://images.unsplash.com/photo-1550583724-b2692b85b150?auto=format&fit=crop&q=80&w=200&h=200'),
            ('Issiq Non', 3500, 'so\\'m/dona', 'bakery', 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&q=80&w=200&h=200'),
            ('Sprite Ichimlik', 8500, 'so\\'m/dona', 'drinks', 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&q=80&w=200&h=200'),
            ('Yangi Bodring', 7000, 'so\\'m/kg', 'vegetables', 'https://images.unsplash.com/photo-1604977042946-1eecc30f269e?auto=format&fit=crop&q=80&w=200&h=200'),
            ('Go\\'sht (mol)', 85000, 'so\\'m/kg', 'meat', 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?auto=format&fit=crop&q=80&w=200&h=200'),
            ('Tvorog', 18000, 'so\\'m/kg', 'dairy', 'https://images.unsplash.com/photo-1559561853-08451507cbe7?auto=format&fit=crop&q=80&w=200&h=200')
        ");
    }

    echo "✅ Ma'lumotlar bazasi va jadvallar muvaffaqiyatli o'rnatildi/yangilandi!";

} catch (PDOException $e) {
    die("Xatolik yuz berdi: " . $e->getMessage());
}
?>
