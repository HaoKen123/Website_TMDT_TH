<?php
session_start();
require_once 'db.php';

$msg = '';
$error = '';
$email = filter_input(INPUT_GET, 'email', FILTER_VALIDATE_EMAIL);

if (isset($_GET['sent']) && $_GET['sent'] == 1) {
    $msg = "Mã OTP 6 chữ số đã được gửi tới Email " . htmlspecialchars($email) . ". Vui lòng kiểm tra hòm thư!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $otp = trim($_POST['otp']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!$email || empty($otp)) {
        $error = "Vui lòng nhập Email và mã OTP!";
    } elseif ($new_password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp nhau!";
    } elseif (strlen($new_password) < 6) {
        $error = "Mật khẩu mới phải có tối thiểu 6 ký tự!";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND reset_otp = ? AND reset_expiry > NOW()");
        $stmt->execute([$email, $otp]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Mã OTP không chính xác hoặc đã hết hạn!";
        } else {
            // Update password & clear OTP
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $stmtUpdate = $pdo->prepare("UPDATE users SET password = ?, reset_otp = NULL, reset_expiry = NULL WHERE id = ?");
            $stmtUpdate->execute([$hashedPassword, $user['id']]);

            header('Location: login.php?msg=reset_success');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt Lại Mật Khẩu | PixelGear Store</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; }
        .auth-card { max-width: 440px; margin: 50px auto; padding: 35px; background: white; border-radius: 12px; border: 1px solid var(--border-color); text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .auth-card input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; font-size: 14px; outline: none; }
        .auth-card input:focus { border-color: #15803d; }
        .auth-card button { width: 100%; padding: 14px; margin-bottom: 15px; font-size: 15px; font-weight: 700; background: #15803d; border: none; border-radius: 6px; color: #fff; cursor: pointer; }
        .auth-card button:hover { background: #166534; }
        .alert-success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; font-size: 13px; text-align: left; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; font-size: 13px; text-align: left; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2 style="margin-bottom: 8px; color: #0f172a;"><i class="fas fa-lock" style="color: #15803d;"></i> ĐẶT LẠI MẬT KHẨU</h2>
        <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Nhập mã OTP 6 chữ số từ Email của bạn và tạo mật khẩu mới.</p>

        <?php if($msg) echo "<div class='alert-success'><i class='fas fa-check-circle'></i> $msg</div>"; ?>
        <?php if($error) echo "<div class='alert-error'><i class='fas fa-exclamation-circle'></i> $error</div>"; ?>

        <form method="POST">
            <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" placeholder="Địa chỉ Email" required>
            <input type="text" name="otp" placeholder="Mã OTP 6 chữ số (VD: 123456)" maxlength="6" style="letter-spacing: 3px; font-weight: bold; text-align: center;" required>
            <input type="password" name="new_password" placeholder="Mật khẩu mới" required>
            <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu mới" required>
            <button type="submit">CẬP NHẬT MẬT KHẨU MỚI</button>
        </form>

        <p style="font-size: 13px; color: #64748b; margin-top: 15px;">
            Quay lại <a href="login.php" style="color: #15803d; font-weight: 700; text-decoration: none;">Đăng nhập</a>
        </p>
    </div>
</body>
</html>
