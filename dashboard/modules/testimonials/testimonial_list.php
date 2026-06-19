<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/TestimonialController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new TestimonialController($db);
$page = (int)($_GET['page'] ?? 1);
$list = $ctrl->list($page);

$pageTitle = 'Testimonials';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1>💬 Testimonials</h1>
        <a href="testimonial_form.php" class="btn btn-primary">+ New Testimonial</a>
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
                <tr><th>Name</th><th>Role</th><th>Video</th><th>Featured</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($list['rows'] as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['name']) ?></td>
                    <td><?= htmlspecialchars($t['role'] ?? '—') ?></td>
                    <td><?= $t['video_type'] !== 'none' ? '🎬 ' . $t['video_type'] : '—' ?></td>
                    <td><?= $t['is_featured'] ? '⭐' : '—' ?></td>
                    <td>
                        <a href="testimonial_form.php?id=<?= (int)$t['id'] ?>" class="btn btn-sm">Edit</a>
                        <a href="testimonial_api.php?action=delete&id=<?= (int)$t['id'] ?>&csrf_token=<?= urlencode(Csrf::generate()) ?>" 
                           class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>