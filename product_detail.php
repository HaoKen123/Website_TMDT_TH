<?php
session_start();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: index.php");
    exit;
}

// Fetch related products (same category)
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? AND id != ? LIMIT 4");
$stmt->execute([$product['category'], $id]);
$related_products = $stmt->fetchAll();

// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += $qty;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> | PixelGear</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-detail-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .breadcrumb {
            margin-bottom: 25px;
            font-size: 14px;
            color: #666;
        }
        .breadcrumb a { color: var(--primary-color); font-weight: 600; }
        .product-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .product-detail-img {
            width: 100%;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid var(--border-color);
            max-height: 500px;
        }
        .product-detail-info {
            display: flex;
            flex-direction: column;
        }
        .detail-badge {
            align-self: flex-start;
            background-color: #ff9800;
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .detail-badge.sale { background-color: var(--sale-color); }
        .detail-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--text-color-dark);
        }
        .detail-price {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .detail-old-price {
            font-size: 20px;
            color: #999;
            text-decoration: line-through;
            font-weight: 400;
        }
        .detail-description {
            font-size: 16px;
            color: #555;
            line-height: 1.8;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .purchase-action {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 30px;
        }
        .quantity-control-lg {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            overflow: hidden;
        }
        .quantity-control-lg button {
            width: 40px;
            height: 44px;
            background: #f5f5f5;
            border: none;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
        }
        .quantity-control-lg input {
            width: 60px;
            height: 44px;
            border: none;
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            outline: none;
        }
        .btn-add-detail {
            flex: 1;
            padding: 14px 25px;
            font-size: 16px;
            font-weight: 700;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-add-detail:hover {
            background-color: var(--primary-hover);
        }
        .product-meta {
            font-size: 14px;
            color: #777;
        }
        .related-section {
            margin-top: 60px;
        }
        @media (max-width: 768px) {
            .product-detail-grid { grid-template-columns: 1fr; gap: 30px; }
        }
    </style>
</head>
<body>
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

    <div class="product-detail-container">
        <div class="breadcrumb">
            <a href="index.php">Trang chủ</a> / 
            <a href="index.php?category=<?php echo $product['category']; ?>">
                <?php 
                    $cats = ['clothing' => 'Quần Áo', 'accessories' => 'Phụ Kiện', 'toys' => 'Đồ Chơi & Game'];
                    echo $cats[$product['category']] ?? $product['category'];
                ?>
            </a> / 
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <div class="product-detail-grid">
            <div class="product-detail-media">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-detail-img">
            </div>

            <div class="product-detail-info">
                <?php if ($product['badge']): ?>
                    <span class="detail-badge <?php echo $product['badge'] === 'Giảm giá' ? 'sale' : ''; ?>">
                        <?php echo htmlspecialchars($product['badge']); ?>
                    </span>
                <?php endif; ?>

                <h1 class="detail-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                <div class="detail-price">
                    <?php if ($product['old_price']): ?>
                        <span class="detail-old-price">$<?php echo htmlspecialchars($product['old_price']); ?></span>
                    <?php endif; ?>
                    $<?php echo htmlspecialchars($product['price']); ?>
                </div>

                <div class="detail-description">
                    <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'Sản phẩm cao cấp chính hãng PixelGear, thiết kế độc quyền chất lượng cao.')); ?></p>
                </div>

                <div class="purchase-action">
                    <div class="quantity-control-lg">
                        <button onclick="adjustDetailQty(-1)">-</button>
                        <input type="number" id="detailQty" value="1" min="1">
                        <button onclick="adjustDetailQty(1)">+</button>
                    </div>
                    <button class="btn-add-detail" onclick="addToCartDetailed(<?php echo $product['id']; ?>)">
                        <i class="fas fa-shopping-cart" style="margin-right: 8px;"></i> THÊM VÀO GIỎ HÀNG
                    </button>
                </div>

                <div class="product-meta">
                    <p><i class="fas fa-shield-alt" style="color: var(--primary-color);"></i> Cam kết hàng chính hãng 100% Minecraft</p>
                    <p style="margin-top: 8px;"><i class="fas fa-shipping-fast" style="color: var(--primary-color);"></i> Giao hàng toàn quốc từ 2-4 ngày</p>
                </div>
            </div>
        </div>

        <?php if (!empty($related_products)): ?>
        <div class="related-section">
            <h2 class="section-title" style="text-align: left; font-size: 24px; margin-bottom: 25px;">SẢN PHẨM TƯƠNG TỰ</h2>
            <div class="product-grid">
                <?php foreach ($related_products as $rel): ?>
                <div class="product-card">
                    <div class="product-image">
                        <a href="product_detail.php?id=<?php echo $rel['id']; ?>">
                            <img src="<?php echo htmlspecialchars($rel['image_url']); ?>" alt="<?php echo htmlspecialchars($rel['name']); ?>">
                        </a>
                    </div>
                    <div class="product-info">
                        <h3><a href="product_detail.php?id=<?php echo $rel['id']; ?>"><?php echo htmlspecialchars($rel['name']); ?></a></h3>
                        <p class="price">$<?php echo htmlspecialchars($rel['price']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="site-footer" style="margin-top: 80px;">
        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> Cửa Hàng PixelGear. Tất cả các quyền được bảo lưu.</p>
        </div>
    </footer>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i class="fas fa-check-circle"></i> Đã thêm sản phẩm vào giỏ hàng!
    </div>

    <script src="script.js"></script>
    <script>
    function adjustDetailQty(delta) {
        const input = document.getElementById('detailQty');
        let current = parseInt(input.value) || 1;
        current += delta;
        if (current < 1) current = 1;
        input.value = current;
    }

    async function addToCartDetailed(productId) {
        const qty = parseInt(document.getElementById('detailQty').value) || 1;
        try {
            const response = await fetch('update_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: productId, action: 'set', quantity: qty })
            });
            const data = await response.json();
            if (data.success) {
                document.querySelector('.cart-count').textContent = data.cart_count;
                const toast = document.getElementById('toast');
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 3000);
            }
        } catch (err) {
            console.error(err);
        }
    }
    </script>
</body>
</html>
