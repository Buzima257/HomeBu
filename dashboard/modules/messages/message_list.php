<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/MessageController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin','superviseur']);

$ctrl = new MessageController($db);
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$list = $ctrl->list($status, $type, $page);

// Counts for tabs
$newCount = $ctrl->countByStatus('new');
$readCount = $ctrl->countByStatus('read');
$repliedCount = $ctrl->countByStatus('replied');
$archivedCount = $ctrl->countByStatus('archived');

$pageTitle = 'Messages';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>✉️ Messages</h1>
        <span class="meta">Inbox & Donation Inquiries</span>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    
    <!-- Tabs -->
    <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
        <a href="?status=" class="btn btn-sm <?= $status === '' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
        <a href="?status=new" class="btn btn-sm <?= $status === 'new' ? 'btn-primary' : 'btn-secondary' ?>">
            New <?= $newCount > 0 ? '<span style="background:#e74c3c;color:#fff;padding:2px 6px;border-radius:10px;font-size:11px;margin-left:4px">' . $newCount . '</span>' : '' ?>
        </a>
        <a href="?status=read" class="btn btn-sm <?= $status === 'read' ? 'btn-primary' : 'btn-secondary' ?>">Read <?= $readCount ?></a>
        <a href="?status=replied" class="btn btn-sm <?= $status === 'replied' ? 'btn-primary' : 'btn-secondary' ?>">Replied <?= $repliedCount ?></a>
        <a href="?status=archived" class="btn btn-sm <?= $status === 'archived' ? 'btn-primary' : 'btn-secondary' ?>">Archived <?= $archivedCount ?></a>
        
        <div style="margin-left:auto;display:flex;gap:8px">
            <a href="?type=" class="btn btn-sm <?= $type === '' ? 'btn-primary' : 'btn-secondary' ?>">All Types</a>
            <a href="?type=contact" class="btn btn-sm <?= $type === 'contact' ? 'btn-primary' : 'btn-secondary' ?>">Contact</a>
            <a href="?type=donation_inquiry" class="btn btn-sm <?= $type === 'donation_inquiry' ? 'btn-primary' : 'btn-secondary' ?>">Donation</a>
        </div>
    </div>
    
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Type</th>
                    <th>From</th>
                    <th>Subject</th>
                    <th>Assigned</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list['rows'] as $m): 
                    $statusClass = match($m['status']) {
                        'new' => 'badge-red',
                        'read' => 'badge-orange',
                        'replied' => 'badge-green',
                        'archived' => 'badge-gray',
                        default => 'badge-gray'
                    };
                ?>
                <tr style="<?= $m['status'] === 'new' ? 'background:#fff8f0' : '' ?>">
                    <td><span class="badge <?= $statusClass ?>"><?= $m['status'] ?></span></td>
                    <td><span class="badge badge-gray"><?= str_replace('_', ' ', $m['type']) ?></span></td>
                    <td>
                        <div style="font-weight:500"><?= htmlspecialchars($m['name']) ?></div>
                        <div style="font-size:12px;color:#7f8c8d"><?= htmlspecialchars($m['email']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($m['subject'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($m['assigned_name'] ?: '—') ?></td>
                    <td style="font-size:12px;color:#95a5a6"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
                    <td>
                        <a href="message_detail.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm">Open</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($list['pages'] > 1): ?>
        <div style="display:flex;gap:8px;justify-content:center;margin-top:20px">
            <?php for ($i = 1; $i <= $list['pages']; $i++): ?>
                <a href="?status=<?= htmlspecialchars($status) ?>&type=<?= htmlspecialchars($type) ?>&page=<?= $i ?>" 
                   class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>