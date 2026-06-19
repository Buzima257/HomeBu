<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/SocialLinkController.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

if (!Csrf::validate($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
    header('Location: social_list.php?error=Invalid token'); exit;
}

$ctrl = new SocialLinkController($db);
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$actorId = $auth->id();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $r = $ctrl->store($_POST, $actorId, $ip, $ua);
        header('Location: social_list.php?' . ($r['success'] ? 'success=Created' : 'error=' . urlencode($r['error']))); exit;
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $r = $ctrl->update($id, $_POST, $actorId, $ip, $ua);
        header('Location: social_list.php?' . ($r['success'] ? 'success=Updated' : 'error=' . urlencode($r['error']))); exit;
    case 'delete':
        $id = (int)($_GET['id'] ?? 0);
        $r = $ctrl->delete($id, $actorId, $ip, $ua);
        header('Location: social_list.php?' . ($r['success'] ? 'success=Deleted' : 'error=' . urlencode($r['error']))); exit;
    default:
        header('Location: social_list.php?error=Unknown'); exit;
}