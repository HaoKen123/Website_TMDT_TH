<?php
session_start();
require_once 'db.php';
require_once 'mailer.php';

// Google OAuth Callback Handler
$email = '';
$fullname = '';

if (isset($_POST['credential'])) {
    $jwt = $_POST['credential'];
    $parts = explode('.', $jwt);
    if (count($parts) === 3) {
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
        if ($payload && isset($payload['email'])) {
            $email = $payload['email'];
            $fullname = $payload['name'] ?? 'Thành viên Google';
        }
    }
} else if (isset($_GET['code'])) {
    // Fallback for mocked google_oauth.php simulator
    $email = $_SESSION['google_pending_email'] ?? '';
    $fullname = $_SESSION['google_pending_name'] ?? 'Thành viên Google';
}

if (empty($email)) {
    die("Không thể lấy được địa chỉ Email từ Google. Vui lòng thử lại!");
}

try {
    // Check if user exists by email in MySQL
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    $isNewUser = false;
    if (!$user) {
        $isNewUser = true;
        // Auto-register user in DB
        $username = strtolower(explode('@', $email)[0]) . rand(100, 999);
        $dummy_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        
        $stmtIns = $pdo->prepare('INSERT INTO users (username, email, password, fullname, status, role) VALUES (?, ?, ?, ?, 1, "customer")');
        $stmtIns->execute([$username, $email, $dummy_password, $fullname]);
        
        $user_id = $pdo->lastInsertId();
        $user = [
            'id' => $user_id,
            'fullname' => $fullname,
            'role' => 'customer',
            'status' => 1
        ];

        // Gửi Email chào mừng & Tặng voucher cho người dùng đăng nhập Google LẦN ĐẦU TIÊN
        try {
            send_voucher_email($email, $fullname);
            $_SESSION['custom_notice'] = "🎉 Chào mừng bạn gia nhập PixelGear! Bộ đôi Voucher 15% & FREESHIP đã được gửi tới Email " . $email . " của bạn!";
            
            $stmtSubIns = $pdo->prepare("INSERT INTO subscribers (email, voucher_sent) VALUES (?, 'WELCOME15') ON DUPLICATE KEY UPDATE voucher_sent='WELCOME15'");
            $stmtSubIns->execute([$email]);
        } catch (Exception $ex) {}
    }

    if (isset($user['status']) && (int)$user['status'] === 0) {
        die("Tài khoản của bạn đã bị khóa bởi Quản trị viên!");
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['fullname'];
    $_SESSION['user_role'] = $user['role'] ?? 'customer';

    if (in_array($_SESSION['user_role'], ['admin', 'staff'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'] ?? $user['email'];
        $_SESSION['admin_role'] = $_SESSION['user_role'];
    }

    // Isolate and load cart for this user
    sync_user_cart_load($pdo, $user['id']);

    header('Location: index.php');
    exit;
} catch (Exception $e) {
    die("Lỗi xử lý đăng nhập Google: " . $e->getMessage());
}
?>
