<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        header('Location: index.php');
        exit;
    }

    // Ensure selected items exist
    if (!isset($_SESSION['selected_items']) || empty($_SESSION['selected_items'])) {
        header('Location: cart.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'bank';
    if (!in_array($payment_method, ['bank', 'cod'])) {
        $payment_method = 'bank';
    }
    
    // Convert payment method for display
    $payment_labels = [
        'bank' => 'Chuyển Khoản Ngân Hàng',
        'cod' => 'Thanh Toán Khi Nhận Hàng'
    ];
    $payment_str = isset($payment_labels[$payment_method]) ? $payment_labels[$payment_method] : 'Chuyển Khoản Ngân Hàng';
    
    $payment_status = ($payment_method === 'cod') ? 'Chưa thanh toán' : 'Chưa thanh toán';

    // Calculate total from selected items only
    $selected_keys = array_filter($_SESSION['selected_items'], function($k) { return isset($_SESSION['cart'][$k]); });
    $ids = implode(',', array_map('intval', $selected_keys));
    if (!$ids) {
        header('Location: cart.php');
        exit;
    }

    $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
    $products = $stmt->fetchAll();
    
    $subtotal_amount = 0;
    foreach ($products as $product) {
        $qty = $_SESSION['cart'][$product['id']];
        $subtotal_amount += $qty * $product['price'];
    }

    // Apply Coupon Discount if exists
    $discount_amount = 0;
    if (isset($_SESSION['coupon'])) {
        $coupon = $_SESSION['coupon'];
        if ($subtotal_amount >= ($coupon['min_order'] ?? 0)) {
            if ($coupon['discount_type'] === 'percent') {
                $discount_amount = ($subtotal_amount * $coupon['discount_value']) / 100.0;
            } else {
                $discount_amount = min($subtotal_amount, $coupon['discount_value']);
            }
        } else {
            unset($_SESSION['coupon']);
        }
    }
    $total_amount = max(0, $subtotal_amount - $discount_amount);

    // Insert Order
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, customer_name, customer_phone, customer_address, total_amount, payment_method, payment_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Chờ xác nhận')");
    $stmt->execute([$user_id, $name, $phone, $address, $total_amount, $payment_str, $payment_status]);
    
    $order_id = $pdo->lastInsertId();

    // Insert Order Items
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($products as $product) {
        $qty = $_SESSION['cart'][$product['id']];
        $stmt->execute([$order_id, $product['id'], $qty, $product['price']]);
    }

    // Record coupon usage for user
    if (isset($_SESSION['coupon'])) {
        try {
            $stmtUserCoupon = $pdo->prepare("INSERT IGNORE INTO user_coupons (user_id, coupon_code) VALUES (?, ?)");
            $stmtUserCoupon->execute([$user_id, strtoupper($_SESSION['coupon']['code'])]);
        } catch (Exception $e) {}
    }

    // Clear checked items from Cart & remove Coupon
    foreach ($_SESSION['selected_items'] as $id) {
        unset($_SESSION['cart'][$id]);
    }
    unset($_SESSION['selected_items']);
    unset($_SESSION['coupon']);
    
    // Sync cart to DB for this user
    sync_user_cart_save($pdo, $user_id);
    
    // Redirect based on payment method
    if ($payment_method === 'cod') {
        header("Location: payment_success.php?order_id=$order_id");
    } else {
        header("Location: payment.php?order_id=$order_id");
    }
    exit;
}
?>
