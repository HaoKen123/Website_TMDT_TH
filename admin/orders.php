<?php
session_start();
require_once '../db.php';
require_once '../lang.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Single Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && !isset($_POST['action'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    header('Location: orders.php?msg=updated');
    exit;
}

// Single Order Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_single') {
    $order_id = intval($_POST['delete_id']);
    if ($order_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        // Also delete order items
        $stmtItem = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmtItem->execute([$order_id]);
    }
    header('Location: orders.php?msg=deleted');
    exit;
}

// Bulk Order Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_selected') {
    if (!empty($_POST['selected_orders']) && is_array($_POST['selected_orders'])) {
        $ids = array_map('intval', $_POST['selected_orders']);
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id IN ($in)");
        $stmt->execute($ids);
        $stmtItem = $pdo->prepare("DELETE FROM order_items WHERE order_id IN ($in)");
        $stmtItem->execute($ids);
    }
    header('Location: orders.php?msg=bulk_deleted');
    exit;
}

// Fetch all products for the product filter dropdown
$all_products = [];
try {
    $all_products = $pdo->query("SELECT id, name FROM products ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {}

// Filters logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$payment_filter = isset($_GET['payment_status']) ? trim($_GET['payment_status']) : '';
$product_filter = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(o.id = :search_id OR o.customer_name LIKE :search_txt OR o.customer_phone LIKE :search_txt OR o.customer_address LIKE :search_txt)";
    $params[':search_id'] = intval($search);
    $params[':search_txt'] = '%' . $search . '%';
}

if ($status_filter !== '') {
    $where[] = "o.status = :st";
    $params[':st'] = $status_filter;
}

if ($payment_filter !== '') {
    $where[] = "o.payment_status = :pay_st";
    $params[':pay_st'] = $payment_filter;
}

if ($product_filter > 0) {
    $where[] = "EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.product_id = :p_id)";
    $params[':p_id'] = $product_filter;
}

if ($from_date !== '') {
    $where[] = "DATE(o.created_at) >= :from_d";
    $params[':from_d'] = $from_date;
}

if ($to_date !== '') {
    $where[] = "DATE(o.created_at) <= :to_d";
    $params[':to_d'] = $to_date;
}

$whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmtOrders = $pdo->prepare("SELECT o.* FROM orders o $whereSql ORDER BY o.id DESC");
$stmtOrders->execute($params);
$orders = $stmtOrders->fetchAll();

// Fetch order items map for instant fast modal preview
$order_items_map = [];
try {
    $stmtAllItems = $pdo->query("SELECT oi.*, p.name as product_name, p.image_url 
                                 FROM order_items oi 
                                 LEFT JOIN products p ON oi.product_id = p.id");
    while ($it = $stmtAllItems->fetch(PDO::FETCH_ASSOC)) {
        $oid = $it['order_id'];
        if (!isset($order_items_map[$oid])) {
            $order_items_map[$oid] = [];
        }
        $order_items_map[$oid][] = $it;
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/png" href="../favicon.png?v=2">
    <link rel="shortcut icon" href="../favicon.ico?v=2">
    <meta charset="UTF-8">
    <title>Quản Lý Đơn Hàng - Admin PixelGear</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 18px 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            align-items: flex-end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .filter-group label {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
        }
        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-family: 'Inter';
            font-size: 13px;
            width: 100%;
            box-sizing: border-box;
        }
        .filter-actions {
            display: flex;
            gap: 8px;
        }
        .filter-actions button, .filter-actions a {
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            cursor: pointer;
            border: none;
            height: 38px;
            box-sizing: border-box;
        }
        .btn-filter { background: #15803d; color: #fff; }
        .btn-filter:hover { background: #166534; }
        .btn-reset { background: #64748b; color: #fff; }
        .btn-reset:hover { background: #475569; }

        /* Order details modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.55); z-index: 9999; justify-content: center; align-items: center; }
        .modal-content { background: #fff; padding: 25px; border-radius: 12px; width: 680px; max-width: 95vw; max-height: 90vh; overflow-y: auto; text-align: left; box-shadow: 0 15px 35px rgba(0,0,0,0.25); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 18px; }
        .modal-header h3 { margin: 0; color: #0f172a; font-size: 18px; display: flex; align-items: center; gap: 8px; }
        .modal-close { background: none; border: none; font-size: 20px; color: #64748b; cursor: pointer; }
        
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 15px; }
        .detail-item { font-size: 13px; line-height: 1.6; }
        .detail-item strong { color: #1e293b; }

        .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 13px; }
        .items-table th { background: #f1f5f9; padding: 10px; text-align: left; border-bottom: 2px solid #cbd5e1; }
        .items-table td { padding: 10px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        .item-thumb { width: 44px; height: 44px; border-radius: 6px; object-fit: cover; background: #f1f5f9; }

        .summary-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; margin-top: 15px; }
        .summary-line { display: flex; justify-content: space-between; font-size: 13px; margin: 4px 0; }
        .summary-line.total { font-weight: 800; font-size: 16px; color: #15803d; border-top: 1px solid #86efac; padding-top: 8px; margin-top: 8px; }
    </style>
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
        <div class="top-header" style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 20px;">
            <h1>Quản Lý Đơn Hàng (<?php echo count($orders); ?>)</h1>
            <div>
                <button type="button" class="btn btn-danger" style="padding: 10px 18px;" onclick="submitBulkDelete();">
                    <i class="fas fa-trash-alt"></i> Xóa các đơn đã chọn (<span id="selectedCount">0</span>)
                </button>
            </div>
        </div>

        <!-- Bộ Lọc Đơn Hàng Nâng Cao -->
        <div class="filter-card">
            <form method="GET" class="filter-grid">
                <div class="filter-group" style="grid-column: span 1;">
                    <label><i class="fas fa-search"></i> Từ khóa tìm kiếm</label>
                    <input type="text" name="search" placeholder="Mã đơn, Tên khách, SĐT, Địa chỉ..." value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-tasks"></i> Trạng thái đơn</label>
                    <select name="status">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="Chờ xác nhận" <?php echo $status_filter==='Chờ xác nhận'?'selected':''; ?>>1. Chờ xác nhận</option>
                        <option value="Đã xác nhận" <?php echo $status_filter==='Đã xác nhận'?'selected':''; ?>>2. Đã xác nhận</option>
                        <option value="Đang giao" <?php echo $status_filter==='Đang giao'?'selected':''; ?>>3. Đang giao</option>
                        <option value="Hoàn thành" <?php echo $status_filter==='Hoàn thành'?'selected':''; ?>>4. Hoàn thành</option>
                        <option value="Đã hủy" <?php echo $status_filter==='Đã hủy'?'selected':''; ?>>❌ Đã hủy</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-credit-card"></i> Thanh toán</label>
                    <select name="payment_status">
                        <option value="">-- Tất cả thanh toán --</option>
                        <option value="Đã thanh toán" <?php echo $payment_filter==='Đã thanh toán'?'selected':''; ?>>Đã thanh toán</option>
                        <option value="Chưa thanh toán" <?php echo $payment_filter==='Chưa thanh toán'?'selected':''; ?>>Chưa thanh toán</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-box-open"></i> Sản phẩm đã mua</label>
                    <select name="product_id">
                        <option value="0">-- Tất cả sản phẩm --</option>
                        <?php foreach ($all_products as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo $product_filter===$p['id']?'selected':''; ?>>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-calendar-alt"></i> Từ ngày</label>
                    <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-calendar-check"></i> Đến ngày</label>
                    <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Lọc</button>
                    <?php if ($search || $status_filter || $payment_filter || $product_filter || $from_date || $to_date): ?>
                        <a href="orders.php" class="btn-reset" title="Xóa bộ lọc"><i class="fas fa-redo"></i> Đặt lại</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <form method="POST" id="bulkDeleteForm">
            <input type="hidden" name="action" value="delete_selected">

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
                        <th style="width: 220px; text-align:center;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding: 40px; color: #777;">
                            Không tìm thấy đơn hàng nào phù hợp với bộ lọc.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): 
                            $orderItems = $order_items_map[$order['id']] ?? [];
                        ?>
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
                                <div style="display:flex; gap:6px;">
                                    <select id="status_<?php echo $order['id']; ?>" style="width:130px; margin-bottom:0; padding:5px; font-size:12px;">
                                        <option value="Chờ xác nhận" selected>1. Chờ xác nhận</option>
                                        <option value="Đã xác nhận">2. Đã xác nhận</option>
                                        <option value="Đang giao">3. Đang giao</option>
                                        <option value="Đã hủy">❌ Hủy đơn</option>
                                    </select>
                                    <button type="button" class="btn btn-primary" style="padding:5px 8px; font-size:12px;" onclick="updateOrderStatus(<?php echo $order['id']; ?>)">Lưu</button>
                                </div>
                                <?php elseif ($order['status'] == 'Đã xác nhận'): ?>
                                <div style="display:flex; gap:6px;">
                                    <select id="status_<?php echo $order['id']; ?>" style="width:130px; margin-bottom:0; padding:5px; font-size:12px;">
                                        <option value="Đã xác nhận" selected>2. Đã xác nhận</option>
                                        <option value="Đang giao">3. Đang giao</option>
                                        <option value="Đã hủy">❌ Hủy đơn</option>
                                    </select>
                                    <button type="button" class="btn btn-primary" style="padding:5px 8px; font-size:12px;" onclick="updateOrderStatus(<?php echo $order['id']; ?>)">Lưu</button>
                                </div>
                                <?php elseif ($order['status'] == 'Đang giao'): ?>
                                <div style="display:flex; gap:6px;">
                                    <select id="status_<?php echo $order['id']; ?>" style="width:130px; margin-bottom:0; padding:5px; font-size:12px;">
                                        <option value="Đang giao" selected>3. Đang giao</option>
                                        <option value="Hoàn thành">4. Hoàn thành</option>
                                        <option value="Đã hủy">❌ Hủy đơn</option>
                                    </select>
                                    <button type="button" class="btn btn-primary" style="padding:5px 8px; font-size:12px;" onclick="updateOrderStatus(<?php echo $order['id']; ?>)">Lưu</button>
                                </div>
                                <?php elseif ($order['status'] == 'Hoàn thành'): ?>
                                    <strong style="color:#15803d; font-size:13px;"><i class="fas fa-check-circle"></i> Hoàn thành</strong>
                                <?php else: ?>
                                    <em style="color:#dc2626; font-size:13px;"><i class="fas fa-times-circle"></i> Đã hủy</em>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <button type="button" class="btn" style="background:#0284c7; color:#fff; padding:5px 8px; font-size:12px; border:none; cursor:pointer; border-radius:4px; font-weight:700; margin-right:3px;" 
                                    onclick='viewOrderDetails(<?php echo json_encode($order, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>, <?php echo json_encode($orderItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>)' title="Xem chi tiết đơn hàng">
                                    <i class="fas fa-eye"></i> Chi tiết
                                </button>
                                <a href="print_order.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn" style="background:#15803d; color:#fff; padding:5px 8px; font-size:12px; text-decoration:none; border-radius:4px; font-weight:700; margin-right:3px;" title="In hóa đơn">
                                    <i class="fas fa-print"></i> In đơn
                                </a>
                                <button type="button" class="btn btn-danger" style="padding:5px 8px; font-size:12px;" onclick="deleteSingleOrder(<?php echo $order['id']; ?>)" title="Xóa đơn">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>

        <!-- Hidden form for updating single order status or single delete -->
        <form id="actionForm" method="POST" style="display:none;">
            <input type="hidden" name="action" id="formAction" value="">
            <input type="hidden" name="order_id" id="formOrderId" value="">
            <input type="hidden" name="status" id="formStatus" value="">
            <input type="hidden" name="delete_id" id="formDeleteId" value="">
        </form>
    </div>

    <!-- Modal Chi Tiết Đơn Hàng -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-receipt" style="color: #15803d;"></i> Chi Tiết Đơn Hàng #<span id="modalOrderNum"></span></h3>
                <button type="button" class="modal-close" onclick="closeOrderModal()">&times;</button>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <p style="margin: 0 0 6px 0;"><strong>Khách hàng:</strong> <span id="mCustomerName"></span></p>
                    <p style="margin: 0 0 6px 0;"><strong>Số điện thoại:</strong> <span id="mCustomerPhone"></span></p>
                    <p style="margin: 0 0 6px 0;"><strong>Địa chỉ giao:</strong> <span id="mCustomerAddress"></span></p>
                </div>
                <div class="detail-item">
                    <p style="margin: 0 0 6px 0;"><strong>Ngày đặt hàng:</strong> <span id="mOrderDate"></span></p>
                    <p style="margin: 0 0 6px 0;"><strong>Phương thức:</strong> <span id="mPaymentMethod"></span></p>
                    <p style="margin: 0 0 6px 0;"><strong>Thanh toán:</strong> <span id="mPaymentStatus" class="badge"></span></p>
                    <p style="margin: 0 0 6px 0;"><strong>Trạng thái:</strong> <span id="mOrderStatus" class="badge"></span></p>
                </div>
            </div>

            <h4 style="margin: 15px 0 8px 0; color: #1e293b; font-size: 14px;"><i class="fas fa-box"></i> Danh Sách Sản Phẩm Trong Đơn</h4>
            <div style="max-height: 220px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                <table class="items-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th style="text-align: center;">Đơn giá</th>
                            <th style="text-align: center;">Số lượng</th>
                            <th style="text-align: right;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody id="mItemsBody"></tbody>
                </table>
            </div>

            <div class="summary-box">
                <div class="summary-line total">
                    <span>Tổng tiền thanh toán:</span>
                    <span id="mTotalAmount">$0.00</span>
                </div>
            </div>

            <!-- Quick Status Change inside Modal -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                <div style="display: flex; gap: 8px; align-items: center;">
                    <label style="font-size: 13px; font-weight: 700; color: #475569;">Đổi trạng thái:</label>
                    <select id="modalQuickStatus" style="padding: 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; width: 140px;">
                        <option value="Chờ xác nhận">Chờ xác nhận</option>
                        <option value="Đã xác nhận">Đã xác nhận</option>
                        <option value="Đang giao">Đang giao</option>
                        <option value="Hoàn thành">Hoàn thành</option>
                        <option value="Đã hủy">Đã hủy</option>
                    </select>
                    <button type="button" class="btn btn-primary" style="padding: 6px 14px; font-size: 13px;" onclick="applyModalStatusChange()">Cập nhật</button>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a id="modalPrintLink" href="#" target="_blank" class="btn" style="background: #15803d; color: #fff; padding: 7px 14px; font-size: 13px; text-decoration: none; border-radius: 6px; font-weight: 700;">
                        <i class="fas fa-print"></i> In đơn hàng
                    </a>
                    <button type="button" class="btn" style="background: #64748b; color: #fff; padding: 7px 14px; font-size: 13px; border: none; border-radius: 6px; font-weight: 700; cursor: pointer;" onclick="closeOrderModal()">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentModalOrderId = 0;

        function viewOrderDetails(order, items) {
            currentModalOrderId = order.id;
            document.getElementById('modalOrderNum').innerText = order.id;
            document.getElementById('mCustomerName').innerText = order.customer_name || 'N/A';
            document.getElementById('mCustomerPhone').innerText = order.customer_phone || 'N/A';
            document.getElementById('mCustomerAddress').innerText = order.customer_address || 'N/A';
            document.getElementById('mOrderDate').innerText = order.created_at || 'N/A';
            document.getElementById('mPaymentMethod').innerText = order.payment_method || 'COD';
            
            const paySt = document.getElementById('mPaymentStatus');
            paySt.innerText = order.payment_status || 'Chưa thanh toán';
            paySt.className = 'badge ' + (order.payment_status === 'Đã thanh toán' ? 'success' : 'danger');

            const ordSt = document.getElementById('mOrderStatus');
            ordSt.innerText = order.status || 'Chờ xác nhận';
            ordSt.className = 'badge ' + (order.status === 'Hoàn thành' ? 'success' : (order.status === 'Đã hủy' ? 'danger' : 'pending'));

            document.getElementById('modalQuickStatus').value = order.status;
            document.getElementById('modalPrintLink').href = 'print_order.php?id=' + order.id;
            document.getElementById('mTotalAmount').innerText = '$' + parseFloat(order.total_amount).toFixed(2);

            // Render Items
            const tbody = document.getElementById('mItemsBody');
            tbody.innerHTML = '';
            if (items && items.length > 0) {
                items.forEach(it => {
                    const price = parseFloat(it.price) || 0;
                    const qty = parseInt(it.quantity) || 1;
                    const sub = price * qty;
                    const imgUrl = it.image_url || 'https://via.placeholder.com/44';
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><img src="${imgUrl}" class="item-thumb" onerror="this.src='https://via.placeholder.com/44'"></td>
                        <td><strong>${it.product_name || ('Sản phẩm #' + it.product_id)}</strong></td>
                        <td style="text-align: center;">$${price.toFixed(2)}</td>
                        <td style="text-align: center; font-weight: 700;">${qty}</td>
                        <td style="text-align: right; font-weight: 700; color: #15803d;">$${sub.toFixed(2)}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color:#64748b;">Không có chi tiết sản phẩm.</td></tr>';
            }

            document.getElementById('orderDetailsModal').style.display = 'flex';
        }

        function closeOrderModal() {
            document.getElementById('orderDetailsModal').style.display = 'none';
        }

        function applyModalStatusChange() {
            if (currentModalOrderId > 0) {
                const newSt = document.getElementById('modalQuickStatus').value;
                document.getElementById('formAction').value = '';
                document.getElementById('formOrderId').value = currentModalOrderId;
                document.getElementById('formStatus').value = newSt;
                document.getElementById('actionForm').submit();
            }
        }

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

        function submitBulkDelete() {
            const checked = document.querySelectorAll('.order-checkbox:checked');
            if (checked.length === 0) {
                alert('Vui lòng chọn ít nhất 1 đơn hàng để xóa!');
                return;
            }
            if (confirm(`Bạn có chắc chắn muốn XÓA ${checked.length} đơn hàng đã chọn không?`)) {
                document.getElementById('bulkDeleteForm').submit();
            }
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
