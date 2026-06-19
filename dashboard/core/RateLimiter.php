<?php
declare(strict_types=1);

class RateLimiter {
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function check(string $ip, string $action = 'login', int $maxAttempts = 5, int $windowSeconds = 900): array {
        // Clean old entries
        $clean = $this->db->prepare(
            "DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)"
        );
        $clean->execute([$windowSeconds]);
        
        // Count recent attempts
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM rate_limits WHERE ip = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
        );
        $stmt->execute([$ip, $action, $windowSeconds]);
        $count = (int)$stmt->fetchColumn();
        
        if ($count >= $maxAttempts) {
            return ['allowed' => false, 'remaining' => 0, 'retry_after' => $windowSeconds];
        }
        
        // Log this attempt
        $ins = $this->db->prepare("INSERT INTO rate_limits (ip, action) VALUES (?, ?)");
        $ins->execute([$ip, $action]);
        
        return ['allowed' => true, 'remaining' => $maxAttempts - $count - 1];
    }
}