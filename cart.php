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
    <link rel="icon" type="image/png" href="favicon.png?v=2">
    <link rel="shortcut icon" href="favicon.ico?v=2">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng | PixelGear Shop</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
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
        <div class="header-container">
            <div class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </div>
            
            <div class="logo">
                <a href="index.php" class="mc-logo">
                    <span class="mc-logo__icon" aria-hidden="true"></span>
                    <span class="mc-logo__text" data-text="PIXELGEAR">PIXELGEAR</span>
                </a>
            </div>

            <nav class="main-nav">
                <ul>
                    <li><a href="index.php"><?php echo __('NAV_HOME'); ?></a></li>
                    <li><a href="products.php"><?php echo __('NAV_ALL'); ?></a></li>
                    <li><a href="products.php?category=clothing"><?php echo __('NAV_CLOTHING'); ?></a></li>
                    <li><a href="products.php?category=accessories"><?php echo __('NAV_ACCESSORIES'); ?></a></li>
                    <li><a href="products.php?category=toys"><?php echo __('NAV_TOYS'); ?></a></li>
                </ul>
            </nav>

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

                <form action="products.php" method="GET" class="search-container">
                    <input type="text" name="search" placeholder="<?php echo __('SEARCH_PLACEHOLDER'); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" title="<?php echo __('PROFILE'); ?>" style="font-size: 14px; font-weight: 600;"><i class="fas fa-user-circle"></i> <?php echo explode(' ', trim($_SESSION['user_name']))[0]; ?></a>
                <?php else: ?>
                    <a href="login.php" title="<?php echo __('LOGIN'); ?>"><i class="fas fa-user"></i></a>
                <?php endif; ?>

                <a href="cart.php" class="cart-icon" title="<?php echo __('CART'); ?>">
                    <i class="fas fa-shopping-cart"></i>
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
                <form id="cartForm" action="checkout.php" method="POST">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll" checked onclick="toggleAll(this)" style="margin-right:8px; transform:scale(1.2);"> <?php echo $current_region === 'VN' ? 'Sản phẩm' : 'Product'; ?></th>
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
                                <input type="checkbox" class="item-checkbox" name="selected_items[]" value="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>" data-qty="<?php echo $item['quantity']; ?>" checked onchange="calcSelectedTotal()" style="transform:scale(1.2);">
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
                                    <button type="button" class="quantity-btn" onclick="updateQty(<?php echo $item['id']; ?>, 'decrease')">-</button>
                                    <input type="number" class="quantity-input" id="qty-input-<?php echo $item['id']; ?>" value="<?php echo $item['quantity']; ?>" min="1" onchange="updateQty(<?php echo $item['id']; ?>, 'set', this.value)">
                                    <button type="button" class="quantity-btn" onclick="updateQty(<?php echo $item['id']; ?>, 'increase')">+</button>
                                </div>
                            </td>
                            <td><span id="subtotal-<?php echo $item['id']; ?>"><?php echo format_price($item['subtotal']); ?></span></td>
                            <td>
                                <button type="button" class="remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)"><i class="fas fa-trash"></i> <?php echo $current_region === 'VN' ? 'Xóa' : 'Remove'; ?></button>
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

                    <button type="submit" id="btnProceedCheckout" class="btn btn-primary" style="width: 100%; text-align: center; margin-top: 15px; font-size: 16px;"><?php echo $current_region === 'VN' ? 'TIẾN HÀNH THANH TOÁN' : 'PROCEED TO CHECKOUT'; ?></button>
                </div>
                </form>
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
    const currentRegion = '<?php echo $current_region; ?>';
    const exchangeRate = <?php echo defined('EXCHANGE_RATE_VND') ? EXCHANGE_RATE_VND : 25400; ?>;

    const discountType = '<?php echo isset($_SESSION["coupon"]) ? $_SESSION["coupon"]["discount_type"] : ""; ?>';
    const discountValue = parseFloat('<?php echo isset($_SESSION["coupon"]) ? $_SESSION["coupon"]["discount_value"] : "0"; ?>');
    const couponMinOrder = parseFloat('<?php echo isset($_SESSION["coupon"]) ? $_SESSION["coupon"]["min_order"] : "0"; ?>');

    function saveSelectedState() {
        const checkedIds = [];
        document.querySelectorAll('.item-checkbox').forEach(cb => {
            if (cb.checked) checkedIds.push(cb.value);
        });
        sessionStorage.setItem('selected_cart_items', JSON.stringify(checkedIds));
    }

    function restoreSelectedState() {
        const saved = sessionStorage.getItem('selected_cart_items');
        if (saved) {
            try {
                const checkedIds = JSON.parse(saved);
                document.querySelectorAll('.item-checkbox').forEach(cb => {
                    cb.checked = checkedIds.includes(cb.value);
                });
            } catch (e) {}
        }
    }

    function toggleAll(source) {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        for(let i=0; i<checkboxes.length; i++) {
            checkboxes[i].checked = source.checked;
        }
        saveSelectedState();
        calcSelectedTotal();
    }

    function calcSelectedTotal() {
        saveSelectedState();
        let selectedTotal = 0;
        let anyChecked = false;
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(cb => {
            if(cb.checked) {
                anyChecked = true;
                selectedTotal += parseFloat(cb.getAttribute('data-price')) * parseInt(cb.getAttribute('data-qty'));
            }
        });
        
        let discount = 0;
        if (selectedTotal >= couponMinOrder) {
            if (discountType === 'percent') {
                discount = (selectedTotal * discountValue) / 100;
            } else if (discountType === 'fixed') {
                discount = Math.min(selectedTotal, discountValue);
            }
        } else {
            if (discountType !== '') {
                const msgEl = document.getElementById('couponMsg');
                if (msgEl) {
                    msgEl.style.color = '#dc2626';
                    msgEl.textContent = '⚠️ Đơn hàng chưa đạt mức tối thiểu của mã giảm giá.';
                }
            }
        }
        
        const finalTotal = Math.max(0, selectedTotal - discount);
        
        let displayTotal = finalTotal;
        if (currentRegion === 'VN') {
            displayTotal = Math.round(finalTotal * exchangeRate);
            document.getElementById('cart-total').innerHTML = displayTotal.toLocaleString('vi-VN') + ' ₫';
        } else {
            document.getElementById('cart-total').innerHTML = '$' + displayTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        
        const btn = document.getElementById('btnProceedCheckout');
        if(!anyChecked) {
            btn.disabled = true;
            btn.style.opacity = '0.5';
        } else {
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    }
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
            payload.quantity = (value !== null && value !== '' && !isNaN(value)) ? parseInt(value) : 0;
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
                const countBadge = document.querySelector('.cart-count');
                if (countBadge) countBadge.textContent = data.cart_count;

                if (data.item_qty <= 0) {
                    const row = document.getElementById('cart-row-' + id);
                    if (row) row.remove();
                    
                    // Remove from sessionStorage
                    const saved = sessionStorage.getItem('selected_cart_items');
                    if (saved) {
                        try {
                            let checkedIds = JSON.parse(saved);
                            checkedIds = checkedIds.filter(x => String(x) !== String(id));
                            sessionStorage.setItem('selected_cart_items', JSON.stringify(checkedIds));
                        } catch(e) {}
                    }
                    calcSelectedTotal();
                } else {
                    const input = document.getElementById('qty-input-' + id);
                    if (input) input.value = data.item_qty;
                    const subtotalEl = document.getElementById('subtotal-' + id);
                    if (subtotalEl) subtotalEl.textContent = data.item_subtotal;
                    
                    const cb = document.querySelector('.item-checkbox[value="'+id+'"]');
                    if (cb) {
                        cb.setAttribute('data-qty', data.item_qty);
                        calcSelectedTotal();
                    }
                }

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

    document.addEventListener('DOMContentLoaded', () => {
        restoreSelectedState();
        calcSelectedTotal();
    });
    </script>
</body>
</html>
