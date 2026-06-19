<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/ImpactStatController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new ImpactStatController($db);
$stats = $ctrl->list();

$pageTitle = 'Impact Stats';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<<main class="content">
    <div class="page-header">
        <h1>📊 Impact Statistics</h1>
        <a href="stat_form.php" class="btn btn-primary">+ New Stat</a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <div class="card">
        <table class="table">
            <thead>
                <tr><th>Icon</th><th>Label EN</th><th>Label FR</th><th>Value</th><th>Order</th><th>Active</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $s): ?>
                <tr>
                    <td>
                        <?php if ($s['icon']): ?>
                            <img src="<?= UPLOAD_URL . htmlspecialchars($s['icon']) ?>" style="height:32px;width:32px;object-fit:contain">
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($s['label_en']) ?></td>
                    <td><?= htmlspecialchars($s['label_fr'] ?? '—') ?></td>
                    <td><strong><?= (int)$s['value'] ?></strong> <?= htmlspecialchars($s['suffix_en'] ?? '') ?></td>
                    <td><?= (int)$s['display_order'] ?></td>
                    <td><?= $s['is_active'] ? '✅' : '—' ?></td>
                    <td>
                        <a href="stat_form.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm">Edit</a>
                        <a href="stat_api.php?action=delete&id=<?= (int)$s['id'] ?>&csrf_token=<?= urlencode(Csrf::generate()) ?>" 
                           class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>