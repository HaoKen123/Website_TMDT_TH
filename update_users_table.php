<?php
require_once 'db.php';

try {
    // Add email column to users table if not exists
    $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL AFTER username;");
    echo "Đã thêm cột email vào bảng users thành công!";
} catch (Exception $e) {
    echo "Thông báo: Cột email đã tồn tại hoặc: " . $e->getMessage();
}
?>
