<?php
declare(strict_types=1);

class StaticSection {
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function findAll(): array {
        $stmt = $this->db->query("SELECT * FROM static_sections ORDER BY display_order, id");
        return $stmt->fetchAll();
    }
    
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM static_sections WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    public function findBySlug(string $slug): ?array {
        $stmt = $this->db->prepare("SELECT * FROM static_sections WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }
    
    public function update(int $id, array $data): bool {
        $fields = [];
        $values = [];
        $allowed = ['title_en','title_fr','subtitle_en','subtitle_fr','content_en','content_fr',
                    'layout_type','bg_color','bg_image','is_active','display_order'];
        
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $fields[] = "$k = ?";
                $values[] = $data[$k];
            }
        }
        if (empty($fields)) return false;
        
        $values[] = $id;
        $stmt = $this->db->prepare("UPDATE static_sections SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }
    
    // ─── Media ───
    public function getMedia(int $sectionId): array {
        $stmt = $this->db->prepare("SELECT * FROM section_media WHERE section_id = ? ORDER BY sort_order, id");
        $stmt->execute([$sectionId]);
        return $stmt->fetchAll();
    }
    
    public function addMedia(int $sectionId, array $item): int {
        $stmt = $this->db->prepare("
            INSERT INTO section_media 
            (section_id, media_type, file_path, embed_url, file_name, file_size, mime_type, checksum_md5, 
             caption_en, caption_fr, sort_order, is_primary, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $sectionId,
            $item['media_type'],
            $item['file_path'] ?? null,
            $item['embed_url'] ?? null,
            $item['file_name'] ?? null,
            $item['file_size'] ?? null,
            $item['mime_type'] ?? null,
            $item['checksum_md5'] ?? null,
            $item['caption_en'] ?? null,
            $item['caption_fr'] ?? null,
            $item['sort_order'] ?? 0,
            $item['is_primary'] ?? 0,
            $item['uploaded_by'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }
    
    public function deleteMedia(int $mediaId): bool {
        $stmt = $this->db->prepare("DELETE FROM section_media WHERE id = ?");
        return $stmt->execute([$mediaId]);
    }
    
    public function countMedia(int $sectionId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM section_media WHERE section_id = ?");
        $stmt->execute([$sectionId]);
        return (int)$stmt->fetchColumn();
    }
    
    public function hasMediaType(int $sectionId, string $type): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM section_media WHERE section_id = ? AND media_type = ?");
        $stmt->execute([$sectionId, $type]);
        return (int)$stmt->fetchColumn() > 0;
    }
    
    public function clearMedia(int $sectionId): void {
        $stmt = $this->db->prepare("DELETE FROM section_media WHERE section_id = ?");
        $stmt->execute([$sectionId]);
    }
}