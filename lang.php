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
        'SITE_TITLE' => 'PixelGear | Cửa Hàng Thời Trang, Phụ Kiện & Đồ Chơi Độc Quyền',
        'ANNOUNCEMENT_1' => 'ĐĂNG KÝ NHẬN TIN ĐỂ GIẢM 15% CHO ĐƠN HÀNG ĐẦU TIÊN!',
        'ANNOUNCEMENT_2' => 'MIỄN PHÍ VẬN CHUYỂN TOÀN QUỐC CHO ĐƠN HÀNG TỪ 500K!',
        'ANNOUNCEMENT_3' => 'KHÁM PHÁ BỘ SƯU TẬP THỜI TRANG & PHỤ KIỆN HOT NHẤT!',
        
        'NAV_HOME' => 'TRANG CHỦ',
        'NAV_ALL' => 'TẤT CẢ SẢN PHẨM',
        'NAV_CLOTHING' => 'QUẦN ÁO',
        'NAV_ACCESSORIES' => 'PHỤ KIỆN',
        'NAV_TOYS' => 'ĐỒ CHƠI & GAME',
        'NAV_CART' => 'GIỎ HÀNG',
        'NAV_PROFILE' => 'HỒ SƠ CÁ NHÂN',
        
        'HERO_TITLE' => 'KHÁM PHÁ PHONG CÁCH & THỜI TRANG ĐỘC ĐÁO',
        'HERO_SUBTITLE' => 'Trang phục cao cấp, phụ kiện thời thượng và đồ chơi sưu tầm độc quyền.',
        'HERO_BTN' => 'MUA SẮM NGAY',
        
        'FEATURED_TITLE' => 'SẢN PHẨM NỔI BẬT',
        'VIEW_ALL_BTN' => 'XEM TẤT CẢ SẢN PHẨM & BỘ LỌC',
        'QUICK_VIEW' => 'XEM NHANH',
        
        'COLLECTION_BANNER_TITLE' => 'BỘ SƯU TẬP THỜI TRANG & PHỤ KIỆN',
        'BREADCRUMB_HOME' => 'Trang chủ',
        'BREADCRUMB_PRODUCTS' => 'Sản phẩm',
        
        'FILTER_TYPE' => 'LOẠI SẢN PHẨM',
        'FILTER_STYLE' => 'PHONG CÁCH',
        'FILTER_THEME' => 'CHỦ ĐỀ & THƯƠNG HIỆU',
        'SHOWING' => 'Hiển thị',
        'PRODUCTS_LABEL' => 'sản phẩm',
        'CLEAR_FILTER' => 'Xóa bộ lọc',
        'SORT_LABEL' => 'Sắp xếp:',
        'SORT_NEWEST' => 'Mới nhất',
        'SORT_PRICE_ASC' => 'Giá: Thấp đến Cao',
        'SORT_PRICE_DESC' => 'Giá: Cao đến Thấp',
        
        'ADD_TO_CART' => 'THÊM VÀO GIỎ HÀNG',
        'SEARCH_PLACEHOLDER' => 'Tìm kiếm sản phẩm...',
        'LOGIN' => 'Đăng nhập',
        'PROFILE' => 'Hồ sơ cá nhân',
        'CART' => 'Giỏ hàng',
        
        'FOOTER_SHOP' => 'MUA SẮM',
        'FOOTER_SUPPORT' => 'HỖ TRỢ',
        'FOOTER_NEWSLETTER' => 'KẾT NỐI VỚI CHÚNG TÔI',
        'FOOTER_SUBSCRIBE' => 'ĐĂNG KÝ',
    ],
    'US' => [
        'SITE_TITLE' => 'PixelGear | Exclusive Fashion, Accessories & Toys Store',
        'ANNOUNCEMENT_1' => 'SIGN UP FOR OUR NEWSLETTER & SAVE 15% ON YOUR FIRST ORDER!',
        'ANNOUNCEMENT_2' => 'FREE SHIPPING ON ALL US ORDERS OVER $50!',
        'ANNOUNCEMENT_3' => 'DISCOVER THE LATEST EXCLUSIVE GEAR & APPAREL COLLECTION!',
        
        'NAV_HOME' => 'HOME',
        'NAV_ALL' => 'ALL PRODUCTS',
        'NAV_CLOTHING' => 'CLOTHING',
        'NAV_ACCESSORIES' => 'ACCESSORIES',
        'NAV_TOYS' => 'TOYS & GAMES',
        'NAV_CART' => 'CART',
        'NAV_PROFILE' => 'PROFILE',
        
        'HERO_TITLE' => 'DISCOVER UNIQUE STYLE & PREMIUM GEAR',
        'HERO_SUBTITLE' => 'Explore premium apparel, trendy accessories, and collectible gear.',
        'HERO_BTN' => 'SHOP NOW',
        
        'FEATURED_TITLE' => 'FEATURED PRODUCTS',
        'VIEW_ALL_BTN' => 'VIEW ALL PRODUCTS & FILTERS',
        'QUICK_VIEW' => 'QUICK VIEW',
        
        'COLLECTION_BANNER_TITLE' => 'CLOTHING & ACCESSORIES COLLECTION',
        'BREADCRUMB_HOME' => 'Home',
        'BREADCRUMB_PRODUCTS' => 'Products',
        
        'FILTER_TYPE' => 'PRODUCT TYPE',
        'FILTER_STYLE' => 'STYLE',
        'FILTER_THEME' => 'THEME & BRAND',
        'SHOWING' => 'Showing',
        'PRODUCTS_LABEL' => 'products',
        'CLEAR_FILTER' => 'Clear filter',
        'SORT_LABEL' => 'Sort by:',
        'SORT_NEWEST' => 'Newest Arrivals',
        'SORT_PRICE_ASC' => 'Price: Low to High',
        'SORT_PRICE_DESC' => 'Price: High to Low',
        
        'ADD_TO_CART' => 'ADD TO CART',
        'SEARCH_PLACEHOLDER' => 'Search products...',
        'LOGIN' => 'Sign In',
        'PROFILE' => 'My Account',
        'CART' => 'Cart',
        
        'FOOTER_SHOP' => 'SHOP',
        'FOOTER_SUPPORT' => 'SUPPORT',
        'FOOTER_NEWSLETTER' => 'STAY CONNECTED',
        'FOOTER_SUBSCRIBE' => 'SUBSCRIBE',
    ]
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
