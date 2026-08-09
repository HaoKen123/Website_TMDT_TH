<?php
// Cấu hình Cổng Thanh Toán Thực Tế (Payment Gateway Configuration)

// 1. Cấu hình Ví MoMo (MoMo API v2 Signature & Gateway)
define('MOMO_PHONE', '0939916872');                    // Số điện thoại Ví MoMo cá nhân
define('MOMO_PARTNER_CODE', 'MOMO_PARTNER_CODE_HERE'); // Thay mã Partner Code MoMo của bạn
define('MOMO_ACCESS_KEY', 'MOMO_ACCESS_KEY_HERE');     // Thay Access Key MoMo của bạn
define('MOMO_SECRET_KEY', 'MOMO_SECRET_KEY_HERE');     // Thay Secret Key MoMo của bạn
define('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'); // Test Sandbox

// 2. Cấu hình Chuyển Khoản Ngân Hàng Qua VietQR & SePay Webhook (Khôi phục chuẩn 100% như ban đầu)
define('VIETQR_BANK_ID', 'VCB');             // Mã ngân hàng Vietcombank
define('VIETQR_ACCOUNT_NO', 'SBSEPAY0HYNZEHCXONI'); // Số tài khoản ngân hàng (VA) SePay
define('VIETQR_ACCOUNT_NAME', 'HO NHAT HAO'); // Tên chủ tài khoản ngân hàng
define('SEPAY_API_KEY', '9D0LTOE7KAJ7RRWNYUPZKTX63BQAVEHBSXGNP1MAKDD4RM5JLV8FLFWYULO2MSFW'); // API Key xác thực Webhook SePay
define('USD_TO_VND_RATE', 25400);            // Tỷ giá quy đổi USD sang VND
?>
