<?php
declare(strict_types=1);

class Admin {
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function findAll(): array {
        $stmt = $this->db->query("
            SELECT id, username, email, full_name, role, description, 
                   avatar, phone, is_active, last_login, created_at
            FROM admins 
            ORDER BY FIELD(role, 'super_admin', 'admin', 'superviseur'), created_at DESC
        ");
        return $stmt->fetchAll();
    }
    
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
    
    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }
    
    public function countSuperAdmins(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM admins WHERE role = 'super_admin'");
        return (int)$stmt->fetchColumn();
    }
    
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO admins 
            (username, email, password_hash, full_name, role, description, avatar, phone, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['full_name'] ?? null,
            $data['role'],
            $data['description'] ?? null,
            $data['avatar'] ?? null,
            $data['phone'] ?? null,
            $data['is_active'] ?? 1,
            $data['created_by'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }
    
    public function update(int $id, array $data): bool {
        $fields = [];
        $values = [];
        
        foreach ($data as $k => $v) {
            if ($v !== null || in_array($k, ['description','avatar','phone'])) {
                $fields[] = "$k = ?";
                $values[] = $v;
            }
        }
        
        if (empty($fields)) return false;
        
        $values[] = $id;
        $stmt = $this->db->prepare("UPDATE admins SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }
    
    public function updatePassword(int $id, string $hash): bool {
        $stmt = $this->db->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$hash, $id]);
    }
    
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM admins WHERE id = ?");
        return $stmt->execute([$id]);
    }
}