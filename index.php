<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

// Fetch featured products for Homepage (limit 8, chỉ lấy sản phẩm đang hiển thị status = 1)
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE (status = 1 OR status IS NULL) ORDER BY id DESC LIMIT 8");
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    $stmt = $pdo->prepare("SELECT * FROM products ORDER BY id DESC LIMIT 8");
    $stmt->execute();
    $products = $stmt->fetchAll();
}

// Cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += $qty;
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
    <title><?php echo __('SITE_TITLE'); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .search-container {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 2px 10px;
            border: 1px solid rgba(255,255,255,0.4);
        }
        .search-container input {
            background: transparent;
            border: none;
            color: white;
            padding: 5px 8px;
            outline: none;
            font-size: 13px;
            width: 140px;
        }
        .search-container input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        .search-container button {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            padding: 4px;
        }
        .search-container button:hover {
            color: #f59e0b;
        }
    </style>
</head>
<body>
    <!-- Announcement Bar -->
    <div class="announcement-bar">
        <div class="announcement-slider">
            <p class="slide active"><i class="fas fa-bullhorn"></i> <?php echo __('ANNOUNCEMENT_1'); ?></p>
            <p class="slide"><i class="fas fa-truck"></i> <?php echo __('ANNOUNCEMENT_2'); ?></p>
            <p class="slide"><i class="fas fa-star"></i> <?php echo __('ANNOUNCEMENT_3'); ?></p>
        </div>
    </div>

    <!-- Header / Navigation -->
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
                    <li><a href="index.php" class="active"><?php echo __('NAV_HOME'); ?></a></li>
                    <li><a href="products.php"><?php echo __('NAV_ALL'); ?></a></li>
                    <li><a href="products.php?category=clothing"><?php echo __('NAV_CLOTHING'); ?></a></li>
                    <li><a href="products.php?category=accessories"><?php echo __('NAV_ACCESSORIES'); ?></a></li>
                    <li><a href="products.php?category=toys"><?php echo __('NAV_TOYS'); ?></a></li>
                </ul>
            </nav>

            <div class="header-icons">
                <!-- Region & Currency Switcher -->
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

    <!-- Hero Section with Auto Slider & Manual Arrows -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <button class="hero-arrow hero-arrow-left" id="heroPrev"><i class="fas fa-chevron-left"></i></button>
        <button class="hero-arrow hero-arrow-right" id="heroNext"><i class="fas fa-chevron-right"></i></button>
        <div class="hero-content">
            <h2><?php echo __('HERO_TITLE'); ?></h2>
            <p><?php echo __('HERO_SUBTITLE'); ?></p>
            <a href="products.php" class="btn btn-primary"><?php echo __('HERO_BTN'); ?></a>
        </div>
    </section>

    <!-- Homepage Featured Products Section -->
    <section id="shop" class="collection" style="padding: 50px 0;">
        <div class="container">
            <h2 class="section-title"><?php echo __('FEATURED_TITLE'); ?></h2>

            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <a href="product_detail.php?id=<?php echo $product['id']; ?>">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </a>
                        
                        <?php if ($product['badge']): ?>
                            <div class="product-badge <?php echo $product['badge'] === 'Giảm giá' ? 'sale' : ''; ?>">
                                <?php echo htmlspecialchars($product['badge']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <button class="btn-quick-view" data-id="<?php echo $product['id']; ?>"><?php echo __('QUICK_VIEW'); ?></button>
                    </div>
                    <div class="product-info">
                        <h3>
                            <a href="product_detail.php?id=<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars(translate_product_name($product['name'])); ?>
                            </a>
                        </h3>
                        <p class="price">
                            <?php if ($product['old_price']): ?>
                                <span class="old-price"><?php echo format_price($product['old_price']); ?></span>
                            <?php endif; ?>
                            <?php echo format_price($product['price']); ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="view-all-container" style="text-align: center; margin-top: 40px;">
                <a href="products.php" class="btn-add-to-cart-green" style="display: inline-block; width: auto; padding: 14px 40px; text-decoration: none;"><?php echo __('VIEW_ALL_BTN'); ?></a>
            </div>
        </div>
    </section>

    <!-- Quick View Modal Popup -->
    <div id="quickViewModal" class="quick-view-modal">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <button class="modal-close" id="closeQuickView">&times;</button>
            <div class="modal-body">
                <div class="modal-left">
                    <div class="modal-thumbnails">
                        <img id="qvThumb1" class="thumb active" src="" alt="Thumbnail">
                    </div>
                    <div class="modal-main-img">
                        <img id="qvMainImg" src="" alt="Product Image">
                    </div>
                </div>
                <div class="modal-right">
                    <h2 id="qvTitle" class="qv-title">MINECRAFT PRODUCT</h2>
                    <div id="qvPrice" class="qv-price">$0.00</div>
                    
                    <div class="qv-option-group">
                        <label>Size / Kích thước:</label>
                        <div class="qv-sizes">
                            <span class="size-btn active">Freesize</span>
                            <span class="size-btn">M</span>
                            <span class="size-btn">L</span>
                            <span class="size-btn">XL</span>
                        </div>
                    </div>

                    <div class="qv-option-group">
                        <label>Số lượng (Quantity):</label>
                        <div class="qv-quantity-picker">
                            <button type="button" id="qvQtyMinus">-</button>
                            <input type="number" id="qvQtyInput" value="1" min="1">
                            <button type="button" id="qvQtyPlus">+</button>
                        </div>
                    </div>

                    <div class="qv-shipping-note">
                        <i class="fas fa-truck"></i> Giao hàng tận nơi toàn quốc từ 1-3 ngày.
                    </div>

                    <button id="qvAddToCartBtn" class="btn-add-to-cart-green"><?php echo __('ADD_TO_CART'); ?></button>

                    <div class="qv-details">
                        <h4>Mô tả sản phẩm:</h4>
                        <p id="qvDescription">Thông tin chi tiết sản phẩm Minecraft chính hãng...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container footer-grid">
            <div class="footer-column">
                <h3><?php echo __('FOOTER_SHOP'); ?></h3>
                <ul>
                    <li><a href="products.php?category=clothing"><?php echo __('NAV_CLOTHING'); ?></a></li>
                    <li><a href="products.php?category=accessories"><?php echo __('NAV_ACCESSORIES'); ?></a></li>
                    <li><a href="products.php?category=toys"><?php echo __('NAV_TOYS'); ?></a></li>
                    <li><a href="products.php"><?php echo __('NAV_ALL'); ?></a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3><?php echo __('FOOTER_SUPPORT'); ?></h3>
                <ul>
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Shipping Policy</a></li>
                    <li><a href="#">Returns & Refunds</a></li>
                    <li><a href="#">Support Contact</a></li>
                </ul>
            </div>
            <div class="footer-column newsletter">
                <h3><?php echo __('FOOTER_NEWSLETTER'); ?></h3>
                <p><?php echo __('ANNOUNCEMENT_1'); ?></p>
                <form class="newsletter-form" onsubmit="handleNewsletterSubmit(event, this); return false;">
                    <input type="email" name="email" placeholder="Email" required>
                    <button type="submit"><?php echo __('FOOTER_SUBSCRIBE'); ?></button>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> PixelGear Store. All rights reserved.</p>
        </div>
    </footer>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i class="fas fa-check-circle"></i> Đã thêm sản phẩm vào giỏ hàng!
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
    <script>
    async function handleNewsletterSubmit(e, form) {
        if (e) e.preventDefault();
        
        const emailInput = form.querySelector('input[type="email"]');
        const email = emailInput ? emailInput.value.trim() : '';
        if (!email) {
            if (window.showCustomNotice) showCustomNotice('Vui lòng nhập địa chỉ Email!', 'warning');
            return false;
        }

        const btn = form.querySelector('button');
        const originalText = btn ? btn.innerText : 'ĐĂNG KÝ';
        if (btn) {
            btn.innerText = 'Đang xử lý...';
            btn.disabled = true;
        }

        try {
            const response = await fetch('subscribe_newsletter.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'email=' + encodeURIComponent(email)
            });
            const data = await response.json();

            if (data.status === 'success') {
                if (window.showCustomNotice) showCustomNotice(data.message, 'success', 6000);
                if (emailInput) emailInput.value = '';
            } else if (data.status === 'already_registered') {
                if (window.showCustomNotice) showCustomNotice(data.message, 'info', 6000);
            } else if (data.status === 'not_registered') {
                if (window.showCustomNotice) showCustomNotice(data.message, 'info', 4000);
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            } else {
                if (window.showCustomNotice) showCustomNotice('Lỗi: ' + data.message, 'error');
            }
        } catch (err) {
            if (window.showCustomNotice) showCustomNotice('Lỗi kết nối kiểm tra email!', 'error');
        } finally {
            if (btn) {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }
        return false;
    }
    </script>
    <?php if (isset($_SESSION['custom_notice'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.showCustomNotice) {
            showCustomNotice(<?php echo json_encode($_SESSION['custom_notice']); ?>, 'success', 7000);
        }
    });
    </script>
    <?php unset($_SESSION['custom_notice']); endif; ?>
    <?php include_once 'ai_assistant.php'; ?>
</body>
</html>
