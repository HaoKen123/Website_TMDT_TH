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

    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $payment_method = $_POST['payment_method'];
    
    // Convert payment method for display
    $payment_labels = [
        'momo' => 'Ví MoMo / ZaloPay',
        'bank' => 'Chuyển Khoản Ngân Hàng',
        'card' => 'Thẻ Tín Dụng',
        'cod' => 'Thanh Toán Khi Nhận Hàng'
    ];
    $payment_str = isset($payment_labels[$payment_method]) ? $payment_labels[$payment_method] : $payment_method;
    
    $payment_status = ($payment_method === 'cod') ? 'Chưa thanh toán' : 'Chưa thanh toán';

    // Calculate total
    $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
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
        if ($_SESSION['coupon']['discount_type'] === 'percent') {
            $discount_amount = ($subtotal_amount * $_SESSION['coupon']['discount_value']) / 100.0;
        } else {
            $discount_amount = min($subtotal_amount, $_SESSION['coupon']['discount_value']);
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

    // Clear Cart & Coupon
    $_SESSION['cart'] = [];
    unset($_SESSION['coupon']);
    
    // Redirect based on payment method
    if ($payment_method === 'cod') {
        header("Location: payment_success.php?order_id=$order_id");
    } else {
        header("Location: payment.php?order_id=$order_id");
    }
    exit;
}
?>
