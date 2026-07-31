<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if order belongs to user and is cancelable
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'Chờ xác nhận'");
    $stmt->execute([$order_id, $user_id]);
    if ($stmt->fetch()) {
        $update = $pdo->prepare("UPDATE orders SET status = 'Đã hủy' WHERE id = ?");
        $update->execute([$order_id]);
    }
}
header('Location: profile.php');
exit;
