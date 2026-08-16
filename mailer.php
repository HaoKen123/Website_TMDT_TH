<?php
require_once 'config_email.php';

function send_smtp_email($toEmail, $subject, $htmlContent) {
    if (!defined('SMTP_ENABLED') || !SMTP_ENABLED) {
        return ['success' => false, 'message' => 'SMTP chưa được bật'];
    }

    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $username = SMTP_USERNAME;
    $password = SMTP_PASSWORD;
    $fromEmail = SMTP_FROM_EMAIL;
    $fromName = SMTP_FROM_NAME;

    // Helper for PHP mail() fallback
    $fallbackMail = function() use ($toEmail, $subject, $htmlContent, $fromName, $fromEmail) {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>\r\n";
        $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        $sent = @mail($toEmail, $encodedSubject, $htmlContent, $headers);
        return [
            'success' => $sent,
            'message' => $sent ? 'Đã gửi qua PHP mail()' : 'Không thể gửi mail qua server. Vui lòng thử lại sau!'
        ];
    };

    // If default dummy credentials, use fallback
    if ($username === 'YOUR_GMAIL@gmail.com' || $password === 'YOUR_APP_PASSWORD_HERE') {
        return $fallbackMail();
    }

    try {
        // Attempt socket connection (timeout 5s)
        $socket = @fsockopen(($port == 465 ? 'ssl://' : '') . $host, $port, $errno, $errstr, 5);
        if (!$socket) {
            // Socket blocked on Shared Hosting -> Fallback to PHP mail()
            return $fallbackMail();
        }

        read_smtp_response($socket);
        send_smtp_command($socket, "EHLO " . gethostname());
        
        if ($port == 587) {
            send_smtp_command($socket, "STARTTLS");
            crypto_enable($socket);
            send_smtp_command($socket, "EHLO " . gethostname());
        }

        // Authenticate
        send_smtp_command($socket, "AUTH LOGIN");
        send_smtp_command($socket, base64_encode($username));
        send_smtp_command($socket, base64_encode($password));

        // Mail From & To
        send_smtp_command($socket, "MAIL FROM: <$fromEmail>");
        send_smtp_command($socket, "RCPT TO: <$toEmail>");
        send_smtp_command($socket, "DATA");

        // Headers and MIME Body
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>\r\n";
        $headers .= "To: <$toEmail>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "Date: " . date("r") . "\r\n\r\n";

        $fullBody = $headers . $htmlContent . "\r\n.\r\n";
        fputs($socket, $fullBody);
        read_smtp_response($socket);

        send_smtp_command($socket, "QUIT");
        fclose($socket);

        return ['success' => true, 'message' => 'Gửi Email SMTP thành công!'];

    } catch (Exception $e) {
        return $fallbackMail();
    }
}

function send_smtp_command($socket, $cmd) {
    fputs($socket, $cmd . "\r\n");
    return read_smtp_response($socket);
}

function read_smtp_response($socket) {
    $response = "";
    while ($str = fgets($socket, 512)) {
        $response .= $str;
        if (substr($str, 3, 1) == " ") {
            break;
        }
    }
    return $response;
}

function crypto_enable($socket) {
    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    }
}

/**
 * Gửi email chứa Voucher ưu đãi 15% + Freeship cho khách hàng
 */
function send_voucher_email($toEmail, $userName = '') {
    $subject = "🎁 Tặng bạn Bộ Đôi Voucher Giảm 15% & Freeship từ PixelGear Store!";
    $nameDisplay = !empty($userName) ? htmlspecialchars($userName) : 'Bạn';
    
    $htmlContent = '
    <!DOCTYPE html>
    <html lang="vi">
    <head>
    <link rel="icon" type="image/png" href="favicon.png?v=2">
    <link rel="shortcut icon" href="favicon.ico?v=2">
        <meta charset="utf-8">
        <style>
            body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #334155; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
            .header { background: linear-gradient(135deg, #059669, #10b981); color: #ffffff; text-align: center; padding: 35px 20px; }
            .header h1 { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: 1px; }
            .content { padding: 30px 25px; line-height: 1.7; }
            .greeting { font-size: 18px; font-weight: 600; color: #0f172a; margin-bottom: 15px; }
            .voucher-box { background: #f0fdf4; border: 2px dashed #10b981; border-radius: 12px; padding: 20px; text-align: center; margin: 25px 0; }
            .voucher-title { font-weight: 700; color: #047857; font-size: 14px; text-transform: uppercase; margin-bottom: 6px; }
            .code { font-size: 24px; font-weight: 800; color: #059669; letter-spacing: 2px; background: #ffffff; padding: 10px 20px; border-radius: 8px; display: inline-block; margin: 6px 0 16px 0; border: 1px solid #a7f3d0; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
            .btn-wrap { text-align: center; margin-top: 25px; }
            .btn { display: inline-block; background: #10b981; color: #ffffff !important; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 700; font-size: 16px; box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
            .footer { background: #f8fafc; text-align: center; padding: 20px; font-size: 13px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎮 PIXELGEAR STORE</h1>
                <p style="margin:8px 0 0 0; opacity:0.95; font-size:15px;">Quà Tặng Đăng Ký Thành Viên Mới</p>
            </div>
            <div class="content">
                <div class="greeting">Xin chào ' . $nameDisplay . ' 👋,</div>
                <p>Cảm ơn bạn đã tham gia cùng cộng đồng <strong>PixelGear Store</strong>! Chúng tôi gửi tặng bạn bộ đôi mã giảm giá độc quyền cho đơn hàng sắp tới:</p>
                
                <div class="voucher-box">
                    <div class="voucher-title">🏷️ MÃ GIẢM 15% CHO ĐƠN HÀNG</div>
                    <div class="code">WELCOME15</div>

                    <div class="voucher-title" style="margin-top: 10px;">🚚 MÃ MIỄN PHÍ VẬN CHUYỂN</div>
                    <div class="code">FREESHIP</div>
                </div>

                <p>👉 Bạn chỉ cần sao chép mã trên và dán vào mục <strong>Mã giảm giá</strong> tại trang Thanh toán để nhận ưu đãi ngay nhé!</p>
                
                <div class="btn-wrap">
                    <a href="https://honhathao.id.vn" class="btn">MUA SẮM NGAY 🛒</a>
                </div>
            </div>
            <div class="footer">
                <p>© 2026 PixelGear Store. Tất cả quyền được bảo lưu.</p>
                <p style="font-size:11px; margin-top:4px;">Email này được gửi tự động. Vui lòng không phản hồi trực tiếp qua email này.</p>
            </div>
        </div>
    </body>
    </html>';

    return send_smtp_email($toEmail, $subject, $htmlContent);
}
