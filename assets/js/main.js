// ============================================
// KOZMETIKAI SZALON – Frontend JavaScript
// ============================================

document.addEventListener('DOMContentLoaded', function () {

    // ── Header scroll ──
    const header = document.getElementById('siteHeader');
    if (header) {
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

    // ── Mobil navigáció ──
    const navToggle = document.getElementById('navToggle');
    const mainNav   = document.getElementById('mainNav');
    if (navToggle && mainNav) {
        navToggle.addEventListener('click', () => {
            navToggle.classList.toggle('open');
            mainNav.classList.toggle('open');
            document.body.style.overflow = mainNav.classList.contains('open') ? 'hidden' : '';
        });
        // Menüpont kattintásra zárás
        mainNav.querySelectorAll('a').forEach(a => {
            a.addEventListener('click', () => {
                navToggle.classList.remove('open');
                mainNav.classList.remove('open');
                document.body.style.overflow = '';
            });
        });
    }

    // ── Scroll reveal animáció ──
    const revealEls = document.querySelectorAll('.reveal');
    if (revealEls.length) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        revealEls.forEach(el => observer.observe(el));
    }

    // ── Előtte-utána képes összehasonlító ──
    if (typeof window.initImageComparison === 'function') {
        window.initImageComparison();
    }

    // ── Smooth scroll belső linkekhez ──
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                const offset = 96;
                window.scrollTo({ top: target.offsetTop - offset, behavior: 'smooth' });
            }
        });
    });

    // ── Kapcsolati form AJAX ──
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn     = this.querySelector('[type=submit]');
            const success = document.getElementById('contactSuccess');
            const error   = document.getElementById('contactError');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Küldés...';
            if (success) success.classList.remove('show');
            if (error)   error.textContent = '';

            const data = {
                name:    this.querySelector('[name=name]').value,
                email:   this.querySelector('[name=email]').value,
                phone:   this.querySelector('[name=phone]')?.value || '',
                message: this.querySelector('[name=message]').value,
            };

            try {
                const res  = await fetch(BASE_URL + '/api/contact.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if (json.success) {
                    if (success) success.classList.add('show');
                    contactForm.reset();
                } else {
                    if (error) error.textContent = json.errors?.join(' ') || 'Hiba történt.';
                }
            } catch {
                if (error) error.textContent = 'Hálózati hiba. Kérjük próbáld újra.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Üzenet küldése';
            }
        });
    }

});

// Globális BASE_URL (PHP-ból injektálva)
const BASE_URL = window._BASE_URL || '';