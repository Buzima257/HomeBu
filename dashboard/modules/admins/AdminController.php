<?php
declare(strict_types=1);

require_once __DIR__ . '/Admin.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Audit.php';

class AdminController {
    private PDO $db;
    private Admin $model;
    private Audit $audit;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->model = new Admin($db);
        $this->audit = new Audit($db);
    }
    
    public function list(): array {
        return $this->model->findAll();
    }
    
    public function get(int $id): ?array {
        return $this->model->findById($id);
    }
    
    public function store(array $data, int $actorId, string $ip, string $ua): array {
        // Validation
        $username = trim($data['username'] ?? '');
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role     = $data['role'] ?? '';
        
        if ($username === '' || strlen($username) > 50 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['success' => false, 'error' => 'Username: 1-50 chars, alphanumeric + underscore only'];
        }
        if (!Validator::email($email)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }
        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'Password minimum 8 characters'];
        }
        if (!in_array($role, ['super_admin','admin','superviseur'], true)) {
            return ['success' => false, 'error' => 'Invalid role'];
        }
        
        // Hard limit super_admin
        if ($role === 'super_admin' && $this->model->countSuperAdmins() >= 3) {
            return ['success' => false, 'error' => 'Maximum 3 super_admin accounts reached'];
        }
        
        // Uniqueness
        if ($this->model->findByUsername($username)) {
            return ['success' => false, 'error' => 'Username already taken'];
        }
        
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        
        $id = $this->model->create([
            'username'    => $username,
            'email'       => $email,
            'password_hash' => $hash,
            'full_name'   => trim($data['full_name'] ?? ''),
            'role'        => $role,
            'description' => $data['description'] ?? null,
            'phone'       => $data['phone'] ?? null,
            'is_active'   => (int)($data['is_active'] ?? 1),
            'created_by'  => $actorId
        ]);
        
        $this->audit->log($actorId, 'admin_created', 'admin', $id, null, [
            'username' => $username,
            'email'    => $email,
            'role'     => $role,
            'created_by' => $actorId
        ], $ip, $ua);
        
        return ['success' => true, 'id' => $id];
    }
    
    public function update(int $id, array $data, int $actorId, string $ip, string $ua): array {
        $admin = $this->model->findById($id);
        if (!$admin) return ['success' => false, 'error' => 'Admin not found'];
        
        // Prevent role escalation to super_admin if limit reached
        if (($data['role'] ?? '') === 'super_admin' && $admin['role'] !== 'super_admin') {
            if ($this->model->countSuperAdmins() >= 3) {
                return ['success' => false, 'error' => 'Maximum 3 super_admin accounts reached'];
            }
        }
        
        $old = [
            'username' => $admin['username'],
            'email'    => $admin['email'],
            'role'     => $admin['role'],
            'is_active'=> $admin['is_active'],
            'full_name'=> $admin['full_name']
        ];
        
        $update = [];
        if (isset($data['email']))    $update['email']    = trim($data['email']);
        if (isset($data['full_name']))$update['full_name']= trim($data['full_name']);
        if (isset($data['role']))     $update['role']     = $data['role'];
        if (isset($data['phone']))    $update['phone']    = $data['phone'] ?: null;
        if (isset($data['description'])) $update['description'] = $data['description'] ?: null;
        if (isset($data['is_active'])) $update['is_active'] = (int)$data['is_active'];
        
        $this->model->update($id, $update);
        
        $newAdmin = $this->model->findById($id);
        $new = [
            'username' => $newAdmin['username'],
            'email'    => $newAdmin['email'],
            'role'     => $newAdmin['role'],
            'is_active'=> $newAdmin['is_active'],
            'full_name'=> $newAdmin['full_name']
        ];
        
        $this->audit->log($actorId, 'admin_updated', 'admin', $id, $old, $new, $ip, $ua);
        
        return ['success' => true];
    }
    
    public function changePassword(int $id, string $newPassword, int $actorId, string $ip, string $ua): array {
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'error' => 'Password minimum 8 characters'];
        }
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $this->model->updatePassword($id, $hash);
        
        $this->audit->log($actorId, 'password_changed', 'admin', $id, null, [
            'changed_by' => $actorId,
            'ip' => $ip
        ], $ip, $ua);
        
        return ['success' => true];
    }
    
    public function delete(int $id, int $actorId, string $ip, string $ua): array {
        $admin = $this->model->findById($id);
        if (!$admin) return ['success' => false, 'error' => 'Admin not found'];
        
        // Prevent self-deletion
        if ($id === $actorId) {
            return ['success' => false, 'error' => 'You cannot delete your own account'];
        }
        
        // Prevent deleting last super_admin
        if ($admin['role'] === 'super_admin' && $this->model->countSuperAdmins() <= 1) {
            return ['success' => false, 'error' => 'Cannot delete the last super_admin'];
        }
        
        $old = ['username' => $admin['username'], 'role' => $admin['role']];
        
        $this->model->delete($id);
        
        $this->audit->log($actorId, 'admin_deleted', 'admin', $id, $old, null, $ip, $ua);
        
        return ['success' => true];
    }
}