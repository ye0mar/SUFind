// =========================================================
// MAIN JS — lightweight interactions
// =========================================================
(function () {
    'use strict';

    // Sidebar toggle (mobile)
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            if (backdrop) backdrop.classList.toggle('open');
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', function () {
            sidebar.classList.remove('open');
            backdrop.classList.remove('open');
        });
    }

    // Profile dropdown
    const btn = document.getElementById('profileBtn');
    const menu = document.getElementById('profileMenu');
    if (btn && menu) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const open = menu.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function (e) {
            if (!menu.contains(e.target) && !btn.contains(e.target)) {
                menu.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Auto-dismiss flash messages after 6s
    document.querySelectorAll('.flash').forEach(function (f) {
        setTimeout(function () {
            f.style.transition = 'opacity .3s';
            f.style.opacity = '0';
            setTimeout(function () { f.remove(); }, 300);
        }, 6000);
    });
})();

// Toggle password visibility (used in login)
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = 'Hide';
    } else {
        input.type = 'password';
        btn.textContent = 'Show';
    }
}