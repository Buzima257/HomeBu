<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/ArticleController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new ArticleController($db);
$page = (int)($_GET['page'] ?? 1);
$langFilter = $_GET['lang'] ?? '';
$list = $ctrl->list($langFilter, $page);

$pageTitle = 'Articles';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>📝 Articles</h1>
        <a href="article_form.php" class="btn btn-primary">+ New Article</a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div style="display:flex;gap:12px;margin-bottom:16px;align-items:center">
            <form method="GET" style="display:flex;gap:8px">
                <select name="lang" onchange="this.form.submit()">
                    <option value="">All languages</option>
                    <option value="en" <?= $langFilter === 'en' ? 'selected' : '' ?>>English</option>
                    <option value="fr" <?= $langFilter === 'fr' ? 'selected' : '' ?>>Français</option>
                </select>
            </form>
            <span class="meta"><?= $list['total'] ?> articles</span>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Lang</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Gallery</th>
                    <th>Published</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list['rows'] as $a): 
                    $statusClass = match($a['status']) {
                        'published' => 'badge-green',
                        'draft' => 'badge-orange',
                        'archived' => 'badge-gray',
                        default => 'badge-gray'
                    };
                ?>
                <tr>
                    <td><?= (int)$a['id'] ?></td>
                    <td>
                        <div class="name"><?= htmlspecialchars($a['title']) ?></div>
                        <div class="email">/<?= htmlspecialchars($a['slug']) ?></div>
                    </td>
                    <td><span class="badge"><?= strtoupper($a['lang']) ?></span></td>
                    <td><span class="badge <?= $statusClass ?>"><?= $a['status'] ?></span></td>
                    <td><?= (int)$a['view_count'] ?></td>
                    <td><?= (int)$a['gallery_count'] ?></td>
                    <td><?= $a['published_at'] ? date('d/m/Y', strtotime($a['published_at'])) : '—' ?></td>
                    <td>
                        <a href="article_form.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm">Edit</a>
                        <a href="article_api.php?action=delete&id=<?= (int)$a['id'] ?>&csrf_token=<?= urlencode(Csrf::generate()) ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Delete this article and all its media?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($list['pages'] > 1): ?>
        <div style="display:flex;gap:8px;justify-content:center;margin-top:20px">
            <?php for ($i = 1; $i <= $list['pages']; $i++): ?>
                <a href="?page=<?= $i ?>&lang=<?= htmlspecialchars($langFilter) ?>" 
                   class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>