<?php
declare(strict_types=1);

class Setting {
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function findAll(): array {
        $stmt = $this->db->query("SELECT * FROM site_settings ORDER BY id");
        $rows = [];
        foreach ($stmt->fetchAll() as $r) {
            $rows[$r['setting_key']] = $r;
        }
        return $rows;
    }
    
    public function findByKey(string $key): ?array {
        $stmt = $this->db->prepare("SELECT * FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetch() ?: null;
    }
    
    public function getValue(string $key, mixed $default = null): mixed {
        $s = $this->findByKey($key);
        if (!$s) return $default;
        return match($s['setting_type']) {
            'int' => (int)$s['setting_value'],
            'bool' => $s['setting_value'] === '1' || $s['setting_value'] === 'true',
            'json' => json_decode($s['setting_value'] ?? 'null', true),
            default => $s['setting_value']
        };
    }
    
    public function update(string $key, mixed $value, ?int $updatedBy): bool {
        $stmt = $this->db->prepare("
            UPDATE site_settings 
            SET setting_value = ?, updated_by = ?, updated_at = NOW() 
            WHERE setting_key = ?
        ");
        return $stmt->execute([(string)$value, $updatedBy, $key]);
    }
    
    public function getSettingKeys(): array {
        return [
            'uploads' => [
                'image_max_size_mb','image_min_size_kb','image_max_width_px','image_max_height_px',
                'image_min_width_px','image_min_height_px','video_max_size_mb','video_min_size_kb',
                'short_video_max_size_mb','short_video_max_duration_sec','icon_max_size_kb','icon_min_size_kb'
            ],
            'security' => [
                'session_timeout_minutes','max_login_attempts','lockout_duration_minutes','enforce_ip_check'
            ],
            'sections' => [
                'show_mission_section','show_vision_section','show_values_section','show_history_section',
                'show_sectors_section','show_zones_section','show_stats_section','show_team_section',
                'show_articles_section','show_testimonials_section','show_announcements_section',
                'show_partners_section','show_donate_section','show_contact_section'
            ],
            'team_history' => [
                'team_members_per_page','history_events_per_page','team_show_email_default','team_show_phone_default'
            ],
            'global' => [
                'allow_static_section_edit','maintenance_mode','theme_active'
            ]
        ];
    }
}