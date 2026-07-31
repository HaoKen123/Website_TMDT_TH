<?php
session_start();
require_once '../db.php';
require_once '../lang.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$params = [];
$whereSql = '';

if ($search !== '') {
    $whereSql = "WHERE (username LIKE :s OR email LIKE :s OR fullname LIKE :s OR phone LIKE :s)";
    $params[':s'] = '%' . $search . '%';
}

// Handle Reset Password for User
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_user_id'])) {
    $u_id = intval($_POST['reset_user_id']);
    $new_pass = trim($_POST['new_password']);
    if ($u_id > 0 && !empty($new_pass)) {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmtPass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmtPass->execute([$hashed, $u_id]);
        $msg = "Đã cập nhật mật khẩu mới cho thành viên #$u_id thành công!";
    }
}

// Fetch Users with Order Counts
$stmt = $pdo->prepare("SELECT u.*, COUNT(o.id) as order_count 
                       FROM users u 
                       LEFT JOIN orders o ON u.id = o.user_id 
                       $whereSql 
                       GROUP BY u.id 
                       ORDER BY u.id DESC");
$stmt->execute($params);
$usersList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Khách Hàng - Admin PixelGear</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .action-bar { display: flex; gap: 12px; align-items: center; }
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
        }
        .btn-delete-selected:hover { background: #b91c1c; }
        .checkbox-cell { width: 45px; text-align: center; }
        .checkbox-cell input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #dc2626; }
        .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-box input { flex: 1; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; font-size: 14px; }
        .search-box button { padding: 10px 20px; background: #15803d; color: #fff; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; }
        
        .alert-success { background: #dcfce7; color: #166534; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; }
        
        /* Reset Password Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center; }
        .modal-content { background: #fff; padding: 30px; border-radius: 10px; width: 400px; text-align: center; }
        .modal-content input { width: 100%; padding: 10px; margin: 15px 0; border: 1px solid #ccc; border-radius: 6px; font-family: 'Inter'; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>PIXELGEAR</h2>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Tổng quan</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
            <li><a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a></li>
            <li><a href="coupons.php"><i class="fas fa-ticket-alt"></i> Mã giảm giá</a></li>
            <li><a href="users.php" class="active"><i class="fas fa-users"></i> Khách hàng</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>Quản Lý Khách Hàng (<?php echo count($usersList); ?>)</h1>
            <div class="action-bar">
                <button type="button" id="btnDeleteSelected" class="btn-delete-selected" onclick="deleteSelectedUsers()">
                    <i class="fas fa-trash-alt" style="margin-right: 6px;"></i> Xóa tài khoản đã chọn (<span id="selectedCount">0</span>)
                </button>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
        <?php endif; ?>

        <!-- Search Box -->
        <form method="GET" class="search-box">
            <input type="text" name="search" placeholder="Tìm theo Tên, Email, Tên đăng nhập hoặc Số điện thoại..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit"><i class="fas fa-search"></i> Tìm kiếm</button>
            <?php if ($search): ?>
                <a href="users.php" style="padding: 10px 15px; background: #64748b; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600;">Xóa tìm</a>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th class="checkbox-cell">
                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                    </th>
                    <th style="width: 60px;">ID</th>
                    <th>Tên tài khoản</th>
                    <th>Email</th>
                    <th>Họ và tên</th>
                    <th>Số điện thoại</th>
                    <th>Địa chỉ</th>
                    <th>Đơn hàng</th>
                    <th style="width: 170px; text-align: center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usersList)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px; color: #64748b;">
                        Chưa có khách hàng nào đăng ký.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($usersList as $u): ?>
                    <tr id="user-row-<?php echo $u['id']; ?>">
                        <td class="checkbox-cell">
                            <input type="checkbox" class="user-checkbox" value="<?php echo $u['id']; ?>" onchange="updateSelectedCount()">
                        </td>
                        <td><strong>#<?php echo $u['id']; ?></strong></td>
                        <td><strong style="color: #0284c7;"><?php echo htmlspecialchars($u['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['email'] ?? 'Chưa cập nhật'); ?></td>
                        <td><strong><?php echo htmlspecialchars($u['fullname']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['phone']); ?></td>
                        <td style="font-size: 13px; color: #475569; max-width: 200px;"><?php echo htmlspecialchars($u['address']); ?></td>
                        <td><span class="badge pending" style="background: #e0f2fe; color: #0369a1; font-weight: 700;"><?php echo $u['order_count']; ?> đơn</span></td>
                        <td style="text-align: center;">
                            <button type="button" class="btn btn-primary" style="background: #f59e0b; padding: 5px 10px; font-size: 12px; border:none; cursor:pointer;" onclick="openResetModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')">
                                <i class="fas fa-key"></i> Đổi pass
                            </button>
                            <button type="button" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px; border:none; cursor:pointer; background: #dc2626;" onclick="deleteSingleUser(<?php echo $u['id']; ?>)">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 10px; color: #0f172a;">Đổi Mật Khẩu Khách Hàng</h3>
            <p style="font-size: 13px; color: #64748b;">Tài khoản: <strong id="modalUsername" style="color: #0284c7;"></strong></p>
            
            <form method="POST">
                <input type="hidden" name="reset_user_id" id="modalUserId">
                <input type="text" name="new_password" placeholder="Nhập mật khẩu mới..." required>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 10px; background: #15803d; border: none; font-weight: 700;">CẬP NHẬT</button>
                    <button type="button" class="btn" style="flex: 1; padding: 10px; background: #64748b; color: #fff; border: none; font-weight: 700;" onclick="closeResetModal()">HỦY</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(cb => cb.checked = master.checked);
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const checkboxes = document.querySelectorAll('.user-checkbox');
        const checked = document.querySelectorAll('.user-checkbox:checked');
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

    function openResetModal(id, username) {
        document.getElementById('modalUserId').value = id;
        document.getElementById('modalUsername').innerText = username;
        document.getElementById('resetModal').style.display = 'flex';
    }

    function closeResetModal() {
        document.getElementById('resetModal').style.display = 'none';
    }

    function deleteSingleUser(id) {
        if (!confirm('Bạn có chắc chắn muốn XÓA tài khoản khách hàng #' + id + ' không? Tất cả đơn hàng liên quan sẽ bị xóa!')) {
            return;
        }

        fetch('delete_user.php?id=' + id + '&ajax=1')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const row = document.getElementById('user-row-' + id);
                    if (row) row.remove();
                    updateSelectedCount();
                    alert('Đã xóa tài khoản thành công!');
                } else {
                    alert('Lỗi: ' + data.message);
                }
            });
    }

    function deleteSelectedUsers() {
        const checked = document.querySelectorAll('.user-checkbox:checked');
        const ids = Array.from(checked).map(cb => cb.value);

        if (ids.length === 0) return;

        if (!confirm('Bạn có chắc chắn muốn XÓA ' + ids.length + ' tài khoản khách hàng đã chọn không?')) {
            return;
        }

        const formData = new FormData();
        ids.forEach(id => formData.append('ids[]', id));
        formData.append('ajax', '1');

        fetch('delete_user.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                ids.forEach(id => {
                    const row = document.getElementById('user-row-' + id);
                    if (row) row.remove();
                });
                document.getElementById('selectAll').checked = false;
                updateSelectedCount();
                alert('Đã xóa ' + ids.length + ' tài khoản thành công!');
            } else {
                alert('Lỗi: ' + data.message);
            }
        });
    }
    </script>
</body>
</html>
