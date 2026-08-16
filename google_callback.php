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

    if (!$user) {
        // Auto-register user in DB
        $username = strtolower(explode('@', $email)[0]);
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
    }

    if (isset($user['status']) && (int)$user['status'] === 0) {
        die("Tài khoản của bạn đã bị khóa bởi Quản trị viên!");
    }

    // Automatically send Voucher Email for Google registration (first-time only) & add to subscribers
    $stmtSub = $pdo->prepare("SELECT id FROM subscribers WHERE email = ?");
    $stmtSub->execute([$email]);
    $subExists = $stmtSub->fetch();

    if (!$subExists) {
        $stmtSubIns = $pdo->prepare("INSERT INTO subscribers (email, voucher_sent) VALUES (?, 'WELCOME15')");
        $stmtSubIns->execute([$email]);
        send_voucher_email($email, $fullname);
        $_SESSION['custom_notice'] = "🎉 Đăng nhập Google thành công! Bộ đôi Voucher 15% & FREESHIP đã được gửi tới Email " . $email . " của bạn!";
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
