<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/ArticleController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new ArticleController($db);
$isEdit = isset($_GET['id']);
$article = $isEdit ? $ctrl->get((int)$_GET['id']) : null;
$partners = $ctrl->getAllPartners();
$linkedPartners = $isEdit ? array_column($article['partners'] ?? [], 'id') : [];

if ($isEdit && !$article) {
    header('Location: article_list.php?error=Article not found');
    exit;
}

$pageTitle = $isEdit ? 'Edit Article' : 'New Article';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<<main class="content">
    <div class="page-header">
        <h1><?= $isEdit ? '✏️ Edit Article' : '➕ New Article' ?></h1>
    </div>
    
    <div class="card">
        <form method="POST" action="article_api.php" enctype="multipart/form-data" id="articleForm" class="form-grid">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= (int)$article['id'] ?>">
            <?php endif; ?>
            
            <!-- Language -->
            <div class="form-group">
                <label>Language *</label>
                <select name="lang" required>
                    <option value="en" <?= ($isEdit && $article['lang'] === 'en') || !$isEdit ? 'selected' : '' ?>>English</option>
                    <option value="fr" <?= ($isEdit && $article['lang'] === 'fr') ? 'selected' : '' ?>>Français</option>
                </select>
            </div>
            
            <!-- Slug (auto-généré si vide) -->
            <div class="form-group">
                <label>Slug</label>
                <input type="text" name="slug" id="slugField" value="<?= $isEdit ? htmlspecialchars($article['slug']) : '' ?>" 
                       maxlength="150" placeholder="Auto-generated from title">
                <small>Leave empty to auto-generate from title</small>
            </div>
            
            <!-- Title -->
            <div class="form-group full-width">
                <label>Title *</label>
                <input type="text" name="title" id="titleField" value="<?= $isEdit ? htmlspecialchars($article['title']) : '' ?>" 
                       required maxlength="255" oninput="generateSlug()">
            </div>
            
            <!-- Description -->
            <div class="form-group full-width">
                <label>Content</label>
                <textarea name="description" rows="10" style="font-family:monospace"><?= $isEdit ? htmlspecialchars($article['description'] ?? '') : '' ?></textarea>
                <small>HTML allowed. TinyMCE can be integrated here.</small>
            </div>
            
            <!-- Featured Media -->
            <div class="form-group full-width" style="border:2px dashed #ddd;padding:20px;border-radius:8px">
                <label>Featured Media</label>
                <div style="display:flex;gap:16px;margin-bottom:12px">
                    <label class="toggle">
                        <input type="radio" name="featured_type" value="image" 
                               <?= ($isEdit && $article['featured_type'] === 'image') || !$isEdit ? 'checked' : '' ?>
                               onchange="toggleFeatured(this.value)">
                        <span>Image</span>
                    </label>
                    <label class="toggle">
                        <input type="radio" name="featured_type" value="video_embed" 
                               <?= ($isEdit && $article['featured_type'] === 'video_embed') ? 'checked' : '' ?>
                               onchange="toggleFeatured(this.value)">
                        <span>Video Embed</span>
                    </label>
                </div>
                
                     <!-- IMAGE -->
                <div id="featured-image" style="<?= ($isEdit && $article['featured_type'] === 'video_embed') ? 'display:none' : '' ?>">
                    <input type="file" name="featured_image_file" id="featuredImageInput" 
                           accept="image/jpeg,image/png,image/webp" onchange="previewFeaturedImage(this)">
                    
                    <!-- Prévisualisation nouvelle image (upload avant save) -->
                    <div id="previewNewImage" style="margin-top:12px;display:none">
                        <p style="font-size:12px;color:#3498db;font-weight:500">New image preview:</p>
                        <img id="previewNewImageTag" style="max-height:160px;border-radius:6px;border:2px solid #3498db">
                    </div>
                    
                    <!-- Image existante (mode edit) -->
                    <?php if ($isEdit && $article['featured_image']): ?>
                        <div id="existingImage" style="margin-top:12px">
                            <p style="font-size:12px;color:#555;font-weight:500">Current image:</p>
                            <img src="<?= UPLOAD_URL . htmlspecialchars($article['featured_image']) ?>" 
                                 id="existingImageTag"
                                 style="max-height:160px;border-radius:6px;border:1px solid #eee;display:block">
                            <input type="hidden" name="featured_image_path" value="<?= htmlspecialchars($article['featured_image']) ?>">
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- VIDEO EMBED -->
                <div id="featured-embed" style="<?= ($isEdit && $article['featured_type'] === 'video_embed') ? '' : 'display:none' ?>">
                    <input type="url" name="video_embed_url" id="videoEmbedUrl" 
                           placeholder="https://www.youtube.com/watch?v=..." 
                           value="<?= $isEdit ? htmlspecialchars($article['video_embed_url'] ?? '') : '' ?>"
                           onchange="previewFeaturedVideo()">
                    <small>YouTube, Vimeo only</small>
                    
                    <!-- Prévisualisation vidéo -->
                    <div id="previewVideo" style="margin-top:12px;<?= ($isEdit && $article['video_embed_url']) ? '' : 'display:none' ?>">
                        <p style="font-size:12px;color:#555;font-weight:500">Preview:</p>
                        <div id="videoPreviewContainer" style="max-width:480px">
                            <?php if ($isEdit && $article['video_embed_url']): ?>
                                <iframe src="<?= htmlspecialchars($article['video_embed_url']) ?>" 
                                        style="width:100%;height:270px;border:none;border-radius:6px" 
                                        allowfullscreen loading="lazy"></iframe>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Status -->
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="draft" <?= ($isEdit && $article['status'] === 'draft') || !$isEdit ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= ($isEdit && $article['status'] === 'published') ? 'selected' : '' ?>>Published</option>
                    <option value="archived" <?= ($isEdit && $article['status'] === 'archived') ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>
            
            <!-- Publish Date -->
            <div class="form-group">
                <label>Publish Date</label>
                <input type="datetime-local" name="published_at" 
                       value="<?= $isEdit && $article['published_at'] ? date('Y-m-d\TH:i', strtotime($article['published_at'])) : '' ?>">
                <small>Leave empty for immediate publication</small>
            </div>
            
            <!-- Partners -->
            <div class="form-group full-width">
                <label>Linked Partners</label>
                <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:8px">
                    <?php foreach ($partners as $p): ?>
                    <label class="toggle" style="background:#f8f9fa;padding:8px 12px;border-radius:6px;border:1px solid #e1e1e1">
                        <input type="checkbox" name="partner_ids[]" value="<?= (int)$p['id'] ?>"
                               <?= in_array((int)$p['id'], $linkedPartners) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($p['name']) ?></span>
                    </label>
                    <?php endforeach; ?>
                    <?php if (empty($partners)): ?>
                        <span style="color:#95a5a6;font-size:13px">No partners available. Create partners first.</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Gallery -->
            <div class="form-group full-width" style="border-top:2px solid #eee;padding-top:20px;margin-top:20px">
                <label>Gallery</label>
                
                <!-- Galerie existante (mode edit) -->
                <?php if ($isEdit && !empty($article['gallery'])): ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin:12px 0">
                    <?php foreach ($article['gallery'] as $g): ?>
                    <div style="position:relative;background:#f8f9fa;border-radius:6px;overflow:hidden">
                        <?php if ($g['media_type'] === 'image'): ?>
                            <img src="<?= UPLOAD_URL . htmlspecialchars($g['file_path']) ?>" style="width:100%;height:100px;object-fit:cover">
                        <?php else: ?>
                            <div style="padding:20px;text-align:center;color:#7f8c8d;font-size:12px">🎬 Embed</div>
                        <?php endif; ?>
                        <a href="article_api.php?action=delete_gallery_item&item_id=<?= (int)$g['id'] ?>&article_id=<?= (int)$article['id'] ?>&csrf_token=<?= urlencode(Csrf::generate()) ?>" 
                           style="position:absolute;top:4px;right:4px;background:#e74c3c;color:#fff;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;text-decoration:none"
                           onclick="return confirm('Remove?')">×</a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Upload nouveaux médias -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:12px">
                    <div>
                        <p style="font-size:13px;font-weight:500;color:#555;margin-bottom:8px">Upload Images</p>
                        <input type="file" name="gallery_images[]" id="galleryImagesInput" 
                               multiple accept="image/jpeg,image/png,image/webp" onchange="previewGalleryImages(this)">
                        
                        <!-- Prévisualisation galerie avant upload -->
                        <div id="galleryPreview" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px;margin-top:12px"></div>
                    </div>
                    <div>

                        <p style="font-size:13px;font-weight:500;color:#555;margin-bottom:8px">Video Embeds</p>
                        <textarea name="gallery_embeds[urls]" id="galleryEmbeds" rows="3" placeholder="One URL per line" oninput="previewGalleryEmbeds()"></textarea>
                        <small style="display:block;margin-top:4px">YouTube, Vimeo only. One per line.</small>
                        
                        <!-- Prévisualisation des embeds saisis -->
                        <div id="galleryEmbedPreview" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;margin-top:12px"></div>
                    </div>
                        <small style="display:block;margin-top:4px">Will be validated on save</small>
                    </div>
                </div>
            </div>
            
            <div class="form-actions full-width">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Article' ?></button>
                <a href="article_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>

<script>
// ─── 1. Auto-génération du slug ───
function generateSlug() {
    const title = document.getElementById('titleField').value;
    const slugField = document.getElementById('slugField');
    
    // Ne génère que si le champ slug est vide (création) ou si on veut forcer
    if (!slugField.value || slugField.dataset.auto === 'true') {
        slugField.value = title
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .substring(0, 150);
        slugField.dataset.auto = 'true';
    }
}

// Marquer comme auto si l'utilisateur n'a pas encore touché le slug
document.getElementById('slugField').addEventListener('input', function() {
    this.dataset.auto = this.value ? 'false' : 'true';
});

// ─── 2. Toggle featured media ───
function toggleFeatured(type) {
    document.getElementById('featured-image').style.display = type === 'image' ? 'block' : 'none';
    document.getElementById('featured-embed').style.display = type === 'video_embed' ? 'block' : 'none';
}

// ─── 3. Prévisualisation image featured ───
function previewFeaturedImage(input) {
    const containerNew = document.getElementById('previewNewImage');
    const imgNew = document.getElementById('previewNewImageTag');
    const existing = document.getElementById('existingImage');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imgNew.src = e.target.result;
            containerNew.style.display = 'block';
            // Cacher l'ancienne image seulement si on a une nouvelle
            if (existing) existing.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        // Si l'utilisateur annule la sélection, remontrer l'ancienne
        containerNew.style.display = 'none';
        if (existing) existing.style.display = 'block';
    }
}

// ─── 4. Prévisualisation vidéo embed ───
function previewFeaturedVideo() {
    const url = document.getElementById('videoEmbedUrl').value.trim();
    const container = document.getElementById('previewVideo');
    const box = document.getElementById('videoPreviewContainer');
    
    if (!url) {
        container.style.display = 'none';
        return;
    }
    
    // Conversion simple YouTube/Vimeo pour preview
    let embedUrl = '';
    const ytMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/);
    const vmMatch = url.match(/vimeo\.com\/(\d+)/);
    
    if (ytMatch) {
        embedUrl = 'https://www.youtube.com/embed/' + ytMatch[1];
    } else if (vmMatch) {
        embedUrl = 'https://player.vimeo.com/video/' + vmMatch[1];
    }
    
    if (embedUrl) {
        box.innerHTML = '<iframe src="' + embedUrl + '" style="width:100%;height:270px;border:none;border-radius:6px" allowfullscreen loading="lazy"></iframe>';
        container.style.display = 'block';
    } else {
        box.innerHTML = '<p style="color:#e74c3c;font-size:13px">Invalid URL. Use YouTube or Vimeo.</p>';
        container.style.display = 'block';
    }
}

// ─── 5. Prévisualisation galerie images ───
function previewGalleryImages(input) {
    const container = document.getElementById('galleryPreview');
    container.innerHTML = '';
    
    if (input.files) {
        Array.from(input.files).forEach(file => {
            if (!file.type.startsWith('image/')) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.style.cssText = 'position:relative;border-radius:6px;overflow:hidden;border:1px solid #eee';
                div.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:80px;object-fit:cover">' +
                    '<span style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.6);color:#fff;font-size:10px;padding:2px 6px">' + 
                    (file.size / 1024).toFixed(1) + ' KB</span>';
                container.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
}

// ─── 6. Prévisualisation galerie embeds (avant save) ───
function previewGalleryEmbeds() {
    const container = document.getElementById('galleryEmbedPreview');
    const textarea = document.getElementById('galleryEmbeds');
    container.innerHTML = '';
    
    if (!textarea) return;
    
    const lines = textarea.value.split('\n').map(l => l.trim()).filter(l => l !== '');
    
    lines.forEach((url, idx) => {
        let embedUrl = '';
        const ytMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/);
        const vmMatch = url.match(/vimeo\.com\/(\d+)/);
        
        if (ytMatch) {
            embedUrl = 'https://www.youtube.com/embed/' + ytMatch[1];
        } else if (vmMatch) {
            embedUrl = 'https://player.vimeo.com/video/' + vmMatch[1];
        }
        
        const div = document.createElement('div');
        div.style.cssText = 'background:#f8f9fa;border-radius:6px;overflow:hidden;border:1px solid #eee;position:relative';
        
        if (embedUrl) {
            div.innerHTML = '<iframe src="' + embedUrl + '" style="width:100%;height:120px;border:none" allowfullscreen loading="lazy"></iframe>' +
                '<div style="position:absolute;top:4px;right:4px;background:#27ae60;color:#fff;font-size:10px;padding:2px 6px;border-radius:4px">Preview ' + (idx+1) + '</div>';
        } else {
            div.innerHTML = '<div style="padding:20px;text-align:center;color:#e74c3c;font-size:12px">❌ Invalid URL<br>' + url.substring(0,30) + '...</div>';
        }
        
        container.appendChild(div);
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>