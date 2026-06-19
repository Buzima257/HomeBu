<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/AdminController.php';

$auth = new Auth($db);
$auth->requireRole('super_admin');

$ctrl = new AdminController($db);
$isEdit = isset($_GET['id']);
$admin = $isEdit ? $ctrl->get((int)$_GET['id']) : null;

if ($isEdit && !$admin) {
    header('Location: admin_list.php?error=Admin not found');
    exit;
}

$pageTitle = $isEdit ? 'Edit Admin' : 'New Admin';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1><?= $isEdit ? '✏️ Edit: ' . htmlspecialchars($admin['full_name'] ?: $admin['username']) : '➕ New Administrator' ?></h1>
    </div>
    
    <div class="card">
        <form method="POST" action="admin_api.php" class="form-grid">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $admin['id'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" value="<?= $isEdit ? htmlspecialchars($admin['username']) : '' ?>" 
                       <?= $isEdit ? 'readonly' : '' ?> required maxlength="50" pattern="[a-zA-Z0-9_]+">
                <small>1-50 chars, alphanumeric + underscore only</small>
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" value="<?= $isEdit ? htmlspecialchars($admin['email']) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?= $isEdit ? htmlspecialchars($admin['full_name'] ?? '') : '' ?>">
            </div>
            
            <div class="form-group">
                <label>Role *</label>
                <select name="role" required>
                    <option value="superviseur" <?= ($isEdit && $admin['role'] === 'superviseur') ? 'selected' : '' ?>>Superviseur (Read-only)</option>
                    <option value="admin" <?= ($isEdit && $admin['role'] === 'admin') ? 'selected' : '' ?>>Admin (Content manager)</option>
                    <option value="super_admin" <?= ($isEdit && $admin['role'] === 'super_admin') ? 'selected' : '' ?>>Super Admin (Full access)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone" value="<?= $isEdit ? htmlspecialchars($admin['phone'] ?? '') : '' ?>">
            </div>
            
            <div class="form-group full-width">
                <label>Biography / Description</label>
                <textarea name="description" rows="4"><?= $isEdit ? htmlspecialchars($admin['description'] ?? '') : '' ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Password <?= $isEdit ? '(leave blank to keep current)' : '*' ?></label>
                <input type="password" name="password" <?= $isEdit ? '' : 'required' ?> minlength="8">
                <small>Minimum 8 characters, bcrypt hashed</small>
            </div>
            
            <div class="form-group">
                <label>Status</label>
                <label class="toggle">
                    <input type="checkbox" name="is_active" value="1" <?= (!$isEdit || $admin['is_active']) ? 'checked' : '' ?>>
                    <span>Account active</span>
                </label>
            </div>
            
            <div class="form-actions full-width">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Account' ?></button>
                <a href="admin_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>