<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Single Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && !isset($_POST['action'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    header('Location: orders.php');
    exit;
}

// Single Order Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_single') {
    $order_id = intval($_POST['delete_id']);
    if ($order_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
    }
    header('Location: orders.php');
    exit;
}

// Bulk Order Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_selected') {
    if (!empty($_POST['selected_orders']) && is_array($_POST['selected_orders'])) {
        $ids = array_map('intval', $_POST['selected_orders']);
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id IN ($in)");
        $stmt->execute($ids);
    }
    header('Location: orders.php');
    exit;
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Đơn Hàng - Admin</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <h2>PIXELGEAR</h2>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Tổng quan</a></li>
            <li><a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
            <li><a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a></li>
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
        <form method="POST" id="bulkDeleteForm">
            <div class="top-header" style="display:flex; justify-space-between; align-items:center;">
                <h1>Quản Lý Đơn Hàng</h1>
                <div>
                    <button type="submit" name="action" value="delete_selected" class="btn btn-danger" style="padding: 10px 18px;" onclick="return confirmBulkDelete();">
                        <i class="fas fa-trash-alt"></i> Xóa các đơn đã chọn (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 40px; text-align:center;">
                            <input type="checkbox" id="selectAll" style="width:18px; height:18px; cursor:pointer; margin:0;">
                        </th>
                        <th>Mã ĐH</th>
                        <th>Khách hàng</th>
                        <th>Tổng tiền</th>
                        <th>Thanh toán</th>
                        <th>Trạng thái</th>
                        <th>Cập nhật trạng thái</th>
                        <th style="width: 160px; text-align:center;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding: 30px; color: #777;">
                            Chưa có đơn hàng nào trong hệ thống.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td style="text-align:center;">
                                <input type="checkbox" name="selected_orders[]" value="<?php echo $order['id']; ?>" class="order-checkbox" style="width:18px; height:18px; cursor:pointer; margin:0;">
                            </td>
                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                <span style="font-size:12px; color:#666;"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                            </td>
                            <td style="font-weight:700; color:#15803d;">$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge <?php echo $order['payment_status'] == 'Đã thanh toán' ? 'success' : 'danger'; ?>">
                                    <?php echo $order['payment_status']; ?>
                                </span><br>
                                <span style="font-size:11px;"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                            </td>
                            <td>
                                <span class="badge <?php echo $order['status'] == 'Đã hủy' ? 'danger' : ($order['status'] == 'Hoàn thành' ? 'success' : 'pending'); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($order['status'] == 'Chờ xác nhận'): ?>
                                <div style="display:flex; gap:10px;">
                                    <select id="status_<?php echo $order['id']; ?>" style="width:130px; margin-bottom:0; padding:5px;">
                                        <option value="Chờ xác nhận" selected>1. Chờ xác nhận</option>
                                        <option value="Đã xác nhận">2. Đã xác nhận</option>
                                        <option value="Đang giao">3. Đang giao</option>
                                        <option value="Đã hủy">❌ Hủy đơn</option>
                                    </select>
                                    <button type="button" class="btn btn-primary" style="padding:5px 10px;" onclick="updateOrderStatus(<?php echo $order['id']; ?>)">Lưu</button>
                                </div>
                                <?php elseif ($order['status'] == 'Đã xác nhận'): ?>
                                <div style="display:flex; gap:10px;">
                                    <select id="status_<?php echo $order['id']; ?>" style="width:130px; margin-bottom:0; padding:5px;">
                                        <option value="Đã xác nhận" selected>2. Đã xác nhận</option>
                                        <option value="Đang giao">3. Đang giao</option>
                                        <option value="Đã hủy">❌ Hủy đơn</option>
                                    </select>
                                    <button type="button" class="btn btn-primary" style="padding:5px 10px;" onclick="updateOrderStatus(<?php echo $order['id']; ?>)">Lưu</button>
                                </div>
                                <?php elseif ($order['status'] == 'Đang giao'): ?>
                                <div style="display:flex; gap:10px;">
                                    <select id="status_<?php echo $order['id']; ?>" style="width:130px; margin-bottom:0; padding:5px;">
                                        <option value="Đang giao" selected>3. Đang giao</option>
                                        <option value="Hoàn thành">4. Hoàn thành</option>
                                        <option value="Đã hủy">❌ Hủy đơn</option>
                                    </select>
                                    <button type="button" class="btn btn-primary" style="padding:5px 10px;" onclick="updateOrderStatus(<?php echo $order['id']; ?>)">Lưu</button>
                                </div>
                                <?php elseif ($order['status'] == 'Hoàn thành'): ?>
                                    <strong style="color:#15803d;"><i class="fas fa-check-circle"></i> Hoàn thành</strong>
                                <?php else: ?>
                                    <em style="color:#dc2626;"><i class="fas fa-times-circle"></i> Đã hủy</em>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <a href="print_order.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn" style="background:#15803d; color:#fff; padding:5px 8px; font-size:12px; text-decoration:none; border-radius:4px; font-weight:700; margin-right:4px;" title="In hóa đơn">
                                    <i class="fas fa-print"></i> In đơn
                                </a>
                                <button type="button" class="btn btn-danger" style="padding:5px 8px; font-size:12px;" onclick="deleteSingleOrder(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>

        <!-- Hidden form for updating single order status or single delete without submitting bulk form -->
        <form id="actionForm" method="POST" style="display:none;">
            <input type="hidden" name="action" id="formAction" value="">
            <input type="hidden" name="order_id" id="formOrderId" value="">
            <input type="hidden" name="status" id="formStatus" value="">
            <input type="hidden" name="delete_id" id="formDeleteId" value="">
        </form>
    </div>

    <script>
        const selectAll = document.getElementById('selectAll');
        const orderCheckboxes = document.querySelectorAll('.order-checkbox');
        const selectedCount = document.getElementById('selectedCount');

        function updateCount() {
            const checked = document.querySelectorAll('.order-checkbox:checked');
            selectedCount.textContent = checked.length;
            if (selectAll) {
                selectAll.checked = orderCheckboxes.length > 0 && checked.length === orderCheckboxes.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                orderCheckboxes.forEach(cb => cb.checked = selectAll.checked);
                updateCount();
            });
        }

        orderCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateCount);
        });

        function confirmBulkDelete() {
            const checked = document.querySelectorAll('.order-checkbox:checked');
            if (checked.length === 0) {
                alert('Vui lòng chọn ít nhất 1 đơn hàng để xóa!');
                return false;
            }
            return confirm(`Bạn có chắc chắn muốn xóa ${checked.length} đơn hàng đã chọn không?`);
        }

        function updateOrderStatus(orderId) {
            const selectEl = document.getElementById('status_' + orderId);
            if (!selectEl) return;
            document.getElementById('formAction').value = '';
            document.getElementById('formOrderId').value = orderId;
            document.getElementById('formStatus').value = selectEl.value;
            document.getElementById('actionForm').submit();
        }

        function deleteSingleOrder(orderId) {
            if (confirm(`Bạn có chắc muốn xóa đơn hàng #${orderId} không?`)) {
                document.getElementById('formAction').value = 'delete_single';
                document.getElementById('formDeleteId').value = orderId;
                document.getElementById('actionForm').submit();
            }
        }
    </script>
</body>
</html>
