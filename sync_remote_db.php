<?php
// Remote Database Sync Script for AwardSpace Hosting
$host = 'fdb1030.awardspace.net';
$db   = '4776587_pixelgear';
$user = '4776587_pixelgear';
$pass = 'thapvi123';

echo "<h2>Đang kết nối CSDL từ xa AwardSpace ($host)...</h2>\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "<p style='color:green;'>✓ Kết nối CSDL từ xa Awardspace thành công!</p>\n";
} catch (Exception $e) {
    echo "<p style='color:red;'>Không thể kết nối CSDL từ xa trực tiếp (do Awardspace chặn IP bên ngoài): " . $e->getMessage() . "</p>\n";
    echo "<p>Vui lòng chạy file này bằng cách truy cập trình duyệt sau khi upload.</p>\n";
    exit;
}

// 1. Chạy Migration CSDL
echo "<h3>1. Đang nâng cấp cấu trúc bảng...</h3>\n";
try {
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('status', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1");
    }
    if (!in_array('role', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'customer'");
    }

    $prodColumns = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('stock', $prodColumns)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN stock INT NOT NULL DEFAULT 50");
    }
    if (!in_array('status', $prodColumns)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        status TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("INSERT INTO categories (name, slug, status) VALUES 
        ('Quần áo & Hoodies', 'clothing', 1),
        ('Phụ kiện Minecraft', 'accessories', 1),
        ('Đồ chơi & Gấu bông', 'toys', 1),
        ('Đèn & Trang trí', 'decor', 1)
        ON DUPLICATE KEY UPDATE status = 1
    ");

    echo "<p style='color:green;'>✓ Đã nâng cấp cấu trúc CSDL từ xa thành công!</p>\n";
} catch (Exception $e) {
    echo "<p style='color:orange;'>Lưu ý Migration: " . $e->getMessage() . "</p>\n";
}

// 2. Chèn 40 sản phẩm mới
echo "<h3>2. Đang nạp 40 sản phẩm mới vào CSDL từ xa...</h3>\n";
require_once 'insert_10_per_category.php';
