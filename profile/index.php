<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
require_once dirname(__DIR__) . '/includes/sidebar.php';

require_login();

$uid = current_user_id();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

$errors = []; $success = '';

// Update profile
if (isset($_POST['update_profile'])) {
    csrf_require($_POST['_csrf'] ?? null);
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    if ($fullName === '') $errors[] = 'Full name is required.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';

    if (!$errors) {
        $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?")->execute([$fullName, $email ?: null, $uid]);
        $_SESSION['user_name'] = $fullName;
        $_SESSION['user_email'] = $email;
        log_activity($pdo, $uid, 'profile_updated', 'user', $uid, 'Updated profile');
        flash_success('Profile updated.');
        header('Location: ' . BASE_URL . '/profile/'); exit;
    }
}

// Change password
if (isset($_POST['change_password'])) {
    csrf_require($_POST['_csrf'] ?? null);
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password_hash'])) $errors[] = 'Current password is incorrect.';
    elseif (strlen($new) < 8) $errors[] = 'New password must be at least 8 characters.';
    elseif ($new !== $confirm) $errors[] = 'Passwords do not match.';
    else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $uid]);
        log_activity($pdo, $uid, 'password_changed', 'user', $uid, 'Changed password');
        flash_success('Password changed successfully.');
        header('Location: ' . BASE_URL . '/profile/'); exit;
    }
}

$pageTitle = 'My Profile';
$activePage = 'profile';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">My Profile</h1>
            <p class="page-subtitle">Manage your personal information and password.</p>
        </div>
    </div>

    <?php flash_render(); ?>
    <?php if ($errors): ?><div class="flash flash-error"><span><?= e(implode(' ', $errors)) ?></span></div><?php endif; ?>

    <div class="detail-grid">
        <div class="card">
            <div class="card-head"><h2 class="card-title">Account Information</h2></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required></div>
                    <div class="form-group"><label>Username</label><input type="text" value="<?= e($user['username']) ?>" disabled></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($user['email'] ?? '') ?>"></div>
                    <div class="form-group"><label>Role</label><input type="text" value="<?= e(ucfirst($user['role'])) ?>" disabled></div>
                    <div class="form-group"><label>Last Login</label><input type="text" value="<?= format_datetime($user['last_login']) ?>" disabled></div>
                    <div class="form-actions"><button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button></div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2 class="card-title">Change Password</h2></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
                    <div class="form-group"><label>New Password</label><input type="password" name="new_password" required minlength="8"></div>
                    <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" required></div>
                    <div class="form-actions"><button type="submit" name="change_password" class="btn btn-primary">Update Password</button></div>
                </form>
            </div>
        </div>
    </div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>