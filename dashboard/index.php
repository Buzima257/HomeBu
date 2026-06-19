<?php
declare(strict_types=1);

require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/core/Auth.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin','superviseur']);

$_SESSION['last_activity'] = time();

$role = $auth->role();
$username = $auth->username();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>📊 Dashboard Overview</h1>
        <span class="badge badge-<?= match($role){'super_admin'=>'red','admin'=>'orange','superviseur'=>'green'} ?>"><?= $role ?></span>
    </div>
    
    <div class="alert alert-success">
        ✅ Welcome <strong><?= htmlspecialchars($username) ?></strong> | Session active | IP: <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'unknown') ?>
    </div>

    <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px">
        <div class="card">
            <h3>📝 Articles</h3>
            <p>Manage activities and publications</p>
        </div>
        <div class="card">
            <h3>💬 Testimonials</h3>
            <p>Beneficiary stories</p>
        </div>
        <div class="card">
            <h3>👤 Team</h3>
            <p>Leadership directory</p>
        </div>
        <div class="card">
            <h3>📅 History</h3>
            <p>Organization timeline</p>
        </div>
        <div class="card">
            <h3>✉️ Messages</h3>
            <p>Inbox & donations</p>
        </div>
        <div class="card">
            <h3>⚙️ Settings</h3>
            <p>Global configuration</p>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>