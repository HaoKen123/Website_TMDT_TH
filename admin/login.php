<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db.php';

// Tự động kiểm tra và thêm cột status, role vào bảng users nếu thiếu
try {
    $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('status', $cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Active, 0: Blocked'");
    }
    if (!in_array('role', $cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'customer' COMMENT 'customer, staff, admin'");
    }
} catch (Exception $e) {}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ Tên đăng nhập và Mật khẩu!";
    } else {
        // 1. Kiểm tra tài khoản trong bảng admins (TK mặc định admin/admin)
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && ($password === $admin['password'] || password_verify($password, $admin['password']))) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = 'admin';
            header('Location: index.php');
            exit;
        } else {
            // 2. Kiểm tra tài khoản trong bảng users (Có quyền staff hoặc admin)
            $stmtUser = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmtUser->execute([$username, $username]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $userRole = strtolower(trim($user['role'] ?? 'customer'));
                $userStatus = isset($user['status']) ? intval($user['status']) : 1;

                if ($userStatus === 0) {
                    $error = "Tài khoản này đã bị Khóa! Vui lòng liên hệ Quản trị viên.";
                } elseif (!in_array($userRole, ['staff', 'admin'])) {
                    $error = "Tài khoản '@" . htmlspecialchars($user['username']) . "' hiện là Khách hàng (Customer), chưa được cấp quyền Nhân viên (Staff) hoặc Admin!";
                } elseif (password_verify($password, $user['password']) || $password === $user['password'] || md5($password) === $user['password']) {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_role'] = $userRole; // 'staff' hoặc 'admin'
                    header('Location: index.php');
                    exit;
                } else {
                    $error = "Mật khẩu không chính xác!";
                }
            } else {
                $error = "Tài khoản không tồn tại trong hệ thống!";
            }
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
    <title>Đăng Nhập Quản Trị & Nhân Viên | PixelGear</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            padding: 20px;
        }
        .login-card { 
            background: #ffffff; 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.3); 
            width: 100%; 
            max-width: 420px; 
            text-align: center; 
        }
        .login-card .logo { 
            font-size: 24px; 
            font-weight: 800; 
            color: #15803d; 
            letter-spacing: 1px; 
            margin-bottom: 8px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 10px;
        }
        .login-card .sub-title { 
            font-size: 13px; 
            color: #64748b; 
            margin-bottom: 25px; 
            font-weight: 600;
        }
        .form-group { 
            margin-bottom: 18px; 
            text-align: left; 
        }
        .form-group label { 
            display: block; 
            font-size: 13px; 
            font-weight: 700; 
            color: #334155; 
            margin-bottom: 6px; 
        }
        .input-box {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-box i {
            position: absolute;
            left: 14px;
            color: #94a3b8;
            font-size: 15px;
        }
        .input-box input { 
            width: 100%; 
            padding: 12px 14px 12px 42px; 
            border: 1.5px solid #cbd5e1; 
            border-radius: 8px; 
            font-family: 'Inter'; 
            font-size: 14px; 
            outline: none;
            transition: all 0.2s;
        }
        .input-box input:focus { 
            border-color: #15803d; 
            box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.15); 
        }
        .btn-submit { 
            width: 100%; 
            padding: 13px; 
            background: #15803d; 
            color: #fff; 
            border: none; 
            border-radius: 8px; 
            font-family: 'Inter'; 
            font-size: 15px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: background 0.2s; 
            margin-top: 10px; 
        }
        .btn-submit:hover { 
            background: #166534; 
        }
        .error-msg { 
            background: #fee2e2; 
            color: #991b1b; 
            padding: 12px 15px; 
            border-radius: 8px; 
            font-size: 13px; 
            font-weight: 600; 
            margin-bottom: 20px; 
            text-align: left;
            border: 1px solid #f87171;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .back-home {
            display: inline-block;
            margin-top: 20px;
            font-size: 13px;
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
        }
        .back-home:hover {
            color: #15803d;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">
            <i class="fas fa-shield-alt"></i> PIXELGEAR ADMIN
        </div>
        <div class="sub-title">Cổng Đăng Nhập Quản Trị Viên & Nhân Viên</div>

        <?php if (!empty($error)): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-circle" style="font-size:16px; flex-shrink:0;"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Tên đăng nhập hoặc Email:</label>
                <div class="input-box">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Nhập username hoặc email..." required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Mật khẩu:</label>
                <div class="input-box">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Nhập mật khẩu..." required>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-sign-in-alt"></i> ĐĂNG NHẬP
            </button>
        </form>

        <a href="../index.php" class="back-home">
            <i class="fas fa-arrow-left"></i> Quay lại trang chủ PixelGear
        </a>
    </div>
</body>
</html>
