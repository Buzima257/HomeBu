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
$sections = $ctrl->list();

$pageTitle = 'Static Sections';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>📄 Static Sections</h1>
        <span class="meta">Mission, Vision, Sectors, Values, Zones, Donate</span>
    </div>
    
    <?php if (!$canEdit): ?>
        <div class="alert alert-error">Static sections are locked. Only super_admin can edit.</div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px">
        <?php foreach ($sections as $s): 
            $layoutLabel = match($s['layout_type']) {
                'text-left' => 'Text Left', 'text-right' => 'Text Right',
                'text-top' => 'Text Top', 'text-bottom' => 'Text Bottom',
                'full-width' => 'Full Width', 'gallery-grid' => 'Gallery Grid',
                default => $s['layout_type']
            };
        ?>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px">
                <div>
                    <h3 style="font-size:16px;color:#2c3e50"><?= htmlspecialchars($s['title_en'] ?: $s['slug']) ?></h3>
                    <span class="badge" style="background:#ecf0f1;color:#555"><?= $layoutLabel ?></span>
                    <?= $s['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-gray">Inactive</span>' ?>
                </div>
                <div style="display:flex;gap:6px">
                    <a href="section_form.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm">Edit</a>
                </div>
            </div>
            <p style="color:#7f8c8d;font-size:13px;margin-bottom:12px"><?= htmlspecialchars(substr($s['content_en'] ?? '', 0, 120)) ?>...</p>
            
            <!-- Mini preview -->
            <div style="background:#f8f9fa;border-radius:6px;padding:12px;margin-bottom:12px;font-size:12px;color:#555;border:1px solid #eee">
                <strong>EN:</strong> <?= htmlspecialchars($s['title_en'] ?: '—') ?><br>
                <strong>FR:</strong> <?= htmlspecialchars($s['title_fr'] ?: '—') ?>
            </div>
            
            <div style="display:flex;gap:8px;align-items:center;justify-content:space-between">
                <span style="font-size:12px;color:#95a5a6">Order: <?= (int)$s['display_order'] ?> | BG: <?= htmlspecialchars($s['bg_color']) ?></span>
                <a href="section_form.php?id=<?= (int)$s['id'] ?>#livePreview" class="btn btn-sm btn-secondary" style="font-size:12px">👁 Preview</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>