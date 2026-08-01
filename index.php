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
</head>
<?php require_once 'header.php'; ?>
    <section class="hero">
        <div class="hero-overlay"></div>
        <button class="hero-arrow hero-arrow-left" id="heroPrev"><i class="fas fa-chevron-left"></i></button>
        <button class="hero-arrow hero-arrow-right" id="heroNext"><i class="fas fa-chevron-right"></i></button>
        <div class="hero-content">
            <h2 class="hero-title"><?php echo __('HERO_TITLE'); ?></h2>
            <p class="hero-subtitle"><?php echo __('HERO_SUBTITLE'); ?></p>
            <div class="hero-buttons">
                <a href="products.php" class="btn btn-primary btn-lg"><?php echo __('HERO_BTN'); ?></a>
                <a href="products.php?category=toys" class="btn btn-secondary btn-lg">ĐỒ CHƠI & GAME</a>
            </div>
        </div>
    </section>

    <!-- Best Sellers Section -->
    <section class="best-sellers">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?php echo __('BEST_SELLERS_TITLE'); ?></h2>
                <p class="section-subtitle">Sản phẩm bán chạy nhất tháng này</p>
                <?php if (!empty($best_sellers)): ?>
                <p class="section-subtitle small"><?php echo count($best_sellers); ?> sản phẩm</p>
                <?php endif; ?>
            </div>
                <div class="product-grid">
                <!-- Best sellers will be fetched from DB or hardcoded -->
                <?php 
                if (!empty($pdo)) {
                    $stmt = $pdo->query("
                        SELECT p.*, SUM(oi.quantity) as total_sold
                        FROM products p
                        LEFT JOIN order_items oi ON p.id = oi.product_id
                        LEFT JOIN orders o ON oi.order_id = o.id
                        WHERE o.id IS NOT NULL
                        GROUP BY p.id
                        ORDER BY total_sold DESC
                        LIMIT 6
                    ");
                    $best_sellers = $stmt->fetchAll();
                }
                foreach ($best_sellers as $product): 
                ?>
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
                        
                        <span class="badge-sale">
                            <i class="fas fa-fire"></i> <?php echo number_format($product['total_sold']); ?>
                        </span>
                        
                        <button class="btn-quick-view" data-id="<?php echo $product['id']; ?>">
                            <?php echo __('QUICK_VIEW'); ?>
                        </button>
                        <button class="btn-add-to-cart-heart" data-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-heart"></i>
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
        </div>
    </section>

    <!-- New Arrivals Section -->
    <section class="new-arrivals">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?php echo __('NEW_ARRIVALS_TITLE'); ?></h2>
                <p class="section-subtitle">Sản phẩm mới cập nhật hôm nay</p>
            </div>
            <div class="product-grid">
                <?php 
                if (!empty($pdo)) {
                    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 6");
                    $new_arrivals = $stmt->fetchAll();
                }
                foreach ($new_arrivals as $product): 
                ?>
                <div class="product-card">
                    <div class="product-image">
                        <a href="product_detail.php?id=<?php echo $product['id']; ?>">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </a>
                        
                        <?php if ((isset($product['new']) && $product['new'] == 1) || (isset($product['created_at']) && strtotime($product['created_at']) > time() - 86400)): ?>
                            <div class="product-badge new">
                                NEW
                            </div>
                        <?php endif; ?>
                        
                        <button class="btn-quick-view" data-id="<?php echo $product['id']; ?>">
                            <?php echo __('QUICK_VIEW'); ?>
                        </button>
                        <button class="btn-add-to-cart-heart" data-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                    <div class="product-info">
                        <h3>
                            <a href="product_detail.php?id=<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h3>
                        <p class="price">
                            <?php echo format_price($product['price']); ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Trust Badges Section -->
    <section class="trust-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?php echo __('TRUST_BADGES_TITLE'); ?></h2>
            </div>
            <div class="trust-grid">
                <div class="trust-item">
                    <i class="fas fa-check-circle" style="color: var(--mc-primary); font-size: 40px;"></i>
                    <h3>Hàng Chính Hãng</h3>
                    <p>100% sản phẩm chính hãng, nguồn gốc rõ ràng</p>
                </div>
                <div class="trust-item">
                    <i class="fas fa-shipping-fast" style="color: var(--mc-primary); font-size: 40px;"></i>
                    <h3>Ship Nhanh</h3>
                    <p>Giao hàng 24-48h trên toàn quốc</p>
                </div>
                <div class="trust-item">
                    <i class="fas fa-shield-alt" style="color: var(--mc-primary); font-size: 40px;"></i>
                    <h3>Bảo Hành</h3>
                    <p>12 tháng bảo hành chính thức</p>
                </div>
                <div class="trust-item">
                    <i class="fas fa-headset" style="color: var(--mc-primary); font-size: 40px;"></i>
                    <h3>Hỗ Trợ 24/7</h3>
                    <p>Đóng cửa liên tục hỗ trợ khách hàng</p>
                </div>
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
                        
                        <?php if ((isset($product['new']) && $product['new'] == 1) || (isset($product['created_at']) && strtotime($product['created_at']) > time() - 86400)): ?>
                            <div class="product-badge new">
                                NEW
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
        <!-- Top Section: Main Links -->
        <div class="footer-top">
            <div class="container">
                <div class="footer-grid">
                    <div class="footer-brand">
                        <div class="logo">
                            <h2 class="logo-text"><?php echo $current_region === 'VN' ? 'PIXELGEAR' : 'PIXELGEAR'; ?></h2>
                        </div>
                        <p class="footer-tagline"><?php echo __('FOOTER_TAGLINE_VN'); ?></p>
                        <div class="footer-connect">
                            <p><?php echo __('FOOTER_CONNECT'); ?></p>
                            <div class="social-mini">
                                <a href="#" class="social-btn">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-btn">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" class="social-btn">
                                    <i class="fab fa-tiktok"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="footer-column">
                        <h4 class="footer-heading"><?php echo __('FOOTER_SHOP'); ?></h4>
                        <ul class="footer-menu">
                            <li><a href="products.php?category=clothing"><?php echo __('NAV_CLOTHING'); ?></a></li>
                            <li><a href="products.php?category=accessories"><?php echo __('NAV_ACCESSORIES'); ?></a></li>
                            <li><a href="products.php?category=toys"><?php echo __('NAV_TOYS'); ?></a></li>
                            <li><a href="products.php"><?php echo __('NAV_ALL'); ?></a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4 class="footer-heading"><?php echo __('FOOTER_SUPPORT'); ?></h4>
                        <ul class="footer-menu">
                            <li><a href="#">FAQ</a></li>
                            <li><a href="#">Chính Sách Giao Hàng</a></li>
                            <li><a href="#">Hoàn Hàng & Đổi Hàng</a></li>
                            <li><a href="#">Liên Hệ</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4 class="footer-heading"><?php echo __('FOOTER_CONTACT'); ?></h4>
                        <ul class="footer-contact">
                            <li class="footer-contact-item">
                                <i class="fas fa-phone-alt"></i>
                                <a href="tel:19001234"><?php echo __('FOOTER_PHONE'); ?></a>
                            </li>
                            <li class="footer-contact-item">
                                <i class="fas fa-envelope"></i>
                                <a href="mailto:<?php echo __('FOOTER_EMAIL'); ?>"><?php echo __('FOOTER_EMAIL'); ?></a>
                            </li>
                        </ul>
                        <p class="footer-copyright"><?php echo __('FOOTER_COPYRIGHT'); ?></p>
                    </div>
                </div>
            </div>
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

    </script>
</html>
