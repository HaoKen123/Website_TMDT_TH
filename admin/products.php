<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Tự động kiểm tra và thêm cột stock, status vào bảng products nếu thiếu
try {
    $prodCols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('stock', $prodCols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN stock INT NOT NULL DEFAULT 50");
    }
    if (!in_array('status', $prodCols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Hiển thị, 0: Ẩn'");
    }
} catch (Exception $e) {
}

// Toggle status (Show / Hide product)
if (isset($_GET['toggle_status_id'])) {
    $pid = intval($_GET['toggle_status_id']);
    $stmtSt = $pdo->prepare("UPDATE products SET status = IF(status=1, 0, 1) WHERE id = ?");
    $stmtSt->execute([$pid]);
    header('Location: products.php?msg=status_updated');
    exit;
}

$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <link rel="icon" type="image/png" href="../favicon.png?v=2">
    <link rel="shortcut icon" href="../favicon.ico?v=2">
    <meta charset="UTF-8">
    <title>Quản lý Sản Phẩm - Admin PixelGear</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .action-bar {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn-delete-selected {
            background: #dc2626;
            color: #ffffff;
            padding: 9px 18px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 700;
            font-size: 13px;
            display: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        }

        .btn-delete-selected:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }

        .row-removing {
            opacity: 0;
            transform: translateX(30px);
            transition: all 0.3s ease;
        }

        .toast-notify {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: #16a34a;
            color: #ffffff;
            padding: 14px 24px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            display: none;
            z-index: 9999;
            font-weight: 700;
            font-size: 14px;
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

        .checkbox-cell {
            width: 45px;
            text-align: center;
        }

        .checkbox-cell input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #dc2626;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h2>PIXELGEAR</h2>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Tổng quan</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
            <li><a href="products.php" class="active"><i class="fas fa-box"></i> Sản phẩm</a></li>
            <li><a href="categories.php"><i class="fas fa-list"></i> Danh mục</a></li>
            <li><a href="coupons.php"><i class="fas fa-ticket-alt"></i> Mã giảm giá</a></li>
            <li><a href="shipping.php"><i class="fas fa-truck"></i> Phí vận chuyển</a></li>
            <li><a href="comments.php"><i class="fas fa-comments"></i> Bình luận</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Khách hàng & Nhân viên</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <form id="bulkDeleteForm" method="POST" action="delete_product.php">
            <div class="top-header"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h1>Quản Lý Sản Phẩm (<span id="totalProductCount"><?php echo count($products); ?></span>)</h1>
                <div class="action-bar">
                    <button type="button" id="btnDeleteSelected" class="btn-delete-selected"
                        onclick="deleteSelectedProducts()">
                        <i class="fas fa-trash-alt" style="margin-right: 6px;"></i> Xóa các sản phẩm đã chọn (<span
                            id="selectedCount">0</span>)
                    </button>
                    <a href="add_product.php" class="btn btn-primary"
                        style="padding: 10px 20px; font-weight: 700; background:#15803d; border:none; text-decoration:none;">
                        <i class="fas fa-plus" style="margin-right: 6px;"></i> Thêm sản phẩm
                    </a>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="checkbox-cell">
                            <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)" title="Chọn tất cả">
                        </th>
                        <th style="width: 60px;">ID</th>
                        <th style="width: 70px;">Hình ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Giá bán</th>
                        <th>Tồn kho</th>
                        <th>Trạng thái</th>
                        <th style="width: 220px; text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <?php if (empty($products)): ?>
                        <tr id="emptyRow">
                            <td colspan="9" style="text-align: center; padding: 50px; color: #64748b;">
                                <i class="fas fa-box-open"
                                    style="font-size: 36px; margin-bottom: 10px; color: #cbd5e1; display: block;"></i>
                                Chưa có sản phẩm nào trong kho.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p):
                            $pSt = intval($p['status'] ?? 1);
                            ?>
                            <tr id="product-row-<?php echo $p['id']; ?>">
                                <td class="checkbox-cell">
                                    <input type="checkbox" name="ids[]" class="product-checkbox" value="<?php echo $p['id']; ?>"
                                        onchange="updateSelectedCount()">
                                </td>
                                <td><strong>#<?php echo $p['id']; ?></strong></td>
                                <td>
                                    <?php
                                    $pImg = $p['image_url'];
                                    if (strpos($pImg, 'http://') !== 0 && strpos($pImg, 'https://') !== 0 && strpos($pImg, 'data:') !== 0 && strpos($pImg, '//') !== 0) {
                                        $pImg = '../' . ltrim($pImg, '/');
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($pImg); ?>" class="product-img"
                                        onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'50\' height=\'50\' viewBox=\'0 0 50 50\' fill=\'%23e2e8f0\'><rect width=\'50\' height=\'50\' rx=\'4\'/><text x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-size=\'10\' fill=\'%2364748b\'>No Img</text></svg>';">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                    <?php if (!empty($p['badge'])): ?>
                                        <span
                                            style="font-size: 11px; background: #e2e8f0; color: #475569; padding: 2px 6px; border-radius: 4px; margin-left: 6px; font-weight: 700;"><?php echo htmlspecialchars($p['badge']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge pending"
                                        style="text-transform: uppercase; font-weight:700;"><?php echo htmlspecialchars($p['category']); ?></span>
                                </td>
                                <td style="font-weight: 700; color: #2e7d32;">$<?php echo number_format($p['price'], 2); ?></td>
                                <td><strong style="color:#0369a1;"><?php echo intval($p['stock'] ?? 50); ?></strong></td>
                                <td>
                                    <?php if ($pSt === 1): ?>
                                        <span class="status-badge status-show"><i class="fas fa-eye"></i> Hiển thị</span>
                                    <?php else: ?>
                                        <span class="status-badge status-hide"><i class="fas fa-eye-slash"></i> Đã ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <a href="products.php?toggle_status_id=<?php echo $p['id']; ?>" class="btn"
                                        style="background: <?php echo $pSt === 1 ? '#64748b' : '#16a34a'; ?>; color:#fff; padding: 5px 9px; font-size: 12px; font-weight:700; text-decoration:none; border-radius:4px;">
                                        <i class="fas fa-<?php echo $pSt === 1 ? 'eye-slash' : 'eye'; ?>"></i>
                                        <?php echo $pSt === 1 ? 'Ẩn' : 'Hiện'; ?>
                                    </a>
                                    <a href="edit_product.php?id=<?php echo $p['id']; ?>"
                                        onclick="sessionStorage.setItem('admin_product_scroll_id', <?php echo $p['id']; ?>)"
                                        class="btn"
                                        style="background:#f59e0b; color:#fff; padding: 5px 9px; font-size: 12px; font-weight:700; text-decoration:none; border-radius:4px;"><i
                                            class="fas fa-edit"></i> Sửa</a>
                                    <button type="button" class="btn"
                                        style="padding: 5px 9px; font-size: 12px; border:none; cursor:pointer; background:#dc2626; color:#fff; font-weight:700; border-radius:4px;"
                                        onclick="quickDeleteProduct(<?php echo $p['id']; ?>)">
                                        <i class="fas fa-trash-alt"></i> Xóa
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>

    <!-- Toast Notification -->
    <div id="toastNotify" class="toast-notify">
        <i class="fas fa-check-circle" style="margin-right: 8px;"></i> <span id="toastMessage">Đã xóa sản phẩm thành
            công!</span>
    </div>

    <script>
        function toggleSelectAll(master) {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(cb => cb.checked = master.checked);
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            const checked = document.querySelectorAll('.product-checkbox:checked');
            const count = checked.length;
            const btn = document.getElementById('btnDeleteSelected');
            const selectAll = document.getElementById('selectAll');

            document.getElementById('selectedCount').innerText = count;

            if (checkboxes.length > 0 && count === checkboxes.length) {
                selectAll.checked = true;
            } else {
                selectAll.checked = false;
            }

            if (count > 0) {
                btn.style.display = 'inline-block';
            } else {
                btn.style.display = 'none';
            }
        }

        function showToast(msg) {
            const toast = document.getElementById('toastNotify');
            document.getElementById('toastMessage').innerText = msg;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        // Single Product Delete via AJAX
        function quickDeleteProduct(id) {
            if (!confirm('Bạn có chắc chắn muốn xóa sản phẩm #' + id + ' không?')) {
                return;
            }

            fetch('delete_product.php?id=' + id + '&ajax=1')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const row = document.getElementById('product-row-' + id);
                        if (row) {
                            row.classList.add('row-removing');
                            setTimeout(() => {
                                row.remove();
                                updateTotalCount(-1);
                                updateSelectedCount();
                            }, 300);
                        }
                        showToast('Đã xóa thành công sản phẩm #' + id);
                    } else {
                        alert('Lỗi: ' + (data.message || 'Không thể xóa'));
                    }
                })
                .catch(err => {
                    window.location.href = 'delete_product.php?id=' + id;
                });
        }

        // Bulk Delete Selected Products via Checkbox AJAX
        function deleteSelectedProducts() {
            const checked = document.querySelectorAll('.product-checkbox:checked');
            const ids = Array.from(checked).map(cb => cb.value);

            if (ids.length === 0) return;

            if (!confirm('Bạn có chắc chắn muốn XÓA ' + ids.length + ' sản phẩm đã chọn không?')) {
                return;
            }

            const formData = new FormData();
            ids.forEach(id => formData.append('ids[]', id));
            formData.append('ajax', '1');

            fetch('delete_product.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        ids.forEach(id => {
                            const row = document.getElementById('product-row-' + id);
                            if (row) row.remove();
                        });
                        updateTotalCount(-ids.length);
                        document.getElementById('selectAll').checked = false;
                        updateSelectedCount();
                        showToast('Đã xóa thành công ' + ids.length + ' sản phẩm đã chọn!');
                    } else {
                        alert('Lỗi: ' + (data.message || 'Không thể xóa'));
                    }
                })
                .catch(err => {
                    alert('Có lỗi xảy ra khi xóa sản phẩm');
                });
        }

        function updateTotalCount(delta) {
            const totalEl = document.getElementById('totalProductCount');
            if (totalEl) {
                let curr = parseInt(totalEl.innerText) || 0;
                totalEl.innerText = Math.max(0, curr + delta);
            }
        }

        // Tự động cuộn đến sản phẩm vừa chỉnh sửa sau khi lưu
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const updatedId = urlParams.get('updated_id') || sessionStorage.getItem('admin_product_scroll_id');

            if (updatedId) {
                sessionStorage.removeItem('admin_product_scroll_id');
                const targetRow = document.getElementById('product-row-' + updatedId);
                if (targetRow) {
                    setTimeout(() => {
                        targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        targetRow.style.transition = 'all 0.5s ease';
                        targetRow.style.backgroundColor = '#dcfce7';
                        targetRow.style.boxShadow = '0 0 15px rgba(22, 163, 74, 0.4)';

                        showToast('✨ Đã lưu cập nhật sản phẩm #' + updatedId + ' thành công!');

                        setTimeout(() => {
                            targetRow.style.backgroundColor = '';
                            targetRow.style.boxShadow = '';
                        }, 3500);
                    }, 300);
                }
            }
        });
    </script>
</body>

</html>