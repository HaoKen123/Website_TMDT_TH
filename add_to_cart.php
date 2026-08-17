<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

header('Content-Type: application/json; charset=UTF-8');

// 1. Thu thập product_id và quantity từ mọi nguồn (JSON body, POST form-urlencoded, GET query)
$product_id = 0;
$quantity = 1;

// Đọc JSON payload nếu có
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $jsonData = json_decode($rawInput, true);
    if (is_array($jsonData)) {
        if (!empty($jsonData['product_id'])) $product_id = intval($jsonData['product_id']);
        elseif (!empty($jsonData['id'])) $product_id = intval($jsonData['id']);
        if (!empty($jsonData['quantity'])) $quantity = max(1, intval($jsonData['quantity']));
    }
}

// Nếu chưa có từ JSON, đọc từ $_POST
if ($product_id <= 0) {
    if (!empty($_POST['product_id'])) $product_id = intval($_POST['product_id']);
    elseif (!empty($_POST['id'])) $product_id = intval($_POST['id']);
    if (!empty($_POST['quantity'])) $quantity = max(1, intval($_POST['quantity']));
}

// Nếu vẫn chưa có, đọc từ $_GET (fallback)
if ($product_id <= 0) {
    if (!empty($_GET['product_id'])) $product_id = intval($_GET['product_id']);
    elseif (!empty($_GET['id'])) $product_id = intval($_GET['id']);
    if (!empty($_GET['quantity'])) $quantity = max(1, intval($_GET['quantity']));
}

if ($product_id <= 0) {
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Lỗi: Không tìm thấy ID sản phẩm hợp lệ!'
    ]);
    exit;
}

try {
    // 2. Kiểm tra sản phẩm có tồn tại trong CSDL không
    $stmt = $pdo->prepare("SELECT id, name, price, stock, status FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode([
            'success' => false,
            'status' => 'error',
            'message' => 'Lỗi: Sản phẩm không tồn tại trong hệ thống!'
        ]);
        exit;
    }

    if (isset($product['status']) && intval($product['status']) === 0) {
        echo json_encode([
            'success' => false,
            'status' => 'error',
            'message' => 'Sản phẩm này hiện đang tạm ngưng kinh doanh!'
        ]);
        exit;
    }

    // 3. Khởi tạo và cập nhật giỏ hàng trong Session
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $currentQtyInCart = isset($_SESSION['cart'][$product_id]) ? intval($_SESSION['cart'][$product_id]) : 0;
    $maxStock = isset($product['stock']) ? intval($product['stock']) : 50;

    $newQty = $currentQtyInCart + $quantity;
    if ($maxStock > 0 && $newQty > $maxStock) {
        // Nếu vượt quá tồn kho, đặt bằng tồn kho tối đa
        $_SESSION['cart'][$product_id] = $maxStock;
    } else {
        $_SESSION['cart'][$product_id] = $newQty;
    }

    // 4. Đồng bộ giỏ hàng với CSDL nếu đã đăng nhập
    if (isset($_SESSION['user_id']) && function_exists('sync_user_cart_save')) {
        sync_user_cart_save($pdo, $_SESSION['user_id']);
    }

    // 5. Tính tổng số lượng món trong giỏ hàng
    $totalCartCount = 0;
    foreach ($_SESSION['cart'] as $qty) {
        $totalCartCount += intval($qty);
    }

    echo json_encode([
        'success' => true,
        'status' => 'success',
        'cart_count' => $totalCartCount,
        'message' => 'Đã thêm ' . $quantity . ' sản phẩm vào giỏ hàng thành công!'
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
    exit;
}
