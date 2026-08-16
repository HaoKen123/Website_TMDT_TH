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

// 1. Duyệt bình luận (Approve Comment)
if (isset($_GET['approve_id'])) {
    $cid = intval($_GET['approve_id']);
    $stmt = $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
    $stmt->execute([$cid]);
    header('Location: comments.php?msg=approved');
    exit;
}

// 2. Khóa bình luận (Lock/Reject Comment - Khác với Xóa, chỉ khóa/ẩn bình luận này)
if (isset($_GET['lock_comment_id'])) {
    $cid = intval($_GET['lock_comment_id']);
    $stmt = $pdo->prepare("UPDATE comments SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$cid]);
    header('Location: comments.php?msg=comment_locked');
    exit;
}

// 3. Mở khóa bình luận (Unlock Comment)
if (isset($_GET['unlock_comment_id'])) {
    $cid = intval($_GET['unlock_comment_id']);
    $stmt = $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
    $stmt->execute([$cid]);
    header('Location: comments.php?msg=comment_unlocked');
    exit;
}

// 4. Khóa tài khoản Người dùng (Lock User - Khóa quyền đăng nhập & gửi bình luận của user đó)
if (isset($_GET['lock_user_id'])) {
    $uid = intval($_GET['lock_user_id']);
    if ($uid > 0) {
        $stmt = $pdo->prepare("UPDATE users SET status = 0 WHERE id = ?");
        $stmt->execute([$uid]);
        header('Location: comments.php?msg=user_locked');
        exit;
    }
}

// 5. Mở khóa tài khoản Người dùng (Unlock User)
if (isset($_GET['unlock_user_id'])) {
    $uid = intval($_GET['unlock_user_id']);
    if ($uid > 0) {
        $stmt = $pdo->prepare("UPDATE users SET status = 1 WHERE id = ?");
        $stmt->execute([$uid]);
        header('Location: comments.php?msg=user_unlocked');
        exit;
    }
}

// 6. Xóa vĩnh viễn bình luận (Delete permanently from database)
if (isset($_GET['delete_id'])) {
    $cid = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$cid]);
    header('Location: comments.php?msg=deleted');
    exit;
}

// Filters
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = [];
$params = [];

if ($status_filter !== '') {
    $where[] = "c.status = :st";
    $params[':st'] = $status_filter;
}

if ($search !== '') {
    $where[] = "(c.comment LIKE :s OR c.user_name LIKE :s OR p.name LIKE :s)";
    $params[':s'] = '%' . $search . '%';
}

$whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

// Fetch comments with product details and user status
$comments = [];
try {
    $stmtC = $pdo->prepare("SELECT c.*, p.name as product_name, p.image_url, u.status as user_account_status, u.email as user_email
                            FROM comments c 
                            LEFT JOIN products p ON c.product_id = p.id 
                            LEFT JOIN users u ON c.user_id = u.id 
                            $whereSql 
                            ORDER BY c.id DESC");
    $stmtC->execute($params);
    $comments = $stmtC->fetchAll();
} catch (Exception $e) {
    // Tự động tạo bảng comments nếu chưa có
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT DEFAULT NULL,
        user_name VARCHAR(255) NOT NULL,
        rating INT DEFAULT 5,
        comment TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    try {
        $stmtC = $pdo->prepare("SELECT c.*, p.name as product_name, p.image_url, u.status as user_account_status, u.email as user_email
                                FROM comments c 
                                LEFT JOIN products p ON c.product_id = p.id 
                                LEFT JOIN users u ON c.user_id = u.id 
                                $whereSql 
                                ORDER BY c.id DESC");
        $stmtC->execute($params);
        $comments = $stmtC->fetchAll();
    } catch (Exception $ex) {}
}

// Counts for quick stats
$count_all = 0;
$count_pending = 0;
$count_approved = 0;
$count_rejected = 0;
try {
    $count_all = (int)$pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    $count_pending = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
    $count_approved = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn();
    $count_rejected = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'rejected'")->fetchColumn();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/png" href="../favicon.png?v=2">
    <link rel="shortcut icon" href="../favicon.ico?v=2">
    <meta charset="UTF-8">
    <title>Quản Lý & Duyệt Bình Luận - Admin PixelGear</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-thumb { width: 44px; height: 44px; border-radius: 6px; object-fit: cover; background: #e2e8f0; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        
        .user-locked-badge { font-size: 11px; background: #fee2e2; color: #b91c1c; padding: 2px 6px; border-radius: 4px; font-weight: 700; display: inline-block; margin-top: 3px; }
        .user-active-badge { font-size: 11px; background: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 4px; font-weight: 700; display: inline-block; margin-top: 3px; }

        .alert-box { padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: #dcfce7; color: #166534; }
        
        .filter-bar { display: flex; gap: 12px; margin-bottom: 20px; align-items: center; flex-wrap: wrap; background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #cbd5e1; }
        .filter-bar input, .filter-bar select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; font-size: 13px; }
        .filter-bar button { padding: 8px 16px; background: #15803d; color: #fff; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; }
        
        .stat-tabs { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
        .stat-tab { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #cbd5e1; background: #fff; color: #475569; }
        .stat-tab.active { background: #0f172a; color: #fff; border-color: #0f172a; }
        .stat-tab .count { background: rgba(0,0,0,0.1); padding: 2px 6px; border-radius: 10px; font-size: 11px; }
        .stat-tab.active .count { background: rgba(255,255,255,0.2); }
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
            <li><a href="comments.php" class="active"><i class="fas fa-comments"></i> Bình luận <?php if ($count_pending > 0): ?><span style="background:#e11d48; color:#fff; font-size:11px; padding:2px 6px; border-radius:10px; margin-left:4px;"><?php echo $count_pending; ?></span><?php endif; ?></a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Khách hàng & Nhân viên</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <h1 style="display: flex; align-items: center; justify-content: space-between;">
            <span>Quản Lý, Duyệt & Khóa Bình Luận (<?php echo count($comments); ?>)</span>
        </h1>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert-box alert-success">
                <i class="fas fa-check-circle"></i>
                <?php 
                    switch($_GET['msg']) {
                        case 'approved': echo "Đã DUYỆT bình luận thành công! Bình luận này hiện đang hiển thị công khai trên web."; break;
                        case 'comment_locked': echo "Đã KHÓA bình luận thành công! Bình luận này đã được ẩn khỏi trang sản phẩm (vẫn lưu trong Admin)."; break;
                        case 'comment_unlocked': echo "Đã MỞ KHÓA bình luận thành công!"; break;
                        case 'user_locked': echo "Đã KHÓA tài khoản người dùng! Người dùng này sẽ không thể gửi thêm bất kỳ bình luận nào."; break;
                        case 'user_unlocked': echo "Đã MỞ KHÓA tài khoản người dùng thành công!"; break;
                        case 'deleted': echo "Đã XÓA VĨNH VIỄN bình luận khỏi hệ thống!"; break;
                        default: echo "Thao tác thành công!"; break;
                    }
                ?>
            </div>
        <?php endif; ?>

        <!-- Quick Status Tabs -->
        <div class="stat-tabs">
            <a href="comments.php" class="stat-tab <?php echo $status_filter===''?'active':''; ?>">
                Tất cả <span class="count"><?php echo $count_all; ?></span>
            </a>
            <a href="comments.php?status=pending" class="stat-tab <?php echo $status_filter==='pending'?'active':''; ?>" style="<?php echo $count_pending>0?'border-color:#f59e0b; color:#b45309;':''; ?>">
                <i class="fas fa-clock"></i> Chờ duyệt <span class="count" style="<?php echo $count_pending>0?'background:#fef3c7; color:#92400e; font-weight:700;':''; ?>"><?php echo $count_pending; ?></span>
            </a>
            <a href="comments.php?status=approved" class="stat-tab <?php echo $status_filter==='approved'?'active':''; ?>">
                <i class="fas fa-check-circle"></i> Đã duyệt <span class="count"><?php echo $count_approved; ?></span>
            </a>
            <a href="comments.php?status=rejected" class="stat-tab <?php echo $status_filter==='rejected'?'active':''; ?>">
                <i class="fas fa-ban"></i> Đã khóa / Từ chối <span class="count"><?php echo $count_rejected; ?></span>
            </a>
        </div>

        <!-- Filter Bar -->
        <form method="GET" class="filter-bar">
            <div style="flex: 1; min-width: 220px;">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm theo nội dung, tên người dùng, tên sản phẩm..." style="width: 100%; box-sizing: border-box;">
            </div>
            <div>
                <select name="status">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>⏳ Chờ duyệt (Pending)</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>✅ Đã duyệt (Approved)</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>🚫 Đã khóa / Ẩn (Locked/Rejected)</option>
                </select>
            </div>
            <button type="submit"><i class="fas fa-filter"></i> Lọc</button>
            <?php if ($status_filter !== '' || $search !== ''): ?>
                <a href="comments.php" class="btn" style="background: #64748b; color: #fff; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-weight: 700; font-size: 13px;">Đặt lại</a>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 180px;">Sản phẩm</th>
                    <th style="width: 160px;">Người bình luận</th>
                    <th style="width: 80px; text-align: center;">Đánh giá</th>
                    <th>Nội dung bình luận</th>
                    <th style="width: 120px; text-align: center;">Trạng thái</th>
                    <th style="width: 110px;">Thời gian</th>
                    <th style="width: 210px; text-align: center;">Thao tác quản trị</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($comments)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b; padding: 30px;">Không tìm thấy bình luận nào phù hợp!</td>
                </tr>
                <?php else: ?>
                    <?php 
                    $fallbackSvg = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='44' height='44' viewBox='0 0 44 44' fill='%23e2e8f0'><rect width='44' height='44' rx='4'/><text x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-size='9' fill='%2364748b'>No Img</text></svg>";
                    foreach ($comments as $c): 
                        $pImg = $c['image_url'] ?? '';
                        if (!empty($pImg) && strpos($pImg, 'http://') !== 0 && strpos($pImg, 'https://') !== 0 && strpos($pImg, 'data:') !== 0 && strpos($pImg, '//') !== 0) {
                            $pImg = '../' . ltrim($pImg, '/');
                        }
                        if (empty($pImg)) {
                            $pImg = $fallbackSvg;
                        }
                        $uId = intval($c['user_id'] ?? 0);
                        $isUserLocked = isset($c['user_account_status']) && intval($c['user_account_status']) === 0;
                    ?>
                    <tr>
                        <td><strong>#<?php echo $c['id']; ?></strong></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <img src="<?php echo htmlspecialchars($pImg); ?>" class="product-thumb" onerror="this.onerror=null; this.src='<?php echo $fallbackSvg; ?>'">
                                <div>
                                    <strong style="font-size: 13px; color: #0f172a; display: block; max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($c['product_name'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($c['product_name'] ?? ('SP #' . $c['product_id'])); ?>
                                    </strong>
                                    <a href="../product_detail.php?id=<?php echo $c['product_id']; ?>" target="_blank" style="font-size: 11px; color: #0284c7; text-decoration: none;"><i class="fas fa-external-link-alt"></i> Xem SP</a>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong style="color: #0f172a; font-size: 13px;"><?php echo htmlspecialchars($c['user_name']); ?></strong>
                            <?php if (!empty($c['user_email'])): ?>
                                <div style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($c['user_email']); ?></div>
                            <?php endif; ?>
                            
                            <?php if ($uId > 0): ?>
                                <?php if ($isUserLocked): ?>
                                    <span class="user-locked-badge"><i class="fas fa-user-lock"></i> User bị Khóa</span>
                                <?php else: ?>
                                    <span class="user-active-badge"><i class="fas fa-user-check"></i> User Hoạt động</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <div style="color: #f59e0b; font-size: 12px; font-weight: 700;">
                                <?php echo intval($c['rating']); ?> <i class="fas fa-star"></i>
                            </div>
                        </td>
                        <td>
                            <p style="margin: 0; font-size: 13px; color: #334155; line-height: 1.4; word-break: break-word;">
                                <?php echo nl2br(htmlspecialchars($c['comment'])); ?>
                            </p>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($c['status'] === 'approved'): ?>
                                <span class="status-badge status-approved"><i class="fas fa-check-circle"></i> Đã duyệt</span>
                            <?php elseif ($c['status'] === 'pending'): ?>
                                <span class="status-badge status-pending"><i class="fas fa-clock"></i> Chờ duyệt</span>
                            <?php else: ?>
                                <span class="status-badge status-rejected"><i class="fas fa-ban"></i> Đã khóa/Ẩn</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 12px; color: #64748b;">
                            <?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 4px; justify-content: center; flex-wrap: wrap;">
                                <!-- 1. NÚT DUYỆT -->
                                <?php if ($c['status'] !== 'approved'): ?>
                                    <a href="comments.php?approve_id=<?php echo $c['id']; ?>" class="btn" style="background: #15803d; color: #fff; padding: 4px 8px; font-size: 11px; text-decoration: none; border-radius: 4px; font-weight: 700;" title="Duyệt bình luận này để hiển thị trên web">
                                        <i class="fas fa-check"></i> Duyệt
                                    </a>
                                <?php endif; ?>

                                <!-- 2. NÚT KHÓA BÌNH LUẬN (Tách riêng với xóa) -->
                                <?php if ($c['status'] !== 'rejected'): ?>
                                    <a href="comments.php?lock_comment_id=<?php echo $c['id']; ?>" class="btn" style="background: #ea580c; color: #fff; padding: 4px 8px; font-size: 11px; text-decoration: none; border-radius: 4px; font-weight: 700;" title="Khóa/Ẩn bình luận này khỏi trang sản phẩm">
                                        <i class="fas fa-lock"></i> Khóa BL
                                    </a>
                                <?php else: ?>
                                    <a href="comments.php?unlock_comment_id=<?php echo $c['id']; ?>" class="btn" style="background: #0284c7; color: #fff; padding: 4px 8px; font-size: 11px; text-decoration: none; border-radius: 4px; font-weight: 700;" title="Mở khóa bình luận này">
                                        <i class="fas fa-unlock"></i> Mở BL
                                    </a>
                                <?php endif; ?>

                                <!-- 3. NÚT KHÓA / MỞ KHÓA USER -->
                                <?php if ($uId > 0): ?>
                                    <?php if (!$isUserLocked): ?>
                                        <a href="comments.php?lock_user_id=<?php echo $uId; ?>" onclick="return confirm('Bạn có chắc muốn KHÓA tài khoản người dùng này (sẽ chặn tài khoản này gửi bình luận)?')" class="btn" style="background: #7c3aed; color: #fff; padding: 4px 8px; font-size: 11px; text-decoration: none; border-radius: 4px; font-weight: 700;" title="Khóa tài khoản người dùng này">
                                            <i class="fas fa-user-slash"></i> Khóa User
                                        </a>
                                    <?php else: ?>
                                        <a href="comments.php?unlock_user_id=<?php echo $uId; ?>" class="btn" style="background: #059669; color: #fff; padding: 4px 8px; font-size: 11px; text-decoration: none; border-radius: 4px; font-weight: 700;" title="Mở khóa tài khoản người dùng">
                                            <i class="fas fa-user-check"></i> Mở User
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- 4. NÚT XÓA VĨNH VIỄN -->
                                <a href="comments.php?delete_id=<?php echo $c['id']; ?>" onclick="return confirm('Bạn có chắc chắn muốn XÓA VĨNH VIỄN bình luận #<?php echo $c['id']; ?> khỏi Database? Hành động này không thể hoàn tác!')" class="btn" style="background: #dc2626; color: #fff; padding: 4px 8px; font-size: 11px; text-decoration: none; border-radius: 4px; font-weight: 700;" title="Xóa vĩnh viễn khỏi Database">
                                    <i class="fas fa-trash"></i> Xóa
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
