<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

// Category, Search & Sort
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

$whereClauses = [];
$params = [];

if ($category) {
    $whereClauses[] = 'category = :category';
    $params[':category'] = $category;
}

if ($search !== '') {
    $whereClauses[] = '(name LIKE :search OR description LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

$orderSql = 'ORDER BY id DESC';
if ($sort === 'price_asc') {
    $orderSql = 'ORDER BY price ASC';
} elseif ($sort === 'price_desc') {
    $orderSql = 'ORDER BY price DESC';
}

// Fetch products from database
$stmt = $pdo->prepare("SELECT * FROM products $whereSql $orderSql");
$stmt->execute($params);
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
    <style>
        .search-container {
             display: flex;
             align-items: center;
             background: rgba(255, 255, 255, 0.08);
             border-radius: 999px;
             padding: 3px 16px;
             border: 1px solid rgba(56, 182, 172, 0.3);
             transition: all 0.3s var(--mc-ease);
             backdrop-filter: blur(10px);
         }
         .search-container:hover {
             background: rgba(56, 182, 172, 0.15);
             border-color: var(--mc-primary);
             box-shadow: 0 0 20px rgba(56, 182, 172, 0.3);
         }
         .search-container input {
             background: transparent;
             border: none;
             outline: none;
             color: #fff;
             padding: 8px;
             font-size: 13px;
             width: 200px;
             font-family: var(--font-body);
         }
         .search-container input::placeholder { color: rgba(255, 255, 255, 0.5); }
         .search-container button {
             background: none;
             border: none;
             color: #fff;
             cursor: pointer;
             padding: 6px;
             transition: all 0.3s var(--mc-ease);
             display: flex;
             align-items: center;
         }
         .search-container button:hover {
             color: var(--mc-primary);
             transform: scale(1.25) rotate(5deg);
             text-shadow: 0 0 20px rgba(56, 182, 172, 0.8);
         }
        
        .filter-toolbar {
             display: flex;
             justify-content: space-between;
             align-items: center;
             margin-bottom: 25px;
             flex-wrap: wrap;
             gap: 15px;
         }
        .filter-results-info {
             font-weight: 600;
             font-size: 13px;
             color: #666;
         }
        .filter-results-info strong {
             color: #2e7d32;
         }
        .sort-select {
             padding: 10px 18px;
             border-radius: 999px;
             border: 1px solid rgba(56, 182, 172, 0.4);
             font-family: var(--font-body);
             font-weight: 700;
             color: #fff;
             outline: none;
             cursor: pointer;
             background: linear-gradient(135deg, var(--mc-primary), var(--mc-primary-light));
             box-shadow: 0 4px 15px rgba(56, 182, 172, 0.4);
             transition: all 0.3s var(--mc-ease);
             font-size: 12px;
             letter-spacing: 0.5px;
             text-transform: uppercase;
         }
        .sort-select:hover {
             transform: translateY(-1px);
             box-shadow: 0 6px 20px rgba(56, 182, 172, 0.6);
         }
        .sort-select option {
             background: var(--mc-primary);
             color: #fff;
             font-weight: 700;
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

    <!-- Collection Header Banner -->
    <div class="mc-collection-banner">
        <div class="mc-banner-accent"></div>
        <div class="container">
            <h2 class="mc-banner-title">
                <?php 
                    if ($search !== '') {
                        echo ($current_region === 'VN' ? 'KẾT QUẢ TÌM KIẾM: "' : 'SEARCH RESULTS: "') . htmlspecialchars($search) . '"';
                    } elseif ($category) {
                        $cat_names = [
                            'clothing' => ($current_region === 'VN' ? 'QUẦN ÁO & THỜI TRANG' : 'CLOTHING & APPAREL'),
                            'accessories' => ($current_region === 'VN' ? 'PHỤ KIỆN & GEAR CAO CẤP' : 'PREMIUM ACCESSORIES & GEAR'),
                            'toys' => ($current_region === 'VN' ? 'ĐỒ CHƠI & QUÀ TẶNG SƯU TẦM' : 'TOYS & COLLECTIBLES')
                        ];
                        echo $cat_names[$category] ?? __('COLLECTION_BANNER_TITLE');
                    } else {
                        echo __('COLLECTION_BANNER_TITLE');
                    }
                ?>
            </h2>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="mc-breadcrumb-bar">
        <div class="container">
            <a href="index.php"><?php echo __('BREADCRUMB_HOME'); ?></a> / <span><?php echo __('BREADCRUMB_PRODUCTS'); ?></span>
        </div>
    </div>

    <!-- Products & Sidebar Section -->
    <section id="shop" class="collection">
        <div class="container collection-layout">
            <!-- Left Filter Sidebar -->
            <aside class="filter-sidebar">
                <div class="filter-group">
                    <h3 class="filter-title"><?php echo __('FILTER_TYPE'); ?> <i class="fas fa-chevron-down"></i></h3>
                    <ul class="filter-list">
                        <li><label><input type="checkbox" <?php echo $category === 'clothing' ? 'checked' : ''; ?> onclick="window.location.href='products.php?category=clothing'"> <?php echo $current_region === 'VN' ? 'Áo Thun & Hoodies' : 'T-Shirts & Hoodies'; ?></label></li>
                        <li><label><input type="checkbox" <?php echo $category === 'accessories' ? 'checked' : ''; ?> onclick="window.location.href='products.php?category=accessories'"> <?php echo $current_region === 'VN' ? 'Nón & Phụ Kiện' : 'Hats & Accessories'; ?></label></li>
                        <li><label><input type="checkbox" <?php echo $category === 'toys' ? 'checked' : ''; ?> onclick="window.location.href='products.php?category=toys'"> <?php echo $current_region === 'VN' ? 'Đồ Chơi & Móc Khóa' : 'Toys & Keychains'; ?></label></li>
                        <li><label><input type="checkbox"> <?php echo $current_region === 'VN' ? 'Trang Phục Cosplay & Outfit' : 'Costumes & Outfits'; ?></label></li>
                    </ul>
                </div>

                <div class="filter-group">
                    <h3 class="filter-title"><?php echo __('FILTER_STYLE'); ?> <i class="fas fa-chevron-down"></i></h3>
                    <ul class="filter-list">
                        <li><label><input type="checkbox"> <?php echo $current_region === 'VN' ? 'Trẻ Em (Kids)' : 'Kids'; ?></label></li>
                        <li><label><input type="checkbox"> <?php echo $current_region === 'VN' ? 'Nam (Men)' : 'Men'; ?></label></li>
                        <li><label><input type="checkbox"> <?php echo $current_region === 'VN' ? 'Nữ (Women)' : 'Women'; ?></label></li>
                        <li><label><input type="checkbox" checked> Unisex</label></li>
                    </ul>
                </div>

                <div class="filter-group">
                    <h3 class="filter-title"><?php echo __('FILTER_THEME'); ?> <i class="fas fa-chevron-down"></i></h3>
                    <ul class="filter-list">
                        <li><label><input type="checkbox"> Streetwear & Cyberpunk</label></li>
                        <li><label><input type="checkbox"> Gaming & Esports</label></li>
                        <li><label><input type="checkbox"> Pixel & Retro Gaming</label></li>
                        <li><label><input type="checkbox"> Casual & Everyday</label></li>
                    </ul>
                </div>
            </aside>

            <!-- Main Product Grid -->
            <main class="collection-main">
                <!-- Filter Toolbar -->
                <div class="filter-toolbar">
                    <div class="filter-results-info">
                        <?php echo __('SHOWING'); ?> <strong><?php echo count($products); ?></strong> <?php echo __('PRODUCTS_LABEL'); ?>
                        <?php if ($search || $category): ?>
                            <a href="products.php" style="color: var(--sale-color); margin-left: 10px; font-size: 13px;"><i class="fas fa-times-circle"></i> <?php echo __('CLEAR_FILTER'); ?></a>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="sortSelect" style="font-weight: 600; font-size: 14px; margin-right: 8px;"><?php echo __('SORT_LABEL'); ?></label>
                        <select id="sortSelect" class="sort-select" onchange="changeSort(this.value)">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>><?php echo __('SORT_NEWEST'); ?></option>
                            <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>><?php echo __('SORT_PRICE_ASC'); ?></option>
                            <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>><?php echo __('SORT_PRICE_DESC'); ?></option>
                        </select>
                    </div>
                </div>
                
                 <?php if (empty($products)): ?>
                     <div style="text-align: center; padding: 60px 0; max-width: 600px; margin: 0 auto;">
                         <i class="fas fa-search" style="font-size: 64px; color: #444; margin-bottom: 20px; opacity: 0.5;"></i>
                         <p style="font-size: 18px; color: #666;"><?php echo $current_region === 'VN' ? 'Không tìm thấy sản phẩm nào phù hợp.' : 'No products found matching your search.'; ?></p>
                         <p style="font-size: 14px; color: #999; margin-top: 10px;"><?php echo $current_region === 'VN' ? 'Hãy thử tìm kiếm với từ khóa khác.' : 'Try searching with different keywords.'; ?></p>
                         <br>
                         <a href="products.php" class="btn btn-primary"><?php echo __('NAV_ALL'); ?></a>
                     </div>
                 <?php else: ?>
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
                                 
                                 <div class="product-actions">
                                     <button class="btn-favorite" data-id="<?php echo $product['id']; ?>">
                                         <i class="far fa-heart"></i>
                                     </button>
                                     <button class="btn-quick-view" data-id="<?php echo $product['id']; ?>">
                                         <?php echo __('QUICK_VIEW'); ?>
                                     </button>
                                 </div>
                             </div>
                             <div class="product-info">
                                 <h3 class="product-name">
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
                <?php endif; ?>
            </main>
        </div>
    </section>

    <!-- Quick View Modal Popup -->
    <div id="quickViewModal" class="quick-view-modal">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <div class="modal-header">
                <button class="modal-close" id="closeQuickView">&times;</button>
                <h3 class="modal-title"><?php echo $current_region === 'VN' ? 'XEM NHANH' : 'QUICK VIEW'; ?></h3>
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
                    <h2 id="qvTitle" class="qv-title product-name">MINECRAFT PRODUCT</h2>
                    <div id="qvPrice" class="qv-price">$0.00</div>
                    
                    <div class="qv-option-group">
                        <label><?php echo $current_region === 'VN' ? 'Kích Thước' : 'Size'; ?>:</label>
                        <div class="qv-sizes">
                            <span class="size-btn active">Freesize</span>
                            <span class="size-btn">M</span>
                            <span class="size-btn">L</span>
                            <span class="size-btn">XL</span>
                        </div>
                    </div>

                    <div class="qv-option-group">
                        <label><?php echo $current_region === 'VN' ? 'Số Lượng' : 'Quantity'; ?>:</label>
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
                        <h4><?php echo $current_region === 'VN' ? 'Mô Tả:' : 'Description:'; ?></h4>
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
        <i class="fas fa-check-circle"></i> <?php echo $current_region === 'VN' ? 'Đã thêm sản phẩm vào giỏ hàng!' : 'Product added to cart!'; ?>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
    <script>
        function changeSort(sortValue) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('sort', sortValue);
            window.location.search = urlParams.toString();
        }

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
