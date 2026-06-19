<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/AnnouncementController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new AnnouncementController($db);
$isEdit = isset($_GET['id']);
$a = $isEdit ? $ctrl->get((int)$_GET['id']) : null;

if ($isEdit && !$a) {
    header('Location: announcement_list.php?error=Not found');
    exit;
}

$pageTitle = $isEdit ? 'Edit Announcement' : 'New Announcement';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<<main class="content">
    <div class="page-header">
        <h1><?= $isEdit ? '✏️ ' . htmlspecialchars($a['title']) : '➕ New Announcement' ?></h1>
    </div>
    
    <div class="card">
        <form method="POST" action="announcement_api.php" class="form-grid">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><?php endif; ?>
            
            <div class="form-group">
                <label>Language</label>
                <select name="lang">
                    <option value="en" <?= ($isEdit && $a['lang'] === 'en') || !$isEdit ? 'selected' : '' ?>>English</option>
                    <option value="fr" <?= ($isEdit && $a['lang'] === 'fr') ? 'selected' : '' ?>>Français</option>
                </select>
            </div>
            <div class="form-group">
                <label>Slug</label>
                <input type="text" name="slug" id="slugField" value="<?= $isEdit ? htmlspecialchars($a['slug']) : '' ?>" placeholder="Auto-generated from title">
                <small>Leave empty to auto-generate</small>
            </div>
            <div class="form-group full-width">
                <label>Title *</label>
                <input type="text" name="title" id="titleField" value="<?= $isEdit ? htmlspecialchars($a['title']) : '' ?>" required maxlength="255" oninput="generateSlug()">
            </div>
            <div class="form-group full-width">
                <label>Description</label>
                <textarea name="description" rows="5"><?= $isEdit ? htmlspecialchars($a['description'] ?? '') : '' ?></textarea>
            </div>
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?= $isEdit ? htmlspecialchars($a['start_date'] ?? '') : '' ?>">
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type="date" name="end_date" value="<?= $isEdit ? htmlspecialchars($a['end_date'] ?? '') : '' ?>">
                <small>Auto-expired after this date</small>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="draft" <?= ($isEdit && $a['status'] === 'draft') || !$isEdit ? 'selected' : '' ?>>Draft</option>
                    <option value="active" <?= ($isEdit && $a['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                </select>
            </div>
            
            <div class="form-actions full-width">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="announcement_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>

<script>
function generateSlug() {
    const title = document.getElementById('titleField').value;
    const slugField = document.getElementById('slugField');
    if (!slugField.value || slugField.dataset.auto === 'true') {
        slugField.value = title.toLowerCase().trim().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').substring(0, 150);
        slugField.dataset.auto = 'true';
    }
}
document.getElementById('slugField').addEventListener('input', function() {
    this.dataset.auto = this.value ? 'false' : 'true';
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>