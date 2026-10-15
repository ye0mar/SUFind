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
    'date_found' => '', 'location_found' => '', 'storage_location' => '', 'notes' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require($_POST['_csrf'] ?? null);
    $old = array_merge($old, array_map('trim', $_POST));

    if ($old['item_name'] === '') $errors[] = 'Item name is required.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $old['date_found'])) $errors[] = 'Invalid date.';

    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        try { $imagePath = handle_upload($_FILES['image']); }
        catch (Throwable $e) { $errors[] = $e->getMessage(); }
    }

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO found_items (item_name, category_id, description, date_found, location_found, storage_location, notes, image_path, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $old['item_name'], $old['category_id'] ?: null, $old['description'] ?: null,
            $old['date_found'], $old['location_found'] ?: null, $old['storage_location'] ?: null,
            $old['notes'] ?: null, $imagePath, current_user_id()
        ]);
        $id = (int)$pdo->lastInsertId();
        log_activity($pdo, current_user_id(), 'found_item_created', 'found_item', $id, 'Added: ' . $old['item_name']);
        flash_success('Found item added successfully.');
        header('Location: ' . BASE_URL . '/found_items/view.php?id=' . $id);
        exit;
    }
}

$categories = get_categories($pdo);
$pageTitle = 'Add Found Item';
$activePage = 'add-found';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Add a Found Item</h1>
            <p class="page-subtitle">Record details so the owner can be located.</p>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="flash flash-error"><span><?= e(implode(' ', $errors)) ?></span></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form-card card">
        <?= csrf_field() ?>
        <div class="form-grid two-col">
            <div class="form-group"><label>Item Name <span class="req">*</span></label><input type="text" name="item_name" value="<?= e($old['item_name']) ?>" required maxlength="150"></div>
            <div class="form-group">
                <label>Category</label>
                <select name="category_id">
                    <option value="">— Select —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (string)$old['category_id'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Date Found <span class="req">*</span></label><input type="date" name="date_found" value="<?= e($old['date_found']) ?>" required></div>
            <div class="form-group"><label>Location Found</label><input type="text" name="location_found" value="<?= e($old['location_found']) ?>" maxlength="180"></div>
            <div class="form-group"><label>Storage Location</label><input type="text" name="storage_location" value="<?= e($old['storage_location']) ?>" maxlength="180" placeholder="e.g. Admin Office, Cabinet 3"></div>
            <div class="form-group"><label>Image (optional, max 4 MB)</label><input type="file" name="image" accept="image/*"></div>
            <div class="form-group full"><label>Description</label><textarea name="description" rows="3"><?= e($old['description']) ?></textarea></div>
            <div class="form-group full"><label>Additional Notes</label><textarea name="notes" rows="2"><?= e($old['notes']) ?></textarea></div>
        </div>
        <div class="form-actions">
            <a href="<?= BASE_URL ?>/found_items/" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Found Item</button>
        </div>
    </form>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>