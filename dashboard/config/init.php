<?php
declare(strict_types=1);

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';

// ─── Session Security ───
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly',    '1');
    ini_set('session.cookie_secure',      '1');
    ini_set('session.cookie_samesite',     'Strict');
    ini_set('session.use_strict_mode',    '1');
    ini_set('session.gc_maxlifetime',     (string)SESSION_TIMEOUT);
    
    session_start();
}

// ─── Timezone ───
date_default_timezone_set('Africa/Bujumbura'); // ← ADAPTEZ

// ─── Regenerate ID every 15 min ───
if (isset($_SESSION['last_regeneration'])) {
    if (time() - $_SESSION['last_regeneration'] > 900) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
} else {
    $_SESSION['last_regeneration'] = time();
}

// ─── Auto-logout on inactivity ───
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
$_SESSION['last_activity'] = time();