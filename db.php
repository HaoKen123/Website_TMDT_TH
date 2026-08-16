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
    // 2. Cấu hình CSDL Chạy trên Host Thật (InfinityFree)
    $host = 'sql202.infinityfree.com';
    $db   = 'if0_42613531_pixelgear';
    $user = 'if0_42613531';
    $pass = 'hqUiibOaHn';
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

/**
 * Helper: Load cart from MySQL database for a specific user into $_SESSION['cart']
 */
function sync_user_cart_load($pdo, $user_id) {
    if (!$user_id || !$pdo) return;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_carts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            UNIQUE KEY user_prod_uniq (user_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $pdo->prepare("SELECT product_id, quantity FROM user_carts WHERE user_id = ?");
        $stmt->execute([(int)$user_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $_SESSION['cart'] = [];
        foreach ($rows as $r) {
            $pId = (int)$r['product_id'];
            $qty = (int)$r['quantity'];
            if ($pId > 0 && $qty > 0) {
                $_SESSION['cart'][$pId] = $qty;
            }
        }
    } catch (Exception $e) {}
}

/**
 * Helper: Save current $_SESSION['cart'] to MySQL database for a specific user
 */
function sync_user_cart_save($pdo, $user_id) {
    if (!$user_id || !$pdo) return;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_carts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            UNIQUE KEY user_prod_uniq (user_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmtDel = $pdo->prepare("DELETE FROM user_carts WHERE user_id = ?");
        $stmtDel->execute([(int)$user_id]);
        
        if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            $stmtIns = $pdo->prepare("INSERT INTO user_carts (user_id, product_id, quantity) VALUES (?, ?, ?)");
            foreach ($_SESSION['cart'] as $pId => $qty) {
                $pId = (int)$pId;
                $qty = (int)$qty;
                if ($pId > 0 && $qty > 0) {
                    $stmtIns->execute([(int)$user_id, $pId, $qty]);
                }
            }
        }
    } catch (Exception $e) {}
}
?>
