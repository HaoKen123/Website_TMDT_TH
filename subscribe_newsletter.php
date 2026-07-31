<?php
session_start();
require_once 'db.php';
require_once 'lang.php';
require_once 'mailer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Phương thức yêu cầu không hợp lệ']);
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (!$email) {
    // Also try JSON body
    $jsonInput = json_decode(file_get_contents('php://input'), true);
    if (isset($jsonInput['email'])) {
        $email = filter_var($jsonInput['email'], FILTER_VALIDATE_EMAIL);
    }
}

if (!$email) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập địa chỉ Email hợp lệ!']);
    exit;
}

try {
    // 1. Check if email exists in users or subscribers table
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmtUser->execute([$email]);
    $userExists = $stmtUser->fetch();

    $stmtSub = $pdo->prepare("SELECT id FROM subscribers WHERE email = ?");
    $stmtSub->execute([$email]);
    $subExists = $stmtSub->fetch();

    if ($userExists || $subExists) {
        // Case B: Already registered/subscribed -> Gentle reminder!
        echo json_encode([
            'status' => 'already_registered',
            'message' => 'Tài khoản Email (' . $email . ') đã được nhận Voucher ưu đãi 15% & FREESHIP rồi nhé! Bạn hãy sử dụng mã WELCOME15 & FREESHIP khi thanh toán.',
            'voucher' => 'WELCOME15'
        ]);
        exit;
    } else {
        // Case A: Not registered yet -> Redirect to registration page with pre-filled email
        echo json_encode([
            'status' => 'not_registered',
            'redirect' => 'register.php?email=' . urlencode($email),
            'message' => 'Email chưa có tài khoản. Đang chuyển tới trang Đăng Ký để tạo tài khoản & nhận ngay bộ đôi Voucher 15% + Freeship!'
        ]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi xử lý: ' . $e->getMessage()]);
}
?>
