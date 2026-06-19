<?php
declare(strict_types=1);

require_once __DIR__ . '/ImpactStat.php';
require_once __DIR__ . '/../../core/Audit.php';
require_once __DIR__ . '/../../core/Upload.php';

class ImpactStatController {
    private PDO $db;
    private ImpactStat $model;
    private Audit $audit;
    private Upload $upload;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->model = new ImpactStat($db);
        $this->audit = new Audit($db);
        $this->upload = new Upload($db);
    }
    public function list(): array { return $this->model->findAll(); }
    public function get(int $id): ?array { return $this->model->findById($id); }
    
    public function store(array $data, int $actorId, string $ip, string $ua): array {
        $label = trim($data['label_en'] ?? '');
        if ($label === '') return ['success' => false, 'error' => 'English label required'];
        
        $icon = null;
        if (!empty($data['icon_file']['tmp_name'])) {
            $up = $this->upload->image($data['icon_file'], 'icons', $actorId, $ip);
            if (!$up['success']) return $up;
            $icon = $up['path'];
        }
        
        $id = $this->model->create([
            'label_en' => $label, 'label_fr' => $data['label_fr'] ?? null,
            'value' => (int)($data['value'] ?? 0), 'suffix_en' => $data['suffix_en'] ?? null,
            'suffix_fr' => $data['suffix_fr'] ?? null, 'icon' => $icon,
            'display_order' => (int)($data['display_order'] ?? 0),
            'is_active' => (int)($data['is_active'] ?? 1), 'updated_by' => $actorId
        ]);
        $this->audit->log($actorId, 'impact_stat_created', 'impact_stat', $id, null, ['label_en' => $label], $ip, $ua);
        return ['success' => true, 'id' => $id];
    }
    
    public function update(int $id, array $data, int $actorId, string $ip, string $ua): array {
        $s = $this->model->findById($id);
        if (!$s) return ['success' => false, 'error' => 'Not found'];
        
        $update = [];
        foreach (['label_en','label_fr','suffix_en','suffix_fr'] as $k) {
            if (isset($data[$k])) $update[$k] = trim($data[$k]) ?: null;
        }
        if (isset($data['value'])) $update['value'] = (int)$data['value'];
        if (isset($data['display_order'])) $update['display_order'] = (int)$data['display_order'];
        if (isset($data['is_active'])) $update['is_active'] = (int)$data['is_active'];
        $update['updated_by'] = $actorId;
        
        if (!empty($data['icon_file']['tmp_name'])) {
            $up = $this->upload->image($data['icon_file'], 'icons', $actorId, $ip);
            if (!$up['success']) return $up;
            $update['icon'] = $up['path'];
            if ($s['icon'] && file_exists(UPLOAD_PATH . $s['icon'])) unlink(UPLOAD_PATH . $s['icon']);
        }
        
        $this->model->update($id, $update);
        $this->audit->log($actorId, 'impact_stat_updated', 'impact_stat', $id, null, $update, $ip, $ua);
        return ['success' => true];
    }
    
    public function delete(int $id, int $actorId, string $ip, string $ua): array {
        $s = $this->model->findById($id);
        if (!$s) return ['success' => false, 'error' => 'Not found'];
        if ($s['icon'] && file_exists(UPLOAD_PATH . $s['icon'])) unlink(UPLOAD_PATH . $s['icon']);
        $this->model->delete($id);
        $this->audit->log($actorId, 'impact_stat_deleted', 'impact_stat', $id, ['label_en' => $s['label_en']], null, $ip, $ua);
        return ['success' => true];
    }
}