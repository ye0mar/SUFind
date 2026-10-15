<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
require_once dirname(__DIR__) . '/includes/sidebar.php';

require_role('admin');

$errors = [];
$success = '';

// Create user
if (isset($_POST['create_user'])) {
    csrf_require($_POST['_csrf'] ?? null);
    $full = trim($_POST['full_name'] ?? '');
    $user = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'staff';

    if ($full === '' || $user === '' || $pass === '') $errors[] = 'Full name, username, and password are required.';
    elseif (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
    elseif (!in_array($role, ['admin','staff'])) $errors[] = 'Invalid role.';
    else {
        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $chk->execute([$user, $email ?: null]);
        if ($chk->fetch()) $errors[] = 'Username or email already exists.';
    }

    if (!$errors) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, role) VALUES (?, ?, ?, ?, ?)")
            ->execute([$full, $user, $email ?: null, $hash, $role]);
        $newId = (int)$pdo->lastInsertId();
        log_activity($pdo, current_user_id(), 'user_created', 'user', $newId, "Created $role: $user");
        flash_success("Staff account '$user' created.");
        header('Location: ' . BASE_URL . '/admin/manage_users.php'); exit;
    }
}

// Toggle status
if (isset($_POST['toggle_status'])) {
    csrf_require($_POST['_csrf'] ?? null);
    $uid = (int)$_POST['user_id'];
    $new = $_POST['new_status'] ?? 'active';

    // Prevent deactivating the last admin
    if ($new === 'inactive') {
        $u = $pdo->prepare("SELECT role FROM users WHERE id = ?"); $u->execute([$uid]); $u = $u->fetch();
        if ($u && $u['role'] === 'admin') {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active'")->fetchColumn();
            if ($count <= 1) {
                flash_error('Cannot deactivate the last active administrator.');
                header('Location: ' . BASE_URL . '/admin/manage_users.php'); exit;
            }
        }
    }
    $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new, $uid]);
    log_activity($pdo, current_user_id(), 'user_status', 'user', $uid, "Status → $new");
    flash_success('User status updated.');
    header('Location: ' . BASE_URL . '/admin/manage_users.php'); exit;
}

// Reset password
if (isset($_POST['reset_password'])) {
    csrf_require($_POST['_csrf'] ?? null);
    $uid = (int)$_POST['user_id'];
    $pass = $_POST['new_password'] ?? '';
    if (strlen($pass) < 8) { flash_error('Password must be at least 8 characters.'); }
    else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $uid]);
        log_activity($pdo, current_user_id(), 'user_password_reset', 'user', $uid, 'Password reset');
        flash_success('Password reset successfully.');
    }
    header('Location: ' . BASE_URL . '/admin/manage_users.php'); exit;
}

// Delete user (soft: set inactive, keep records)
if (isset($_POST['delete_user'])) {
    csrf_require($_POST['_csrf'] ?? null);
    $uid = (int)$_POST['user_id'];
    if ($uid === current_user_id()) { flash_error('You cannot delete your own account.'); }
    else {
        $u = $pdo->prepare("SELECT role FROM users WHERE id = ?"); $u->execute([$uid]); $u = $u->fetch();
        if ($u && $u['role'] === 'admin') {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active'")->fetchColumn();
            if ($count <= 1) { flash_error('Cannot delete the last active administrator.'); header('Location: ' . BASE_URL . '/admin/manage_users.php'); exit; }
        }
        $pdo->prepare("UPDATE users SET status='inactive' WHERE id=?")->execute([$uid]);
        log_activity($pdo, current_user_id(), 'user_deleted', 'user', $uid, 'Soft-deleted');
        flash_success('User deactivated.');
    }
    header('Location: ' . BASE_URL . '/admin/manage_users.php'); exit;
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$pageTitle = 'Manage Users';
$activePage = 'users';
?>
<main class="main-content">
    <div class="page-header">
        <div><h1 class="page-title">Manage Users</h1><p class="page-subtitle">Create and manage staff accounts.</p></div>
    </div>

    <?php flash_render(); ?>
    <?php if ($errors): ?><div class="flash flash-error"><span><?= e(implode(' ', $errors)) ?></span></div><?php endif; ?>

    <div class="card">
        <div class="card-head"><h2 class="card-title">Create New Staff Account</h2></div>
        <div class="card-body">
            <form method="post" class="form-grid two-col">
                <?= csrf_field() ?>
                <div class="form-group"><label>Full Name <span class="req">*</span></label><input type="text" name="full_name" required></div>
                <div class="form-group"><label>Username <span class="req">*</span></label><input type="text" name="username" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                <div class="form-group"><label>Role</label>
                    <select name="role"><option value="staff">Staff</option><option value="admin">Administrator</option></select>
                </div>
                <div class="form-group"><label>Password <span class="req">*</span></label><input type="password" name="password" required minlength="8"></div>
                <div class="form-group form-actions"><button type="submit" name="create_user" class="btn btn-primary">Create Account</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="card-title">All Users (<?= count($users) ?>)</h2></div>
        <div class="card-body">
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?= e($u['full_name']) ?></strong></td>
                            <td><?= e($u['username']) ?></td>
                            <td><?= e($u['email'] ?: '—') ?></td>
                            <td><span class="badge badge-blue"><?= e(ucfirst($u['role'])) ?></span></td>
                            <td><?= $u['status'] === 'active' ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-muted">Inactive</span>' ?></td>
                            <td><?= format_datetime($u['last_login']) ?></td>
                            <td><?= format_date($u['created_at']) ?></td>
                            <td class="text-right">
                                <?php if ($u['status'] === 'active'): ?>
                                    <form method="post" class="inline-actions">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <input type="hidden" name="new_status" value="inactive">
                                        <button type="submit" name="toggle_status" class="link-btn">Deactivate</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" class="inline-actions">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <input type="hidden" name="new_status" value="active">
                                        <button type="submit" name="toggle_status" class="link-btn">Activate</button>
                                    </form>
                                <?php endif; ?>
                                <details class="inline-actions">
                                    <summary class="link-btn">More</summary>
                                    <div class="mini-menu">
                                        <form method="post" class="mini-form" onsubmit="return confirm('Reset password for this user?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                            <input type="password" name="new_password" placeholder="New password" minlength="8" required>
                                            <button type="submit" name="reset_password" class="link-btn">Reset Password</button>
                                        </form>
                                        <form method="post" class="mini-form" onsubmit="return confirm('Deactivate this user?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                            <button type="submit" name="delete_user" class="link-btn text-danger">Deactivate</button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>