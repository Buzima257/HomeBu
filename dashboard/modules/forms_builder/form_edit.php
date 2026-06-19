<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/FormController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$ctrl = new FormController($db);
$isEdit = isset($_GET['id']);
$form = $isEdit ? $ctrl->get((int)$_GET['id']) : null;
$fields = $isEdit ? $ctrl->getFields((int)$_GET['id']) : [];

if ($isEdit && !$form) { header('Location: form_list.php?error=Not found'); exit; }

$pageTitle = $isEdit ? 'Edit Form' : 'New Form';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<main class="content">
    <div class="page-header">
        <h1><?= $isEdit ? '✏️ ' . htmlspecialchars($form['title']) : '➕ New Dynamic Form' ?></h1>
    </div>
    
    <!-- Form Settings -->
    <div class="card" style="margin-bottom:24px">
        <form method="POST" action="form_api.php" class="form-grid">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$form['id'] ?>"><?php endif; ?>
            
            <div class="form-group full-width">
                <label>Title *</label>
                <input type="text" name="title" value="<?= $isEdit ? htmlspecialchars($form['title']) : '' ?>" required maxlength="255">
            </div>
            <div class="form-group">
                <label>Slug</label>
                <input type="text" name="slug" value="<?= $isEdit ? htmlspecialchars($form['slug']) : '' ?>" placeholder="auto-generated">
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <option value="custom" <?= ($isEdit && $form['type'] === 'custom') || !$isEdit ? 'selected' : '' ?>>Custom</option>
                    <option value="recruitment" <?= ($isEdit && $form['type'] === 'recruitment') ? 'selected' : '' ?>>Recruitment</option>
                    <option value="training" <?= ($isEdit && $form['type'] === 'training') ? 'selected' : '' ?>>Training</option>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="inactive" <?= ($isEdit && $form['status'] === 'inactive') || !$isEdit ? 'selected' : '' ?>>Inactive</option>
                    <option value="active" <?= ($isEdit && $form['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="closed" <?= ($isEdit && $form['status'] === 'closed') ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>
            <div class="form-group full-width">
                <label>Description</label>
                <textarea name="description" rows="2"><?= $isEdit ? htmlspecialchars($form['description'] ?? '') : '' ?></textarea>
            </div>
            <div class="form-group">
                <label>Success Message EN</label>
                <input type="text" name="success_message_en" value="<?= $isEdit ? htmlspecialchars($form['success_message_en'] ?? '') : '' ?>" placeholder="Thank you for your submission">
            </div>
            <div class="form-group">
                <label>Success Message FR</label>
                <input type="text" name="success_message_fr" value="<?= $isEdit ? htmlspecialchars($form['success_message_fr'] ?? '') : '' ?>" placeholder="Merci pour votre soumission">
            </div>
            <div class="form-group">
                <label>Notification Email</label>
                <input type="email" name="notification_email" value="<?= $isEdit ? htmlspecialchars($form['notification_email'] ?? '') : '' ?>">
            </div>
            
            <div class="form-actions full-width">
                <button type="submit" class="btn btn-primary">Save Form</button>
                <a href="form_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <?php if ($isEdit): ?>
    <!-- Field Builder -->
    <div class="card" style="margin-bottom:24px">
        <h3 style="margin-bottom:16px">🔧 Field Builder</h3>
        
        <?php if (!empty($fields)): ?>
        <table class="table" style="margin-bottom:20px">
            <thead>
                <tr><th>Order</th><th>Label</th><th>Type</th><th>Required</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($fields as $f): ?>
                <tr>
                    <td><?= (int)$f['sort_order'] ?></td>
                    <td><?= htmlspecialchars($f['field_label_en']) ?></td>
                    <td><span class="badge badge-gray"><?= $f['field_type'] ?></span></td>
                    <td><?= $f['is_required'] ? '✅' : '—' ?></td>
                    <td>
                        <a href="form_api.php?action=delete_field&field_id=<?= (int)$f['id'] ?>&form_id=<?= (int)$form['id'] ?>&csrf_token=<?= urlencode(Csrf::generate()) ?>" 
                           class="btn btn-sm btn-danger" onclick="return confirm('Remove field?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <form method="POST" action="form_api.php" class="form-grid" style="border-top:1px solid #eee;padding-top:20px">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="add_field">
            <input type="hidden" name="form_id" value="<?= (int)$form['id'] ?>">
            
            <div class="form-group">
                <label>Field Name *</label>
                <input type="text" name="field_name" required placeholder="Full Name">
            </div>
            <div class="form-group">
                <label>Type *</label>
                <select name="field_type">
                    <option value="text">Text</option>
                    <option value="email">Email</option>
                    <option value="tel">Phone</option>
                    <option value="textarea">Textarea</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                    <option value="select">Select</option>
                    <option value="radio">Radio</option>
                    <option value="checkbox">Checkbox</option>
                    <option value="file">File</option>
                </select>
            </div>
            <div class="form-group">
                <label>Label FR</label>
                <input type="text" name="field_label_fr" placeholder="Nom complet">
            </div>
            <div class="form-group">
                <label>Order</label>
                <input type="number" name="sort_order" value="<?= count($fields) ?>" min="0">
            </div>
            <div class="form-group full-width">
                <label>Options (for select/radio/checkbox, one per line)</label>
                <textarea name="options" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
            </div>
            <div class="form-group full-width" style="display:flex;gap:20px">
                <label class="toggle">
                    <input type="checkbox" name="is_required" value="1">
                    <span>Required</span>
                </label>
            </div>
            
            <div class="form-actions full-width">
                <button type="submit" class="btn btn-primary btn-sm">+ Add Field</button>
            </div>
        </form>
    </div>
    
    <!-- Preview -->
    <div class="card" style="background:#f8f9fa">
        <h3 style="margin-bottom:16px">👁️ Preview</h3>
        <div style="border:1px solid #ddd;border-radius:8px;padding:24px;background:#fff">
            <h4 style="margin-bottom:16px"><?= htmlspecialchars($form['title']) ?></h4>
            <?php if (!empty($fields)): ?>
                <?php foreach ($fields as $f): ?>
                <div style="margin-bottom:16px">
                    <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px">
                        <?= htmlspecialchars($f['field_label_en']) ?>
                        <?= $f['is_required'] ? '<span style="color:#e74c3c">*</span>' : '' ?>
                    </label>
                    <?php if ($f['field_type'] === 'textarea'): ?>
                        <textarea style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px" disabled placeholder="..."></textarea>
                    <?php elseif (in_array($f['field_type'], ['select','radio'])): ?>
                        <select style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px" disabled>
                            <option>Select...</option>
                        </select>
                    <?php else: ?>
                        <input type="text" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px" disabled placeholder="<?= htmlspecialchars($f['placeholder_en'] ?? '') ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <button type="button" class="btn btn-primary" disabled>Submit</button>
            <?php else: ?>
                <p style="color:#95a5a6">No fields added yet.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>