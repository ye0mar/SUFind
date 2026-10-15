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
$stmt = $pdo->prepare("SELECT * FROM lost_items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) { flash_error('Not found.'); header('Location: ' . BASE_URL . '/lost_items/'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require($_POST['_csrf'] ?? null);
    $itemName   = trim($_POST['item_name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
    $desc       = trim($_POST['description'] ?? '');
    $dateLost   = trim($_POST['date_lost'] ?? '');
    $location   = trim($_POST['location_lost'] ?? '');
    $contact    = trim($_POST['contact_info'] ?? '');

    if ($itemName === '') $errors[] = 'Item name is required.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateLost)) $errors[] = 'Invalid date.';

    $newImage = null;
    if (!empty($_FILES['image']['name'])) {
        try {
            $newImage = handle_upload($_FILES['image']);
        } catch (Throwable $e) { $errors[] = $e->getMessage(); }
    }

    if (!$errors) {
        if ($newImage && $item['image_path']) delete_upload($item['image_path']);
        $imagePath = $newImage ?: $item['image_path'];

        $upd = $pdo->prepare("UPDATE lost_items SET item_name=?, category_id=?, description=?, date_lost=?, location_lost=?, contact_info=?, image_path=? WHERE id=?");
        $upd->execute([$itemName, $categoryId, $desc ?: null, $dateLost, $location ?: null, $contact ?: null, $imagePath, $id]);
        log_activity($pdo, current_user_id(), 'lost_item_updated', 'lost_item', $id, 'Updated record');
        flash_success('Lost item updated.');
        header('Location: ' . BASE_URL . '/lost_items/view.php?id=' . $id);
        exit;
    }
    $item = array_merge($item, $_POST);
}

$categories = get_categories($pdo);
$pageTitle = 'Edit Lost Item';
$activePage = 'lost';
?>
<main class="main-content">
    <div class="page-header">
        <div>
            <div class="breadcrumb"><a href="<?= BASE_URL ?>/lost_items/">Lost Items</a> / Edit #<?= $id ?></div>
            <h1 class="page-title">Edit Lost Item</h1>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="flash flash-error"><span><?= e(implode(' ', $errors)) ?></span></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form-card card">
        <?= csrf_field() ?>
        <div class="form-grid two-col">
            <div class="form-group">
                <label>Item Name <span class="req">*</span></label>
                <input type="text" name="item_name" value="<?= e($item['item_name']) ?>" required maxlength="150">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category_id">
                    <option value="">— Select —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (int)($item['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Date Lost <span class="req">*</span></label>
                <input type="date" name="date_lost" value="<?= e($item['date_lost']) ?>" required>
            </div>
            <div class="form-group">
                <label>Location Lost</label>
                <input type="text" name="location_lost" value="<?= e($item['location_lost'] ?? '') ?>" maxlength="180">
            </div>
            <div class="form-group full">
                <label>Description</label>
                <textarea name="description" rows="4"><?= e($item['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Contact Info</label>
                <input type="text" name="contact_info" value="<?= e($item['contact_info'] ?? '') ?>" maxlength="180">
            </div>
            <div class="form-group">
                <label>Replace Image (optional)</label>
                <input type="file" name="image" accept="image/*">
                <?php if (!empty($item['image_path'])): ?>
                    <div class="small text-muted" style="margin-top:6px">Current: <a href="<?= UPLOAD_URL . e($item['image_path']) ?>" target="_blank">view</a></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-actions">
            <a href="<?= BASE_URL ?>/lost_items/view.php?id=<?= $id ?>" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</main>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>