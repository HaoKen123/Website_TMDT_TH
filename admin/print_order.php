<?php
session_start();
require_once '../db.php';
require_once '../lang.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    die("Đơn hàng không tồn tại!");
}

$stmtItems = $pdo->prepare("SELECT oi.*, p.name, p.image_url 
                           FROM order_items oi 
                           JOIN products p ON oi.product_id = p.id 
                           WHERE oi.order_id = ?");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/png" href="../favicon.png?v=2">
    <link rel="shortcut icon" href="../favicon.ico?v=2">
    <meta charset="UTF-8">
    <title>Hóa Đơn Bán Hàng #<?php echo $order['id']; ?> | PixelGear Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 40px; }
        .invoice-box { max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 12px; border: 1px solid #cbd5e1; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .invoice-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #15803d; padding-bottom: 20px; margin-bottom: 30px; }
        .company-logo { font-size: 26px; font-weight: 800; color: #15803d; text-transform: uppercase; letter-spacing: 1px; }
        .company-sub { font-size: 12px; color: #64748b; font-weight: 500; margin-top: 4px; }
        .invoice-title { text-align: right; }
        .invoice-title h2 { margin: 0; font-size: 24px; color: #0f172a; }
        .invoice-info { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .info-col { flex: 1; }
        .info-col h4 { margin: 0 0 10px 0; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-col p { margin: 4px 0; font-size: 14px; font-weight: 600; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f1f5f9; padding: 12px; text-align: left; font-size: 13px; color: #475569; border-bottom: 2px solid #cbd5e1; }
        td { padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        .total-row td { font-weight: 800; font-size: 16px; border-top: 2px solid #15803d; color: #15803d; }
        
        /* Signature & Stamp Section */
        .signatures-container {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            text-align: center;
        }
        .signature-box {
            width: 280px;
            position: relative;
        }
        .signature-box h5 {
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 4px 0;
            color: #0f172a;
        }
        .signature-box .sub-note {
            color: #64748b;
            font-size: 12px;
            margin: 0;
        }
        .seal-area {
            position: relative;
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 5px;
        }
        .director-signature-img {
            max-height: 85px;
            max-width: 180px;
            mix-blend-mode: multiply;
            position: relative;
            z-index: 1;
        }
        .company-stamp-img {
            position: absolute;
            width: 120px;
            height: 120px;
            right: 15px;
            top: 10px;
            mix-blend-mode: multiply;
            transform: rotate(-8deg);
            opacity: 0.95;
            z-index: 2;
            pointer-events: none;
        }
        .signer-name {
            font-weight: 700;
            font-size: 13px;
            color: #1e293b;
            margin-top: 5px;
        }

        .print-btn { background: #15803d; color: #fff; border: none; padding: 12px 28px; font-weight: 700; font-size: 15px; border-radius: 6px; cursor: pointer; display: block; margin: 30px auto 0 auto; box-shadow: 0 4px 12px rgba(21,128,61,0.25); }
        .print-btn:hover { background: #166534; }

        @media print {
            body { background: #fff; padding: 0; }
            .invoice-box { border: none; box-shadow: none; padding: 0; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

<div class="invoice-box">
    <div class="invoice-header">
        <div>
            <div class="company-logo">
                <i class="fas fa-cube" style="color: #f59e0b;"></i> PIXELGEAR STORE
            </div>
            <div class="company-sub">Thế Giới Đồ Chơi & Thời Trang Minecraft Chính Hãng</div>
        </div>
        <div class="invoice-title">
            <h2>HÓA ĐƠN BÁN HÀNG</h2>
            <p style="margin: 5px 0 0 0; color: #64748b; font-size: 14px;">Mã đơn: <strong>#<?php echo $order['id']; ?></strong></p>
        </div>
    </div>

    <div class="invoice-info">
        <div class="info-col">
            <h4>Thông tin khách hàng</h4>
            <p>Họ tên: <?php echo htmlspecialchars($order['customer_name']); ?></p>
            <p>Số điện thoại: <?php echo htmlspecialchars($order['customer_phone']); ?></p>
            <p>Địa chỉ: <?php echo htmlspecialchars($order['customer_address']); ?></p>
        </div>
        <div class="info-col" style="text-align: right;">
            <h4>Chi tiết đơn hàng</h4>
            <p>Ngày đặt: <?php echo date("d/m/Y H:i", strtotime($order['created_at'])); ?></p>
            <p>Thanh toán: <?php echo htmlspecialchars($order['payment_method']); ?></p>
            <p>Trạng thái thanh toán: <strong style="color: #15803d;"><?php echo htmlspecialchars($order['payment_status']); ?></strong></p>
            <p>Tình trạng đơn: <strong><?php echo htmlspecialchars($order['status']); ?></strong></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 45px;">STT</th>
                <th>Sản phẩm</th>
                <th style="text-align: center;">Đơn giá</th>
                <th style="text-align: center;">Số lượng</th>
                <th style="text-align: right;">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            foreach ($items as $item): 
                $sub = $item['quantity'] * $item['price'];
            ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                <td style="text-align: center;">$<?php echo number_format($item['price'], 2); ?></td>
                <td style="text-align: center; font-weight: 700;"><?php echo $item['quantity']; ?></td>
                <td style="text-align: right; font-weight: 700;">$<?php echo number_format($sub, 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="4" style="text-align: right;">TỔNG CỘNG HÓA ĐƠN:</td>
                <td style="text-align: right;">$<?php echo number_format($order['total_amount'], 2); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="signatures-container">
        <div class="signature-box">
            <h5>NGƯỜI MUA HÀNG</h5>
            <p class="sub-note">(Ký và ghi rõ họ tên)</p>
            <div class="seal-area" style="height: 110px;"></div>
            <div class="signer-name"><?php echo htmlspecialchars($order['customer_name']); ?></div>
        </div>

        <div class="signature-box">
            <h5>XÁC NHẬN CỬA HÀNG</h5>
            <p class="sub-note">(Ký tên và đóng dấu xác nhận)</p>
            <div class="seal-area">
                <!-- Chữ ký điện tử -->
                <img src="../images/director_signature.jpg" alt="Chữ ký giám đốc" class="director-signature-img" onerror="this.style.display='none'">
                <!-- Con dấu tròn đỏ chính thức -->
                <img src="../images/company_stamp.jpg" alt="Con dấu công ty" class="company-stamp-img" onerror="this.style.display='none'">
            </div>
            <div class="signer-name">HỒ NHẬT HẢO</div>
            <div style="font-size: 11px; color: #64748b;">(Đại diện PixelGear Store)</div>
        </div>
    </div>

    <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> IN HÓA ĐƠN NÀY</button>
</div>

<script>
if (window.location.search.includes('autoprint=1')) {
    window.onload = function() { window.print(); };
}
</script>

</body>
</html>
