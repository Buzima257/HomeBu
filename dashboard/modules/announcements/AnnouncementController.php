<?php
declare(strict_types=1);

require_once __DIR__ . '/Announcement.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Audit.php';

class AnnouncementController {
    private PDO $db;
    private Announcement $model;
    private Audit $audit;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->model = new Announcement($db);
        $this->audit = new Audit($db);
    }
    
    public function list(string $status = '', int $page = 1): array {
        $this->model->expireOld();
        return $this->model->findAll($status, $page);
    }
    
    public function get(int $id): ?array {
        return $this->model->findById($id);
    }
    
    public function store(array $data, int $actorId, string $ip, string $ua): array {
        $title = trim($data['title'] ?? '');
        if ($title === '') return ['success' => false, 'error' => 'Title required'];
        
        $slug = Validator::slug($data['slug'] ?? $title);
        $base = $slug; $counter = 1;
        while ($this->db->prepare("SELECT id FROM announcements WHERE slug = ?")->execute([$slug]) && $this->db->prepare("SELECT id FROM announcements WHERE slug = ?")->fetch()) {
            $slug = $base . '-' . $counter++;
        }
        
        $status = $data['status'] ?? 'draft';
        $start = $data['start_date'] ?? null;
        $end = $data['end_date'] ?? null;
        
        if ($end && $start && $end < $start) {
            return ['success' => false, 'error' => 'End date must be after start date'];
        }
        
        $id = $this->model->create([
            'slug' => $slug, 'lang' => $data['lang'] ?? 'en', 'title' => $title,
            'description' => $data['description'] ?? null,
            'start_date' => $start, 'end_date' => $end,
            'status' => $status, 'created_by' => $actorId
        ]);
        
        $this->audit->log($actorId, 'announcement_created', 'announcement', $id, null, ['title' => $title, 'status' => $status], $ip, $ua);
        return ['success' => true, 'id' => $id];
    }
    
    public function update(int $id, array $data, int $actorId, string $ip, string $ua): array {
        $a = $this->model->findById($id);
        if (!$a) return ['success' => false, 'error' => 'Not found'];
        
        $update = [];
        if (isset($data['title'])) $update['title'] = trim($data['title']);
        if (isset($data['description'])) $update['description'] = trim($data['description']);
        if (isset($data['start_date'])) $update['start_date'] = $data['start_date'] ?: null;
        if (isset($data['end_date'])) $update['end_date'] = $data['end_date'] ?: null;
        if (isset($data['status'])) $update['status'] = $data['status'];
        
        if (!empty($update['end_date']) && !empty($update['start_date']) && $update['end_date'] < $update['start_date']) {
            return ['success' => false, 'error' => 'End date must be after start date'];
        }
        
        $this->model->update($id, $update);
        $this->audit->log($actorId, 'announcement_updated', 'announcement', $id, null, $update, $ip, $ua);
        return ['success' => true];
    }
    
    public function delete(int $id, int $actorId, string $ip, string $ua): array {
        $a = $this->model->findById($id);
        if (!$a) return ['success' => false, 'error' => 'Not found'];
        $this->model->delete($id);
        $this->audit->log($actorId, 'announcement_deleted', 'announcement', $id, ['title' => $a['title']], null, $ip, $ua);
        return ['success' => true];
    }
}