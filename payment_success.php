<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: index.php');
    exit;
}
$order_id = $_GET['order_id'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/png" href="favicon.png?v=2">
    <link rel="shortcut icon" href="favicon.ico?v=2">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công | PixelGear</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body style="background: #f4f8fe; display: flex; align-items: center; justify-content: center; height: 100vh;">
    <div style="background: white; padding: 50px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; max-width: 500px;">
        <img src="https://cdn-icons-png.flaticon.com/512/190/190411.png" width="100" style="margin-bottom: 20px;">
        <h2 style="color: var(--primary-color); margin-bottom: 15px;">XÁC NHẬN ĐƠN HÀNG THÀNH CÔNG!</h2>
        <p style="color: #555; margin-bottom: 30px; font-size: 16px;">
            Cảm ơn <b><?php echo htmlspecialchars($_SESSION['user_name']); ?></b> đã mua sắm tại PixelGear.<br>
            Đơn hàng <b>#<?php echo htmlspecialchars($order_id); ?></b> của bạn đã được ghi nhận vào hệ thống.<br>
            Vui lòng vào trang Hồ sơ để theo dõi trạng thái đơn hàng.
        </p>
        <div style="display:flex; gap:10px; justify-content:center;">
            <a href="index.php" class="btn" style="background: #eee; color: #333; font-weight:600;">VỀ TRANG CHỦ</a>
            <a href="profile.php" class="btn btn-primary">XEM ĐƠN HÀNG</a>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        if ('speechSynthesis' in window) {
            const text = "Cảm ơn quý khách đã mua hàng và thanh toán thành công tại PixelGear!";
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'vi-VN';
            utterance.rate = 1.0;
            utterance.pitch = 1.0;
            utterance.volume = 1.0;
            
            const speak = () => {
                const voices = window.speechSynthesis.getVoices();
                const viVoice = voices.find(v => v.lang && (v.lang.includes('vi') || v.lang.includes('VI')));
                if (viVoice) utterance.voice = viVoice;
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(utterance);
            };
            
            speak();
            if (window.speechSynthesis.onvoiceschanged !== undefined) {
                window.speechSynthesis.onvoiceschanged = speak;
            }
        }
    });
    </script>
</body>
</html>
