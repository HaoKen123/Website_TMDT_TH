<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_SESSION['user_id'])) {
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id'];
    
    // Update payment status
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'Đã thanh toán', status = 'Đã xác nhận' WHERE id = ?");
    $stmt->execute([$order_id]);

    header("Location: payment_success.php?order_id=" . $order_id);
    exit;
}

header('Location: index.php');
exit;
