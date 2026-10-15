<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
require_once dirname(__DIR__) . '/includes/sidebar.php';

require_role('admin');

$keys = ['system_name','organization_name','default_status_lost','default_status_found','items_per_page'];
$settings = [];
foreach ($keys as $k) {
    $s = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $s->execute([$k]);
    $settings[$k] = $s->fetchColumn() ?: '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require($_POST['_csrf'] ?? null);
    foreach ($keys as $k) {
        $val = trim($_POST[$k] ?? '');
        $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
            ->execute([$k, $val]);
        $settings[$k] = $val;
    }
    log_activity($pdo, current_user_id(), 'settings_updated', 'settings', null, 'Updated system settings');
    flash_success('Settings saved.');
    header('Location: ' . BASE_URL . '/admin/settings.php'); exit;
}

$pageTitle = 'Settings';
$activePage = 'settings';
?>
<main class="main-content">
    <div class="page-header"><div><h1 class="page-title">Settings</h1><p class="page-subtitle">Configure general system options.</p></div></div>
    <?php flash_render(); ?>

    <form method="post" class="form-card card">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="form-grid two-col">
                <div class="form-group"><label>System Name</label><input type="text" name="system_name" value="<?= e($settings['system_name']) ?>"></div>
                <div class="form-group"><label>Organization Name</label><input type="text" name="organization_name" value="<?= e($settings['organization_name']) ?>"></div>
                <div class="form-group">
                    <label>Default Lost Status</label>
                    <select name="default_status_lost">
                        <?php foreach (['open','matched','returned','closed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $settings['default_status_lost'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Default Found Status</label>
                    <select name="default_status_found">
                        <?php foreach (['available','matched','returned','closed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $settings['default_status_found'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Items per Page</label><input type="number" name="items_per_page" value="<?= e($settings['items_per_page']) ?>" min="5" max="100"></div>
            </div>
            <div class="form-actions"><button type="submit" class="btn btn-primary">Save Settings</button></div>
        </div>
    </form>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>