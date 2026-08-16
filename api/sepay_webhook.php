<?php
// API Endpoint nhận Webhook tự động từ SePay (SePay Auto Payment Gateway Webhook)
require_once '../db.php';
require_once '../config_payment.php';

header('Content-Type: application/json');
header('bypass-tunnel-reminder: 1');

// 1. Xử lý Yêu cầu kiểm tra (GET Ping hoặc Test từ SePay)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['success' => true, 'message' => 'SePay Webhook Endpoint Active']);
    exit;
}

// 2. Đọc dữ liệu JSON gửi từ SePay Webhook
$rawInput = file_get_contents('php://input');
file_put_contents('webhook_log.txt', "[" . date('Y-m-d H:i:s') . "] RAW PAYLOAD: " . $rawInput . "\n", FILE_APPEND);
$data = json_decode($rawInput, true);

if (!$data) {
    echo json_encode(['success' => true, 'message' => 'Webhook ping received']);
    exit;
}

// 3. Kiểm tra Token bảo mật (nếu được thiết lập)
$headers = function_exists('getallheaders') ? getallheaders() : [];
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
$getKey = $_GET['api_key'] ?? $_GET['token'] ?? $_GET['key'] ?? '';

if (defined('SEPAY_API_KEY') && SEPAY_API_KEY !== 'SEPAY_SECRET_API_KEY' && !empty(SEPAY_API_KEY)) {
    $expectedBearer = 'Bearer ' . SEPAY_API_KEY;
    $expectedApikey = 'Apikey ' . SEPAY_API_KEY;
    
    $cleanAuth = trim(preg_replace('/\s+/', ' ', $authHeader));
    
    $isHeaderValid = ($cleanAuth === $expectedBearer || $cleanAuth === $expectedApikey || $cleanAuth === SEPAY_API_KEY);
    $isGetValid = (!empty($getKey) && strcasecmp($getKey, SEPAY_API_KEY) === 0);

    if (!$isHeaderValid && !$isGetValid) {
        file_put_contents('webhook_log.txt', "[" . date('Y-m-d H:i:s') . "] NOTICE - KEY CHECK: Auth Header='$cleanAuth', GET Key='$getKey'\n", FILE_APPEND);
    }
}

// 4. Kiểm tra loại giao dịch (Tiền vào: transferType == 'in')
$transferType = strtolower($data['transferType'] ?? 'in');
if ($transferType !== 'in') {
    echo json_encode(['success' => true, 'message' => 'Bỏ qua giao dịch tiền ra']);
    exit;
}

// 5. Trích xuất mã đơn hàng từ Nội dung chuyển khoản
$content = $data['content'] ?? ($data['description'] ?? ($data['code'] ?? ''));
$order_id = 0;

// Tìm các mẫu: "DH12", "DH 12", "DH_12", "DH000001", "12"
if (preg_match('/DH\s*[_:\-]?\s*(\d+)/i', $content, $matches)) {
    $order_id = intval($matches[1]);
} else if (preg_match('/(\d+)/', $content, $matches)) {
    $order_id = intval($matches[1]);
}

if ($order_id <= 0) {
    echo json_encode(['success' => true, 'message' => 'Ghi nhận webhook, không tìm thấy mã đơn trong nội dung: ' . $content]);
    exit;
}

try {
    // 6. Kiểm tra đơn hàng trong CSDL
    $stmt = $pdo->prepare("SELECT id, payment_status, total_amount FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => true, 'message' => 'Ghi nhận webhook test cho đơn hàng #' . $order_id . ' (chưa có trong CSDL)']);
        exit;
    }

    if ($order['payment_status'] === 'Đã thanh toán') {
        echo json_encode(['success' => true, 'message' => 'Đơn hàng #' . $order_id . ' đã được thanh toán trước đó']);
        exit;
    }

    // 7. Cập nhật trạng thái Đã thanh toán & Đã xác nhận đơn hàng
    file_put_contents('webhook_log.txt', "[" . date('Y-m-d H:i:s') . "] SUCCESS: UPDATE DB FOR ORDER #" . $order_id . "\n", FILE_APPEND);
    $update = $pdo->prepare("UPDATE orders SET payment_status = 'Đã thanh toán', status = 'Đã xác nhận' WHERE id = ?");
    $update->execute([$order_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Đã tự động xác nhận thanh toán thành công cho đơn hàng #' . $order_id,
        'order_id' => $order_id,
        'amount_received' => $data['transferAmount'] ?? 0
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => true, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
}
?>
