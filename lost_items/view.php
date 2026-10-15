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
$stmt = $pdo->prepare("SELECT l.*, c.name AS category_name, u.full_name AS reporter 
                       FROM lost_items l 
                       LEFT JOIN categories c ON c.id = l.category_id 
                       LEFT JOIN users u ON u.id = l.reported_by 
                       WHERE l.id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    flash_error('Lost item not found.');
    header('Location: ' . BASE_URL . '/lost_items/'); exit;
}

// Suggested matches (simple heuristic)
$suggestions = [];
if ($item['category_id']) {
    $s = $pdo->prepare("SELECT f.*, u.full_name AS recorder 
                        FROM found_items f 
                        LEFT JOIN users u ON u.id = f.recorded_by 
                        WHERE f.category_id = ? AND f.status = 'available' 
                        AND (f.item_name LIKE ? OR f.description LIKE ?) 
                        ORDER BY f.date_found DESC LIMIT 5");
    $like = '%' . $item['item_name'] . '%';
    $s->execute([$item['category_id'], $like, $like]);
    $suggestions = $s->fetchAll();
}

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {
    csrf_require($_POST['_csrf'] ?? null);
    $action = $_POST['action'] ?? '';
    $newStatus = null;
    if ($action === 'set_matched')  $newStatus = 'matched';
    if ($action === 'set_returned') $newStatus = 'returned';
    if ($action === 'set_closed')   $newStatus = 'closed';
    if ($action === 'set_open')     $newStatus = 'open';

    if ($newStatus) {
        $upd = $pdo->prepare("UPDATE lost_items SET status = ? WHERE id = ?");
        $upd->execute([$newStatus, $id]);
        log_activity($pdo, current_user_id(), 'lost_item_status', 'lost_item', $id, "Status → $newStatus");
        flash_success('Status updated.');
        header('Location: ' . BASE_URL . '/lost_items/view.php?id=' . $id);
        exit;
    }

    // Confirm match
    if ($action === 'confirm_match') {
        $foundId = (int)($_POST['found_id'] ?? 0);
        if ($foundId) {
            $pdo->beginTransaction();
            try {
                $ins = $pdo->prepare("INSERT INTO matches (lost_item_id, found_item_id, confirmed_by, notes) VALUES (?, ?, ?, ?)");
                $ins->execute([$id, $foundId, current_user_id(), 'Match confirmed by admin']);
                $pdo->prepare("UPDATE lost_items SET status='matched' WHERE id=?")->execute([$id]);
                $pdo->prepare("UPDATE found_items SET status='matched' WHERE id=?")->execute([$foundId]);
                $pdo->commit();
                log_activity($pdo, current_user_id(), 'match_confirmed', 'match', $id, "Lost #$id ↔ Found #$foundId");
                flash_success('Match confirmed. Both items marked as Matched.');
            } catch (Throwable $e) {
                $pdo->rollBack();
                flash_error('Could not confirm match.');
            }
            header('Location: ' . BASE_URL . '/lost_items/view.php?id=' . $id);
            exit;
        }
    }
}

$pageTitle = 'Lost Item #' . $id;
$activePage = 'lost';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <div class="breadcrumb"><a href="<?= BASE_URL ?>/lost_items/">Lost Items</a> / #<?= $id ?></div>
            <h1 class="page-title"><?= e($item['item_name']) ?> <?= status_badge($item['status']) ?></h1>
            <p class="page-subtitle">Reported on <?= format_datetime($item['created_at']) ?> by <?= e($item['reporter'] ?? '—') ?></p>
        </div>
        <div class="page-actions">
            <a href="<?= BASE_URL ?>/lost_items/edit.php?id=<?= $id ?>" class="btn btn-outline">Edit</a>
        </div>
    </div>

    <?php flash_render(); ?>

    <div class="detail-grid">
        <div class="card">
            <div class="card-head"><h2 class="card-title">Details</h2></div>
            <div class="card-body detail-body">
                <div class="detail-row"><span>Category</span><strong><?= e($item['category_name'] ?? '—') ?></strong></div>
                <div class="detail-row"><span>Date Lost</span><strong><?= format_date($item['date_lost']) ?></strong></div>
                <div class="detail-row"><span>Location Lost</span><strong><?= e($item['location_lost'] ?: '—') ?></strong></div>
                <div class="detail-row"><span>Contact</span><strong><?= e($item['contact_info'] ?: '—') ?></strong></div>
                <div class="detail-row full"><span>Description</span><p><?= nl2br(e($item['description'] ?: '—')) ?></p></div>
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
            <form method="post" class="inline-actions">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="set_open">
                <button type="submit" class="btn btn-ghost">Mark Open</button>
            </form>
            <form method="post" class="inline-actions">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="set_matched">
                <button type="submit" class="btn btn-outline">Mark Matched</button>
            </form>
            <form method="post" class="inline-actions">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="set_returned">
                <button type="submit" class="btn btn-primary">Mark Returned</button>
            </form>
            <form method="post" class="inline-actions">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="set_closed">
                <button type="submit" class="btn btn-ghost">Mark Closed</button>
            </form>
        </div>
    </div>

    <?php if ($suggestions): ?>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Suggested Matches</h2></div>
        <div class="card-body">
            <p class="text-muted small">Review these found items and confirm a match if appropriate.</p>
            <ul class="match-list">
            <?php foreach ($suggestions as $s): ?>
                <li class="match-item">
                    <div>
                        <div class="recent-title"><?= e($s['item_name']) ?></div>
                        <div class="recent-meta">Found <?= format_date($s['date_found']) ?> · <?= e($s['location_found'] ?: '—') ?></div>
                    </div>
                    <form method="post" class="inline-actions">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="confirm_match">
                        <input type="hidden" name="found_id" value="<?= (int)$s['id'] ?>">
                        <a href="<?= BASE_URL ?>/found_items/view.php?id=<?= (int)$s['id'] ?>" class="btn btn-ghost">View</a>
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