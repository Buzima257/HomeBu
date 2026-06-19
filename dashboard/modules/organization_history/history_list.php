<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/HistoryController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new HistoryController($db);
$page = (int)($_GET['page'] ?? 1);
$list = $ctrl->list($page);

$pageTitle = 'Organization History';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>📅 Organization History</h1>
        <a href="history_form.php" class="btn btn-primary">+ New Event</a>
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
                <tr><th>Date</th><th>Title</th><th>Icon</th><th>Major</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($list['rows'] as $h): ?>
                <tr>
                    <td>
                        <strong><?= (int)$h['year'] ?></strong>
                        <?= $h['month'] ? '/' . str_pad((string)$h['month'], 2, '0', STR_PAD_LEFT) : '' ?>
                        <?= $h['day'] ? '/' . str_pad((string)$h['day'], 2, '0', STR_PAD_LEFT) : '' ?>
                    </td>
                    <td><?= htmlspecialchars($h['title']) ?></td>
                    <td><?= htmlspecialchars($h['icon']) ?></td>
                    <td><?= $h['is_major'] ? '🔴' : '—' ?></td>
                    <td>
                        <a href="history_form.php?id=<?= (int)$h['id'] ?>" class="btn btn-sm">Edit</a>
                        <a href="history_api.php?action=delete&id=<?= (int)$h['id'] ?>&csrf_token=<?= urlencode(Csrf::generate()) ?>" 
                           class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>