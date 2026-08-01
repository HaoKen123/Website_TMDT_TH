<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code = trim($input['code'] ?? $_POST['code'] ?? '');

if (empty($code)) {
    // If clearing coupon
    unset($_SESSION['coupon']);
    echo json_encode(['status' => 'cleared', 'message' => 'Đã hủy áp dụng mã giảm giá']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE UPPER(code) = UPPER(?) AND status = 'active'");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        echo json_encode(['status' => 'error', 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn!']);
        exit;
    }

    // 1. Kiểm tra ngày hết hạn (Expiration Cooldown)
    if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < time()) {
        echo json_encode(['status' => 'error', 'message' => 'Mã giảm giá ' . strtoupper($code) . ' đã hết hạn sử dụng!']);
        exit;
    }

    // 2. Kiểm tra giới hạn 1 lần sử dụng per tài khoản (đặc biệt cho WELCOME15)
    $user_id = $_SESSION['user_id'] ?? 0;
    if ($user_id > 0) {
        try {
            $stmtUsed = $pdo->prepare("SELECT id FROM user_coupons WHERE user_id = ? AND UPPER(coupon_code) = UPPER(?)");
            $stmtUsed->execute([$user_id, $code]);
            if ($stmtUsed->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Tài khoản của bạn đã dùng mã ' . strtoupper($code) . ' trước đó rồi! Mỗi tài khoản chỉ được dùng 1 lần.']);
                exit;
            }
        } catch (Exception $ex) {
            // Table might be created on fly
        }
    }

    // Calculate cart total in USD
    $total_usd = 0;
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
        $stmtCart = $pdo->query("SELECT id, price FROM products WHERE id IN ($ids)");
        $products = $stmtCart->fetchAll();

        foreach ($products as $p) {
            $qty = $_SESSION['cart'][$p['id']] ?? 0;
            $total_usd += $qty * $p['price'];
        }
    }

    if ($total_usd <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Giỏ hàng của bạn đang trống!']);
        exit;
    }

    // Check minimum order
    if ($coupon['min_order'] > 0 && $total_usd < $coupon['min_order']) {
        $min_formatted = format_price($coupon['min_order']);
        echo json_encode(['status' => 'error', 'message' => "Mã này chỉ áp dụng cho đơn hàng từ $min_formatted trở lên!"]);
        exit;
    }

    // Calculate discount amount
    $discount_usd = 0;
    if ($coupon['discount_type'] === 'percent') {
        $discount_usd = ($total_usd * $coupon['discount_value']) / 100.0;
    } else { // fixed
        $discount_usd = min($total_usd, $coupon['discount_value']);
    }

    $final_total_usd = max(0, $total_usd - $discount_usd);

    $_SESSION['coupon'] = [
        'code' => strtoupper($coupon['code']),
        'discount_type' => $coupon['discount_type'],
        'discount_value' => $coupon['discount_value'],
        'discount_usd' => $discount_usd
    ];

    echo json_encode([
        'status' => 'success',
        'message' => 'Áp dụng mã giảm giá ' . strtoupper($coupon['code']) . ' thành công!',
        'code' => strtoupper($coupon['code']),
        'discount_formatted' => format_price($discount_usd),
        'total_formatted' => format_price($final_total_usd)
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>
