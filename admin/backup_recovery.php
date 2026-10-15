<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
require_once dirname(__DIR__) . '/includes/sidebar.php';

require_role('admin');

// Create backup
if (isset($_POST['create_backup'])) {
    csrf_require($_POST['_csrf'] ?? null);
    $filename = 'backup_' . date('Ymd_His') . '.sql';
    $path = BACKUP_DIR . $filename;

    $cmd = sprintf(
        'mysqldump -u %s %s %s > %s 2>&1',
        escapeshellarg(DB_USER),
        DB_PASS === '' ? '' : '-p' . escapeshellarg(DB_PASS),
        escapeshellarg(DB_NAME),
        escapeshellarg($path)
    );
    $out = []; $code = 0;
    exec($cmd, $out, $code);

    $size = is_file($path) ? filesize($path) : 0;
    $ok = ($code === 0 && $size > 0);

    $pdo->prepare("INSERT INTO backup_logs (user_id, filename, size_bytes, action, status, notes) VALUES (?, ?, ?, 'backup', ?, ?)")
        ->execute([current_user_id(), $filename, $size, $ok ? 'success' : 'failed', $ok ? 'Manual backup' : implode(' ', $out)]);
    log_activity($pdo, current_user_id(), 'backup', 'backup', null, $ok ? "Created $filename" : 'Backup failed');

    flash_success($ok ? "Backup created: $filename" : 'Backup failed. Check storage folder permissions.');
    header('Location: ' . BASE_URL . '/admin/backup_recovery.php'); exit;
}

// Download backup
if (isset($_GET['download'])) {
    $fn = basename($_GET['download']);
    $path = BACKUP_DIR . $fn;
    if (is_file($path) && preg_match('/^backup_\d{8}_\d{6}\.sql$/', $fn)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $fn . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path); exit;
    }
    flash_error('Invalid backup file.');
    header('Location: ' . BASE_URL . '/admin/backup_recovery.php'); exit;
}

// Restore backup
if (isset($_POST['restore_backup'])) {
    csrf_require($_POST['_csrf'] ?? null);
    $confirm = $_POST['confirm'] ?? '';
    $fn = basename($_POST['filename'] ?? '');
    $path = BACKUP_DIR . $fn;

    if ($confirm !== 'RESTORE' || !is_file($path) || !preg_match('/^backup_\d{8}_\d{6}\.sql$/', $fn)) {
        flash_error('Restore cancelled: invalid file or missing confirmation.');
    } else {
        $cmd = sprintf(
            'mysql -u %s %s %s < %s 2>&1',
            escapeshellarg(DB_USER),
            DB_PASS === '' ? '' : '-p' . escapeshellarg(DB_PASS),
            escapeshellarg(DB_NAME),
            escapeshellarg($path)
        );
        $out = []; $code = 0;
        exec($cmd, $out, $code);
        $ok = ($code === 0);
        $pdo->prepare("INSERT INTO backup_logs (user_id, filename, size_bytes, action, status, notes) VALUES (?, ?, ?, 'restore', ?, ?)")
            ->execute([current_user_id(), $fn, filesize($path), $ok ? 'success' : 'failed', $ok ? 'Manual restore' : implode(' ', $out)]);
        log_activity($pdo, current_user_id(), 'restore', 'backup', null, $ok ? "Restored $fn" : 'Restore failed');
        flash_success($ok ? 'Database restored successfully.' : 'Restore failed. Check logs.');
    }
    header('Location: ' . BASE_URL . '/admin/backup_recovery.php'); exit;
}

$backups = $pdo->query("SELECT * FROM backup_logs ORDER BY created_at DESC LIMIT 20")->fetchAll();
$latest = $pdo->query("SELECT * FROM backup_logs WHERE action='backup' AND status='success' ORDER BY created_at DESC LIMIT 1")->fetch();

$pageTitle = 'Backup & Recovery';
$activePage = 'backup';
?>
<main class="main-content">
    <div class="page-header">
        <div><h1 class="page-title">Backup &amp; Recovery</h1><p class="page-subtitle">Create, download, and restore database backups.</p></div>
    </div>
    <?php flash_render(); ?>

    <div class="stats-grid three">
        <div class="stat-card">
            <div class="stat-label">Latest Backup</div>
            <div class="stat-value small"><?= $latest ? format_datetime($latest['created_at']) : 'None' ?></div>
            <div class="stat-foot"><?= $latest ? e($latest['filename']) : 'No backups yet' ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Backup Folder</div>
            <div class="stat-value small" style="font-size:13px;word-break:break-all"><?= e(BACKUP_DIR) ?></div>
            <div class="stat-foot">Stored outside web root access</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Backups</div>
            <div class="stat-value"><?= count($backups) ?></div>
            <div class="stat-foot">Recent operations</div>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="card-title">Create Backup</h2></div>
        <div class="card-body">
            <p class="text-muted">Generate a full SQL dump of the current database. The file will be stored securely on the server.</p>
            <form method="post">
                <?= csrf_field() ?>
                <button type="submit" name="create_backup" class="btn btn-primary">Create Backup Now</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="card-title">Backup History</h2></div>
        <div class="card-body">
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Date</th><th>File</th><th>Action</th><th>Size</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($backups as $b): ?>
                        <tr>
                            <td><?= format_datetime($b['created_at']) ?></td>
                            <td><?= e($b['filename']) ?></td>
                            <td><span class="badge badge-blue"><?= e(ucfirst($b['action'])) ?></span></td>
                            <td><?= $b['size_bytes'] ? number_format($b['size_bytes']/1024, 1) . ' KB' : '—' ?></td>
                            <td><?= $b['status'] === 'success' ? '<span class="badge badge-success">Success</span>' : '<span class="badge badge-error">Failed</span>' ?></td>
                            <td class="text-right">
                                <?php if ($b['action'] === 'backup' && $b['status'] === 'success'): ?>
                                    <a href="?download=<?= e($b['filename']) ?>" class="link-btn">Download</a>
                                    <form method="post" class="inline-actions" onsubmit="return confirm('RESTORE will overwrite current data. Continue?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="filename" value="<?= e($b['filename']) ?>">
                                        <input type="hidden" name="confirm" value="RESTORE">
                                        <button type="submit" name="restore_backup" class="link-btn text-danger">Restore</button>
                                    </form>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$backups): ?><tr><td colspan="6" class="text-center text-muted">No backups yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>