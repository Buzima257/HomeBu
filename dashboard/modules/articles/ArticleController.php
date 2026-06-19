<?php
declare(strict_types=1);

require_once __DIR__ . '/Article.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/EmbedValidator.php';
require_once __DIR__ . '/../../core/Audit.php';
require_once __DIR__ . '/../../core/Upload.php';

class ArticleController {
    private PDO $db;
    private Article $model;
    private Audit $audit;
    private Upload $upload;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->model = new Article($db);
        $this->audit = new Audit($db);
        $this->upload = new Upload($db);
    }
    
    public function list(string $lang = '', int $page = 1): array {
        return $this->model->findAll($lang, $page);
    }
    
    public function get(int $id): ?array {
        $article = $this->model->findById($id);
        if ($article) {
            $article['gallery'] = $this->model->getGallery($id);
            $article['partners'] = $this->model->getLinkedPartners($id);
        }
        return $article;
    }
    
    public function store(array $data, int $actorId, string $ip, string $ua): array {
        $lang = $data['lang'] ?? 'en';
        $slug = Validator::slug($data['slug'] ?? $data['title'] ?? '');
        $title = trim($data['title'] ?? '');
        
        if ($title === '' || strlen($title) > 255) {
            return ['success' => false, 'error' => 'Title required, max 255 chars'];
        }
        if ($slug === '') {
            return ['success' => false, 'error' => 'Slug cannot be empty'];
        }
        
        // Uniqueness slug+lang
        if ($this->model->findBySlug($slug, $lang)) {
            $slug .= '-' . bin2hex(random_bytes(2));
        }
        
        $featuredType = $data['featured_type'] ?? 'image';
        $featuredImage = null;
        $videoEmbed = null;
        
        if ($featuredType === 'video_embed') {
            $url = trim($data['video_embed_url'] ?? '');
            $clean = EmbedValidator::validate($url);
            if (!$clean) {
                return ['success' => false, 'error' => 'Invalid embed URL (YouTube/Vimeo only)'];
            }
            $videoEmbed = $clean;
        } else {
            // Handle featured image upload
            if (!empty($data['featured_image_file']['tmp_name'])) {
                $up = $this->upload->image($data['featured_image_file'], 'articles', $actorId, $ip);
                if (!$up['success']) return $up;
                $featuredImage = $up['path'];
            } elseif (!empty($data['featured_image_path'])) {
                $featuredImage = $data['featured_image_path'];
            }
        }
        
        $id = $this->model->create([
            'slug'            => $slug,
            'lang'            => $lang,
            'title'           => $title,
            'description'     => $data['description'] ?? null,
            'featured_type'   => $featuredType,
            'featured_image'  => $featuredImage,
            'video_embed_url' => $videoEmbed,
            'status'          => in_array($data['status'] ?? '', ['draft','published','archived']) ? $data['status'] : 'draft',
            'published_at'    => ($data['status'] ?? '') === 'published' ? ($data['published_at'] ?? date('Y-m-d H:i:s')) : null,
            'created_by'      => $actorId
        ]);
        
        // Sync partners
        if (!empty($data['partner_ids']) && is_array($data['partner_ids'])) {
            $this->model->syncPartners($id, $data['partner_ids']);
        }
        
        $this->audit->log($actorId, 'article_created', 'article', $id, null, [
            'slug' => $slug, 'lang' => $lang, 'title' => $title, 'status' => $data['status'] ?? 'draft'
        ], $ip, $ua);
        
        return ['success' => true, 'id' => $id, 'slug' => $slug];
    }
    
       public function update(int $id, array $data, int $actorId, string $ip, string $ua): array {
        $article = $this->model->findById($id);
        if (!$article) return ['success' => false, 'error' => 'Article not found'];
        
        $update = [];
        
        // Champs texte simples
        if (isset($data['title'])) {
            $update['title'] = trim($data['title']);
        }
        if (isset($data['description'])) {
            $update['description'] = $data['description'];
        }
        if (isset($data['status'])) {
            $update['status'] = in_array($data['status'], ['draft','published','archived']) ? $data['status'] : 'draft';
        }
        if (isset($data['published_at']) && $data['published_at'] !== '') {
            $update['published_at'] = $data['published_at'];
        } elseif (isset($data['status']) && $data['status'] === 'published' && empty($article['published_at'])) {
            $update['published_at'] = date('Y-m-d H:i:s');
        }
        
        // ─── Featured Media ───
        $featuredType = $data['featured_type'] ?? $article['featured_type'];
        $update['featured_type'] = $featuredType;
        
        if ($featuredType === 'video_embed') {
            $url = trim($data['video_embed_url'] ?? '');
            if ($url !== '') {
                $clean = EmbedValidator::validate($url);
                if ($clean) {
                    $update['video_embed_url'] = $clean;
                    $update['featured_image'] = null;
                } else {
                    return ['success' => false, 'error' => 'Invalid embed URL (YouTube/Vimeo only)'];
                }
            }
            // Si URL vide mais on passe en mode video, garder l'ancienne si elle existe
            elseif ($article['featured_type'] === 'video_embed') {
                $update['video_embed_url'] = $article['video_embed_url'];
            }
        } else {
            // Mode image
            if (!empty($data['featured_image_file']['tmp_name'])) {
                $up = $this->upload->image($data['featured_image_file'], 'articles', $actorId, $ip);
                if (!$up['success']) return $up;
                $update['featured_image'] = $up['path'];
                $update['video_embed_url'] = null;
            } elseif (!empty($data['featured_image_path'])) {
                // Garder l'image existante
                $update['featured_image'] = $data['featured_image_path'];
                $update['video_embed_url'] = null;
            } elseif ($article['featured_type'] === 'image' && $article['featured_image']) {
                $update['featured_image'] = $article['featured_image'];
                $update['video_embed_url'] = null;
            }
        }
        
        // ─── Slug ───
        if (!empty($data['slug'])) {
            $newSlug = Validator::slug($data['slug']);
            // Si le slug change, vérifier qu'il n'existe pas déjà pour cette langue (autre article)
            if ($newSlug !== $article['slug']) {
                $existing = $this->model->findBySlug($newSlug, $article['lang']);
                if ($existing && (int)$existing['id'] !== $id) {
                    $newSlug .= '-' . bin2hex(random_bytes(2));
                }
            }
            $update['slug'] = $newSlug;
        }
        
        if (empty($update)) {
            return ['success' => false, 'error' => 'No data to update'];
        }
        
        $old = [
            'title' => $article['title'],
            'status' => $article['status'],
            'featured_type' => $article['featured_type']
        ];
        
        $this->model->update($id, $update);
        
        // Sync partners
        if (isset($data['partner_ids']) && is_array($data['partner_ids'])) {
            $this->model->syncPartners($id, $data['partner_ids']);
        }
        
        $this->audit->log($actorId, 'article_updated', 'article', $id, $old, $update, $ip, $ua);
        
        return ['success' => true];
    }
    
    public function delete(int $id, int $actorId, string $ip, string $ua): array {
        $article = $this->model->findById($id);
        if (!$article) return ['success' => false, 'error' => 'Article not found'];
        
        // Delete gallery files
        $gallery = $this->model->getGallery($id);
        foreach ($gallery as $g) {
            if ($g['media_type'] === 'image' && $g['file_path'] && file_exists(UPLOAD_PATH . $g['file_path'])) {
                unlink(UPLOAD_PATH . $g['file_path']);
            }
        }
        
        // Delete featured image
        if ($article['featured_image'] && file_exists(UPLOAD_PATH . $article['featured_image'])) {
            unlink(UPLOAD_PATH . $article['featured_image']);
        }
        
        $old = ['title' => $article['title'], 'slug' => $article['slug'], 'lang' => $article['lang']];
        
        $this->model->delete($id);
        
        $this->audit->log($actorId, 'article_deleted', 'article', $id, $old, null, $ip, $ua);
        
        return ['success' => true];
    }
    
    public function addGallery(int $articleId, array $files, array $embeds, int $actorId, string $ip): array {
        $results = [];
        
        // Upload images
        if (!empty($files['tmp_name']) && is_array($files['tmp_name'])) {
            foreach ($files['tmp_name'] as $i => $tmp) {
                if (empty($tmp)) continue;
                $file = [
                    'tmp_name' => $tmp,
                    'name'     => $files['name'][$i] ?? 'image.jpg',
                    'size'     => $files['size'][$i] ?? 0,
                    'error'    => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE
                ];
                $up = $this->upload->image($file, 'articles', $actorId, $ip);
                if ($up['success']) {
                    $this->model->addGalleryItem($articleId, [
                        'media_type' => 'image',
                        'file_path'  => $up['path'],
                        'caption_en' => $embeds['caption_en'][$i] ?? null,
                        'caption_fr' => $embeds['caption_fr'][$i] ?? null,
                        'sort_order' => $i
                    ]);
                    $results[] = ['type' => 'image', 'success' => true];
                } else {
                    $results[] = ['type' => 'image', 'success' => false, 'error' => $up['error']];
                }
            }
        }
        
        // Embed URLs
        if (!empty($embeds['urls']) && is_array($embeds['urls'])) {
            foreach ($embeds['urls'] as $i => $url) {
                $url = trim($url);
                if ($url === '') continue;
                $clean = EmbedValidator::validate($url);
                if ($clean) {
                    $this->model->addGalleryItem($articleId, [
                        'media_type' => 'video_embed',
                        'embed_url'  => $clean,
                        'caption_en' => $embeds['caption_en'][$i] ?? null,
                        'caption_fr' => $embeds['caption_fr'][$i] ?? null,
                        'sort_order' => 100 + $i
                    ]);
                    $results[] = ['type' => 'embed', 'success' => true];
                } else {
                    $results[] = ['type' => 'embed', 'success' => false, 'error' => 'Invalid URL'];
                }
            }
        }
        
        return ['success' => true, 'results' => $results];
    }
    
    public function deleteGalleryItem(int $itemId): array {
        $stmt = $this->db->prepare("SELECT * FROM article_gallery WHERE id = ?");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        
        if (!$item) return ['success' => false, 'error' => 'Item not found'];
        
        if ($item['media_type'] === 'image' && $item['file_path'] && file_exists(UPLOAD_PATH . $item['file_path'])) {
            unlink(UPLOAD_PATH . $item['file_path']);
        }
        
        $this->model->deleteGalleryItem($itemId);
        return ['success' => true];
    }

        public function getAllPartners(): array {
        return $this->model->getAllPartners();
    }
}