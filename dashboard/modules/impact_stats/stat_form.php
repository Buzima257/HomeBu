<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/ImpactStatController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new ImpactStatController($db);
$isEdit = isset($_GET['id']);
$s = $isEdit ? $ctrl->get((int)$_GET['id']) : null;

if ($isEdit && !$s) { header('Location: stat_list.php?error=Not found'); exit; }

$pageTitle = $isEdit ? 'Edit Stat' : 'New Stat';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1><?= $isEdit ? '✏️ ' . htmlspecialchars($s['label_en']) : '➕ New Impact Stat' ?></h1>
    </div>
    
    <div class="card">
        <form method="POST" action="stat_api.php" enctype="multipart/form-data" class="form-grid">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><?php endif; ?>
            
            <div class="form-group">
                <label>Label EN *</label>
                <input type="text" name="label_en" value="<?= $isEdit ? htmlspecialchars($s['label_en']) : '' ?>" required maxlength="150" placeholder="People Reached">
            </div>
            <div class="form-group">
                <label>Label FR</label>
                <input type="text" name="label_fr" value="<?= $isEdit ? htmlspecialchars($s['label_fr'] ?? '') : '' ?>" maxlength="150" placeholder="Personnes touchées">
            </div>
            <div class="form-group">
                <label>Value *</label>
                <input type="number" name="value" value="<?= $isEdit ? (int)$s['value'] : '0' ?>" required min="0">
            </div>
            <div class="form-group">
                <label>Suffix EN</label>
                <input type="text" name="suffix_en" value="<?= $isEdit ? htmlspecialchars($s['suffix_en'] ?? '') : '' ?>" maxlength="50" placeholder="+, families, projects">
            </div>
            <div class="form-group">
                <label>Suffix FR</label>
                <input type="text" name="suffix_fr" value="<?= $isEdit ? htmlspecialchars($s['suffix_fr'] ?? '') : '' ?>" maxlength="50">
            </div>
            <div class="form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" value="<?= $isEdit ? (int)$s['display_order'] : '0' ?>" min="0">
            </div>
                        <div class="form-group">
                <label>Icon</label>
                <input type="file" name="icon_file" id="iconInput" accept="image/svg+xml,image/png,image/jpeg" onchange="previewIcon(this)">
                <small style="display:block;margin-top:4px;color:#7f8c8d">SVG or PNG, max 500KB</small>
                
                <!-- Preview NOUVEAU -->
                <div id="previewNewIcon" style="margin-top:10px;display:none">
                    <p style="font-size:12px;color:#3498db;font-weight:600">New icon:</p>
                    <img id="previewNewIconTag" style="height:40px;width:40px;border-radius:4px;border:2px solid #3498db;background:#f8f9fa;padding:4px">
                    <span id="iconSizeInfo" style="font-size:12px;color:#555;margin-left:8px"></span>
                </div>
                
                <!-- Icon EXISTANT -->
                <?php if ($isEdit && $s['icon']): ?>
                    <div id="existingIcon" style="margin-top:10px">
                        <p style="font-size:12px;color:#555;font-weight:600">Current icon:</p>
                        <img src="<?= UPLOAD_URL . htmlspecialchars($s['icon']) ?>" style="height:40px;width:40px;border-radius:4px;border:1px solid #ddd;display:block;background:#f8f9fa;padding:4px">
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="toggle">
                    <input type="checkbox" name="is_active" value="1" <?= ($isEdit && $s['is_active']) || !$isEdit ? 'checked' : '' ?>>
                    <span>Active</span>
                </label>
            </div>
            
            <div class="form-actions full-width">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="stat_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <script>
function previewIcon(input) {
    const newDiv = document.getElementById('previewNewIcon');
    const newImg = document.getElementById('previewNewIconTag');
    const existing = document.getElementById('existingIcon');
    const info = document.getElementById('iconSizeInfo');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const sizeKB = (file.size / 1024).toFixed(1);
        const maxKB = 500;
        
        let color = '#27ae60';
        let msg = sizeKB + ' KB ✅';
        if (file.size > maxKB * 1024) { color = '#e74c3c'; msg = sizeKB + ' KB ❌ Exceeds ' + maxKB + 'KB'; }
        
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