<?php
// Cấu hình Cloudflare Turnstile (Xác thực Robot Chống Spam)

// 1. Sitekey (Mã công khai dùng trên giao diện HTML):
define('TURNSTILE_SITEKEY', '0x4AAAAAAELV4KShgaZla1w6'); 

// 2. Secret Key (Mã bảo mật dùng kiểm tra phía Server):
define('TURNSTILE_SECRET_KEY', '0x4AAAAAAELV4NzLOPCC4UsTlLRkhRAf0fs');
?>
