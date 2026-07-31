<?php
require_once 'db.php';

try {
    // Add reset_otp and reset_expiry to users table if not exists
    $pdo->exec("ALTER TABLE users ADD COLUMN reset_otp VARCHAR(10) DEFAULT NULL AFTER address;");
    $pdo->exec("ALTER TABLE users ADD COLUMN reset_expiry DATETIME DEFAULT NULL AFTER reset_otp;");
    echo "Đã thêm cột reset_otp và reset_expiry vào bảng users thành công!";
} catch (Exception $e) {
    echo "Thông báo: Cột reset_otp đã tồn tại hoặc: " . $e->getMessage();
}
?>
