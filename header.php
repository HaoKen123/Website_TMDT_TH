<?php
require_once 'db.php';
require_once 'lang.php';

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
    <title><?php echo isset($page_title) ? $page_title . ' | ' : ''; ?><?php echo __('SITE_TITLE'); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <h1 class="glitch-title" data-text="<?php echo $current_region === 'VN' ? 'PIXELGEAR' : 'PIXELGEAR'; ?>">
                    PIXELGEAR
                </h1>
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
                <!-- Add Product Button -->
                <a href="admin/add_product.php" class="btn-add-product">
                    <i class="fas fa-plus-circle"></i>
                </a>
                
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
