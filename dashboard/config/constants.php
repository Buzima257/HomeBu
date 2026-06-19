<?php
declare(strict_types=1);

// ─── Database ───
define('DB_HOST', 'localhost');
define('DB_NAME', 'homecare');
define('DB_USER', 'root');      // ← ADAPTEZ
define('DB_PASS', '');          // ← ADAPTEZ

// ─── Paths ───
define('BASE_URL',     '/homecare/');          // ← ADAPTEZ selon votre dossier htdocs
define('ROOT_PATH',    dirname(__DIR__, 2));   // Remonte 2 niveaux depuis config/
define('DASHBOARD_PATH', ROOT_PATH . '/dashboard/');
define('UPLOAD_PATH',  ROOT_PATH . '/uploads/');
define('UPLOAD_URL',   BASE_URL . 'uploads/');

// ─── Security ───
define('SESSION_TIMEOUT',        1800);  // 30 minutes
define('MAX_LOGIN_ATTEMPTS',     5);
define('LOCKOUT_DURATION',       900);   // 15 minutes
define('BCRYPT_COST',            12);

// ─── Upload Limits ───
define('IMAGE_MAX_SIZE',      5 * 1024 * 1024);   // 5 MB
define('IMAGE_MIN_SIZE',      100 * 1024);         // 100 KB
define('VIDEO_MAX_SIZE',      50 * 1024 * 1024);   // 50 MB
define('SHORT_VIDEO_MAX_SIZE',10 * 1024 * 1024);   // 10 MB
define('SHORT_VIDEO_MAX_DUR', 60);                  // 60 sec