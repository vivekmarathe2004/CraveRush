(() => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const revealSelectors = [
        '.hero-swiggy',
        '.stats-grid',
        '.feature-grid',
        '.category-row',
        '.scroll-row',
        '.restaurant-grid',
        '.dish-grid',
        '.cta-band',
        '.app-strip',
        '.banner-row',
        '.form',
        '.footer-grid'
    ];

    const elements = document.querySelectorAll(revealSelectors.join(','));
    elements.forEach((el) => {
        el.classList.add('reveal');
    });

    document.querySelectorAll('.button').forEach((btn) => {
        btn.classList.add('ripple');
    });

    if (!prefersReducedMotion) {
        const observer = new IntersectionObserver(
            (entries, obs) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        obs.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
        );

        elements.forEach((el) => observer.observe(el));
    } else {
        elements.forEach((el) => el.classList.add('is-visible'));
    }

    const navbar = document.querySelector('.navbar');
    const scrollProgress = document.getElementById('scrollProgress');
    const backToTop = document.getElementById('backToTop');

    const updateScrollUI = () => {
        const doc = document.documentElement;
        const scrollTop = window.pageYOffset || doc.scrollTop;
        const maxScroll = doc.scrollHeight - doc.clientHeight;
        const ratio = maxScroll > 0 ? (scrollTop / maxScroll) : 0;

        if (scrollProgress) {
            scrollProgress.style.width = `${Math.min(Math.max(ratio * 100, 0), 100)}%`;
        }
        if (navbar) {
            navbar.classList.toggle('scrolled', scrollTop > 8);
        }
        if (backToTop) {
            backToTop.classList.toggle('is-visible', scrollTop > 480);
        }
    };

    let scrollTicking = false;
    const onScroll = () => {
        if (!scrollTicking) {
            window.requestAnimationFrame(() => {
                updateScrollUI();
                scrollTicking = false;
            });
            scrollTicking = true;
        }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', updateScrollUI);
    updateScrollUI();

    if (backToTop) {
        backToTop.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: prefersReducedMotion ? 'auto' : 'smooth'
            });
        });
    }

    const dragTargets = document.querySelectorAll('.scroll-row, .category-row');
    dragTargets.forEach((row) => {
        let isDown = false;
        let startX = 0;
        let scrollLeft = 0;
        let dragged = false;

        const onPointerDown = (event) => {
            if (event.pointerType === 'mouse' && event.button !== 0) return;
            isDown = true;
            dragged = false;
            startX = event.clientX;
            scrollLeft = row.scrollLeft;
            row.classList.add('dragging');
            if (row.setPointerCapture) {
                row.setPointerCapture(event.pointerId);
            }
        };

        const onPointerMove = (event) => {
            if (!isDown) return;
            const delta = event.clientX - startX;
            if (Math.abs(delta) > 6) {
                dragged = true;
            }
            row.scrollLeft = scrollLeft - delta;
        };

        const onPointerUp = (event) => {
            isDown = false;
            row.classList.remove('dragging');
            if (row.releasePointerCapture) {
                row.releasePointerCapture(event.pointerId);
            }
            setTimeout(() => {
                dragged = false;
            }, 0);
        };

        row.addEventListener('pointerdown', onPointerDown);
        row.addEventListener('pointermove', onPointerMove);
        row.addEventListener('pointerup', onPointerUp);
        row.addEventListener('pointerleave', onPointerUp);
        row.addEventListener('pointercancel', onPointerUp);
        row.addEventListener('click', (event) => {
            if (dragged) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    });

    const cartToggle = document.getElementById('cartToggle');
    const cartOverlay = document.getElementById('cartOverlay');
    const cartClose = document.getElementById('cartClose');

    let cartScrollY = 0;

    const openCart = () => {
        cartScrollY = window.scrollY || document.documentElement.scrollTop || 0;
        document.body.style.top = `-${cartScrollY}px`;
        document.body.classList.add('cart-open', 'scroll-lock');
    };

    const closeCart = () => {
        document.body.classList.remove('cart-open', 'scroll-lock');
        document.body.style.top = '';
        window.scrollTo(0, cartScrollY);
    };

    if (cartToggle) cartToggle.addEventListener('click', openCart);
    if (cartOverlay) cartOverlay.addEventListener('click', closeCart);
    if (cartClose) cartClose.addEventListener('click', closeCart);

    document.addEventListener('click', (event) => {
        if (event.target.matches('.qty-btn')) {
            const wrap = event.target.closest('.qty-control');
            if (!wrap) return;
            const valueEl = wrap.querySelector('.qty-value');
            const input = wrap.querySelector('input[type="hidden"]');
            let value = parseInt(valueEl.textContent, 10) || 1;
            if (event.target.dataset.action === 'dec') {
                value = Math.max(1, value - 1);
            } else {
                value += 1;
            }
            valueEl.textContent = String(value);
            if (input) input.value = value;
        }

        if (event.target.matches('.password-toggle')) {
            const button = event.target;
            const targetId = button.dataset.target;
            const input = targetId ? document.getElementById(targetId) : null;
            if (!input) return;
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            button.textContent = showing ? 'Show' : 'Hide';
        }

        if (event.target.matches('.add-bounce')) {
            event.target.classList.remove('bounce');
            void event.target.offsetWidth;
            event.target.classList.add('bounce');
            const badge = document.querySelector('.cart-badge');
            if (badge) {
                badge.classList.remove('pulse');
                void badge.offsetWidth;
                badge.classList.add('pulse');
            }
        }
    });

    const paymentMethod = document.getElementById('method');
    const paymentLabel = document.getElementById('label');
    const placeholders = {
        UPI: 'name@upi',
        Card: 'Visa **** 4286',
        Wallet: 'Paytm / PhonePe / Amazon Pay'
    };
    const updatePaymentPlaceholder = () => {
        if (!paymentMethod || !paymentLabel) return;
        const value = paymentMethod.value;
        paymentLabel.placeholder = placeholders[value] || 'Payment details';
    };
    if (paymentMethod && paymentLabel) {
        paymentMethod.addEventListener('change', updatePaymentPlaceholder);
        updatePaymentPlaceholder();
    }
})();
