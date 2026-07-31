<?php
require_once '../db.php';
require_once '../config_payment.php';

header('Content-Type: application/json');

// Read incoming JSON body (compatible with SePAY, Cassette, VietQR Webhook, etc.)
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    // Also support GET / POST form params
    $data = $_REQUEST;
}

$content = $data['content'] ?? $data['description'] ?? $data['transferContent'] ?? '';
$amount = floatval($data['transferAmount'] ?? $data['amount'] ?? 0);

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Nội dung chuyển khoản trống']);
    exit;
}

// Extract order ID using Regex pattern for "DH 123" or "DH123"
$order_id = 0;
if (preg_match('/DH\s*(\d+)/i', $content, $matches)) {
    $order_id = intval($matches[1]);
}

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy mã đơn hàng trong nội dung chuyển khoản']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Đơn hàng không tồn tại trong hệ thống']);
        exit;
    }

    if ($order['payment_status'] === 'Đã thanh toán') {
        echo json_encode(['success' => true, 'message' => 'Đơn hàng đã được thanh toán trước đó']);
        exit;
    }

    // Mark as Paid
    $updateStmt = $pdo->prepare("UPDATE orders SET payment_status = 'Đã thanh toán', status = 'Đã xác nhận' WHERE id = ?");
    $updateStmt->execute([$order_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Đã tự động xác nhận thanh toán đơn hàng #' . $order_id,
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi xử lý: ' . $e->getMessage()]);
}
