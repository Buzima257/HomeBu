<?php
declare(strict_types=1);

require_once __DIR__ . '/TeamMember.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Audit.php';
require_once __DIR__ . '/../../core/Upload.php';

class TeamMemberController {
    private PDO $db;
    private TeamMember $model;
    private Audit $audit;
    private Upload $upload;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->model = new TeamMember($db);
        $this->audit = new Audit($db);
        $this->upload = new Upload($db);
    }
    
    public function list(string $filter = '', int $page = 1): array {
        return $this->model->findAll($filter, $page);
    }
    
    public function get(int $id): ?array {
        return $this->model->findById($id);
    }
    
    public function store(array $data, int $actorId, string $ip, string $ua): array {
        $fullName = trim($data['full_name'] ?? '');
        $role = trim($data['role'] ?? '');
        if ($fullName === '' || $role === '') {
            return ['success' => false, 'error' => 'Full name and role are required'];
        }
        
        $slug = Validator::slug($data['slug'] ?? $fullName);
        // ensure unique
        $base = $slug;
        $counter = 1;
        while ($this->db->prepare("SELECT id FROM team_members WHERE slug = ?")->execute([$slug]) && $this->db->prepare("SELECT id FROM team_members WHERE slug = ?")->fetch()) {
            $slug = $base . '-' . $counter++;
        }
        
        $photo = null;
        if (!empty($data['photo_file']['tmp_name'])) {
            $up = $this->upload->image($data['photo_file'], 'team', $actorId, $ip);
            if (!$up['success']) return $up;
            $photo = $up['path'];
        }
        
        $social = [];
        foreach (['linkedin','twitter','facebook'] as $platform) {
            if (!empty($data["social_$platform"])) {
                $social[$platform] = trim($data["social_$platform"]);
            }
        }
        
        $id = $this->model->create([
            'slug' => $slug, 'full_name' => $fullName, 'role' => $role,
            'title' => $data['title'] ?? null, 'biography' => $data['biography'] ?? null,
            'photo' => $photo, 'email' => $data['email'] ?? null, 'phone' => $data['phone'] ?? null,
            'social_links' => $social, 'display_order' => (int)($data['display_order'] ?? 0),
            'is_active' => (int)($data['is_active'] ?? 1),
            'is_leadership' => (int)($data['is_leadership'] ?? 0),
            'is_featured' => (int)($data['is_featured'] ?? 0),
            'department' => $data['department'] ?? null,
            'show_email_public' => (int)($data['show_email_public'] ?? 0),
            'show_phone_public' => (int)($data['show_phone_public'] ?? 0),
            'created_by' => $actorId
        ]);
        
        $this->audit->log($actorId, 'team_member_created', 'team_member', $id, null, ['slug' => $slug, 'name' => $fullName], $ip, $ua);
        return ['success' => true, 'id' => $id];
    }
    
    public function update(int $id, array $data, int $actorId, string $ip, string $ua): array {
        $member = $this->model->findById($id);
        if (!$member) return ['success' => false, 'error' => 'Not found'];
        
        $update = [];
        foreach (['full_name','role','title','biography','email','phone','department','display_order'] as $k) {
            if (isset($data[$k])) $update[$k] = trim($data[$k]);
        }
        foreach (['is_active','is_leadership','is_featured','show_email_public','show_phone_public'] as $k) {
            if (isset($data[$k])) $update[$k] = (int)$data[$k];
        }
        
        if (!empty($data['photo_file']['tmp_name'])) {
            $up = $this->upload->image($data['photo_file'], 'team', $actorId, $ip);
            if (!$up['success']) return $up;
            $update['photo'] = $up['path'];
            if ($member['photo'] && file_exists(UPLOAD_PATH . $member['photo'])) {
                unlink(UPLOAD_PATH . $member['photo']);
            }
        }
        
        $social = [];
        foreach (['linkedin','twitter','facebook'] as $platform) {
            if (!empty($data["social_$platform"])) $social[$platform] = trim($data["social_$platform"]);
        }
        if (!empty($social)) $update['social_links'] = $social;
        
        $this->model->update($id, $update);
        $this->audit->log($actorId, 'team_member_updated', 'team_member', $id, null, $update, $ip, $ua);
        return ['success' => true];
    }
    
    public function delete(int $id, int $actorId, string $ip, string $ua): array {
        $member = $this->model->findById($id);
        if (!$member) return ['success' => false, 'error' => 'Not found'];
        if ($member['photo'] && file_exists(UPLOAD_PATH . $member['photo'])) {
            unlink(UPLOAD_PATH . $member['photo']);
        }
        $this->model->delete($id);
        $this->audit->log($actorId, 'team_member_deleted', 'team_member', $id, ['name' => $member['full_name']], null, $ip, $ua);
        return ['success' => true];
    }
}