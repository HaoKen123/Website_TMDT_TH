<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle region switching via GET query parameter ?region=VN or ?region=US
if (isset($_GET['region'])) {
    $r = strtoupper(trim($_GET['region']));
    if (in_array($r, ['VN', 'US'])) {
        $_SESSION['region'] = $r;
    }
}

// Default region is VN (Việt Nam)
if (!isset($_SESSION['region'])) {
    $_SESSION['region'] = 'VN';
}

define('EXCHANGE_RATE_VND', 25400); // 1 USD = 25,400 VNĐ

// Dictionary for Internationalization (i18n)
$dictionary = [
    'VN' => [
        // Site Title & Announcements
        'SITE_TITLE' => 'PixelGear | Cửa Hàng Thời Trang & Đồ Chơi Độc Quyền',
        'ANNOUNCEMENT_1' => '🎁 ĐĂNG KÝ NHẬN TIN để giảm 15% đơn hàng đầu tiên!',
        'ANNOUNCEMENT_2' => '🚚 MIỄN PHÍ VẬN CHUYỂN cho đơn từ 500k!',
        'ANNOUNCEMENT_3' => '🔥 KHÁM PHÁ bộ sưu tập HOT nhất!',
        
        // Navigation
        'NAV_HOME' => 'TRANG CHỦ',
        'NAV_ALL' => 'TẤT CẢ SẢN PHẨM',
        'NAV_CLOTHING' => 'QUẦN ÁO',
        'NAV_ACCESSORIES' => 'PHỤ KIỆN',
        'NAV_TOYS' => 'ĐỒ CHƠI & GAME',
        'NAV_CART' => 'GIỎ HÀNG',
        'NAV_PROFILE' => 'HỒ SƠ',
        'PRODUCTS_PAGE_TITLE' => 'SẢN PHẨM',
        'CART_PAGE_TITLE' => 'GIỎ HÀNG',
        
        // Hero Section
        'HERO_TITLE' => 'KHÁM PHÁ PHONG CÁCH ĐỘC ĐÁO',
        'HERO_SUBTITLE' => 'Thời trang cao cấp, phụ kiện độc quyền và đồ chơi sưu tầm.',
        'HERO_BTN' => 'MUA NGAY',
        
        // Product Sections
        'FEATURED_TITLE' => 'SẢN PHẨM NỔI BẬT',
        'BEST_SELLERS_TITLE' => 'SẢN PHẨM BÁN CHẠY NHẤT',
        'NEW_ARRIVALS_TITLE' => 'SẢN PHẨM MỚI',
        'VIEW_ALL_BTN' => 'XEM TẤT CẢ',
        'QUICK_VIEW' => 'XEM NHANH',
        'TRUST_BADGES_TITLE' => 'LÝ DO MUA TỪ CHÚNG TÔI',
        
        // Breadcrumbs
        'BREADCRUMB_HOME' => 'Trang chủ',
        'BREADCRUMB_PRODUCTS' => 'Sản phẩm',
        'BREADCRUMB_ALL' => 'Tất cả',
        
        // Filters
        'FILTER_TYPE' => 'LOẠI SẢN PHẨM',
        'FILTER_STYLE' => 'PHONG CÁCH',
        'FILTER_THEME' => 'CHỦ ĐỀ',
        'SHOWING' => 'Hiển thị',
        'PRODUCTS_LABEL' => 'sản phẩm',
        'CLEAR_FILTER' => 'Xóa bộ lọc',
        'SORT_LABEL' => 'Sắp xếp:',
        'SORT_NEWEST' => 'Mới nhất',
        'SORT_PRICE_ASC' => 'Giá: Thấp → Cao',
        'SORT_PRICE_DESC' => 'Giá: Cao → Thấp',
        
        // Buttons
        'ADD_TO_CART' => 'THÊM VÀO GIỎ',
        'SEARCH_PLACEHOLDER' => 'Tìm kiếm sản phẩm...',
        'LOGIN' => 'Đăng nhập',
        'PROFILE' => 'Hồ sơ',
        'CART' => 'Giỏ hàng',
        
        // Product Detail
        'PRODUCT_DETAIL' => [
            'PRODUCT_DETAIL_TITLE' => 'CHI TIẾT SẢN PHẨM',
            'PRODUCT_BUY_NOW' => 'MUA NGAY',
            'PRODUCT_QUANTITY' => 'Số Lượng',
            'PRODUCT_QUANTITY_LABEL' => 'Lượng',
            'PRODUCT_VIEW_QUICK' => 'XEM NHANH',
            'PRODUCT_ADD_TO_CART' => 'THÊM VÀO GIỎ',
            'PRODUCT_TRUST_Genuine' => 'Hàng chính hãng',
            'PRODUCT_TRUST_NewPackaging' => 'Bao bì mới',
            'PRODUCT_TRUST_FastShipping' => 'Giao nhanh',
            'PRODUCT_TRUST_ReturnPolicy' => 'Hoàn tiền 7 ngày',
            'PRODUCT_TRUST_Warranty' => 'Bảo hành 12 tháng',
            'PRODUCT_TRUST_FreeShipping' => 'Miễn phí ship',
            'PRODUCT_TRUST_SecurePayment' => 'Thanh toán an toàn',
            'PRODUCT_TRUST_Badge' => 'Chính hãng',
            'PRODUCT_FEATURES' => 'Tính Năng',
            'PRODUCT_DESCRIPTION' => 'Mô Tả',
            'PRODUCT_SPECIFICATIONS' => 'Thông Số',
            'PRODUCT_REVIEWS' => 'Đánh Giá',
            'PRODUCT_STAR' => '★',
            'PRODUCT_VERIFIED' => 'Đã kiểm chứng',
            'PRODUCT_REVIEWS_COUNT' => 'đánh giá',
            'PRODUCT_WRITE_REVIEW' => 'Viết đánh giá',
            'PRODUCT_SHARE_REVIEW' => 'Chia sẻ',
            'PRODUCT_REVIEWS_TITLE' => 'Đánh giá khách hàng',
            'PRODUCT_REVIEWS_OF' => '/ 5.0',
            'PRODUCT_REVIEWS_NAME' => 'Khách hàng',
            'PRODUCT_REVIEWS_DATE' => 'Ngày',
            'PRODUCT_REVIEWS_COMMENT' => 'Bình luận',
            'PRODUCT_REVIEWS_GIVE_REVIEW' => 'Viết đánh giá',
            'PRODUCT_REVIEWS_STAR' => '★',
        ],
        
        // Footer
        'FOOTER_SHOP' => 'MUA SẮM',
        'FOOTER_SUPPORT' => 'HỖ TRỢ',
        'FOOTER_CONTACT' => 'LIÊN HỆ',
        'FOOTER_NEWSLETTER' => 'NHẬN TIN',
        'FOOTER_NEWSLETTER_DESC' => 'Đăng ký nhận ưu đãi đặc biệt',
        'FOOTER_SUBSCRIBE' => 'ĐĂNG KÝ',
        'FOOTER_PRIVACY' => 'Chính Sách',
        'FOOTER_PRIVACY_POLICY' => 'Bảo Mật',
        'FOOTER_TERMS' => 'Điều Khoản',
        'FOOTER_TERMS_CONDITIONS' => 'Dịch Vụ',
        'FOOTER_PHONE' => '1900 123 456',
        'FOOTER_EMAIL' => 'support@pixelearg.com',
        'FOOTER_FACEBOOK' => 'Facebook',
        'FOOTER_INSTAGRAM' => 'Instagram',
        'FOOTER_TIKTOK' => 'TikTok',
        'FOOTER_YOUTUBE' => 'YouTube',
        'FOOTER_TAGLINE_VN' => 'Thời trang thời thượng, phụ kiện độc quyền',
        'FOOTER_CONNECT' => 'Kết nối với chúng tôi',
        'FOOTER_FOLLOW' => 'FOLLOW US',
        'FOOTER_FOLLOW_DESC' => 'Theo dõi để cập nhật',
        'FOOTER_NEWSLETTER_DESC' => 'Đăng ký nhận ưu đãi',
        'FOOTER_SUBSCRIBE' => 'ĐĂNG KÝ',
        'FOOTER_COPYRIGHT' => 'All rights reserved.',
    ],
    
    'US' => [
        // Site Title & Announcements
        'SITE_TITLE' => 'PixelGear | Premium Fashion & Toys',
        'ANNOUNCEMENT_1' => '🎁 SIGN UP to save 15% on first order!',
        'ANNOUNCEMENT_2' => '🚚 FREE SHIPPING on $50+ orders!',
        'ANNOUNCEMENT_3' => '🔥 DISCOVER our hottest collection!',
        
        // Navigation
        'NAV_HOME' => 'HOME',
        'NAV_ALL' => 'ALL PRODUCTS',
        'NAV_CLOTHING' => 'CLOTHING',
        'NAV_ACCESSORIES' => 'ACCESSORIES',
        'NAV_TOYS' => 'TOYS & GAMES',
        'NAV_CART' => 'CART',
        'NAV_PROFILE' => 'PROFILE',
        'PRODUCTS_PAGE_TITLE' => 'PRODUCTS',
        'CART_PAGE_TITLE' => 'CART',
        
        // Hero Section
        'HERO_TITLE' => 'DISCOVER UNIQUE STYLE',
        'HERO_SUBTITLE' => 'Premium fashion, exclusive accessories, and collectibles.',
        'HERO_BTN' => 'SHOP NOW',
        
        // Product Sections
        'FEATURED_TITLE' => 'FEATURED PRODUCTS',
        'BEST_SELLERS_TITLE' => 'BEST SELLERS',
        'NEW_ARRIVALS_TITLE' => 'NEW ARRIVALS',
        'VIEW_ALL_BTN' => 'VIEW ALL',
        'QUICK_VIEW' => 'QUICK VIEW',
        'TRUST_BADGES_TITLE' => 'WHY BUY FROM US',
        
        // Breadcrumbs
        'BREADCRUMB_HOME' => 'Home',
        'BREADCRUMB_PRODUCTS' => 'Products',
        'BREADCRUMB_ALL' => 'All',
        
        // Filters
        'FILTER_TYPE' => 'PRODUCT TYPE',
        'FILTER_STYLE' => 'STYLE',
        'FILTER_THEME' => 'THEME',
        'SHOWING' => 'Showing',
        'PRODUCTS_LABEL' => 'products',
        'CLEAR_FILTER' => 'Clear filters',
        'SORT_LABEL' => 'Sort by:',
        'SORT_NEWEST' => 'Newest',
        'SORT_PRICE_ASC' => 'Price: Low → High',
        'SORT_PRICE_DESC' => 'Price: High → Low',
        
        // Buttons
        'ADD_TO_CART' => 'ADD TO CART',
        'SEARCH_PLACEHOLDER' => 'Search products...',
        'LOGIN' => 'Login',
        'PROFILE' => 'Profile',
        'CART' => 'Cart',
        
        // Product Detail
        'PRODUCT_DETAIL' => [
            'PRODUCT_DETAIL_TITLE' => 'PRODUCT DETAILS',
            'PRODUCT_BUY_NOW' => 'BUY NOW',
            'PRODUCT_QUANTITY' => 'Quantity',
            'PRODUCT_QUANTITY_LABEL' => 'Qty',
            'PRODUCT_VIEW_QUICK' => 'QUICK VIEW',
            'PRODUCT_ADD_TO_CART' => 'ADD TO CART',
            'PRODUCT_TRUST_Genuine' => '100% Genuine',
            'PRODUCT_TRUST_NewPackaging' => 'Brand new',
            'PRODUCT_TRUST_FastShipping' => 'Fast shipping',
            'PRODUCT_TRUST_ReturnPolicy' => '7-day return',
            'PRODUCT_TRUST_Warranty' => '12-month warranty',
            'PRODUCT_TRUST_FreeShipping' => 'Free shipping',
            'PRODUCT_TRUST_SecurePayment' => 'Secure payment',
            'PRODUCT_TRUST_Badge' => 'AUTHENTIC',
            'PRODUCT_FEATURES' => 'Features',
            'PRODUCT_DESCRIPTION' => 'Description',
            'PRODUCT_SPECIFICATIONS' => 'Specifications',
            'PRODUCT_REVIEWS' => 'Reviews',
            'PRODUCT_STAR' => '★',
            'PRODUCT_VERIFIED' => 'Verified',
            'PRODUCT_REVIEWS_COUNT' => 'reviews',
            'PRODUCT_WRITE_REVIEW' => 'Write a Review',
            'PRODUCT_SHARE_REVIEW' => 'Share',
            'PRODUCT_REVIEWS_TITLE' => 'Customer Reviews',
            'PRODUCT_REVIEWS_OF' => '/ 5.0',
            'PRODUCT_REVIEWS_NAME' => 'Customer',
            'PRODUCT_REVIEWS_DATE' => 'Date',
            'PRODUCT_REVIEWS_COMMENT' => 'Comments',
            'PRODUCT_REVIEWS_GIVE_REVIEW' => 'Write a Review',
            'PRODUCT_REVIEWS_STAR' => '★',
        ],
        
        // Footer
        'FOOTER_SHOP' => 'SHOP',
        'FOOTER_SUPPORT' => 'SUPPORT',
        'FOOTER_CONTACT' => 'CONTACT',
        'FOOTER_NEWSLETTER' => 'NEWSLETTER',
        'FOOTER_NEWSLETTER_DESC' => 'Subscribe for exclusive offers',
        'FOOTER_SUBSCRIBE' => 'SUBSCRIBE',
        'FOOTER_PRIVACY' => 'Privacy',
        'FOOTER_PRIVACY_POLICY' => 'Policy',
        'FOOTER_TERMS' => 'Terms',
        'FOOTER_TERMS_CONDITIONS' => 'Conditions',
        'FOOTER_PHONE' => '1-800-123-4567',
        'FOOTER_EMAIL' => 'support@pixelearg.com',
        'FOOTER_FACEBOOK' => 'Facebook',
        'FOOTER_INSTAGRAM' => 'Instagram',
        'FOOTER_TIKTOK' => 'TikTok',
        'FOOTER_YOUTUBE' => 'YouTube',
        'FOOTER_TAGLINE_US' => 'Premium fashion, exclusive accessories',
        'FOOTER_CONNECT' => 'Connect with us',
        'FOOTER_FOLLOW' => 'FOLLOW US',
        'FOOTER_FOLLOW_DESC' => 'Follow us to stay updated',
        'FOOTER_NEWSLETTER_DESC' => 'Subscribe to our newsletter',
        'FOOTER_SUBSCRIBE' => 'SUBSCRIBE',
        'FOOTER_COPYRIGHT' => 'All rights reserved.',
    ],
];

function get_current_region() {
    return $_SESSION['region'] ?? 'VN';
}

function __($key) {
    global $dictionary;
    $region = get_current_region();
    return $dictionary[$region][$key] ?? $key;
}

function format_price($price_in_usd) {
    $region = get_current_region();
    if ($region === 'VN') {
        $vnd = round($price_in_usd * EXCHANGE_RATE_VND);
        return number_format($vnd) . ' ₫';
    } else {
        return '$' . number_format($price_in_usd, 2);
    }
}
?>
