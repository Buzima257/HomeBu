<?php
declare(strict_types=1);

class Announcement {
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function findAll(string $status = '', int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        $where = "1=1";
        $params = [];
        
        if ($status && in_array($status, ['active','expired','draft'])) {
            $where .= " AND status = ?";
            $params[] = $status;
        }
        
        $stmt = $this->db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM announcements WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([...$params, $perPage, $offset]);
        $rows = $stmt->fetchAll();
        $total = (int)$this->db->query("SELECT FOUND_ROWS()")->fetchColumn();
        return ['rows' => $rows, 'total' => $total, 'pages' => (int)ceil($total / $perPage)];
    }
    
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM announcements WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO announcements 
            (slug, lang, title, description, start_date, end_date, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['slug'], $data['lang'] ?? 'en', $data['title'], $data['description'] ?? null,
            $data['start_date'] ?? null, $data['end_date'] ?? null,
            $data['status'] ?? 'draft', $data['created_by'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }
    
    public function update(int $id, array $data): bool {
        $fields = []; $values = [];
        $allowed = ['slug','title','description','start_date','end_date','status'];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $fields[] = "$k = ?";
                $values[] = $data[$k];
            }
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $stmt = $this->db->prepare("UPDATE announcements SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }
    
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM announcements WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function expireOld(): int {
        $stmt = $this->db->prepare("UPDATE announcements SET status = 'expired' WHERE end_date < CURDATE() AND status != 'expired'");
        $stmt->execute();
        return $stmt->rowCount();
    }
}