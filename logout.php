<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require($_POST['_csrf'] ?? null);
}
logout_user($pdo);
header('Location: ' . BASE_URL . '/login.php');
exit;