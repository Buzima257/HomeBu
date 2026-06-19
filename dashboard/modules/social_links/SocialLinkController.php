<?php
declare(strict_types=1);

require_once __DIR__ . '/SocialLink.php';
require_once __DIR__ . '/../../core/Audit.php';
require_once __DIR__ . '/../../core/Upload.php';

class SocialLinkController {
    private PDO $db;
    private SocialLink $model;
    private Audit $audit;
    private Upload $upload;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->model = new SocialLink($db);
        $this->audit = new Audit($db);
        $this->upload = new Upload($db);
    }
    public function list(): array { return $this->model->findAll(); }
    public function get(int $id): ?array { return $this->model->findById($id); }
    
    public function store(array $data, int $actorId, string $ip, string $ua): array {
        $platform = trim($data['platform'] ?? '');
        $url = trim($data['url'] ?? '');
        if ($platform === '' || $url === '') return ['success' => false, 'error' => 'Platform and URL required'];
        
        $icon = null;
        if (!empty($data['icon_file']['tmp_name'])) {
            $up = $this->upload->image($data['icon_file'], 'icons', $actorId, $ip);
            if (!$up['success']) return $up;
            $icon = $up['path'];
        }
        
        $id = $this->model->create([
            'platform' => $platform, 'icon' => $icon, 'url' => $url,
            'display_order' => (int)($data['display_order'] ?? 0),
            'is_active' => (int)($data['is_active'] ?? 1)
        ]);
        $this->audit->log($actorId, 'social_link_created', 'social_link', $id, null, ['platform' => $platform], $ip, $ua);
        return ['success' => true, 'id' => $id];
    }
    
    public function update(int $id, array $data, int $actorId, string $ip, string $ua): array {
        $s = $this->model->findById($id);
        if (!$s) return ['success' => false, 'error' => 'Not found'];
        
        $update = [];
        if (isset($data['platform'])) $update['platform'] = trim($data['platform']);
        if (isset($data['url'])) $update['url'] = trim($data['url']);
        if (isset($data['display_order'])) $update['display_order'] = (int)$data['display_order'];
        if (isset($data['is_active'])) $update['is_active'] = (int)$data['is_active'];
        
        if (!empty($data['icon_file']['tmp_name'])) {
            $up = $this->upload->image($data['icon_file'], 'icons', $actorId, $ip);
            if (!$up['success']) return $up;
            $update['icon'] = $up['path'];
            if ($s['icon'] && file_exists(UPLOAD_PATH . $s['icon'])) unlink(UPLOAD_PATH . $s['icon']);
        }
        
        $this->model->update($id, $update);
        $this->audit->log($actorId, 'social_link_updated', 'social_link', $id, null, $update, $ip, $ua);
        return ['success' => true];
    }
    
    public function delete(int $id, int $actorId, string $ip, string $ua): array {
        $s = $this->model->findById($id);
        if (!$s) return ['success' => false, 'error' => 'Not found'];
        if ($s['icon'] && file_exists(UPLOAD_PATH . $s['icon'])) unlink(UPLOAD_PATH . $s['icon']);
        $this->model->delete($id);
        $this->audit->log($actorId, 'social_link_deleted', 'social_link', $id, ['platform' => $s['platform']], null, $ip, $ua);
        return ['success' => true];
    }
}