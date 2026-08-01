document.addEventListener('DOMContentLoaded', () => {
    // Region switcher button handler
    const regionBtn = document.querySelector('.region-btn');
    const regionDropdown = document.querySelector('.region-dropdown');
    
    if (regionBtn && regionDropdown) {
        regionBtn.addEventListener('click', (e) => {
            e.preventDefault();
            regionDropdown.classList.toggle('show');
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!regionBtn.contains(e.target) && !regionDropdown.contains(e.target)) {
            regionDropdown.classList.remove('show');
        }
    });
    
    // Close dropdown when clicking on container
    const regionSwitcherContainer = document.querySelector('.region-switcher-container');
    if (regionSwitcherContainer) {
        regionSwitcherContainer.addEventListener('click', (e) => {
            if (e.target === regionSwitcherContainer) {
                regionDropdown.classList.remove('show');
            }
        });
    }
    
    // Progressive Image Loading - Prevents blur
    const productImages = document.querySelectorAll('.product-image-lazy');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
                imageObserver.unobserve(img);
            }
        });
    }, {
        root: null,
        rootMargin: '100px',
        threshold: 0.01
    });

    productImages.forEach(img => {
        imageObserver.observe(img);
    });
    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mainNav = document.querySelector('.main-nav');

    if (mobileMenuBtn && mainNav) {
        mobileMenuBtn.addEventListener('click', () => {
            if (mainNav.style.display === 'block') {
                mainNav.style.display = 'none';
            } else {
                mainNav.style.display = 'block';
                mainNav.style.position = 'absolute';
                mainNav.style.top = '100%';
                mainNav.style.left = '0';
                mainNav.style.width = '100%';
                mainNav.style.backgroundColor = '#fff';
                mainNav.style.boxShadow = '0 5px 10px rgba(0,0,0,0.1)';
                mainNav.style.padding = '20px';

                const ul = mainNav.querySelector('ul');
                if (ul) {
                    ul.style.flexDirection = 'column';
                    ul.style.gap = '15px';
                }
            }
        });
    }

    // Add to cart functionality - Product grid buttons
    const productAddToCartBtns = document.querySelectorAll('.product-card .btn-add-to-cart-green.small');
    const cartCount = document.querySelector('.cart-count');
    const toast = document.getElementById('toast');

    productAddToCartBtns.forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();

            const productId = btn.getAttribute('data-id');

            try {
                const response = await fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: productId })
                });

                const data = await response.json();

                if (data.success) {
                    // Update cart count
                    if (cartCount) cartCount.textContent = data.cart_count;
                    // Show toast notification
                    showToast();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            } catch (err) {
                console.error('Fetch error:', err);
            }
        });
    });

    // Add to cart functionality - "Xem tất cả" button
    const viewAllBtn = document.querySelector('.btn-xl');
    if (viewAllBtn) {
        viewAllBtn.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = viewAllBtn.getAttribute('href');
        });
    }
    
    // Heart Button - Add to Cart
    const heartButtons = document.querySelectorAll('.btn-add-to-cart-heart');
    heartButtons.forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            
            const productId = btn.getAttribute('data-id');
            const quantity = 1;
            
            if (!productId) {
                console.error('No product ID found');
                return;
            }
            
            try {
                const response = await fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: productId, quantity: quantity })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update cart count
                    if (cartCount) cartCount.textContent = data.cart_count;
                    
                    // Visual feedback - pulse animation on button
                    btn.style.animation = 'heart-pulse 0.6s ease-in-out';
                    setTimeout(() => {
                        btn.style.animation = '';
                    }, 600);
                    
                    showToast();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            } catch (err) {
                console.error('Heart button error:', err);
                alert('Lỗi khi thêm vào giỏ hàng!');
            }
        });
    });
    
    function showToast() {
        if (toast) {
            toast.classList.add('show');

            // Hide toast after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    }

    // Hero Background Slider
    const heroSection = document.querySelector('.hero');
    if (heroSection) {
        const bgImages = [
            'images/minecraft_temple_bg.png',
            'images/bg2.png',
            'images/bg3.png',
            'images/bg4.png'
        ];
        let currentBgIndex = 0;
        let slideInterval;
        
        function updateHeroBackground() {
            heroSection.style.backgroundImage = `url('${bgImages[currentBgIndex]}')`;
        }

        function nextHeroSlide() {
            currentBgIndex = (currentBgIndex + 1) % bgImages.length;
            updateHeroBackground();
        }

        function prevHeroSlide() {
            currentBgIndex = (currentBgIndex - 1 + bgImages.length) % bgImages.length;
            updateHeroBackground();
        }
        
        function startHeroSlider() {
            clearInterval(slideInterval);
            slideInterval = setInterval(nextHeroSlide, 5000);
        }

        const prevBtn = document.getElementById('heroPrev');
        const nextBtn = document.getElementById('heroNext');

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                prevHeroSlide();
                startHeroSlider(); // Reset interval
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                nextHeroSlide();
                startHeroSlider(); // Reset interval
            });
        }

        // Start auto slider initially
        startHeroSlider();
    }

    // Announcement Bar Slider
    const slides = document.querySelectorAll('.announcement-slider .slide');
    if (slides.length > 0) {
        let currentSlide = 0;
        setInterval(() => {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }, 4000);
    }

    // ==========================================
    // QUICK VIEW MODAL POPUP LOGIC
    // ==========================================
    const quickViewModal = document.getElementById('quickViewModal');
    const closeQuickView = document.getElementById('closeQuickView');
    const modalOverlay = document.querySelector('.modal-overlay');
    const quickViewBtns = document.querySelectorAll('.btn-quick-view');

    const qvTitle = document.getElementById('qvTitle');
    const qvPrice = document.getElementById('qvPrice');
    const qvMainImg = document.getElementById('qvMainImg');
    const qvThumb1 = document.getElementById('qvThumb1');
    const qvDescription = document.getElementById('qvDescription');
    const qvQtyInput = document.getElementById('qvQtyInput');
    const qvQtyMinus = document.getElementById('qvQtyMinus');
    const qvQtyPlus = document.getElementById('qvQtyPlus');
    const qvAddToCartBtn = document.getElementById('qvAddToCartBtn');

    let currentActiveProductId = null;

    quickViewBtns.forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const productId = btn.getAttribute('data-id');
            currentActiveProductId = productId;

            console.log('🔍 Quick View clicked! Product ID:', productId);
            console.log('🖼️ Modal element:', quickViewModal);
            console.log('📍 Modal active state before:', quickViewModal ? quickViewModal.classList.contains('active') : 'N/A');

            try {
                const response = await fetch(`get_product.php?id=${productId}`);
                const data = await response.json();
                console.log('📦 get_product.php response:', data);

                if (data.success) {
                    const p = data.product;
                    console.log('✅ Product data:', p);
                    qvTitle.textContent = p.name;
                    qvPrice.textContent = `$${parseFloat(p.price).toFixed(2)}`;
                    qvMainImg.src = p.image_url;
                    qvThumb1.src = p.image_url;
                    qvDescription.textContent = p.description || 'Sản phẩm Minecraft chính hãng, chất lượng cao với thiết kế độc quyền.';
                    qvQtyInput.value = 1;

                    console.log('🚀 Opening modal...');
                    console.log('🖼️ Modal classList before:', quickViewModal ? Array.from(quickViewModal.classList) : 'N/A');
                    quickViewModal.classList.add('active');
                    console.log('🖼️ Modal classList after:', quickViewModal ? Array.from(quickViewModal.classList) : 'N/A');
                } else {
                    console.error('❌ Product fetch failed:', data.message);
                    alert('Lỗi: ' + data.message);
                }
            } catch (err) {
                console.error('🔥 Lỗi khi tải chi tiết sản phẩm:', err);
            }
        });
    });

    function hideQuickViewModal() {
        if (quickViewModal) {
            quickViewModal.classList.remove('active');
        }
    }

    if (closeQuickView) closeQuickView.addEventListener('click', hideQuickViewModal);
    if (modalOverlay) modalOverlay.addEventListener('click', hideQuickViewModal);

    // Quantity controls inside modal
    if (qvQtyMinus) {
        qvQtyMinus.addEventListener('click', () => {
            let val = parseInt(qvQtyInput.value) || 1;
            if (val > 1) qvQtyInput.value = val - 1;
        });
    }

    if (qvQtyPlus) {
        qvQtyPlus.addEventListener('click', () => {
            let val = parseInt(qvQtyInput.value) || 1;
            qvQtyInput.value = val + 1;
        });
    }

    // Size selection buttons
    const sizeBtns = document.querySelectorAll('.size-btn');
    sizeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            sizeBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    // Add to cart from Quick View Modal
    if (qvAddToCartBtn) {
        qvAddToCartBtn.addEventListener('click', async () => {
            if (!currentActiveProductId) return;
            const quantity = parseInt(qvQtyInput.value) || 1;

            try {
                const response = await fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: currentActiveProductId, quantity: quantity })
                });

                const data = await response.json();

                if (data.success) {
                    if (cartCount) cartCount.textContent = data.cart_count;
                    hideQuickViewModal();
                    showToast();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            } catch (err) {
                console.error('Fetch error:', err);
            }
        });
    }

    // Newsletter Form Submission Handler
    const newsletterForms = document.querySelectorAll('.newsletter-form');
    newsletterForms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const emailInput = form.querySelector('input[type="email"]');
            const submitBtn = form.querySelector('button');
            const email = emailInput ? emailInput.value.trim() : '';

            if (!email) return;

            const originalBtnText = submitBtn ? submitBtn.innerText : 'ĐĂNG KÝ';
            if (submitBtn) {
                submitBtn.innerText = 'Đang gửi...';
                submitBtn.disabled = true;
            }

            try {
                const res = await fetch('subscribe_newsletter.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'email=' + encodeURIComponent(email)
                });
                const data = await res.json();

                if (data.status === 'not_registered') {
                    alert(data.message);
                    window.location.href = data.redirect;
                } else if (data.status === 'already_registered') {
                    alert('ℹ️ ' + data.message);
                } else if (data.status === 'success') {
                    alert('✅ ' + data.message);
                    if (emailInput) emailInput.value = '';
                } else {
                    alert('⚠️ Lỗi: ' + data.message);
                }
            } catch (err) {
                alert('Có lỗi xảy ra khi kiểm tra đăng ký!');
            } finally {
                if (submitBtn) {
                    submitBtn.innerText = originalBtnText;
                    submitBtn.disabled = false;
                }
            }
        });
    });
});
