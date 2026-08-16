<?php
session_start();
require_once '../db.php';
require_once '../lang.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$year_filter = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month_filter = isset($_GET['month']) ? intval($_GET['month']) : 0;
$quarter_filter = isset($_GET['quarter']) ? intval($_GET['quarter']) : 0;

// Build date filter SQL condition
$where_date = "WHERE YEAR(o.created_at) = $year_filter AND o.status != 'Đã hủy'";
if ($month_filter > 0) {
    $where_date .= " AND MONTH(o.created_at) = $month_filter";
}
if ($quarter_filter > 0) {
    if ($quarter_filter == 1) $where_date .= " AND MONTH(o.created_at) BETWEEN 1 AND 3";
    elseif ($quarter_filter == 2) $where_date .= " AND MONTH(o.created_at) BETWEEN 4 AND 6";
    elseif ($quarter_filter == 3) $where_date .= " AND MONTH(o.created_at) BETWEEN 7 AND 9";
    elseif ($quarter_filter == 4) $where_date .= " AND MONTH(o.created_at) BETWEEN 10 AND 12";
}

$customer_reports = [];
$product_reports = [];
$monthly_reports = [];
$summary = ['order_count' => 0, 'total_revenue' => 0];

try {
    // 1. Thống kê theo tài khoản khách hàng (Top Spenders)
    $customer_reports = $pdo->query("
        SELECT u.id, u.username, u.fullname, u.phone, COUNT(o.id) as total_orders, SUM(o.total_amount) as total_spent 
        FROM users u 
        JOIN orders o ON u.id = o.user_id 
        $where_date 
        GROUP BY u.id 
        ORDER BY total_spent DESC 
        LIMIT 10
    ")->fetchAll();

    // 2. Thống kê theo mặt hàng (Best Selling Products)
    $product_reports = $pdo->query("
        SELECT p.id, p.name, p.category, p.image_url, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as total_revenue 
        FROM products p 
        JOIN order_items oi ON p.id = oi.product_id 
        JOIN orders o ON oi.order_id = o.id 
        $where_date 
        GROUP BY p.id 
        ORDER BY total_sold DESC 
        LIMIT 10
    ")->fetchAll();

    // 3. Thống kê theo tháng trong năm
    $monthly_reports = $pdo->query("
        SELECT MONTH(o.created_at) as month, COUNT(o.id) as total_orders, SUM(o.total_amount) as revenue 
        FROM orders o 
        WHERE YEAR(o.created_at) = $year_filter AND o.status != 'Đã hủy' 
        GROUP BY MONTH(o.created_at) 
        ORDER BY month ASC
    ")->fetchAll();

    // Total Summary Stats
    $summary_res = $pdo->query("
        SELECT COUNT(o.id) as order_count, SUM(o.total_amount) as total_revenue 
        FROM orders o 
        $where_date
    ")->fetch();
    if ($summary_res) {
        $summary = $summary_res;
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/png" href="../favicon.png?v=2">
    <link rel="shortcut icon" href="../favicon.ico?v=2">
    <meta charset="UTF-8">
    <title>Thống Kê Báo Cáo Doanh Thu - Admin PixelGear</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stats-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-box { background: #fff; padding: 25px; border-radius: 10px; border: 1px solid #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .stat-box h3 { font-size: 14px; color: #64748b; margin: 0 0 10px 0; text-transform: uppercase; }
        .stat-box .number { font-size: 28px; font-weight: 800; color: #15803d; }
        
        .filter-bar { background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 30px; display: flex; gap: 20px; align-items: center; }
        .filter-bar select { padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; font-weight: 600; font-size: 14px; }
        
        .section-header { font-size: 20px; margin: 40px 0 15px 0; color: #0f172a; display: flex; align-items: center; gap: 10px; }
        .prod-img { width: 40px; height: 40px; border-radius: 4px; object-fit: cover; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>PIXELGEAR</h2>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Tổng quan</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
            <li><a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a></li>
            <li><a href="categories.php"><i class="fas fa-list"></i> Danh mục</a></li>
            <li><a href="coupons.php"><i class="fas fa-ticket-alt"></i> Mã giảm giá</a></li>
            <li><a href="shipping.php"><i class="fas fa-truck"></i> Phí vận chuyển</a></li>
            <li><a href="comments.php"><i class="fas fa-comments"></i> Bình luận</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Khách hàng & Nhân viên</a></li>
            <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <h1>Thống Kê Báo Cáo Doanh Thu & Bán Hàng</h1>

        <!-- Filter Bar -->
        <form method="GET" class="filter-bar">
            <span style="font-weight: 700; color: #334155;"><i class="fas fa-filter" style="color: #15803d;"></i> Lọc báo cáo:</span>
            
            <label style="font-size: 14px; font-weight: 600;">Năm:</label>
            <select name="year" onchange="this.form.submit()">
                <?php for($y = date('Y'); $y >= 2024; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $year_filter == $y ? 'selected' : ''; ?>>Năm <?php echo $y; ?></option>
                <?php endfor; ?>
            </select>

            <label style="font-size: 14px; font-weight: 600;">Quý:</label>
            <select name="quarter" onchange="this.form.submit()">
                <option value="0">Tất cả các quý</option>
                <option value="1" <?php echo $quarter_filter == 1 ? 'selected' : ''; ?>>Quý 1 (Tháng 1-3)</option>
                <option value="2" <?php echo $quarter_filter == 2 ? 'selected' : ''; ?>>Quý 2 (Tháng 4-6)</option>
                <option value="3" <?php echo $quarter_filter == 3 ? 'selected' : ''; ?>>Quý 3 (Tháng 7-9)</option>
                <option value="4" <?php echo $quarter_filter == 4 ? 'selected' : ''; ?>>Quý 4 (Tháng 10-12)</option>
            </select>

            <label style="font-size: 14px; font-weight: 600;">Tháng:</label>
            <select name="month" onchange="this.form.submit()">
                <option value="0">Tất cả các tháng</option>
                <?php for($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $month_filter == $m ? 'selected' : ''; ?>>Tháng <?php echo $m; ?></option>
                <?php endfor; ?>
            </select>

            <a href="reports.php" style="padding: 10px 15px; background: #64748b; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 13px;">Đặt lại</a>
        </form>

        <!-- Summary Stats Cards -->
        <div class="stats-cards">
            <div class="stat-box">
                <h3>TỔNG DOANH THU (KỲ BÁO CÁO)</h3>
                <div class="number">$<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></div>
            </div>
            <div class="stat-box">
                <h3>TỔNG SỐ ĐƠN HÀNG THÀNH CÔNG</h3>
                <div class="number" style="color: #0284c7;"><?php echo intval($summary['order_count'] ?? 0); ?> đơn</div>
            </div>
            <div class="stat-box">
                <h3>GIÁ TRỊ ĐƠN TRUNG BÌNH</h3>
                <div class="number" style="color: #d97706;">
                    $<?php echo ($summary['order_count'] ?? 0) > 0 ? number_format($summary['total_revenue'] / $summary['order_count'], 2) : '0.00'; ?>
                </div>
            </div>
        </div>

        <!-- 1. Báo cáo theo tháng / quý / năm -->
        <h2 class="section-header"><i class="fas fa-calendar-alt" style="color: #15803d;"></i> 1. Báo Cáo Doanh Thu Theo Tháng (Năm <?php echo $year_filter; ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>Tháng</th>
                    <th>Số đơn hàng</th>
                    <th>Tổng doanh thu</th>
                    <th>Đóng góp doanh thu</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($monthly_reports)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 25px; color: #64748b;">Chưa có dữ liệu doanh thu cho khoảng thời gian này.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($monthly_reports as $mr): 
                        $pct = ($summary['total_revenue'] > 0) ? round(($mr['revenue'] / $summary['total_revenue']) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><strong>Tháng <?php echo $mr['month']; ?> / <?php echo $year_filter; ?></strong></td>
                        <td><span class="badge pending" style="background: #e0f2fe; color: #0369a1; font-weight: 700;"><?php echo $mr['total_orders']; ?> đơn</span></td>
                        <td><strong style="color: #15803d; font-size: 15px;">$<?php echo number_format($mr['revenue'], 2); ?></strong></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; background: #e2e8f0; height: 10px; border-radius: 5px; overflow: hidden;">
                                    <div style="width: <?php echo $pct; ?>%; background: #15803d; height: 100%;"></div>
                                </div>
                                <span style="font-weight: 700; font-size: 13px; color: #475569; width: 45px;"><?php echo $pct; ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- 2. Báo cáo theo mặt hàng -->
        <h2 class="section-header"><i class="fas fa-boxes" style="color: #0284c7;"></i> 2. Báo Cáo Top Mặt Hàng Bán Chạy Nhất</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">Hạng</th>
                    <th>Sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Số lượng bán</th>
                    <th>Tổng doanh thu mặt hàng</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($product_reports)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 25px; color: #64748b;">Chưa có dữ liệu bán hàng.</td>
                </tr>
                <?php else: ?>
                    <?php $rank = 1; foreach ($product_reports as $pr): ?>
                    <tr>
                        <td><strong style="font-size: 16px; color: #64748b;">#<?php echo $rank++; ?></strong></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="<?php echo htmlspecialchars($pr['image_url']); ?>" class="prod-img">
                                <strong style="color: #0f172a;"><?php echo htmlspecialchars($pr['name']); ?></strong>
                            </div>
                        </td>
                        <td><span class="badge pending" style="text-transform: uppercase; font-weight: 700;"><?php echo htmlspecialchars($pr['category']); ?></span></td>
                        <td><strong style="color: #0284c7; font-size: 15px;"><?php echo $pr['total_sold']; ?> sản phẩm</strong></td>
                        <td><strong style="color: #15803d; font-size: 15px;">$<?php echo number_format($pr['total_revenue'], 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- 3. Báo cáo theo tài khoản khách hàng -->
        <h2 class="section-header"><i class="fas fa-users" style="color: #d97706;"></i> 3. Báo Cáo Top Khách Hàng Mua Nhiều Nhất</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">Hạng</th>
                    <th>Tài khoản</th>
                    <th>Họ và tên</th>
                    <th>Số điện thoại</th>
                    <th>Số đơn mua</th>
                    <th>Tổng chi tiêu</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customer_reports)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 25px; color: #64748b;">Chưa có dữ liệu mua hàng của khách.</td>
                </tr>
                <?php else: ?>
                    <?php $cRank = 1; foreach ($customer_reports as $cr): ?>
                    <tr>
                        <td><strong style="font-size: 16px; color: #64748b;">#<?php echo $cRank++; ?></strong></td>
                        <td><strong style="color: #0284c7;"><?php echo htmlspecialchars($cr['username']); ?></strong></td>
                        <td><strong><?php echo htmlspecialchars($cr['fullname']); ?></strong></td>
                        <td><?php echo htmlspecialchars($cr['phone']); ?></td>
                        <td><span class="badge pending" style="background: #e0f2fe; color: #0369a1; font-weight: 700;"><?php echo $cr['total_orders']; ?> đơn</span></td>
                        <td><strong style="color: #15803d; font-size: 15px;">$<?php echo number_format($cr['total_spent'], 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
