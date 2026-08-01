<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

// Fetch products in cart
$cart_items = [];
$total_price = 0;
$cart_count = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
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
    <title>Giỏ Hàng | PixelGear Shop</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cart-container { padding: 50px 20px; min-height: 500px; }
        .cart-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .cart-table th, .cart-table td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .cart-table th { background-color: var(--bg-color-gray); }
        .cart-item-img { width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border-color); }
        .cart-summary { background: var(--bg-color-gray); padding: 30px; border-radius: 8px; max-width: 400px; margin-left: auto; border: 1px solid var(--border-color); }
        .cart-summary h3 { margin-bottom: 20px; }
        .cart-summary .total { font-size: 24px; font-weight: 700; color: var(--primary-color); margin-bottom: 20px; }
        .remove-btn { color: var(--sale-color); cursor: pointer; background: none; border: none; font-size: 14px; }
        .remove-btn:hover { text-decoration: underline; }
        .empty-cart { text-align: center; padding: 50px; }
        
        .quantity-control {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
        }
        .quantity-btn {
            background: #f1f1f1;
            border: none;
            width: 32px;
            height: 32px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }
        .quantity-btn:hover { background: #e2e2e2; }
        .quantity-input {
            width: 45px;
            height: 32px;
            border: none;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            outline: none;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="header-container" style="width: 100%;">
            <div class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </div>
            
            <div class="logo">
                <h1 class="glitch-title" data-text="PIXELGEAR">PIXELGEAR</h1>
            </div>

            <nav class="main-nav">
                <ul>
                    <li><a href="index.php" class="active-nav <?php echo $current_page === 'index' ? 'active' : ''; ?>"><?php echo __('NAV_HOME'); ?></a></li>
                    <li><a href="cart.php" class="active-nav <?php echo $current_page === 'cart' ? 'active' : ''; ?>"><?php echo __('NAV_CART'); ?></a></li>
                </ul>
            </nav>

            <div class="header-icons">
                <!-- Region & Currency Switcher -->
                <div class="region-switcher-container">
                    <button class="region-btn" type="button">
                        <?php if ($current_region === 'VN'): ?>
                            <div class="flag vn"><i class="fas fa-flag"></i></div>
                            <span>Việt Nam</span>
                        <?php else: ?>
                            <div class="flag us"><i class="fas fa-flag"></i></div>
                            <span>USA</span>
                        <?php endif; ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="region-dropdown">
                        <a href="?region=VN" class="region-option <?php echo $current_region === 'VN' ? 'active' : ''; ?>">
                            <div class="flag vn"><i class="fas fa-flag"></i></div>
                            <span>Việt Nam (₫)</span>
                        </a>
                        <a href="?region=US" class="region-option <?php echo $current_region === 'US' ? 'active' : ''; ?>">
                            <div class="flag us"><i class="fas fa-flag"></i></div>
                            <span>USA ($)</span>
                        </a>
                    </div>
                </div>

                <a href="index.php" class="btn-primary btn-sm" style="padding: 8px 16px; font-size: 11px; text-decoration: none; color: #000;"><?php echo __('NAV_HOME'); ?></a>
                <a href="cart.php" class="cart-icon">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </div>
        </div>
    </header>

    <!-- Cart Content -->
    <div class="container cart-container">
        <h2 class="section-title"><?php echo $current_region === 'VN' ? 'GIỎ HÀNG CỦA BẠN' : 'YOUR SHOPPING CART'; ?></h2>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart" id="emptyCartBlock">
                <p><?php echo $current_region === 'VN' ? 'Giỏ hàng của bạn đang trống.' : 'Your cart is currently empty.'; ?></p>
                <br>
                <a href="products.php" class="btn btn-primary"><?php echo $current_region === 'VN' ? 'TIẾP TỤC MUA SẮM' : 'CONTINUE SHOPPING'; ?></a>
            </div>
        <?php else: ?>
            <div id="cartContent">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th><?php echo $current_region === 'VN' ? 'Sản phẩm' : 'Product'; ?></th>
                            <th><?php echo $current_region === 'VN' ? 'Giá' : 'Price'; ?></th>
                            <th><?php echo $current_region === 'VN' ? 'Số lượng' : 'Quantity'; ?></th>
                            <th><?php echo $current_region === 'VN' ? 'Tổng' : 'Subtotal'; ?></th>
                            <th><?php echo $current_region === 'VN' ? 'Thao tác' : 'Action'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                        <tr id="cart-row-<?php echo $item['id']; ?>">
                            <td style="display: flex; align-items: center; gap: 15px;">
                                <a href="product_detail.php?id=<?php echo $item['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="cart-item-img" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </a>
                                <div>
                                    <a href="product_detail.php?id=<?php echo $item['id']; ?>" style="font-weight: 600; color: var(--text-color-dark);">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                </div>
                            </td>
                            <td><?php echo format_price($item['price']); ?></td>
                            <td>
                                <div class="quantity-control">
                                    <button class="quantity-btn" onclick="updateQty(<?php echo $item['id']; ?>, 'decrease')">-</button>
                                    <input type="number" class="quantity-input" id="qty-input-<?php echo $item['id']; ?>" value="<?php echo $item['quantity']; ?>" min="1" onchange="updateQty(<?php echo $item['id']; ?>, 'set', this.value)">
                                    <button class="quantity-btn" onclick="updateQty(<?php echo $item['id']; ?>, 'increase')">+</button>
                                </div>
                            </td>
                            <td><span id="subtotal-<?php echo $item['id']; ?>"><?php echo format_price($item['subtotal']); ?></span></td>
                            <td>
                                <button class="remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)"><i class="fas fa-trash"></i> <?php echo $current_region === 'VN' ? 'Xóa' : 'Remove'; ?></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

<?php
$discount_usd = 0;
$coupon_code = '';
if (isset($_SESSION['coupon'])) {
    $coupon_code = $_SESSION['coupon']['code'];
    if ($_SESSION['coupon']['discount_type'] === 'percent') {
        $discount_usd = ($total_price * $_SESSION['coupon']['discount_value']) / 100.0;
    } else {
        $discount_usd = min($total_price, $_SESSION['coupon']['discount_value']);
    }
}
$final_total_price = max(0, $total_price - $discount_usd);
?>

                <div class="cart-summary">
                    <h3><?php echo $current_region === 'VN' ? 'TỔNG ĐƠN HÀNG' : 'ORDER SUMMARY'; ?></h3>
                    
                    <!-- Coupon Code Input Box -->
                    <div style="margin-bottom: 20px; background: #fff; padding: 15px; border-radius: 6px; border: 1px solid #ddd;">
                        <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; color: #333;">
                            <i class="fas fa-ticket-alt" style="color: var(--primary-color);"></i> <?php echo $current_region === 'VN' ? 'Mã Giảm Giá / Voucher' : 'Promo Code / Coupon'; ?>
                        </label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="couponInput" placeholder="VD: WELCOME15" value="<?php echo htmlspecialchars($coupon_code); ?>" style="flex: 1; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-weight: 700; text-transform: uppercase;">
                            <button type="button" onclick="handleApplyCoupon()" class="btn btn-primary" style="padding: 8px 15px; font-size: 13px;"><?php echo $current_region === 'VN' ? 'ÁP DỤNG' : 'APPLY'; ?></button>
                        </div>
                        <div id="couponMsg" style="font-size: 12px; margin-top: 6px; font-weight: 600;"></div>
                    </div>

                    <div class="summary-line" style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 15px;">
                        <span><?php echo $current_region === 'VN' ? 'Tạm tính:' : 'Subtotal:'; ?></span>
                        <strong><?php echo format_price($total_price); ?></strong>
                    </div>

                    <?php if ($discount_usd > 0): ?>
                    <div class="summary-line" style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 15px; color: #16a34a; font-weight: 700;">
                        <span><?php echo $current_region === 'VN' ? 'Giảm giá (' . $coupon_code . '):' : 'Discount (' . $coupon_code . '):'; ?></span>
                        <span>-<?php echo format_price($discount_usd); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="total" style="border-top: 2px solid #ddd; padding-top: 15px;">
                        <?php echo $current_region === 'VN' ? 'Tổng cộng:' : 'Total:'; ?> 
                        <span id="cart-total"><?php echo format_price($final_total_price); ?></span>
                    </div>

                    <a href="checkout.php" class="btn btn-primary" style="width: 100%; text-align: center; margin-top: 15px;"><?php echo $current_region === 'VN' ? 'TIẾN HÀNH THANH TOÁN' : 'PROCEED TO CHECKOUT'; ?></a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> Cửa Hàng PixelGear. Tất cả các quyền được bảo lưu.</p>
        </div>
    </footer>

    <script>
    async function handleApplyCoupon() {
        const input = document.getElementById('couponInput');
        const msgEl = document.getElementById('couponMsg');
        const code = input ? input.value.trim() : '';

        msgEl.style.color = '#0284c7';
        msgEl.textContent = 'Đang kiểm tra mã...';

        try {
            const res = await fetch('apply_coupon.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: code })
            });
            const data = await res.json();

            if (data.status === 'success') {
                msgEl.style.color = '#16a34a';
                msgEl.textContent = data.message;
                setTimeout(() => location.reload(), 1000);
            } else if (data.status === 'cleared') {
                msgEl.style.color = '#dc2626';
                msgEl.textContent = data.message;
                setTimeout(() => location.reload(), 1000);
            } else {
                msgEl.style.color = '#dc2626';
                msgEl.textContent = data.message;
            }
        } catch (err) {
            msgEl.style.color = '#dc2626';
            msgEl.textContent = 'Lỗi kết nối kiểm tra mã!';
        }
    }

    async function updateQty(id, action, value = null) {
        let payload = { id: id, action: action };
        if (action === 'set') {
            payload.quantity = parseInt(value) || 1;
        }

        try {
            const response = await fetch('update_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();

            if (data.success) {
                // Update badge count
                document.querySelector('.cart-count').textContent = data.cart_count;

                if (data.item_qty <= 0) {
                    const row = document.getElementById('cart-row-' + id);
                    if (row) row.remove();
                } else {
                    const input = document.getElementById('qty-input-' + id);
                    if (input) input.value = data.item_qty;
                    const subtotalEl = document.getElementById('subtotal-' + id);
                    if (subtotalEl) subtotalEl.textContent = data.item_subtotal;
                }

                document.getElementById('cart-total').textContent = data.total_price;

                if (data.cart_count === 0) {
                    location.reload();
                }
            }
        } catch (err) {
            console.error('Update qty error:', err);
        }
    }

    async function removeItem(id) {
        if(confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?')) {
            await updateQty(id, 'set', 0);
        }
    }
    </script>
</body>
</html>
