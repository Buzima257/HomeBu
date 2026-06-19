<?php
declare(strict_types=1);

class StaticContent {
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function findByKey(string $key): ?array {
        $stmt = $this->db->prepare("SELECT * FROM static_content WHERE content_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetch() ?: null;
    }
    
    public function update(string $key, array $data, ?int $updatedBy): bool {
        $fields = [];
        $values = [];
        
        foreach (['value_en','value_fr','media_path','media_type'] as $k) {
            if (array_key_exists($k, $data)) {
                $fields[] = "$k = ?";
                $values[] = $data[$k];
            }
        }
        if (empty($fields)) return false;
        
        $fields[] = "updated_by = ?";
        $fields[] = "updated_at = NOW()";
        $values[] = $updatedBy;
        $values[] = $key;
        
        $stmt = $this->db->prepare("UPDATE static_content SET " . implode(', ', $fields) . " WHERE content_key = ?");
        return $stmt->execute($values);
    }
    
    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM static_content ORDER BY id");
        return $stmt->fetchAll();
    }
}