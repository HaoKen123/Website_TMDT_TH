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

if (!$order || $order['payment_status'] == 'Đã thanh toán') {
    header('Location: profile.php');
    exit;
}

$amount_vnd = round($order['total_amount'] * USD_TO_VND_RATE);
$momo_error = isset($_SESSION['momo_error']) ? $_SESSION['momo_error'] : '';
unset($_SESSION['momo_error']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cổng Thanh Toán Thực Tế | PixelGear Shop</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7fa; font-family: 'Inter', sans-serif; }
        .payment-container { max-width: 750px; margin: 40px auto; padding: 0 20px; }
        .payment-card { background: #ffffff; border-radius: 12px; border: 1px solid #e1e8ed; box-shadow: 0 10px 30px rgba(0,0,0,0.06); padding: 35px; }
        .payment-header { text-align: center; border-bottom: 1px solid #eee; padding-bottom: 25px; margin-bottom: 25px; }
        .payment-header h2 { font-size: 24px; font-weight: 800; color: #1e293b; margin-bottom: 10px; }
        .amount-tag { font-size: 36px; font-weight: 800; color: #2e7d32; }
        .amount-sub { font-size: 14px; color: #64748b; font-weight: 600; margin-top: 5px; }
        
        .qr-box { background: #fafafa; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 25px; text-align: center; margin: 25px 0; }
        .qr-box img { width: 220px; height: 220px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); background: #fff; padding: 10px; }
        
        .bank-details { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; text-align: left; margin-top: 20px; }
        .bank-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .bank-row:last-child { border-bottom: none; }
        .copy-btn { background: #e2e8f0; border: none; padding: 4px 10px; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: 600; }
        .copy-btn:hover { background: #cbd5e1; }

        /* Card Form */
        .card-form { text-align: left; background: #fff; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .card-form .form-group { margin-bottom: 15px; }
        .card-form label { display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px; }
        .card-form input { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; font-size: 14px; outline: none; }
        .card-form input:focus { border-color: #2e7d32; box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.15); }
        .card-row { display: flex; gap: 15px; }

        .btn-momo-real { background: #a50064; color: white; width: 100%; padding: 16px; border-radius: 8px; font-size: 16px; font-weight: 800; border: none; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; transition: background 0.2s; box-shadow: 0 4px 15px rgba(165, 0, 100, 0.3); }
        .btn-momo-real:hover { background: #880052; }
        
        .btn-confirm-pay { background: #2e7d32; color: white; width: 100%; padding: 16px; border-radius: 8px; font-size: 16px; font-weight: 800; border: none; cursor: pointer; margin-top: 20px; transition: background 0.2s; box-shadow: 0 4px 15px rgba(46, 125, 50, 0.3); }
        .btn-confirm-pay:hover { background: #1b5e20; }
        
        .security-badge { text-align: center; margin-top: 20px; font-size: 12px; color: #94a3b8; display: flex; align-items: center; justify-content: center; gap: 6px; }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <h2>Thanh Toán Đơn Hàng #<?php echo $order['id']; ?></h2>
                <div class="amount-tag">$<?php echo number_format($order['total_amount'], 2); ?></div>
                <div class="amount-sub">Tương đương: <strong><?php echo number_format($amount_vnd); ?> VNĐ</strong> (Tỷ giá 1 USD = <?php echo number_format(USD_TO_VND_RATE); ?> VNĐ)</div>
            </div>

            <?php if (strpos($order['payment_method'], 'MoMo') !== false): ?>
                <!-- MoMo Real Gateway & QR -->
                <div style="text-align: center;">
                    <div style="margin-bottom: 20px;">
                        <a href="momo_pay.php?order_id=<?php echo $order['id']; ?>" class="btn-momo-real">
                            <i class="fas fa-wallet" style="margin-right: 8px;"></i> MỞ CỔNG THANH TOÁN VÍ MOMO THẬT (MOMO API V2)
                        </a>
                    </div>

                    <?php if ($momo_error): ?>
                        <div style="background: #fff1f2; border: 1px solid #fecdd3; color: #be123c; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 20px;">
                            <i class="fas fa-info-circle"></i> <strong>Lưu ý MoMo API:</strong> <?php echo htmlspecialchars($momo_error); ?><br>
                            <em>(Vui lòng quét mã QR MoMo bên dưới để hoàn tất thanh toán)</em>
                        </div>
                    <?php endif; ?>

                    <div class="qr-box">
                        <h4 style="margin-bottom: 15px; color: #a50064;"><i class="fas fa-qrcode"></i> Quét Mã QR MoMo Trực Tiếp (SĐT: <?php echo MOMO_PHONE; ?>)</h4>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=2|99|<?php echo MOMO_PHONE; ?>|HO%20NHAT%20HAO||0|0|<?php echo $amount_vnd; ?>|Thanh%20toan%20DH%20<?php echo $order['id']; ?>|transfer_myqr" alt="MoMo QR Code">
                        <p style="margin-top: 15px; font-size: 14px; color: #475569;">
                            Ví MoMo: <strong><?php echo MOMO_PHONE; ?></strong> (HO NHAT HAO)<br>
                            Nội dung chuyển khoản: <strong style="color: #a50064;">Thanh toan DH <?php echo $order['id']; ?></strong>
                        </p>
                    </div>
                </div>

            <?php elseif (strpos($order['payment_method'], 'Ngân Hàng') !== false): ?>
                <?php $order_code = 'DH' . sprintf('%06d', $order['id']); ?>
                <!-- VietQR & SePay Auto Banking Transfer -->
                <div class="qr-box">
                    <h4 style="margin-bottom: 15px; color: #003087;"><i class="fas fa-university"></i> Quét QR Thanh Toán Ngân Hàng Tự Động (VietQR / SePay)</h4>
                    <img src="https://img.vietqr.io/image/<?php echo VIETQR_BANK_ID; ?>-<?php echo VIETQR_ACCOUNT_NO; ?>-compact2.png?amount=<?php echo $amount_vnd; ?>&addInfo=<?php echo $order_code; ?>&accountName=<?php echo urlencode(VIETQR_ACCOUNT_NAME); ?>" alt="VietQR Banking Code" style="width: 260px; height: auto;">
                    
                    <div class="bank-details">
                        <div class="bank-row">
                            <span>Ngân hàng:</span>
                            <strong>Vietcombank (VCB)</strong>
                        </div>
                        <div class="bank-row">
                            <span>Số tài khoản:</span>
                            <strong><?php echo VIETQR_ACCOUNT_NO; ?> <button class="copy-btn" onclick="navigator.clipboard.writeText('<?php echo VIETQR_ACCOUNT_NO; ?>'); alert('Đã sao chép số tài khoản!');">Copy</button></strong>
                        </div>
                        <div class="bank-row">
                            <span>Tên chủ tài khoản:</span>
                            <strong><?php echo VIETQR_ACCOUNT_NAME; ?></strong>
                        </div>
                        <div class="bank-row">
                            <span>Số tiền thanh toán:</span>
                            <strong style="color: #2e7d32;"><?php echo number_format($amount_vnd); ?> VNĐ <button class="copy-btn" onclick="navigator.clipboard.writeText('<?php echo $amount_vnd; ?>'); alert('Đã sao chép số tiền!');">Copy</button></strong>
                        </div>
                        <div class="bank-row">
                            <span>Nội dung chuyển khoản (BẮT BUỘC):</span>
                            <strong style="color: #d97706;"><?php echo $order_code; ?> <button class="copy-btn" onclick="navigator.clipboard.writeText('<?php echo $order_code; ?>'); alert('Đã sao chép nội dung!');">Copy</button></strong>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Real Credit / Debit Card Payment Form -->
                <div class="card-form">
                    <h4 style="margin-bottom: 15px; color: #1e293b;"><i class="fab fa-cc-visa" style="color:#1A1F71;"></i> Thanh Toán Thẻ Quốc Tế / Thẻ Ghi Nợ (Visa / MasterCard / JCB)</h4>
                    
                    <div class="form-group">
                        <label>Số thẻ (Card Number)</label>
                        <input type="text" id="cardNumber" placeholder="4532 •••• •••• 8899" maxlength="19" required>
                    </div>

                    <div class="form-group">
                        <label>Tên in trên thẻ (Cardholder Name)</label>
                        <input type="text" id="cardName" placeholder="HO NHAT HAO" style="text-transform: uppercase;" required>
                    </div>

                    <div class="card-row">
                        <div class="form-group" style="flex:1;">
                            <label>Hạn thẻ (MM/YY)</label>
                            <input type="text" id="cardExpiry" placeholder="12/28" maxlength="5" required>
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Mã bảo mật (CVV/CVC)</label>
                            <input type="password" id="cardCvv" placeholder="•••" maxlength="4" required>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div id="autoStatusBanner" style="margin-top:20px; background: #e0f2fe; border: 1px solid #bae6fd; color: #0369a1; padding: 12px 18px; border-radius: 8px; font-size: 14px; text-align: center; font-weight: 600;">
                <i class="fas fa-sync fa-spin" style="margin-right: 8px;"></i> Hệ thống đang tự động kiểm tra giao dịch chuyển khoản 24/7 (Tự động chuyển trang khi nhận được tiền)...
            </div>



            <div class="security-badge">
                <i class="fas fa-shield-alt" style="color: #2e7d32;"></i> Thanh toán mã hóa bảo mật SSL 256-bit chuẩn quốc tế PCI-DSS.
            </div>
        </div>
    </div>

    <script>
        const orderId = <?php echo $order['id']; ?>;

        // Auto-polling payment status every 2.5 seconds
        const pollInterval = setInterval(async () => {
            try {
                const res = await fetch(`api/check_payment_status.php?order_id=${orderId}`);
                const data = await res.json();

                if (data.paid) {
                    clearInterval(pollInterval);
                    const banner = document.getElementById('autoStatusBanner');
                    if (banner) {
                        banner.style.background = '#dcfce7';
                        banner.style.borderColor = '#86efac';
                        banner.style.color = '#15803d';
                        banner.innerHTML = '<i class="fas fa-check-circle" style="font-size:18px;"></i> <strong>ĐÃ NHẬN THANH TOÁN THÀNH CÔNG!</strong> Đang tự động chuyển hướng...';
                    }
                    setTimeout(() => {
                        window.location.href = `payment_success.php?order_id=${orderId}`;
                    }, 1200);
                }
            } catch (err) {
                console.error('Lỗi tự động kiểm tra thanh toán:', err);
            }
        }, 2500);


        // Card formatting
        const cardNumInput = document.getElementById('cardNumber');
        if (cardNumInput) {
            cardNumInput.addEventListener('input', (e) => {
                let v = e.target.value.replace(/\D/g, '').substring(0, 16);
                let parts = [];
                for (let i = 0; i < v.length; i += 4) {
                    parts.push(v.substring(i, i + 4));
                }
                e.target.value = parts.join(' ');
            });
        }

        const cardExpInput = document.getElementById('cardExpiry');
        if (cardExpInput) {
            cardExpInput.addEventListener('input', (e) => {
                let v = e.target.value.replace(/\D/g, '').substring(0, 4);
                if (v.length >= 3) {
                    e.target.value = v.substring(0, 2) + '/' + v.substring(2);
                } else {
                    e.target.value = v;
                }
            });
        }
    </script>
</body>
</html>
