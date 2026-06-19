<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/FormController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new FormController($db);
$formId = (int)($_GET['form_id'] ?? 0);
$form = $ctrl->get($formId);
if (!$form) { header('Location: form_list.php?error=Form not found'); exit; }

$status = $_GET['status'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$subs = $ctrl->getSubmissions($formId, $status, $page);

$pageTitle = 'Submissions: ' . htmlspecialchars($form['title']);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>📥 Submissions</h1>
        <span class="badge badge-gray"><?= htmlspecialchars($form['title']) ?></span>
    </div>
    
    <div style="display:flex;gap:8px;margin-bottom:20px">
        <a href="?form_id=<?= $formId ?>&status=" class="btn btn-sm <?= $status === '' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
        <a href="?form_id=<?= $formId ?>&status=new" class="btn btn-sm <?= $status === 'new' ? 'btn-primary' : 'btn-secondary' ?>">New</a>
        <a href="?form_id=<?= $formId ?>&status=read" class="btn btn-sm <?= $status === 'read' ? 'btn-primary' : 'btn-secondary' ?>">Read</a>
        <a href="?form_id=<?= $formId ?>&status=processed" class="btn btn-sm <?= $status === 'processed' ? 'btn-primary' : 'btn-secondary' ?>">Processed</a>
    </div>
    
    <div class="card">
        <table class="table">
            <thead>
                <tr><th>ID</th><th>Data</th><th>Status</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($subs['rows'] as $s): 
                    $data = json_decode($s['data'] ?? '{}', true);
                    $preview = [];
                    foreach ($data as $k => $v) {
                        if (is_string($v) && strlen($v) > 30) $v = substr($v, 0, 30) . '...';
                        $preview[] = "$k: $v";
                    }
                ?>
                <tr>
                    <td>#<?= (int)$s['id'] ?></td>
                    <td style="font-size:12px;color:#555;max-width:400px;word-break:break-all">
                        <?= htmlspecialchars(implode(' | ', $preview)) ?>
                    </td>
                    <td><span class="badge badge-gray"><?= $s['status'] ?></span></td>
                    <td style="font-size:12px"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
                    <td>
                        <a href="submission_detail.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>