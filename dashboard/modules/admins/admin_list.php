<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/AdminController.php';

$auth = new Auth($db);
$auth->requireRole('super_admin'); // Seul super_admin peut gérer les comptes

$ctrl = new AdminController($db);
$admins = $ctrl->list();
$superCount = (new Admin($db))->countSuperAdmins();
$currentId = (int)$auth->id();

$pageTitle = 'Administrators';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>👥 Administrators</h1>
        <a href="admin_form.php" class="btn btn-primary">+ New Admin</a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <div class="card">
        <p class="meta">Super admin limit: <strong><?= $superCount ?>/3</strong></p>
        
            <table class="table">
    <thead>
        <tr>
            <th>ID</th><th>User</th><th>Role</th><th>Status</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($admins as $a): ?>
        <tr>
            <td><?= $a['id'] ?></td>
            <td><?= htmlspecialchars($a['username']) ?></td>
            <td><?= htmlspecialchars($a['role']) ?></td>
            <td><?= $a['is_active'] ?></td>
            <td>
                <a href="admin_form.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm">Edit</a>
                
                <?php 
                $rowId = (int)$a['id'];
                $isSelf = ($rowId === $currentId);
                ?>

                <?php if ($isSelf): ?>
                    <span style="color:#95a5a6;font-size:12px">(You)</span>
                <?php else: ?>
                    <a href="admin_api.php?action=delete&id=<?= $rowId ?>&csrf_token=<?= Csrf::generate() ?>" 
                       class="btn btn-sm btn-danger" 
                       onclick="return confirm('Supprimer ce compte ?')">Delete</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
        </table>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>