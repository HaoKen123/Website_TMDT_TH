<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lang.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin_role = strtolower($_SESSION['admin_role'] ?? 'admin');
$is_admin = ($admin_role === 'admin');

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

// Get Stats
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'Đã thanh toán'")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Get recent orders
$recent_orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 8")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/png" href="../favicon.png?v=2">
    <link rel="shortcut icon" href="../favicon.ico?v=2">
    <meta charset="UTF-8">
    <title>Bảng Điều Khiển Quản Trị - PixelGear</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php if (isset($_GET['error']) && $_GET['error'] === 'no_permission'): ?>
            <div style="background:#fee2e2; color:#991b1b; padding:14px 20px; border-radius:8px; margin-bottom:20px; border:1px solid #f87171; font-weight:700; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-ban" style="font-size:20px;"></i>
                <span>Bạn không có quyền truy cập trang này. Chức năng này chỉ dành riêng cho Quản Trị Viên (Admin)!</span>
            </div>
        <?php endif; ?>

        <div class="top-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
            <div>
                <h1 style="margin:0;">Bảng Điều Khiển (Dashboard)</h1>
                <p style="margin:4px 0 0 0; color:#64748b; font-size:14px;">Xin chào, <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong></p>
            </div>
            <div>
                <?php if ($is_admin): ?>
                    <span style="display:inline-block; padding:6px 14px; border-radius:20px; background:#dcfce7; color:#15803d; font-weight:700; font-size:13px;">
                        <i class="fas fa-crown"></i> QUẢN TRỊ VIÊN
                    </span>
                <?php else: ?>
                    <span style="display:inline-block; padding:6px 14px; border-radius:20px; background:#e0f2fe; color:#0369a1; font-weight:700; font-size:13px;">
                        <i class="fas fa-user-tie"></i> NHÂN VIÊN BÁN HÀNG
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>TỔNG ĐƠN HÀNG</h3>
                <div class="value"><?php echo number_format($total_orders); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>DOANH THU (ĐÃ THANH TOÁN)</h3>
                <?php if ($is_admin): ?>
                    <div class="value" style="color: #15803d;">$<?php echo number_format($total_revenue ?: 0, 2); ?></div>
                <?php else: ?>
                    <div class="value" style="color: #64748b; font-size:18px; margin-top:5px;"><i class="fas fa-lock"></i> Bảo mật (Admin)</div>
                <?php endif; ?>
            </div>

            <div class="stat-card">
                <h3>SẢN PHẨM ĐANG BÁN</h3>
                <div class="value"><?php echo number_format($total_products); ?></div>
            </div>
        </div>

        <h2 style="margin:30px 0 15px 0; font-size:18px; color:#0f172a;"><i class="fas fa-clock" style="color:#0284c7;"></i> Đơn Hàng Mới Nhất</h2>
        <table>
            <thead>
                <tr>
                    <th>Mã ĐH</th>
                    <th>Khách hàng</th>
                    <th>Tổng tiền</th>
                    <th>Phương thức</th>
                    <th>Ngày đặt</th>
                    <th>Trạng thái</th>
                    <th style="text-align:center;">Chi tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_orders)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:25px; color:#64748b;">Chưa có đơn hàng nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></td>
                            <td><strong style="color:#15803d;">$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                            <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                            <td>
                                <span class="badge <?php echo $order['status'] == 'Đang xử lý' ? 'pending' : 'success'; ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <a href="orders.php" class="btn" style="background:#0284c7; color:#fff; padding:4px 10px; font-size:12px; font-weight:700; text-decoration:none; border-radius:4px;">
                                    <i class="fas fa-eye"></i> Xem
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>