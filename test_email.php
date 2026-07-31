<?php
require_once 'mailer.php';

$to = 'gekkoria@gmail.com';
$subject = "🎁 HỆ THỐNG PIXELGEAR GỬI THỬ EMAIL THẬT CỦA BẠN!";
$html = '
<div style="font-family: Arial; padding: 20px; background: #f0fdf4; border: 2px solid #16a34a; border-radius: 10px;">
    <h2 style="color: #15803d;">CHÀO MỪNG BẠN ĐẾN VỚI PIXELGEAR STORE!</h2>
    <p>Hệ thống Gmail SMTP đã hoạt động THẬT 100%.</p>
    <p>Mã giảm giá ưu đãi của bạn: <strong style="font-size: 20px; color: #16a34a;">WELCOME15</strong> (Giảm 15% cho đơn đầu tiên).</p>
</div>
';

$res = send_smtp_email($to, $subject, $html);
echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
