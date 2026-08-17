<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../db.php';
require_once '../lang.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Chỉ Quản trị viên mới được quản lý danh mục
if (($_SESSION['admin_role'] ?? 'admin') !== 'admin') {
    header('Location: index.php?error=no_permission');
    exit;
}

$msg = '';
$error = '';

// Add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }
    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, status) VALUES (?, ?, 1)");
            $stmt->execute([$name, $slug]);
            $msg = "Đã thêm danh mục mới '$name' thành công!";
        } catch (Exception $e) {
            $error = "Lỗi: Tên hoặc đường dẫn (slug) danh mục đã tồn tại!";
        }
    }
}

// Edit category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = intval($_POST['cat_id']);
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $status = intval($_POST['status'] ?? 1);

    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }

    if (!empty($name) && $id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $status, $id]);
            $msg = "Đã cập nhật thông tin danh mục #$id ($name) thành công!";
        } catch (Exception $e) {
            $error = "Lỗi: Không thể cập nhật hoặc mã Slug '$slug' đã bị trùng!";
        }
    }
}

// Toggle Hide/Show Status
if (isset($_GET['toggle_id'])) {
    $id = intval($_GET['toggle_id']);
    $stmt = $pdo->prepare("UPDATE categories SET status = IF(status=1, 0, 1) WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: categories.php?msg=status_updated');
    exit;
}

// Delete category
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: categories.php?msg=deleted');
    exit;
}

$categories = [];
try {
    $categories = $pdo->query("
        SELECT c.*, 
        (
            SELECT COUNT(*) FROM products p 
            WHERE p.category COLLATE utf8mb4_unicode_ci = c.slug COLLATE utf8mb4_unicode_ci 
            OR (c.slug = 'clothing' AND p.category IN ('clothing', 'tshirts', 'cosplay'))
            OR (c.slug = 'accessories' AND p.category IN ('accessories', 'hats', 'keychains'))
            OR (c.slug = 'toys' AND p.category IN ('toys', 'toys_models', 'plushies'))
            OR (c.slug = 'decor' AND p.category IN ('decor', 'lights'))
        ) as product_count 
        FROM categories c 
        ORDER BY c.id ASC
    ")->fetchAll();
} catch (Exception $e) {
}

if (empty($categories)) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            status TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("INSERT INTO categories (id, name, slug, status) VALUES 
            (1, 'Quần áo & Hoodies', 'clothing', 1),
            (2, 'Phụ kiện Minecraft', 'accessories', 1),
            (3, 'Đồ chơi & Gấu bông', 'toys', 1),
            (4, 'Đèn & Trang trí', 'decor', 1)
            ON DUPLICATE KEY UPDATE status = 1
        ");

        $categories = $pdo->query("
            SELECT c.*, 
            (
                SELECT COUNT(*) FROM products p 
                WHERE p.category COLLATE utf8mb4_unicode_ci = c.slug COLLATE utf8mb4_unicode_ci 
                OR (c.slug = 'clothing' AND p.category IN ('clothing', 'tshirts', 'cosplay'))
                OR (c.slug = 'accessories' AND p.category IN ('accessories', 'hats', 'keychains'))
                OR (c.slug = 'toys' AND p.category IN ('toys', 'toys_models', 'plushies'))
                OR (c.slug = 'decor' AND p.category IN ('decor', 'lights'))
            ) as product_count 
            FROM categories c 
            ORDER BY c.id ASC
        ")->fetchAll();
    } catch (Exception $ex) {
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <link rel="icon" type="image/png" href="../favicon.png?v=2">
    <link rel="shortcut icon" href="../favicon.ico?v=2">
    <meta charset="UTF-8">
    <title>Quản Lý Danh Mục Sản Phẩm - Admin PixelGear</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .layout-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 30px;
        }

        .card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }

        .card h3 {
            margin-bottom: 15px;
            font-size: 18px;
            color: #1e293b;
        }

        .card form label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            color: #475569;
            margin-bottom: 5px;
        }

        .card form input,
        .card form select {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-family: 'Inter';
            box-sizing: border-box;
            margin-bottom: 15px;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-show {
            background: #dcfce7;
            color: #166534;
        }

        .status-hide {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            width: 440px;
            text-align: left;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-content input,
        .modal-content select {
            width: 100%;
            padding: 10px;
            margin: 8px 0 15px 0;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-family: 'Inter';
            box-sizing: border-box;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h2>PIXELGEAR</h2>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Tổng quan</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
            <li><a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a></li>
            <li><a href="categories.php" class="active"><i class="fas fa-list"></i> Danh mục</a></li>
            <li><a href="coupons.php"><i class="fas fa-ticket-alt"></i> Mã giảm giá</a></li>
            <li><a href="shipping.php"><i class="fas fa-truck"></i> Phí vận chuyển</a></li>
            <li><a href="comments.php"><i class="fas fa-comments"></i> Bình luận</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Khách hàng & Nhân viên</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <h1>Quản Lý Danh Mục Sản Phẩm (<?php echo count($categories); ?>)</h1>

        <?php if ($msg || isset($_GET['msg'])): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i>
                <?php echo $msg ? $msg : 'Đã cập nhật danh mục thành công!'; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="layout-grid">
            <div class="card">
                <h3><i class="fas fa-plus-circle" style="color: #15803d;"></i> Thêm Danh Mục Mới</h3>
                <form method="POST">
                    <input type="hidden" name="add_category" value="1">
                    <label>Tên danh mục *</label>
                    <input type="text" name="name" required placeholder="Ví dụ: Áo Khoác Minecraft...">

                    <label>Mã danh mục (Slug/URL)</label>
                    <input type="text" name="slug" placeholder="Ví dụ: ao-khoac (Tự sinh nếu trống)">

                    <button type="submit" class="btn"
                        style="width: 100%; padding: 12px; background: #15803d; color: #fff; border: none; font-weight: 700; border-radius: 6px; cursor: pointer;">THÊM
                        DANH MỤC</button>
                </form>
            </div>

            <div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Tên danh mục</th>
                            <th>Mã Slug</th>
                            <th>Số sản phẩm</th>
                            <th>Trạng thái</th>
                            <th style="width: 220px; text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat):
                            $cSt = intval($cat['status']);
                            ?>
                            <tr>
                                <td><strong>#<?php echo $cat['id']; ?></strong></td>
                                <td><strong
                                        style="color: #0f172a; font-size: 15px;"><?php echo htmlspecialchars($cat['name']); ?></strong>
                                </td>
                                <td><code
                                        style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #0284c7;"><?php echo htmlspecialchars($cat['slug']); ?></code>
                                </td>
                                <td><span class="badge pending"
                                        style="background: #e0f2fe; color: #0369a1; font-weight: 700;"><?php echo $cat['product_count']; ?>
                                        SP</span></td>
                                <td>
                                    <?php if ($cSt === 1): ?>
                                        <span class="status-badge status-show"><i class="fas fa-eye"></i> Hiển thị</span>
                                    <?php else: ?>
                                        <span class="status-badge status-hide"><i class="fas fa-eye-slash"></i> Đã ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="btn"
                                        style="background: #0284c7; color: #fff; padding: 5px 9px; font-size: 12px; border:none; cursor:pointer; border-radius: 4px; font-weight: 700; margin-right: 3px;"
                                        onclick="openEditCatModal(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars(addslashes($cat['name'])); ?>', '<?php echo htmlspecialchars(addslashes($cat['slug'])); ?>', <?php echo $cSt; ?>)">
                                        <i class="fas fa-edit"></i> Sửa
                                    </button>
                                    <a href="categories.php?toggle_id=<?php echo $cat['id']; ?>" class="btn"
                                        style="background: <?php echo $cSt === 1 ? '#64748b' : '#16a34a'; ?>; color: #fff; padding: 5px 8px; font-size: 12px; text-decoration: none; border-radius: 4px; font-weight: 700; margin-right: 3px;">
                                        <i class="fas fa-<?php echo $cSt === 1 ? 'eye-slash' : 'eye'; ?>"></i>
                                        <?php echo $cSt === 1 ? 'Ẩn' : 'Hiện'; ?>
                                    </a>
                                    <a href="categories.php?delete_id=<?php echo $cat['id']; ?>"
                                        onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này không?')"
                                        class="btn"
                                        style="background: #dc2626; color: #fff; padding: 5px 8px; font-size: 12px; text-decoration: none; border-radius: 4px; font-weight: 700;">
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

    <!-- Modal Chỉnh Sửa Danh Mục -->
    <div id="editCatModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 15px; color: #0f172a;"><i class="fas fa-edit" style="color: #0284c7;"></i> Chỉnh
                Sửa Danh Mục</h3>
            <form method="POST">
                <input type="hidden" name="edit_category" value="1">
                <input type="hidden" name="cat_id" id="editCatId">

                <label style="font-size: 13px; font-weight: 600;">Tên danh mục *</label>
                <input type="text" name="name" id="editCatName" required placeholder="Tên danh mục...">

                <label style="font-size: 13px; font-weight: 600;">Mã Slug / URL *</label>
                <input type="text" name="slug" id="editCatSlug" required placeholder="Mã slug...">

                <label style="font-size: 13px; font-weight: 600;">Trạng thái hiển thị</label>
                <select name="status" id="editCatStatus">
                    <option value="1">Hiển thị ngoài Website</option>
                    <option value="0">Tạm ẩn</option>
                </select>

                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" class="btn"
                        style="flex: 1; padding: 10px; background: #15803d; color: #fff; border: none; font-weight: 700; border-radius: 6px; cursor: pointer;">LƯU
                        THAY ĐỔI</button>
                    <button type="button" class="btn"
                        style="flex: 1; padding: 10px; background: #64748b; color: #fff; border: none; font-weight: 700; border-radius: 6px; cursor: pointer;"
                        onclick="closeEditCatModal()">HỦY</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditCatModal(id, name, slug, status) {
            document.getElementById('editCatId').value = id;
            document.getElementById('editCatName').value = name;
            document.getElementById('editCatSlug').value = slug;
            document.getElementById('editCatStatus').value = status;
            document.getElementById('editCatModal').style.display = 'flex';
        }
        function closeEditCatModal() {
            document.getElementById('editCatModal').style.display = 'none';
        }
    </script>
</body>

</html>