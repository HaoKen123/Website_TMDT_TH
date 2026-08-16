<?php
session_start();
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/png" href="favicon.png?v=2">
    <link rel="shortcut icon" href="favicon.ico?v=2">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập bằng Google</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Roboto', sans-serif; background-color: #ffffff; display: flex; flex-direction: column; justify-content: space-between; min-height: 100vh; color: #202124; }
        .header { display: flex; align-items: center; padding: 16px 24px; border-bottom: 1px solid #dadce0; }
        .google-logo { display: flex; align-items: center; gap: 8px; font-weight: 500; font-size: 16px; color: #5f6368; }
        .container { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; max-width: 450px; margin: 0 auto; width: 100%; text-align: center; }
        .title { font-size: 24px; font-weight: 500; color: #202124; margin-top: 16px; margin-bottom: 8px; }
        .subtitle { font-size: 14px; color: #5f6368; margin-bottom: 32px; }
        .account-card { width: 100%; border: 1px solid #dadce0; border-radius: 8px; overflow: hidden; margin-bottom: 24px; }
        .account-item { display: flex; align-items: center; padding: 14px 16px; border-bottom: 1px solid #f1f3f4; cursor: pointer; transition: background 0.2s; text-align: left; }
        .account-item:last-child { border-bottom: none; }
        .account-item:hover { background-color: #f8f9fa; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: #15803d; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; margin-right: 14px; }
        .acc-details { flex: 1; }
        .acc-name { font-size: 14px; font-weight: 500; color: #202124; }
        .acc-email { font-size: 12px; color: #5f6368; }
        .add-account { display: flex; align-items: center; padding: 14px 16px; cursor: pointer; color: #1a73e8; font-size: 14px; font-weight: 500; }
        .add-account:hover { background-color: #f8f9fa; }
        .add-account i { margin-right: 14px; font-size: 18px; }
        .footer { padding: 16px 24px; border-top: 1px solid #f1f3f4; display: flex; justify-content: space-between; font-size: 12px; color: #5f6368; }
        .footer a { color: #5f6368; text-decoration: none; }
        .footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="header">
    <div class="google-logo">
        <svg width="24" height="24" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.66 0 6.6 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24s.92 7.54 2.55 10.78l7.98-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.6 42.62 14.66 48 24 48z"/></svg>
        <span>Đăng nhập bằng Google</span>
    </div>
</div>

<div class="container">
    <svg width="48" height="48" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.66 0 6.6 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24s.92 7.54 2.55 10.78l7.98-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.6 42.62 14.66 48 24 48z"/></svg>
    <h1 class="title">Chọn một tài khoản</h1>
    <p class="subtitle">để tiếp tục đến PixelGear Store</p>

    <div class="account-card">
        <!-- Account option 1 -->
        <div class="account-item" onclick="selectAccount('honhathao1905@gmail.com', 'Nhat Hao')">
            <div class="avatar">N</div>
            <div class="acc-details">
                <div class="acc-name">Nhat Hao</div>
                <div class="acc-email">honhathao1905@gmail.com</div>
            </div>
            <i class="fas fa-chevron-right" style="color: #5f6368; font-size: 12px;"></i>
        </div>

        <!-- Add custom email -->
        <div class="add-account" onclick="inputAccount()">
            <i class="fas fa-user-circle"></i>
            <span>Sử dụng một tài khoản khác</span>
        </div>
    </div>
</div>

<div class="footer">
    <span>Tiếng Việt</span>
    <div>
        <a href="#">Trợ giúp</a> · <a href="#">Bảo mật</a> · <a href="#">Điều khoản</a>
    </div>
</div>

<script>
function selectAccount(email, name) {
    fetch('google_login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email, name: name })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (window.opener && !window.opener.closed) {
                window.opener.location.href = data.redirect;
                window.close();
            } else {
                window.location.href = data.redirect;
            }
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(e => alert('Lỗi xác thực Google!'));
}

function inputAccount() {
    const email = prompt("Nhập địa chỉ Email Google của bạn:", "khachhang@gmail.com");
    if (!email) return;
    const name = prompt("Nhập Tên của bạn:", "Thành viên Google");
    selectAccount(email, name);
}
</script>
</body>
</html>
