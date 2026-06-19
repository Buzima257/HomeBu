<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/TeamMemberController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new TeamMemberController($db);
$page = (int)($_GET['page'] ?? 1);
$filter = $_GET['filter'] ?? '';
$list = $ctrl->list($filter, $page);

$pageTitle = 'Team Members';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>👤 Team Members</h1>
        <a href="team_form.php" class="btn btn-primary">+ New Member</a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div style="display:flex;gap:12px;margin-bottom:16px">
            <a href="?filter=" class="btn btn-sm <?= $filter === '' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
            <a href="?filter=leadership" class="btn btn-sm <?= $filter === 'leadership' ? 'btn-primary' : 'btn-secondary' ?>">Leadership</a>
        </div>
        
        <table class="table">
            <thead>
                <tr><th>Photo</th><th>Name</th><th>Role</th><th>Dept</th><th>Leadership</th><th>Featured</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($list['rows'] as $m): ?>
                <tr>
                    <td>
                        <?php if ($m['photo']): ?>
                            <img src="<?= UPLOAD_URL . htmlspecialchars($m['photo']) ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover">
                        <?php else: ?>
                            <div class="avatar-sm" style="width:40px;height:40px"><?= strtoupper(substr($m['full_name'], 0, 1)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($m['full_name']) ?><br><small><?= htmlspecialchars($m['title'] ?? '') ?></small></td>
                    <td><?= htmlspecialchars($m['role']) ?></td>
                    <td><?= htmlspecialchars($m['department'] ?? '—') ?></td>
                    <td><?= $m['is_leadership'] ? '✅' : '—' ?></td>
                    <td><?= $m['is_featured'] ? '⭐' : '—' ?></td>
                    <td>
                        <a href="team_form.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm">Edit</a>
                        <a href="team_api.php?action=delete&id=<?= (int)$m['id'] ?>&csrf_token=<?= urlencode(Csrf::generate()) ?>" 
                           class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>