<?php
session_start();
require_once 'db.php';
require_once 'mailer.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Thông tin đăng nhập Google không hợp lệ!']);
    exit;
}

$email = trim($data['email']);
$fullname = trim($data['name'] ?? 'Thành viên Google');

try {
    // 1. Check if user exists by email
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    $isNewUser = false;
    if (!$user) {
        $isNewUser = true;
        // Auto-register user
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
    }

    if (isset($user['status']) && (int)$user['status'] === 0) {
        echo json_encode(['success' => false, 'message' => 'Tài khoản của bạn đã bị khóa bởi Quản trị viên!']);
        exit;
    }

    // 2. Automatically send Voucher Email for Google registration (first-time only) & add to subscribers
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

    echo json_encode(['success' => true, 'redirect' => 'index.php']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>
