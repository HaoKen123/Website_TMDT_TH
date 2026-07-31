<?php
require_once 'db.php';
require_once 'config_payment.php';

header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['resultCode' => 99, 'message' => 'No data received']);
    exit;
}

$partnerCode = $data['partnerCode'] ?? '';
$orderId = $data['orderId'] ?? '';
$requestId = $data['requestId'] ?? '';
$amount = $data['amount'] ?? '';
$orderInfo = $data['orderInfo'] ?? '';
$orderType = $data['orderType'] ?? '';
$transId = $data['transId'] ?? '';
$resultCode = $data['resultCode'] ?? -1;
$message = $data['message'] ?? '';
$payType = $data['payType'] ?? '';
$responseTime = $data['responseTime'] ?? '';
$extraData = $data['extraData'] ?? '';
$signature = $data['signature'] ?? '';

// Verify Signature
$rawHash = "accessKey=" . MOMO_ACCESS_KEY .
    "&amount=" . $amount .
    "&extraData=" . $extraData .
    "&message=" . $message .
    "&orderId=" . $orderId .
    "&orderInfo=" . $orderInfo .
    "&orderType=" . $orderType .
    "&partnerCode=" . $partnerCode .
    "&payType=" . $payType .
    "&requestId=" . $requestId .
    "&responseTime=" . $responseTime .
    "&resultCode=" . $resultCode .
    "&transId=" . $transId;

$checkSignature = hash_hmac("sha256", $rawHash, MOMO_SECRET_KEY);

if ($signature === $checkSignature) {
    if ($resultCode == 0) { // Payment successful
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'Đã thanh toán', status = 'Đã xác nhận' WHERE id = ?");
        $stmt->execute([$orderId]);

        echo json_encode(['resultCode' => 0, 'message' => 'Success']);
    } else {
        echo json_encode(['resultCode' => $resultCode, 'message' => 'Payment failed']);
    }
} else {
    echo json_encode(['resultCode' => 97, 'message' => 'Invalid signature']);
}
?>
