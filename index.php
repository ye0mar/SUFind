<?php
// =========================================================
// LANDING / WELCOME PAGE
// =========================================================
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, go to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lost &amp; Found — Welcome</title>
<link rel="icon" type="image/jpeg" href="<?= logo_url() ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body class="landing-body">

<nav class="landing-nav glass-panel">
    <a href="<?= BASE_URL ?>/" class="brand">
        <img src="<?= logo_url() ?>" alt="Logo" class="brand-logo" onerror="this.style.display='none'">
        <span class="brand-name">Lost &amp; Found</span>
    </a>
    <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-sm">Sign In</a>
</nav>

<section class="hero">
    <div class="hero-glow"></div>
    <div class="hero-content fade-up">
        <span class="hero-eyebrow">Internal Management System</span>
        <h1 class="hero-title">Lost something?<br>Find your way back to it.</h1>
        <p class="hero-desc">A simple, organized way for authorized staff to record, track, and reconnect lost belongings with the people looking for them.</p>
        <div class="hero-actions">
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">Sign In to Continue</a>
        </div>
    </div>
</section>

<section class="features">
    <div class="feature-card fade-up">
        <div class="feature-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </div>
        <h3>Search &amp; Track</h3>
        <p>Quickly locate lost and found items using smart filters and categories.</p>
    </div>
    <div class="feature-card fade-up">
        <div class="feature-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        </div>
        <h3>Match &amp; Return</h3>
        <p>Connect reported items with found ones and manage the return workflow.</p>
    </div>
    <div class="feature-card fade-up">
        <div class="feature-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <h3>Secure &amp; Private</h3>
        <p>Role-based access ensures only authorized staff handle sensitive records.</p>
    </div>
</section>

<footer class="landing-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <img src="<?= logo_url() ?>" alt="" class="footer-logo" onerror="this.style.display='none'">
            <div>
                <div class="footer-title">Lost &amp; Found</div>
                <div class="footer-sub">An internal management system.</div>
            </div>
        </div>
        <div class="footer-copy">&copy; <?= date('Y') ?> All rights reserved.</div>
    </div>
</footer>

</body>
</html>