<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
require_once dirname(__DIR__) . '/includes/sidebar.php';

require_login();

$q        = trim($_GET['q'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$status   = trim($_GET['status'] ?? '');
$from     = trim($_GET['from'] ?? '');
$to       = trim($_GET['to'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 15;

$where = []; $params = [];
if ($q !== '') {
    $where[] = "(f.item_name LIKE CONCAT('%', ?, '%') OR f.description LIKE CONCAT('%', ?, '%') OR f.location_found LIKE CONCAT('%', ?, '%'))";
    $params[] = $q; $params[] = $q; $params[] = $q;
}
if ($category > 0) { $where[] = "f.category_id = ?"; $params[] = $category; }
if ($status !== '') { $where[] = "f.status = ?"; $params[] = $status; }
if ($from !== '') { $where[] = "f.date_found >= ?"; $params[] = $from; }
if ($to !== '')   { $where[] = "f.date_found <= ?"; $params[] = $to; }

$sql = "SELECT f.*, c.name AS category_name, u.full_name AS recorder 
        FROM found_items f 
        LEFT JOIN categories c ON c.id = f.category_id 
        LEFT JOIN users u ON u.id = f.recorded_by";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY f.created_at DESC";

$countSql = "SELECT COUNT(*) FROM found_items f" . ($where ? " WHERE " . implode(' AND ', $where) : "");
$total = $pdo->prepare($countSql); $total->execute($params); $total = (int)$total->fetchColumn();

[$offset, $pages, $page] = paginate($total, $perPage, $page);
$sql .= " LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();

$categories = get_categories($pdo);
$pageTitle = 'Found Items';
$activePage = 'found';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Found Items</h1>
            <p class="page-subtitle"><?= number_format($total) ?> record<?= $total === 1 ? '' : 's' ?> found.</p>
        </div>
        <div class="page-actions">
            <a href="<?= BASE_URL ?>/found_items/add.php" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Found Item
            </a>
        </div>
    </div>

    <?php flash_render(); ?>

    <div class="card filter-card">
        <form method="get" class="filter-form">
            <div class="filter-row">
                <div class="form-group"><label>Search</label><input type="text" name="q" value="<?= e($q) ?>" placeholder="Item name, description, location..."></div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= $category === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All statuses</option>
                        <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="matched"   <?= $status === 'matched' ? 'selected' : '' ?>>Matched</option>
                        <option value="returned"  <?= $status === 'returned' ? 'selected' : '' ?>>Returned</option>
                        <option value="closed"    <?= $status === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
                <div class="form-group"><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div>
                <div class="form-group"><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="<?= BASE_URL ?>/found_items/" class="btn btn-ghost">Clear</a>
            </div>
        </form>
    </div>

    <div class="card">
        <?php if (!$rows): ?>
            <div class="empty-state">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <h3>No found items found</h3>
                <p>Try adjusting your filters or add a new found item.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Item</th><th>Category</th><th>Date Found</th><th>Location</th><th>Storage</th><th>Recorded By</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><strong><?= e($r['item_name']) ?></strong></td>
                            <td><?= e($r['category_name'] ?? '—') ?></td>
                            <td><?= format_date($r['date_found']) ?></td>
                            <td><?= e($r['location_found'] ?: '—') ?></td>
                            <td><?= e($r['storage_location'] ?: '—') ?></td>
                            <td><?= e($r['recorder'] ?? '—') ?></td>
                            <td><?= status_badge($r['status']) ?></td>
                            <td class="text-right"><a href="<?= BASE_URL ?>/found_items/view.php?id=<?= (int)$r['id'] ?>" class="link-btn">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <a href="?<?= qs(['page' => $i]) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>