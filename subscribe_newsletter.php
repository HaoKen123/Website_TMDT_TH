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
    // 1. Check if email exists in subscribers table
    $stmtSub = $pdo->prepare("SELECT id FROM subscribers WHERE email = ?");
    $stmtSub->execute([$email]);
    $subExists = $stmtSub->fetch();

    if (!$subExists) {
        // Insert into subscribers table
        $stmtIns = $pdo->prepare("INSERT INTO subscribers (email, voucher_sent) VALUES (?, 'WELCOME15')");
        $stmtIns->execute([$email]);
    }

    // 2. Always send or re-send the voucher email!
    $mailRes = send_voucher_email($email);

    if ($subExists) {
        echo json_encode([
            'status' => 'already_registered',
            'message' => 'Mã Voucher 15% & Freeship (WELCOME15 / FREESHIP) đã được gửi lại vào Email (' . $email . ') của bạn rồi nhé!',
            'voucher' => 'WELCOME15'
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'message' => '🎉 Đăng ký thành công! Mã Voucher Giảm 15% & Freeship đã được gửi tới Email (' . $email . '). Bạn hãy kiểm tra hòm thư nhé!',
            'voucher' => 'WELCOME15'
        ]);
    }
    exit;

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi xử lý: ' . $e->getMessage()]);
}
?>
