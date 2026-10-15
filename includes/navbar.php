<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_login();
?>
<header class="topbar glass-panel">
    <div class="topbar-left">
        <button class="icon-btn sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <a href="<?= is_admin() ? BASE_URL . '/dashboard.php' : BASE_URL . '/lost_items/' ?>" class="brand">
            <img src="<?= logo_url() ?>" alt="Logo" class="brand-logo" onerror="this.style.display='none'">
            <span class="brand-name">Lost &amp; Found</span>
        </a>
    </div>
    <div class="topbar-right">
        <div class="profile-dropdown" id="profileDropdown">
            <button class="profile-btn" id="profileBtn" aria-haspopup="true" aria-expanded="false">
                <span class="avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?></span>
                <span class="profile-info">
                    <span class="profile-name"><?= e($_SESSION['user_name'] ?? 'User') ?></span>
                    <span class="profile-role"><?= e(ucfirst($_SESSION['user_role'] ?? 'staff')) ?></span>
                </span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="profile-menu" id="profileMenu" role="menu">
                <a href="<?= BASE_URL ?>/profile/" class="profile-menu-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    My Profile
                </a>
                <form method="post" action="<?= BASE_URL ?>/logout.php" class="profile-menu-item">
                    <?= csrf_field() ?>
                    <button type="submit" class="logout-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<!-- ✅ THIS IS THE MISSING WRAPPER -->
<div class="app-shell">