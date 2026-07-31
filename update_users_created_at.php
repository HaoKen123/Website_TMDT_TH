<?php
require_once 'db.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER avatar_url;");
    echo "Đã thêm cột created_at vào bảng users thành công!";
} catch (Exception $e) {
    echo "Thông báo: Cột created_at đã tồn tại hoặc: " . $e->getMessage();
}
?>
