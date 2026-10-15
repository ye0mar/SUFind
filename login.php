<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash_messages.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require($_POST['_csrf'] ?? null);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $result = attempt_login($pdo, $username, $password);
        if ($result['ok']) {
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }
        $error = $result['msg'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — Lost &amp; Found</title>
<link rel="icon" type="image/jpeg" href="<?= logo_url() ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body class="auth-body">

<div class="auth-glow"></div>

<div class="auth-card fade-up">
    <div class="auth-logo-wrap">
        <img src="<?= logo_url() ?>" alt="Logo" class="auth-logo" onerror="this.style.display='none'">
    </div>
    <h1 class="auth-title">Welcome Back</h1>
    <p class="auth-subtitle">Sign in to access the Lost &amp; Found Management System.</p>

    <?php if ($error): ?>
        <div class="flash flash-error"><span><?= e($error) ?></span></div>
    <?php endif; ?>

    <form method="post" class="auth-form" autocomplete="on">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= e($username) ?>" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <div class="password-wrap">
                <input type="password" id="password" name="password" required>
                <button type="button" class="toggle-pass" onclick="togglePassword('password', this)">Show</button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
    </form>

    <div class="auth-foot">
        <a href="<?= BASE_URL ?>/">&larr; Back to home</a>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>