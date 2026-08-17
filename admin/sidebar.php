<?php
$current_page = basename($_SERVER['PHP_SELF']);
$admin_role = $_SESSION['admin_role'] ?? 'admin';
$is_admin = ($admin_role === 'admin');
$admin_name = $_SESSION['admin_username'] ?? 'Quản trị viên';
?>
<div class="sidebar">
    <h2>PIXELGEAR</h2>
    <div style="padding: 8px 12px; margin: 0 10px 15px 10px; background: rgba(0,0,0,0.06); border-radius: 6px; text-align: center; font-size: 13px;">
        <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($admin_name); ?></div>
        <?php if ($is_admin): ?>
            <span style="display: inline-block; margin-top: 3px; padding: 2px 8px; border-radius: 10px; background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 700;">👑 QUẢN TRỊ VIÊN</span>
        <?php else: ?>
            <span style="display: inline-block; margin-top: 3px; padding: 2px 8px; border-radius: 10px; background: #e0f2fe; color: #0369a1; font-size: 11px; font-weight: 700;">👔 NHÂN VIÊN</span>
        <?php endif; ?>
    </div>
    <ul>
        <li><a href="index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Tổng quan</a></li>
        <li><a href="orders.php" class="<?php echo in_array($current_page, ['orders.php', 'print_order.php']) ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
        <li><a href="products.php" class="<?php echo in_array($current_page, ['products.php', 'add_product.php', 'edit_product.php']) ? 'active' : ''; ?>"><i class="fas fa-box"></i> Sản phẩm</a></li>
        <li><a href="shipping.php" class="<?php echo $current_page === 'shipping.php' ? 'active' : ''; ?>"><i class="fas fa-truck"></i> Phí vận chuyển</a></li>
        <li><a href="comments.php" class="<?php echo $current_page === 'comments.php' ? 'active' : ''; ?>"><i class="fas fa-comments"></i> Bình luận</a></li>
        
        <?php if ($is_admin): ?>
            <li><a href="categories.php" class="<?php echo $current_page === 'categories.php' ? 'active' : ''; ?>"><i class="fas fa-list"></i> Danh mục</a></li>
            <li><a href="coupons.php" class="<?php echo $current_page === 'coupons.php' ? 'active' : ''; ?>"><i class="fas fa-ticket-alt"></i> Mã giảm giá</a></li>
            <li><a href="users.php" class="<?php echo $current_page === 'users.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Khách hàng & Nhân viên</a></li>
            <li><a href="reports.php" class="<?php echo $current_page === 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a></li>
        <?php endif; ?>

        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
    </ul>
</div>
