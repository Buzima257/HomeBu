<?php
declare(strict_types=1);

require_once __DIR__ . '/HistoryEvent.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Audit.php';
require_once __DIR__ . '/../../core/Upload.php';

class HistoryController {
    private PDO $db;
    private HistoryEvent $model;
    private Audit $audit;
    private Upload $upload;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->model = new HistoryEvent($db);
        $this->audit = new Audit($db);
        $this->upload = new Upload($db);
    }
    
    public function list(int $page = 1): array {
        return $this->model->findAll($page);
    }
    
    public function get(int $id): ?array {
        return $this->model->findById($id);
    }
    
    public function store(array $data, int $actorId, string $ip, string $ua): array {
        $year = (int)($data['year'] ?? 0);
        $title = trim($data['title'] ?? '');
        if ($year < 1900 || $year > date('Y') + 5) {
            return ['success' => false, 'error' => 'Invalid year'];
        }
        if ($title === '') {
            return ['success' => false, 'error' => 'Title required'];
        }
        
        $slug = Validator::slug($data['slug'] ?? $title . '-' . $year);
        $base = $slug; $counter = 1;
        while ($this->db->prepare("SELECT id FROM organization_history WHERE slug = ?")->execute([$slug]) && $this->db->prepare("SELECT id FROM organization_history WHERE slug = ?")->fetch()) {
            $slug = $base . '-' . $counter++;
        }
        
        $image = null;
        if (!empty($data['image_file']['tmp_name'])) {
            $up = $this->upload->image($data['image_file'], 'history', $actorId, $ip);
            if (!$up['success']) return $up;
            $image = $up['path'];
        }
        
        $id = $this->model->create([
            'slug' => $slug, 'year' => $year, 'month' => !empty($data['month']) ? (int)$data['month'] : null,
            'day' => !empty($data['day']) ? (int)$data['day'] : null, 'title' => $title,
            'description' => $data['description'] ?? null, 'image' => $image,
            'icon' => $data['icon'] ?? 'star', 'is_major' => (int)($data['is_major'] ?? 0),
            'display_order' => (int)($data['display_order'] ?? 0), 'created_by' => $actorId
        ]);
        
        $this->audit->log($actorId, 'history_created', 'history', $id, null, ['year' => $year, 'title' => $title], $ip, $ua);
        return ['success' => true, 'id' => $id];
    }
    
    public function update(int $id, array $data, int $actorId, string $ip, string $ua): array {
        $event = $this->model->findById($id);
        if (!$event) return ['success' => false, 'error' => 'Not found'];
        
        $update = [];
        foreach (['year','month','day','title','description','icon','display_order','is_major','is_active'] as $k) {
            if (isset($data[$k])) $update[$k] = ($k === 'year' || $k === 'month' || $k === 'day' || $k === 'display_order' || $k === 'is_major' || $k === 'is_active') ? (int)$data[$k] : trim($data[$k]);
        }
        
        if (!empty($data['image_file']['tmp_name'])) {
            $up = $this->upload->image($data['image_file'], 'history', $actorId, $ip);
            if (!$up['success']) return $up;
            $update['image'] = $up['path'];
            if ($event['image'] && file_exists(UPLOAD_PATH . $event['image'])) unlink(UPLOAD_PATH . $event['image']);
        }
        
        $this->model->update($id, $update);
        $this->audit->log($actorId, 'history_updated', 'history', $id, null, $update, $ip, $ua);
        return ['success' => true];
    }
    
    public function delete(int $id, int $actorId, string $ip, string $ua): array {
        $event = $this->model->findById($id);
        if (!$event) return ['success' => false, 'error' => 'Not found'];
        if ($event['image'] && file_exists(UPLOAD_PATH . $event['image'])) unlink(UPLOAD_PATH . $event['image']);
        $this->model->delete($id);
        $this->audit->log($actorId, 'history_deleted', 'history', $id, ['title' => $event['title']], null, $ip, $ua);
        return ['success' => true];
    }
}