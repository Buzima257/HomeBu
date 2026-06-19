<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/HistoryController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new HistoryController($db);
$isEdit = isset($_GET['id']);
$event = $isEdit ? $ctrl->get((int)$_GET['id']) : null;
$icons = ['star','flag','heart','building','users','award'];

if ($isEdit && !$event) {
    header('Location: history_list.php?error=Not found');
    exit;
}

$pageTitle = $isEdit ? 'Edit Event' : 'New Event';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1><?= $isEdit ? '✏️ ' . htmlspecialchars($event['title']) : '➕ New Historical Event' ?></h1>
    </div>
    
    <div class="card">
        <form method="POST" action="history_api.php" enctype="multipart/form-data" class="form-grid">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$event['id'] ?>"><?php endif; ?>
            
            <div class="form-group">
                <label>Year *</label>
                <input type="number" name="year" value="<?= $isEdit ? (int)$event['year'] : date('Y') ?>" required min="1900" max="<?= date('Y') + 5 ?>">
            </div>
            <div class="form-group">
                <label>Month</label>
                <select name="month">
                    <option value="">—</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($isEdit && $event['month'] == $m) ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Day</label>
                <select name="day">
                    <option value="">—</option>
                    <?php for ($d = 1; $d <= 31; $d++): ?>
                    <option value="<?= $d ?>" <?= ($isEdit && $event['day'] == $d) ? 'selected' : '' ?>><?= $d ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group full-width">
                <label>Title *</label>
                <input type="text" name="title" value="<?= $isEdit ? htmlspecialchars($event['title']) : '' ?>" required maxlength="255">
            </div>
            <div class="form-group full-width">
                <label>Description</label>
                <textarea name="description" rows="4"><?= $isEdit ? htmlspecialchars($event['description'] ?? '') : '' ?></textarea>
            </div>
            <div class="form-group">
                <label>Icon</label>
                <select name="icon">
                    <?php foreach ($icons as $i): ?>
                    <option value="<?= $i ?>" <?= ($isEdit && $event['icon'] === $i) ? 'selected' : '' ?>><?= ucfirst($i) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
           <div class="form-group">
                <label>Image</label>
                <input type="file" name="image_file" id="imageInput" accept="image/jpeg,image/png,image/webp" onchange="previewImage(this)">
                <small style="display:block;margin-top:4px;color:#7f8c8d">Min 100KB, max 5MB</small>
                
                <!-- Preview NOUVELLE -->
                <div id="previewNewImage" style="margin-top:10px;display:none">
                    <p style="font-size:12px;color:#3498db;font-weight:600">New image:</p>
                    <img id="previewNewImageTag" style="max-height:80px;border-radius:6px;border:2px solid #3498db">
                    <span id="imageSizeInfo" style="font-size:12px;color:#555;margin-left:8px"></span>
                </div>
                
                <!-- Image EXISTANTE -->
                <?php if ($isEdit && $event['image']): ?>
                    <div id="existingImage" style="margin-top:10px">
                        <p style="font-size:12px;color:#555;font-weight:600">Current image:</p>
                        <img src="<?= UPLOAD_URL . htmlspecialchars($event['image']) ?>" style="max-height:80px;border-radius:6px;border:1px solid #ddd;display:block">
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" value="<?= $isEdit ? (int)$event['display_order'] : '0' ?>" min="0">
            </div>
            <div class="form-group full-width" style="display:flex;gap:20px">
                <label class="toggle">
                    <input type="checkbox" name="is_major" value="1" <?= ($isEdit && $event['is_major']) ? 'checked' : '' ?>>
                    <span>Major Event (highlighted)</span>
                </label>
                <label class="toggle">
                    <input type="checkbox" name="is_active" value="1" <?= ($isEdit && $event['is_active']) || !$isEdit ? 'checked' : '' ?>>
                    <span>Active</span>
                </label>
            </div>
            
            <div class="form-actions full-width">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="history_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <script>
function previewImage(input) {
    const newDiv = document.getElementById('previewNewImage');
    const newImg = document.getElementById('previewNewImageTag');
    const existing = document.getElementById('existingImage');
    const info = document.getElementById('imageSizeInfo');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const sizeKB = (file.size / 1024).toFixed(1);
        const maxMB = 5;
        const minKB = 100;
        
        let color = '#27ae60';
        let msg = sizeKB + ' KB ✅';
        if (file.size > maxMB * 1024 * 1024) { color = '#e74c3c'; msg = sizeKB + ' KB ❌ Exceeds ' + maxMB + 'MB'; }
        else if (file.size < minKB * 1024) { color = '#e74c3c'; msg = sizeKB + ' KB ❌ Below ' + minKB + 'KB'; }
        
        info.innerHTML = '<span style="color:' + color + '">' + msg + '</span>';
        
        const reader = new FileReader();
        reader.onload = function(e) {
            newImg.src = e.target.result;
            newDiv.style.display = 'block';
            if (existing) existing.style.display = 'none';
        };
        reader.readAsDataURL(file);
    } else {
        newDiv.style.display = 'none';
        if (existing) existing.style.display = 'block';
    }
}
</script>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>