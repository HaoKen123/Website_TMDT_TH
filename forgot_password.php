<?php
session_start();
require_once 'db.php';
require_once 'mailer.php';

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $error = "Vui lòng nhập địa chỉ Email hợp lệ!";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Email này chưa được đăng ký tài khoản trong hệ thống!";
        } else {
            // Generate 6-digit OTP code
            $otp = sprintf("%06d", mt_rand(100000, 999999));
            $expiry = date('Y-m-d H:i:s', time() + 15 * 60); // 15 mins expiry

            // Save OTP to DB
            $stmtUpdate = $pdo->prepare("UPDATE users SET reset_otp = ?, reset_expiry = ? WHERE id = ?");
            $stmtUpdate->execute([$otp, $expiry, $user['id']]);

            // Send REAL Email with OTP via Gmail SMTP
            $subject = "🔑 Mã Xác Thực Đặt Lại Mật Khẩu - PixelGear Store";
            $message = '
            <html>
            <head>
              <title>Khôi Phục Mật Khẩu PixelGear</title>
            </head>
            <body style="font-family: Arial, sans-serif; background-color: #f4f7fa; padding: 30px; margin: 0;">
              <div style="max-width: 550px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                <div style="background: #0284c7; padding: 25px; text-align: center; color: #ffffff;">
                  <h1 style="margin: 0; font-size: 24px;">PIXELGEAR STORE</h1>
                  <p style="margin-top: 5px; opacity: 0.9;">Yêu cầu đặt lại mật khẩu tài khoản</p>
                </div>
                <div style="padding: 30px; text-align: center; color: #334155;">
                  <h3 style="color: #0f172a; margin-top: 0;">Xin chào ' . htmlspecialchars($user['fullname']) . ',</h3>
                  <p style="font-size: 14px; line-height: 1.6;">Dưới đây là mã OTP 6 chữ số để đặt lại mật khẩu cho tài khoản của bạn:</p>
                  
                  <div style="background: #f0f9ff; border: 2px dashed #0284c7; padding: 15px 25px; border-radius: 8px; display: inline-block; margin: 20px 0;">
                    <span style="font-size: 32px; font-weight: 800; color: #0284c7; letter-spacing: 5px;">' . $otp . '</span>
                  </div>

                  <p style="font-size: 13px; color: #64748b;">* Mã OTP có hiệu lực trong vòng <strong>15 phút</strong>. Vui lòng không chia sẻ mã này cho bất kỳ ai.</p>
                </div>
                <div style="background: #f8fafc; padding: 15px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;">
                  &copy; ' . date("Y") . ' PixelGear Store. Tất cả các quyền được bảo lưu.
                </div>
              </div>
            </body>
            </html>
            ';

            $mailRes = send_smtp_email($email, $subject, $message);

            if ($mailRes['success']) {
                header('Location: reset_password.php?email=' . urlencode($email) . '&sent=1');
                exit;
            } else {
                $error = "Không thể gửi email OTP: " . $mailRes['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quên Mật Khẩu | PixelGear Store</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; }
        .auth-card { max-width: 440px; margin: 60px auto; padding: 35px; background: white; border-radius: 12px; border: 1px solid var(--border-color); text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .auth-card input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; font-size: 14px; outline: none; }
        .auth-card input:focus { border-color: #0284c7; }
        .auth-card button { width: 100%; padding: 14px; margin-bottom: 15px; font-size: 15px; font-weight: 700; background: #0284c7; border: none; border-radius: 6px; color: #fff; cursor: pointer; }
        .auth-card button:hover { background: #0369a1; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; font-size: 13px; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2 style="margin-bottom: 8px; color: #0f172a;"><i class="fas fa-key" style="color: #0284c7;"></i> QUÊN MẬT KHẨU</h2>
        <p style="font-size: 13px; color: #64748b; margin-bottom: 25px;">Nhập Email tài khoản của bạn để nhận mã OTP xác thực đặt lại mật khẩu mới.</p>

        <?php if($error) echo "<div class='alert-error'><i class='fas fa-exclamation-circle'></i> $error</div>"; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Nhập địa chỉ Email của bạn..." required>
            <button type="submit">GỬI MÃ XÁC THỰC (OTP)</button>
        </form>

        <p style="font-size: 13px; color: #64748b; margin-top: 15px;">
            Nhớ mật khẩu? <a href="login.php" style="color: #0284c7; font-weight: 700; text-decoration: none;">Đăng nhập ngay</a>
        </p>
    </div>
</body>
</html>
