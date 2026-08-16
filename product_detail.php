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

// Xử lý gửi bình luận & đánh giá
$comment_msg = '';
$comment_error = '';

// Thêm bình luận mới (RÀNG BUỘC: BẮT BUỘC ĐÃ ĐĂNG NHẬP)
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

        if (!empty($comment_text)) {
            $stmtAdd = $pdo->prepare("INSERT INTO comments (product_id, user_id, user_name, rating, comment, status) VALUES (?, ?, ?, ?, ?, 'approved')");
            $stmtAdd->execute([$id, $user_id, $user_name, $rating, $comment_text]);
            $comment_msg = "Cảm ơn bạn đã gửi đánh giá sản phẩm thành công!";
        }
    }
}

// Lấy danh sách bình luận đã duyệt (Tự động tạo bảng nếu thiếu)
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
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
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
<html lang="vi">
<head>
    <link rel="icon" type="image/png" href="favicon.png?v=2">
    <link rel="shortcut icon" href="favicon.ico?v=2">
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
            margin-bottom: 10px;
            color: var(--text-color-dark);
        }
        .rating-summary {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            font-size: 15px;
            color: #fbbf24;
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
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .stock-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 700;
            font-size: 13px;
            border-radius: 6px;
            margin-bottom: 20px;
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
        /* Comments Section */
        .comments-section {
            margin-top: 50px;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .comment-item {
            padding: 15px 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }
        .comment-user { font-weight: 700; color: #1e293b; font-size: 15px; }
        .comment-date { font-size: 12px; color: #94a3b8; }
        .star-rating-select { display: flex; gap: 5px; margin: 10px 0; cursor: pointer; color: #cbd5e1; font-size: 20px; }
        .star-rating-select i.active { color: #fbbf24; }
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
            <p class="slide active"><i class="fas fa-truck"></i> MIỄN PHÍ VẬN CHUYỂN TOÀN QUỐC CHO ĐƠN HÀNG TỪ 500K!</p>
        </div>
    </div>

    <!-- Header -->
    <header class="site-header">
        <div class="header-container">
            <div class="logo">
                <a href="index.php" class="mc-logo">
                    <span class="mc-logo__icon" aria-hidden="true"></span>
                    <span class="mc-logo__text" data-text="PIXELGEAR">PIXELGEAR</span>
                </a>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php">TẤT CẢ</a></li>
                    <li><a href="index.php?category=clothing">QUẦN ÁO</a></li>
                    <li><a href="index.php?category=accessories">PHỤ KIỆN</a></li>
                    <li><a href="index.php?category=toys">ĐỒ CHƠI & GAME</a></li>
                </ul>
            </nav>
            <div class="header-icons">
                <a href="cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
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
            <span><?php echo htmlspecialchars(translate_product_name($product['name'])); ?></span>
        </div>

        <div class="product-detail-grid">
            <div class="product-detail-media">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars(translate_product_name($product['name'])); ?>" class="product-detail-img">
            </div>

            <div class="product-detail-info">
                <?php if ($product['badge']): ?>
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
                    <?php if ($product['old_price']): ?>
                        <span class="detail-old-price"><?php echo format_price($product['old_price']); ?></span>
                    <?php endif; ?>
                    <?php echo format_price($product['price']); ?>
                </div>

                <div class="stock-badge">
                    <i class="fas fa-boxes" style="margin-right: 5px;"></i> Tồn kho sẵn có: <strong><?php echo intval($product['stock'] ?? 50); ?></strong> sản phẩm
                </div>

                <div class="detail-description">
                    <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'Sản phẩm cao cấp chính hãng PixelGear, thiết kế độc quyền chất lượng cao.')); ?></p>
                </div>

                <div class="purchase-action">
                    <div class="quantity-control-lg">
                        <button onclick="adjustDetailQty(-1)">-</button>
                        <input type="number" id="detailQty" value="1" min="1" max="<?php echo intval($product['stock'] ?? 50); ?>">
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

        <!-- Comments & Ratings Section -->
        <div class="comments-section">
            <h2 style="font-size: 22px; margin-bottom: 20px; color: #1e293b;">BÌNH LUẬN & ĐÁNH GIÁ SẢN PHẨM (<?php echo count($comments_list); ?>)</h2>

            <?php if (!empty($comment_msg)): ?>
                <div style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 600;">
                    <i class="fas fa-check-circle"></i> <?php echo $comment_msg; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($comment_error)): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: 600;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $comment_error; ?>
                </div>
            <?php endif; ?>

            <!-- Form gửi bình luận (CHỈ HIỂN THỊ KHI ĐÃ ĐĂNG NHẬP) -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <form method="POST" style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
                <input type="hidden" name="action" value="submit_comment">
                <input type="hidden" name="rating" id="selectedRating" value="5">

                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <input type="text" name="user_name" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" placeholder="Họ và tên của bạn" required readonly style="flex: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; background: #e2e8f0; color: #475569; font-weight: 600;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="font-weight: 600; font-size: 14px; color: #475569;">Đánh giá của bạn:</label>
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
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 20px; border-radius: 8px; text-align: center; font-size: 15px; margin-bottom: 30px;">
                <i class="fas fa-lock" style="font-size: 20px; color: #2563eb; margin-right: 8px;"></i>
                Vui lòng <a href="login.php" style="color: #15803d; font-weight: 700; text-decoration: underline;">Đăng nhập</a> hoặc <a href="register.php" style="color: #15803d; font-weight: 700; text-decoration: underline;">Đăng ký tài khoản</a> để viết bình luận & đánh giá sản phẩm.
            </div>
            <?php endif; ?>

            <!-- Danh sách bình luận -->
            <div class="comments-list">
                <?php if (empty($comments_list)): ?>
                    <p style="color: #94a3b8; font-style: italic;">Chưa có bình luận nào. Hãy là người đầu tiên đánh giá sản phẩm này!</p>
                <?php else: ?>
                    <?php foreach ($comments_list as $c): ?>
                    <div class="comment-item">
                        <div class="comment-header">
                            <span class="comment-user"><i class="fas fa-user-circle" style="color: #15803d; margin-right: 5px;"></i> <?php echo htmlspecialchars($c['user_name']); ?></span>
                            <span class="comment-date"><?php echo date("d/m/Y H:i", strtotime($c['created_at'])); ?></span>
                        </div>
                        <div style="color: #fbbf24; font-size: 13px; margin-bottom: 6px;">
                            <?php for($k = 1; $k <= 5; $k++) { echo $k <= $c['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; } ?>
                        </div>
                        <p style="color: #475569; font-size: 14px; line-height: 1.5; margin: 0;"><?php echo htmlspecialchars($c['comment']); ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
            } else if (data.error) {
                alert(data.error);
            }
        } catch (err) {
            console.error(err);
        }
    }
    </script>
</body>
</html>

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
    <?php include_once 'ai_assistant.php'; ?>
</body>
</html>
