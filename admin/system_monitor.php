<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
require_once dirname(__DIR__) . '/includes/sidebar.php';

require_role('admin');

// Real metrics
$dbStatus = 'Connected';
try { $pdo->query("SELECT 1"); } catch (Throwable $e) { $dbStatus = 'Error: ' . $e->getMessage(); }

$phpVersion = PHP_VERSION;
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown';

$diskTotal = @disk_total_space(BASE_PATH);
$diskFree  = @disk_free_space(BASE_PATH);
$diskUsed  = ($diskTotal && $diskFree) ? $diskTotal - $diskFree : null;

$latest = $pdo->query("SELECT * FROM backup_logs WHERE action='backup' AND status='success' ORDER BY created_at DESC LIMIT 1")->fetch();

$recentErrors = [];
$logFile = ini_get('error_log');
if ($logFile && is_file($logFile) && is_readable($logFile)) {
    $lines = file($logFile);
    $recentErrors = array_slice($lines, -10);
}

$pageTitle = 'System Monitor';
$activePage = 'monitor';
?>
<main class="main-content">
    <div class="page-header"><div><h1 class="page-title">System Monitor</h1><p class="page-subtitle">Real-time health information about the application.</p></div></div>
    <?php flash_render(); ?>

    <section class="stats-grid">
        <div class="stat-card"><div class="stat-label">PHP Version</div><div class="stat-value small"><?= e($phpVersion) ?></div><div class="stat-foot">Current runtime</div></div>
        <div class="stat-card"><div class="stat-label">Database</div><div class="stat-value small"><?= e($dbStatus) ?></div><div class="stat-foot"><?= e(DB_NAME) ?>@<?= e(DB_HOST) ?></div></div>
        <div class="stat-card"><div class="stat-label">Server</div><div class="stat-value small" style="font-size:13px"><?= e($serverSoftware) ?></div><div class="stat-foot">Apache / XAMPP</div></div>
        <div class="stat-card"><div class="stat-label">Latest Backup</div><div class="stat-value small"><?= $latest ? format_datetime($latest['created_at']) : 'None' ?></div><div class="stat-foot"><?= $latest ? e($latest['filename']) : '—' ?></div></div>
    </section>

    <div class="detail-grid">
        <div class="card">
            <div class="card-head"><h2 class="card-title">Disk Space</h2></div>
            <div class="card-body">
                <?php if ($diskTotal): ?>
                    <div class="detail-row"><span>Total</span><strong><?= number_format($diskTotal/1024/1024/1024, 2) ?> GB</strong></div>
                    <div class="detail-row"><span>Used</span><strong><?= number_format($diskUsed/1024/1024/1024, 2) ?> GB</strong></div>
                    <div class="detail-row"><span>Free</span><strong><?= number_format($diskFree/1024/1024/1024, 2) ?> GB</strong></div>
                    <div class="progress"><div class="progress-bar" style="width:<?= round(($diskUsed/$diskTotal)*100) ?>%"></div></div>
                <?php else: ?>
                    <p class="text-muted">Disk information unavailable on this platform.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h2 class="card-title">CPU &amp; RAM</h2></div>
            <div class="card-body">
                <p class="text-muted">Real-time CPU and RAM metrics are not reliably available to PHP on Windows. Open <strong>Windows Task Manager</strong> (Ctrl+Shift+Esc) → Performance tab to monitor live usage.</p>
            </div>
        </div>
    </div>

    <?php if ($recentErrors): ?>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Recent Application Errors</h2></div>
        <div class="card-body">
            <pre class="log-pre"><?php foreach ($recentErrors as $l) echo e($l); ?></pre>
        </div>
    </div>
    <?php endif; ?>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>