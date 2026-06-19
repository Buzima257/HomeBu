<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/ArticleController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

if (!Csrf::validate($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
    header('Location: article_list.php?error=Invalid security token');
    exit;
}

$ctrl = new ArticleController($db);
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$actorId = $auth->id();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $result = $ctrl->store($_POST, $actorId, $ip, $ua);
        if ($result['success']) {
            // Handle gallery uploads if any
            if (!empty($_FILES['gallery_images']) || !empty($_POST['gallery_embeds'])) {
                $ctrl->addGallery($result['id'], $_FILES['gallery_images'] ?? [], $_POST['gallery_embeds'] ?? [], $actorId, $ip);
            }
            header('Location: article_list.php?success=Article created');
        } else {
            header('Location: article_form.php?error=' . urlencode($result['error']));
        }
        exit;
        
        case 'update':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            header('Location: article_list.php?error=Invalid ID');
            exit;
        }
        $result = $ctrl->update($id, $_POST, $actorId, $ip, $ua);
        if ($result['success']) {
            if (!empty($_FILES['gallery_images']) || !empty($_POST['gallery_embeds'])) {
                $ctrl->addGallery($id, $_FILES['gallery_images'] ?? [], $_POST['gallery_embeds'] ?? [], $actorId, $ip);
            }
            header('Location: article_list.php?success=Article updated');
        } else {
            header('Location: article_form.php?id=' . $id . '&error=' . urlencode($result['error']));
        }
        exit;
        
    case 'delete':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: article_list.php?error=Invalid ID');
            exit;
        }
        $result = $ctrl->delete($id, $actorId, $ip, $ua);
        if ($result['success']) {
            header('Location: article_list.php?success=Article deleted');
        } else {
            header('Location: article_list.php?error=' . urlencode($result['error']));
        }
        exit;
        
    case 'delete_gallery_item':
        $itemId = (int)($_GET['item_id'] ?? 0);
        $articleId = (int)($_GET['article_id'] ?? 0);
        if ($itemId) {
            $ctrl->deleteGalleryItem($itemId);
        }
        header('Location: article_form.php?id=' . $articleId . '&success=Media removed');
        exit;
        
    default:
        header('Location: article_list.php?error=Unknown action');
        exit;
}