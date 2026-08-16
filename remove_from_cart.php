<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $product_id = isset($data['id']) ? (int)$data['id'] : 0;
    
    if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        if (isset($_SESSION['user_id'])) {
            sync_user_cart_save($pdo, $_SESSION['user_id']);
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false]);
?>
