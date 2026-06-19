<?php
declare(strict_types=1);

class Audit {
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function log(
        ?int $adminId,
        string $action,
        ?string $targetType,
        ?int $targetId,
        mixed $oldValues,
        mixed $newValues,
        ?string $ip,
        ?string $userAgent
    ): int {
        try {
            $oldJson = $this->toJson($oldValues);
            $newJson = $this->toJson($newValues);
            
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs 
                (admin_id, action, target_type, target_id, old_values, new_values, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $adminId,
                $action,
                $targetType,
                $targetId,
                $oldJson,
                $newJson,
                $ip,
                $userAgent
            ]);
            
            return (int)$this->db->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("[AUDIT ERROR] " . $e->getMessage() . " | Action: $action");
            return 0;
        }
    }
    
    private function toJson(mixed $value): ?string {
        if ($value === null) return null;
        if (is_string($value)) {
            // Vérifier si c'est déjà du JSON valide
            json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE) return $value;
            return json_encode(['raw' => $value]);
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}