<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/PartnerController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new PartnerController($db);
$partners = $ctrl->list();

$pageTitle = 'Partners';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>🤝 Partners & Sponsors</h1>
        <a href="partner_form.php" class="btn btn-primary">+ New Partner</a>
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
                <tr>
                    <th style="width:100px">Logo</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Link</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($partners as $p): 
                    $typeColor = match($p['partner_type']) {
                        'technical' => 'badge-blue',
                        'financial' => 'badge-green',
                        'media' => 'badge-orange',
                        default => 'badge-gray'
                    };
                ?>
                <tr>
                    <td>
                        <?php if ($p['logo']): ?>
                            <div style="width:80px;height:80px;background:#fff;border-radius:8px;border:1px solid #eee;display:flex;align-items:center;justify-content:center;padding:8px">
                                <img src="<?= UPLOAD_URL . htmlspecialchars($p['logo']) ?>" 
                                     style="max-width:100%;max-height:100%;object-fit:contain" 
                                     alt="<?= htmlspecialchars($p['name']) ?>">
                            </div>
                        <?php else: ?>
                            <div style="width:80px;height:80px;background:#f8f9fa;border-radius:8px;border:1px dashed #ddd;display:flex;align-items:center;justify-content:center;color:#95a5a6;font-size:24px">
                                🏢
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:600;color:#2c3e50;font-size:15px"><?= htmlspecialchars($p['name']) ?></div>
                        <?php if ($p['link']): ?>
                            <a href="<?= htmlspecialchars($p['link']) ?>" target="_blank" rel="noopener noreferrer" style="font-size:12px;color:#3498db">
                                🔗 <?= htmlspecialchars(parse_url($p['link'], PHP_URL_HOST) ?: substr($p['link'], 0, 30)) ?>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $typeColor ?>"><?= ucfirst(htmlspecialchars($p['partner_type'])) ?></span></td>
                    <td style="font-size:13px;color:#7f8c8d;max-width:200px;word-break:break-all">
                        <?= $p['link'] ? htmlspecialchars(substr($p['link'], 0, 40)) . (strlen($p['link']) > 40 ? '...' : '') : '—' ?>
                    </td>
                    <td><?= (int)$p['display_order'] ?></td>
                    <td>
                        <?php if ($p['is_active']): ?>
                            <span class="status active">Active</span>
                        <?php else: ?>
                            <span class="status inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="partner_form.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm">Edit</a>
                        <a href="partner_api.php?action=delete&id=<?= (int)$p['id'] ?>&csrf_token=<?= urlencode(Csrf::generate()) ?>" 
                           class="btn btn-sm btn-danger" onclick="return confirm('Delete this partner?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>