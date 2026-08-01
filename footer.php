    <!-- Footer -->
    <footer class="site-footer">
        <!-- Top Section: Main Links -->
        <div class="footer-top">
            <div class="container">
                <div class="footer-grid">
                    <div class="footer-brand">
                        <div class="logo">
                            <h2 class="logo-text"><?php echo $current_region === 'VN' ? 'PIXELGEAR' : 'PIXELGEAR'; ?></h2>
                        </div>
                        <p class="footer-tagline"><?php echo __('FOOTER_TAGLINE_VN'); ?></p>
                        <div class="footer-connect">
                            <p><?php echo __('FOOTER_CONNECT'); ?></p>
                            <div class="social-mini">
                                <a href="#" class="social-btn">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-btn">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" class="social-btn">
                                    <i class="fab fa-tiktok"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="footer-column">
                        <h4 class="footer-heading"><?php echo __('FOOTER_SHOP'); ?></h4>
                        <ul class="footer-menu">
                            <li><a href="products.php?category=clothing"><?php echo __('NAV_CLOTHING'); ?></a></li>
                            <li><a href="products.php?category=accessories"><?php echo __('NAV_ACCESSORIES'); ?></a></li>
                            <li><a href="products.php?category=toys"><?php echo __('NAV_TOYS'); ?></a></li>
                            <li><a href="products.php"><?php echo __('NAV_ALL'); ?></a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4 class="footer-heading"><?php echo __('FOOTER_SUPPORT'); ?></h4>
                        <ul class="footer-menu">
                            <li><a href="#">FAQ</a></li>
                            <li><a href="#">Chính Sách Giao Hàng</a></li>
                            <li><a href="#">Hoàn Hàng & Đổi Hàng</a></li>
                            <li><a href="#">Liên Hệ</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4 class="footer-heading"><?php echo __('FOOTER_CONTACT'); ?></h4>
                        <ul class="footer-contact">
                            <li class="footer-contact-item">
                                <i class="fas fa-phone-alt"></i>
                                <a href="tel:19001234"><?php echo __('FOOTER_PHONE'); ?></a>
                            </li>
                            <li class="footer-contact-item">
                                <i class="fas fa-envelope"></i>
                                <a href="mailto:<?php echo __('FOOTER_EMAIL'); ?>"><?php echo __('FOOTER_EMAIL'); ?></a>
                            </li>
                        </ul>
                        <p class="footer-copyright"><?php echo __('FOOTER_COPYRIGHT'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
