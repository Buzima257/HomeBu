<?php
declare(strict_types=1);

require_once __DIR__ . '/StaticSection.php';
require_once __DIR__ . '/../../core/Audit.php';
require_once __DIR__ . '/../../core/Upload.php';
require_once __DIR__ . '/../../core/EmbedValidator.php';

class StaticSectionController {
    private PDO $db;
    private StaticSection $model;
    private Audit $audit;
    private Upload $upload;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->model = new StaticSection($db);
        $this->audit = new Audit($db);
        $this->upload = new Upload($db);
    }
    
    public function list(): array {
        return $this->model->findAll();
    }
    
    public function get(int $id): ?array {
        $section = $this->model->findById($id);
        if ($section) {
            $section['media'] = $this->model->getMedia($id);
        }
        return $section;
    }
    
    public function update(int $id, array $data, int $actorId, string $ip, string $ua): array {
        $section = $this->model->findById($id);
        if (!$section) return ['success' => false, 'error' => 'Section not found'];
        
        $update = [];
        foreach (['title_en','title_fr','subtitle_en','subtitle_fr','content_en','content_fr',
                  'layout_type','bg_color','is_active','display_order'] as $k) {
            if (isset($data[$k])) $update[$k] = $data[$k];
        }
        
        if (isset($data['bg_image_file']) && !empty($data['bg_image_file']['tmp_name'])) {
            $up = $this->upload->image($data['bg_image_file'], 'static', $actorId, $ip);
            if (!$up['success']) return $up;
            $update['bg_image'] = $up['path'];
        }
        
        $old = ['title_en' => $section['title_en'], 'is_active' => $section['is_active']];
        $this->model->update($id, $update);
        $this->audit->log($actorId, 'section_updated', 'static_section', $id, $old, $update, $ip, $ua);
        
        return ['success' => true];
    }
    
    public function handleMedia(int $sectionId, array $data, array $files, int $actorId, string $ip): array {
        $section = $this->model->findById($sectionId);
        if (!$section) return ['success' => false, 'error' => 'Section not found'];
        
        $currentCount = $this->model->countMedia($sectionId);
        $mediaType = $data['media_type'] ?? 'image';
        
        // ─── Contraintes de cohabitation ───
        if ($mediaType === 'image') {
            if ($this->model->hasMediaType($sectionId, 'video_embed') || $this->model->hasMediaType($sectionId, 'short_video')) {
                return ['success' => false, 'error' => 'Cannot mix images with video in same section. Delete existing video first.'];
            }
        } elseif (in_array($mediaType, ['video_embed','short_video'], true)) {
            if ($this->model->hasMediaType($sectionId, 'image')) {
                return ['success' => false, 'error' => 'Cannot mix video with images in same section. Delete existing images first.'];
            }
            if ($this->model->hasMediaType($sectionId, 'video_embed') || $this->model->hasMediaType($sectionId, 'short_video')) {
                return ['success' => false, 'error' => 'Only one video allowed per section. Delete existing video first.'];
            }
        }
        
        // ─── Limite 5 max ───
        $newItems = 0;
        if ($mediaType === 'image' && !empty($files['media_files']['tmp_name'])) {
            $newItems = count(array_filter($files['media_files']['tmp_name']));
        } elseif (in_array($mediaType, ['video_embed','short_video'], true)) {
            $newItems = 1;
        }
        
        if ($currentCount + $newItems > 5) {
            return ['success' => false, 'error' => "Maximum 5 media per section. Current: $currentCount, Adding: $newItems"];
        }
        
        // ─── Upload avec gestion d'erreur par fichier ───
        $results = [];
        $hasError = false;
        
        if ($mediaType === 'image' && !empty($files['media_files']['tmp_name'])) {
            foreach ($files['media_files']['tmp_name'] as $i => $tmp) {
                if (empty($tmp)) continue;
                $file = [
                    'tmp_name' => $tmp,
                    'name'     => $files['media_files']['name'][$i] ?? 'img.jpg',
                    'size'     => $files['media_files']['size'][$i] ?? 0,
                    'error'    => $files['media_files']['error'][$i] ?? UPLOAD_ERR_NO_FILE
                ];
                $up = $this->upload->image($file, 'static', $actorId, $ip);
                if ($up['success']) {
                    $this->model->addMedia($sectionId, [
                        'media_type'   => 'image',
                        'file_path'    => $up['path'],
                        'file_name'    => $file['name'],
                        'file_size'    => $file['size'],
                        'mime_type'    => 'image/jpeg',
                        'checksum_md5' => $up['checksum'],
                        'caption_en'   => $data['caption_en'][$i] ?? null,
                        'caption_fr'   => $data['caption_fr'][$i] ?? null,
                        'sort_order'   => $i,
                        'is_primary'   => ($i === 0 && $currentCount === 0) ? 1 : 0,
                        'uploaded_by'  => $actorId
                    ]);
                    $results[] = ['file' => $file['name'], 'status' => 'success'];
                } else {
                    $results[] = ['file' => $file['name'], 'status' => 'error', 'error' => $up['error']];
                    $hasError = true;
                }
            }
        } elseif ($mediaType === 'video_embed' && !empty($data['embed_url'])) {
            $clean = EmbedValidator::validate($data['embed_url']);
            if (!$clean) return ['success' => false, 'error' => 'Invalid embed URL'];
            
            $this->model->addMedia($sectionId, [
                'media_type'  => 'video_embed',
                'embed_url'   => $clean,
                'caption_en'  => $data['caption_en'] ?? null,
                'caption_fr'  => $data['caption_fr'] ?? null,
                'sort_order'  => 0,
                'uploaded_by' => $actorId
            ]);
            $results[] = ['status' => 'success'];
        } elseif ($mediaType === 'short_video' && !empty($files['media_file']['tmp_name'])) {
            $up = $this->upload->shortVideo($files['media_file'], 'short-videos', $actorId, $ip);
            if (!$up['success']) return $up;
            
            $this->model->addMedia($sectionId, [
                'media_type'   => 'short_video',
                'file_path'    => $up['path'],
                'file_name'    => $files['media_file']['name'],
                'file_size'    => $files['media_file']['size'],
                'mime_type'    => 'video/mp4',
                'checksum_md5' => $up['checksum'],
                'caption_en'   => $data['caption_en'] ?? null,
                'caption_fr'   => $data['caption_fr'] ?? null,
                'sort_order'   => 0,
                'uploaded_by'  => $actorId
            ]);
            $results[] = ['status' => 'success'];
        }
        
        if ($hasError && empty(array_filter($results, fn($r) => $r['status'] === 'success'))) {
            // Tous les fichiers ont échoué
            $firstError = array_values(array_filter($results, fn($r) => $r['status'] === 'error'))[0]['error'] ?? 'Upload failed';
            return ['success' => false, 'error' => $firstError, 'details' => $results];
        }
        
        return ['success' => true, 'results' => $results];
    }
    
    public function deleteMedia(int $mediaId, int $actorId, string $ip, string $ua): array {
        $stmt = $this->db->prepare("SELECT * FROM section_media WHERE id = ?");
        $stmt->execute([$mediaId]);
        $media = $stmt->fetch();
        
        if (!$media) return ['success' => false, 'error' => 'Media not found'];
        
        if ($media['file_path'] && file_exists(UPLOAD_PATH . $media['file_path'])) {
            unlink(UPLOAD_PATH . $media['file_path']);
            $dir = dirname(UPLOAD_PATH . $media['file_path']);
            $base = pathinfo($media['file_path'], PATHINFO_FILENAME);
            @unlink($dir . '/webp/' . $base . '.webp');
            @unlink($dir . '/thumb/' . basename($media['file_path']));
            @unlink($dir . '/medium/' . basename($media['file_path']));
        }
        
        $this->model->deleteMedia($mediaId);
        
        $this->audit->log($actorId, 'media_deleted', 'media', $mediaId, [
            'file_path' => $media['file_path'],
            'checksum'  => $media['checksum_md5']
        ], null, $ip, $ua);
        
        return ['success' => true];
    }
}