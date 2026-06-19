<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/MessageController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin','superviseur']);

$ctrl = new MessageController($db);
$id = (int)($_GET['id'] ?? 0);
$msg = $ctrl->get($id);

if (!$msg) {
    header('Location: message_list.php?error=Message not found');
    exit;
}

// Auto mark as read if new
if ($msg['status'] === 'new') {
    $ctrl->markRead($id, $auth->id(), $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $_SERVER['HTTP_USER_AGENT'] ?? '');
    $msg['status'] = 'read';
}

// Get all admins for assignment
$admins = $db->query("SELECT id, username, full_name FROM admins WHERE is_active = 1 ORDER BY username")->fetchAll();

$pageTitle = 'Message: ' . htmlspecialchars($msg['subject'] ?: 'No subject');
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>✉️ Message Detail</h1>
        <a href="message_list.php" class="btn btn-secondary">← Back to Inbox</a>
    </div>
    
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px">
        <!-- Message Content -->
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid #eee">
                <div>
                    <span class="badge <?= match($msg['status']){'new'=>'badge-red','read'=>'badge-orange','replied'=>'badge-green','archived'=>'badge-gray',default=>'badge-gray'} ?>"><?= $msg['status'] ?></span>
                    <span class="badge badge-gray" style="margin-left:6px"><?= str_replace('_', ' ', $msg['type']) ?></span>
                </div>
                <span style="font-size:13px;color:#95a5a6"><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></span>
            </div>
            
            <div style="margin-bottom:16px">
                <p style="margin-bottom:4px"><strong>From:</strong> <?= htmlspecialchars($msg['name']) ?> &lt;<?= htmlspecialchars($msg['email']) ?>&gt;</p>
                <?php if ($msg['subject']): ?>
                    <p><strong>Subject:</strong> <?= htmlspecialchars($msg['subject']) ?></p>
                <?php endif; ?>
                <p style="font-size:12px;color:#95a5a6">IP: <?= htmlspecialchars($msg['ip_address'] ?? 'unknown') ?></p>
            </div>
            
            <div style="background:#f8f9fa;padding:16px;border-radius:6px;border-left:3px solid #3498db">
                <p style="line-height:1.7;color:#333;white-space:pre-wrap"><?= htmlspecialchars($msg['message']) ?></p>
            </div>
            
            <div style="display:flex;gap:12px;margin-top:20px">
                <a href="mailto:<?= htmlspecialchars($msg['email']) ?>?subject=Re: <?= htmlspecialchars($msg['subject'] ?: 'Your message') ?>&body=Dear <?= htmlspecialchars($msg['name']) ?>,%0D%0A%0D%0A" 
                   class="btn btn-primary" target="_blank">✉️ Reply via Email</a>
                
                <?php if ($msg['status'] !== 'archived'): ?>
                    <a href="message_api.php?action=archive&id=<?= (int)$msg['id'] ?>&csrf_token=<?= urlencode(Csrf::generate()) ?>" 
                       class="btn btn-secondary">📦 Archive</a>
                <?php endif; ?>
                
                <?php if ($auth->hasRole(['super_admin','admin'])): ?>
                    <a href="message_api.php?action=delete&id=<?= (int)$msg['id'] ?>&csrf_token=<?= urlencode(Csrf::generate()) ?>" 
                       class="btn btn-danger" onclick="return confirm('Delete permanently?')">🗑 Delete</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sidebar Actions -->
        <div>
            <div class="card" style="margin-bottom:24px">
                <h3 style="margin-bottom:16px;font-size:16px">👤 Assignment</h3>
                <form method="POST" action="message_api.php">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" name="id" value="<?= (int)$msg['id'] ?>">
                    
                    <select name="assigned_to" style="width:100%;margin-bottom:12px">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($admins as $a): ?>
                        <option value="<?= (int)$a['id'] ?>" <?= ($msg['assigned_to'] == $a['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['full_name'] ?: $a['username']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" style="width:100%">Assign</button>
                </form>
                
                <?php if ($msg['assigned_name']): ?>
                    <p style="margin-top:12px;font-size:13px;color:#555">Currently assigned to: <strong><?= htmlspecialchars($msg['assigned_name']) ?></strong></p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h3 style="margin-bottom:16px;font-size:16px">📝 Internal Notes</h3>
                <form method="POST" action="message_api.php">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="notes">
                    <input type="hidden" name="id" value="<?= (int)$msg['id'] ?>">
                    
                    <textarea name="notes" rows="4" style="width:100%;margin-bottom:12px" placeholder="Private notes visible only in dashboard..."><?= htmlspecialchars($msg['internal_notes'] ?? '') ?></textarea>
                    <button type="submit" class="btn btn-primary btn-sm" style="width:100%">Save Notes</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>