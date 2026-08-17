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

// Chỉ Quản trị viên mới được quản lý mã giảm giá & email
if (($_SESSION['admin_role'] ?? 'admin') !== 'admin') {
    header('Location: index.php?error=no_permission');
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
            $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order, expires_at, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$code, $type, $val, $min, $expires_at]);
            $msg = "Đã thêm mã ưu đãi $code thành công!";
        } catch (Exception $e) {
            $error = "Lỗi: Mã coupon này đã tồn tại!";
        }
    } else {
        $error = "Vui lòng nhập đầy đủ mã và giá trị giảm giá hợp lệ.";
    }
}

// Handle Edit Coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_coupon'])) {
    $c_id = intval($_POST['coupon_id']);
    $code = strtoupper(trim($_POST['code']));
    $type = $_POST['discount_type'];
    $val = floatval($_POST['discount_value']);
    $min = floatval($_POST['min_order']);
    $status = $_POST['status'] ?? 'active';
    $expires_at = empty($_POST['expires_at']) ? null : date('Y-m-d H:i:s', strtotime($_POST['expires_at']));

    if ($c_id > 0 && !empty($code) && $val > 0) {
        try {
            // Check if duplicate code on another id
            $stmtChk = $pdo->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
            $stmtChk->execute([$code, $c_id]);
            if ($stmtChk->fetch()) {
                $error = "Lỗi: Mã code '$code' đã bị trùng với voucher khác!";
            } else {
                $stmtUp = $pdo->prepare("UPDATE coupons SET code = ?, discount_type = ?, discount_value = ?, min_order = ?, status = ?, expires_at = ? WHERE id = ?");
                $stmtUp->execute([$code, $type, $val, $min, $status, $expires_at, $c_id]);
                $msg = "Đã cập nhật mã giảm giá #$c_id ($code) thành công!";
            }
        } catch (Exception $e) {
            $error = "Lỗi cập nhật mã giảm giá: " . $e->getMessage();
        }
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

// Handle Delete Subscriber
if (isset($_GET['delete_subscriber'])) {
    $s_id = intval($_GET['delete_subscriber']);
    if ($s_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM subscribers WHERE id = ?");
        $stmt->execute([$s_id]);
        $msg = "Đã xóa email khỏi danh sách nhận bản tin thành công!";
    }
}

// Fetch coupons & subscribers
$coupons = [];
try {
    $coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {
}

$subscribers = [];
try {
    $subscribers = $pdo->query("SELECT * FROM subscribers ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <link rel="icon" type="image/png" href="../favicon.png?v=2">
    <link rel="shortcut icon" href="../favicon.ico?v=2">
    <meta charset="UTF-8">
    <title>Quản Lý Mã Giảm Giá & Email Nhận Tin - Admin PixelGear</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .grid-layout {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .col-main {
            flex: 2;
            min-width: 350px;
        }

        .col-sub {
            flex: 1;
            min-width: 300px;
        }

        .card-box {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            border-bottom: 2px solid #15803d;
            padding-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-family: 'Inter';
            box-sizing: border-box;
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
            width: 480px;
            max-width: 95vw;
            text-align: left;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-content input,
        .modal-content select {
            width: 100%;
            padding: 9px 12px;
            margin: 6px 0 14px 0;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-family: 'Inter';
            box-sizing: border-box;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

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
                    <h3 class="card-title"><i class="fas fa-plus-circle" style="color: #15803d;"></i> Tạo Mã Giảm Giá
                        Mới</h3>
                    <form method="POST">
                        <input type="hidden" name="add_coupon" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Mã Coupon (Code) *</label>
                                <input type="text" name="code" placeholder="VD: SUMMER2026"
                                    style="text-transform: uppercase;" required>
                            </div>
                            <div class="form-group">
                                <label>Loại Giảm Giá *</label>
                                <select name="discount_type">
                                    <option value="percent">Theo phần trăm (%)</option>
                                    <option value="fixed">Số tiền cố định ($/VNĐ)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Giá Trị Giảm (% hoặc $) *</label>
                                <input type="number" step="0.01" name="discount_value"
                                    placeholder="VD: 15 (cho 15%) hoặc 5.00 (cho $5)" required>
                            </div>
                            <div class="form-group">
                                <label>Đơn Hàng Tối Thiểu ($)</label>
                                <input type="number" step="0.01" name="min_order" value="0.00">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Thời Gian Hết Hạn (Tùy chọn)</label>
                            <input type="datetime-local" name="expires_at"
                                style="padding:10px; border:1px solid #cbd5e1; border-radius:6px; width:100%;">
                            <small style="color:#64748b;">Để trống nếu là mã vĩnh viễn.</small>
                        </div>

                        <button type="submit" class="btn btn-primary"
                            style="width: 100%; padding: 12px; font-weight: 700; margin-top:10px; background: #15803d; border: none;">TẠO
                            MÃ KHUYẾN MÃI</button>
                    </form>
                </div>

                <div class="card-box">
                    <h3 class="card-title"><i class="fas fa-list" style="color: #15803d;"></i> Danh Sách Mã Giảm Giá
                        (<?php echo count($coupons); ?>)</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã Code</th>
                                <th>Mức Giảm</th>
                                <th>Đơn Tối Thiểu</th>
                                <th>Hạn Dùng</th>
                                <th>Trạng Thái</th>
                                <th style="width: 140px; text-align: center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coupons as $c):
                                $isExp = !empty($c['expires_at']) && strtotime($c['expires_at']) < time();
                                $cStatus = ($c['status'] ?? 'active') === 'expired' || $isExp ? 'expired' : 'active';
                                ?>
                                <tr>
                                    <td><strong
                                            style="color: #15803d; font-size: 15px; letter-spacing: 1px;"><?php echo htmlspecialchars($c['code']); ?></strong>
                                    </td>
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
                                    <td style="font-size: 12px;">
                                        <?php
                                        if (!empty($c['expires_at'])) {
                                            $exp = strtotime($c['expires_at']);
                                            if ($exp < time()) {
                                                echo '<span style="color:#ef4444; font-weight:600;"><i class="fas fa-clock"></i> Hết hạn (' . date('d/m/Y H:i', $exp) . ')</span>';
                                            } else {
                                                echo '<span style="color:#10b981; font-weight:600;"><i class="fas fa-clock"></i> ' . date('d/m/Y H:i', $exp) . '</span>';
                                            }
                                        } else {
                                            echo '<span style="color:#64748b;">Vĩnh viễn</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($cStatus === 'active'): ?>
                                            <span class="badge success" style="background:#dcfce7; color:#166534;">Hoạt
                                                động</span>
                                        <?php else: ?>
                                            <span class="badge danger" style="background:#fee2e2; color:#991b1b;">Hết hạn</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn"
                                            style="background: #0284c7; color: #fff; padding: 4px 8px; font-size: 12px; border:none; cursor:pointer; border-radius:4px; font-weight:700; margin-right: 3px;"
                                            onclick='openEditCouponModal(<?php echo json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>)'>
                                            <i class="fas fa-edit"></i> Sửa
                                        </button>
                                        <a href="coupons.php?delete=<?php echo $c['id']; ?>" class="btn btn-danger"
                                            style="padding: 4px 8px; font-size: 12px;"
                                            onclick="return confirm('Bạn có chắc chắn muốn xóa mã ưu đãi này?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
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
                    <h3 class="card-title"><i class="fas fa-envelope-open-text" style="color: #15803d;"></i> Email Nhận
                        Bản Tin (<?php echo count($subscribers); ?>)</h3>
                    <div style="max-height: 500px; overflow-y: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Email Đăng Ký</th>
                                    <th>Voucher</th>
                                    <th style="width: 50px; text-align: center;">Xóa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($subscribers)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; color: #888; padding: 20px;">Chưa có
                                            email nào đăng ký.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($subscribers as $idx => $s): ?>
                                        <tr>
                                            <td>#<?php echo $idx + 1; ?></td>
                                            <td style="font-weight: 600; font-size: 13px; word-break: break-all;">
                                                <?php echo htmlspecialchars($s['email']); ?></td>
                                            <td><span
                                                    style="font-size: 11px; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-weight: 700;"><?php echo htmlspecialchars($s['voucher_sent'] ?? 'WELCOME15'); ?></span>
                                            </td>
                                            <td style="text-align: center;">
                                                <a href="coupons.php?delete_subscriber=<?php echo $s['id']; ?>"
                                                    onclick="return confirm('Bạn có chắc chắn muốn xóa email <?php echo htmlspecialchars(addslashes($s['email'])); ?> khỏi danh sách nhận bản tin?');"
                                                    class="btn"
                                                    style="background: #dc2626; color: #fff; padding: 3px 6px; font-size: 11px; text-decoration: none; border-radius: 4px;">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
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

    <!-- Modal Chỉnh Sửa Mã Giảm Giá -->
    <div id="editCouponModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 15px; color: #0f172a;"><i class="fas fa-edit" style="color: #0284c7;"></i> Sửa Mã
                Giảm Giá</h3>
            <form method="POST">
                <input type="hidden" name="edit_coupon" value="1">
                <input type="hidden" name="coupon_id" id="editCouponId">

                <div class="form-row">
                    <div class="form-group">
                        <label>Mã Code *</label>
                        <input type="text" name="code" id="editCouponCode" required style="text-transform: uppercase;">
                    </div>
                    <div class="form-group">
                        <label>Loại Giảm Giá *</label>
                        <select name="discount_type" id="editCouponType">
                            <option value="percent">Theo phần trăm (%)</option>
                            <option value="fixed">Số tiền cố định ($)</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Giá Trị Giảm *</label>
                        <input type="number" step="0.01" name="discount_value" id="editCouponValue" required>
                    </div>
                    <div class="form-group">
                        <label>Đơn Hàng Tối Thiểu ($)</label>
                        <input type="number" step="0.01" name="min_order" id="editCouponMin">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Trạng Thái</label>
                        <select name="status" id="editCouponStatus">
                            <option value="active">Đang kích hoạt (Active)</option>
                            <option value="expired">Đã hết hạn / Vô hiệu (Expired)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Hạn Sử Dụng</label>
                        <input type="datetime-local" name="expires_at" id="editCouponExpires">
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" class="btn"
                        style="flex: 1; padding: 10px; background: #15803d; color: #fff; border: none; font-weight: 700; border-radius: 6px; cursor: pointer;">LƯU
                        THAY ĐỔI</button>
                    <button type="button" class="btn"
                        style="flex: 1; padding: 10px; background: #64748b; color: #fff; border: none; font-weight: 700; border-radius: 6px; cursor: pointer;"
                        onclick="closeEditCouponModal()">HỦY</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditCouponModal(c) {
            document.getElementById('editCouponId').value = c.id;
            document.getElementById('editCouponCode').value = c.code;
            document.getElementById('editCouponType').value = c.discount_type;
            document.getElementById('editCouponValue').value = c.discount_value;
            document.getElementById('editCouponMin').value = c.min_order || '0.00';
            document.getElementById('editCouponStatus').value = c.status || 'active';

            if (c.expires_at) {
                // format YYYY-MM-DDTHH:mm
                const d = new Date(c.expires_at);
                const isoStr = new Date(d.getTime() - (d.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
                document.getElementById('editCouponExpires').value = isoStr;
            } else {
                document.getElementById('editCouponExpires').value = '';
            }

            document.getElementById('editCouponModal').style.display = 'flex';
        }

        function closeEditCouponModal() {
            document.getElementById('editCouponModal').style.display = 'none';
        }
    </script>
</body>

</html>