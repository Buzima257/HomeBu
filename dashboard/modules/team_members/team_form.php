<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/TeamMemberController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new TeamMemberController($db);
$isEdit = isset($_GET['id']);
$member = $isEdit ? $ctrl->get((int)$_GET['id']) : null;
$social = json_decode($member['social_links'] ?? '{}', true);

if ($isEdit && !$member) {
    header('Location: team_list.php?error=Not found');
    exit;
}

$pageTitle = $isEdit ? 'Edit Member' : 'New Member';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1><?= $isEdit ? '✏️ ' . htmlspecialchars($member['full_name']) : '➕ New Team Member' ?></h1>
    </div>
    
    <div class="card">
        <form method="POST" action="team_api.php" enctype="multipart/form-data" class="form-grid">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$member['id'] ?>"><?php endif; ?>
            
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" value="<?= $isEdit ? htmlspecialchars($member['full_name']) : '' ?>" required maxlength="150">
            </div>
            <div class="form-group">
                <label>Role *</label>
                <input type="text" name="role" value="<?= $isEdit ? htmlspecialchars($member['role']) : '' ?>" required maxlength="100" placeholder="Executive Director">
            </div>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" value="<?= $isEdit ? htmlspecialchars($member['title'] ?? '') : '' ?>" maxlength="100" placeholder="Dr., Ing.">
            </div>
            <div class="form-group">
                <label>Department</label>
                <input type="text" name="department" value="<?= $isEdit ? htmlspecialchars($member['department'] ?? '') : '' ?>" maxlength="100" placeholder="Direction, Finance, Projects">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= $isEdit ? htmlspecialchars($member['email'] ?? '') : '' ?>">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone" value="<?= $isEdit ? htmlspecialchars($member['phone'] ?? '') : '' ?>">
            </div>
            <div class="form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" value="<?= $isEdit ? (int)$member['display_order'] : '0' ?>" min="0">
            </div>
            
            <div class="form-group full-width">
                <label>Biography</label>
                <textarea name="biography" rows="4"><?= $isEdit ? htmlspecialchars($member['biography'] ?? '') : '' ?></textarea>
            </div>
            
            <div class="form-group full-width" style="display:flex;gap:24px;flex-wrap:wrap">
                <label class="toggle">
                    <input type="checkbox" name="is_leadership" value="1" <?= ($isEdit && $member['is_leadership']) ? 'checked' : '' ?>>
                    <span>Leadership</span>
                </label>
                <label class="toggle">
                    <input type="checkbox" name="is_featured" value="1" <?= ($isEdit && $member['is_featured']) ? 'checked' : '' ?>>
                    <span>Featured on Homepage</span>
                </label>
                <label class="toggle">
                    <input type="checkbox" name="show_email_public" value="1" <?= ($isEdit && $member['show_email_public']) ? 'checked' : '' ?>>
                    <span>Show Email Publicly</span>
                </label>
                <label class="toggle">
                    <input type="checkbox" name="show_phone_public" value="1" <?= ($isEdit && $member['show_phone_public']) ? 'checked' : '' ?>>
                    <span>Show Phone Publicly</span>
                </label>
            </div>
            
            <div class="form-group full-width" style="border-top:1px solid #eee;padding-top:16px">
                <label>Social Links</label>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                    <input type="url" name="social_linkedin" placeholder="LinkedIn URL" value="<?= $isEdit ? htmlspecialchars($social['linkedin'] ?? '') : '' ?>">
                    <input type="url" name="social_twitter" placeholder="Twitter/X URL" value="<?= $isEdit ? htmlspecialchars($social['twitter'] ?? '') : '' ?>">
                    <input type="url" name="social_facebook" placeholder="Facebook URL" value="<?= $isEdit ? htmlspecialchars($social['facebook'] ?? '') : '' ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Photo Portrait</label>
                <input type="file" name="photo_file" id="photoInput" accept="image/jpeg,image/png,image/webp" onchange="previewPhoto(this)">
                <small style="display:block;margin-top:4px;color:#7f8c8d">Min 100KB, max 2MB, 200x200 min</small>
                
                <!-- Preview NOUVELLE -->
                <div id="previewNewPhoto" style="margin-top:10px;display:none">
                    <p style="font-size:12px;color:#3498db;font-weight:600">New photo:</p>
                    <img id="previewNewPhotoTag" style="max-height:100px;border-radius:6px;border:2px solid #3498db">
                    <span id="photoSizeInfo" style="font-size:12px;color:#555;margin-left:8px"></span>
                </div>
                
                <!-- Photo EXISTANTE -->
                <?php if ($isEdit && $member['photo']): ?>
                    <div id="existingPhoto" style="margin-top:10px">
                        <p style="font-size:12px;color:#555;font-weight:600">Current photo:</p>
                        <img src="<?= UPLOAD_URL . htmlspecialchars($member['photo']) ?>" style="max-height:100px;border-radius:6px;border:1px solid #ddd;display:block">
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-actions full-width">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="team_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
function previewPhoto(input) {
    const newDiv = document.getElementById('previewNewPhoto');
    const newImg = document.getElementById('previewNewPhotoTag');
    const existing = document.getElementById('existingPhoto');
    const info = document.getElementById('photoSizeInfo');
    
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