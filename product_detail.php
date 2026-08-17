<?php
session_start();
require_once 'db.php';
require_once 'lang.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: index.php");
    exit;
}

$current_region = get_current_region();

// Xử lý gửi bình luận & đánh giá
$comment_msg = '';
$comment_error = '';

// Thêm bình luận mới (RÀNG BUỘC: BẮT BUỘC ĐÃ ĐĂNG NHẬP & CHỜ DUYỆT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_comment') {
    if (!isset($_SESSION['user_id'])) {
        $comment_error = "Bạn phải Đăng nhập tài khoản trước khi viết bình luận!";
    } else {
        $user_id = $_SESSION['user_id'];
        $user_name = trim($_POST['user_name'] ?? '');
        $rating = intval($_POST['rating'] ?? 5);
        $comment_text = trim($_POST['comment'] ?? '');

        if (empty($user_name)) {
            $user_name = $_SESSION['user_name'] ?? 'Thành viên';
        }

        // Kiểm tra xem tài khoản người dùng có bị Khóa (status = 0) không
        try {
            $stmtUser = $pdo->prepare("SELECT status FROM users WHERE id = ?");
            $stmtUser->execute([$user_id]);
            $uData = $stmtUser->fetch();
            if ($uData && intval($uData['status']) === 0) {
                $comment_error = "Tài khoản của bạn đã bị Quản trị viên KHÓA quyền gửi bình luận / đánh giá!";
            } else if (!empty($comment_text)) {
                // CHỜ DUYỆT (pending) - Không hiển thị ngay mà phải qua Admin duyệt
                $stmtAdd = $pdo->prepare("INSERT INTO comments (product_id, user_id, user_name, rating, comment, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $stmtAdd->execute([$id, $user_id, $user_name, $rating, $comment_text]);
                $comment_msg = "Cảm ơn bạn đã gửi đánh giá! Bình luận đang CHỜ QUẢN TRỊ VIÊN DUYỆT trước khi hiển thị công khai.";
            }
        } catch (Exception $e) {
            $comment_error = "Lỗi gửi bình luận: " . $e->getMessage();
        }
    }
}

// Lấy danh sách bình luận đã duyệt
$comments_list = [];
try {
    $stmtComments = $pdo->prepare("SELECT * FROM comments WHERE product_id = ? AND status = 'approved' ORDER BY id DESC");
    $stmtComments->execute([$id]);
    $comments_list = $stmtComments->fetchAll();
} catch (Exception $e) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT DEFAULT NULL,
        user_name VARCHAR(255) NOT NULL,
        rating INT DEFAULT 5,
        comment TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    try {
        $stmtComments = $pdo->prepare("SELECT * FROM comments WHERE product_id = ? AND status = 'approved' ORDER BY id DESC");
        $stmtComments->execute([$id]);
        $comments_list = $stmtComments->fetchAll();
    } catch (Exception $ex) {}
}

$avg_rating = 5.0;
if (count($comments_list) > 0) {
    $sum_rating = array_sum(array_column($comments_list, 'rating'));
    $avg_rating = round($sum_rating / count($comments_list), 1);
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
<html lang="<?php echo strtolower($current_region); ?>">
<head>
    <link rel="icon" type="image/png" href="favicon.png?v=2">
    <link rel="shortcut icon" href="favicon.ico?v=2">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(translate_product_name($product['name'])); ?> | PixelGear Store</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-detail-container {
            max-width: 1200px;
            margin: 30px auto 60px auto;
            padding: 0 20px;
        }
        .breadcrumb {
            margin-bottom: 20px;
            font-size: 14px;
            color: #64748b;
        }
        .breadcrumb a { color: var(--primary-color, #15803d); font-weight: 600; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }

        .product-detail-grid {
            display: grid;
            grid-template-columns: 480px 1fr;
            gap: 40px;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }
        .product-detail-media {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e2e8f0;
        }
        .product-detail-img {
            width: 100%;
            max-height: 420px;
            object-fit: contain;
            border-radius: 6px;
        }
        .product-detail-info {
            display: flex;
            flex-direction: column;
        }
        .detail-badge {
            align-self: flex-start;
            background-color: #f59e0b;
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .detail-badge.sale { background-color: #ef4444; }
        .detail-title {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #0f172a;
            line-height: 1.3;
        }
        .rating-summary {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 15px;
            font-size: 15px;
            color: #fbbf24;
        }
        .detail-price {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary-color, #15803d);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .detail-old-price {
            font-size: 18px;
            color: #94a3b8;
            text-decoration: line-through;
            font-weight: 400;
        }
        .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #f0fdf4;
            color: #166534;
            font-weight: 700;
            font-size: 13px;
            border-radius: 6px;
            margin-bottom: 20px;
            align-self: flex-start;
            border: 1px solid #bbf7d0;
        }
        .detail-description {
            font-size: 15px;
            color: #475569;
            line-height: 1.7;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        .purchase-action {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 25px;
        }
        .quantity-control-lg {
            display: inline-flex;
            align-items: center;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
            background: #fff;
        }
        .quantity-control-lg button {
            width: 42px;
            height: 44px;
            background: #f1f5f9;
            border: none;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            color: #334155;
            transition: background 0.2s;
        }
        .quantity-control-lg button:hover { background: #e2e8f0; }
        .quantity-control-lg input {
            width: 55px;
            height: 44px;
            border: none;
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            outline: none;
            color: #0f172a;
        }
        .btn-add-detail {
            flex: 1;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 700;
            background-color: var(--primary-color, #15803d);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(21,128,61,0.25);
        }
        .btn-add-detail:hover {
            background-color: #166534;
            transform: translateY(-1px);
        }
        .product-meta {
            font-size: 13px;
            color: #64748b;
            background: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .product-meta p { display: flex; align-items: center; gap: 8px; margin: 0; }
        .product-meta p + p { margin-top: 8px; }

        /* Comments Section */
        .comments-section {
            margin-top: 40px;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .comment-item {
            padding: 16px 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        .comment-item:last-child { border-bottom: none; }
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }
        .comment-user { font-weight: 700; color: #1e293b; font-size: 14px; }
        .comment-date { font-size: 12px; color: #94a3b8; }
        .star-rating-select { display: flex; gap: 5px; margin: 10px 0; cursor: pointer; color: #cbd5e1; font-size: 22px; }
        .star-rating-select i.active { color: #fbbf24; }

        .related-section {
            margin-top: 50px;
        }
        @media (max-width: 860px) {
            .product-detail-grid { grid-template-columns: 1fr; gap: 25px; padding: 20px; }
        }
    </style>
</head>
<body>
    <!-- Announcement Bar -->
    <div class="announcement-bar">
        <div class="announcement-slider">
            <p class="slide active"><i class="fas fa-truck"></i> <?php echo __('ANNOUNCEMENT_2'); ?></p>
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
                    <li><a href="index.php"><?php echo __('NAV_HOME'); ?></a></li>
                    <li><a href="products.php"><?php echo __('NAV_ALL'); ?></a></li>
                    <li><a href="products.php?category=clothing" class="<?php echo $product['category']==='clothing'?'active':''; ?>"><?php echo __('NAV_CLOTHING'); ?></a></li>
                    <li><a href="products.php?category=accessories" class="<?php echo $product['category']==='accessories'?'active':''; ?>"><?php echo __('NAV_ACCESSORIES'); ?></a></li>
                    <li><a href="products.php?category=toys" class="<?php echo $product['category']==='toys'?'active':''; ?>"><?php echo __('NAV_TOYS'); ?></a></li>
                    <li><a href="products.php?category=decor" class="<?php echo $product['category']==='decor'?'active':''; ?>"><?php echo __('NAV_DECOR'); ?></a></li>
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
                        <a href="?id=<?php echo $id; ?>&region=VN" class="region-option <?php echo $current_region === 'VN' ? 'active' : ''; ?>">
                            <span class="flag-tag">VN</span> <span>Việt Nam (VNĐ - ₫)</span>
                        </a>
                        <a href="?id=<?php echo $id; ?>&region=US" class="region-option <?php echo $current_region === 'US' ? 'active' : ''; ?>">
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

    <div class="product-detail-container">
        <div class="breadcrumb">
            <a href="index.php"><?php echo __('NAV_HOME'); ?></a> / 
            <a href="products.php?category=<?php echo $product['category']; ?>">
                <?php 
                    $cats = ['clothing' => 'Quần Áo', 'accessories' => 'Phụ Kiện', 'toys' => 'Đồ Chơi & Game'];
                    echo $cats[$product['category']] ?? $product['category'];
                ?>
            </a> / 
            <span><?php echo htmlspecialchars(translate_product_name($product['name'])); ?></span>
        </div>

        <div class="product-detail-grid">
            <div class="product-detail-media">
                <?php 
                    $pImg = $product['image_url'];
                    $fallbackSvg = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='300' height='300' viewBox='0 0 300 300' fill='%23e2e8f0'><rect width='300' height='300' rx='8'/><text x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-size='18' fill='%2364748b'>No Image</text></svg>";
                ?>
                <img src="<?php echo htmlspecialchars($pImg); ?>" alt="<?php echo htmlspecialchars(translate_product_name($product['name'])); ?>" class="product-detail-img" onerror="this.onerror=null; this.src='<?php echo $fallbackSvg; ?>'">
            </div>

            <div class="product-detail-info">
                <?php if (!empty($product['badge'])): ?>
                    <span class="detail-badge <?php echo $product['badge'] === 'Giảm giá' ? 'sale' : ''; ?>">
                        <?php echo htmlspecialchars($product['badge']); ?>
                    </span>
                <?php endif; ?>

                <h1 class="detail-title"><?php echo htmlspecialchars(translate_product_name($product['name'])); ?></h1>

                <div class="rating-summary">
                    <?php 
                    for($i = 1; $i <= 5; $i++) {
                        echo $i <= round($avg_rating) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                    }
                    ?>
                    <span style="color: #475569; font-weight: 600; margin-left: 5px;"><?php echo $avg_rating; ?>/5 (<?php echo count($comments_list); ?> đánh giá)</span>
                </div>

                <div class="detail-price">
                    <?php if (!empty($product['old_price'])): ?>
                        <span class="detail-old-price"><?php echo format_price($product['old_price']); ?></span>
                    <?php endif; ?>
                    <?php echo format_price($product['price']); ?>
                </div>

                <div class="stock-badge">
                    <i class="fas fa-boxes"></i> Tồn kho sẵn có: <strong><?php echo intval($product['stock'] ?? 50); ?></strong> sản phẩm
                </div>

                <div class="detail-description">
                    <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'Sản phẩm cao cấp chính hãng PixelGear, thiết kế độc quyền chất lượng cao.')); ?></p>
                </div>

                <div class="purchase-action">
                    <div class="quantity-control-lg">
                        <button type="button" onclick="adjustDetailQty(-1)">-</button>
                        <input type="number" id="detailQty" value="1" min="1" max="<?php echo intval($product['stock'] ?? 50); ?>">
                        <button type="button" onclick="adjustDetailQty(1)">+</button>
                    </div>
                    <button type="button" class="btn-add-detail" onclick="addToCartDetailed(<?php echo $product['id']; ?>)">
                        <i class="fas fa-shopping-cart"></i> THÊM VÀO GIỎ HÀNG
                    </button>
                </div>

                <div class="product-meta">
                    <p><i class="fas fa-shield-alt" style="color: #15803d;"></i> Cam kết hàng chính hãng 100% Minecraft</p>
                    <p><i class="fas fa-shipping-fast" style="color: #15803d;"></i> Giao hàng toàn quốc từ 2-4 ngày làm việc</p>
                </div>
            </div>
        </div>

        <!-- Khối Bình Luận & Đánh Giá -->
        <div class="comments-section">
            <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 20px; border-bottom: 2px solid #15803d; padding-bottom: 8px;">
                <i class="fas fa-comments" style="color: #15803d; margin-right: 6px;"></i> BÌNH LUẬN & ĐÁNH GIÁ SẢN PHẨM (<?php echo count($comments_list); ?>)
            </h2>

            <?php if (!empty($comment_msg)): ?>
                <div style="background: #dcfce7; color: #166534; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 600;">
                    <i class="fas fa-check-circle"></i> <?php echo $comment_msg; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($comment_error)): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 600;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $comment_error; ?>
                </div>
            <?php endif; ?>

            <!-- Form gửi bình luận (CHỈ HIỂN THỊ KHI ĐÃ ĐĂNG NHẬP) -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <form method="POST" style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
                <input type="hidden" name="action" value="submit_comment">
                <input type="hidden" name="rating" id="selectedRating" value="5">

                <div style="margin-bottom: 12px;">
                    <input type="text" name="user_name" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" placeholder="Họ và tên của bạn" required readonly style="width: 100%; max-width: 320px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; background: #e2e8f0; color: #475569; font-weight: 600; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-weight: 600; font-size: 13px; color: #475569;">Đánh giá của bạn:</label>
                    <div class="star-rating-select" id="starRatingSelect">
                        <i class="fas fa-star active" data-val="1"></i>
                        <i class="fas fa-star active" data-val="2"></i>
                        <i class="fas fa-star active" data-val="3"></i>
                        <i class="fas fa-star active" data-val="4"></i>
                        <i class="fas fa-star active" data-val="5"></i>
                    </div>
                </div>

                <textarea name="comment" rows="3" placeholder="Nhập nhận xét chi tiết về sản phẩm..." required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter'; box-sizing: border-box; outline: none; margin-bottom: 15px;"></textarea>

                <button type="submit" style="background: #15803d; color: #fff; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 700; cursor: pointer;">GỬI ĐÁNH GIÁ</button>
            </form>
            <?php else: ?>
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 18px; border-radius: 8px; text-align: center; font-size: 14px; margin-bottom: 30px;">
                <i class="fas fa-lock" style="font-size: 18px; color: #2563eb; margin-right: 6px;"></i>
                Vui lòng <a href="login.php" style="color: #15803d; font-weight: 700; text-decoration: underline;">Đăng nhập</a> hoặc <a href="register.php" style="color: #15803d; font-weight: 700; text-decoration: underline;">Đăng ký</a> để viết bình luận & đánh giá sản phẩm.
            </div>
            <?php endif; ?>

            <!-- Danh sách bình luận đã duyệt -->
            <div class="comments-list">
                <?php if (empty($comments_list)): ?>
                    <p style="color: #94a3b8; font-style: italic; text-align: center; padding: 20px 0;">Chưa có bình luận nào. Hãy là người đầu tiên đánh giá sản phẩm này!</p>
                <?php else: ?>
                    <?php foreach ($comments_list as $c): ?>
                    <div class="comment-item">
                        <div class="comment-header">
                            <span class="comment-user"><i class="fas fa-user-circle" style="color: #15803d; margin-right: 5px;"></i> <?php echo htmlspecialchars($c['user_name']); ?></span>
                            <span class="comment-date"><?php echo date("d/m/Y H:i", strtotime($c['created_at'])); ?></span>
                        </div>
                        <div style="color: #fbbf24; font-size: 12px; margin-bottom: 6px;">
                            <?php for($k = 1; $k <= 5; $k++) { echo $k <= $c['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; } ?>
                        </div>
                        <p style="color: #475569; font-size: 14px; line-height: 1.5; margin: 0;"><?php echo nl2br(htmlspecialchars($c['comment'])); ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Khối Sản Phẩm Tương Tự (DUY NHẤT 1 KHỐI) -->
        <?php if (!empty($related_products)): ?>
        <div class="related-section">
            <h2 class="section-title" style="text-align: left; font-size: 22px; margin-bottom: 20px; border-bottom: 2px solid #15803d; padding-bottom: 8px;">
                <i class="fas fa-cubes" style="color: #15803d; margin-right: 6px;"></i> SẢN PHẨM TƯƠNG TỰ
            </h2>
            <div class="product-grid">
                <?php foreach ($related_products as $rel): ?>
                <div class="product-card">
                    <div class="product-image">
                        <a href="product_detail.php?id=<?php echo $rel['id']; ?>">
                            <img src="<?php echo htmlspecialchars($rel['image_url']); ?>" alt="<?php echo htmlspecialchars(translate_product_name($rel['name'])); ?>" onerror="this.onerror=null; this.src='<?php echo $fallbackSvg; ?>'">
                        </a>
                    </div>
                    <div class="product-info">
                        <h3><a href="product_detail.php?id=<?php echo $rel['id']; ?>"><?php echo htmlspecialchars(translate_product_name($rel['name'])); ?></a></h3>
                        <p class="price"><?php echo format_price($rel['price']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
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
                    <li><a href="#"><?php echo __('FOOTER_SHIPPING_POLICY'); ?></a></li>
                    <li><a href="#"><?php echo __('FOOTER_RETURN_POLICY'); ?></a></li>
                    <li><a href="#"><?php echo __('FOOTER_PRIVACY_POLICY'); ?></a></li>
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

    <script src="script.js"></script>
    <script>
    // Star rating selector script
    document.querySelectorAll('#starRatingSelect i').forEach(star => {
        star.addEventListener('click', function() {
            const val = parseInt(this.getAttribute('data-val'));
            document.getElementById('selectedRating').value = val;
            document.querySelectorAll('#starRatingSelect i').forEach((s, idx) => {
                if (idx < val) s.classList.add('active');
                else s.classList.remove('active');
            });
        });
    });

    function adjustDetailQty(delta) {
        const input = document.getElementById('detailQty');
        let current = parseInt(input.value) || 1;
        const maxStock = parseInt(input.getAttribute('max')) || 50;
        current += delta;
        if (current < 1) current = 1;
        if (current > maxStock) {
            alert('Số lượng không thể vượt quá tồn kho (' + maxStock + ' sản phẩm)!');
            current = maxStock;
        }
        input.value = current;
    }

    async function addToCartDetailed(productId) {
        const qtyInput = document.getElementById('detailQty');
        const qty = qtyInput ? (parseInt(qtyInput.value) || 1) : 1;
        const btn = document.querySelector('.btn-add-detail');
        const origHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ĐANG THÊM...';
            btn.disabled = true;
        }

        try {
            const response = await fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + productId + '&quantity=' + qty
            });
            const data = await response.json();
            if (data.status === 'success' || data.success) {
                document.querySelectorAll('.cart-count').forEach(el => {
                    el.textContent = data.cart_count;
                });
                const toast = document.getElementById('toast');
                if (toast) {
                    toast.classList.add('show');
                    setTimeout(() => toast.classList.remove('show'), 3000);
                }
                if (window.showCustomNotice) {
                    showCustomNotice(data.message || 'Đã thêm sản phẩm vào giỏ hàng!', 'success');
                }
            } else {
                if (window.showCustomNotice) {
                    showCustomNotice(data.message || 'Không thể thêm vào giỏ hàng!', 'error');
                } else {
                    alert(data.message);
                }
            }
        } catch (err) {
            console.error(err);
            if (window.showCustomNotice) {
                showCustomNotice('Lỗi kết nối tới máy chủ!', 'error');
            }
        } finally {
            if (btn) {
                btn.innerHTML = origHtml;
                btn.disabled = false;
            }
        }
    }
    </script>
    <?php include_once 'ai_assistant.php'; ?>
</body>
</html>
