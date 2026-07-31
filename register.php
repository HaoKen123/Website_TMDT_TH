<?php
session_start();
require_once 'db.php';
require_once 'mailer.php';
require_once 'lang.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (!$email) {
        $error = "Vui lòng nhập địa chỉ Email hợp lệ!";
    } else {
        // Check if username or email exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Tên đăng nhập hoặc Email này đã được sử dụng!";
        } else {
            // Insert User
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password, fullname, phone, address) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$username, $email, $password, $fullname, $phone, $address]);
            
            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $fullname;

            // Also save to Subscribers list
            try {
                $stmtSub = $pdo->prepare("INSERT INTO subscribers (email, voucher_sent) VALUES (?, 'WELCOME15 & FREESHIP') ON DUPLICATE KEY UPDATE voucher_sent = 'WELCOME15 & FREESHIP'");
                $stmtSub->execute([$email]);
            } catch (Exception $e) {}

            // Send REAL Email with Voucher WELCOME15 & FREESHIP IMMEDIATELY
            $subject = "🎁 CHÀO MỪNG THÀNH VIÊN MỚI! VOUCHER GIẢM 15% & FREESHIP TỪ PIXELGEAR";
            $message = '
            <html>
            <head>
              <title>Voucher Ưu Đãi Đăng Ký Thành Viên</title>
            </head>
            <body style="font-family: Arial, sans-serif; background-color: #f4f7fa; padding: 30px; margin: 0;">
              <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                <div style="background: #15803d; padding: 25px; text-align: center; color: #ffffff;">
                  <h1 style="margin: 0; font-size: 24px;">PIXELGEAR STORE</h1>
                  <p style="margin-top: 5px; opacity: 0.9;">Chúc mừng bạn đã tạo tài khoản thành công!</p>
                </div>
                <div style="padding: 30px; text-align: center; color: #334155;">
                  <h2 style="color: #0f172a; margin-top: 0;">CHÀO MỪNG ' . mb_strtoupper(htmlspecialchars($fullname), 'UTF-8') . ' 🎁</h2>
                  <p style="font-size: 15px; line-height: 1.6;">Dưới đây là bộ đôi <strong>MÃ GIẢM GIÁ 15% + MIỄN PHÍ VẬN CHUYỂN</strong> dành riêng cho bạn:</p>
                  
                  <div style="background: #f0fdf4; border: 2px dashed #16a34a; padding: 15px 25px; border-radius: 8px; margin: 15px 0;">
                    <div style="font-size: 13px; color: #166534; font-weight: bold;">MÃ GIẢM 15% ĐƠN ĐẦU TIÊN:</div>
                    <span style="font-size: 28px; font-weight: 800; color: #15803d; letter-spacing: 3px;">WELCOME15</span>
                  </div>

                  <div style="background: #eff6ff; border: 2px dashed #2563eb; padding: 15px 25px; border-radius: 8px; margin: 15px 0;">
                    <div style="font-size: 13px; color: #1e40af; font-weight: bold;">MÃ MIỄN PHÍ VẬN CHUYỂN (FREESHIP):</div>
                    <span style="font-size: 28px; font-weight: 800; color: #1d4ed8; letter-spacing: 3px;">FREESHIP</span>
                  </div>

                  <p style="font-size: 13px; color: #64748b; margin-top: 20px;">* Nhập mã này tại trang Giỏ hàng hoặc Thanh toán để nhận ưu đãi ngay lập tức.</p>
                  <div style="margin-top: 25px;">
                    <a href="http://localhost:8080/pixelgear/products.php" style="background: #15803d; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">MUA SẮM VÀ SỬ DỤNG VOUCHER NGAY</a>
                  </div>
                </div>
                <div style="background: #f8fafc; padding: 15px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;">
                  &copy; ' . date("Y") . ' PixelGear Store. Tất cả các quyền được bảo lưu.
                </div>
              </div>
            </body>
            </html>
            ';

            send_smtp_email($email, $subject, $message);

            header('Location: index.php?msg=welcome_voucher');
            exit;
        }
    }
}
?>
<?php
$prefill_email = isset($_GET['email']) ? filter_var($_GET['email'], FILTER_VALIDATE_EMAIL) : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký tài khoản nhận Voucher 15% & Freeship | PixelGear</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; }
        .auth-container { max-width: 480px; margin: 40px auto; padding: 35px; background: white; border-radius: 12px; border: 1px solid var(--border-color); text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .auth-container input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; font-size: 14px; outline: none; }
        .auth-container input:focus { border-color: #15803d; }
        .auth-container button { width: 100%; padding: 14px; margin-bottom: 15px; font-size: 16px; font-weight: 700; background: #15803d; border: none; border-radius: 6px; color: #fff; cursor: pointer; }
        .auth-container button:hover { background: #166534; }
        .auth-error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; font-size: 14px; }
        .voucher-gift-box { background: #f0fdf4; border: 1px dashed #16a34a; padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #166534; font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2 style="margin-bottom: 8px; color: #1e293b;">ĐĂNG KÝ TÀI KHOẢN</h2>
        
        <div class="voucher-gift-box">
            <i class="fas fa-gift" style="font-size: 20px; color: #15803d; margin-bottom: 5px;"></i><br>
            Tạo tài khoản ngay hôm nay để **NHẬN NGAY EMAIL TẶNG VOUCHER GIẢM 15% & FREESHIP**!
        </div>

        <?php if(isset($error)) echo "<div class='auth-error'><i class='fas fa-exclamation-circle'></i> $error</div>"; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Tên đăng nhập (Username)" required>
            <input type="email" name="email" value="<?php echo htmlspecialchars($prefill_email); ?>" placeholder="Địa chỉ Email (Nhận Voucher ngay)" required>
            <input type="password" name="password" placeholder="Mật khẩu" required>
            <input type="text" name="fullname" placeholder="Họ và tên" required>
            <input type="tel" name="phone" placeholder="Số điện thoại" required>
            <input type="text" name="address" placeholder="Địa chỉ nhận hàng" required>
            <button type="submit">TẠO TÀI KHOẢN & NHẬN VOUCHER</button>
        </form>
        <p style="font-size: 14px; color: #64748b;">Đã có tài khoản? <a href="login.php" style="color: #15803d; font-weight:700; text-decoration: none;">Đăng nhập ngay</a></p>
    </div>
</body>
</html>
