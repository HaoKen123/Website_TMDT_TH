<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

// Category, Search & Sort
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

$whereClauses = ['(status = 1 OR status IS NULL)'];
$params = [];

if ($category) {
    if ($category === 'clothing') {
        $whereClauses[] = "(category IN ('clothing', 'tshirts', 'cosplay'))";
    } else if ($category === 'accessories') {
        $whereClauses[] = "(category IN ('accessories', 'hats', 'keychains'))";
    } else if ($category === 'toys') {
        $whereClauses[] = "(category IN ('toys', 'toys_models', 'decor'))";
    } else {
        $whereClauses[] = 'category = :category';
        $params[':category'] = $category;
    }
}

if ($search !== '') {
    $whereClauses[] = '(name LIKE :search1 OR description LIKE :search2)';
    $params[':search1'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
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
try {
    $stmt = $pdo->prepare("SELECT * FROM products $whereSql $orderSql");
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    // Fallback if status column does not exist
    $stmt = $pdo->prepare("SELECT * FROM products $orderSql");
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
            outline: none;
            color: #fff;
            padding: 6px;
            font-size: 13px;
            width: 140px;
        }
        .search-container input::placeholder { color: #e0f2e9; }
        .search-container button {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            padding: 4px;
        }
        
        .filter-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
            background: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .filter-results-info {
            font-weight: 600;
            color: #555;
        }
        .sort-select {
            padding: 8px 15px;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            font-family: 'Inter';
            font-weight: 600;
            color: var(--text-color-dark);
            outline: none;
            cursor: pointer;
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
                <a href="index.php" class="logo-link">
                    <span class="glitch-title" data-text="PIXELGEAR">PIXELGEAR</span>
                </a>
            </div>

            <nav class="main-nav">
                <ul>
                    <li><a href="index.php"><?php echo __('NAV_HOME'); ?></a></li>
                    <li><a href="products.php" class="<?php echo empty($category) ? 'active' : ''; ?>"><?php echo __('NAV_ALL'); ?></a></li>
                    <li><a href="products.php?category=clothing" class="<?php echo $category === 'clothing' ? 'active' : ''; ?>"><?php echo __('NAV_CLOTHING'); ?></a></li>
                    <li><a href="products.php?category=accessories" class="<?php echo $category === 'accessories' ? 'active' : ''; ?>"><?php echo __('NAV_ACCESSORIES'); ?></a></li>
                    <li><a href="products.php?category=toys" class="<?php echo $category === 'toys' ? 'active' : ''; ?>"><?php echo __('NAV_TOYS'); ?></a></li>
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
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['region' => 'VN'])); ?>" class="region-option <?php echo $current_region === 'VN' ? 'active' : ''; ?>">
                            <span class="flag-tag">VN</span> <span>Việt Nam (VNĐ - ₫)</span>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['region' => 'US'])); ?>" class="region-option <?php echo $current_region === 'US' ? 'active' : ''; ?>">
                            <span class="flag-tag">US</span> <span>United States (USD - $)</span>
                        </a>
                    </div>
                </div>

                <form action="products.php" method="GET" class="search-container">
                    <?php if ($category): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                    <?php endif; ?>
                    <input type="text" name="search" placeholder="<?php echo __('SEARCH_PLACEHOLDER'); ?>" value="<?php echo htmlspecialchars($search); ?>">
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
            <?php
            $categories_list = [];
            try {
                $categories_list = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY id ASC")->fetchAll();
            } catch (Exception $e) {}

            if (empty($categories_list)) {
                $categories_list = [
                    ['slug' => 'clothing', 'name' => 'Quần áo & Hoodies'],
                    ['slug' => 'accessories', 'name' => 'Phụ kiện Minecraft'],
                    ['slug' => 'toys', 'name' => 'Đồ chơi & Gấu bông'],
                    ['slug' => 'decor', 'name' => 'Đèn & Trang trí']
                ];
            }
            ?>
            <aside class="filter-sidebar">
                <div class="filter-group">
                    <h3 class="filter-title"><?php echo __('FILTER_TYPE'); ?> <i class="fas fa-chevron-down"></i></h3>
                    <ul class="filter-list">
                        <li><label><input type="checkbox" <?php echo empty($category) ? 'checked' : ''; ?> onclick="window.location.href='products.php'"> <strong><?php echo $current_region === 'VN' ? 'Tất cả sản phẩm' : 'All Products'; ?></strong></label></li>
                        <?php foreach ($categories_list as $cat_item): ?>
                        <li>
                            <label>
                                <input type="checkbox" <?php echo $category === $cat_item['slug'] ? 'checked' : ''; ?> onclick="window.location.href='products.php?category=<?php echo urlencode($cat_item['slug']); ?>'"> 
                                <?php echo htmlspecialchars($cat_item['name']); ?>
                            </label>
                        </li>
                        <?php endforeach; ?>
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
                    <div style="text-align: center; padding: 50px 0; background: #fff; border-radius: 8px;">
                        <p style="font-size: 18px; color: #666;"><?php echo $current_region === 'VN' ? 'Không tìm thấy sản phẩm nào phù hợp.' : 'No products found matching your search.'; ?></p>
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
                                
                                <button class="btn-quick-view" data-id="<?php echo $product['id']; ?>"><?php echo __('QUICK_VIEW'); ?></button>
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
                <?php endif; ?>
            </main>
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
                        <i class="fas fa-truck"></i> <?php echo $current_region === 'VN' ? 'Giao hàng tận nơi toàn quốc từ 1-3 ngày.' : 'Fast US Shipping in 1-3 business days.'; ?>
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
        if (btn) {
            btn.innerText = 'Đang kiểm tra...';
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
            if (btn) {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }
        return false;
    }
    </script>
    <?php include_once 'ai_assistant.php'; ?>
</body>
</html>
