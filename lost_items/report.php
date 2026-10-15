<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
require_once dirname(__DIR__) . '/includes/sidebar.php';

require_login();

$errors = [];
$old = [
    'item_name' => '', 'category_id' => '', 'description' => '',
    'date_lost' => '', 'location_lost' => '', 'contact_info' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require($_POST['_csrf'] ?? null);
    $old = array_merge($old, array_map('trim', $_POST));

    if ($old['item_name'] === '') $errors[] = 'Item name is required.';
    if ($old['date_lost'] === '') $errors[] = 'Date lost is required.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $old['date_lost'])) $errors[] = 'Invalid date format.';

    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        try {
            $imagePath = handle_upload($_FILES['image']);
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO lost_items (item_name, category_id, description, date_lost, location_lost, contact_info, image_path, reported_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $old['item_name'],
            $old['category_id'] ?: null,
            $old['description'] ?: null,
            $old['date_lost'],
            $old['location_lost'] ?: null,
            $old['contact_info'] ?: null,
            $imagePath,
            current_user_id()
        ]);
        $id = (int)$pdo->lastInsertId();
        log_activity($pdo, current_user_id(), 'lost_item_created', 'lost_item', $id, 'Reported: ' . $old['item_name']);
        flash_success('Lost item reported successfully.');
        header('Location: ' . BASE_URL . '/lost_items/view.php?id=' . $id);
        exit;
    }
}

$categories = get_categories($pdo);
$pageTitle = 'Report Lost Item';
$activePage = 'report-lost';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Report a Lost Item</h1>
            <p class="page-subtitle">Provide as much detail as possible to help locate the item.</p>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="flash flash-error"><span><?= e(implode(' ', $errors)) ?></span></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form-card card">
        <?= csrf_field() ?>
        <div class="form-grid two-col">
            <div class="form-group">
                <label for="item_name">Item Name <span class="req">*</span></label>
                <input type="text" id="item_name" name="item_name" value="<?= e($old['item_name']) ?>" required maxlength="150">
            </div>
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="">— Select —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (string)$old['category_id'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="date_lost">Date Lost <span class="req">*</span></label>
                <input type="date" id="date_lost" name="date_lost" value="<?= e($old['date_lost']) ?>" required>
            </div>
            <div class="form-group">
                <label for="location_lost">Location Lost</label>
                <input type="text" id="location_lost" name="location_lost" value="<?= e($old['location_lost']) ?>" maxlength="180" placeholder="e.g. Library, 2nd floor">
            </div>
            <div class="form-group full">
                <label for="description">Description &amp; Identifying Details</label>
                <textarea id="description" name="description" rows="4" maxlength="2000"><?= e($old['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label for="contact_info">Contact Information</label>
                <input type="text" id="contact_info" name="contact_info" value="<?= e($old['contact_info']) ?>" maxlength="180" placeholder="Phone or email">
            </div>
            <div class="form-group">
                <label for="image">Image (optional, max 4 MB)</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
        </div>
        <div class="form-actions">
            <a href="<?= BASE_URL ?>/lost_items/" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Submit Report</button>
        </div>
    </form>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>