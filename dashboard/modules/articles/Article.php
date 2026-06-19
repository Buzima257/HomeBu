<?php
declare(strict_types=1);

class Article {
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function findAll(string $lang = '', int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = "1=1";
        
        if ($lang && in_array($lang, ['en','fr'])) {
            $where .= " AND lang = ?";
            $params[] = $lang;
        }
        
        $stmt = $this->db->prepare("
            SELECT SQL_CALC_FOUND_ROWS a.*, 
                   (SELECT COUNT(*) FROM article_gallery WHERE article_id = a.id) as gallery_count
            FROM articles a
            WHERE $where
            ORDER BY a.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([...$params, $perPage, $offset]);
        $rows = $stmt->fetchAll();
        
        $total = (int)$this->db->query("SELECT FOUND_ROWS()")->fetchColumn();
        
        return ['rows' => $rows, 'total' => $total, 'pages' => (int)ceil($total / $perPage)];
    }
    
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    public function findBySlug(string $slug, string $lang): ?array {
        $stmt = $this->db->prepare("SELECT * FROM articles WHERE slug = ? AND lang = ? LIMIT 1");
        $stmt->execute([$slug, $lang]);
        return $stmt->fetch() ?: null;
    }
    
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO articles 
            (slug, lang, title, description, featured_type, featured_image, video_embed_url, status, published_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['slug'],
            $data['lang'],
            $data['title'],
            $data['description'] ?? null,
            $data['featured_type'] ?? 'image',
            $data['featured_image'] ?? null,
            $data['video_embed_url'] ?? null,
            $data['status'] ?? 'draft',
            $data['published_at'] ?? null,
            $data['created_by'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }
    
    public function update(int $id, array $data): bool {
        $fields = [];
        $values = [];
        $allowed = ['slug','title','description','featured_type','featured_image','video_embed_url','status','published_at'];
        
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $fields[] = "$k = ?";
                $values[] = $data[$k];
            }
        }
        
        if (empty($fields)) return false;
        
        $values[] = $id;
        $stmt = $this->db->prepare("UPDATE articles SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }
    
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM articles WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function incrementViews(int $id): void {
        $stmt = $this->db->prepare("UPDATE articles SET view_count = view_count + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    // ─── Gallery ───
    public function getGallery(int $articleId): array {
        $stmt = $this->db->prepare("SELECT * FROM article_gallery WHERE article_id = ? ORDER BY sort_order, id");
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    }
    
    public function addGalleryItem(int $articleId, array $item): int {
        $stmt = $this->db->prepare("
            INSERT INTO article_gallery (article_id, media_type, file_path, embed_url, caption_en, caption_fr, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $articleId,
            $item['media_type'],
            $item['file_path'] ?? null,
            $item['embed_url'] ?? null,
            $item['caption_en'] ?? null,
            $item['caption_fr'] ?? null,
            $item['sort_order'] ?? 0
        ]);
        return (int)$this->db->lastInsertId();
    }
    
    public function deleteGalleryItem(int $itemId): bool {
        $stmt = $this->db->prepare("DELETE FROM article_gallery WHERE id = ?");
        return $stmt->execute([$itemId]);
    }
    
    public function updateGalleryOrder(int $itemId, int $order): bool {
        $stmt = $this->db->prepare("UPDATE article_gallery SET sort_order = ? WHERE id = ?");
        return $stmt->execute([$order, $itemId]);
    }
    
    // ─── Partners ───
    public function getLinkedPartners(int $articleId): array {
        $stmt = $this->db->prepare("
            SELECT p.id, p.name, p.logo, p.link 
            FROM partners p
            JOIN article_partners ap ON p.id = ap.partner_id
            WHERE ap.article_id = ?
        ");
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    }
    
    public function syncPartners(int $articleId, array $partnerIds): void {
        $this->db->prepare("DELETE FROM article_partners WHERE article_id = ?")->execute([$articleId]);
        if (empty($partnerIds)) return;
        
        $stmt = $this->db->prepare("INSERT INTO article_partners (article_id, partner_id) VALUES (?, ?)");
        foreach ($partnerIds as $pid) {
            $stmt->execute([$articleId, (int)$pid]);
        }
    }
    
    public function getAllPartners(): array {
        $stmt = $this->db->query("SELECT id, name, logo FROM partners WHERE is_active = 1 ORDER BY display_order, name");
        return $stmt->fetchAll();
    }
}