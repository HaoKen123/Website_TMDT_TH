<?php
session_start();
require_once '../db.php';
require_once '../lang.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$msg = '';
$error = '';

// Handle Add Coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $type = $_POST['discount_type'];
    $val = floatval($_POST['discount_value']);
    $min = floatval($_POST['min_order']);
    $expires_at = empty($_POST['expires_at']) ? null : date('Y-m-d H:i:s', strtotime($_POST['expires_at']));

    if (!empty($code) && $val > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order, expires_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$code, $type, $val, $min, $expires_at]);
            $msg = "Đã thêm mã ưu đãi $code thành công!";
        } catch (Exception $e) {
            $error = "Lỗi: Mã coupon này đã tồn tại!";
        }
    } else {
        $error = "Vui lòng nhập đầy đủ mã và giá trị giảm giá hợp lệ.";
    }
}

// Handle Delete Coupon
if (isset($_GET['delete'])) {
    $c_id = intval($_GET['delete']);
    if ($c_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$c_id]);
        $msg = "Đã xóa mã ưu đãi thành công!";
    }
}

// Fetch coupons & subscribers
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
$subscribers = $pdo->query("SELECT * FROM subscribers ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/png" href="../favicon.png?v=2">
    <link rel="shortcut icon" href="../favicon.ico?v=2">
    <meta charset="UTF-8">
    <title>Quản Lý Mã Giảm Giá & Email Nhận Tin - Admin PixelGear</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .grid-layout { display: flex; gap: 30px; flex-wrap: wrap; margin-top: 20px; }
        .col-main { flex: 2; min-width: 350px; }
        .col-sub { flex: 1; min-width: 300px; }
        .card-box { background: #fff; border-radius: 8px; border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .card-title { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 20px; border-bottom: 2px solid #2e7d32; padding-bottom: 8px; }
        
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-group { flex: 1; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; }
        
        .alert-success { background: #dcfce7; color: #166534; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; }
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
            <li><a href="coupons.php" class="active"><i class="fas fa-ticket-alt"></i> Mã giảm giá</a></li>
            <li><a href="shipping.php"><i class="fas fa-truck"></i> Phí vận chuyển</a></li>
            <li><a href="comments.php"><i class="fas fa-comments"></i> Bình luận</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Khách hàng & Nhân viên</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1>Quản Lý Mã Giảm Giá & Email Đăng Ký</h1>
        </div>

        <?php if ($msg): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="grid-layout">
            <!-- Left: Add & List Coupons -->
            <div class="col-main">
                <div class="card-box">
                    <h3 class="card-title"><i class="fas fa-plus-circle"></i> Tạo Mã Giảm Giá Mới</h3>
                    <form method="POST">
                        <input type="hidden" name="add_coupon" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Mã Coupon (Code)</label>
                                <input type="text" name="code" placeholder="VD: SUMMER2026" style="text-transform: uppercase;" required>
                            </div>
                            <div class="form-group">
                                <label>Loại Giảm Giá</label>
                                <select name="discount_type">
                                    <option value="percent">Theo phần trăm (%)</option>
                                    <option value="fixed">Số tiền cố định ($/VNĐ)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Giá Trị Giảm (% hoặc $)</label>
                                <input type="number" step="0.01" name="discount_value" placeholder="VD: 15 (cho 15%) hoặc 5.00 (cho $5)" required>
                            </div>
                            <div class="form-group">
                                <label>Đơn Hàng Tối Thiểu ($)</label>
                                <input type="number" step="0.01" name="min_order" value="0.00">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Thời Gian Hết Hạn / Cooldown (Không bắt buộc)</label>
                            <input type="datetime-local" name="expires_at" style="padding:10px; border:1px solid #cbd5e1; border-radius:6px; width:100%;">
                            <small style="color:#64748b;">Để trống nếu là mã vô thời hạn.</small>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 700; margin-top:10px;">TẠO MÃ KHUYẾN MÃI</button>
                    </form>
                </div>

                <div class="card-box">
                    <h3 class="card-title"><i class="fas fa-list"></i> Danh Sách Mã Giảm Giá (<?php echo count($coupons); ?>)</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã Code</th>
                                <th>Mức Giảm</th>
                                <th>Đơn Tối Thiểu</th>
                                <th>Thời Hạn Hết Hạn</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coupons as $c): ?>
                            <tr>
                                <td><strong style="color: #2e7d32; font-size: 15px; letter-spacing: 1px;"><?php echo htmlspecialchars($c['code']); ?></strong></td>
                                <td>
                                    <?php 
                                        if ($c['discount_type'] === 'percent') {
                                            echo 'Giảm <strong>' . $c['discount_value'] . '%</strong>';
                                        } else {
                                            echo 'Giảm <strong>$' . number_format($c['discount_value'], 2) . '</strong>';
                                        }
                                    ?>
                                </td>
                                <td>$<?php echo number_format($c['min_order'], 2); ?></td>
                                <td>
                                    <?php 
                                        if (!empty($c['expires_at'])) {
                                            $exp = strtotime($c['expires_at']);
                                            if ($exp < time()) {
                                                echo '<span style="color:#ef4444; font-weight:600;"><i class="fas fa-clock"></i> Đã hết hạn (' . date('d/m/Y H:i', $exp) . ')</span>';
                                            } else {
                                                echo '<span style="color:#10b981; font-weight:600;"><i class="fas fa-clock"></i> ' . date('d/m/Y H:i', $exp) . '</span>';
                                            }
                                        } else {
                                            echo '<span style="color:#64748b;">Vĩnh viễn</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <a href="coupons.php?delete=<?php echo $c['id']; ?>" class="btn btn-danger" style="padding: 4px 10px; font-size: 12px;" onclick="return confirm('Bạn có chắc chắn muốn xóa mã ưu đãi này?');"><i class="fas fa-trash"></i> Xóa</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Email Subscribers List -->
            <div class="col-sub">
                <div class="card-box">
                    <h3 class="card-title"><i class="fas fa-envelope-open-text"></i> Email Nhận Bản Tin (<?php echo count($subscribers); ?>)</h3>
                    <div style="max-height: 500px; overflow-y: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Email Đăng Ký</th>
                                    <th>Voucher Gửi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($subscribers)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: #888;">Chưa có email nào đăng ký.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($subscribers as $idx => $s): ?>
                                    <tr>
                                        <td>#<?php echo $idx + 1; ?></td>
                                        <td style="font-weight: 600; font-size: 13px;"><?php echo htmlspecialchars($s['email']); ?></td>
                                        <td><span style="font-size: 11px; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-weight: 700;"><?php echo htmlspecialchars($s['voucher_sent']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
