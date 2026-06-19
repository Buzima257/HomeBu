<?php
declare(strict_types=1);

require_once __DIR__ . '/Testimonial.php';
require_once __DIR__ . '/../../core/Audit.php';
require_once __DIR__ . '/../../core/Upload.php';
require_once __DIR__ . '/../../core/EmbedValidator.php';

class TestimonialController {
    private PDO $db;
    private Testimonial $model;
    private Audit $audit;
    private Upload $upload;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->model = new Testimonial($db);
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
        $name = trim($data['name'] ?? '');
        $text = trim($data['text'] ?? '');
        if ($name === '' || $text === '') {
            return ['success' => false, 'error' => 'Name and text are required'];
        }
        
        $photo = null;
        if (!empty($data['photo_file']['tmp_name'])) {
            $up = $this->upload->image($data['photo_file'], 'testimonials', $actorId, $ip);
            if (!$up['success']) return $up;
            $photo = $up['path'];
        }
        
        $videoType = $data['video_type'] ?? 'none';
        $embedUrl = null;
        $videoFile = null;
        
        if ($videoType === 'embed') {
            $url = trim($data['video_embed_url'] ?? '');
            $clean = EmbedValidator::validate($url);
            if (!$clean) return ['success' => false, 'error' => 'Invalid embed URL'];
            $embedUrl = $clean;
        } elseif ($videoType === 'short') {
            if (!empty($data['video_file']['tmp_name'])) {
                $up = $this->upload->shortVideo($data['video_file'], 'short-videos', $actorId, $ip);
                if (!$up['success']) return $up;
                $videoFile = $up['path'];
            } else {
                return ['success' => false, 'error' => 'Short video file required'];
            }
        }
        
        $id = $this->model->create([
            'lang' => $data['lang'] ?? 'en', 'name' => $name, 'role' => $data['role'] ?? null,
            'text' => $text, 'photo' => $photo, 'video_type' => $videoType,
            'video_embed_url' => $embedUrl, 'video_file' => $videoFile,
            'is_featured' => (int)($data['is_featured'] ?? 0),
            'display_order' => (int)($data['display_order'] ?? 0),
            'created_by' => $actorId
        ]);
        
        $this->audit->log($actorId, 'testimonial_created', 'testimonial', $id, null, ['name' => $name], $ip, $ua);
        return ['success' => true, 'id' => $id];
    }
    
        public function update(int $id, array $data, int $actorId, string $ip, string $ua): array {
        $t = $this->model->findById($id);
        if (!$t) return ['success' => false, 'error' => 'Not found'];
        
        $update = [];
        if (isset($data['name'])) $update['name'] = trim($data['name']);
        if (isset($data['role'])) $update['role'] = trim($data['role']);
        if (isset($data['text'])) $update['text'] = trim($data['text']);
        if (isset($data['is_featured'])) $update['is_featured'] = (int)$data['is_featured'];
        if (isset($data['display_order'])) $update['display_order'] = (int)$data['display_order'];
        
        if (!empty($data['photo_file']['tmp_name'])) {
            $up = $this->upload->image($data['photo_file'], 'testimonials', $actorId, $ip);
            if (!$up['success']) return $up;
            $update['photo'] = $up['path'];
            if ($t['photo'] && file_exists(UPLOAD_PATH . $t['photo'])) unlink(UPLOAD_PATH . $t['photo']);
        }
        
        $videoType = $data['video_type'] ?? $t['video_type'];
        $update['video_type'] = $videoType;
        
        if ($videoType === 'embed') {
            $url = trim($data['video_embed_url'] ?? '');
            if ($url !== '') {
                $clean = EmbedValidator::validate($url);
                if (!$clean) return ['success' => false, 'error' => 'Invalid embed URL'];
                $update['video_embed_url'] = $clean;
                $update['video_file'] = null;
            } elseif ($t['video_type'] === 'embed') {
                // Garder l'ancienne URL si on ne change pas le champ
                $update['video_embed_url'] = $t['video_embed_url'];
            }
        } elseif ($videoType === 'short') {
            if (!empty($data['video_file']['tmp_name'])) {
                // NOUVEAU fichier uploadé
                $up = $this->upload->shortVideo($data['video_file'], 'short-videos', $actorId, $ip);
                if (!$up['success']) return $up;
                $update['video_file'] = $up['path'];
                $update['video_embed_url'] = null;
                // Supprimer l'ancien
                if ($t['video_file'] && file_exists(UPLOAD_PATH . $t['video_file'])) unlink(UPLOAD_PATH . $t['video_file']);
            } elseif ($t['video_type'] === 'short' && $t['video_file']) {
                // AUCUN nouveau fichier → garder l'ancien
                $update['video_file'] = $t['video_file'];
                $update['video_embed_url'] = null;
            } else {
                // Création sans fichier
                return ['success' => false, 'error' => 'Short video file required'];
            }
        } else {
            // none
            $update['video_embed_url'] = null;
            $update['video_file'] = null;
        }
        
        $this->model->update($id, $update);
        $this->audit->log($actorId, 'testimonial_updated', 'testimonial', $id, null, $update, $ip, $ua);
        return ['success' => true];
    }
    
    public function delete(int $id, int $actorId, string $ip, string $ua): array {
        $t = $this->model->findById($id);
        if (!$t) return ['success' => false, 'error' => 'Not found'];
        if ($t['photo'] && file_exists(UPLOAD_PATH . $t['photo'])) unlink(UPLOAD_PATH . $t['photo']);
        if ($t['video_file'] && file_exists(UPLOAD_PATH . $t['video_file'])) unlink(UPLOAD_PATH . $t['video_file']);
        $this->model->delete($id);
        $this->audit->log($actorId, 'testimonial_deleted', 'testimonial', $id, ['name' => $t['name']], null, $ip, $ua);
        return ['success' => true];
    }
}