<?php
// =========================================================
// AUTHENTICATION & SESSION HELPERS
// =========================================================
require_once __DIR__ . '/../config/database.php';

// Secure session settings
function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        ini_set('session.cookie_secure', $isSecure ? 1 : 0);
        ini_set('session.cookie_samesite', 'Lax');
        session_name('LFS_SESSION');
        session_start();
        
        // Regenerate session ID periodically (every 15 minutes)
        if (!isset($_SESSION['created_at'])) {
            $_SESSION['created_at'] = time();
        } elseif (time() - $_SESSION['created_at'] > 900) {
            session_regenerate_id(true);
            $_SESSION['created_at'] = time();
        }
    }
}

start_secure_session();

/**
 * Require login. Redirects to login.php if not authenticated.
 */
function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Require a specific role (admin or staff). Admin can do anything.
 */
function require_role($role) {
    require_login();
    if ($role === 'admin' && ($_SESSION['user_role'] ?? '') !== 'admin') {
        http_response_code(403);
        include __DIR__ . '/../includes/header.php';
        echo '<div class="page-container"><div class="empty-state">
              <h2>Access Denied</h2>
              <p>You do not have permission to view this page.</p>
              <a href="' . (is_admin() ? BASE_URL . '/dashboard.php' : BASE_URL . '/lost_items/') . '" class="btn btn-primary">Go to Main Page</a>
              </div></div>';
        include __DIR__ . '/../includes/footer.php';
        exit;
    }
}

/**
 * Check if current user is admin.
 */
function is_admin() {
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * Get current user id.
 */
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Log the user in. Returns true on success.
 */
function attempt_login(PDO $pdo, string $username, string $password): array {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['ok' => false, 'msg' => 'Invalid username or password.'];
    }
    if ($user['status'] !== 'active') {
        return ['ok' => false, 'msg' => 'This account is inactive. Contact an administrator.'];
    }
    if (!password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'msg' => 'Invalid username or password.'];
    }

    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_email'] = $user['email'];

    // Update last login
    $upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $upd->execute([$user['id']]);

    // Log activity
    log_activity($pdo, $user['id'], 'login', 'user', $user['id'], 'User logged in');

    return ['ok' => true];
}

/**
 * Log out the current user.
 */
function logout_user(PDO $pdo) {
    if (!empty($_SESSION['user_id'])) {
        log_activity($pdo, $_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id'], 'User logged out');
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
}

/**
 * Log an activity entry.
 */
function log_activity(PDO $pdo, ?int $user_id, string $action, ?string $target_type = null, ?int $target_id = null, ?string $details = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, target_type, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $target_type, $target_id, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) {
        // Silently fail — do not break the app
    }
}