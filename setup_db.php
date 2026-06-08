<?php
require_once 'config.php';
require_once 'db.php';

try {
    // Bazani yaratish
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db_name`");

    // Foydalanuvchilar jadvali
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id BIGINT PRIMARY KEY,
        first_name VARCHAR(255),
        username VARCHAR(255),
        photo_url TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Mahsulotlar jadvali
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price INT NOT NULL,
        unit VARCHAR(50) NOT NULL,
        category VARCHAR(50) NOT NULL,
        image TEXT NOT NULL,
        status TINYINT DEFAULT 1
    )");

    // Buyurtmalar jadvali
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT,
        total_price INT NOT NULL,
        status VARCHAR(50) DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Buyurtma qilingan mahsulotlar
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        product_id INT,
        quantity INT NOT NULL,
        price INT NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");

    // Boshlang'ich mahsulotlarni qo'shish (faqat jadval bo'sh bo'lsa)
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    if ($stmt->fetchColumn() == 0) {
        $insert = "INSERT INTO products (name, price, unit, category, image) VALUES 
            ('Sariq Banan', 14500, 'so\'m/kg', 'fruits', 'https://images.unsplash.com/photo-1603833665858-e61d17a86224?auto=format&fit=crop&q=80&w=200&h=200'),
            ('Qizil Pomidor', 14500, 'so\'m/kg', 'fruits', 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?auto=format&fit=crop&q=80&w=200&h=200'),
            ('Yangi Sut 1L', 14500, 'so\'m/dona', 'meat', 'https://images.unsplash.com/photo-1550583724-b2692b85b150?auto=format&fit=crop&q=80&w=200&h=200'),
            ('Issiq Non', 3500, 'so\'m/dona', 'bakery', 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&q=80&w=200&h=200'),
            ('Srinlutile Ichimlik', 14500, 'so\'m/dona', 'drinks', 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&q=80&w=200&h=200'),
            ('Yangi Bodring', 10500, 'so\'m/kg', 'fruits', 'https://images.unsplash.com/photo-1604977042946-1eecc30f269e?auto=format&fit=crop&q=80&w=200&h=200')
        ";
        $pdo->exec($insert);
    }

    echo "✅ Ma'lumotlar bazasi va jadvallar muvaffaqiyatli o'rnatildi!";

} catch (PDOException $e) {
    die("Xatolik yuz berdi: " . $e->getMessage());
}
?>
