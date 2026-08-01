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
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? AND id != ? LIMIT 6");
$stmt->execute([$product['category'], $id]);
$related_products = $stmt->fetchAll();

// Fetch customer reviews
$stmt = $pdo->prepare("SELECT * FROM product_reviews WHERE product_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

// Calculate average rating
$avg_rating = 0;
if ($reviews) {
    $total_rating = 0;
    foreach ($reviews as $review) {
        $total_rating += $review['rating'];
    }
    $avg_rating = round($total_rating / count($reviews), 1);
}

// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += $qty;
    }
}

// Get user info if logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$can_write_reviews = $user_id && isset($product['user_reviews_wrote']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> | PixelGear</title>
    <meta name="description" content="<?php echo htmlspecialchars($product['description']); ?>">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f9fafb;
            color: #111827;
        }
        
        /* ==========================================
           HEADER & ANNOUNCEMENT BAR
           ========================================== */
        .announcement-bar {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            color: #fff;
            text-align: center;
            padding: 10px 0;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #374151;
            position: relative;
        }
        .announcement-bar::before,
        .announcement-bar::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 60px;
            height: 1px;
            background: linear-gradient(90deg, transparent, #10b981, transparent);
        }
        .announcement-bar::before {
            left: 20px;
        }
        .announcement-bar::after {
            right: 20px;
        }
        
        .site-header {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            position: sticky;
            top: 40px;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            border-bottom: 2px solid #10b981;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 15px 20px;
            max-width: 1400px;
            margin: 0 auto;
            gap: 20px;
        }
        .mobile-menu-btn {
            display: none;
            font-size: 24px;
            color: #fff;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .mobile-menu-btn i::before,
        .mobile-menu-btn i::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 2px;
            background: #fff;
            left: 50%;
            transition: all 0.3s ease;
        }
        .mobile-menu-btn i::before {
            top: -8px;
        }
        .mobile-menu-btn i::after {
            bottom: -8px;
        }
        .mobile-menu-btn.active i::before {
            transform: rotate(45deg);
        }
        .mobile-menu-btn.active i::after {
            transform: rotate(-45deg);
        }
        
        .logo h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            font-weight: 900;
            color: #fff;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-shadow: 0 0 20px rgba(16, 185, 129, 0.6);
            margin: 0;
            white-space: nowrap;
        }
        
        .main-nav ul {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .main-nav a {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 8px 16px;
            color: #10b981;
            transition: all 0.25s ease;
            border-radius: 4px;
        }
        .main-nav a:hover,
        .main-nav a.active {
            background: rgba(16, 185, 129, 0.2);
            color: #fff;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
        }
        
        .header-icons {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .header-icons a {
            font-size: 18px;
            color: #fff;
            transition: all 0.3s ease;
            position: relative;
        }
        .header-icons a:hover {
            transform: scale(1.25) rotate(5deg);
            color: #10b981;
        }
        .cart-count {
            position: absolute;
            top: -8px;
            right: -10px;
            background: linear-gradient(135deg, #10b981, #10b981);
            color: #fff;
            font-size: 9px;
            font-weight: 800;
            padding: 2px 5px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.6);
        }
        
        /* ==========================================
           BREADCRUMB
           ========================================== */
        .breadcrumb {
            margin: 20px 20px 30px;
            font-size: 13px;
            color: #6b7280;
        }
        .breadcrumb a {
            color: #10b981;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .breadcrumb a:hover {
            color: #34d399;
        }
        .breadcrumb span {
            color: #9ca3af;
            margin: 0 6px;
        }
        .breadcrumb .current {
            color: #6b7280;
            font-weight: 600;
        }
        
        /* ==========================================
           PRODUCT DETAIL MAIN GRID
           ========================================== */
        .product-detail-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .product-detail-grid {
            display: grid;
            grid-template-columns: 1.1fr 1.3fr;
            gap: 50px;
            margin-bottom: 70px;
        }
        
        /* Gallery Section */
        .product-detail-media {
            position: sticky;
            top: 130px;
        }
        .gallery-main {
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            margin-bottom: 15px;
            position: relative;
        }
        .gallery-main img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            object-position: center;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .gallery-main img:hover {
            transform: scale(1.08);
        }
        .zoom-overlay {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #10b981, #34d399);
            color: #fff;
            padding: 10px 18px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.5);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .gallery-thumbnails {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .gallery-thumb {
            width: 100%;
            height: 85px;
            border-radius: 10px;
            overflow: hidden;
            border: 3px solid transparent;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
        }
        .gallery-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .gallery-thumb.active {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }
        .gallery-thumb:hover {
            transform: scale(1.05);
        }
        .gallery-thumb::after {
            content: '\f04e';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            color: #fff;
            font-size: 24px;
            opacity: 0;
            transition: all 0.3s ease;
        }
        .gallery-thumb:hover::after {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
        .gallery-thumb.active::after {
            content: '\f00d';
            transform: translate(-50%, -50%) scale(1);
        }
        
        /* Product Info Section */
        .product-detail-info {
            display: flex;
            flex-direction: column;
        }
        
        /* Product Badges */
        .product-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
            align-items: center;
        }
        .badge-new {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
        }
        .badge-best-seller {
            background: linear-gradient(135deg, #10b981, #16a34a);
            color: #fff;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.5);
        }
        .badge-sale {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: #fff;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.5);
        }
        
        /* Product Title */
        .detail-title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 15px;
            color: #111827;
            line-height: 1.3;
        }
        
        /* Rating Stars */
        .product-rating {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: linear-gradient(135deg, #f9fafb, #fff);
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 20px;
            align-self: flex-start;
            border: 1px solid #e5e7eb;
        }
        .rating-stars {
            color: #f59e0b;
            font-size: 15px;
        }
        .rating-count {
            color: #6b7280;
            font-size: 12px;
            margin-left: 4px;
        }
        .rating-verified {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            font-size: 10px;
            color: #16a34a;
            font-weight: 700;
            margin-left: 4px;
        }
        
        /* Price */
        .detail-price {
            font-size: 38px;
            font-weight: 900;
            color: #10b981;
            margin-bottom: 20px;
            display: flex;
            align-items: baseline;
            gap: 15px;
        }
        .detail-price-suffix {
            font-size: 22px;
            color: #6b7280;
            font-weight: 600;
        }
        .detail-old-price {
            font-size: 24px;
            color: #d1d5db;
            text-decoration: line-through;
            font-weight: 500;
        }
        .discount-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: #fff;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Feature Tags */
        .product-features {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
        }
        .feature-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 1px solid #86efac;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            color: #16a34a;
        }
        .feature-tag i {
            font-size: 13px;
        }
        
        /* Tabs */
        .tabs-container {
            margin-bottom: 25px;
        }
        .tabs-header {
            display: flex;
            gap: 1px;
            background: #e5e7eb;
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 25px;
            overflow-x: auto;
        }
        .tab {
            padding: 12px 22px;
            font-size: 12px;
            font-weight: 700;
            color: #6b7280;
            background: #e5e7eb;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            white-space: nowrap;
        }
        .tab:hover {
            color: #10b981;
            background: #d1d5db;
        }
        .tab.active {
            color: #fff;
            background: #10b981;
        }
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #fff, transparent);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease;
        }
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .tab-panel {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .tab-panel-description {
            font-size: 15px;
            line-height: 1.8;
            color: #374151;
        }
        .tab-panel-specs table {
            width: 100%;
            border-collapse: collapse;
        }
        .tab-panel-specs td {
            padding: 12px 15px;
            border-bottom: 1px solid #f3f4f6;
        }
        .tab-panel-specs td:first-child {
            font-weight: 600;
            color: #6b7280;
            width: 40%;
        }
        .tab-panel-reviews {
            max-height: 350px;
            overflow-y: auto;
        }
        .review-item {
            background: #f9fafb;
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid #e5e7eb;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .reviewer-name {
            font-weight: 700;
            color: #111827;
            font-size: 14px;
        }
        .review-date {
            font-size: 11px;
            color: #9ca3af;
        }
        .stars-small {
            color: #f59e0b;
            font-size: 12px;
        }
        
        /* Purchase Actions */
        .purchase-actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 25px;
            align-items: flex-start;
        }
        .quantity-section {
            display: flex;
            align-items: center;
            gap: 15px;
            width: 100%;
        }
        .quantity-label {
            font-size: 12px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .quantity-control {
            display: inline-flex;
            align-items: center;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            overflow: hidden;
            background: #f9fafb;
        }
        .quantity-control button {
            width: 36px;
            height: 36px;
            background: #fff;
            border: none;
            font-size: 18px;
            font-weight: 700;
            color: #10b981;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .quantity-control button:hover {
            background: #f9fafb;
            color: #34d399;
            transform: scale(1.1);
        }
        .quantity-control input {
            width: 45px;
            height: 36px;
            border: none;
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            outline: none;
            -moz-appearance: textfield;
        }
        .quantity-control input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .quantity-control input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .btn-primary-large {
            width: 100%;
            padding: 18px 30px;
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        .btn-primary-large::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .btn-primary-large:hover::before {
            width: 300px;
            height: 300px;
        }
        .btn-primary-large:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.5);
        }
        .btn-primary-large:active {
            transform: translateY(0);
        }
        .btn-primary-large i {
            font-size: 18px;
        }
        .btn-primary-large .btn-icon {
            position: absolute;
            right: 20px;
        }
        .btn-primary-large .btn-text {
            position: relative;
            z-index: 1;
        }
        
        .btn-secondary-large {
            padding: 16px 24px;
            font-size: 13px;
            font-weight: 700;
            color: #10b981;
            background: #fff;
            border: 2px solid #10b981;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-secondary-large:hover {
            background: #10b981;
            color: #fff;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .trust-icons {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 20px;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .trust-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
        }
        .trust-item i {
            color: #10b981;
            font-size: 14px;
        }
        .trust-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 1px solid #86efac;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            color: #16a34a;
        }
        
        /* Color Selector */
        .color-selector {
            margin: 25px 0;
        }
        .color-selector-label {
            font-size: 12px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 12px;
            display: block;
        }
        .color-options {
            display: flex;
            gap: 12px;
        }
        .color-option {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .color-option:hover {
            transform: scale(1.25);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .color-option.active {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
            transform: scale(1.15);
        }
        .color-option.active::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #fff;
            font-size: 16px;
        }
        
        /* Size Selector */
        .size-selector {
            margin: 25px 0;
        }
        .size-selector-label {
            font-size: 12px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 12px;
            display: block;
        }
        .size-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .size-option {
            padding: 10px 16px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 60px;
            text-align: center;
        }
        .size-option:hover {
            border-color: #10b981;
            color: #10b981;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        .size-option.selected {
            background: #10b981;
            border-color: #10b981;
            color: #fff;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }
        .size-option.available {
            background: #f0fdf4;
            border-color: #86efac;
            color: #16a34a;
        }
        .size-option.out-of-stock {
            background: #f9fafb;
            border-color: #e5e7eb;
            color: #d1d5db;
            cursor: not-allowed;
        }
        .size-option.out-of-stock::before {
            content: '\f072';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 14px;
        }
        
        /* Related Products */
        .related-section {
            margin-top: 70px;
        }
        .related-section h2 {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 10px;
            color: #111827;
        }
        .related-section p {
            font-size: 15px;
            color: #6b7280;
            margin-bottom: 30px;
        }
        .product-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }
        .related-card-image {
            width: 100%;
            height: 240px;
            overflow: hidden;
            position: relative;
        }
        .related-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .product-card:hover .related-card-image img {
            transform: scale(1.1);
        }
        .related-card-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 10px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: #fff;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .related-card-info {
            padding: 20px;
        }
        .related-card-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #111827;
        }
        .related-card-title a {
            color: inherit;
            text-decoration: none;
        }
        .related-card-price {
            font-size: 22px;
            font-weight: 900;
            color: #10b981;
            margin-bottom: 8px;
        }
        .related-card-old-price {
            font-size: 14px;
            color: #d1d5db;
            text-decoration: line-through;
        }
        
        @media (max-width: 992px) {
            .product-detail-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            .product-detail-media {
                position: static;
            }
            .gallery-thumbnails {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .product-detail-container {
                padding: 15px;
            }
            .detail-title {
                font-size: 22px;
            }
            .detail-price {
                font-size: 28px;
            }
            .tabs-header {
                overflow-x: visible;
            }
            .tab {
                padding: 10px 14px;
                font-size: 11px;
            }
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

    <!-- Header -->
    <header class="site-header">
        <div class="header-container">
            <div class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </div>
            
            <div class="logo">
                <h1>PIXELGEAR</h1>
            </div>

            <nav class="main-nav">
                <ul>
                    <li><a href="index.php" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>"><?php echo __('NAV_HOME'); ?></a></li>
                    <li><a href="products.php" class="<?php echo in_array($current_page, ['products', 'all']) ? 'active' : ''; ?>"><?php echo __('NAV_ALL'); ?></a></li>
                    <li><a href="products.php?category=clothing" class="<?php echo $current_page === 'clothing' ? 'active' : ''; ?>"><?php echo __('NAV_CLOTHING'); ?></a></li>
                    <li><a href="products.php?category=accessories" class="<?php echo $current_page === 'accessories' ? 'active' : ''; ?>"><?php echo __('NAV_ACCESSORIES'); ?></a></li>
                    <li><a href="products.php?category=toys" class="<?php echo $current_page === 'toys' ? 'active' : ''; ?>"><?php echo __('NAV_TOYS'); ?></a></li>
                </ul>
            </nav>

            <div class="header-icons">
                <a href="products.php" class="search-container">
                    <input type="text" name="search" placeholder="Tìm kiếm..." style="background: rgba(255,255,255,0.1); border: none; color: #fff; padding: 6px; font-size: 12px; width: 120px;">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </a>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="profile.php"><i class="fas fa-user-circle"></i></a>
                <?php else: ?>
                    <a href="login.php"><i class="fas fa-user"></i></a>
                <?php endif; ?>
                
                <a href="cart.php">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </div>
        </div>
    </header>

    <!-- Breadcrumb -->
    <nav class="breadcrumb" id="breadcrumb">
        <a href="index.php">Trang chủ</a> <span>/</span>
        <span>Sản phẩm</span> <span>/</span>
        <span class="current"><?php echo htmlspecialchars($product['name']); ?></span>
    </nav>

    <!-- Product Detail -->
    <div class="product-detail-container">
        <div class="product-detail-grid">
            <!-- Left: Gallery -->
            <div class="product-detail-media">
                <div class="gallery-main">
                    <img id="mainImage" src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="zoom-overlay"><i class="fas fa-search-plus"></i> Zoom</div>
                </div>
                <div class="gallery-thumbnails">
                    <div class="gallery-thumb active"><img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt=""></div>
                    <?php if (isset($product['additional_images']) && !empty($product['additional_images'])): ?>
                        <?php foreach (explode(',', $product['additional_images']) as $imgUrl): ?>
                            <div class="gallery-thumb"><img src="<?php echo htmlspecialchars(trim($imgUrl)); ?>" alt=""></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Product Info -->
            <div class="product-detail-info">
                <div class="product-badges">
                    <?php if ($product['badge'] === 'Mới'): ?>
                        <span class="badge-new"><i class="fas fa-star"></i> MỚI</span>
                    <?php endif; ?>
                    <?php if ($product['badge'] === 'Best Seller'): ?>
                        <span class="badge-best-seller"><i class="fas fa-fire"></i> BEST SELLER</span>
                    <?php endif; ?>
                    <?php if ($product['badge'] === 'Giảm giá'): ?>
                        <span class="badge-sale"><i class="fas fa-percent"></i> GIẢM GIÁ</span>
                    <?php endif; ?>
                </div>

                <h1 class="detail-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                <?php if ($avg_rating > 0): ?>
                <div class="product-rating">
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= ceil($avg_rating)): ?>
                                <i class="fas fa-star"></i>
                            <?php elseif ($i <= ceil($avg_rating) + 0.5): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-count">
                        <span class="rating-verified"><i class="fas fa-check-circle"></i> Verified</span>
                        <?php echo number_format(count($reviews), 0); ?> đánh giá
                    </span>
                </div>
                <?php endif; ?>

                <div class="detail-price">
                    <?php if ($product['old_price']): ?>
                        <span class="detail-old-price">$<?php echo $product['old_price']; ?></span>
                        <div class="discount-badge"><i class="fas fa-percentage"></i> <?php echo round((1 - $product['price'] / $product['old_price']) * 100); ?>% OFF</div>
                    <?php endif; ?>
                    $<?php echo number_format($product['price'], 2); ?>
                    <span class="detail-price-suffix">USD</span>
                </div>

                <?php if ($product['category'] === 'clothing' && !empty($sizes)): ?>
                <div class="size-selector">
                    <span class="size-selector-label">Kích Thước</span>
                    <div class="size-options">
                        <?php foreach ($sizes as $size): ?>
                            <?php
                            $sizeLabel = $size['name'] ?? 'S';
                            $sizeLabelVn = $size['label_vn'] ?? $sizeLabel;
                            $available = !empty($size['quantity']) && $size['quantity'] > 0;
                            $selectedSize = $_GET['size'] ?? null;
                            $isSelected = $selectedSize === $size['id'];
                            ?>
                            <div class="size-option <?php echo $available ? '' : 'out-of-stock'; ?> <?php echo $isSelected ? 'selected' : ''; ?>"
                                 onclick="selectSize('<?php echo $size['id']; ?>', this, '<?php echo $sizeLabelVn; ?>')">
                                 <?php echo $sizeLabelVn; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (isset($product['colors']) && !empty($product['colors'])): ?>
                <div class="color-selector">
                    <span class="color-selector-label">Màu Sắc</span>
                    <div class="color-options">
                        <?php foreach (explode(',', $product['colors']) as $color): ?>
                            <div class="color-option" style="background: <?php echo preg_replace('#[^a-zA-Z0-9]+#', '', $color); ?>"
                                 title="<?php echo htmlspecialchars($color); ?>"
                                 onclick="selectColor(this)"></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="product-features">
                    <div class="feature-tag"><i class="fas fa-shield-alt"></i> Hàng chính hãng</div>
                    <div class="feature-tag"><i class="fas fa-box-open"></i> Bao bì mới</div>
                    <div class="feature-tag"><i class="fas fa-shipping-fast"></i> Giao hàng nhanh</div>
                    <div class="feature-tag"><i class="fas fa-undo"></i> Hoàn tiền 30 ngày</div>
                </div>

                <div class="tabs-container">
                    <div class="tabs-header">
                        <button class="tab active" onclick="openTab('tab-description', event)">Mô tả</button>
                        <button class="tab" onclick="openTab('tab-specs', event)">Thông số</button>
                        <?php if ($avg_rating > 0 && $can_write_reviews): ?>
                            <button class="tab" onclick="openTab('tab-reviews', event)">
                                Đánh giá <i class="fas fa-star" style="color: #f59e0b;"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div id="tab-description" class="tab-content active">
                        <div class="tab-panel tab-panel-description">
                            <?php echo nl2br(htmlspecialchars($product['description'] ?? 'Sản phẩm cao cấp chính hãng PixelGear, chất lượng tốt với thiết kế độc quyền.')); ?>
                        </div>
                    </div>

                    <div id="tab-specs" class="tab-content">
                        <div class="tab-panel tab-panel-specs">
                            <table>
                                <tr><td>Độ tuổi</td><td>Adult / Teen</td></tr>
                                <tr><td>Chất liệu</td><td>100% Cotton / Polyester</td></tr>
                                <tr><td>Kích thước</td><td>S - XL</td></tr>
                                <tr><tr><td>Xuất xứ</td><td>Đa quốc gia</td></tr>
                                <tr><td>Bao bì</td><td>Mới 100%, có tem mác</td></tr>
                            </table>
                        </div>
                    </div>

                    <?php if ($avg_rating > 0 && $can_write_reviews): ?>
                    <div id="tab-reviews" class="tab-content">
                        <div class="tab-panel">
                            <h3 style="margin-bottom: 20px; font-size: 18px; color: #111827;">
                                Đánh giá khách hàng 
                                <span style="font-size: 14px; color: #6b7280; font-weight: 400;">(<?php echo number_format($avg_rating, 1); ?> / 5.0)</span>
                            </h3>
                            
                            <?php if ($reviews): ?>
                            <div class="tab-panel-reviews">
                                <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div>
                                            <div class="reviewer-name"><?php echo htmlspecialchars($review['name']); ?></div>
                                            <div class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></div>
                                        </div>
                                        <div class="stars-small">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $review['rating']): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p style="font-size: 14px; color: #6b7280; line-height: 1.6;">
                                        <?php echo htmlspecialchars($review['comment']); ?>
                                    </p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <?php if ($can_write_reviews): ?>
                            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                                <h4 style="margin-bottom: 15px; font-size: 16px; color: #111827;">Viết đánh giá của bạn</h4>
                                <textarea style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-family: inherit; font-size: 14px; min-height: 100px; resize: vertical;" placeholder="Chia sẻ trải nghiệm của bạn..."></textarea>
                                <button style="margin-top: 15px; padding: 10px 24px; background: #10b981; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;" onclick="alert('Tính năng đánh giá đang phát triển')">Gửi Đánh Giá</button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="purchase-actions">
                    <div class="quantity-section">
                        <span class="quantity-label">Số Lượng</span>
                        <div class="quantity-control">
                            <button onclick="adjustQuantity(-1)">-</button>
                            <input type="number" id="productQuantity" value="1" min="1" style="width: 45px;">
                            <button onclick="adjustQuantity(1)">+</button>
                        </div>
                    </div>
                    
                    <button class="btn-primary-large" onclick="addToCart()">
                        <span class="btn-text"><i class="fas fa-shopping-cart"></i> THÊM VÀO GIỎ HÀNG</span>
                        <i class="fas fa-check-circle btn-icon"></i>
                    </button>
                    
                    <button class="btn-secondary-large" onclick="openQuickView()">
                        <i class="fas fa-eye"></i> Xem nhanh
                    </button>
                    
                <div class="trust-icons">
                        <div class="trust-item"><i class="fas fa-check-circle"></i> Bảo hành 1 năm</div>
                        <div class="trust-item"><i class="fas fa-truck"></i> Miễn phí ship</div>
                        <div class="trust-item"><i class="fas fa-lock"></i> Thanh toán an toàn</div>
                        <div class="trust-badge"><i class="fas fa-certificate"></i> Chính hãng</div>
                    </div>
            </div>
        </div>
    </div>

    <!-- Customers Also Bought Section -->
    <?php if (!empty($related_products)): ?>
    <section class="related-section">
        <h2><?php echo __('PRODUCTS_ALSO_BUGHT'); ?></h2>
        <p><?php echo __('CUSTOMERS_ALSO_BUGHT'); ?></p>
        <div class="product-grid">
            <?php foreach ($related_products as $rel): ?>
            <div class="product-card">
                <div class="related-card-image">
                    <?php if ($rel['badge']): ?>
                    <div class="related-card-badge"><?php echo htmlspecialchars($rel['badge']); ?></div>
                    <?php endif; ?>
                    <img src="<?php echo htmlspecialchars($rel['image_url']); ?>" alt="<?php echo htmlspecialchars($rel['name']); ?>">
                </div>
                <div class="related-card-info">
                    <div class="related-card-title"><a href="product_detail.php?id=<?php echo $rel['id']; ?>"><?php echo htmlspecialchars($rel['name']); ?></a></div>
                    <div class="related-card-price">$<?php echo number_format($rel['price'], 2); ?></div>
                    <?php if ($rel['old_price']): ?>
                    <div class="related-card-old-price">$<?php echo $rel['old_price']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Additional Features Section -->
    <section style="margin-top: 80px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 16px; padding: 50px 40px; text-align: center;">
        <h2 style="font-size: 28px; font-weight: 900; color: #fff; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">
            <i class="fas fa-award" style="margin-right: 10px;"></i> <?php echo __('BENEFITS_TITLE'); ?>
        </h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-top: 40px;">
            <div style="background: rgba(255, 255, 255, 0.15); padding: 30px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.2);">
                <div style="font-size: 48px; margin-bottom: 15px;">🚚</div>
                <h3 style="font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 10px;">MIỄN PHÍ GIAO HÀNG</h3>
                <p style="font-size: 14px; color: rgba(255, 255, 255, 0.85); line-height: 1.6;">Miễn phí vận chuyển toàn quốc cho đơn hàng từ 500k</p>
            </div>
            <div style="background: rgba(255, 255, 255, 0.15); padding: 30px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.2);">
                <div style="font-size: 48px; margin-bottom: 15px;">🔒</div>
                <h3 style="font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 10px;">THANH TOÁN AN TOÀN</h3>
                <p style="font-size: 14px; color: rgba(255, 255, 255, 0.85); line-height: 1.6;">Hệ thống bảo mật hiện đại, thanh toán an toàn 100%</p>
            </div>
            <div style="background: rgba(255, 255, 255, 0.15); padding: 30px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.2);">
                <div style="font-size: 48px; margin-bottom: 15px;">↩️</div>
                <h3 style="font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 10px;">HOÀN TIỀN 30 NGÀY</h3>
                <p style="font-size: 14px; color: rgba(255, 255, 255, 0.85); line-height: 1.6;">Chính sách đổi trả và hoàn tiền linh hoạt</p>
            </div>
            <div style="background: rgba(255, 255, 255, 0.15); padding: 30px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.2);">
                <div style="font-size: 48px; margin-bottom: 15px;">🎁</div>
                <h3 style="font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 10px;">Voucher Quà Tặng</h3>
                <p style="font-size: 14px; color: rgba(255, 255, 255, 0.85); line-height: 1.6;">Nhận voucher giảm giá khi đặt hàng đầu tiên</p>
            </div>
        </div>
    </section>
    </div>

    <!-- Footer -->
    <footer class="site-footer">

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
    <section class="related-section">
        <h2><?php echo __('PRODUCTS_ALSO_BUGHT'); ?></h2>
        <p><?php echo __('CUSTOMERS_ALSO_BUGHT'); ?></p>
        <div class="product-grid">
            <?php foreach ($related_products as $rel): ?>
            <div class="product-card">
                <div class="related-card-image">
                    <?php if ($rel['badge']): ?>
                    <div class="related-card-badge"><?php echo htmlspecialchars($rel['badge']); ?></div>
                    <?php endif; ?>
                    <img src="<?php echo htmlspecialchars($rel['image_url']); ?>" alt="<?php echo htmlspecialchars($rel['name']); ?>">
                </div>
                <div class="related-card-info">
                    <div class="related-card-title"><a href="product_detail.php?id=<?php echo $rel['id']; ?>"><?php echo htmlspecialchars($rel['name']); ?></a></div>
                    <div class="related-card-price">$<?php echo number_format($rel['price'], 2); ?></div>
                    <?php if ($rel['old_price']): ?>
                    <div class="related-card-old-price">$<?php echo $rel['old_price']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i class="fas fa-check-circle"></i> Đã thêm sản phẩm vào giỏ hàng!
    </div>

    <!-- Quick View Modal -->
    <div id="quickViewModal" class="quick-view-modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <button class="modal-close" id="closeQuickView" style="font-size: 30px; color: #fff; border: none; background: none; cursor: pointer;"><i class="fas fa-times"></i></button>
            <div class="modal-body">
                <div class="modal-left">
                    <div class="gallery-main" style="height: 400px;">
                        <img id="qvMainImg" src="" alt="">
                    </div>
                    <div class="gallery-thumbnails" id="qvThumbnails"></div>
                </div>
                <div class="modal-right">
                    <h2 id="qvTitle" style="font-size: 24px; margin-bottom: 15px; color: #111827;"></h2>
                    <div id="qvPrice" style="font-size: 32px; font-weight: 900; color: #10b981; margin-bottom: 20px;"></div>
                    
                    <div class="quantity-section" style="margin-bottom: 20px;">
                        <span style="font-weight: 700; color: #6b7280;">Số Lượng:</span>
                        <div class="quantity-control">
                            <button onclick="qvAdjustQuantity(-1)">-</button>
                            <input type="number" id="qvQty" value="1" min="1">
                            <button onclick="qvAdjustQuantity(1)">+</button>
                        </div>
                    </div>
                    
                    <button id="qvAddToCartBtn" class="btn-primary-large" style="margin-bottom: 20px;">
                        <span><i class="fas fa-shopping-cart"></i> THÊM VÀO GIỎ</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
    // Product-specific scripts
    document.addEventListener('DOMContentLoaded', function() {
        const product = <?php echo json_encode($product); ?>;
        const relatedProducts = <?php echo json_encode($related_products); ?>;
        const reviews = <?php echo json_encode($reviews); ?>;
        const avgRating = <?php echo json_encode($avg_rating); ?>;
        const cartCount = <?php echo json_encode($cart_count); ?>;
        const userId = <?php echo json_encode($user_id); ?>;
        const canWriteReviews = <?php echo json_encode($can_write_reviews); ?>;

        // Set main image
        document.getElementById('mainImage').src = product.image_url;

        // Breadcrumb
        const breadcrumb = document.getElementById('breadcrumb');
        if (breadcrumb && product.category) {
            const categoryLinks = {
                'clothing': 'Quần Áo',
                'accessories': 'Phụ Kiện',
                'toys': 'Đồ Chơi'
            };
            const category = categoryLinks[product.category] || product.category;
            breadcrumb.innerHTML = `<a href="index.php">Trang chủ</a> <span>/</span> <a href="products.php?category=${product.category}">${category}</a> <span>/</span> <span class="current">${product.name}</span>`;
        }

        // Quantity control
        function adjustQuantity(delta) {
            const input = document.getElementById('productQuantity');
            let value = parseInt(input.value) || 1;
            value += delta;
            if (value < 1) value = 1;
            input.value = value;
        }

        // Add to cart
        function addToCart() {
            const quantity = parseInt(document.getElementById('productQuantity').value) || 1;
            
            fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: product.id, action: 'set', quantity: quantity })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('.cart-count').textContent = data.cart_count;
                    showToast();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Error:', err);
            });
        }

        // Quick view
        function openQuickView() {
            const modal = document.getElementById('quickViewModal');
            document.getElementById('qvMainImg').src = product.image_url;
            document.getElementById('qvTitle').textContent = product.name;
            document.getElementById('qvPrice').textContent = '$' + product.price.toFixed(2);
            document.getElementById('qvQty').value = 1;
            
            const thumbnailsContainer = document.getElementById('qvThumbnails');
            thumbnailsContainer.innerHTML = '';
            
            if (product.additional_images) {
                const images = product.additional_images.split(',');
                images.forEach(img => {
                    const div = document.createElement('div');
                    div.className = 'gallery-thumb';
                    div.innerHTML = `<img src="${img.trim()}" alt="">`;
                    thumbnailsContainer.appendChild(div);
                });
            } else {
                const div = document.createElement('div');
                div.className = 'gallery-thumb active';
                div.innerHTML = `<img src="${product.image_url}" alt="">`;
                thumbnailsContainer.appendChild(div);
            }
            
            modal.style.display = 'flex';
        }

        function closeQuickView() {
            document.getElementById('quickViewModal').style.display = 'none';
        }

        function qvAdjustQuantity(delta) {
            const value = parseInt(document.getElementById('qvQty').value) || 1;
            let newValue = value + delta;
            if (newValue < 1) newValue = 1;
            document.getElementById('qvQty').value = newValue;
        }

        // Toast
        function showToast() {
            const toast = document.getElementById('toast');
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Size selection
        function selectSize(sizeId, element, sizeLabel) {
            document.querySelectorAll('.size-option').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('productQuantity').max = parseInt(element.getAttribute('data-max')) || 999;
        }

        // Color selection
        function selectColor(element) {
            document.querySelectorAll('.color-option').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }

        // Tab switching
        function openTab(tabId, event) {
            event.preventDefault();
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        // Close modal when clicking overlay
        document.getElementById('closeQuickView')?.addEventListener('click', closeQuickView);
        document.querySelector('.modal-overlay')?.addEventListener('click', closeQuickView);
    });

    // Mobile menu toggle
    document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
        this.classList.toggle('active');
        const nav = this.nextElementSibling.querySelector('.main-nav');
        if (nav) {
            nav.style.display = this.classList.contains('active') ? 'block' : 'none';
        }
    });
    </script>
</body>
</html>
