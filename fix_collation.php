<?php
require_once 'db.php';
try {
    $pdo->exec("ALTER TABLE categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("ALTER TABLE products CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "<h2 style='color:green;'>✓ Đã đồng bộ Collation utf8mb4_unicode_ci cho cả 2 bảng categories & products thành công!</h2>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
