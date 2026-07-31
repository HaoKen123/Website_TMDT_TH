<?php
session_start();
require_once 'db.php';
require_once 'config_payment.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Không tìm thấy đơn hàng.");
}

// Convert USD to VND for MoMo (MoMo only supports VND)
$amount_vnd = (string)round($order['total_amount'] * USD_TO_VND_RATE);

// Domain host
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$redirectUrl = $protocol . $domainName . "/pixelgear/payment_success.php?order_id=" . $order_id;
$ipnUrl = $protocol . $domainName . "/pixelgear/momo_ipn.php";

$partnerCode = MOMO_PARTNER_CODE;
$accessKey = MOMO_ACCESS_KEY;
$secretKey = MOMO_SECRET_KEY;
$endpoint = MOMO_ENDPOINT;

$orderInfo = "Thanh toan don hang #" . $order_id . " PixelGear";
$requestId = time() . "";
$requestType = "captureWallet";
$extraData = ""; // Pass base64 encoded JSON if needed

// Raw Signature String according to MoMo v2 Specs
$rawHash = "accessKey=" . $accessKey .
    "&amount=" . $amount_vnd .
    "&extraData=" . $extraData .
    "&ipnUrl=" . $ipnUrl .
    "&orderId=" . $order_id .
    "&orderInfo=" . $orderInfo .
    "&partnerCode=" . $partnerCode .
    "&redirectUrl=" . $redirectUrl .
    "&requestId=" . $requestId .
    "&requestType=" . $requestType;

$signature = hash_hmac("sha256", $rawHash, $secretKey);

$data = [
    'partnerCode' => $partnerCode,
    'partnerName' => 'PixelGear Shop',
    'storeId' => 'PixelGearStore',
    'requestId' => $requestId,
    'amount' => $amount_vnd,
    'orderId' => (string)$order_id,
    'orderInfo' => $orderInfo,
    'redirectUrl' => $redirectUrl,
    'ipnUrl' => $ipnUrl,
    'lang' => 'vi',
    'extraData' => $extraData,
    'requestType' => $requestType,
    'signature' => $signature
];

// cURL Call to MoMo Gateway
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($data))
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

$result = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    die("Lỗi kết nối Cổng MoMo: " . $err);
}

$jsonResult = json_decode($result, true);

if (isset($jsonResult['payUrl'])) {
    // Redirect customer to Official MoMo Gateway / App Page!
    header('Location: ' . $jsonResult['payUrl']);
    exit;
} else {
    // If credentials are placeholders or Sandbox error, display clean MoMo QR fallback
    $_SESSION['momo_error'] = isset($jsonResult['message']) ? $jsonResult['message'] : 'Không khởi tạo được Cổng MoMo API.';
    header("Location: payment.php?order_id=" . $order_id);
    exit;
}
?>
