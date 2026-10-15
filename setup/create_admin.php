<?php
// ============================================================
// One-time Admin Setup Script
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$message = '';
$error = '';
$created = false;

$db = getDB();
$adminCount = $db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();

if ($adminCount > 0) {
    $error = 'An administrator account already exists. This setup page is disabled for security.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $adminCount === 0) {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($full_name === '' || $username === '' || $password === '') {
        $error = 'All required fields must be filled.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username can only contain letters, numbers, and underscores.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (full_name, username, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'admin', 'active')");
            $stmt->execute([$full_name, $username, $email ?: null, $hash]);
            $created = true;
            $message = 'Administrator account created successfully. You can now log in.';
        } catch (Exception $e) {
            $error = 'Failed to create admin: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Administrator — Lost and Found</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/auth.css">
<style>
.setup-container { max-width: 500px; margin: 50px auto; padding: 20px; }
.warning-box { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
</style>
</head>
<body class="auth-page">
<div class="setup-container">
    <h1>Initial Administrator Setup</h1>
    <div class="warning-box">
        <strong>Important:</strong> This page should only be used once to create the first administrator account. After creation, delete or restrict access to this file.
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($created): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <p><a href="../login.php">Go to Login →</a></p>
    <?php else: ?>
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" required maxlength="150">
            </div>
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]+">
            </div>
            <div class="form-group">
                <label>Email (optional)</label>
                <input type="email" name="email" maxlength="150">
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required minlength="6">
                <small>Minimum 6 characters. Choose a strong password.</small>
            </div>
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Create Administrator</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>