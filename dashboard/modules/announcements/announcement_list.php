<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/AnnouncementController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new AnnouncementController($db);
$page = (int)($_GET['page'] ?? 1);
$statusFilter = $_GET['status'] ?? '';
$list = $ctrl->list($statusFilter, $page);

$pageTitle = 'Announcements';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>📢 Announcements</h1>
        <a href="announcement_form.php" class="btn btn-primary">+ New Announcement</a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div style="display:flex;gap:8px;margin-bottom:16px">
            <a href="?status=" class="btn btn-sm <?= $statusFilter === '' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
            <a href="?status=active" class="btn btn-sm <?= $statusFilter === 'active' ? 'btn-primary' : 'btn-secondary' ?>">Active</a>
            <a href="?status=expired" class="btn btn-sm <?= $statusFilter === 'expired' ? 'btn-primary' : 'btn-secondary' ?>">Expired</a>
            <a href="?status=draft" class="btn btn-sm <?= $statusFilter === 'draft' ? 'btn-primary' : 'btn-secondary' ?>">Draft</a>
        </div>
        
        <table class="table">
            <thead>
                <tr><th>Title</th><th>Lang</th><th>Start</th><th>End</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($list['rows'] as $a): 
                    $statusClass = match($a['status']) {
                        'active' => 'badge-green',
                        'expired' => 'badge-gray',
                        'draft' => 'badge-orange',
                        default => 'badge-gray'
                    };
                    $isExpired = ($a['end_date'] && $a['end_date'] < date('Y-m-d'));
                ?>
                <tr>
                    <td><?= htmlspecialchars($a['title']) ?></td>
                    <td><?= strtoupper($a['lang']) ?></td>
                    <td><?= $a['start_date'] ?: '—' ?></td>
                    <td><?= $a['end_date'] ?: '—' ?> <?= $isExpired ? '⚠️' : '' ?></td>
                    <td><span class="badge <?= $statusClass ?>"><?= $a['status'] ?></span></td>
                    <td>
                        <a href="announcement_form.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm">Edit</a>
                        <a href="announcement_api.php?action=delete&id=<?= (int)$a['id'] ?>&csrf_token=<?= urlencode(Csrf::generate()) ?>" 
                           class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>