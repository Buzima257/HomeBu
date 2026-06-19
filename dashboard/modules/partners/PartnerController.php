<?php
declare(strict_types=1);

require_once __DIR__ . '/Partner.php';
require_once __DIR__ . '/../../core/Audit.php';
require_once __DIR__ . '/../../core/Upload.php';

class PartnerController {
    private PDO $db;
    private Partner $model;
    private Audit $audit;
    private Upload $upload;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->model = new Partner($db);
        $this->audit = new Audit($db);
        $this->upload = new Upload($db);
    }
    public function list(): array { return $this->model->findAll(); }
    public function get(int $id): ?array { return $this->model->findById($id); }
    
    public function store(array $data, int $actorId, string $ip, string $ua): array {
        $name = trim($data['name'] ?? '');
        if ($name === '') return ['success' => false, 'error' => 'Name required'];
        
        $logo = null;
        if (!empty($data['logo_file']['tmp_name'])) {
            $up = $this->upload->image($data['logo_file'], 'partners', $actorId, $ip);
            if (!$up['success']) return $up;
            $logo = $up['path'];
        }
        
        $id = $this->model->create([
            'name' => $name, 'logo' => $logo, 'link' => $data['link'] ?? null,
            'partner_type' => $data['partner_type'] ?? 'technical',
            'display_order' => (int)($data['display_order'] ?? 0),
            'is_active' => (int)($data['is_active'] ?? 1)
        ]);
        $this->audit->log($actorId, 'partner_created', 'partner', $id, null, ['name' => $name], $ip, $ua);
        return ['success' => true, 'id' => $id];
    }
    
    public function update(int $id, array $data, int $actorId, string $ip, string $ua): array {
        $p = $this->model->findById($id);
        if (!$p) return ['success' => false, 'error' => 'Not found'];
        
        $update = [];
        if (isset($data['name'])) $update['name'] = trim($data['name']);
        if (isset($data['link'])) $update['link'] = $data['link'] ?: null;
        if (isset($data['partner_type'])) $update['partner_type'] = $data['partner_type'];
        if (isset($data['display_order'])) $update['display_order'] = (int)$data['display_order'];
        if (isset($data['is_active'])) $update['is_active'] = (int)$data['is_active'];
        
        if (!empty($data['logo_file']['tmp_name'])) {
            $up = $this->upload->image($data['logo_file'], 'partners', $actorId, $ip);
            if (!$up['success']) return $up;
            $update['logo'] = $up['path'];
            if ($p['logo'] && file_exists(UPLOAD_PATH . $p['logo'])) unlink(UPLOAD_PATH . $p['logo']);
        }
        
        $this->model->update($id, $update);
        $this->audit->log($actorId, 'partner_updated', 'partner', $id, null, $update, $ip, $ua);
        return ['success' => true];
    }
    
    public function delete(int $id, int $actorId, string $ip, string $ua): array {
        $p = $this->model->findById($id);
        if (!$p) return ['success' => false, 'error' => 'Not found'];
        if ($p['logo'] && file_exists(UPLOAD_PATH . $p['logo'])) unlink(UPLOAD_PATH . $p['logo']);
        $this->model->delete($id);
        $this->audit->log($actorId, 'partner_deleted', 'partner', $id, ['name' => $p['name']], null, $ip, $ua);
        return ['success' => true];
    }
}