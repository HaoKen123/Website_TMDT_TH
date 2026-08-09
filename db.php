<?php
// Tự động phát hiện môi trường (Local XAMPP vs Online AwardSpace)
$is_local = (
    php_sapi_name() === 'cli' ||
    empty($_SERVER['HTTP_HOST']) ||
    in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || 
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false
);

if ($is_local) {
    // 1. Cấu hình CSDL Chạy trên Máy Cục Bộ (XAMPP)
    $host = 'localhost';
    $db   = 'pixelgear_shop';
    $user = 'root';
    $pass = '';
} else {
    // 2. Cấu hình CSDL Chạy trên Host Thật (AwardSpace)
    $host = 'fdb1030.awardspace.net';
    $db   = '4776587_pixelgear';
    $user = '4776587_pixelgear';
    $pass = 'thapvi123';
}

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>
