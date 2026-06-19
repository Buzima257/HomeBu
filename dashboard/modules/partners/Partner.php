<?php
declare(strict_types=1);

class Partner {
    private PDO $db;
    public function __construct(PDO $db) { $this->db = $db; }
    
    public function findAll(): array {
        $stmt = $this->db->query("SELECT * FROM partners ORDER BY display_order, name");
        return $stmt->fetchAll();
    }
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM partners WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    public function create(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO partners (name, logo, link, partner_type, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['name'], $data['logo'] ?? null, $data['link'] ?? null, $data['partner_type'] ?? 'technical', $data['display_order'] ?? 0, $data['is_active'] ?? 1]);
        return (int)$this->db->lastInsertId();
    }
    public function update(int $id, array $data): bool {
        $fields = []; $values = [];
        foreach (['name','logo','link','partner_type','display_order','is_active'] as $k) {
            if (array_key_exists($k, $data)) { $fields[] = "$k = ?"; $values[] = $data[$k]; }
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $stmt = $this->db->prepare("UPDATE partners SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM partners WHERE id = ?");
        return $stmt->execute([$id]);
    }
}