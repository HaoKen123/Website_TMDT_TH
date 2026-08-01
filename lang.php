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
         
         'BREADCRUMB_PRODUCTS' => 'Sản phẩm',
        
         'FEATURED_TITLE' => 'SẢN PHẨM NỔI BẬT',
         'BREADCRUMB_PRODUCTS' => 'Sản phẩm',
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
        
        'PRODUCT_DETAIL' => [
            'PRODUCT_DETAIL_TITLE' => 'CHI TIẾT SẢN PHẨM',
            'PRODUCT_BUY_NOW' => 'MUA NGAY',
            'PRODUCT_QUANTITY' => 'Số Lượng',
            'PRODUCT_QUANTITY_LABEL' => 'Lượng',
            'PRODUCT_VIEW_QUICK' => 'XEM NHANH',
            'PRODUCT_ADD_TO_CART' => 'THÊM VÀO GIỎ HÀNG',
            'PRODUCT_TRUST_Genuine' => 'Hàng chính hãng',
            'PRODUCT_TRUST_NewPackaging' => 'Bao bì mới',
            'PRODUCT_TRUST_FastShipping' => 'Giao hàng nhanh',
            'PRODUCT_TRUST_ReturnPolicy' => 'Hoàn tiền 30 ngày',
            'PRODUCT_TRUST_Warranty' => 'Bảo hành 1 năm',
            'PRODUCT_TRUST_FreeShipping' => 'Miễn phí ship',
            'PRODUCT_TRUST_SecurePayment' => 'Thanh toán an toàn',
            'PRODUCT_TRUST_Badge' => 'Chính hãng',
            'PRODUCT_FEATURES' => 'Tính Năng',
            'PRODUCT_DESCRIPTION' => 'Mô Tả',
            'PRODUCT_SPECIFICATIONS' => 'Thông Số Kỹ Thuật',
            'PRODUCT_REVIEWS' => 'Đánh Giá',
            'PRODUCT_STAR' => '★',
            'PRODUCT_VERIFIED' => 'Đã xác minh',
            'PRODUCT_REVIEWS_COUNT' => 'đánh giá',
            'PRODUCT_WRITE_REVIEW' => 'Viết đánh giá của bạn',
            'PRODUCT_SHARE_REVIEW' => 'Chia sẻ đánh giá',
            'PRODUCT_REVIEWS_TITLE' => 'Đánh giá khách hàng',
            'PRODUCT_REVIEWS_OF' => '/ 5.0',
            'PRODUCT_REVIEWS_NAME' => 'Khách hàng',
            'PRODUCT_REVIEWS_DATE' => 'Ngày',
            'PRODUCT_REVIEWS_COMMENT' => 'Bình luận',
            'PRODUCT_REVIEWS_GIVE_REVIEW' => 'Gửi đánh giá',
            'PRODUCT_REVIEWS_STAR' => '★',
        ],
    ],
        
        'FOOTER_SHOP' => 'MUA SẮM',
        'FOOTER_SUPPORT' => 'HỖ TRỢ',
        'FOOTER_NEWSLETTER' => 'KẾT NỐI VỚI CHÚNG TÔI',
        'FOOTER_SUBSCRIBE' => 'ĐĂNG KÝ',
        
        'PRODUCT_DETAIL' => [
            'PRODUCT_DETAIL_TITLE' => 'CHI TIẾT SẢN PHẨM',
            'PRODUCT_BUY_NOW' => 'MUA NGAY',
            'PRODUCT_QUANTITY' => 'Số Lượng',
            'PRODUCT_QUANTITY_LABEL' => 'Lượng',
            'PRODUCT_VIEW_QUICK' => 'XEM NHANH',
            'PRODUCT_ADD_TO_CART' => 'THÊM VÀO GIỎ HÀNG',
            'PRODUCT_TRUST_Genuine' => 'Hàng chính hãng',
            'PRODUCT_TRUST_NewPackaging' => 'Bao bì mới',
            'PRODUCT_TRUST_FastShipping' => 'Giao hàng nhanh',
            'PRODUCT_TRUST_ReturnPolicy' => 'Hoàn tiền 30 ngày',
            'PRODUCT_TRUST_Warranty' => 'Bảo hành 1 năm',
            'PRODUCT_TRUST_FreeShipping' => 'Miễn phí ship',
            'PRODUCT_TRUST_SecurePayment' => 'Thanh toán an toàn',
            'PRODUCT_TRUST_Badge' => 'Chính hãng',
            'PRODUCT_FEATURES' => 'TÍNH NĂNG',
            'PRODUCT_DESCRIPTION' => 'Mô tả',
            'PRODUCT_SPECIFICATIONS' => 'Thông số kỹ thuật',
            'PRODUCT_REVIEWS' => 'Đánh giá',
            'PRODUCT_STAR' => '★',
            'PRODUCT_VERIFIED' => 'Đã xác minh',
            'PRODUCT_REVIEWS_COUNT' => 'đánh giá',
            'PRODUCT_WRITE_REVIEW' => 'Viết đánh giá của bạn',
            'PRODUCT_SHARE_REVIEW' => 'Gửi đánh giá',
            'PRODUCT_REVIEWS_TITLE' => 'Đánh giá khách hàng',
            'PRODUCT_REVIEWS_OF' => '/ 5.0',
             'PRODUCT_REVIEWS_NAME' => 'Người dùng',
             'PRODUCT_REVIEWS_DATE' => 'Ngày',
             'PRODUCT_REVIEWS_COMMENT' => 'Bình luận',
             'PRODUCT_REVIEWS_GIVE_REVIEW' => 'Gửi đánh giá',
             'PRODUCT_REVIEWS_STAR' => '★',
      ],
      'VN' => [
          'SITE_TITLE' => 'PixelGear | Giải Trí & Thời Trang',
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
         
         'BREADCRUMB_PRODUCTS' => 'Products',
        
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
        
        'PRODUCT_DETAIL' => [
            'PRODUCT_DETAIL_TITLE' => 'PRODUCT DETAILS',
            'PRODUCT_BUY_NOW' => 'BUY NOW',
            'PRODUCT_QUANTITY' => 'Quantity',
            'PRODUCT_QUANTITY_LABEL' => 'Qty',
            'PRODUCT_VIEW_QUICK' => 'QUICK VIEW',
            'PRODUCT_ADD_TO_CART' => 'ADD TO CART',
            'PRODUCT_TRUST_Genuine' => 'Genuine Product',
            'PRODUCT_TRUST_NewPackaging' => 'New Packaging',
            'PRODUCT_TRUST_FastShipping' => 'Fast Shipping',
            'PRODUCT_TRUST_ReturnPolicy' => '30-Day Return',
            'PRODUCT_TRUST_Warranty' => '1-Year Warranty',
            'PRODUCT_TRUST_FreeShipping' => 'Free Shipping',
            'PRODUCT_TRUST_SecurePayment' => 'Secure Payment',
            'PRODUCT_TRUST_Badge' => 'Authentic',
            'PRODUCT_FEATURES' => 'Features',
            'PRODUCT_DESCRIPTION' => 'Description',
            'PRODUCT_SPECIFICATIONS' => 'Specifications',
            'PRODUCT_REVIEWS' => 'Reviews',
            'PRODUCT_STAR' => '★',
            'PRODUCT_VERIFIED' => 'Verified',
            'PRODUCT_REVIEWS_COUNT' => 'reviews',
            'PRODUCT_WRITE_REVIEW' => 'Write a Review',
            'PRODUCT_SHARE_REVIEW' => 'Share Review',
            'PRODUCT_REVIEWS_TITLE' => 'Customer Reviews',
            'PRODUCT_REVIEWS_OF' => '/ 5.0',
            'PRODUCT_REVIEWS_NAME' => 'Customer',
            'PRODUCT_REVIEWS_DATE' => 'Date',
            'PRODUCT_REVIEWS_COMMENT' => 'Review',
            'PRODUCT_REVIEWS_GIVE_REVIEW' => 'Give Review',
            'PRODUCT_REVIEWS_STAR' => '★',
            
            'PRODUCTS_ALSO_BUGHT' => 'RELATED PRODUCTS',
            'CUSTOMERS_ALSO_BUGHT' => 'CUSTOMERS ALSO BOUGHT',
            'BENEFITS_TITLE' => 'BENEFITS OF BUYING DIRECTLY',
            'BENEFIT_1' => 'FREE SHIPPING',
            'BENEFIT_1_DESC' => 'Free shipping on all orders over $50',
            'BENEFIT_2' => 'SECURE PAYMENT',
            'BENEFIT_2_DESC' => 'Modern secure payment system, 100% safe',
            'BENEFIT_3' => '30-DAY RETURN',
            'BENEFIT_3_DESC' => 'Flexible return and refund policy',
            'BENEFIT_4' => 'Gift Voucher',
            'BENEFIT_4_DESC' => 'Get discount voucher on first order',
         ],
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
