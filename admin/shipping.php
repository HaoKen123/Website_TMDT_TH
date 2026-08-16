<?php
session_start();
require_once '../db.php';
require_once '../lang.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$msg = '';

// Add Shipping fee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_shipping'])) {
    $province = trim($_POST['province']);
    $fee = floatval($_POST['fee']);
    if (!empty($province) && $fee >= 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO shipping_fees (province, fee) VALUES (?, ?)");
            $stmt->execute([$province, $fee]);
            $msg = "Đã thêm phí vận chuyển mới thành công!";
        } catch (Exception $e) {
            $msg = "Lỗi: Tỉnh/Thành phố này đã tồn tại trong danh sách!";
        }
    }
}

// Edit Shipping fee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_shipping'])) {
    $id = intval($_POST['fee_id']);
    $fee = floatval($_POST['fee']);
    if ($id > 0 && $fee >= 0) {
        $stmt = $pdo->prepare("UPDATE shipping_fees SET fee = ? WHERE id = ?");
        $stmt->execute([$fee, $id]);
        $msg = "Đã cập nhật mức phí cho khu vực #$id thành công!";
    }
}

// Delete Shipping fee
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM shipping_fees WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: shipping.php?msg=deleted');
    exit;
}

$shipping_fees = [];
try {
    $shipping_fees = $pdo->query("SELECT * FROM shipping_fees ORDER BY id ASC")->fetchAll();
} catch (Exception $e) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS shipping_fees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        province VARCHAR(255) NOT NULL UNIQUE,
        fee DECIMAL(10,2) NOT NULL DEFAULT 30000.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("INSERT IGNORE INTO shipping_fees (province, fee) VALUES 
        ('Hà Nội', 20000),
        ('TP. Hồ Chí Minh', 25000),
        ('Đà Nẵng', 30000),
        ('Hải Phòng', 25000),
        ('Cần Thơ', 30000),
        ('Tỉnh/Thành khác', 35000)
    ");

    try {
        $shipping_fees = $pdo->query("SELECT * FROM shipping_fees ORDER BY id ASC")->fetchAll();
    } catch (Exception $ex) {}
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/png" href="../favicon.png?v=2">
    <link rel="shortcut icon" href="../favicon.ico?v=2">
    <meta charset="UTF-8">
    <title>Quản Lý Phí Vận Chuyển - Admin PixelGear</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .layout-grid { display: grid; grid-template-columns: 320px 1fr; gap: 30px; }
        .card { background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #cbd5e1; }
        .card h3 { margin-bottom: 15px; font-size: 18px; color: #1e293b; }
        .card form label { display: block; font-weight: 600; font-size: 13px; color: #475569; margin-bottom: 5px; }
        .card form input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; box-sizing: border-box; margin-bottom: 15px; }
        .alert-success { background: #dcfce7; color: #166534; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; }
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
            <li><a href="shipping.php" class="active"><i class="fas fa-truck"></i> Phí vận chuyển</a></li>
            <li><a href="comments.php"><i class="fas fa-comments"></i> Bình luận</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Khách hàng & Nhân viên</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <h1>Quản Lý Phí Vận Chuyển Theo Tỉnh/Thành (<?php echo count($shipping_fees); ?>)</h1>
        
        <?php if ($msg): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="layout-grid">
            <div class="card">
                <h3><i class="fas fa-truck-loading" style="color: #15803d;"></i> Thêm Mức Phí Mới</h3>
                <form method="POST">
                    <input type="hidden" name="add_shipping" value="1">
                    <label>Tỉnh / Thành phố *</label>
                    <input type="text" name="province" required placeholder="Ví dụ: Quảng Ninh, Bình Dương...">

                    <label>Phí vận chuyển (VNĐ) *</label>
                    <input type="number" name="fee" required placeholder="Ví dụ: 25000..." step="1000" min="0">

                    <button type="submit" class="btn" style="width: 100%; padding: 12px; background: #15803d; color: #fff; border: none; font-weight: 700; border-radius: 6px; cursor: pointer;">THÊM MỨC PHÍ</button>
                </form>
            </div>

            <div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Tỉnh / Thành phố</th>
                            <th>Mức phí giao hàng</th>
                            <th style="width: 180px; text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shipping_fees as $sf): ?>
                        <tr>
                            <td><strong>#<?php echo $sf['id']; ?></strong></td>
                            <td><strong style="color: #0f172a; font-size: 15px;"><?php echo htmlspecialchars($sf['province']); ?></strong></td>
                            <td>
                                <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                                    <input type="hidden" name="edit_shipping" value="1">
                                    <input type="hidden" name="fee_id" value="<?php echo $sf['id']; ?>">
                                    <input type="number" name="fee" value="<?php echo floatval($sf['fee']); ?>" step="1000" style="width: 120px; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px; font-weight: 700;">
                                    <button type="submit" class="btn" style="background: #0284c7; color: #fff; border: none; padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 4px; cursor: pointer;">Lưu</button>
                                </form>
                            </td>
                            <td style="text-align: center;">
                                <a href="shipping.php?delete_id=<?php echo $sf['id']; ?>" onclick="return confirm('Xóa mức phí cho <?php echo htmlspecialchars($sf['province']); ?>?')" class="btn" style="background: #dc2626; color: #fff; padding: 5px 12px; font-size: 12px; text-decoration: none; border-radius: 4px; font-weight: 700;">
                                    <i class="fas fa-trash"></i> Xóa
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
