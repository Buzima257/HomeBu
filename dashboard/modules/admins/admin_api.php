<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/AdminController.php';

$auth = new Auth($db);
$auth->requireRole('super_admin');

if (!Csrf::validate($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
    header('Location: admin_list.php?error=Invalid security token');
    exit;
}

$ctrl = new AdminController($db);
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$actorId = $auth->id();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $result = $ctrl->store($_POST, $actorId, $ip, $ua);
        if ($result['success']) {
            header('Location: admin_list.php?success=Admin created successfully');
        } else {
            header('Location: admin_form.php?error=' . urlencode($result['error']));
        }
        exit;
        
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            header('Location: admin_list.php?error=Invalid ID');
            exit;
        }
        $result = $ctrl->update($id, $_POST, $actorId, $ip, $ua);
        if ($result['success']) {
            // Change password if provided
            if (!empty($_POST['password'])) {
                $ctrl->changePassword($id, $_POST['password'], $actorId, $ip, $ua);
            }
            header('Location: admin_list.php?success=Admin updated successfully');
        } else {
            header('Location: admin_form.php?id=' . $id . '&error=' . urlencode($result['error']));
        }
        exit;
        
    case 'delete':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: admin_list.php?error=Invalid ID');
            exit;
        }
        $result = $ctrl->delete($id, $actorId, $ip, $ua);
        if ($result['success']) {
            header('Location: admin_list.php?success=Admin deleted');
        } else {
            header('Location: admin_list.php?error=' . urlencode($result['error']));
        }
        exit;
        
    default:
        header('Location: admin_list.php?error=Unknown action');
        exit;
}