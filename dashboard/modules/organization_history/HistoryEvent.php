<?php
declare(strict_types=1);

class HistoryEvent {
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function findAll(int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM organization_history WHERE is_active = 1 ORDER BY year DESC, month DESC, day DESC, display_order LIMIT ? OFFSET ?");
        $stmt->execute([$perPage, $offset]);
        $rows = $stmt->fetchAll();
        $total = (int)$this->db->query("SELECT FOUND_ROWS()")->fetchColumn();
        return ['rows' => $rows, 'total' => $total, 'pages' => (int)ceil($total / $perPage)];
    }
    
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM organization_history WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO organization_history 
            (slug, lang, year, month, day, title, description, image, icon, is_major, display_order, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['slug'], $data['lang'] ?? 'en', $data['year'], $data['month'] ?? null,
            $data['day'] ?? null, $data['title'], $data['description'] ?? null,
            $data['image'] ?? null, $data['icon'] ?? 'star', $data['is_major'] ?? 0,
            $data['display_order'] ?? 0, $data['is_active'] ?? 1, $data['created_by'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }
    
    public function update(int $id, array $data): bool {
        $fields = []; $values = [];
        foreach (['year','month','day','title','description','image','icon','is_major','display_order','is_active'] as $k) {
            if (array_key_exists($k, $data)) {
                $fields[] = "$k = ?";
                $values[] = $data[$k];
            }
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $stmt = $this->db->prepare("UPDATE organization_history SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }
    
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM organization_history WHERE id = ?");
        return $stmt->execute([$id]);
    }
}