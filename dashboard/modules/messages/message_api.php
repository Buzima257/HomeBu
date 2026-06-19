<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/MessageController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin','superviseur']);

if (!Csrf::validate($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
    header('Location: message_list.php?error=Invalid token'); exit;
}

$ctrl = new MessageController($db);
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$actorId = $auth->id();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'assign':
        $id = (int)($_POST['id'] ?? 0);
        $adminId = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $r = $ctrl->assign($id, $adminId, $actorId, $ip, $ua);
        header('Location: message_detail.php?id=' . $id . ($r['success'] ? '&success=Assigned' : '&error=' . urlencode($r['error']))); exit;
    case 'notes':
        $id = (int)($_POST['id'] ?? 0);
        $r = $ctrl->saveNotes($id, $_POST['notes'] ?? '', $actorId, $ip, $ua);
        header('Location: message_detail.php?id=' . $id . ($r['success'] ? '&success=Notes saved' : '&error=' . urlencode($r['error']))); exit;
    case 'archive':
        $id = (int)($_GET['id'] ?? 0);
        $r = $ctrl->archive($id, $actorId, $ip, $ua);
        header('Location: message_list.php?' . ($r['success'] ? 'success=Archived' : 'error=' . urlencode($r['error']))); exit;
    case 'delete':
        $id = (int)($_GET['id'] ?? 0);
        $r = $ctrl->delete($id, $actorId, $ip, $ua);
        header('Location: message_list.php?' . ($r['success'] ? 'success=Deleted' : 'error=' . urlencode($r['error']))); exit;
    default:
        header('Location: message_list.php?error=Unknown'); exit;
}