<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/TestimonialController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new TestimonialController($db);
$isEdit = isset($_GET['id']);
$t = $isEdit ? $ctrl->get((int)$_GET['id']) : null;

if ($isEdit && !$t) {
    header('Location: testimonial_list.php?error=Not found');
    exit;
}

$pageTitle = $isEdit ? 'Edit Testimonial' : 'New Testimonial';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1><?= $isEdit ? '✏️ ' . htmlspecialchars($t['name']) : '➕ New Testimonial' ?></h1>
    </div>
    
    <div class="card">
        <form method="POST" action="testimonial_api.php" enctype="multipart/form-data" class="form-grid">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><?php endif; ?>
            
            <div class="form-group">
                <label>Language</label>
                <select name="lang">
                    <option value="en" <?= ($isEdit && $t['lang'] === 'en') || !$isEdit ? 'selected' : '' ?>>English</option>
                    <option value="fr" <?= ($isEdit && $t['lang'] === 'fr') ? 'selected' : '' ?>>Français</option>
                </select>
            </div>
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" value="<?= $isEdit ? htmlspecialchars($t['name']) : '' ?>" required maxlength="150">
            </div>
            <div class="form-group">
                <label>Role</label>
                <input type="text" name="role" value="<?= $isEdit ? htmlspecialchars($t['role'] ?? '') : '' ?>" maxlength="100" placeholder="Beneficiary, North Region">
            </div>
            <div class="form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" value="<?= $isEdit ? (int)$t['display_order'] : '0' ?>" min="0">
            </div>
            <div class="form-group full-width">
                <label>Testimonial Text *</label>
                <textarea name="text" rows="5" required><?= $isEdit ? htmlspecialchars($t['text']) : '' ?></textarea>
            </div>
            
            <!-- PHOTO -->
            <div class="form-group" style="border:2px dashed #ddd;padding:16px;border-radius:8px">
                <label>Photo</label>
                <input type="file" name="photo_file" id="photoInput" accept="image/jpeg,image/png,image/webp" onchange="previewPhoto(this)">
                <small style="display:block;margin-top:4px;color:#7f8c8d">Min 100KB, max 5MB, 800x600 min</small>
                
                <!-- Preview NOUVELLE -->
                <div id="previewNewPhoto" style="margin-top:10px;display:none">
                    <p style="font-size:12px;color:#3498db;font-weight:600">New photo:</p>
                    <img id="previewNewPhotoTag" style="max-height:100px;border-radius:6px;border:2px solid #3498db">
                    <span id="photoSizeInfo" style="font-size:12px;color:#555;margin-left:8px"></span>
                </div>
                
                <!-- Photo EXISTANTE -->
                <?php if ($isEdit && $t['photo']): ?>
                    <div id="existingPhoto" style="margin-top:10px">
                        <p style="font-size:12px;color:#555;font-weight:600">Current photo:</p>
                        <img src="<?= UPLOAD_URL . htmlspecialchars($t['photo']) ?>" style="max-height:100px;border-radius:6px;border:1px solid #ddd;display:block">
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- VIDEO -->
            <div class="form-group full-width" style="border:2px dashed #ddd;padding:16px;border-radius:8px;margin-top:8px">
                <label>Video</label>
                <div style="display:flex;gap:16px;margin-bottom:12px">
                    <label class="toggle"><input type="radio" name="video_type" value="none" <?= ($isEdit && $t['video_type'] === 'none') || !$isEdit ? 'checked' : '' ?> onchange="toggleVideo(this.value)"> <span>No video</span></label>
                    <label class="toggle"><input type="radio" name="video_type" value="embed" <?= ($isEdit && $t['video_type'] === 'embed') ? 'checked' : '' ?> onchange="toggleVideo(this.value)"> <span>Embed (YouTube/Vimeo)</span></label>
                    <label class="toggle"><input type="radio" name="video_type" value="short" <?= ($isEdit && $t['video_type'] === 'short') ? 'checked' : '' ?> onchange="toggleVideo(this.value)"> <span>Short Video</span></label>
                </div>
                
                <div id="video-embed" style="<?= ($isEdit && $t['video_type'] === 'embed') ? '' : 'display:none' ?>">
                    <input type="url" name="video_embed_url" id="embedUrl" placeholder="https://www.youtube.com/watch?v=..." value="<?= $isEdit ? htmlspecialchars($t['video_embed_url'] ?? '') : '' ?>" onchange="previewEmbed()">
                    <small style="display:block;margin-top:4px;color:#7f8c8d">YouTube or Vimeo only</small>
                    
                    <!-- Preview embed -->
                    <div id="embedPreview" style="margin-top:10px;<?= ($isEdit && $t['video_embed_url']) ? '' : 'display:none' ?>">
                        <p style="font-size:12px;color:#555;font-weight:600">Preview:</p>
                        <div id="embedPreviewBox" style="max-width:400px">
                            <?php if ($isEdit && $t['video_embed_url']): ?>
                                <iframe src="<?= htmlspecialchars($t['video_embed_url']) ?>" style="width:100%;height:225px;border:none;border-radius:6px" allowfullscreen loading="lazy"></iframe>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div id="video-short" style="<?= ($isEdit && $t['video_type'] === 'short') ? '' : 'display:none' ?>">
                    <input type="file" name="video_file" id="shortVideoInput" accept="video/mp4" onchange="previewShortVideo(this)">
                    <small style="display:block;margin-top:4px;color:#7f8c8d">Max 10MB, 60 seconds, MP4 only</small>
                    
                    <!-- Preview NOUVEAU short -->
                    <div id="previewNewShort" style="margin-top:10px;display:none">
                        <p style="font-size:12px;color:#3498db;font-weight:600">New video:</p>
                        <video id="previewNewShortTag" style="max-height:120px;border-radius:6px;border:2px solid #3498db" controls muted></video>
                        <span id="shortSizeInfo" style="font-size:12px;color:#555;margin-left:8px"></span>
                    </div>
                    
                    <!-- Short EXISTANT -->
                    <?php if ($isEdit && $t['video_file']): ?>
                        <div id="existingShort" style="margin-top:10px">
                            <p style="font-size:12px;color:#555;font-weight:600">Current video:</p>
                            <video src="<?= UPLOAD_URL . htmlspecialchars($t['video_file']) ?>" style="max-height:120px;border-radius:6px;border:1px solid #ddd;display:block" controls muted></video>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group full-width" style="display:flex;gap:20px;align-items:center;margin-top:8px">
                <label class="toggle">
                    <input type="checkbox" name="is_featured" value="1" <?= ($isEdit && $t['is_featured']) ? 'checked' : '' ?>>
                    <span>Featured on Homepage</span>
                </label>
            </div>
            
            <div class="form-actions full-width">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="testimonial_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>

<script>
function toggleVideo(type) {
    document.getElementById('video-embed').style.display = type === 'embed' ? 'block' : 'none';
    document.getElementById('video-short').style.display = type === 'short' ? 'block' : 'none';
}

function previewPhoto(input) {
    const newDiv = document.getElementById('previewNewPhoto');
    const newImg = document.getElementById('previewNewPhotoTag');
    const existing = document.getElementById('existingPhoto');
    const info = document.getElementById('photoSizeInfo');
    
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

function previewEmbed() {
    const url = document.getElementById('embedUrl').value.trim();
    const container = document.getElementById('embedPreview');
    const box = document.getElementById('embedPreviewBox');
    
    if (!url) { container.style.display = 'none'; return; }
    
    let embedUrl = '';
    const yt = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/);
    const vm = url.match(/vimeo\.com\/(\d+)/);
    if (yt) embedUrl = 'https://www.youtube.com/embed/' + yt[1];
    else if (vm) embedUrl = 'https://player.vimeo.com/video/' + vm[1];
    
    if (embedUrl) {
        box.innerHTML = '<iframe src="' + embedUrl + '" style="width:100%;height:225px;border:none;border-radius:6px" allowfullscreen loading="lazy"></iframe>';
        container.style.display = 'block';
    } else {
        box.innerHTML = '<p style="color:#e74c3c;font-size:13px">❌ Invalid URL. Use YouTube or Vimeo.</p>';
        container.style.display = 'block';
    }
}

function previewShortVideo(input) {
    const newDiv = document.getElementById('previewNewShort');
    const vid = document.getElementById('previewNewShortTag');
    const existing = document.getElementById('existingShort');
    const info = document.getElementById('shortSizeInfo');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
        const maxMB = 10;
        
        let color = '#27ae60';
        let msg = sizeMB + ' MB ✅';
        if (file.size > maxMB * 1024 * 1024) { color = '#e74c3c'; msg = sizeMB + ' MB ❌ Exceeds ' + maxMB + 'MB'; }
        
        info.innerHTML = '<span style="color:' + color + '">' + msg + '</span>';
        
        const reader = new FileReader();
        reader.onload = function(e) {
            vid.src = e.target.result;
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>