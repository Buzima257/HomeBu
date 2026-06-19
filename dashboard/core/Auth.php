<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Audit.php';

class Auth {
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function login(string $username, string $password, string $ip, string $userAgent): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM admins WHERE username = ? AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Session data
            $_SESSION['admin_id']       = $user['id'];
            $_SESSION['admin_role']     = $user['role'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_ip']       = $ip;
            $_SESSION['last_activity']  = time();
            $_SESSION['last_regeneration'] = time();
            
            // Update last_login
            $upd = $this->db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $upd->execute([$user['id']]);
            
            // Audit success
            $audit = new Audit($this->db);
            $audit->log(
                (int)$user['id'], 'login_success', 'admin', (int)$user['id'],
                null, null, $ip, $userAgent
            );
            
            return ['success' => true, 'user' => $user];
        }
        
        // Audit failed
        $audit = new Audit($this->db);
        $audit->log(
            null, 'login_failed', 'admin', null,
            null,
            json_encode(['username' => $username, 'ip' => $ip]),
            $ip, $userAgent
        );
        
        return ['success' => false, 'error' => 'Invalid credentials'];
    }
    
    public function check(): bool {
        return isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0;
    }
    
    public function role(): ?string {
        return $_SESSION['admin_role'] ?? null;
    }
    
    public function hasRole(array|string $roles): bool {
        if (!is_array($roles)) $roles = [$roles];
        return in_array($this->role(), $roles, true);
    }
    
    public function logout(): void {
        $audit = new Audit($this->db);
        $audit->log(
            $_SESSION['admin_id'] ?? null,
            'logout', 'admin',
            $_SESSION['admin_id'] ?? null,
            null, null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
        
        $_SESSION = [];
        session_unset();
        session_destroy();
    }

        public function requireRole(array|string $roles): void {
        if (!$this->check()) {
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }
        if (!$this->hasRole($roles)) {
            http_response_code(403);
            require_once DASHBOARD_PATH . 'includes/403.php';
            exit;
        }
    }

    public function id(): ?int {
    return isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;

}

public function username(): ?string {
    return $_SESSION['admin_username'] ?? null;
}

public function ip(): ?string {
    return $_SESSION['admin_ip'] ?? null;
}
}