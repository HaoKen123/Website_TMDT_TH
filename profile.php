<?php
session_start();
require_once 'db.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = '';

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $avatar_url = empty($_POST['avatar_url']) ? null : $_POST['avatar_url'];

    $stmt = $pdo->prepare("UPDATE users SET fullname=?, phone=?, address=?, avatar_url=? WHERE id=?");
    $stmt->execute([$fullname, $phone, $address, $avatar_url, $user_id]);
    
    $_SESSION['user_name'] = $fullname;
    $success_msg = "Cập nhật thông tin thành công!";
}

// Lấy thông tin user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Lấy lịch sử đơn hàng
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân | PixelGear</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-wrapper { max-width: 1200px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 350px 1fr; gap: 30px; }
        .profile-card { background: white; padding: 30px; border-radius: 8px; border: 1px solid var(--border-color); text-align: center; }
        .profile-card img { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-color); margin-bottom: 15px;}
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 20px; }
        .stat-box { background: var(--bg-color-gray); padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--border-color); }
        .stat-box h4 { font-size: 12px; color: #666; margin-bottom: 5px; text-transform: uppercase;}
        .stat-box .val { font-size: 20px; font-weight: 700; color: var(--primary-color); }
        
        .form-group { margin-bottom: 15px; text-align: left;}
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px;}
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; font-family: 'Inter'; }
        
        .order-card { border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; margin-bottom: 20px; background: white;}
        .order-header { display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee;}
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge.pending { background: #ff9800; color: #fff; }
        .badge.success { background: #4caf50; color: #fff; }
        .badge.danger { background: #f44336; color: #fff; }
        .btn-cancel { background: #f44336; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;}
        .btn-pay { background: #003087; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none;}
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #c3e6cb;}
    </style>
</head>
<body>
    <!-- Header / Navigation -->
    <header class="site-header">
        <div class="header-container" style="width: 100%;">
            <div class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </div>
            
            <div class="logo">
                <h1 class="glitch-title" data-text="PIXELGEAR">PIXELGEAR</h1>
            </div>

            <nav class="main-nav">
                <ul>
                    <li><a href="index.php" class="active-nav <?php echo $current_page === 'index' ? 'active' : ''; ?>"><?php echo __('NAV_HOME'); ?></a></li>
                    <li><a href="products.php" class="nav-link <?php echo in_array($current_page, ['products', 'all']) ? 'active' : ''; ?>"><?php echo __('NAV_ALL'); ?></a></li>
                    <li><a href="products.php?category=clothing" class="nav-link <?php echo $current_page === 'clothing' ? 'active' : ''; ?>"><?php echo __('NAV_CLOTHING'); ?></a></li>
                    <li><a href="products.php?category=accessories" class="nav-link <?php echo $current_page === 'accessories' ? 'active' : ''; ?>"><?php echo __('NAV_ACCESSORIES'); ?></a></li>
                    <li><a href="products.php?category=toys" class="nav-link <?php echo $current_page === 'toys' ? 'active' : ''; ?>"><?php echo __('NAV_TOYS'); ?></a></li>
                </ul>
            </nav>

            <div class="header-icons">
                <!-- Region & Currency Switcher -->
                <div class="region-switcher-container">
                    <button class="region-btn" type="button">
                        <?php if ($current_region === 'VN'): ?>
                            <div class="flag vn"><i class="fas fa-flag"></i></div>
                            <span>Việt Nam</span>
                        <?php else: ?>
                            <div class="flag us"><i class="fas fa-flag"></i></div>
                            <span>USA</span>
                        <?php endif; ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="region-dropdown">
                        <a href="?region=VN" class="region-option <?php echo $current_region === 'VN' ? 'active' : ''; ?>">
                            <div class="flag vn"><i class="fas fa-flag"></i></div>
                            <span>Việt Nam (₫)</span>
                        </a>
                        <a href="?region=US" class="region-option <?php echo $current_region === 'US' ? 'active' : ''; ?>">
                            <div class="flag us"><i class="fas fa-flag"></i></div>
                            <span>USA ($)</span>
                        </a>
                    </div>
                </div>

                <form action="products.php" method="GET" class="search-container">
                    <input type="text" name="search" placeholder="Tìm kiếm sản phẩm...">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" title="<?php echo __('PROFILE'); ?>" class="user-menu"><i class="fas fa-user-circle"></i></a>
                <?php else: ?>
                    <a href="login.php" title="<?php echo __('LOGIN'); ?>" class="auth-btn"><i class="fas fa-user"></i></a>
                <?php endif; ?>
                
                <a href="cart.php" class="cart-icon" title="<?php echo __('CART'); ?>">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </div>
        </div>
    </header>

    <div class="profile-wrapper">
        <!-- Sidebar Cá nhân hóa -->
        <div class="profile-sidebar">
            <div class="profile-card">
                <img src="<?php echo $user['avatar_url'] ? htmlspecialchars($user['avatar_url']) : 'https://ui-avatars.com/api/?name='.urlencode($user['fullname']).'&background=0e8543&color=fff'; ?>" alt="Avatar">
                <h3><?php echo htmlspecialchars($user['fullname']); ?></h3>
                <p style="color:#666; font-size:14px;">@<?php echo htmlspecialchars($user['username']); ?></p>
                
                <div class="stats-grid">
                    <div class="stat-box">
                        <h4>Đã chi tiêu</h4>
                        <div class="val">$<?php echo number_format($total_spent, 2); ?></div>
                    </div>
                    <div class="stat-box">
                        <h4>Đơn hàng</h4>
                        <div class="val"><?php echo $total_orders; ?></div>
                    </div>
                </div>

                <hr style="margin:20px 0; border:none; border-top:1px solid var(--border-color);">

                <h4 style="text-align:left; margin-bottom:15px;">Cập nhật thông tin</h4>
                <?php if($success_msg) echo "<div class='alert-success'>$success_msg</div>"; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Link Ảnh đại diện</label>
                        <input type="text" name="avatar_url" value="<?php echo htmlspecialchars($user['avatar_url'] ?? ''); ?>" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label>Họ và Tên</label>
                        <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Địa chỉ nhận hàng</label>
                        <textarea name="address" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; padding:10px;">LƯU THAY ĐỔI</button>
                </form>
            </div>
        </div>

        <!-- Lịch sử đơn hàng -->
        <div class="order-history">
            <h2 class="section-title" style="text-align:left; font-size:24px; margin-bottom:20px;">Lịch sử đơn hàng</h2>
            
            <?php if (empty($orders)): ?>
                <div class="profile-card">Bạn chưa có đơn hàng nào. <a href="index.php" style="color:var(--primary-color);">Mua sắm ngay!</a></div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <strong>Đơn hàng #<?php echo $order['id']; ?></strong>
                                <span style="color:#888; font-size:13px; margin-left:10px;"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div style="font-weight: 700; color: var(--primary-color);">
                                $<?php echo number_format($order['total_amount'], 2); ?>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <p><strong>Thanh toán:</strong> 
                                <span class="badge <?php echo $order['payment_status'] == 'Đã thanh toán' ? 'success' : 'danger'; ?>">
                                    <?php echo $order['payment_status']; ?>
                                </span>
                                <span style="font-size: 13px; color:#666;">(<?php echo $order['payment_method']; ?>)</span>
                            </p>
                            <p style="margin-top: 10px;"><strong>Trạng thái:</strong> 
                                <span class="badge <?php echo $order['status'] == 'Đã hủy' ? 'danger' : ($order['status'] == 'Hoàn thành' ? 'success' : 'pending'); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </p>
                        </div>

                        <?php if ($order['status'] == 'Chờ xác nhận'): ?>
                            <form action="cancel_order.php" method="POST" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" class="btn-cancel" onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?');">Hủy Đơn Hàng</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($order['payment_status'] == 'Chưa thanh toán' && $order['status'] != 'Đã hủy'): ?>
                            <?php if ($order['payment_method'] != 'COD'): ?>
                                <a href="payment.php?order_id=<?php echo $order['id']; ?>" class="btn-pay">Thanh toán ngay</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
