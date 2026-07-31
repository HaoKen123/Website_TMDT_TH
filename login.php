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
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['fullname'];
        header('Location: index.php');
        exit;
    } else {
        $error = "Tài khoản hoặc mật khẩu không chính xác!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập | PixelGear Store</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; }
        .auth-container { max-width: 420px; margin: 60px auto; padding: 35px; background: white; border-radius: 12px; border: 1px solid var(--border-color); text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .auth-container input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; font-size: 14px; outline: none; }
        .auth-container input:focus { border-color: #15803d; }
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
            <input type="password" name="password" placeholder="Mật khẩu" required>
            <a href="forgot_password.php" class="forgot-link">Quên mật khẩu?</a>
            <button type="submit">ĐĂNG NHẬP</button>
        </form>

        <p style="font-size: 14px; color: #64748b;">Chưa có tài khoản? <a href="register.php" style="color: #15803d; font-weight:700; text-decoration: none;">Đăng ký ngay</a></p>
    </div>
</body>
</html>
