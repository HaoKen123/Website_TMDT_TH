document.addEventListener('DOMContentLoaded', () => {
    // Custom Toast Notification System
    function getOrCreateToastContainer() {
        let container = document.getElementById('customToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'customToastContainer';
            container.style.cssText = 'position:fixed; top:20px; right:20px; z-index:99999; display:flex; flex-direction:column; gap:10px; max-width:400px; width:calc(100% - 40px); pointer-events:none;';
            document.body.appendChild(container);
        }
        return container;
    }

    window.showCustomNotice = function(message, type = 'info', duration = 4000) {
        const container = getOrCreateToastContainer();
        const toastEl = document.createElement('div');
        
        let bg = '#0f172a';
        let icon = 'fa-info-circle';
        let border = '#334155';

        if (type === 'success') {
            bg = '#065f46';
            icon = 'fa-check-circle';
            border = '#10b981';
        } else if (type === 'warning') {
            bg = '#9a3412';
            icon = 'fa-exclamation-circle';
            border = '#f97316';
        } else if (type === 'error') {
            bg = '#991b1b';
            icon = 'fa-times-circle';
            border = '#ef4444';
        }

        toastEl.style.cssText = `background:${bg}; color:#ffffff; border-left:4px solid ${border}; padding:14px 18px; border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.2); font-size:14px; font-weight:500; font-family:'Inter', sans-serif; display:flex; align-items:flex-start; gap:12px; opacity:0; transform:translateY(-15px); transition:all 0.3s cubic-bezier(0.16, 1, 0.3, 1); pointer-events:auto; line-height:1.5;`;
        toastEl.innerHTML = `<i class="fas ${icon}" style="font-size:18px; margin-top:2px; flex-shrink:0;"></i><div style="flex:1;">${message}</div><i class="fas fa-times" style="font-size:14px; opacity:0.6; cursor:pointer; margin-top:3px; flex-shrink:0;" onclick="this.parentElement.remove()"></i>`;

        container.appendChild(toastEl);
        
        // Trigger animation
        requestAnimationFrame(() => {
            toastEl.style.opacity = '1';
            toastEl.style.transform = 'translateY(0)';
        });

        setTimeout(() => {
            toastEl.style.opacity = '0';
            toastEl.style.transform = 'translateY(-15px)';
            setTimeout(() => toastEl.remove(), 300);
        }, duration);
    };

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

    addToCartBtns.forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const productId = btn.getAttribute('data-id');

            try {
                const response = await fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: productId })
                });

                const data = await response.json();

                if (data.success) {
                    if (cartCount) cartCount.textContent = data.cart_count;
                    showCustomNotice('Đã thêm sản phẩm vào giỏ hàng thành công!', 'success');
                } else {
                    showCustomNotice(data.message || 'Không thể thêm sản phẩm!', 'error');
                }
            } catch (err) {
                console.error('Fetch error:', err);
                showCustomNotice('Có lỗi xảy ra khi thêm vào giỏ hàng!', 'error');
            }
        });
    });

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
                startHeroSlider();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                nextHeroSlide();
                startHeroSlider();
            });
        }

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
                    qvPrice.textContent = p.price_formatted || `$${parseFloat(p.price).toFixed(2)}`;
                    qvMainImg.src = p.image_url;
                    qvThumb1.src = p.image_url;
                    qvDescription.textContent = p.description || 'Sản phẩm Minecraft chính hãng, chất lượng cao với thiết kế độc quyền.';
                    qvQtyInput.value = 1;

                    quickViewModal.classList.add('active');
                } else {
                    showCustomNotice(data.message, 'error');
                }
            } catch (err) {
                console.error('Lỗi khi tải chi tiết sản phẩm:', err);
                showCustomNotice('Không thể tải chi tiết sản phẩm', 'error');
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

    const sizeBtns = document.querySelectorAll('.size-btn');
    sizeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            sizeBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

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
                    showCustomNotice('Đã thêm sản phẩm vào giỏ hàng!', 'success');
                } else {
                    showCustomNotice(data.message, 'error');
                }
            } catch (err) {
                console.error('Fetch error:', err);
                showCustomNotice('Không thể thêm sản phẩm!', 'error');
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

            if (!email) {
                showCustomNotice('Vui lòng nhập địa chỉ Email!', 'warning');
                return;
            }

            const originalBtnText = submitBtn ? submitBtn.innerText : 'ĐĂNG KÝ';
            if (submitBtn) {
                submitBtn.innerText = 'Đang xử lý...';
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
                    showCustomNotice(data.message, 'info');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else if (data.status === 'already_registered') {
                    showCustomNotice(data.message, 'info', 6000);
                } else if (data.status === 'success') {
                    showCustomNotice(data.message, 'success');
                    if (emailInput) emailInput.value = '';
                } else {
                    showCustomNotice('Lỗi: ' + data.message, 'error');
                }
            } catch (err) {
                showCustomNotice('Có lỗi xảy ra khi xử lý yêu cầu!', 'error');
            } finally {
                if (submitBtn) {
                    submitBtn.innerText = originalBtnText;
                    submitBtn.disabled = false;
                }
            }
        });
    });

    // =========================================================
    // Auto-initialize Minecraft Pixel Logo (from logo_standalone.html)
    // =========================================================
    const FACE = [
        "..GGGG..",
        ".GGGGGG.",
        ".KKGGKK.",
        ".KKGGKK.",
        "GGGGGGGG",
        "GGKKKKGG",
        "GGKKKKGG",
        ".GGGGGG."
    ];
    
    document.querySelectorAll('.mc-logo__icon').forEach(icon => {
        if (icon.children.length === 0) {
            FACE.forEach(row => {
                [...row].forEach(ch => {
                    const p = document.createElement('i');
                    p.className = 'px ' + (ch === 'G' ? 'px-g' : ch === 'K' ? 'px-k' : 'px-e');
                    icon.appendChild(p);
                });
            });
        }
    });

    // Block-break particle burst on hover
    const COLORS = ['#22c55e', '#06b6d4', '#16a34a', '#f9fafb', '#ff3b3b'];
    document.querySelectorAll('.mc-logo').forEach(logo => {
        logo.addEventListener('mouseenter', () => {
            const r = logo.getBoundingClientRect();
            const cx = r.left + r.width * 0.22;
            const cy = r.top + r.height * 0.5;
            for (let i = 0; i < 22; i++) {
                const f = document.createElement('div');
                f.className = 'frag';
                f.style.background = COLORS[i % COLORS.length];
                f.style.left = cx + 'px';
                f.style.top = cy + 'px';
                f.style.boxShadow = '0 0 6px ' + f.style.background;
                document.body.appendChild(f);
                const ang = Math.random() * Math.PI * 2;
                const dist = 30 + Math.random() * 60;
                const dx = Math.cos(ang) * dist;
                const dy = Math.sin(ang) * dist * 0.6 - 20;
                const rot = (Math.random() * 360) | 0;
                f.animate([
                    { transform: 'translate(0,0) rotate(0)', opacity: 1 },
                    { transform: `translate(${dx}px, ${dy + 40}px) rotate(${rot}deg)`, opacity: 0 }
                ], { duration: 600 + Math.random() * 300, easing: 'cubic-bezier(0.16,1,0.3,1)' })
                .onfinish = () => f.remove();
            }
        });
    });
});
