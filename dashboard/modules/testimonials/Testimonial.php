<?php
declare(strict_types=1);

class Testimonial {
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function findAll(int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM testimonials ORDER BY is_featured DESC, display_order, id DESC LIMIT ? OFFSET ?");
        $stmt->execute([$perPage, $offset]);
        $rows = $stmt->fetchAll();
        $total = (int)$this->db->query("SELECT FOUND_ROWS()")->fetchColumn();
        return ['rows' => $rows, 'total' => $total, 'pages' => (int)ceil($total / $perPage)];
    }
    
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM testimonials WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO testimonials 
            (lang, name, role, text, photo, video_type, video_embed_url, video_file, is_featured, display_order, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['lang'] ?? 'en', $data['name'], $data['role'] ?? null, $data['text'],
            $data['photo'] ?? null, $data['video_type'] ?? 'none', $data['video_embed_url'] ?? null,
            $data['video_file'] ?? null, $data['is_featured'] ?? 0, $data['display_order'] ?? 0,
            $data['created_by'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }
    
    public function update(int $id, array $data): bool {
        $fields = []; $values = [];
        $allowed = ['lang','name','role','text','photo','video_type','video_embed_url','video_file','is_featured','display_order'];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $fields[] = "$k = ?";
                $values[] = $data[$k];
            }
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $stmt = $this->db->prepare("UPDATE testimonials SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }
    
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM testimonials WHERE id = ?");
        return $stmt->execute([$id]);
    }
}