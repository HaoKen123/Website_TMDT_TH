<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

// Fetch featured products for Homepage (limit 8)
$stmt = $pdo->prepare("SELECT * FROM products ORDER BY id DESC LIMIT 8");
$stmt->execute();
$products = $stmt->fetchAll();

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('SITE_TITLE'); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <canvas id="particles-js"></canvas>
    
    <!-- Announcement Bar -->
    <div class="announcement-bar">
        <div class="announcement-slider">
            <p class="slide active"><i class="fas fa-gift"></i> <?php echo __('ANNOUNCEMENT_1'); ?></p>
            <p class="slide"><i class="fas fa-rocket"></i> <?php echo __('ANNOUNCEMENT_2'); ?></p>
            <p class="slide"><i class="fas fa-trophy"></i> <?php echo __('ANNOUNCEMENT_3'); ?></p>
        </div>
    </div>

    <!-- Header / Navigation -->
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
                    <li><a href="products.php" class="nav-link <?php echo in_array($current_page, ['products', 'all']) ? 'active' : ''; ?>"><?php echo __('NAV_ALL'); ?></a></li>
                    <li><a href="products.php?category=clothing" class="nav-link <?php echo $current_page === 'clothing' ? 'active' : ''; ?>"><?php echo __('NAV_CLOTHING'); ?></a></li>
                    <li><a href="products.php?category=accessories" class="nav-link <?php echo $current_page === 'accessories' ? 'active' : ''; ?>"><?php echo __('NAV_ACCESSORIES'); ?></a></li>
                    <li><a href="products.php?category=toys" class="nav-link <?php echo $current_page === 'toys' ? 'active' : ''; ?>"><?php echo __('NAV_TOYS'); ?></a></li>
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

                <form action="products.php" method="GET" class="search-container">
                    <input type="text" name="search" placeholder="Tìm kiếm sản phẩm...">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" title="<?php echo __('PROFILE'); ?>" class="user-menu"><i class="fas fa-user-circle"></i></a>
                <?php else: ?>
                    <a href="login.php" title="<?php echo __('LOGIN'); ?>" class="auth-btn"><i class="fas fa-user"></i></a>
                <?php endif; ?>
                
                <a href="cart.php" class="cart-icon" title="<?php echo __('CART'); ?>">
                    <i class="fas fa-shopping-bag"></i>
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
            <h2 class="hero-title">MINECRAFT STORE</h2>
            <p class="hero-subtitle">CHOOSE YOUR ADVENTURE</p>
            <div class="hero-buttons">
                <a href="products.php" class="btn btn-primary btn-lg">SHOP NOW</a>
                <a href="products.php?category=toys" class="btn btn-secondary btn-lg">VIEW TOYS</a>
            </div>
        </div>
    </section>

    <!-- Homepage Featured Products Section -->
    <section id="shop" class="collection">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?php echo __('FEATURED_TITLE'); ?></h2>
                <p class="section-subtitle"><?php echo __('FEATURED_SUBTITLE'); ?></p>
            </div>

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
                        
                        <button class="btn-quick-view" data-id="<?php echo $product['id']; ?>">
                            <?php echo __('QUICK_VIEW'); ?>
                        </button>
                    </div>
                    <div class="product-info">
                        <h3>
                            <a href="product_detail.php?id=<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
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
            
            <div class="view-all-container">
                <a href="products.php" class="btn-add-to-cart-green btn-xl">
                    <i class="fas fa-th-large"></i> Xem Tất Cả Sản Phẩm
                </a>
            </div>
        </div>
    </section>

    <!-- Quick View Modal Popup -->
    <div id="quickViewModal" class="quick-view-modal">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <div class="modal-header">
                <button class="modal-close" id="closeQuickView">&times;</button>
                <h3 class="modal-title">Xem Nhanh</h3>
            </div>
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
                        <label>Kích Thước:</label>
                        <div class="qv-sizes">
                            <span class="size-btn active">Freesize</span>
                            <span class="size-btn">M</span>
                            <span class="size-btn">L</span>
                            <span class="size-btn">XL</span>
                        </div>
                    </div>

                    <div class="qv-option-group">
                        <label>Số Lượng:</label>
                        <div class="qv-quantity-picker">
                            <button type="button" id="qvQtyMinus">-</button>
                            <input type="number" id="qvQtyInput" value="1" min="1">
                            <button type="button" id="qvQtyPlus">+</button>
                        </div>
                    </div>

                    <button id="qvAddToCartBtn" class="btn-add-to-cart-green btn-lg">
                        <i class="fas fa-shopping-cart"></i> <span><?php echo __('ADD_TO_CART'); ?></span>
                    </button>

                    <div class="qv-details">
                        <h4>Mô Tả:</h4>
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
                <h3 class="footer-title"><?php echo __('FOOTER_SHOP'); ?></h3>
                <ul class="footer-links">
                    <li><a href="products.php?category=clothing"><?php echo __('NAV_CLOTHING'); ?></a></li>
                    <li><a href="products.php?category=accessories"><?php echo __('NAV_ACCESSORIES'); ?></a></li>
                    <li><a href="products.php?category=toys"><?php echo __('NAV_TOYS'); ?></a></li>
                    <li><a href="products.php"><?php echo __('NAV_ALL'); ?></a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3 class="footer-title"><?php echo __('FOOTER_SUPPORT'); ?></h3>
                <ul class="footer-links">
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Chính Sách Giao Hàng</a></li>
                    <li><a href="#">Hoàn Hàng & Đổi Đổi</a></li>
                    <li><a href="#">Liên Hệ</a></li>
                </ul>
            </div>
            <div class="footer-column newsletter">
                <h3 class="footer-title"><?php echo __('FOOTER_NEWSLETTER'); ?></h3>
                <p class="newsletter-text"><?php echo __('ANNOUNCEMENT_1'); ?></p>
                <form class="newsletter-form" onsubmit="handleNewsletterSubmit(event, this); return false;">
                    <input type="email" name="email" placeholder="Email của bạn" required>
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

    <canvas id="particles-js"></canvas>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script src="script.js"></script>
    <script>
        particlesJS('particles-js', {
            particles: {
                particleCount: { amount: 80, density: { enable: true, valueRadius: 100 } },
                align: 'rect',
                background: { color: { value: 'transparent' }, type: 'solid' },
                fill: { color: { value: ['#22B573', '#5DD9D9', '#F2C14D'] }, gradient: { enabled: true, colorStops: 5 } },
                opacity: { value: 0.6, random: { enable: true, factor: 2 }, anim: { enable: false } },
                size: { value: 6, random: { enable: true, factor: 4 }, anim: { enable: false } },
                line_linked: { enable: true, distance: 180, color: '#22B573', opacity: 0.4, width: 1 },
                move: { enable: true, speed: 1.5, direction: 'bottom', random: { enable: true, factor: 1 }, straight: { enable: false }, connect: { enable: false } },
                rotate: { enable: true, animation: { enable: true, speed: 0.3, sync: true } }
            },
            interactivity: {
                detectOn: 'canvas',
                events: { onhover: { enable: true, mode: 'grab' }, onclick: { enable: true, mode: 'push' }, resize: true },
                modes: { grab: { distance: 150, line_linked: { distance: 250, opacity: 0.5 } }, bubble: { distance: 400, size: 60, speed: 3 } }
            },
            retinaDetect: true
        });

        async function handleNewsletterSubmit(e, form) {
            if (e) e.preventDefault();
            const emailInput = form.querySelector('input[type="email"]');
            const email = emailInput ? emailInput.value.trim() : '';
            if (!email) return false;
            const btn = form.querySelector('button');
            const originalText = btn ? btn.innerText : 'ĐĂNG KÝ';
            if (btn) { btn.innerText = 'Đang kiểm tra...'; btn.disabled = true; }
            try {
                const response = await fetch('subscribe_newsletter.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'email=' + encodeURIComponent(email)
                });
                const data = await response.json();
                if (data.status === 'not_registered') {
                    alert('Email chưa có tài khoản. Đang dắt bạn qua trang Đăng Ký để tạo tài khoản & nhận ngay bộ đôi Voucher 15% + Freeship!');
                    window.location.href = data.redirect;
                } else if (data.status === 'already_registered') {
                    alert('ℹ️ ' + data.message);
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert('Lỗi kết nối kiểm tra email!');
            } finally {
                if (btn) { btn.innerText = originalText; btn.disabled = false; }
            }
            return false;
        }
    </script>
</body>
</html>
