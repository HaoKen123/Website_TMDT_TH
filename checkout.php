<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit;
}

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$cart_count = 0;
$total_price = 0;
$cart_items = [];
$ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));

if ($ids) {
    $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
    $products = $stmt->fetchAll();

    foreach ($products as $product) {
        $qty = $_SESSION['cart'][$product['id']];
        $cart_count += $qty;
        $subtotal = $qty * $product['price'];
        $total_price += $subtotal;
        
        $product['quantity'] = $qty;
        $product['subtotal'] = $subtotal;
        $cart_items[] = $product;
    }
}
$current_region = get_current_region();
?>
<!DOCTYPE html>
<html lang="<?php echo strtolower($current_region); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán 4.0 | PixelGear Shop</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .checkout-layout { display: flex; flex-wrap: wrap; gap: 40px; padding: 40px 20px; max-width: 1200px; margin: 0 auto; }
        .checkout-main { flex: 1; min-width: 300px; }
        .checkout-sidebar { flex: 0 0 400px; background: #f9f9f9; padding: 30px; border-radius: 8px; border: 1px solid var(--border-color); height: fit-content;}
        
        .section-title { font-size: 24px; margin-bottom: 25px; border-bottom: 2px solid var(--primary-color); padding-bottom: 10px; display: inline-block;}
        
        /* Form fields */
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .form-group { flex: 1; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #555;}
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-family: 'Inter', sans-serif; transition: border-color 0.3s;}
        .form-group input:focus { border-color: var(--primary-color); outline: none; }
        
        /* Payment Methods Tabs */
        .payment-methods { margin-top: 30px; }
        .payment-tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;}
        .payment-tab { padding: 12px 20px; border: 1px solid #ccc; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 10px; font-weight: 500; transition: all 0.3s; background: #fff;}
        .payment-tab.active { border-color: var(--primary-color); background: #f0fdf4; color: var(--primary-color); box-shadow: 0 0 0 1px var(--primary-color);}
        .payment-tab.disabled { opacity: 0.55; cursor: not-allowed; pointer-events: none; background: #f8fafc; border-color: #e2e8f0; color: #94a3b8; user-select: none; }
        .payment-tab.disabled i { filter: grayscale(70%); }
        .badge-disabled { font-size: 11px; background: #fee2e2; color: #ef4444; padding: 2px 6px; border-radius: 4px; font-weight: 600; margin-left: auto; }
        .payment-tab i { font-size: 20px; }
        
        /* Payment Contents */
        .payment-content { display: none; padding: 20px; border: 1px solid #ccc; border-radius: 6px; background: #fff; margin-bottom: 30px;}
        .payment-content.active { display: block; animation: fadeIn 0.3s ease; }
        
        /* Order Summary */
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 15px; }
        .summary-item img { width: 50px; height: 50px; border-radius: 4px; object-fit: cover; }
        .summary-item-info { flex: 1; padding: 0 15px; }
        .summary-item-title { font-weight: 600; font-size: 14px; }
        .summary-item-qty { color: #666; font-size: 13px; }
        .summary-total { border-top: 1px solid #ddd; padding-top: 20px; margin-top: 20px; font-size: 18px; font-weight: 700; display: flex; justify-content: space-between; }
        
        .btn-checkout { width: 100%; padding: 15px; font-size: 16px; border-radius: 6px; margin-top: 20px;}

        @media (max-width: 800px) {
            .checkout-sidebar { flex: 1 1 100%; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <div class="logo">
                <a href="index.php" class="logo-link">
                    <span class="glitch-title" data-text="PIXELGEAR">PIXELGEAR</span>
                </a>
            </div>
            <div class="header-icons">
                <!-- Region Switcher -->
                <div class="region-switcher-container">
                    <button class="region-btn" type="button">
                        <?php if ($current_region === 'VN'): ?>
                            <span class="flag-tag">VN</span> <span>Việt Nam (VNĐ)</span>
                        <?php else: ?>
                            <span class="flag-tag">US</span> <span>USA (USD)</span>
                        <?php endif; ?>
                        <i class="fas fa-chevron-down" style="font-size: 10px;"></i>
                    </button>
                    <div class="region-dropdown">
                        <a href="?region=VN" class="region-option <?php echo $current_region === 'VN' ? 'active' : ''; ?>">
                            <span class="flag-tag">VN</span> <span>Việt Nam (VNĐ - ₫)</span>
                        </a>
                        <a href="?region=US" class="region-option <?php echo $current_region === 'US' ? 'active' : ''; ?>">
                            <span class="flag-tag">US</span> <span>United States (USD - $)</span>
                        </a>
                    </div>
                </div>

                <a href="profile.php" style="font-size: 14px; font-weight: 600; color: #fff; text-decoration: none;"><i class="fas fa-user-circle"></i> <?php echo explode(' ', trim($_SESSION['user_name']))[0]; ?></a>
                <a href="cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </div>
        </div>
    </header>

    <div class="checkout-layout">
        <!-- Main Form -->
        <div class="checkout-main">
            <form id="checkout-form" method="POST" action="process_checkout.php">
                <h2 class="section-title"><?php echo $current_region === 'VN' ? 'Thông tin giao hàng' : 'Shipping Information'; ?></h2>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo $current_region === 'VN' ? 'Họ và Tên' : 'Full Name'; ?></label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $current_region === 'VN' ? 'Số điện thoại' : 'Phone Number'; ?></label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo $current_region === 'VN' ? 'Địa chỉ nhận hàng' : 'Shipping Address'; ?></label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                </div>
                
                <?php
                // Fetch shipping fees
                $shipping_fees = [];
                try {
                    $shipping_fees = $pdo->query("SELECT * FROM shipping_fees ORDER BY id ASC")->fetchAll();
                } catch (Exception $e) {}
                ?>
                <div class="form-group" style="margin-top: 15px;">
                    <label><?php echo $current_region === 'VN' ? 'Tỉnh / Thành phố (Tính phí vận chuyển)' : 'Province / State'; ?></label>
                    <select name="province" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; font-size: 14px; outline: none;">
                        <?php if (empty($shipping_fees)): ?>
                            <option value="Khác">Toàn quốc (Phí VC: 30.000₫)</option>
                        <?php else: ?>
                            <?php foreach ($shipping_fees as $sf): ?>
                                <option value="<?php echo htmlspecialchars($sf['province']); ?>">
                                    <?php echo htmlspecialchars($sf['province']); ?> - Phí VC: <?php echo format_price($sf['fee']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="payment-methods">
                    <h2 class="section-title"><?php echo $current_region === 'VN' ? 'Phương thức thanh toán' : 'Payment Method'; ?></h2>
                    
                    <div class="payment-tabs">
                        <div class="payment-tab active" data-target="bank">
                            <i class="fas fa-university" style="color:#0078d7;"></i> <?php echo $current_region === 'VN' ? 'Chuyển Khoản NH (Tự Động 24/7)' : 'Auto Bank Transfer'; ?>
                        </div>
                        <div class="payment-tab" data-target="cod">
                            <i class="fas fa-truck" style="color:#0e8543;"></i> <?php echo $current_region === 'VN' ? 'Thanh toán COD' : 'Cash on Delivery'; ?>
                        </div>
                        <div class="payment-tab disabled" data-target="momo">
                            <i class="fas fa-qrcode" style="color:#A50064;"></i> Ví MoMo / ZaloPay <span class="badge-disabled">Bảo trì</span>
                        </div>
                        <div class="payment-tab disabled" data-target="card">
                            <i class="fab fa-cc-visa" style="color:#1A1F71;"></i> <?php echo $current_region === 'VN' ? 'Thẻ Tín Dụng / Visa' : 'Credit Card'; ?> <span class="badge-disabled">Bảo trì</span>
                        </div>
                    </div>

                    <!-- Hidden input to store selected method -->
                    <input type="hidden" name="payment_method" id="payment_method" value="bank">

                    <div class="payment-content active" id="content-bank">
                        <div style="background:#eff6ff; padding:15px; border-radius:6px; border:1px solid #bfdbfe; color:#1e40af;">
                            <i class="fas fa-bolt" style="color:#eab308; margin-right:6px;"></i>
                            <strong>Tự động xác nhận 24/7 qua SePay:</strong> Hệ thống sẽ tự động hiển thị mã VietQR chuyển khoản. Sau khi chuyển xong, đơn hàng sẽ được duyệt tức thì!
                        </div>
                    </div>

                    <div class="payment-content" id="content-cod">
                        <div style="text-align:center; padding:15px; background:#f0fdf4; border-radius:6px; border:1px solid #bbf7d0; color:#166534;">
                            <i class="fas fa-box-open" style="font-size:32px; color:#16a34a; margin-bottom:10px;"></i>
                            <p><?php echo $current_region === 'VN' ? 'Thanh toán tiền mặt trực tiếp cho nhân viên giao hàng (Shipper) khi nhận hàng tại nhà.' : 'Pay with cash upon delivery.'; ?></p>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sidebar Summary -->
        <div class="checkout-sidebar">
            <h2 class="section-title"><?php echo $current_region === 'VN' ? 'Đơn hàng của bạn' : 'Your Order'; ?></h2>
            
            <?php foreach ($cart_items as $item): ?>
            <div class="summary-item">
                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="">
                <div class="summary-item-info">
                    <div class="summary-item-title"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="summary-item-qty">SL: <?php echo $item['quantity']; ?></div>
                </div>
                <div class="summary-item-price"><?php echo format_price($item['subtotal']); ?></div>
            </div>
            <?php endforeach; ?>

            <div class="summary-total">
                <span><?php echo $current_region === 'VN' ? 'TỔNG CỘNG' : 'TOTAL'; ?></span>
                <span style="color:var(--primary-color)"><?php echo format_price($total_price); ?></span>
            </div>

            <button type="submit" form="checkout-form" class="btn btn-primary btn-checkout"><?php echo $current_region === 'VN' ? 'ĐẶT HÀNG & THANH TOÁN' : 'PLACE ORDER & PAY'; ?></button>
        </div>
    </div>

    <script>
        const tabs = document.querySelectorAll('.payment-tab');
        const contents = document.querySelectorAll('.payment-content');
        const hiddenInput = document.getElementById('payment_method');

        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                if (tab.classList.contains('disabled')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                const target = tab.getAttribute('data-target');
                document.getElementById('content-' + target).classList.add('active');
                hiddenInput.value = target;
            });
        });
    </script>
</body>
</html>
