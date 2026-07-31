<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    $product_id = isset($data['id']) ? (int)$data['id'] : 0;
    $quantity = isset($data['quantity']) ? max(1, (int)$data['quantity']) : 1;
    
    if ($product_id > 0) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        
        // Calculate total items
        $total = 0;
        foreach ($_SESSION['cart'] as $qty) {
            $total += $qty;
        }
        
        echo json_encode(['success' => true, 'cart_count' => $total]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Lỗi thêm vào giỏ hàng']);
