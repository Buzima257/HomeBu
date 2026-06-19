<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/StaticSectionController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

$toggleStmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'allow_static_section_edit'");
$toggleStmt->execute();
$canEdit = ($toggleStmt->fetchColumn() ?? '1') === '1';
if (!$canEdit && !$auth->hasRole('super_admin')) {
    header('Location: section_list.php?error=Section editing is disabled');
    exit;
}

if (!Csrf::validate($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
    header('Location: section_list.php?error=Invalid token');
    exit;
}

$ctrl = new StaticSectionController($db);
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$actorId = $auth->id();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            header('Location: section_list.php?error=Invalid ID');
            exit;
        }
        $result = $ctrl->update($id, $_POST, $actorId, $ip, $ua);
        if ($result['success']) {
            header('Location: section_form.php?id=' . $id . '&success=Section saved');
        } else {
            header('Location: section_form.php?id=' . $id . '&error=' . urlencode($result['error']));
        }
        exit;
        
    case 'add_media':
        $sectionId = (int)($_POST['section_id'] ?? 0);
        if (!$sectionId) {
            header('Location: section_list.php?error=Invalid section');
            exit;
        }
        $result = $ctrl->handleMedia($sectionId, $_POST, $_FILES, $actorId, $ip);
        if ($result['success']) {
            header('Location: section_form.php?id=' . $sectionId . '&success=Media added');
        } else {
            header('Location: section_form.php?id=' . $sectionId . '&error=' . urlencode($result['error']));
        }
        exit;
        
    case 'delete_media':
        $mediaId = (int)($_GET['media_id'] ?? 0);
        $sectionId = (int)($_GET['section_id'] ?? 0);
        if ($mediaId) {
            $ctrl->deleteMedia($mediaId, $actorId, $ip, $ua);
        }
        header('Location: section_form.php?id=' . $sectionId . '&success=Media removed');
        exit;
        
    default:
        header('Location: section_list.php?error=Unknown action');
        exit;
}