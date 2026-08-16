<?php
session_start();
require_once '../db.php';
require_once '../lang.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$msg = '';

// Toggle Approve / Reject Status
if (isset($_GET['approve_id'])) {
    $cid = intval($_GET['approve_id']);
    $stmt = $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
    $stmt->execute([$cid]);
    header('Location: comments.php?msg=approved');
    exit;
}

// Delete comment
if (isset($_GET['delete_id'])) {
    $cid = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$cid]);
    header('Location: comments.php?msg=deleted');
    exit;
}

// Fetch comments with product details (Tự động khởi tạo nếu chưa có bảng trên Host)
$comments = [];
try {
    $comments = $pdo->query("SELECT c.*, p.name as product_name, p.image_url 
                            FROM comments c 
                            LEFT JOIN products p ON c.product_id = p.id 
                            ORDER BY c.id DESC")->fetchAll();
} catch (Exception $e) {
    // Tự động tạo bảng comments
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT DEFAULT NULL,
        user_name VARCHAR(255) NOT NULL,
        rating INT DEFAULT 5,
        comment TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Thêm comment mẫu
    $pdo->exec("INSERT IGNORE INTO comments (id, product_id, user_name, rating, comment, status) VALUES 
        (1, 1, 'Nguyễn Văn An', 5, 'Mũ lưỡi trai Minecraft rất đẹp, vải mịn, thêu sắc nét!', 'approved'),
        (2, 2, 'Trần Thị Bích', 5, 'Áo thun cotton thoáng mát, in hình Enderman cực chất.', 'approved')
    ");

    try {
        $comments = $pdo->query("SELECT c.*, p.name as product_name, p.image_url 
                                FROM comments c 
                                LEFT JOIN products p ON c.product_id = p.id 
                                ORDER BY c.id DESC")->fetchAll();
    } catch (Exception $ex) {}
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/png" href="../favicon.png?v=2">
    <link rel="shortcut icon" href="../favicon.ico?v=2">
    <meta charset="UTF-8">
    <title>Quản Lý Bình Luận - Admin PixelGear</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-thumb { width: 40px; height: 40px; border-radius: 4px; object-fit: cover; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
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
            <li><a href="shipping.php"><i class="fas fa-truck"></i> Phí vận chuyển</a></li>
            <li><a href="comments.php" class="active"><i class="fas fa-comments"></i> Bình luận</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Khách hàng & Nhân viên</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <h1>Quản Lý & Duyệt Bình Luận (<?php echo count($comments); ?>)</h1>
        
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> Đã cập nhật bình luận thành công!</div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Sản phẩm</th>
                    <th>Người gửi</th>
                    <th style="width: 100px;">Đánh giá</th>
                    <th>Nội dung nhận xét</th>
                    <th>Trạng thái</th>
                    <th>Ngày gửi</th>
                    <th style="width: 170px; text-align: center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($comments)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #64748b;">
                        Chưa có bình luận nào từ khách hàng.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($comments as $c): 
                        $st = $c['status'];
                    ?>
                    <tr>
                        <td><strong>#<?php echo $c['id']; ?></strong></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <img src="<?php echo htmlspecialchars($c['image_url'] ?? 'https://via.placeholder.com/40'); ?>" class="product-thumb">
                                <span style="font-size: 13px; font-weight: 600; color: #0284c7;"><?php echo htmlspecialchars($c['product_name'] ?? ('SP #'.$c['product_id'])); ?></span>
                            </div>
                        </td>
                        <td><strong><?php echo htmlspecialchars($c['user_name']); ?></strong></td>
                        <td>
                            <div style="color: #fbbf24; font-size: 12px;">
                                <?php for($k=1; $k<=5; $k++) { echo $k <= $c['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; } ?>
                            </div>
                        </td>
                        <td style="font-size: 13px; color: #334155; max-width: 300px;"><?php echo htmlspecialchars($c['comment']); ?></td>
                        <td>
                            <?php if ($st === 'approved'): ?>
                                <span class="status-badge status-approved"><i class="fas fa-check"></i> Đã duyệt</span>
                            <?php else: ?>
                                <span class="status-badge status-pending"><i class="fas fa-clock"></i> Chờ duyệt</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 12px; color: #64748b;"><?php echo date("d/m/Y H:i", strtotime($c['created_at'])); ?></td>
                        <td style="text-align: center;">
                            <?php if ($st !== 'approved'): ?>
                                <a href="comments.php?approve_id=<?php echo $c['id']; ?>" class="btn" style="background: #16a34a; color: #fff; padding: 5px 9px; font-size: 12px; text-decoration: none; border-radius: 4px; font-weight: 700;">
                                    <i class="fas fa-check"></i> Duyệt
                                </a>
                            <?php endif; ?>
                            <a href="comments.php?delete_id=<?php echo $c['id']; ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa bình luận này không?')" class="btn" style="background: #dc2626; color: #fff; padding: 5px 9px; font-size: 12px; text-decoration: none; border-radius: 4px; font-weight: 700;">
                                <i class="fas fa-trash"></i> Xóa
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
