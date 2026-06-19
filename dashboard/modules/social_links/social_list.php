<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/SocialLinkController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new SocialLinkController($db);
$links = $ctrl->list();

$pageTitle = 'Social Links';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>🔗 Social Links</h1>
        <a href="social_form.php" class="btn btn-primary">+ New Link</a>
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
                <tr><th>Icon</th><th>Platform</th><th>URL</th><th>Order</th><th>Active</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($links as $l): ?>
                <tr>
                    <td>
                        <?php if ($l['icon']): ?>
                            <img src="<?= UPLOAD_URL . htmlspecialchars($l['icon']) ?>" style="height:24px;width:24px;object-fit:contain">
                        <?php else: ?>
                            <span style="font-size:20px">🔗</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($l['platform']) ?></td>
                    <td><a href="<?= htmlspecialchars($l['url']) ?>" target="_blank" style="font-size:12px"><?= htmlspecialchars(substr($l['url'], 0, 35)) ?></a></td>
                    <td><?= (int)$l['display_order'] ?></td>
                    <td><?= $l['is_active'] ? '✅' : '—' ?></td>
                    <td>
                        <a href="social_form.php?id=<?= (int)$l['id'] ?>" class="btn btn-sm">Edit</a>
                        <a href="social_api.php?action=delete&id=<?= (int)$l['id'] ?>&csrf_token=<?= urlencode(Csrf::generate()) ?>" 
                           class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>