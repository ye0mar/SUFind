<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
require_once dirname(__DIR__) . '/includes/sidebar.php';

require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT f.*, c.name AS category_name, u.full_name AS recorder 
                       FROM found_items f 
                       LEFT JOIN categories c ON c.id = f.category_id 
                       LEFT JOIN users u ON u.id = f.recorded_by 
                       WHERE f.id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) { flash_error('Not found.'); header('Location: ' . BASE_URL . '/found_items/'); exit; }

// Suggested lost items
$suggestions = [];
if ($item['category_id']) {
    $s = $pdo->prepare("SELECT l.*, u.full_name AS reporter 
                        FROM lost_items l 
                        LEFT JOIN users u ON u.id = l.reported_by 
                        WHERE l.category_id = ? AND l.status = 'open' 
                        AND (l.item_name LIKE ? OR l.description LIKE ?) 
                        ORDER BY l.date_lost DESC LIMIT 5");
    $like = '%' . $item['item_name'] . '%';
    $s->execute([$item['category_id'], $like, $like]);
    $suggestions = $s->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {
    csrf_require($_POST['_csrf'] ?? null);
    $action = $_POST['action'] ?? '';
    $newStatus = null;
    if ($action === 'set_available') $newStatus = 'available';
    if ($action === 'set_matched')   $newStatus = 'matched';
    if ($action === 'set_returned')  $newStatus = 'returned';
    if ($action === 'set_closed')    $newStatus = 'closed';

    if ($newStatus) {
        $pdo->prepare("UPDATE found_items SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
        log_activity($pdo, current_user_id(), 'found_item_status', 'found_item', $id, "Status → $newStatus");
        flash_success('Status updated.');
        header('Location: ' . BASE_URL . '/found_items/view.php?id=' . $id); exit;
    }

    if ($action === 'confirm_match') {
        $lostId = (int)($_POST['lost_id'] ?? 0);
        if ($lostId) {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("INSERT INTO matches (lost_item_id, found_item_id, confirmed_by, notes) VALUES (?, ?, ?, ?)")
                    ->execute([$lostId, $id, current_user_id(), 'Match confirmed']);
                $pdo->prepare("UPDATE lost_items SET status='matched' WHERE id=?")->execute([$lostId]);
                $pdo->prepare("UPDATE found_items SET status='matched' WHERE id=?")->execute([$id]);
                $pdo->commit();
                log_activity($pdo, current_user_id(), 'match_confirmed', 'match', $id, "Lost #$lostId ↔ Found #$id");
                flash_success('Match confirmed.');
            } catch (Throwable $e) {
                $pdo->rollBack();
                flash_error('Could not confirm match.');
            }
            header('Location: ' . BASE_URL . '/found_items/view.php?id=' . $id); exit;
        }
    }
}

$pageTitle = 'Found Item #' . $id;
$activePage = 'found';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <div class="breadcrumb"><a href="<?= BASE_URL ?>/found_items/">Found Items</a> / #<?= $id ?></div>
            <h1 class="page-title"><?= e($item['item_name']) ?> <?= status_badge($item['status']) ?></h1>
            <p class="page-subtitle">Recorded on <?= format_datetime($item['created_at']) ?> by <?= e($item['recorder'] ?? '—') ?></p>
        </div>
        <div class="page-actions">
            <a href="<?= BASE_URL ?>/found_items/edit.php?id=<?= $id ?>" class="btn btn-outline">Edit</a>
        </div>
    </div>

    <?php flash_render(); ?>

    <div class="detail-grid">
        <div class="card">
            <div class="card-head"><h2 class="card-title">Details</h2></div>
            <div class="card-body detail-body">
                <div class="detail-row"><span>Category</span><strong><?= e($item['category_name'] ?? '—') ?></strong></div>
                <div class="detail-row"><span>Date Found</span><strong><?= format_date($item['date_found']) ?></strong></div>
                <div class="detail-row"><span>Location Found</span><strong><?= e($item['location_found'] ?: '—') ?></strong></div>
                <div class="detail-row"><span>Storage Location</span><strong><?= e($item['storage_location'] ?: '—') ?></strong></div>
                <div class="detail-row full"><span>Description</span><p><?= nl2br(e($item['description'] ?: '—')) ?></p></div>
                <div class="detail-row full"><span>Notes</span><p><?= nl2br(e($item['notes'] ?: '—')) ?></p></div>
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h2 class="card-title">Image</h2></div>
            <div class="card-body">
                <?php if ($item['image_path']): ?>
                    <img src="<?= UPLOAD_URL . e($item['image_path']) ?>" alt="" class="detail-image">
                <?php else: ?>
                    <div class="empty-state small">No image uploaded.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (is_admin()): ?>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Update Status</h2></div>
        <div class="card-body">
            <?php foreach (['available','matched','returned','closed'] as $s): ?>
            <form method="post" class="inline-actions">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="set_<?= $s ?>">
                <button type="submit" class="btn btn-<?= $s === 'returned' ? 'primary' : 'outline' ?>">Mark <?= ucfirst($s) ?></button>
            </form>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($suggestions): ?>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Possible Lost Items</h2></div>
        <div class="card-body">
            <ul class="match-list">
            <?php foreach ($suggestions as $s): ?>
                <li class="match-item">
                    <div>
                        <div class="recent-title"><?= e($s['item_name']) ?></div>
                        <div class="recent-meta">Lost <?= format_date($s['date_lost']) ?> · <?= e($s['location_lost'] ?: '—') ?></div>
                    </div>
                    <form method="post" class="inline-actions">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="confirm_match">
                        <input type="hidden" name="lost_id" value="<?= (int)$s['id'] ?>">
                        <a href="<?= BASE_URL ?>/lost_items/view.php?id=<?= (int)$s['id'] ?>" class="btn btn-ghost">View</a>
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm this match?')">Confirm Match</button>
                    </form>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>