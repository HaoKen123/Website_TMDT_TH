<?php
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 1. Kiểm tra tài khoản trong bảng admins (TK mặc định admin/admin)
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? AND password = ?');
    $stmt->execute([$username, $password]);
    $admin = $stmt->fetch();

    if ($admin) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = 'admin';
        header('Location: index.php');
        exit;
    } else {
        // 2. Kiểm tra tài khoản trong bảng users (Có quyền staff hoặc admin và status = 1)
        $stmtUser = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 1 AND role IN ('staff', 'admin')");
        $stmtUser->execute([$username, $username]);
        $user = $stmtUser->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = "Tài khoản hoặc mật khẩu không chính xác hoặc bạn không có quyền Nhân viên/Admin!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/png" href="../favicon.png?v=2">
    <link rel="shortcut icon" href="../favicon.ico?v=2">
    <meta charset="UTF-8">
    <title>Admin Login - PixelGear</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
        body { 
            font-family: 'Inter', sans-serif; 
            background: url('https://minecraft.wiki/images/Dirt.png') repeat;
            color: #333; 
            display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; 
        }
        .login-box { 
            background: #c6c6c6; 
            padding: 40px; 
            width: 350px; text-align: center; 
            border: 4px solid #000;
            box-shadow: inset 4px 4px 0px #fff, inset -4px -4px 0px #555, 0 10px 30px rgba(0,0,0,0.8);
        }
        h2 { 
            margin-top: 0; 
            font-family: 'Minecraft', sans-serif; 
            color: #333; 
            text-shadow: 2px 2px 0px #fff;
            margin-bottom: 25px;
        }
        input { 
            width: 100%; box-sizing: border-box; padding: 12px; margin: 10px 0; 
            background: #fff;
            color: #000;
            border: 2px solid #000; 
            border-top-color: #555; border-left-color: #555; 
            border-bottom-color: #fff; border-right-color: #fff;
            outline: none; font-family: 'Inter'; 
        }
        input:focus { outline: 2px solid #fff; }
        button { 
            width: 100%; padding: 15px; margin-top: 15px;
            font-family: 'Minecraft', sans-serif; 
            font-size: 16px;
            background: #55ff55; color: #000; 
            border: 2px solid #000; cursor: pointer; 
            border-top-color: #fff; border-left-color: #fff; 
            border-bottom-color: #555; border-right-color: #555;
        }
        button:hover { background: #45d545; }
        button:active {
            border-top-color: #555; border-left-color: #555; 
            border-bottom-color: #fff; border-right-color: #fff;
            background: #a0a0a0;
        }
        .error { 
            background: #ff5555; color: white; padding: 10px; font-size: 14px; margin-bottom: 15px; 
            border: 2px solid #000; border-top-color: #fff; border-left-color: #fff; border-bottom-color: #555; border-right-color: #555;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>PIXELGEAR ADMIN</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Tên đăng nhập" required>
            <input type="password" name="password" placeholder="Mật khẩu" required>
            <button type="submit">ĐĂNG NHẬP</button>
        </form>
    </div>
</body>
</html>
