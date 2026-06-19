<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/StaticContent.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$model = new StaticContent($db);
$items = $model->getAll();

// Index by key for easy access
$byKey = [];
foreach ($items as $item) {
    $byKey[$item['content_key']] = $item;
}

$pageTitle = 'Static Content';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>🏢 Static Content</h1>
        <span class="meta">Hero, About, Contact, Organization</span>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="content_api.php" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="update_all">
        
        <!-- ════════════════════════════════════════
             🇬🇧 ENGLISH CONTENT
             ════════════════════════════════════════ -->
        <div class="card" style="border-left:4px solid #3498db">
            <h3 style="color:#3498db;margin-bottom:20px">🇬🇧 English Content</h3>
            
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Hero Headline</label>
                    <input type="text" name="content[hero_headline_en]" 
                           value="<?= htmlspecialchars($byKey['hero_headline_en']['value_en'] ?? '') ?>" 
                           style="width:100%;font-size:16px">
                </div>
                <div class="form-group full-width">
                    <label>Hero Subheadline</label>
                    <input type="text" name="content[hero_subheadline_en]" 
                           value="<?= htmlspecialchars($byKey['hero_subheadline_en']['value_en'] ?? '') ?>" 
                           style="width:100%">
                </div>
                <div class="form-group full-width">
                    <label>About Description</label>
                    <textarea name="content[about_description_en]" rows="4" style="width:100%"><?= htmlspecialchars($byKey['about_description_en']['value_en'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- ════════════════════════════════════════
             🇫🇷 FRENCH CONTENT
             ════════════════════════════════════════ -->
        <div class="card" style="border-left:4px solid #e74c3c;margin-top:24px">
            <h3 style="color:#e74c3c;margin-bottom:20px">🇫🇷 Contenu Français</h3>
            
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Hero Headline</label>
                    <input type="text" name="content[hero_headline_fr]" 
                           value="<?= htmlspecialchars($byKey['hero_headline_fr']['value_en'] ?? '') ?>" 
                           style="width:100%;font-size:16px">
                </div>
                <div class="form-group full-width">
                    <label>Hero Subheadline</label>
                    <input type="text" name="content[hero_subheadline_fr]" 
                           value="<?= htmlspecialchars($byKey['hero_subheadline_fr']['value_en'] ?? '') ?>" 
                           style="width:100%">
                </div>
                <div class="form-group full-width">
                    <label>About Description</label>
                    <textarea name="content[about_description_fr]" rows="4" style="width:100%"><?= htmlspecialchars($byKey['about_description_fr']['value_en'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- ════════════════════════════════════════
             📞 CONTACT & ORGANIZATION
             ════════════════════════════════════════ -->
        <div class="card" style="margin-top:24px">
            <h3 style="margin-bottom:20px">📞 Contact & Organization</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Organization Name</label>
                    <input type="text" name="content[organization_name]" 
                           value="<?= htmlspecialchars($byKey['organization_name']['value_en'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="content[contact_email]" 
                           value="<?= htmlspecialchars($byKey['contact_email']['value_en'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="content[contact_phone]" 
                           value="<?= htmlspecialchars($byKey['contact_phone']['value_en'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Address Line 1</label>
                    <input type="text" name="content[address_line1]" 
                           value="<?= htmlspecialchars($byKey['address_line1']['value_en'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Address Line 2</label>
                    <input type="text" name="content[address_line2]" 
                           value="<?= htmlspecialchars($byKey['address_line2']['value_en'] ?? '') ?>">
                </div>
            </div>
        </div>
        
            <!-- ════════════════════════════════════════
             🎬 HERO MEDIA
             ════════════════════════════════════════ -->
        <div class="card" style="margin-top:24px">
            <h3 style="margin-bottom:20px">🎬 Hero / Loading Page Media</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Media Type</label>
                    <select name="hero_media_type" id="heroMediaType" onchange="toggleHeroMedia(this.value)">
                        <option value="">None</option>
                        <option value="image">Image</option>
                        <option value="video_embed">Video Embed</option>
                        <option value="short_video">Short Video</option>
                    </select>
                </div>
                <div class="form-group" id="heroFileGroup">
                    <label>Upload File</label>
                    <input type="file" name="hero_media_file" id="heroMediaFile" accept="image/*,video/mp4" onchange="previewHeroMedia(this)">
                    <small style="display:block;margin-top:4px;color:#7f8c8d" id="heroFileHint">Image: max 5MB | Video: max 10MB, 60s</small>
                </div>
                <div class="form-group full-width" id="heroUrlGroup" style="display:none">
                    <label>Or Embed URL</label>
                    <input type="url" name="hero_media_url" id="heroMediaUrl" placeholder="https://www.youtube.com/watch?v=..." style="width:100%" onchange="previewHeroEmbed()">
                </div>
            </div>
            
            <!-- Preview -->
            <div id="heroPreview" style="margin-top:16px;display:none">
                <p style="font-size:12px;color:#555;font-weight:600">Preview:</p>
                <div id="heroPreviewBox" style="max-width:400px;border-radius:6px;overflow:hidden;border:1px solid #eee"></div>
                <span id="heroSizeInfo" style="font-size:12px;color:#555"></span>
            </div>
        </div>
        
        <!-- ════════════════════════════════════════
             👁️ PREVIEW
             ════════════════════════════════════════ -->
        <div class="card" style="margin-top:24px;background:#f8f9fa">
            <h3 style="margin-bottom:16px">👁️ Preview</h3>
            <div style="border:1px solid #ddd;border-radius:8px;padding:32px;background:#fff;text-align:center">
                <h1 style="font-size:28px;color:#2c3e50;margin-bottom:8px"><?= htmlspecialchars($byKey['hero_headline_en']['value_en'] ?? 'Headline') ?></h1>
                <p style="font-size:16px;color:#7f8c8d;margin-bottom:24px"><?= htmlspecialchars($byKey['hero_subheadline_en']['value_en'] ?? 'Subheadline') ?></p>
                <div style="max-width:600px;margin:0 auto;padding:20px;background:#f5f6fa;border-radius:8px">
                    <p style="color:#555;line-height:1.6"><?= nl2br(htmlspecialchars($byKey['about_description_en']['value_en'] ?? 'About description...')) ?></p>
                </div>
                <div style="margin-top:24px;color:#95a5a6;font-size:14px">
                    📍 <?= htmlspecialchars($byKey['address_line1']['value_en'] ?? '') ?><br>
                    ✉️ <?= htmlspecialchars($byKey['contact_email']['value_en'] ?? '') ?> | 📞 <?= htmlspecialchars($byKey['contact_phone']['value_en'] ?? '') ?>
                </div>
            </div>
        </div>
        
        <div class="form-actions" style="margin-top:24px">
            <button type="submit" class="btn btn-primary">💾 Save All Content</button>
        </div>
    </form>

    <script>
function toggleHeroMedia(type) {
    document.getElementById('heroFileGroup').style.display = type === 'video_embed' ? 'none' : 'block';
    document.getElementById('heroUrlGroup').style.display = type === 'video_embed' ? 'block' : 'none';
    document.getElementById('heroFileHint').textContent = type === 'short_video' ? 'Max 10MB, 60 seconds, MP4' : 'Max 5MB, JPG/PNG/WebP';
    if (!type) document.getElementById('heroPreview').style.display = 'none';
}

function previewHeroMedia(input) {
    const container = document.getElementById('heroPreview');
    const box = document.getElementById('heroPreviewBox');
    const info = document.getElementById('heroSizeInfo');
    const type = document.getElementById('heroMediaType').value;
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
        const maxMB = type === 'short_video' ? 10 : 5;
        
        let color = '#27ae60';
        let msg = sizeMB + ' MB ✅';
        if (file.size > maxMB * 1024 * 1024) { color = '#e74c3c'; msg = sizeMB + ' MB ❌ Exceeds ' + maxMB + 'MB'; }
        
        info.innerHTML = '<span style="color:' + color + '">' + msg + '</span>';
        
        const reader = new FileReader();
        reader.onload = function(e) {
            if (type === 'short_video') {
                box.innerHTML = '<video src="' + e.target.result + '" style="width:100%;max-height:200px" controls muted></video>';
            } else {
                box.innerHTML = '<img src="' + e.target.result + '" style="width:100%;max-height:200px;object-fit:cover">';
            }
            container.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

function previewHeroEmbed() {
    const url = document.getElementById('heroMediaUrl').value.trim();
    const container = document.getElementById('heroPreview');
    const box = document.getElementById('heroPreviewBox');
    const info = document.getElementById('heroSizeInfo');
    
    if (!url) { container.style.display = 'none'; return; }
    
    let embedUrl = '';
    const yt = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/);
    const vm = url.match(/vimeo\.com\/(\d+)/);
    if (yt) embedUrl = 'https://www.youtube.com/embed/' + yt[1];
    else if (vm) embedUrl = 'https://player.vimeo.com/video/' + vm[1];
    
    if (embedUrl) {
        box.innerHTML = '<iframe src="' + embedUrl + '" style="width:100%;height:225px;border:none" allowfullscreen></iframe>';
        info.innerHTML = '';
        container.style.display = 'block';
    } else {
        box.innerHTML = '<p style="color:#e74c3c;padding:20px">Invalid URL</p>';
        container.style.display = 'block';
    }
}
</script>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>