<?php
// API Endpoint nhận Webhook tự động từ SePay (SePay Auto Payment Gateway Webhook)
require_once '../db.php';
require_once '../config_payment.php';

header('Content-Type: application/json');

// Bypass Localtunnel warning page
header('bypass-tunnel-reminder: 1');



// 1. Đọc dữ liệu JSON gửi từ SePay Webhook
$rawInput = file_get_contents('php://input');
file_put_contents('webhook_log.txt', "[" . date('Y-m-d H:i:s') . "] RAW PAYLOAD: " . $rawInput . "\n", FILE_APPEND);
$headers = getallheaders();
file_put_contents('webhook_log.txt', "[" . date('Y-m-d H:i:s') . "] HEADERS: " . json_encode($headers) . "\n", FILE_APPEND);
$data = json_decode($rawInput, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu webhook không hợp lệ']);
    exit;
}

// 2. Kiểm tra Token bảo mật (nếu được thiết lập)
$headers = function_exists('getallheaders') ? getallheaders() : [];
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
$getKey = $_GET['api_key'] ?? $_GET['token'] ?? $_GET['key'] ?? '';

if (defined('SEPAY_API_KEY') && SEPAY_API_KEY !== 'SEPAY_SECRET_API_KEY' && !empty(SEPAY_API_KEY)) {
    $expectedBearer = 'Bearer ' . SEPAY_API_KEY;
    $expectedApikey = 'Apikey ' . SEPAY_API_KEY;
    
    // Normalize spaces and compare
    $cleanAuth = trim(preg_replace('/\s+/', ' ', $authHeader));
    
    $isHeaderValid = ($cleanAuth === $expectedBearer || $cleanAuth === $expectedApikey || $cleanAuth === SEPAY_API_KEY);
    $isGetValid = ($getKey === SEPAY_API_KEY);

    if (!$isHeaderValid && !$isGetValid) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'Mã API Key / Authorization không chính xác',
            'debug_received' => $cleanAuth,
            'debug_get' => $getKey
        ]);
        exit;
    }
}

// 3. Kiểm tra loại giao dịch (Tiền vào: transferType == 'in')
$transferType = strtolower($data['transferType'] ?? 'in');
if ($transferType !== 'in') {
    echo json_encode(['success' => true, 'message' => 'Bỏ qua giao dịch tiền ra']);
    exit;
}

// 4. Trích xuất mã đơn hàng từ Nội dung chuyển khoản (content / description / code)
$content = $data['content'] ?? ($data['description'] ?? ($data['code'] ?? ''));
$order_id = 0;

// Tìm mẫu cú pháp: "DH12", "DH 12", "Thanh toan DH 12", "DH_12"
if (preg_match('/DH\s*[_:\-]?\s*(\d+)/i', $content, $matches)) {
    $order_id = intval($matches[1]);
} else if (preg_match('/(\d+)/', $content, $matches)) {
    $order_id = intval($matches[1]);
}

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy mã đơn hàng trong nội dung: ' . $content]);
    exit;
}

try {
    // 5. Kiểm tra đơn hàng trong cơ sở dữ liệu
    $stmt = $pdo->prepare("SELECT id, payment_status, total_amount FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Đơn hàng #' . $order_id . ' không tồn tại']);
        exit;
    }

    if ($order['payment_status'] === 'Đã thanh toán') {
        echo json_encode(['success' => true, 'message' => 'Đơn hàng #' . $order_id . ' đã được thanh toán trước đó']);
        exit;
    }

    // 6. Cập nhật trạng thái Đã thanh toán & Đã xác nhận đơn hàng
    file_put_contents('webhook_log.txt', "[" . date('Y-m-d H:i:s') . "] UPDATE DB FOR ORDER: " . $order_id . "\n", FILE_APPEND);
    $update = $pdo->prepare("UPDATE orders SET payment_status = 'Đã thanh toán', status = 'Đã xác nhận' WHERE id = ?");
    $update->execute([$order_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Đã tự động xác nhận thanh toán thành công cho đơn hàng #' . $order_id,
        'order_id' => $order_id,
        'amount_received' => $data['transferAmount'] ?? 0
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi xử lý cơ sở dữ liệu: ' . $e->getMessage()]);
}
?>
