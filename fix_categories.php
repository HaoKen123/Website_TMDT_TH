<?php
require_once 'db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        status TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("INSERT INTO categories (id, name, slug, status) VALUES 
        (1, 'Quần áo & Hoodies', 'clothing', 1),
        (2, 'Phụ kiện Minecraft', 'accessories', 1),
        (3, 'Đồ chơi & Gấu bông', 'toys', 1),
        (4, 'Đèn & Trang trí', 'decor', 1)
        ON DUPLICATE KEY UPDATE name=VALUES(name), status=1;
    ");

    echo "<h2 style='color:green;'>✓ Đã khởi tạo thành công 4 Danh mục chuẩn vào CSDL Localhost!</h2>";
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage();
}
