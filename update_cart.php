<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$id = intval($data['id']);
$action = isset($data['action']) ? $data['action'] : 'set';
$quantity = isset($data['quantity']) ? intval($data['quantity']) : 1;

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Kiểm tra số lượng tồn kho
$stmtStock = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
$stmtStock->execute([$id]);
$product_stock = $stmtStock->fetchColumn();

if ($product_stock !== false) {
    $target_qty = $_SESSION['cart'][$id] ?? 0;
    if ($action === 'increase') {
        $target_qty++;
    } elseif ($action === 'set') {
        $target_qty = $quantity;
    }

    if ($target_qty > $product_stock) {
        echo json_encode([
            'success' => false,
            'error' => "Rất tiếc, số lượng sản phẩm trong kho chỉ còn " . intval($product_stock) . " sản phẩm!"
        ]);
        exit;
    }
}

if ($action === 'increase') {
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
} elseif ($action === 'decrease') {
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]--;
        if ($_SESSION['cart'][$id] <= 0) {
            unset($_SESSION['cart'][$id]);
        }
    }
} elseif ($action === 'set') {
    if ($quantity > 0) {
        $_SESSION['cart'][$id] = $quantity;
    } else {
        unset($_SESSION['cart'][$id]);
    }
}

// Calculate totals
$cart_count = 0;
$total_price = 0;
$item_subtotal = 0;
$item_qty = 0;

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
    $products = $stmt->fetchAll();

    foreach ($products as $product) {
        $qty = $_SESSION['cart'][$product['id']];
        $cart_count += $qty;
        $subtotal = $qty * $product['price'];
        $total_price += $subtotal;

        if ($product['id'] == $id) {
            $item_subtotal = $subtotal;
            $item_qty = $qty;
        }
    }
}

echo json_encode([
    'success' => true,
    'cart_count' => $cart_count,
    'total_price' => number_format($total_price, 2),
    'item_subtotal' => number_format($item_subtotal, 2),
    'item_qty' => $item_qty
]);
