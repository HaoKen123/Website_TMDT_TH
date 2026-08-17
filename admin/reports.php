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

// Chỉ Quản trị viên (Admin) mới được xem báo cáo doanh thu tài chính
if (($_SESSION['admin_role'] ?? 'admin') !== 'admin') {
    header('Location: index.php?error=no_permission');
    exit;
}

$year_filter = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month_filter = isset($_GET['month']) ? intval($_GET['month']) : 0;
$quarter_filter = isset($_GET['quarter']) ? intval($_GET['quarter']) : 0;
$product_filter = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$customer_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// 1. Danh sách sản phẩm cho bộ lọc dropdown
$all_products = [];
try {
    $all_products = $pdo->query("SELECT id, name, category, price FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// 2. Danh sách khách hàng cho bộ lọc dropdown
$all_customers = [];
try {
    $all_customers = $pdo->query("SELECT id, username, fullname, email, phone FROM users ORDER BY fullname ASC, username ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// 3. Xây dựng điều kiện SQL linh hoạt
$where_clauses = ["o.status != 'Đã hủy'"];
$params = [];

if ($year_filter > 0) {
    $where_clauses[] = "YEAR(o.created_at) = :year";
    $params[':year'] = $year_filter;
}

if ($month_filter > 0) {
    $where_clauses[] = "MONTH(o.created_at) = :month";
    $params[':month'] = $month_filter;
}

if ($quarter_filter > 0) {
    if ($quarter_filter == 1) $where_clauses[] = "MONTH(o.created_at) BETWEEN 1 AND 3";
    elseif ($quarter_filter == 2) $where_clauses[] = "MONTH(o.created_at) BETWEEN 4 AND 6";
    elseif ($quarter_filter == 3) $where_clauses[] = "MONTH(o.created_at) BETWEEN 7 AND 9";
    elseif ($quarter_filter == 4) $where_clauses[] = "MONTH(o.created_at) BETWEEN 10 AND 12";
}

if ($customer_filter > 0) {
    $where_clauses[] = "o.user_id = :cust_id";
    $params[':cust_id'] = $customer_filter;
}

$where_orders_sql = "WHERE " . implode(' AND ', $where_clauses);

// Thông tin chi tiết khi lọc theo từng Sản phẩm
$selected_product_info = null;
$product_order_details = [];
$product_summary = ['sold_count' => 0, 'revenue' => 0, 'order_count' => 0];

if ($product_filter > 0) {
    try {
        $stProd = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stProd->execute([$product_filter]);
        $selected_product_info = $stProd->fetch(PDO::FETCH_ASSOC);

        $prod_where = $where_clauses;
        $prod_where[] = "oi.product_id = :filter_prod_id";
        $prod_params = $params;
        $prod_params[':filter_prod_id'] = $product_filter;
        $prod_where_sql = "WHERE " . implode(' AND ', $prod_where);

        $stProdOrders = $pdo->prepare("
            SELECT o.id as order_id, o.created_at, o.status, o.payment_status, o.payment_method, 
                   u.username, u.fullname, u.phone,
                   oi.quantity, oi.price as unit_price, (oi.quantity * oi.price) as item_subtotal
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            LEFT JOIN users u ON o.user_id = u.id
            $prod_where_sql
            ORDER BY o.created_at DESC
        ");
        $stProdOrders->execute($prod_params);
        $product_order_details = $stProdOrders->fetchAll(PDO::FETCH_ASSOC);

        foreach ($product_order_details as $pod) {
            $product_summary['sold_count'] += $pod['quantity'];
            $product_summary['revenue'] += $pod['item_subtotal'];
            $product_summary['order_count']++;
        }
    } catch (Exception $e) {}
}

// Thông tin chi tiết khi lọc theo Khách hàng
$selected_customer_info = null;
$customer_order_details = [];
$customer_summary = ['total_spent' => 0, 'order_count' => 0];

if ($customer_filter > 0) {
    try {
        $stCust = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stCust->execute([$customer_filter]);
        $selected_customer_info = $stCust->fetch(PDO::FETCH_ASSOC);

        $stCustOrders = $pdo->prepare("
            SELECT o.*, 
                   (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count,
                   (SELECT GROUP_CONCAT(CONCAT(p.name, ' (x', oi2.quantity, ')') SEPARATOR '<br>') 
                    FROM order_items oi2 
                    JOIN products p ON oi2.product_id = p.id 
                    WHERE oi2.order_id = o.id) as product_names
            FROM orders o
            $where_orders_sql
            ORDER BY o.created_at DESC
        ");
        $stCustOrders->execute($params);
        $customer_order_details = $stCustOrders->fetchAll(PDO::FETCH_ASSOC);

        foreach ($customer_order_details as $cod) {
            if ($cod['payment_status'] === 'Đã thanh toán') {
                $customer_summary['total_spent'] += $cod['total_amount'];
            }
            $customer_summary['order_count']++;
        }
    } catch (Exception $e) {}
}

// Thống kê tổng hợp chung
$customer_reports = [];
$product_reports = [];
$monthly_reports = [];
$summary = ['order_count' => 0, 'total_revenue' => 0];

try {
    // 1. Thống kê theo tháng trong năm
    $monthly_stmt = $pdo->prepare("
        SELECT MONTH(o.created_at) as month, COUNT(o.id) as total_orders, SUM(o.total_amount) as revenue 
        FROM orders o 
        $where_orders_sql
        GROUP BY MONTH(o.created_at) 
        ORDER BY month ASC
    ");
    $monthly_stmt->execute($params);
    $monthly_reports = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Thống kê Top mặt hàng
    $prod_stmt = $pdo->prepare("
        SELECT p.id, p.name, p.category, p.image_url, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as total_revenue 
        FROM products p 
        JOIN order_items oi ON p.id = oi.product_id 
        JOIN orders o ON oi.order_id = o.id 
        $where_orders_sql
        GROUP BY p.id 
        ORDER BY total_sold DESC 
        LIMIT 15
    ");
    $prod_stmt->execute($params);
    $product_reports = $prod_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Thống kê Top khách hàng
    $cust_stmt = $pdo->prepare("
        SELECT u.id, u.username, u.fullname, u.phone, COUNT(o.id) as total_orders, SUM(o.total_amount) as total_spent 
        FROM users u 
        JOIN orders o ON u.id = o.user_id 
        $where_orders_sql
        GROUP BY u.id 
        ORDER BY total_spent DESC 
        LIMIT 15
    ");
    $cust_stmt->execute($params);
    $customer_reports = $cust_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tổng quan số liệu chung
    $summary_stmt = $pdo->prepare("
        SELECT COUNT(o.id) as order_count, SUM(o.total_amount) as total_revenue 
        FROM orders o 
        $where_orders_sql
    ");
    $summary_stmt->execute($params);
    $summary_res = $summary_stmt->fetch(PDO::FETCH_ASSOC);
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
    <title>Báo Cáo Doanh Thu Theo Mặt Hàng & Khách Hàng | PixelGear Admin</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-box { background: #fff; padding: 22px; border-radius: 10px; border: 1px solid #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .stat-box h3 { font-size: 13px; color: #64748b; margin: 0 0 10px 0; text-transform: uppercase; font-weight: 700; }
        .stat-box .number { font-size: 26px; font-weight: 800; color: #15803d; }
        
        .filter-panel { background: #fff; padding: 22px; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-size: 13px; font-weight: 700; color: #334155; }
        .filter-group select, .filter-group input { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; font-size: 13px; }
        
        .section-header { font-size: 18px; margin: 35px 0 15px 0; color: #0f172a; display: flex; align-items: center; justify-content: space-between; }
        .prod-img { width: 44px; height: 44px; border-radius: 6px; object-fit: cover; border: 1px solid #e2e8f0; }
        .badge-pill { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; }
        .highlight-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 15px 20px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="top-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
            <div>
                <h1 style="margin:0;"><i class="fas fa-chart-pie" style="color:#15803d;"></i> Báo Cáo Doanh Thu & Bán Hàng</h1>
                <p style="margin:5px 0 0 0; color:#64748b; font-size:14px;">Thống kê đa chiều theo thời gian, từng mặt hàng và tài khoản khách hàng</p>
            </div>
            <a href="reports.php" class="btn" style="background:#f1f5f9; color:#475569; padding:8px 16px; border-radius:6px; font-weight:600; text-decoration:none; border:1px solid #cbd5e1;">
                <i class="fas fa-redo"></i> Đặt lại bộ lọc
            </a>
        </div>

        <!-- BỘ LỌC ĐA CHIỀU (MULTI-FILTER PANEL) -->
        <div class="filter-panel">
            <div style="font-weight: 700; color: #0f172a; font-size: 15px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-filter" style="color: #15803d;"></i> Bộ Lọc Báo Cáo Doanh Thu:
            </div>
            <form method="GET">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i> Năm báo cáo:</label>
                        <select name="year">
                            <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $year_filter == $y ? 'selected' : ''; ?>>Năm <?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-clock"></i> Quý trong năm:</label>
                        <select name="quarter">
                            <option value="0">-- Tất cả 4 Quý --</option>
                            <option value="1" <?php echo $quarter_filter == 1 ? 'selected' : ''; ?>>Quý 1 (Tháng 1 - 3)</option>
                            <option value="2" <?php echo $quarter_filter == 2 ? 'selected' : ''; ?>>Quý 2 (Tháng 4 - 6)</option>
                            <option value="3" <?php echo $quarter_filter == 3 ? 'selected' : ''; ?>>Quý 3 (Tháng 7 - 9)</option>
                            <option value="4" <?php echo $quarter_filter == 4 ? 'selected' : ''; ?>>Quý 4 (Tháng 10 - 12)</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label><i class="fas fa-calendar-day"></i> Tháng trong năm:</label>
                        <select name="month">
                            <option value="0">-- Tất cả 12 Tháng --</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $month_filter == $m ? 'selected' : ''; ?>>Tháng <?php echo $m; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- LỌC THEO MẶT HÀNG (SẢN PHẨM) -->
                    <div class="filter-group">
                        <label><i class="fas fa-box-open" style="color:#0284c7;"></i> Lọc theo mặt hàng:</label>
                        <select name="product_id">
                            <option value="0">-- Tất cả mặt hàng --</option>
                            <?php foreach ($all_products as $pItem): ?>
                                <option value="<?php echo $pItem['id']; ?>" <?php echo $product_filter == $pItem['id'] ? 'selected' : ''; ?>>
                                    #<?php echo $pItem['id']; ?> - <?php echo htmlspecialchars($pItem['name']); ?> ($<?php echo number_format($pItem['price'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- LỌC THEO TÀI KHOẢN KHÁCH HÀNG -->
                    <div class="filter-group">
                        <label><i class="fas fa-user-circle" style="color:#d97706;"></i> Lọc theo khách hàng:</label>
                        <select name="user_id">
                            <option value="0">-- Tất cả khách hàng --</option>
                            <?php foreach ($all_customers as $cItem): ?>
                                <option value="<?php echo $cItem['id']; ?>" <?php echo $customer_filter == $cItem['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cItem['fullname'] ?: $cItem['username']); ?> (@<?php echo htmlspecialchars($cItem['username']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="margin-top: 18px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="background:#15803d; border:none; padding:10px 24px; font-weight:700; border-radius:6px; cursor:pointer;">
                        <i class="fas fa-search"></i> ÁP DỤNG BỘ LỌC
                    </button>
                    <a href="reports.php" class="btn" style="background:#f1f5f9; color:#475569; padding:10px 18px; border-radius:6px; font-weight:600; text-decoration:none; border:1px solid #cbd5e1;">
                        Xóa bộ lọc
                    </a>
                </div>
            </form>
        </div>

        <!-- THẺ TỔNG HỢP CHỈ SỐ DOANH THU -->
        <div class="stats-cards">
            <div class="stat-box">
                <h3><i class="fas fa-dollar-sign" style="color:#15803d;"></i> Tổng Doanh Thu (Kỳ Lọc)</h3>
                <div class="number">$<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></div>
            </div>
            <div class="stat-box">
                <h3><i class="fas fa-shopping-bag" style="color:#0284c7;"></i> Số Đơn Hàng Thành Công</h3>
                <div class="number" style="color: #0284c7;"><?php echo intval($summary['order_count'] ?? 0); ?> đơn</div>
            </div>
            <div class="stat-box">
                <h3><i class="fas fa-calculator" style="color:#d97706;"></i> Giá Trị Đơn Trung Bình</h3>
                <div class="number" style="color: #d97706;">
                    $<?php echo ($summary['order_count'] ?? 0) > 0 ? number_format($summary['total_revenue'] / $summary['order_count'], 2) : '0.00'; ?>
                </div>
            </div>
        </div>

        <!-- TRƯỜNG HỢP 1: ĐANG LỌC RIÊNG 1 MẶT HÀNG -->
        <?php if ($selected_product_info): ?>
            <div class="highlight-box">
                <div style="display:flex; align-items:center; gap:15px;">
                    <img src="<?php echo htmlspecialchars($selected_product_info['image_url']); ?>" class="prod-img" style="width:60px; height:60px;">
                    <div>
                        <div style="font-size:12px; color:#15803d; font-weight:700; text-transform:uppercase;">Chi tiết báo cáo mặt hàng:</div>
                        <h2 style="margin:2px 0 0 0; font-size:18px; color:#0f172a;"><?php echo htmlspecialchars($selected_product_info['name']); ?></h2>
                        <span class="badge pending" style="text-transform:uppercase; font-size:11px; margin-top:4px;"><?php echo htmlspecialchars($selected_product_info['category']); ?></span>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:12px; color:#64748b;">Doanh thu từ mặt hàng này:</div>
                    <div style="font-size:22px; font-weight:800; color:#15803d;">$<?php echo number_format($product_summary['revenue'], 2); ?></div>
                    <div style="font-size:13px; font-weight:700; color:#0284c7;">Đã bán: <?php echo $product_summary['sold_count']; ?> chiếc (<?php echo $product_summary['order_count']; ?> lượt đơn)</div>
                </div>
            </div>

            <h2 class="section-header"><i class="fas fa-list-ul" style="color:#15803d;"></i> Lịch Sử Đơn Hàng Mua Mặt Hàng Này</h2>
            <table>
                <thead>
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Ngày đặt</th>
                        <th>Khách hàng</th>
                        <th>Số lượng mua</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                        <th>Trạng thái đơn</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($product_order_details)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:30px; color:#64748b;">Chưa có đơn hàng nào mua sản phẩm này trong kỳ lọc.</td></tr>
                    <?php else: ?>
                        <?php foreach ($product_order_details as $pod): ?>
                            <tr>
                                <td><a href="orders.php" style="font-weight:700; color:#15803d;">#<?php echo $pod['order_id']; ?></a></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pod['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($pod['fullname'] ?: ($pod['username'] ?? 'Khách vãng lai')); ?></strong>
                                    <div style="font-size:12px; color:#64748b;"><?php echo htmlspecialchars($pod['phone'] ?? ''); ?></div>
                                </td>
                                <td><strong style="color:#0284c7; font-size:15px;"><?php echo $pod['quantity']; ?></strong></td>
                                <td>$<?php echo number_format($pod['unit_price'], 2); ?></td>
                                <td><strong style="color:#15803d; font-size:15px;">$<?php echo number_format($pod['item_subtotal'], 2); ?></strong></td>
                                <td><span class="badge pending" style="background:#e0f2fe; color:#0369a1;"><?php echo htmlspecialchars($pod['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- TRƯỜNG HỢP 2: ĐANG LỌC RIÊNG 1 KHÁCH HÀNG -->
        <?php if ($selected_customer_info): ?>
            <div class="highlight-box" style="background:#eff6ff; border-color:#bfdbfe; margin-top:25px;">
                <div style="display:flex; align-items:center; gap:15px;">
                    <div style="width:50px; height:50px; border-radius:50%; background:#0284c7; color:#fff; display:flex; align-items:center; justify-content:center; font-size:24px;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div style="font-size:12px; color:#0369a1; font-weight:700; text-transform:uppercase;">Chi tiết chi tiêu của khách hàng:</div>
                        <h2 style="margin:2px 0 0 0; font-size:18px; color:#0f172a;"><?php echo htmlspecialchars($selected_customer_info['fullname'] ?: $selected_customer_info['username']); ?></h2>
                        <div style="font-size:13px; color:#475569;">@<?php echo htmlspecialchars($selected_customer_info['username']); ?> • SĐT: <?php echo htmlspecialchars($selected_customer_info['phone'] ?? 'Chưa có'); ?></div>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:12px; color:#64748b;">Tổng chi tiêu của khách:</div>
                    <div style="font-size:22px; font-weight:800; color:#15803d;">$<?php echo number_format($customer_summary['total_spent'], 2); ?></div>
                    <div style="font-size:13px; font-weight:700; color:#0369a1;">Tổng số đơn: <?php echo $customer_summary['order_count']; ?> đơn hàng</div>
                </div>
            </div>

            <h2 class="section-header"><i class="fas fa-shopping-cart" style="color:#0284c7;"></i> Tất Cả Đơn Hàng Của Khách Hàng Này</h2>
            <table>
                <thead>
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Ngày đặt</th>
                        <th>Các sản phẩm đã mua</th>
                        <th>Tổng tiền</th>
                        <th>Thanh toán</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customer_order_details)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:#64748b;">Khách hàng này chưa có đơn hàng nào trong kỳ lọc.</td></tr>
                    <?php else: ?>
                        <?php foreach ($customer_order_details as $cod): ?>
                            <tr>
                                <td><a href="orders.php" style="font-weight:700; color:#15803d;">#<?php echo $cod['id']; ?></a></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($cod['created_at'])); ?></td>
                                <td style="font-size:13px; line-height:1.4;"><?php echo $cod['product_names'] ?: 'Chi tiết đơn hàng'; ?></td>
                                <td><strong style="color:#15803d; font-size:15px;">$<?php echo number_format($cod['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="badge <?php echo $cod['payment_status'] === 'Đã thanh toán' ? 'success' : 'danger'; ?>">
                                        <?php echo htmlspecialchars($cod['payment_status']); ?>
                                    </span>
                                </td>
                                <td><span class="badge pending"><?php echo htmlspecialchars($cod['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- BÁO CÁO DOANH THU THEO THÁNG TRONG NĂM -->
        <h2 class="section-header"><i class="fas fa-calendar-alt" style="color: #15803d;"></i> 1. Báo Cáo Doanh Thu Theo Tháng (Năm <?php echo $year_filter; ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>Tháng</th>
                    <th>Số đơn hàng</th>
                    <th>Tổng doanh thu</th>
                    <th>Tỷ lệ đóng góp doanh thu</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($monthly_reports)): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 25px; color: #64748b;">Chưa có dữ liệu doanh thu cho khoảng thời gian này.</td></tr>
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

        <!-- BÁO CÁO DOANH THU THEO MẶT HÀNG -->
        <h2 class="section-header"><i class="fas fa-boxes" style="color: #0284c7;"></i> 2. Báo Cáo Doanh Thu & Số Lượng Theo Mặt Hàng</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">Hạng</th>
                    <th>Sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Số lượng bán</th>
                    <th>Tổng doanh thu mặt hàng</th>
                    <th style="width: 120px; text-align: center;">Xem chi tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($product_reports)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 25px; color: #64748b;">Chưa có dữ liệu bán hàng.</td></tr>
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
                            <td style="text-align: center;">
                                <a href="reports.php?product_id=<?php echo $pr['id']; ?>&year=<?php echo $year_filter; ?>" class="btn" style="background:#0284c7; color:#fff; padding:5px 10px; font-size:12px; font-weight:700; text-decoration:none; border-radius:4px;">
                                    <i class="fas fa-eye"></i> Lọc
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- BÁO CÁO DOANH THU THEO TÀI KHOẢN KHÁCH HÀNG -->
        <h2 class="section-header"><i class="fas fa-users" style="color: #d97706;"></i> 3. Báo Cáo Doanh Thu Theo Tài Khoản Khách Hàng</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">Hạng</th>
                    <th>Tài khoản</th>
                    <th>Họ và tên</th>
                    <th>Số điện thoại</th>
                    <th>Số đơn mua</th>
                    <th>Tổng tiền chi tiêu</th>
                    <th style="width: 120px; text-align: center;">Xem chi tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customer_reports)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 25px; color: #64748b;">Chưa có dữ liệu mua hàng của khách.</td></tr>
                <?php else: ?>
                    <?php $cRank = 1; foreach ($customer_reports as $cr): ?>
                        <tr>
                            <td><strong style="font-size: 16px; color: #64748b;">#<?php echo $cRank++; ?></strong></td>
                            <td><strong style="color: #0284c7;">@<?php echo htmlspecialchars($cr['username']); ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($cr['fullname'] ?: 'Chưa cập nhật'); ?></strong></td>
                            <td><?php echo htmlspecialchars($cr['phone'] ?: 'Chưa có'); ?></td>
                            <td><span class="badge pending" style="background: #e0f2fe; color: #0369a1; font-weight: 700;"><?php echo $cr['total_orders']; ?> đơn</span></td>
                            <td><strong style="color: #15803d; font-size: 15px;">$<?php echo number_format($cr['total_spent'], 2); ?></strong></td>
                            <td style="text-align: center;">
                                <a href="reports.php?user_id=<?php echo $cr['id']; ?>&year=<?php echo $year_filter; ?>" class="btn" style="background:#d97706; color:#fff; padding:5px 10px; font-size:12px; font-weight:700; text-decoration:none; border-radius:4px;">
                                    <i class="fas fa-eye"></i> Lọc
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