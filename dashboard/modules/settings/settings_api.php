<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/SettingController.php';

$auth = new Auth($db);
$auth->requireRole('super_admin');

if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
    header('Location: settings_form.php?error=Invalid security token');
    exit;
}

$ctrl = new SettingController($db);
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$actorId = $auth->id();

$action = $_POST['action'] ?? '';

if ($action === 'update_all') {
    $result = $ctrl->updateBatch($_POST, $actorId, $ip, $ua);
    if ($result['success']) {
        header('Location: settings_form.php?success=' . ($result['changed'] ?? 'all'));
    } else {
        header('Location: settings_form.php?error=' . urlencode($result['error']));
    }
    exit;
}

header('Location: settings_form.php?error=Unknown action');
exit;