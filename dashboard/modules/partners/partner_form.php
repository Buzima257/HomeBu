<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/PartnerController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new PartnerController($db);
$isEdit = isset($_GET['id']);
$p = $isEdit ? $ctrl->get((int)$_GET['id']) : null;

if ($isEdit && !$p) { header('Location: partner_list.php?error=Not found'); exit; }

$pageTitle = $isEdit ? 'Edit Partner' : 'New Partner';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1><?= $isEdit ? '✏️ ' . htmlspecialchars($p['name']) : '➕ New Partner' ?></h1>
    </div>
    
    <div class="card">
        <form method="POST" action="partner_api.php" enctype="multipart/form-data" class="form-grid">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><?php endif; ?>
            
            <div class="form-group full-width">
                <label>Name *</label>
                <input type="text" name="name" value="<?= $isEdit ? htmlspecialchars($p['name']) : '' ?>" required maxlength="255">
            </div>
            <div class="form-group">
                <label>Website Link</label>
                <input type="url" name="link" value="<?= $isEdit ? htmlspecialchars($p['link'] ?? '') : '' ?>" placeholder="https://...">
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="partner_type">
                    <option value="technical" <?= ($isEdit && $p['partner_type'] === 'technical') ? 'selected' : '' ?>>Technical</option>
                    <option value="financial" <?= ($isEdit && $p['partner_type'] === 'financial') ? 'selected' : '' ?>>Financial</option>
                    <option value="media" <?= ($isEdit && $p['partner_type'] === 'media') ? 'selected' : '' ?>>Media</option>
                    <option value="other" <?= ($isEdit && $p['partner_type'] === 'other') ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" value="<?= $isEdit ? (int)$p['display_order'] : '0' ?>" min="0">
            </div>
                        <div class="form-group">
                <label>Logo</label>
                <input type="file" name="logo_file" id="logoInput" accept="image/jpeg,image/png,image/webp,image/svg+xml" onchange="previewLogo(this)">
                <small style="display:block;margin-top:4px;color:#7f8c8d">PNG/SVG/WebP recommended, max 2MB, min 50KB</small>
                
                <!-- Preview NOUVEAU -->
                <div id="previewNewLogo" style="margin-top:10px;display:none">
                    <p style="font-size:12px;color:#3498db;font-weight:600">New logo:</p>
                    <img id="previewNewLogoTag" style="max-height:60px;border-radius:6px;border:2px solid #3498db;background:#f8f9fa;padding:4px">
                    <span id="logoSizeInfo" style="font-size:12px;color:#555;margin-left:8px"></span>
                </div>
                
                <!-- Logo EXISTANT -->
                <?php if ($isEdit && $p['logo']): ?>
                    <div id="existingLogo" style="margin-top:10px">
                        <p style="font-size:12px;color:#555;font-weight:600">Current logo:</p>
                        <img src="<?= UPLOAD_URL . htmlspecialchars($p['logo']) ?>" style="max-height:60px;border-radius:6px;border:1px solid #ddd;display:block;background:#f8f9fa;padding:4px">
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="toggle">
                    <input type="checkbox" name="is_active" value="1" <?= ($isEdit && $p['is_active']) || !$isEdit ? 'checked' : '' ?>>
                    <span>Active</span>
                </label>
            </div>
            
            <div class="form-actions full-width">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="partner_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
function previewLogo(input) {
    const newDiv = document.getElementById('previewNewLogo');
    const newImg = document.getElementById('previewNewLogoTag');
    const existing = document.getElementById('existingLogo');
    const info = document.getElementById('logoSizeInfo');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const sizeKB = (file.size / 1024).toFixed(1);
        const maxMB = 2;
        const minKB = 50;
        
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