document.addEventListener('DOMContentLoaded', () => {
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

    // Add to cart functionality
    const addToCartBtns = document.querySelectorAll('.add-to-cart-quick');
    const cartCount = document.querySelector('.cart-count');
    const toast = document.getElementById('toast');

    addToCartBtns.forEach(btn => {
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

            try {
                const response = await fetch(`get_product.php?id=${productId}`);
                const data = await response.json();

                if (data.success) {
                    const p = data.product;
                    qvTitle.textContent = p.name;
                    qvPrice.textContent = `$${parseFloat(p.price).toFixed(2)}`;
                    qvMainImg.src = p.image_url;
                    qvThumb1.src = p.image_url;
                    qvDescription.textContent = p.description || 'Sản phẩm Minecraft chính hãng, chất lượng cao với thiết kế độc quyền.';
                    qvQtyInput.value = 1;

                    quickViewModal.classList.add('active');
                } else {
                    alert('Lỗi: ' + data.message);
                }
            } catch (err) {
                console.error('Lỗi khi tải chi tiết sản phẩm:', err);
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
