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
$stmt = $pdo->prepare("SELECT * FROM found_items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) { flash_error('Not found.'); header('Location: ' . BASE_URL . '/found_items/'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require($_POST['_csrf'] ?? null);
    $itemName   = trim($_POST['item_name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
    $desc       = trim($_POST['description'] ?? '');
    $dateFound  = trim($_POST['date_found'] ?? '');
    $locFound   = trim($_POST['location_found'] ?? '');
    $storage    = trim($_POST['storage_location'] ?? '');
    $notes      = trim($_POST['notes'] ?? '');

    if ($itemName === '') $errors[] = 'Item name is required.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFound)) $errors[] = 'Invalid date.';

    $newImage = null;
    if (!empty($_FILES['image']['name'])) {
        try { $newImage = handle_upload($_FILES['image']); }
        catch (Throwable $e) { $errors[] = $e->getMessage(); }
    }

    if (!$errors) {
        if ($newImage && $item['image_path']) delete_upload($item['image_path']);
        $imagePath = $newImage ?: $item['image_path'];
        $upd = $pdo->prepare("UPDATE found_items SET item_name=?, category_id=?, description=?, date_found=?, location_found=?, storage_location=?, notes=?, image_path=? WHERE id=?");
        $upd->execute([$itemName, $categoryId, $desc ?: null, $dateFound, $locFound ?: null, $storage ?: null, $notes ?: null, $imagePath, $id]);
        log_activity($pdo, current_user_id(), 'found_item_updated', 'found_item', $id, 'Updated');
        flash_success('Found item updated.');
        header('Location: ' . BASE_URL . '/found_items/view.php?id=' . $id); exit;
    }
    $item = array_merge($item, $_POST);
}

$categories = get_categories($pdo);
$pageTitle = 'Edit Found Item';
$activePage = 'found';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <div class="breadcrumb"><a href="<?= BASE_URL ?>/found_items/">Found Items</a> / Edit #<?= $id ?></div>
            <h1 class="page-title">Edit Found Item</h1>
        </div>
    </div>
    <?php if ($errors): ?><div class="flash flash-error"><span><?= e(implode(' ', $errors)) ?></span></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form-card card">
        <?= csrf_field() ?>
        <div class="form-grid two-col">
            <div class="form-group"><label>Item Name <span class="req">*</span></label><input type="text" name="item_name" value="<?= e($item['item_name']) ?>" required></div>
            <div class="form-group">
                <label>Category</label>
                <select name="category_id">
                    <option value="">— Select —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (int)($item['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Date Found <span class="req">*</span></label><input type="date" name="date_found" value="<?= e($item['date_found']) ?>" required></div>
            <div class="form-group"><label>Location Found</label><input type="text" name="location_found" value="<?= e($item['location_found'] ?? '') ?>"></div>
            <div class="form-group"><label>Storage Location</label><input type="text" name="storage_location" value="<?= e($item['storage_location'] ?? '') ?>"></div>
            <div class="form-group"><label>Replace Image</label><input type="file" name="image" accept="image/*"></div>
            <div class="form-group full"><label>Description</label><textarea name="description" rows="3"><?= e($item['description'] ?? '') ?></textarea></div>
            <div class="form-group full"><label>Notes</label><textarea name="notes" rows="2"><?= e($item['notes'] ?? '') ?></textarea></div>
        </div>
        <div class="form-actions">
            <a href="<?= BASE_URL ?>/found_items/view.php?id=<?= $id ?>" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>