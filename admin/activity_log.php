<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
require_once dirname(__DIR__) . '/includes/sidebar.php';

require_role('admin');

$q = trim($_GET['q'] ?? '');
$action = trim($_GET['action'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$where = []; $params = [];
if ($q !== '') { $where[] = "(a.details LIKE CONCAT('%', ?, '%') OR u.full_name LIKE CONCAT('%', ?, '%'))"; $params[] = $q; $params[] = $q; }
if ($action !== '') { $where[] = "a.action = ?"; $params[] = $action; }

$sql = "SELECT a.*, u.full_name AS user_name 
        FROM activity_logs a 
        LEFT JOIN users u ON u.id = a.user_id";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY a.created_at DESC";

$countSql = "SELECT COUNT(*) FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id" . ($where ? " WHERE " . implode(' AND ', $where) : "");
$total = $pdo->prepare($countSql); $total->execute($params); $total = (int)$total->fetchColumn();
[$offset, $pages, $page] = paginate($total, $perPage, $page);
$sql .= " LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();

$pageTitle = 'Activity Log';
$activePage = 'activity';
?>
<main class="main-content">
    <div class="page-header"><div><h1 class="page-title">Activity Log</h1><p class="page-subtitle"><?= number_format($total) ?> events recorded.</p></div></div>
    <?php flash_render(); ?>

    <div class="card filter-card">
        <form method="get" class="filter-form">
            <div class="filter-row">
                <div class="form-group"><label>Search</label><input type="text" name="q" value="<?= e($q) ?>" placeholder="User or details..."></div>
                <div class="form-group"><label>Action</label>
                    <select name="action">
                        <option value="">All actions</option>
                        <?php foreach (['login','logout','user_created','user_status','user_password_reset','lost_item_created','lost_item_updated','lost_item_status','found_item_created','found_item_updated','found_item_status','match_confirmed','settings_updated','backup','restore'] as $a): ?>
                            <option value="<?= $a ?>" <?= $action === $a ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_',' ',$a))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="filter-actions"><button type="submit" class="btn btn-primary">Filter</button><a href="<?= BASE_URL ?>/admin/activity_log.php" class="btn btn-ghost">Clear</a></div>
        </form>
    </div>

    <div class="card">
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Target</th><th>Details</th><th>IP</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= format_datetime($r['created_at']) ?></td>
                            <td><?= e($r['user_name'] ?? 'System') ?></td>
                            <td><span class="badge badge-blue"><?= e(ucfirst(str_replace('_',' ',$r['action']))) ?></span></td>
                            <td><?= e($r['target_type'] ?? '—') ?><?= $r['target_id'] ? ' #' . $r['target_id'] : '' ?></td>
                            <td><?= e($r['details'] ?? '—') ?></td>
                            <td><?= e($r['ip_address'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted">No activity recorded.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pages > 1): ?>
                <div class="pagination" style="padding: 12px 20px;">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <a href="?<?= qs(['page' => $i]) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>