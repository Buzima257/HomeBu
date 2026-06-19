<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/StaticSectionController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$toggleStmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'allow_static_section_edit'");
$toggleStmt->execute();
$canEdit = ($toggleStmt->fetchColumn() ?? '1') === '1';
if (!$canEdit && !$auth->hasRole('super_admin')) {
    http_response_code(403);
    require_once __DIR__ . '/../../includes/403.php';
    exit;
}

$ctrl = new StaticSectionController($db);
$id = (int)($_GET['id'] ?? 0);
$section = $ctrl->get($id);

if (!$section) {
    header('Location: section_list.php?error=Section not found');
    exit;
}

$media = $section['media'] ?? [];
$hasImages = count(array_filter($media, fn($m) => $m['media_type'] === 'image')) > 0;
$hasVideo = count(array_filter($media, fn($m) => in_array($m['media_type'], ['video_embed','short_video']))) > 0;

$pageTitle = 'Edit: ' . htmlspecialchars($section['title_en'] ?: $section['slug']);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>✏️ <?= htmlspecialchars($section['title_en'] ?: $section['slug']) ?></h1>
        <span class="badge badge-gray"><?= htmlspecialchars($section['slug']) ?></span>
    </div>
    
    <div class="card">
        <form method="POST" action="section_api.php" enctype="multipart/form-data" class="form-grid">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= (int)$section['id'] ?>">
            
            <!-- EN -->
            <div class="form-group full-width" style="background:#eff6ff;border-left:4px solid #3498db;padding:16px;border-radius:6px">
                <label style="color:#3498db;font-weight:700;font-size:14px;text-transform:uppercase;letter-spacing:1px">🇬🇧 English Content</label>
            </div>
            <div class="form-group">
                <label>Title EN</label>
                <input type="text" name="title_en" value="<?= htmlspecialchars($section['title_en'] ?? '') ?>" maxlength="255">
            </div>
            <div class="form-group">
                <label>Subtitle EN</label>
                <input type="text" name="subtitle_en" value="<?= htmlspecialchars($section['subtitle_en'] ?? '') ?>" maxlength="255">
            </div>
            <div class="form-group full-width">
                <label>Content EN</label>
                <textarea name="content_en" rows="6"><?= htmlspecialchars($section['content_en'] ?? '') ?></textarea>
            </div>
            
            <!-- FR -->
            <div class="form-group full-width" style="background:#fef2f2;border-left:4px solid #e74c3c;padding:16px;border-radius:6px;margin-top:12px">
                <label style="color:#e74c3c;font-weight:700;font-size:14px;text-transform:uppercase;letter-spacing:1px">🇫🇷 Contenu Français</label>
            </div>
            <div class="form-group">
                <label>Title FR</label>
                <input type="text" name="title_fr" value="<?= htmlspecialchars($section['title_fr'] ?? '') ?>" maxlength="255">
            </div>
            <div class="form-group">
                <label>Subtitle FR</label>
                <input type="text" name="subtitle_fr" value="<?= htmlspecialchars($section['subtitle_fr'] ?? '') ?>" maxlength="255">
            </div>
            <div class="form-group full-width">
                <label>Content FR</label>
                <textarea name="content_fr" rows="6"><?= htmlspecialchars($section['content_fr'] ?? '') ?></textarea>
            </div>
            
            <!-- Layout -->
            <div class="form-group">
                <label>Layout</label>
                <select name="layout_type">
                    <?php foreach (['text-left','text-right','text-top','text-bottom','full-width','gallery-grid'] as $l): ?>
                    <option value="<?= $l ?>" <?= $section['layout_type'] === $l ? 'selected' : '' ?>><?= ucfirst(str_replace('-', ' ', $l)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Background Color</label>
                <input type="color" name="bg_color" value="<?= htmlspecialchars($section['bg_color'] ?? '#ffffff') ?>">
            </div>
            
            <div class="form-group">
                <label>Background Image</label>
                <input type="file" name="bg_image_file" id="bgImageInput" accept="image/jpeg,image/png,image/webp" onchange="previewBgImage(this)">
                
                <!-- Preview NOUVELLE image -->
                <div id="previewNewBg" style="margin-top:10px;display:none">
                    <p style="font-size:12px;color:#3498db;font-weight:600">New image preview:</p>
                    <img id="previewNewBgTag" style="max-height:100px;border-radius:6px;border:2px solid #3498db">
                </div>
                
                <!-- Image EXISTANTE -->
                <?php if ($section['bg_image']): ?>
                    <div id="existingBg" style="margin-top:10px">
                        <p style="font-size:12px;color:#555;font-weight:600">Current image:</p>
                        <img src="<?= UPLOAD_URL . htmlspecialchars($section['bg_image']) ?>" 
                             style="max-height:100px;border-radius:6px;border:1px solid #ddd;display:block">
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" value="<?= (int)$section['display_order'] ?>" min="0">
            </div>
            
            <div class="form-group">
                <label>Active</label>
                <label class="toggle">
                    <input type="checkbox" name="is_active" value="1" <?= $section['is_active'] ? 'checked' : '' ?>>
                    <span>Show on website</span>
                </label>
            </div>
            
            <div class="form-actions full-width">
                <button type="submit" class="btn btn-primary">Save Section</button>
                <a href="section_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <!-- ─── MEDIA SECTION ─── -->
    <div class="card" style="margin-top:24px">
        <h3 style="margin-bottom:16px">🖼️ Section Media (<?= count($media) ?>/5)</h3>
        
        <?php if ($hasImages): ?>
            <p style="color:#27ae60;font-size:13px;margin-bottom:12px">✅ Images only mode</p>
        <?php elseif ($hasVideo): ?>
            <p style="color:#e74c3c;font-size:13px;margin-bottom:12px">🎬 Video only mode — delete video to add images</p>
        <?php else: ?>
            <p style="color:#7f8c8d;font-size:13px;margin-bottom:12px">No media yet. Choose type below.</p>
        <?php endif; ?>
        
        <!-- Galerie existante -->
        <?php if (!empty($media)): ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:20px">
            <?php foreach ($media as $m): ?>
            <div style="position:relative;background:#f8f9fa;border-radius:8px;overflow:hidden;border:1px solid #eee">
                <?php if ($m['media_type'] === 'image'): ?>
                    <img src="<?= UPLOAD_URL . htmlspecialchars($m['file_path']) ?>" style="width:100%;height:110px;object-fit:cover;display:block">
                <?php elseif ($m['media_type'] === 'video_embed'): ?>
                    <iframe src="<?= htmlspecialchars($m['embed_url']) ?>" style="width:100%;height:110px;border:none;display:block" allowfullscreen loading="lazy"></iframe>
                <?php else: ?>
                    <video src="<?= UPLOAD_URL . htmlspecialchars($m['file_path']) ?>" style="width:100%;height:110px;object-fit:cover;display:block" muted playsinline></video>
                <?php endif; ?>
                
                <div style="padding:6px 8px;font-size:11px;color:#555;background:#fff;border-top:1px solid #eee">
                    <?= htmlspecialchars($m['caption_en'] ?: ($m['caption_fr'] ?: 'No caption')) ?>
                </div>
                
                <a href="section_api.php?action=delete_media&media_id=<?= (int)$m['id'] ?>&section_id=<?= (int)$section['id'] ?>&csrf_token=<?= urlencode(Csrf::generate()) ?>" 
                   style="position:absolute;top:6px;right:6px;background:#e74c3c;color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;text-decoration:none;box-shadow:0 2px 4px rgba(0,0,0,.2)"
                   onclick="return confirm('Delete this media?')" title="Delete">×</a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Ajout média -->
        <?php if (count($media) < 5): ?>
        <form method="POST" action="section_api.php" enctype="multipart/form-data" style="border-top:2px solid #eee;padding-top:20px">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="add_media">
            <input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>">
            
            <div style="display:flex;gap:16px;margin-bottom:16px;align-items:center;flex-wrap:wrap">
                <label style="font-weight:600">Add media:</label>
                <?php if (!$hasVideo): ?>
                <label class="toggle" style="padding:8px 14px;background:#ecf0f1;border-radius:6px;cursor:pointer">
                    <input type="radio" name="media_type" value="image" checked onchange="toggleMediaType(this.value)"> <span>📷 Image</span>
                </label>
                <?php endif; ?>
                <?php if (!$hasImages && !$hasVideo): ?>
                <label class="toggle" style="padding:8px 14px;background:#ecf0f1;border-radius:6px;cursor:pointer">
                    <input type="radio" name="media_type" value="video_embed" onchange="toggleMediaType(this.value)"> <span>🎬 Embed</span>
                </label>
                <label class="toggle" style="padding:8px 14px;background:#ecf0f1;border-radius:6px;cursor:pointer">
                    <input type="radio" name="media_type" value="short_video" onchange="toggleMediaType(this.value)"> <span>📹 Short</span>
                </label>
                <?php endif; ?>
            </div>
            
            <div id="media-image" style="<?= $hasVideo ? 'display:none' : '' ?>">
                <input type="file" name="media_files[]" id="mediaFilesInput" multiple accept="image/jpeg,image/png,image/webp" onchange="previewMediaFiles(this)">
                <small style="display:block;margin-top:4px;color:#7f8c8d">Max <?= 5 - count($media) ?> files. Min 100KB, max 5MB each.</small>
                
                <!-- Preview avant upload -->
                <div id="mediaFilesPreview" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px;margin-top:12px"></div>
            </div>
            
            <div id="media-embed" style="display:none">
                <input type="url" name="embed_url" placeholder="https://www.youtube.com/watch?v=..." style="width:100%;max-width:400px">
                <small style="display:block;margin-top:4px;color:#7f8c8d">YouTube or Vimeo only</small>
            </div>
            
            <div id="media-short" style="display:none">
                <input type="file" name="media_file" accept="video/mp4">
                <small style="display:block;margin-top:4px;color:#7f8c8d">Max 10MB, 60 seconds, MP4 only</small>
            </div>
            
            <div style="margin-top:16px">
                <button type="submit" class="btn btn-primary btn-sm">Add to Gallery</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
    
    <!-- ─── LIVE PREVIEW ─── -->
    <div class="card" style="margin-top:24px;background:#f8f9fa">
        <h3 style="margin-bottom:16px">👁️ Live Preview</h3>
        <div style="border:1px solid #ddd;border-radius:8px;padding:24px;background:#fff" id="livePreview">
            <h2 style="color:#2c3e50;margin-bottom:8px"><?= htmlspecialchars($section['title_en'] ?: 'Title') ?></h2>
            <h4 style="color:#7f8c8d;font-weight:400;margin-bottom:16px"><?= htmlspecialchars($section['subtitle_en'] ?: '') ?></h4>
            <p style="line-height:1.6;color:#555"><?= nl2br(htmlspecialchars($section['content_en'] ?: 'Content...')) ?></p>
            
            <?php if ($section['bg_image']): ?>
                <p style="margin-top:16px"><img src="<?= UPLOAD_URL . htmlspecialchars($section['bg_image']) ?>" style="max-width:100%;border-radius:6px"></p>
            <?php endif; ?>
            
            <?php if (!empty($media)): ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:8px;margin-top:16px">
                <?php foreach ($media as $m): ?>
                    <?php if ($m['media_type'] === 'image'): ?>
                        <img src="<?= UPLOAD_URL . htmlspecialchars($m['file_path']) ?>" style="width:100%;border-radius:4px">
                    <?php elseif ($m['media_type'] === 'video_embed'): ?>
                        <div style="background:#333;color:#fff;padding:20px;text-align:center;border-radius:4px;font-size:12px">🎬 Video</div>
                    <?php else: ?>
                        <div style="background:#333;color:#fff;padding:20px;text-align:center;border-radius:4px;font-size:12px">📹 Short</div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function previewBgImage(input) {
    const newDiv = document.getElementById('previewNewBg');
    const newImg = document.getElementById('previewNewBgTag');
    const existing = document.getElementById('existingBg');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            newImg.src = e.target.result;
            newDiv.style.display = 'block';
            if (existing) existing.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleMediaType(type) {
    document.getElementById('media-image').style.display = type === 'image' ? 'block' : 'none';
    document.getElementById('media-embed').style.display = type === 'video_embed' ? 'block' : 'none';
    document.getElementById('media-short').style.display = type === 'short_video' ? 'block' : 'none';
}

function previewMediaFiles(input) {
    const container = document.getElementById('mediaFilesPreview');
    container.innerHTML = '';
    if (input.files) {
        Array.from(input.files).forEach(file => {
            if (!file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.style.cssText = 'position:relative;border-radius:6px;overflow:hidden;border:1px solid #eee';
                div.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:80px;object-fit:cover">' +
                    '<span style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.6);color:#fff;font-size:10px;padding:2px 6px">' + (file.size/1024).toFixed(1) + 'KB</span>';
                container.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>