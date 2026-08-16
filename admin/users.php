<?php
session_start();
require_once '../db.php';
require_once '../lang.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Tự động kiểm tra và thêm cột status, role vào bảng users nếu thiếu
try {
    $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('status', $cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Active, 0: Blocked'");
    }
    if (!in_array('role', $cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'customer' COMMENT 'customer, staff, admin'");
    }
} catch (Exception $e) {}

$msg = '';

// Handle Add New User / Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user_action'])) {
    $username = trim($_POST['username']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $raw_pass = trim($_POST['password']);
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $role = $_POST['role'] ?? 'customer';
    $status = intval($_POST['status'] ?? 1);

    if ($username && $raw_pass && $fullname) {
        $stmtChk = $pdo->prepare("SELECT id FROM users WHERE username = ? OR (email = ? AND email != '')");
        $stmtChk->execute([$username, $email]);
        if ($stmtChk->fetch()) {
            $msg = "Lỗi: Tên đăng nhập hoặc Email đã tồn tại!";
        } else {
            $hashed = password_hash($raw_pass, PASSWORD_DEFAULT);
            $stmtIns = $pdo->prepare("INSERT INTO users (username, email, password, fullname, phone, address, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([$username, $email, $hashed, $fullname, $phone, $address, $role, $status]);
            $msg = "Đã thêm thành công người dùng mới ($role)!";
        }
    }
}

// Handle Lock/Unlock Status
if (isset($_GET['toggle_status_id'])) {
    $uid = intval($_GET['toggle_status_id']);
    $stmtSt = $pdo->prepare("UPDATE users SET status = IF(status=1, 0, 1) WHERE id = ?");
    $stmtSt->execute([$uid]);
    header('Location: users.php?msg=status_updated');
    exit;
}

// Handle Change Role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role_id'])) {
    $uid = intval($_POST['change_role_id']);
    $new_role = $_POST['new_role'];
    if (in_array($new_role, ['customer', 'staff', 'admin'])) {
        $stmtR = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmtR->execute([$new_role, $uid]);
        $msg = "Đã cập nhật vai trò/quyền cho người dùng #$uid!";
    }
}

// Handle Reset Password for User
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

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$params = [];
$whereSql = '';

if ($search !== '') {
    $whereSql = "WHERE (username LIKE :s OR email LIKE :s OR fullname LIKE :s OR phone LIKE :s)";
    $params[':s'] = '%' . $search . '%';
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
    <link rel="icon" type="image/png" href="../favicon.png?v=2">
    <link rel="shortcut icon" href="../favicon.ico?v=2">
    <meta charset="UTF-8">
    <title>Quản Lý Người Dùng & Phân Quyền Nhân Viên - Admin PixelGear</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .action-bar { display: flex; gap: 12px; align-items: center; }
        .btn-add-user { background: #15803d; color: #fff; padding: 9px 18px; border-radius: 6px; border: none; cursor: pointer; font-weight: 700; font-size: 13px; text-decoration: none; }
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
        
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-blocked { background: #fee2e2; color: #991b1b; }

        /* Modal styling */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center; }
        .modal-content { background: #fff; padding: 30px; border-radius: 10px; width: 440px; text-align: left; }
        .modal-content input, .modal-content select { width: 100%; padding: 10px; margin: 8px 0 15px 0; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; box-sizing: border-box; }
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
            <li><a href="comments.php"><i class="fas fa-comments"></i> Bình luận</a></li>
            <li><a href="users.php" class="active"><i class="fas fa-users"></i> Khách hàng & Nhân viên</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>Quản Lý Người Dùng & Nhân Viên (<?php echo count($usersList); ?>)</h1>
            <div class="action-bar">
                <button type="button" class="btn-add-user" onclick="openAddModal()"><i class="fas fa-user-plus"></i> Thêm Tài Khoản / Nhân Viên</button>
                <button type="button" id="btnDeleteSelected" class="btn-delete-selected" onclick="deleteSelectedUsers()">
                    <i class="fas fa-trash-alt" style="margin-right: 6px;"></i> Xóa đã chọn (<span id="selectedCount">0</span>)
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
                    <th style="width: 50px;">ID</th>
                    <th>Tên tài khoản</th>
                    <th>Họ và tên</th>
                    <th>Quyền / Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Số điện thoại</th>
                    <th>Đơn hàng</th>
                    <th style="width: 220px; text-align: center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usersList)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px; color: #64748b;">
                        Chưa có người dùng nào.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($usersList as $u): 
                        $uRole = $u['role'] ?? 'customer';
                        $uStatus = intval($u['status'] ?? 1);
                    ?>
                    <tr id="user-row-<?php echo $u['id']; ?>">
                        <td class="checkbox-cell">
                            <input type="checkbox" class="user-checkbox" value="<?php echo $u['id']; ?>" onchange="updateSelectedCount()">
                        </td>
                        <td><strong>#<?php echo $u['id']; ?></strong></td>
                        <td><strong style="color: #0284c7;"><?php echo htmlspecialchars($u['username']); ?></strong></td>
                        <td><strong><?php echo htmlspecialchars($u['fullname']); ?></strong></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="change_role_id" value="<?php echo $u['id']; ?>">
                                <select name="new_role" onchange="this.form.submit()" style="padding: 4px 8px; border-radius: 4px; font-weight: 700; font-size: 12px; cursor: pointer;">
                                    <option value="customer" <?php echo $uRole==='customer'?'selected':''; ?>>Khách hàng</option>
                                    <option value="staff" <?php echo $uRole==='staff'?'selected':''; ?>>Nhân viên</option>
                                    <option value="admin" <?php echo $uRole==='admin'?'selected':''; ?>>Admin</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <?php if ($uStatus === 1): ?>
                                <span class="status-badge status-active"><i class="fas fa-check-circle"></i> Hoạt động</span>
                            <?php else: ?>
                                <span class="status-badge status-blocked"><i class="fas fa-lock"></i> Đã khóa</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($u['phone']); ?></td>
                        <td><span class="badge pending" style="background: #e0f2fe; color: #0369a1; font-weight: 700;"><?php echo $u['order_count']; ?> đơn</span></td>
                        <td style="text-align: center;">
                            <a href="users.php?toggle_status_id=<?php echo $u['id']; ?>" class="btn" style="background: <?php echo $uStatus===1?'#ef4444':'#16a34a'; ?>; color: #fff; padding: 5px 9px; font-size: 12px; text-decoration: none; border-radius: 4px; font-weight: 700;">
                                <i class="fas fa-<?php echo $uStatus===1?'lock':'unlock'; ?>"></i> <?php echo $uStatus===1?'Khóa':'Mở'; ?>
                            </a>
                            <button type="button" class="btn" style="background: #f59e0b; color: #fff; padding: 5px 9px; font-size: 12px; border:none; cursor:pointer; border-radius: 4px; font-weight: 700;" onclick="openResetModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')">
                                <i class="fas fa-key"></i> Pass
                            </button>
                            <button type="button" class="btn" style="padding: 5px 9px; font-size: 12px; border:none; cursor:pointer; background: #dc2626; color: #fff; border-radius: 4px; font-weight: 700;" onclick="deleteSingleUser(<?php echo $u['id']; ?>)">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal Thêm người dùng / Nhân viên -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 15px; color: #0f172a;"><i class="fas fa-user-plus" style="color: #15803d;"></i> Thêm Tài Khoản / Nhân Viên</h3>
            <form method="POST">
                <input type="hidden" name="add_user_action" value="1">
                <label style="font-size: 13px; font-weight: 600;">Tên đăng nhập *</label>
                <input type="text" name="username" required placeholder="Nhập username...">

                <label style="font-size: 13px; font-weight: 600;">Email</label>
                <input type="email" name="email" placeholder="Địa chỉ email...">

                <label style="font-size: 13px; font-weight: 600;">Mật khẩu *</label>
                <input type="password" name="password" required placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)...">

                <label style="font-size: 13px; font-weight: 600;">Họ và tên *</label>
                <input type="text" name="fullname" required placeholder="Nhập họ và tên...">

                <label style="font-size: 13px; font-weight: 600;">Số điện thoại</label>
                <input type="text" name="phone" placeholder="Số điện thoại...">

                <label style="font-size: 13px; font-weight: 600;">Địa chỉ</label>
                <input type="text" name="address" placeholder="Địa chỉ...">

                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label style="font-size: 13px; font-weight: 600;">Phân quyền *</label>
                        <select name="role">
                            <option value="customer">Khách hàng</option>
                            <option value="staff" selected>Nhân viên</option>
                            <option value="admin">Quản trị viên (Admin)</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 13px; font-weight: 600;">Trạng thái</label>
                        <select name="status">
                            <option value="1">Kích hoạt</option>
                            <option value="0">Khóa</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" class="btn" style="flex: 1; padding: 10px; background: #15803d; color: #fff; border: none; font-weight: 700; border-radius: 6px; cursor: pointer;">LƯU TÀI KHOẢN</button>
                    <button type="button" class="btn" style="flex: 1; padding: 10px; background: #64748b; color: #fff; border: none; font-weight: 700; border-radius: 6px; cursor: pointer;" onclick="closeAddModal()">HỦY</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 10px; color: #0f172a;">Đổi Mật Khẩu Nhanh</h3>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 15px;">Tài khoản: <strong id="modalUsername" style="color: #0284c7;"></strong></p>
            
            <form method="POST">
                <input type="hidden" name="reset_user_id" id="modalUserId">
                <input type="text" name="new_password" placeholder="Nhập mật khẩu mới..." required style="width:100%; padding:10px; margin-bottom:15px; border:1px solid #cbd5e1; border-radius:6px;">
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 10px; background: #15803d; border: none; font-weight: 700; cursor:pointer;">CẬP NHẬT</button>
                    <button type="button" class="btn" style="flex: 1; padding: 10px; background: #64748b; color: #fff; border: none; font-weight: 700; cursor:pointer;" onclick="closeResetModal()">HỦY</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openAddModal() {
        document.getElementById('addModal').style.display = 'flex';
    }
    function closeAddModal() {
        document.getElementById('addModal').style.display = 'none';
    }
    function openResetModal(id, username) {
        document.getElementById('modalUserId').value = id;
        document.getElementById('modalUsername').innerText = username;
        document.getElementById('resetModal').style.display = 'flex';
    }
    function closeResetModal() {
        document.getElementById('resetModal').style.display = 'none';
    }

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

    function deleteSingleUser(id) {
        if (!confirm('Bạn có chắc chắn muốn XÓA tài khoản #' + id + ' không?')) return;
        fetch('delete_user.php?id=' + id + '&ajax=1')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const row = document.getElementById('user-row-' + id);
                    if (row) row.remove();
                    updateSelectedCount();
                    alert('Đã xóa thành công!');
                } else alert('Lỗi: ' + data.message);
            });
    }

    function deleteSelectedUsers() {
        const checked = document.querySelectorAll('.user-checkbox:checked');
        const ids = Array.from(checked).map(cb => cb.value);
        if (ids.length === 0) return;
        if (!confirm('Bạn có chắc chắn muốn XÓA ' + ids.length + ' tài khoản đã chọn không?')) return;

        const formData = new FormData();
        ids.forEach(id => formData.append('ids[]', id));
        formData.append('ajax', '1');

        fetch('delete_user.php', { method: 'POST', body: formData })
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
            } else alert('Lỗi: ' + data.message);
        });
    }
    </script>
</body>
</html>
