<?php
session_start();
require_once 'db.php';

$msg = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'reset_success') {
    $msg = "Đã cập nhật mật khẩu mới thành công! Vui lòng đăng nhập.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if (isset($user['status']) && (int)$user['status'] === 0) {
            $error = "Tài khoản của bạn đã bị khóa bởi Quản trị viên! Vui lòng liên hệ hỗ trợ.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['fullname'];
            $_SESSION['user_role'] = $user['role'] ?? 'customer';

            if (in_array($_SESSION['user_role'], ['admin', 'staff'])) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_role'] = $_SESSION['user_role'];
            }

            // Isolate and load cart for this user
            sync_user_cart_load($pdo, $user['id']);

            header('Location: index.php');
            exit;
        }
    } else {
        $error = "Tài khoản hoặc mật khẩu không chính xác!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/png" href="favicon.png?v=2">
    <link rel="shortcut icon" href="favicon.ico?v=2">
    <meta charset="UTF-8">
    <title>Đăng nhập | PixelGear Store</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; }
        .auth-container { max-width: 420px; margin: 60px auto; padding: 35px; background: white; border-radius: 12px; border: 1px solid var(--border-color); text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .auth-container input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; font-size: 14px; outline: none; box-sizing: border-box; }
        .auth-container input:focus { border-color: #15803d; }
        .password-wrapper { position: relative; width: 100%; }
        .password-wrapper input { padding-right: 40px; }
        .toggle-password { position: absolute; right: 12px; top: 13px; cursor: pointer; color: #64748b; font-size: 16px; }
        .toggle-password:hover { color: #15803d; }
        .auth-container button { width: 100%; padding: 14px; margin-bottom: 15px; font-size: 16px; font-weight: 700; background: #15803d; border: none; border-radius: 6px; color: #fff; cursor: pointer; }
        .auth-container button:hover { background: #166534; }
        .auth-error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; font-size: 14px; }
        .alert-success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; font-size: 14px; }
        .forgot-link { display: block; text-align: right; margin-top: -8px; margin-bottom: 20px; font-size: 13px; color: #0284c7; text-decoration: none; font-weight: 600; }
        .forgot-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2 style="margin-bottom: 20px; color: #1e293b;">ĐĂNG NHẬP TÀI KHOẢN</h2>
        
        <?php if($msg) echo "<div class='alert-success'><i class='fas fa-check-circle'></i> $msg</div>"; ?>
        <?php if(isset($error)) echo "<div class='auth-error'><i class='fas fa-exclamation-circle'></i> $error</div>"; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Tên đăng nhập hoặc Email" required>
            <div class="password-wrapper">
                <input type="password" name="password" id="login_pass" placeholder="Mật khẩu" required>
                <i class="fas fa-eye toggle-password" onclick="togglePass('login_pass', this)"></i>
            </div>
            <a href="forgot_password.php" class="forgot-link">Quên mật khẩu?</a>
            <button type="submit">ĐĂNG NHẬP</button>
        </form>

        <div style="margin: 20px 0; position: relative; text-align: center;">
            <hr style="border: 0; border-top: 1px solid #cbd5e1;">
            <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #fff; padding: 0 12px; color: #64748b; font-size: 13px; font-weight: 600;">HOẶC BẰNG GOOGLE</span>
        </div>

        <?php
        require_once 'google_config.php';
        $is_valid_client = defined('GOOGLE_CLIENT_ID') && !empty(GOOGLE_CLIENT_ID) && strpos(GOOGLE_CLIENT_ID, 'YOUR_') === false;
        
        if ($is_valid_client) {
            $is_https = (
                (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            );
            $protocol = $is_https ? 'https' : 'http';
            $redirect_uri = $protocol . "://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/google_callback.php";
        ?>
            <script src="https://accounts.google.com/gsi/client" async defer></script>
            <div id="g_id_onload"
                 data-client_id="<?php echo GOOGLE_CLIENT_ID; ?>"
                 data-login_uri="<?php echo $redirect_uri; ?>"
                 data-auto_prompt="false">
            </div>
            <div class="g_id_signin"
                 data-type="standard"
                 data-size="large"
                 data-theme="outline"
                 data-text="sign_in_with"
                 data-shape="rectangular"
                 data-logo_alignment="left"
                 style="display: flex; justify-content: center; margin-bottom: 20px;">
            </div>
        <?php } else { ?>
            <a href="google_oauth.php" style="background: #ffffff; color: #334155; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 20px; width: 100%; padding: 12px; border-radius: 6px; font-weight: 600; text-decoration: none; box-sizing: border-box; font-size: 15px;">
                <svg width="20" height="20" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.66 0 6.6 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24s.92 7.54 2.55 10.78l7.98-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.6 42.62 14.66 48 24 48z"/></svg>
                Đăng nhập bằng Google
            </a>
        <?php } ?>

        <p style="font-size: 14px; color: #64748b;">Chưa có tài khoản? <a href="signup.php" style="color: #15803d; font-weight:700; text-decoration: none;">Đăng ký ngay</a></p>
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
