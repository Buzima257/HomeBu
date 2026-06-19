<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/FormController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new FormController($db);
$forms = $ctrl->list();

$pageTitle = 'Forms Builder';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>📋 Dynamic Forms</h1>
        <a href="form_edit.php" class="btn btn-primary">+ New Form</a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    
    <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px">
        <?php foreach ($forms as $f): 
            $statusClass = match($f['status']) {
                'active' => 'badge-green',
                'inactive' => 'badge-orange',
                'closed' => 'badge-gray',
                default => 'badge-gray'
            };
            $fieldCount = $db->prepare("SELECT COUNT(*) FROM dynamic_form_fields WHERE form_id = ?");
            $fieldCount->execute([$f['id']]);
            $fields = (int)$fieldCount->fetchColumn();
        ?>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px">
                <div>
                    <h3 style="font-size:16px;color:#2c3e50"><?= htmlspecialchars($f['title']) ?></h3>
                    <span class="badge <?= $statusClass ?>"><?= $f['status'] ?></span>
                    <span class="badge badge-gray"><?= $f['type'] ?></span>
                </div>
            </div>
            <p style="color:#7f8c8d;font-size:13px;margin-bottom:8px"><?= htmlspecialchars(substr($f['description'] ?? '', 0, 100)) ?></p>
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:12px;color:#95a5a6"><?= $fields ?> fields | slug: <?= htmlspecialchars($f['slug']) ?></span>
                <div style="display:flex;gap:6px">
                    <a href="form_edit.php?id=<?= (int)$f['id'] ?>" class="btn btn-sm">Edit</a>
                    <a href="form_submissions.php?form_id=<?= (int)$f['id'] ?>" class="btn btn-sm btn-secondary">Submissions</a>
                    <a href="form_api.php?action=delete&id=<?= (int)$f['id'] ?>&csrf_token=<?= urlencode(Csrf::generate()) ?>" 
                       class="btn btn-sm btn-danger" onclick="return confirm('Delete this form and all submissions?')">Delete</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>