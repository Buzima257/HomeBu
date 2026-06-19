<?php
declare(strict_types=1);

require_once __DIR__ . '/Setting.php';
require_once __DIR__ . '/../../core/Audit.php';

class SettingController {
    private PDO $db;
    private Setting $model;
    private Audit $audit;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->model = new Setting($db);
        $this->audit = new Audit($db);
    }
    
    public function getAll(): array {
        return $this->model->findAll();
    }
    
    public function updateBatch(array $data, int $actorId, string $ip, string $ua): array {
        $errors = [];
        $changed = [];
        $allKeys = $this->model->getSettingKeys();
        $flatKeys = array_merge(...array_values($allKeys));
        
        // ─── Validation Uploads ───
        $imgMax = (int)($data['image_max_size_mb'] ?? 5);
        $imgMin = (int)($data['image_min_size_kb'] ?? 100);
        if ($imgMax * 1024 < $imgMin) {
            $errors[] = "Image max size must be greater than min size";
        }
        
        $vidMax = (int)($data['video_max_size_mb'] ?? 50);
        $vidMin = (int)($data['video_min_size_kb'] ?? 500);
        if ($vidMax * 1024 < $vidMin) {
            $errors[] = "Video max size must be greater than min size";
        }
        
        $shortMax = (int)($data['short_video_max_size_mb'] ?? 10);
        if ($shortMax > 50) $errors[] = "Short video max size cannot exceed 50MB";
        
        $shortDur = (int)($data['short_video_max_duration_sec'] ?? 60);
        if ($shortDur > 300) $errors[] = "Short video duration cannot exceed 300 seconds";
        
        // ─── Validation Sécurité ───
        $timeout = (int)($data['session_timeout_minutes'] ?? 30);
        if ($timeout < 5 || $timeout > 1440) $errors[] = "Session timeout must be between 5 and 1440 minutes";
        
        $maxAttempts = (int)($data['max_login_attempts'] ?? 5);
        if ($maxAttempts < 1 || $maxAttempts > 20) $errors[] = "Max login attempts must be between 1 and 20";
        
        $lockout = (int)($data['lockout_duration_minutes'] ?? 15);
        if ($lockout < 1 || $lockout > 1440) $errors[] = "Lockout duration must be between 1 and 1440 minutes";
        
        // ─── Validation Pagination ───
        $teamPerPage = (int)($data['team_members_per_page'] ?? 12);
        if ($teamPerPage < 1) $errors[] = "Team members per page must be at least 1";
        
        $histPerPage = (int)($data['history_events_per_page'] ?? 20);
        if ($histPerPage < 1) $errors[] = "History events per page must be at least 1";
        
        if (!empty($errors)) {
            return ['success' => false, 'error' => implode(' | ', $errors)];
        }
        
        // ─── Mise à jour ───
        foreach ($flatKeys as $key) {
            $type = $this->model->findByKey($key)['setting_type'] ?? 'string';
            
            if ($type === 'bool') {
                // Checkbox : présente = 1, absente = 0
                $value = isset($data[$key]) ? '1' : '0';
            } elseif ($type === 'int') {
                $value = (int)($data[$key] ?? 0);
            } else {
                $value = trim($data[$key] ?? '');
            }
            
            $old = $this->model->findByKey($key)['setting_value'] ?? null;
            if ((string)$old !== (string)$value) {
                $this->model->update($key, $value, $actorId);
                $changed[$key] = ['old' => $old, 'new' => $value];
            }
        }
        
        if (!empty($changed)) {
            $this->audit->log($actorId, 'settings_changed', 'settings', null, $changed, null, $ip, $ua);
        }
        
        return ['success' => true, 'changed' => count($changed)];
    }
}