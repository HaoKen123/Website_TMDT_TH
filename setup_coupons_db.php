<?php
require_once 'db.php';

try {
    // 1. Create coupons table
    $pdo->exec("CREATE TABLE IF NOT EXISTS coupons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        discount_type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
        discount_value DECIMAL(10, 2) NOT NULL,
        min_order DECIMAL(10, 2) DEFAULT 0,
        status ENUM('active', 'expired') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Create subscribers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        voucher_sent VARCHAR(50) DEFAULT 'WELCOME15',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Seed default coupons if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM coupons");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO coupons (code, discount_type, discount_value, min_order, status) VALUES
            ('WELCOME15', 'percent', 15.00, 0, 'active'),
            ('FREESHIP', 'fixed', 2.00, 10.00, 'active'),
            ('PIXEL10', 'percent', 10.00, 0, 'active');");
    }

    echo "Kởi tạo bảng coupons và subscribers thành công!";
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>
