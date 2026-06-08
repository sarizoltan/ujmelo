// ============================================
// BARBER ADMIN – JavaScript
// ============================================

document.addEventListener('DOMContentLoaded', function () {

    // ── Sidebar toggle (mobil) ──
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // ── Alert auto-hide ──
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all .4s ease';
            setTimeout(() => alert.remove(), 400);
        }, 4000);
    });

    // ── Megerősítés törléshez ──
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm || 'Biztosan törölni szeretnéd?')) {
                e.preventDefault();
            }
        });
    });

    // ── Modal kezelés ──
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.modalOpen;
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('open');
        });
    });

    document.querySelectorAll('.modal-close, [data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal-overlay').classList.remove('open');
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });

    // ── Táblázat keresés ──
    const tableSearch = document.getElementById('tableSearch');
    if (tableSearch) {
        tableSearch.addEventListener('input', function () {
            const term = this.value.toLowerCase();
            document.querySelectorAll('.admin-table tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    }

});

// ── Flash üzenet megjelenítés ──
function showFlash(message, type = 'success') {
    const flash = document.createElement('div');
    flash.className = `alert alert-${type}`;
    flash.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
    flash.style.cssText = 'position:fixed;top:80px;right:24px;z-index:9999;min-width:300px;animation:slideIn .3s ease';
    document.body.appendChild(flash);
    setTimeout(() => { flash.style.opacity = '0'; setTimeout(() => flash.remove(), 400); }, 3500);
}