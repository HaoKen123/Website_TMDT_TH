<?php
session_start();
require_once 'db.php';
require_once 'mailer.php';
require_once 'config_turnstile.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $fullname = trim($_POST['fullname']);

    if (empty($username) || empty($email) || empty($password) || empty($fullname)) {
        $error = "Vui lòng điền đầy đủ các thông tin!";
    } elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không trùng khớp!";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải chứa ít nhất 6 ký tự!";
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Tên đăng nhập hoặc Email này đã tồn tại!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password, fullname, role, status) VALUES (?, ?, ?, ?, "customer", 1)');
            if ($stmt->execute([$username, $email, $hashed_password, $fullname])) {
                $user_id = $pdo->lastInsertId();

                // Gửi Email Voucher 15% + Freeship cho người dùng mới
                $stmtSub = $pdo->prepare("SELECT id FROM subscribers WHERE email = ?");
                $stmtSub->execute([$email]);
                if (!$stmtSub->fetch()) {
                    $stmtSubIns = $pdo->prepare("INSERT INTO subscribers (email, voucher_sent) VALUES (?, 'WELCOME15')");
                    $stmtSubIns->execute([$email]);
                    send_voucher_email($email, $fullname);
                }

                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $fullname;
                $_SESSION['user_role'] = 'customer';
                $_SESSION['custom_notice'] = "🎉 Đăng ký tài khoản thành công! Mã Voucher Giảm 15% & Freeship đã được gửi tới Email của bạn.";

                header('Location: index.php');
                exit;
            } else {
                $error = "Có lỗi xảy ra trong quá trình đăng ký. Vui lòng thử lại!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/png" href="favicon.png?v=2">
    <link rel="shortcut icon" href="favicon.ico?v=2">
    <meta charset="UTF-8">
    <title>Đăng ký tài khoản | PixelGear Store</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Cloudflare Turnstile API -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <style>
        body { background: #f8fafc; }
        .auth-container { max-width: 450px; margin: 50px auto; padding: 35px; background: white; border-radius: 12px; border: 1px solid var(--border-color); text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .auth-container input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; font-size: 14px; outline: none; box-sizing: border-box; }
        .auth-container input:focus { border-color: #15803d; }
        .password-wrapper { position: relative; width: 100%; }
        .password-wrapper input { padding-right: 40px; }
        .toggle-password { position: absolute; right: 12px; top: 13px; cursor: pointer; color: #64748b; font-size: 16px; }
        .toggle-password:hover { color: #15803d; }
        .auth-container button { width: 100%; padding: 14px; margin-bottom: 15px; font-size: 16px; font-weight: 700; background: #15803d; border: none; border-radius: 6px; color: #fff; cursor: pointer; }
        .auth-container button:hover { background: #166534; }
        .auth-error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2 style="margin-bottom: 20px; color: #1e293b;">ĐĂNG KÝ TÀI KHOẢN MỚI</h2>

        <?php if($error) echo "<div class='auth-error'><i class='fas fa-exclamation-circle'></i> $error</div>"; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Tên đăng nhập" required>
            <input type="email" name="email" placeholder="Địa chỉ Email" required>
            <div class="password-wrapper">
                <input type="password" name="password" id="reg_pass" placeholder="Mật khẩu (Tối thiểu 6 ký tự)" required>
                <i class="fas fa-eye toggle-password" onclick="togglePass('reg_pass', this)"></i>
            </div>
            <div class="password-wrapper">
                <input type="password" name="confirm_password" id="reg_pass_confirm" placeholder="Xác nhận mật khẩu" required>
                <i class="fas fa-eye toggle-password" onclick="togglePass('reg_pass_confirm', this)"></i>
            </div>
            <input type="text" name="fullname" placeholder="Họ và tên" required>

            <!-- Cloudflare Turnstile Verification Widget -->
            <div style="display: flex; justify-content: center; margin-bottom: 15px;">
                <div class="cf-turnstile" data-sitekey="<?php echo TURNSTILE_SITEKEY; ?>" data-theme="light"></div>
            </div>

            <button type="submit">ĐĂNG KÝ NGAY</button>
        </form>

        <div style="margin: 20px 0; position: relative; text-align: center;">
            <hr style="border: 0; border-top: 1px solid #cbd5e1;">
            <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #fff; padding: 0 12px; color: #64748b; font-size: 13px; font-weight: 600;">HOẶC BẰNG GOOGLE</span>
        </div>

        <?php
        require_once 'google_config.php';
        $is_valid_client = defined('GOOGLE_CLIENT_ID') && !empty(GOOGLE_CLIENT_ID);
        
        if ($is_valid_client) {
            $is_https = (
                (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            );
            $protocol = $is_https ? 'https' : 'http';
            $redirect_uri = urlencode($protocol . "://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/google_callback.php");
            $google_target_url = "https://accounts.google.com/o/oauth2/v2/auth?client_id=" . GOOGLE_CLIENT_ID . "&response_type=code&scope=openid%20email%20profile&redirect_uri={$redirect_uri}&prompt=select_account";
        } else {
            $google_target_url = "google_oauth.php";
        }
        ?>

        <a href="<?php echo $google_target_url; ?>" style="background: #ffffff; color: #334155; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 20px; width: 100%; padding: 12px; border-radius: 6px; font-weight: 600; text-decoration: none; box-sizing: border-box; font-size: 15px;">
            <svg width="20" height="20" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.66 0 6.6 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24s.92 7.54 2.55 10.78l7.98-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.6 42.62 14.66 48 24 48z"/></svg>
            Tạo tài khoản bằng Google
        </a>

        <p style="font-size: 14px; color: #64748b;">Đã có tài khoản? <a href="login.php" style="color: #15803d; font-weight:700; text-decoration: none;">Đăng nhập ngay</a></p>
    </div>

    <script>
    function togglePass(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
    </script>
</body>
</html>
