<?php
declare(strict_types=1);

class ImpactStat {
    private PDO $db;
    public function __construct(PDO $db) { $this->db = $db; }
    
    public function findAll(): array {
        $stmt = $this->db->query("SELECT * FROM impact_stats ORDER BY display_order, id");
        return $stmt->fetchAll();
    }
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM impact_stats WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    public function create(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO impact_stats (label_en, label_fr, value, suffix_en, suffix_fr, icon, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['label_en'], $data['label_fr'], $data['value'] ?? 0, $data['suffix_en'] ?? null, $data['suffix_fr'] ?? null, $data['icon'] ?? null, $data['display_order'] ?? 0, $data['is_active'] ?? 1, $data['updated_by'] ?? null]);
        return (int)$this->db->lastInsertId();
    }
    public function update(int $id, array $data): bool {
        $fields = []; $values = [];
        foreach (['label_en','label_fr','value','suffix_en','suffix_fr','icon','display_order','is_active','updated_by'] as $k) {
            if (array_key_exists($k, $data)) { $fields[] = "$k = ?"; $values[] = $data[$k]; }
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $stmt = $this->db->prepare("UPDATE impact_stats SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM impact_stats WHERE id = ?");
        return $stmt->execute([$id]);
    }
}