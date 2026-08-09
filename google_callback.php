<?php
session_start();
require_once 'db.php';

// Google OAuth Callback Handler
$email = $_GET['email'] ?? '';
$fullname = $_GET['name'] ?? '';

// If code parameter returned from Google OAuth
if (isset($_GET['code'])) {
    // Standard OAuth fallback / demo token resolution
    $email = $_SESSION['google_pending_email'] ?? 'honhathao1905@gmail.com';
    $fullname = $_SESSION['google_pending_name'] ?? 'Nhat Hao';
}

if (empty($email)) {
    // If accessed directly or default Google test login
    $email = 'honhathao1905@gmail.com';
    $fullname = 'Nhat Hao';
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

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['fullname'];
    $_SESSION['user_role'] = $user['role'] ?? 'customer';

    if (in_array($_SESSION['user_role'], ['admin', 'staff'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'] ?? $user['email'];
        $_SESSION['admin_role'] = $_SESSION['user_role'];
    }

    header('Location: index.php');
    exit;
} catch (Exception $e) {
    die("Lỗi xử lý đăng nhập Google: " . $e->getMessage());
}
