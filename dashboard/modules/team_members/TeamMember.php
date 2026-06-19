<?php
declare(strict_types=1);

class TeamMember {
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function findAll(string $filter = '', int $page = 1, int $perPage = 12): array {
        $offset = ($page - 1) * $perPage;
        $where = "1=1";
        $params = [];
        
        if ($filter === 'leadership') {
            $where .= " AND is_leadership = 1";
        } elseif ($filter) {
            $where .= " AND department = ?";
            $params[] = $filter;
        }
        
        $stmt = $this->db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM team_members WHERE $where AND is_active = 1 ORDER BY is_leadership DESC, display_order, id DESC LIMIT ? OFFSET ?");
        $stmt->execute([...$params, $perPage, $offset]);
        $rows = $stmt->fetchAll();
        $total = (int)$this->db->query("SELECT FOUND_ROWS()")->fetchColumn();
        
        return ['rows' => $rows, 'total' => $total, 'pages' => (int)ceil($total / $perPage)];
    }
    
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM team_members WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO team_members 
            (slug, lang, full_name, role, title, biography, photo, email, phone, social_links,
             display_order, is_active, is_leadership, is_featured, department,
             show_email_public, show_phone_public, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['slug'], $data['lang'] ?? 'en', $data['full_name'], $data['role'],
            $data['title'] ?? null, $data['biography'] ?? null, $data['photo'] ?? null,
            $data['email'] ?? null, $data['phone'] ?? null,
            isset($data['social_links']) ? json_encode($data['social_links']) : null,
            $data['display_order'] ?? 0, $data['is_active'] ?? 1,
            $data['is_leadership'] ?? 0, $data['is_featured'] ?? 0,
            $data['department'] ?? null, $data['show_email_public'] ?? 0,
            $data['show_phone_public'] ?? 0, $data['created_by'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }
    
    public function update(int $id, array $data): bool {
        $fields = []; $values = [];
        $allowed = ['slug','full_name','role','title','biography','photo','email','phone',
                    'social_links','display_order','is_active','is_leadership','is_featured',
                    'department','show_email_public','show_phone_public'];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $fields[] = "$k = ?";
                $values[] = ($k === 'social_links') ? json_encode($data[$k]) : $data[$k];
            }
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $stmt = $this->db->prepare("UPDATE team_members SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }
    
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM team_members WHERE id = ?");
        return $stmt->execute([$id]);
    }
}