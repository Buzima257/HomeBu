<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/AnnouncementController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

if (!Csrf::validate($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
    header('Location: announcement_list.php?error=Invalid token');
    exit;
}

$ctrl = new AnnouncementController($db);
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$actorId = $auth->id();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $result = $ctrl->store($_POST, $actorId, $ip, $ua);
        header('Location: announcement_list.php?' . ($result['success'] ? 'success=Created' : 'error=' . urlencode($result['error'])));
        exit;
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $result = $ctrl->update($id, $_POST, $actorId, $ip, $ua);
        header('Location: announcement_list.php?' . ($result['success'] ? 'success=Updated' : 'error=' . urlencode($result['error'])));
        exit;
    case 'delete':
        $id = (int)($_GET['id'] ?? 0);
        $result = $ctrl->delete($id, $actorId, $ip, $ua);
        header('Location: announcement_list.php?' . ($result['success'] ? 'success=Deleted' : 'error=' . urlencode($result['error'])));
        exit;
    default:
        header('Location: announcement_list.php?error=Unknown');
        exit;
}
