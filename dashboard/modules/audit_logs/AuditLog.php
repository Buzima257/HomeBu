<?php
declare(strict_types=1);

class AuditLog {
    private PDO $db;
    public function __construct(PDO $db) { $this->db = $db; }
    
    public function findAll(array $filters = [], int $page = 1, int $perPage = 50): array {
        $offset = ($page - 1) * $perPage;
        $where = ["1=1"];
        $params = [];
        
        if (!empty($filters['admin_id'])) { $where[] = "admin_id = ?"; $params[] = (int)$filters['admin_id']; }
        if (!empty($filters['action'])) { $where[] = "action = ?"; $params[] = $filters['action']; }
        if (!empty($filters['target_type'])) { $where[] = "target_type = ?"; $params[] = $filters['target_type']; }
        if (!empty($filters['date_from'])) { $where[] = "created_at >= ?"; $params[] = $filters['date_from'] . ' 00:00:00'; }
        if (!empty($filters['date_to'])) { $where[] = "created_at <= ?"; $params[] = $filters['date_to'] . ' 23:59:59'; }
        if (!empty($filters['ip'])) { $where[] = "ip_address LIKE ?"; $params[] = '%' . $filters['ip'] . '%'; }
        
        $sql = "SELECT SQL_CALC_FOUND_ROWS al.*, a.username 
                FROM audit_logs al 
                LEFT JOIN admins a ON al.admin_id = a.id 
                WHERE " . implode(" AND ", $where) . " 
                ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([...$params, $perPage, $offset]);
        $rows = $stmt->fetchAll();
        $total = (int)$this->db->query("SELECT FOUND_ROWS()")->fetchColumn();
        return ['rows' => $rows, 'total' => $total, 'pages' => (int)ceil($total / $perPage)];
    }
    
    public function getDistinctActions(): array {
        $stmt = $this->db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    public function getDistinctTargets(): array {
        $stmt = $this->db->query("SELECT DISTINCT target_type FROM audit_logs WHERE target_type IS NOT NULL ORDER BY target_type");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}