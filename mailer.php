<?php
require_once 'config_email.php';

function send_smtp_email($toEmail, $subject, $htmlContent) {
    if (!SMTP_ENABLED) {
        return ['success' => false, 'message' => 'SMTP chưa được bật'];
    }

    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $username = SMTP_USERNAME;
    $password = SMTP_PASSWORD;
    $fromEmail = SMTP_FROM_EMAIL;
    $fromName = SMTP_FROM_NAME;

    // Check if credentials are set
    if ($username === 'YOUR_GMAIL@gmail.com' || $password === 'YOUR_APP_PASSWORD_HERE') {
        // Fallback to PHP mail()
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: $fromName <$fromEmail>" . "\r\n";
        $sent = @mail($toEmail, $subject, $htmlContent, $headers);
        return [
            'success' => $sent,
            'message' => $sent ? 'Đã gửi qua PHP mail()' : 'Vui lòng điền Mật khẩu ứng dụng Gmail vào file config_email.php'
        ];
    }

    try {
        $socket = fsockopen(($port == 465 ? 'ssl://' : '') . $host, $port, $errno, $errstr, 15);
        if (!$socket) {
            throw new Exception("Không thể kết nối Server SMTP: $errstr ($errno)");
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
        return ['success' => false, 'message' => 'Lỗi SMTP: ' . $e->getMessage()];
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
        // Fallback for older TLS
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    }
}
?>
