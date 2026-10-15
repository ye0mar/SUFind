<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

require_role('admin'); // Home is admin-only per spec

// Real stats
$totalLost     = (int)$pdo->query("SELECT COUNT(*) FROM lost_items")->fetchColumn();
$totalFound    = (int)$pdo->query("SELECT COUNT(*) FROM found_items")->fetchColumn();
$totalMatched  = (int)$pdo->query("SELECT COUNT(*) FROM matches")->fetchColumn();
$totalReturned = (int)$pdo->query("SELECT COUNT(*) FROM lost_items WHERE status='returned'") + 
                 (int)$pdo->query("SELECT COUNT(*) FROM found_items WHERE status='returned'");

$recentLost = $pdo->query("SELECT l.*, c.name AS category_name, u.full_name AS reporter 
                           FROM lost_items l 
                           LEFT JOIN categories c ON c.id = l.category_id 
                           LEFT JOIN users u ON u.id = l.reported_by 
                           ORDER BY l.created_at DESC LIMIT 5")->fetchAll();

$recentFound = $pdo->query("SELECT f.*, c.name AS category_name, u.full_name AS recorder 
                            FROM found_items f 
                            LEFT JOIN categories c ON c.id = f.category_id 
                            LEFT JOIN users u ON u.id = f.recorded_by 
                            ORDER BY f.created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Home';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Welcome back, <?= e($_SESSION['user_name']) ?></h1>
            <p class="page-subtitle">Here's what's happening in the Lost &amp; Found system today.</p>
        </div>
        <div class="page-actions">
            <a href="<?= BASE_URL ?>/lost_items/report.php" class="btn btn-outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                Report Lost
            </a>
            <a href="<?= BASE_URL ?>/found_items/add.php" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Found
            </a>
        </div>
    </div>

    <?php flash_render(); ?>

    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Lost Items</div>
            <div class="stat-value"><?= number_format($totalLost) ?></div>
            <div class="stat-foot">Open reports in the system</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Found Items</div>
            <div class="stat-value"><?= number_format($totalFound) ?></div>
            <div class="stat-foot">Items recorded by staff</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Items Matched</div>
            <div class="stat-value"><?= number_format($totalMatched) ?></div>
            <div class="stat-foot">Lost &amp; found pairs identified</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Items Returned</div>
            <div class="stat-value"><?= number_format($totalReturned) ?></div>
            <div class="stat-foot">Successfully reunited</div>
        </div>
    </section>

    <section class="dash-grid">
        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Recently Reported Lost Items</h2>
                <a href="<?= BASE_URL ?>/lost_items/" class="card-link">View all →</a>
            </div>
            <div class="card-body">
                <?php if (!$recentLost): ?>
                    <div class="empty-state small">No lost items reported yet.</div>
                <?php else: ?>
                    <ul class="recent-list">
                    <?php foreach ($recentLost as $r): ?>
                        <li class="recent-item">
                            <div class="recent-main">
                                <div class="recent-title"><?= e($r['item_name']) ?></div>
                                <div class="recent-meta">
                                    <?= e($r['category_name'] ?? 'Uncategorized') ?> · <?= format_date($r['date_lost']) ?> · <?= e($r['location_lost'] ?: '—') ?>
                                </div>
                            </div>
                            <div class="recent-side">
                                <?= status_badge($r['status']) ?>
                                <a href="<?= BASE_URL ?>/lost_items/view.php?id=<?= (int)$r['id'] ?>" class="recent-link">View</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Recently Added Found Items</h2>
                <a href="<?= BASE_URL ?>/found_items/" class="card-link">View all →</a>
            </div>
            <div class="card-body">
                <?php if (!$recentFound): ?>
                    <div class="empty-state small">No found items recorded yet.</div>
                <?php else: ?>
                    <ul class="recent-list">
                    <?php foreach ($recentFound as $r): ?>
                        <li class="recent-item">
                            <div class="recent-main">
                                <div class="recent-title"><?= e($r['item_name']) ?></div>
                                <div class="recent-meta">
                                    <?= e($r['category_name'] ?? 'Uncategorized') ?> · <?= format_date($r['date_found']) ?> · <?= e($r['location_found'] ?: '—') ?>
                                </div>
                            </div>
                            <div class="recent-side">
                                <?= status_badge($r['status']) ?>
                                <a href="<?= BASE_URL ?>/found_items/view.php?id=<?= (int)$r['id'] ?>" class="recent-link">View</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>