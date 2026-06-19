<?php
declare(strict_types=1);

require_once __DIR__ . '/dashboard/config/init.php';
require_once __DIR__ . '/dashboard/core/Auth.php';

$auth = new Auth($db);
$auth->logout();

header('Location: ' . BASE_URL . 'login.php');
exit;