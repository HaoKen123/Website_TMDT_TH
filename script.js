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
    // ROBUST QUICK VIEW MODAL POPUP LOGIC
    // ==========================================
    let currentActiveProductId = null;

    // Helper: Tạo động Quick View Modal nếu trang chưa có
    function getOrCreateQuickViewModal() {
        let modal = document.getElementById('quickViewModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'quickViewModal';
            modal.className = 'quick-view-modal';
            modal.innerHTML = `
                <div class="modal-overlay"></div>
                <div class="modal-container">
                    <button class="modal-close" id="closeQuickView">&times;</button>
                    <div class="modal-body">
                        <div class="modal-left">
                            <div class="modal-thumbnails">
                                <img id="qvThumb1" class="thumb active" src="" alt="Thumbnail">
                            </div>
                            <div class="modal-main-img">
                                <img id="qvMainImg" src="" alt="Product Image">
                            </div>
                        </div>
                        <div class="modal-right">
                            <h2 id="qvTitle" class="qv-title">SẢN PHẨM</h2>
                            <div id="qvPrice" class="qv-price">$0.00</div>
                            
                            <div class="qv-option-group">
                                <label>Size / Kích thước:</label>
                                <div class="qv-sizes">
                                    <span class="size-btn active">Freesize</span>
                                    <span class="size-btn">M</span>
                                    <span class="size-btn">L</span>
                                    <span class="size-btn">XL</span>
                                </div>
                            </div>

                            <div class="qv-option-group">
                                <label>Số lượng (Quantity):</label>
                                <div class="qv-quantity-picker">
                                    <button type="button" id="qvQtyMinus">-</button>
                                    <input type="number" id="qvQtyInput" value="1" min="1">
                                    <button type="button" id="qvQtyPlus">+</button>
                                </div>
                            </div>

                            <div class="qv-shipping-note">
                                <i class="fas fa-truck"></i> Giao hàng tận nơi toàn quốc từ 1-3 ngày.
                            </div>

                            <button id="qvAddToCartBtn" class="btn-add-to-cart-green">THÊM VÀO GIỎ HÀNG</button>

                            <div class="qv-details">
                                <h4>Mô tả sản phẩm:</h4>
                                <p id="qvDescription">Đang tải thông tin chi tiết...</p>
                            </div>
                        </div>
                    </div>
                </div>`;
            document.body.appendChild(modal);
            bindModalEvents(modal);
        }
        return modal;
    }

    function bindModalEvents(modal) {
        const closeBtn = modal.querySelector('#closeQuickView');
        const overlay = modal.querySelector('.modal-overlay');
        const qvQtyMinus = modal.querySelector('#qvQtyMinus');
        const qvQtyPlus = modal.querySelector('#qvQtyPlus');
        const qvQtyInput = modal.querySelector('#qvQtyInput');
        const qvAddToCartBtn = modal.querySelector('#qvAddToCartBtn');

        if (closeBtn) closeBtn.onclick = () => modal.classList.remove('active');
        if (overlay) overlay.onclick = () => modal.classList.remove('active');

        if (qvQtyMinus && qvQtyInput) {
            qvQtyMinus.onclick = () => {
                let val = parseInt(qvQtyInput.value) || 1;
                if (val > 1) qvQtyInput.value = val - 1;
            };
        }

        if (qvQtyPlus && qvQtyInput) {
            qvQtyPlus.onclick = () => {
                let val = parseInt(qvQtyInput.value) || 1;
                qvQtyInput.value = val + 1;
            };
        }

        modal.querySelectorAll('.size-btn').forEach(btn => {
            btn.onclick = () => {
                modal.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            };
        });

        if (qvAddToCartBtn) {
            qvAddToCartBtn.onclick = async () => {
                if (!currentActiveProductId) return;
                const qty = qvQtyInput ? (parseInt(qvQtyInput.value) || 1) : 1;
                const originalText = qvAddToCartBtn.innerText;
                qvAddToCartBtn.innerText = 'Đang thêm...';
                qvAddToCartBtn.disabled = true;

                try {
                    const response = await fetch('add_to_cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: currentActiveProductId, quantity: qty })
                    });
                    const data = await response.json();

                    if (data.success || data.status === 'success') {
                        document.querySelectorAll('.cart-count').forEach(el => {
                            el.textContent = data.cart_count;
                        });
                        modal.classList.remove('active');
                        if (window.showCustomNotice) {
                            showCustomNotice('Đã thêm ' + qty + ' sản phẩm vào giỏ hàng!', 'success');
                        }
                    } else {
                        if (window.showCustomNotice) showCustomNotice(data.message || 'Lỗi thêm giỏ hàng', 'error');
                    }
                } catch (err) {
                    if (window.showCustomNotice) showCustomNotice('Không thể kết nối đến máy chủ!', 'error');
                } finally {
                    qvAddToCartBtn.innerText = originalText;
                    qvAddToCartBtn.disabled = false;
                }
            };
        }
    }

    // Khởi tạo các sự kiện cho modal có sẵn trên trang
    const existingModal = document.getElementById('quickViewModal');
    if (existingModal) {
        bindModalEvents(existingModal);
    }

    // Hàm mở Quick View toàn cục (Global)
    window.openQuickView = async function(productId) {
        if (!productId) return;
        currentActiveProductId = productId;
        const modal = getOrCreateQuickViewModal();

        const qvTitle = modal.querySelector('#qvTitle');
        const qvPrice = modal.querySelector('#qvPrice');
        const qvMainImg = modal.querySelector('#qvMainImg');
        const qvThumb1 = modal.querySelector('#qvThumb1');
        const qvDescription = modal.querySelector('#qvDescription');
        const qvQtyInput = modal.querySelector('#qvQtyInput');

        if (qvTitle) qvTitle.textContent = 'Đang tải thông tin sản phẩm...';
        if (qvPrice) qvPrice.textContent = '...';
        if (qvDescription) qvDescription.textContent = 'Vui lòng chờ trong giây lát...';
        if (qvQtyInput) qvQtyInput.value = 1;

        modal.classList.add('active');

        try {
            const response = await fetch(`get_product.php?id=${productId}`);
            const data = await response.json();

            if (data.success && data.product) {
                const p = data.product;
                if (qvTitle) qvTitle.textContent = p.name;
                if (qvPrice) qvPrice.textContent = p.price_formatted || `$${parseFloat(p.price).toFixed(2)}`;
                if (qvMainImg) qvMainImg.src = p.image_url;
                if (qvThumb1) qvThumb1.src = p.image_url;
                if (qvDescription) qvDescription.textContent = p.description || 'Sản phẩm chính hãng chất lượng cao với thiết kế độc quyền.';
            } else {
                if (window.showCustomNotice) showCustomNotice(data.message || 'Không tìm thấy sản phẩm', 'error');
                modal.classList.remove('active');
            }
        } catch (err) {
            console.error('Lỗi khi tải chi tiết sản phẩm:', err);
            if (window.showCustomNotice) showCustomNotice('Không thể tải chi tiết sản phẩm!', 'error');
            modal.classList.remove('active');
        }
    };

    // Bắt sự kiện Click Toàn Cục (Event Delegation) cho tất cả các nút Xem Nhanh
    document.addEventListener('click', function(e) {
        const qvBtn = e.target.closest('.btn-quick-view, [data-action="quickview"]');
        if (qvBtn) {
            e.preventDefault();
            e.stopPropagation();
            const id = qvBtn.getAttribute('data-id') || qvBtn.dataset.id;
            if (id) {
                window.openQuickView(id);
            }
        }
    });

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
                    { transform: 'translate(0,0) rotate(0deg) scale(1)', opacity: 1 },
                    { transform: `translate(${dx}px, ${dy}px) rotate(${rot}deg) scale(0.3)`, opacity: 0 }
                ], { duration: 600 + Math.random() * 300, easing: 'cubic-bezier(0, .9, .57, 1)', fill: 'forwards' });
                setTimeout(() => f.remove(), 1000);
            }
        });
    });

    // Global Newsletter Submit Handler
    window.handleNewsletterSubmit = async function(event, form) {
        if (event) event.preventDefault();
        const emailInput = form.querySelector('input[name="email"]');
        const email = emailInput ? emailInput.value.trim() : '';
        if (!email) return false;

        const btn = form.querySelector('button');
        const originalText = btn ? btn.innerText : 'ĐĂNG KÝ';
        if (btn) {
            btn.innerText = 'Đang xử lý...';
            btn.disabled = true;
        }

        try {
            const response = await fetch('subscribe_newsletter.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email)
            });
            const data = await response.json();

            if (data.status === 'success') {
                if (window.showCustomNotice) showCustomNotice(data.message, 'success', 6000);
                if (emailInput) emailInput.value = '';
            } else if (data.status === 'already_registered') {
                if (window.showCustomNotice) showCustomNotice(data.message, 'info', 6000);
            } else if (data.status === 'not_registered') {
                if (window.showCustomNotice) showCustomNotice(data.message, 'info', 4000);
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            } else {
                if (window.showCustomNotice) showCustomNotice('Lỗi: ' + data.message, 'error');
            }
        } catch (err) {
            if (window.showCustomNotice) showCustomNotice('Lỗi kết nối kiểm tra email!', 'error');
        } finally {
            if (btn) {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }
        return false;
    };
});
