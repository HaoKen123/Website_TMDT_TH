<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = intval($_SESSION['user_id']);
$success_msg = '';
$error_msg = '';

// Lấy thông tin user hiện tại
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// 1. Xử lý Tự xóa tài khoản (Self Delete Account)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_my_account'])) {
    // Nếu tài khoản có đặt mật khẩu truyền thống, yêu cầu nhập mật khẩu xác nhận
    if (!empty($user['password'])) {
        $confirm_pw = $_POST['confirm_password'] ?? '';
        if (empty($confirm_pw) || !password_verify($confirm_pw, $user['password'])) {
            $error_msg = "Mật khẩu xác nhận không chính xác! Không thể xóa tài khoản.";
        }
    }

    if (empty($error_msg)) {
        try {
            $pdo->beginTransaction();

            // Xóa người nhận tin nếu có email trùng
            if (!empty($user['email'])) {
                try {
                    $stDelSub = $pdo->prepare("DELETE FROM subscribers WHERE email = ?");
                    $stDelSub->execute([$user['email']]);
                } catch (Exception $ex) {}
            }

            // Lấy và xóa toàn bộ chi tiết đơn hàng & đơn hàng của user
            $stOrderIds = $pdo->prepare("SELECT id FROM orders WHERE user_id = ?");
            $stOrderIds->execute([$user_id]);
            $orderIds = $stOrderIds->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($orderIds)) {
                $inOrders = implode(',', array_fill(0, count($orderIds), '?'));
                $stDelItems = $pdo->prepare("DELETE FROM order_items WHERE order_id IN ($inOrders)");
                $stDelItems->execute($orderIds);

                $stDelOrders = $pdo->prepare("DELETE FROM orders WHERE user_id = ?");
                $stDelOrders->execute([$user_id]);
            }

            // Xóa bình luận của user
            try {
                $stDelComments = $pdo->prepare("DELETE FROM comments WHERE user_id = ?");
                $stDelComments->execute([$user_id]);
            } catch (Exception $ex) {}

            // Xóa giỏ hàng đã lưu
            try {
                $stDelCart = $pdo->prepare("DELETE FROM user_carts WHERE user_id = ?");
                $stDelCart->execute([$user_id]);
            } catch (Exception $ex) {}

            // Xóa file ảnh đại diện upload trên máy chủ nếu có
            if (!empty($user['avatar_url']) && strpos($user['avatar_url'], 'uploads/avatars/') !== false && file_exists($user['avatar_url'])) {
                @unlink($user['avatar_url']);
            }

            // Xóa bản ghi tài khoản khỏi bảng users
            $stDelUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stDelUser->execute([$user_id]);

            $pdo->commit();

            // Hủy toàn bộ phiên đăng nhập
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();

            // Bắt đầu session mới để lưu thông báo xóa thành công
            session_start();
            $_SESSION['custom_notice'] = "Tài khoản của bạn đã được xóa vĩnh viễn khỏi hệ thống!";
            header('Location: index.php?account_deleted=1');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Lỗi hệ thống khi xóa tài khoản: " . $e->getMessage();
        }
    }
}

// 2. Xử lý cập nhật thông tin cá nhân & Avatar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_my_account'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $avatar_url = empty($_POST['avatar_url']) ? $user['avatar_url'] : trim($_POST['avatar_url']);

    // Tải ảnh đại diện từ máy tính (Browse File)
    if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['avatar_file']['tmp_name'];
        $fileName = $_FILES['avatar_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'avatar_' . $user_id . '_' . time() . '.' . $fileExtension;
            $uploadFileDir = 'uploads/avatars/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                $avatar_url = $uploadFileDir . $newFileName;
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET fullname=?, phone=?, address=?, avatar_url=? WHERE id=?");
    $stmt->execute([$fullname, $phone, $address, $avatar_url, $user_id]);
    
    $_SESSION['user_name'] = $fullname;
    $success_msg = "Cập nhật thông tin và ảnh đại diện thành công!";
    
    // Refresh user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lấy lịch sử đơn hàng
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tính toán thống kê
$total_spent = 0;
$total_orders = count($orders);
foreach ($orders as $order) {
    if ($order['payment_status'] === 'Đã thanh toán') {
        $total_spent += $order['total_amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/png" href="favicon.png?v=2">
    <link rel="shortcut icon" href="favicon.ico?v=2">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân | PixelGear</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-wrapper { max-width: 1200px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 360px 1fr; gap: 30px; }
        .profile-card { background: white; padding: 30px; border-radius: 12px; border: 1px solid var(--border-color); text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .profile-card img { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-color); margin-bottom: 15px; box-shadow: 0 4px 12px rgba(21,128,61,0.2); }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 20px; }
        .stat-box { background: var(--bg-color-gray); padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--border-color); }
        .stat-box h4 { font-size: 12px; color: #666; margin-bottom: 5px; text-transform: uppercase;}
        .stat-box .val { font-size: 20px; font-weight: 700; color: var(--primary-color); }
        
        .form-group { margin-bottom: 15px; text-align: left;}
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;}
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-family: 'Inter'; box-sizing: border-box; }
        
        .order-card { border: 1px solid var(--border-color); border-radius: 10px; padding: 20px; margin-bottom: 20px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .order-header { display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee;}
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge.pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .badge.success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge.danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .btn-cancel { background: #ef4444; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;}
        .btn-cancel:hover { background: #dc2626; }
        .btn-pay { background: #15803d; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; font-weight: 600; display: inline-block;}
        .btn-pay:hover { background: #166534; }
        .alert-success { background: #dcfce7; color: #15803d; padding: 12px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #bbf7d0; font-size: 14px;}
        .alert-danger { background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #fca5a5; font-size: 14px;}
        
        @keyframes modalPop {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        @media (max-width: 860px) {
            .profile-wrapper { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <div class="logo">
                <a href="index.php" class="mc-logo">
                    <span class="mc-logo__icon" aria-hidden="true"></span>
                    <span class="mc-logo__text" data-text="PIXELGEAR">PIXELGEAR</span>
                </a>
            </div>
            <div class="header-icons">
                <a href="index.php" style="font-size: 14px; font-weight: 700; color: #fff; background: rgba(255,255,255,0.15); padding: 8px 16px; border-radius: 6px; text-decoration: none;">
                    <i class="fas fa-home"></i> VỀ TRANG CHỦ
                </a>
                <a href="logout.php" title="Đăng xuất" style="color: #fca5a5; font-size: 18px; margin-left: 15px;"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </header>

    <div class="profile-wrapper">
        <!-- Sidebar Cá nhân hóa -->
        <div class="profile-sidebar">
            <div class="profile-card">
                <img src="<?php echo $user['avatar_url'] ? htmlspecialchars($user['avatar_url']) : 'https://ui-avatars.com/api/?name='.urlencode($user['fullname']).'&background=15803d&color=fff'; ?>" alt="Avatar">
                <h3 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($user['fullname']); ?></h3>
                <p style="color:#64748b; font-size:14px; margin: 0 0 15px 0;">@<?php echo htmlspecialchars($user['username'] ?? explode('@', $user['email'])[0]); ?></p>
                
                <div class="stats-grid">
                    <div class="stat-box">
                        <h4>ĐÃ CHI TIÊU</h4>
                        <div class="val"><?php echo format_price($total_spent); ?></div>
                    </div>
                    <div class="stat-box">
                        <h4>ĐƠN HÀNG</h4>
                        <div class="val"><?php echo $total_orders; ?></div>
                    </div>
                </div>

                <hr style="margin:25px 0; border:none; border-top:1px solid var(--border-color);">

                <h4 style="text-align:left; margin-bottom:15px; font-size: 16px;"><i class="fas fa-user-edit" style="color: #15803d;"></i> Cập nhật thông tin</h4>
                <?php if($success_msg) echo "<div class='alert-success'><i class='fas fa-check-circle'></i> $success_msg</div>"; ?>
                <?php if($error_msg) echo "<div class='alert-danger'><i class='fas fa-exclamation-triangle'></i> $error_msg</div>"; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group" style="background:#f8fafc; padding:15px; border-radius:8px; border:1px dashed #cbd5e1;">
                        <label style="font-weight:600;"><i class="fas fa-camera"></i> Ảnh đại diện (Avatar):</label>
                        <div style="margin-top:8px;">
                            <label style="font-size:13px; font-weight:600; color:#334155;"><i class="fas fa-upload"></i> Tải từ máy tính (Browse File):</label>
                            <input type="file" name="avatar_file" accept="image/*" style="margin-top:4px; margin-bottom:10px;">
                        </div>
                        <div>
                            <label style="font-size:13px; font-weight:600; color:#334155;"><i class="fas fa-link"></i> Hoặc dán Link URL:</label>
                            <input type="text" name="avatar_url" value="<?php echo htmlspecialchars($user['avatar_url'] ?? ''); ?>" placeholder="https://example.com/avatar.jpg">
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:600;">Họ và Tên</label>
                        <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:600;">Số điện thoại</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label style="font-weight:600;">Địa chỉ nhận hàng</label>
                        <textarea name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; padding:12px; font-weight:700; border-radius:6px; cursor:pointer;"><i class="fas fa-save"></i> LƯU THAY ĐỔI</button>
                </form>

                <!-- KHU VỰC TỰ XÓA TÀI KHOẢN (DANGER ZONE) -->
                <div style="margin-top: 30px; padding: 18px; border-radius: 10px; background: #fff1f2; border: 1px solid #fecdd3; text-align: left;">
                    <h4 style="margin: 0 0 8px 0; color: #9f1239; font-size: 14px; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-exclamation-triangle"></i> Vùng nguy hiểm
                    </h4>
                    <p style="font-size: 12px; color: #881337; margin: 0 0 12px 0; line-height: 1.4;">
                        Nếu bạn không còn sử dụng dịch vụ, bạn có thể tự xóa vĩnh viễn tài khoản và toàn bộ dữ liệu cá nhân.
                    </p>
                    <button type="button" onclick="openDeleteAccountModal()" style="width: 100%; background: #e11d48; color: #fff; border: none; padding: 10px; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: background 0.2s;">
                        <i class="fas fa-user-slash"></i> XÓA TÀI KHOẢN CỦA TÔI
                    </button>
                </div>
            </div>
        </div>

        <!-- Lịch sử đơn hàng -->
        <div class="order-history">
            <h2 class="section-title" style="text-align:left; font-size:24px; margin-bottom:20px;">
                <i class="fas fa-shopping-bag" style="color: #15803d;"></i> Lịch sử đơn hàng
            </h2>
            
            <?php if (empty($orders)): ?>
                <div class="profile-card" style="text-align:center; padding: 50px 20px;">
                    <i class="fas fa-box-open" style="font-size: 48px; color: #94a3b8; margin-bottom: 15px;"></i>
                    <p style="color:#64748b; margin-bottom: 20px;">Bạn chưa có đơn hàng nào tại PixelGear.</p>
                    <a href="products.php" class="btn btn-primary" style="display:inline-block; padding: 10px 24px; text-decoration: none; font-weight: 700;">Khám Phá Sản Phẩm Ngay</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <strong>Đơn hàng #<?php echo $order['id']; ?></strong>
                                <span style="color:#888; font-size:13px; margin-left:10px;"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div style="font-weight: 700; color: var(--primary-color); font-size: 16px;">
                                <?php echo format_price($order['total_amount']); ?>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <p style="margin: 5px 0;"><strong>Thanh toán:</strong> 
                                <span class="badge <?php echo $order['payment_status'] == 'Đã thanh toán' ? 'success' : 'danger'; ?>">
                                    <?php echo $order['payment_status']; ?>
                                </span>
                                <span style="font-size: 13px; color:#666; margin-left: 6px;">(<?php echo $order['payment_method']; ?>)</span>
                            </p>
                            <p style="margin: 8px 0;"><strong>Trạng thái:</strong> 
                                <span class="badge <?php echo $order['status'] == 'Đã hủy' ? 'danger' : ($order['status'] == 'Hoàn thành' ? 'success' : 'pending'); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </p>
                        </div>

                        <div style="display:flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <?php if ($order['status'] == 'Chờ xác nhận'): ?>
                                <form action="cancel_order.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" class="btn-cancel" onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng #<?php echo $order['id']; ?>?');">
                                        <i class="fas fa-ban"></i> Hủy Đơn Hàng
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($order['payment_status'] == 'Chưa thanh toán' && $order['status'] != 'Đã hủy'): ?>
                                <?php if ($order['payment_method'] != 'COD'): ?>
                                    <a href="payment.php?order_id=<?php echo $order['id']; ?>" class="btn-pay">
                                        <i class="fas fa-credit-card"></i> Thanh toán ngay
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL XÁC NHẬN XÓA TÀI KHOẢN -->
    <div id="deleteAccountModal" class="modal-delete-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.7); z-index:99999; justify-content:center; align-items:center; backdrop-filter:blur(4px);">
        <div style="background:#fff; width:90%; max-width:440px; padding:30px; border-radius:12px; box-shadow:0 20px 40px rgba(0,0,0,0.3); text-align:center; animation:modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
            <div style="width:64px; height:64px; background:#fee2e2; color:#ef4444; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:28px; margin:0 auto 15px auto;">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3 style="margin:0 0 10px 0; color:#0f172a; font-size:20px; font-weight:700;">Xóa vĩnh viễn tài khoản?</h3>
            <p style="font-size:13px; color:#64748b; margin-bottom:20px; line-height:1.5;">
                Hành động này <strong>không thể khôi phục</strong>. Toàn bộ lịch sử đơn hàng, thông tin cá nhân và giỏ hàng của bạn sẽ bị xóa hoàn toàn khỏi hệ thống PixelGear.
            </p>

            <form method="POST">
                <input type="hidden" name="delete_my_account" value="1">
                <?php if (!empty($user['password'])): ?>
                    <div style="text-align:left; margin-bottom:15px;">
                        <label style="font-size:13px; font-weight:600; color:#334155; display:block; margin-bottom:5px;">
                            <i class="fas fa-lock"></i> Nhập mật khẩu tài khoản để xác nhận:
                        </label>
                        <input type="password" name="confirm_password" placeholder="Nhập mật khẩu hiện tại..." required style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; box-sizing:border-box;">
                    </div>
                <?php else: ?>
                    <div style="background:#f1f5f9; padding:10px 12px; border-radius:6px; font-size:13px; color:#475569; margin-bottom:15px; text-align:left;">
                        <i class="fab fa-google" style="color:#ea4335;"></i> Bạn đang đăng nhập bằng Google. Bấm xác nhận bên dưới để hoàn tất xóa tài khoản.
                    </div>
                <?php endif; ?>

                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="button" onclick="closeDeleteAccountModal()" style="flex:1; background:#f1f5f9; color:#475569; border:none; padding:12px; border-radius:6px; font-weight:600; cursor:pointer;">
                        Hủy bỏ
                    </button>
                    <button type="submit" style="flex:1; background:#e11d48; color:#fff; border:none; padding:12px; border-radius:6px; font-weight:700; cursor:pointer;">
                        Xác nhận Xóa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openDeleteAccountModal() {
        const modal = document.getElementById('deleteAccountModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeDeleteAccountModal() {
        const modal = document.getElementById('deleteAccountModal');
        if (modal) modal.style.display = 'none';
    }

    // Đóng modal khi click ra ngoài overlay
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('deleteAccountModal');
        if (e.target === modal) {
            closeDeleteAccountModal();
        }
    });
    </script>
</body>
</html>
