<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    echo json_encode(['paid' => false, 'message' => 'Mã đơn hàng không hợp lệ']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT payment_status, status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if ($order && $order['payment_status'] === 'Đã thanh toán') {
        echo json_encode(['paid' => true, 'status' => $order['status']]);
    } else {
        echo json_encode(['paid' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['paid' => false, 'error' => $e->getMessage()]);
}
