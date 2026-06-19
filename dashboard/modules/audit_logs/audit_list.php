<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/AuditLog.php';

$auth = new Auth($db);
$auth->requireRole('super_admin'); // STRICT

$model = new AuditLog($db);
$filters = [
    'admin_id' => $_GET['admin_id'] ?? '',
    'action' => $_GET['action'] ?? '',
    'target_type' => $_GET['target_type'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'ip' => $_GET['ip'] ?? ''
];
$page = (int)($_GET['page'] ?? 1);
$logs = $model->findAll(array_filter($filters), $page);

$actions = $model->getDistinctActions();
$targets = $model->getDistinctTargets();
$admins = $db->query("SELECT id, username FROM admins ORDER BY username")->fetchAll();

$pageTitle = 'Audit Logs';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>📜 Audit Logs</h1>
        <span class="meta">Activity tracking & integrity</span>
    </div>
    
    <!-- Filters -->
    <div class="card" style="margin-bottom:24px">
        <form method="GET" class="form-grid">
            <div class="form-group">
                <label>Admin</label>
                <select name="admin_id">
                    <option value="">All</option>
                    <?php foreach ($admins as $a): ?>
                    <option value="<?= (int)$a['id'] ?>" <?= ($filters['admin_id'] == $a['id']) ? 'selected' : '' ?>><?= htmlspecialchars($a['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Action</label>
                <select name="action">
                    <option value="">All</option>
                    <?php foreach ($actions as $act): ?>
                    <option value="<?= htmlspecialchars($act) ?>" <?= $filters['action'] === $act ? 'selected' : '' ?>><?= htmlspecialchars($act) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Target Type</label>
                <select name="target_type">
                    <option value="">All</option>
                    <?php foreach ($targets as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>" <?= $filters['target_type'] === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Date From</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>">
            </div>
            <div class="form-group">
                <label>Date To</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>">
            </div>
            <div class="form-group">
                <label>IP Address</label>
                <input type="text" name="ip" value="<?= htmlspecialchars($filters['ip']) ?>" placeholder="192.168...">
            </div>
            <div class="form-actions full-width">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="audit_list.php" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
    
    <div class="card">
        <p class="meta" style="margin-bottom:16px"><?= $logs['total'] ?> entries found</p>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Admin</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>ID</th>
                    <th>IP</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs['rows'] as $log): ?>
                <tr>
                    <td style="font-size:12px;white-space:nowrap"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                    <td><?= htmlspecialchars($log['username'] ?: 'System') ?></td>
                    <td><span class="badge badge-gray" style="font-size:11px"><?= htmlspecialchars($log['action']) ?></span></td>
                    <td><?= htmlspecialchars($log['target_type'] ?: '—') ?></td>
                    <td><?= $log['target_id'] ?: '—' ?></td>
                    <td style="font-size:12px;color:#95a5a6"><?= htmlspecialchars($log['ip_address'] ?: '—') ?></td>
                    <td style="font-size:12px;max-width:300px">
                        <?php if ($log['new_values']): ?>
                            <details>
                                <summary style="cursor:pointer;color:#3498db">View changes</summary>
                                <pre style="margin-top:8px;background:#f8f9fa;padding:8px;border-radius:4px;overflow-x:auto;font-size:11px"><?= htmlspecialchars($log['new_values']) ?></pre>
                            </details>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($logs['pages'] > 1): ?>
        <div style="display:flex;gap:8px;justify-content:center;margin-top:20px">
            <?php for ($i = 1; $i <= $logs['pages']; $i++): ?>
                <a href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>" 
                   class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>