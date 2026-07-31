<?php
// Cấu hình Cổng Thanh Toán Thực Tế (Payment Gateway Configuration)

// 1. Cấu hình Ví MoMo (MoMo API v2 Signature & Gateway)
define('MOMO_PHONE', '0939916872');                    // Số điện thoại Ví MoMo cá nhân
define('MOMO_PARTNER_CODE', 'MOMO_PARTNER_CODE_HERE'); // Thay mã Partner Code MoMo của bạn
define('MOMO_ACCESS_KEY', 'MOMO_ACCESS_KEY_HERE');     // Thay Access Key MoMo của bạn
define('MOMO_SECRET_KEY', 'MOMO_SECRET_KEY_HERE');     // Thay Secret Key MoMo của bạn
define('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'); // Test Sandbox
// define('MOMO_ENDPOINT', 'https://payment.momo.vn/v2/gateway/api/create');   // Production Thật

// 2. Cấu hình Chuyển Khoản Ngân Hàng Qua VietQR (Quét QR thật bằng tất cả App Ngân hàng Việt Nam)
define('VIETQR_BANK_ID', 'VCB');             // Mã ngân hàng (VCB: Vietcombank, MB: MBBank, TCB: Techcombank, ACB, STB, CTG...)
define('VIETQR_ACCOUNT_NO', '9939916872');   // Số tài khoản ngân hàng thật
define('VIETQR_ACCOUNT_NAME', 'HO NHAT HAO'); // Tên chủ tài khoản ngân hàng
define('USD_TO_VND_RATE', 25400);            // Tỷ giá quy đổi USD sang VND
?>
