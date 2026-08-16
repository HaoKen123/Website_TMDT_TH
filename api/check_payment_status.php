<?php
session_start();
require_once '../db.php';
require_once '../config_payment.php';

header('Content-Type: application/json');

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    echo json_encode(['paid' => false, 'message' => 'Mã đơn hàng không hợp lệ']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT payment_status, status, total_amount FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if ($order && $order['payment_status'] === 'Đã thanh toán') {
        echo json_encode(['paid' => true, 'status' => $order['status']]);
        exit;
    }

    if (!$order) {
        echo json_encode(['paid' => false]);
        exit;
    }

    // PULL API: Chủ động kiểm tra giao dịch từ SePay API v2 (Tối ưu tốc độ, chống nghẽn)
    if (defined('SEPAY_API_KEY') && !empty(SEPAY_API_KEY)) {
        $search_code = 'DH' . str_pad($order_id, 6, '0', STR_PAD_LEFT); // VD: DH000002
        $search_code_2 = 'DH' . $order_id; // VD: DH2
        
        // Thử Endpoint Sandbox trước (Test mode)
        $apiUrl = "https://userapi-sandbox.sepay.vn/v2/transactions?limit=15";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . SEPAY_API_KEY,
            "Content-Type: application/json",
            "User-Agent: Mozilla/5.0"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Nhanh 2 giây
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $resData = json_decode($response, true);
            $txList = $resData['data'] ?? $resData['transactions'] ?? [];
            
            if (is_array($txList) && !empty($txList)) {
                foreach ($txList as $tx) {
                    $content = strtoupper($tx['transaction_content'] ?? $tx['code'] ?? '');
                    $transfer_type = strtolower($tx['transfer_type'] ?? 'in');
                    
                    // Kiểm tra nếu nội dung CK có chứa mã đơn hàng và là giao dịch tiền vào
                    if ((strpos($content, $search_code) !== false || strpos($content, $search_code_2) !== false) 
                        && $transfer_type === 'in') {
                        
                        // Đã tìm thấy giao dịch -> Cập nhật CSDL
                        $update = $pdo->prepare("UPDATE orders SET payment_status = 'Đã thanh toán', status = 'Đã xác nhận' WHERE id = ?");
                        $update->execute([$order_id]);
                        
                        echo json_encode(['paid' => true, 'status' => 'Đã xác nhận', 'note' => 'Auto-verified via SePay API v2']);
                        exit;
                    }
                }
            }
        }
    }

    // Nếu không có giao dịch nào khớp, trả về chưa thanh toán
    echo json_encode(['paid' => false]);
} catch (Exception $e) {
    echo json_encode(['paid' => false, 'error' => $e->getMessage()]);
}
