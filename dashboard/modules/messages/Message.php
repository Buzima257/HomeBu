<?php
declare(strict_types=1);

class Message {
    private PDO $db;
    public function __construct(PDO $db) { $this->db = $db; }
    
    public function findAll(string $status = '', string $type = '', int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        $where = ["1=1"];
        $params = [];
        
        if ($status && in_array($status, ['new','read','replied','archived'])) {
            $where[] = "status = ?";
            $params[] = $status;
        }
        if ($type && in_array($type, ['contact','donation_inquiry'])) {
            $where[] = "type = ?";
            $params[] = $type;
        }
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS m.*, a.username as assigned_name 
                FROM messages m 
                LEFT JOIN admins a ON m.assigned_to = a.id 
                WHERE " . implode(" AND ", $where) . " 
                ORDER BY m.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([...$params, $perPage, $offset]);
        $rows = $stmt->fetchAll();
        $total = (int)$this->db->query("SELECT FOUND_ROWS()")->fetchColumn();
        return ['rows' => $rows, 'total' => $total, 'pages' => (int)ceil($total / $perPage)];
    }
    
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT m.*, a.username as assigned_name FROM messages m LEFT JOIN admins a ON m.assigned_to = a.id WHERE m.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    public function countByStatus(string $status): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM messages WHERE status = ?");
        $stmt->execute([$status]);
        return (int)$stmt->fetchColumn();
    }
    
    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("UPDATE messages SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    
    public function assign(int $id, ?int $adminId): bool {
        $stmt = $this->db->prepare("UPDATE messages SET assigned_to = ? WHERE id = ?");
        return $stmt->execute([$adminId, $id]);
    }
    
    public function updateNotes(int $id, string $notes): bool {
        $stmt = $this->db->prepare("UPDATE messages SET internal_notes = ? WHERE id = ?");
        return $stmt->execute([$notes, $id]);
    }
    
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM messages WHERE id = ?");
        return $stmt->execute([$id]);
    }
}