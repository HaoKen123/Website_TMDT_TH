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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - PixelGear</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #1e293b;
            background-image: radial-gradient(#334155 1px, transparent 1px);
            background-size: 20px 20px;
            color: #333; 
            display: flex; justify-content: center; align-items: center; min-height: 100vh;
        }
        .login-box { 
            background: #ffffff; 
            padding: 40px 35px; 
            width: 380px; 
            max-width: 90vw;
            text-align: center; 
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            border: 1px solid #e2e8f0;
        }
        .logo-badge {
            display: inline-block;
            background: #15803d;
            color: #ffffff;
            font-weight: 800;
            font-size: 14px;
            padding: 6px 16px;
            border-radius: 20px;
            margin-bottom: 20px;
            letter-spacing: 1px;
        }
        h2 { 
            margin-top: 0; 
            font-size: 22px;
            font-weight: 700;
            color: #0f172a; 
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 16px;
            text-align: left;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
        input { 
            width: 100%; 
            padding: 12px 14px; 
            background: #f8fafc;
            color: #0f172a;
            border: 1px solid #cbd5e1; 
            border-radius: 6px;
            outline: none; 
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        input:focus { 
            border-color: #15803d; 
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(21,128,61,0.15);
        }
        button { 
            width: 100%; 
            padding: 14px; 
            margin-top: 10px;
            font-size: 15px;
            font-weight: 700;
            background: #15803d; 
            color: #ffffff; 
            border: none; 
            border-radius: 6px;
            cursor: pointer; 
            transition: all 0.2s ease;
        }
        button:hover { 
            background: #166534; 
            transform: translateY(-1px);
        }
        .error { 
            background: #fee2e2; 
            color: #991b1b; 
            padding: 12px; 
            font-size: 13px; 
            margin-bottom: 20px; 
            border-radius: 6px;
            border: 1px solid #fecaca;
            font-weight: 600;
            text-align: left;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            font-size: 13px;
            color: #64748b;
            text-decoration: none;
        }
        .back-link:hover { color: #15803d; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo-badge">PIXELGEAR</div>
        <h2>ĐĂNG NHẬP HỆ THỐNG</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Tên đăng nhập / Email:</label>
                <input type="text" name="username" placeholder="Nhập tên đăng nhập hoặc email" required autofocus>
            </div>
            <div class="form-group">
                <label>Mật khẩu:</label>
                <input type="password" name="password" placeholder="Nhập mật khẩu quản trị" required>
            </div>
            <button type="submit">ĐĂNG NHẬP QUẢN TRỊ</button>
        </form>

        <a href="../index.php" class="back-link">← Quay về Cửa Hàng PixelGear</a>
    </div>
</body>
</html>
