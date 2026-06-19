<?php
declare(strict_types=1);

class Session {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly',    '1');
            ini_set('session.cookie_secure',      '1');
            ini_set('session.cookie_samesite',     'Strict');
            ini_set('session.use_strict_mode',    '1');
            session_start();
        }
    }
    
    public static function regenerate(): void {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    public static function destroy(): void {
        $_SESSION = [];
        session_unset();
        session_destroy();
    }
}