<?php
declare(strict_types=1);

require_once __DIR__ . '/Audit.php';

class Upload {
    private PDO $db;
    private array $allowedImages = ['image/jpeg','image/png','image/gif','image/webp'];
    private array $allowedVideos = ['video/mp4','video/webm'];
    private int $maxImageSize;
    private int $minImageSize;
    private int $maxVideoSize;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->maxImageSize = (int)(IMAGE_MAX_SIZE);
        $this->minImageSize = (int)(IMAGE_MIN_SIZE);
        $this->maxVideoSize = (int)(VIDEO_MAX_SIZE);
    }
    
    public function image(array $file, string $directory, ?int $adminId, string $ip): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload error: ' . $this->uploadError($file['error'])];
        }
        
        if ($file['size'] > $this->maxImageSize) {
            return ['success' => false, 'error' => 'File exceeds ' . ($this->maxImageSize/1024/1024) . 'MB'];
        }
        if ($file['size'] < $this->minImageSize) {
            return ['success' => false, 'error' => 'File smaller than ' . ($this->minImageSize/1024) . 'KB'];
        }
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        if (!in_array($mime, $this->allowedImages, true)) {
            return ['success' => false, 'error' => 'Invalid type. Use JPG, PNG, GIF or WebP'];
        }
        
        // Dimensions check
        $dims = getimagesize($file['tmp_name']);
        if ($dims) {
            [$w, $h] = $dims;
            if ($w < 800 || $h < 600) {
                return ['success' => false, 'error' => "Image too small: {$w}x{$h} (min 800x600)"];
            }
            if ($w > 4000 || $h > 4000) {
                return ['success' => false, 'error' => "Image too large: {$w}x{$h} (max 4000x4000)"];
            }
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === 'jpeg') $ext = 'jpg';
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dirPath = UPLOAD_PATH . $directory;
        $fullPath = $dirPath . '/' . $filename;
        
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }
        
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => false, 'error' => 'Failed to save file'];
        }
        
        chmod($fullPath, 0644);
        
        // ─── EXIF Strip ───
        $this->stripExif($fullPath, $mime);
        
        // ─── WebP Conversion + Thumbnails ───
        $webpPath = $this->convertToWebP($fullPath, $mime, $dirPath);
        $this->generateThumbnails($fullPath, $mime, $dirPath, $filename);
        
        $checksum = md5_file($fullPath);
        
        // Audit témoin
        $audit = new Audit($this->db);
        $audit->log($adminId, 'media_uploaded', 'media', null, null, [
            'file_name'     => $filename,
            'original_name' => $file['name'],
            'file_size'     => $file['size'],
            'mime_type'     => $mime,
            'checksum_md5'  => $checksum,
            'upload_path'   => $directory . '/' . $filename,
            'dimensions'    => ($dims ? "{$w}x{$h}" : 'unknown'),
            'uploaded_by'   => $adminId
        ], $ip, $_SERVER['HTTP_USER_AGENT'] ?? null);
        
        return [
            'success'   => true,
            'path'      => $directory . '/' . $filename,
            'url'       => UPLOAD_URL . $directory . '/' . $filename,
            'webp_url'  => $webpPath ? (UPLOAD_URL . $directory . '/' . basename($webpPath)) : null,
            'checksum'  => $checksum
        ];
    }
    
    public function shortVideo(array $file, string $directory, ?int $adminId, string $ip): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload error'];
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            return ['success' => false, 'error' => 'Short video max 10MB'];
        }
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if ($mime !== 'video/mp4') {
            return ['success' => false, 'error' => 'Short video must be MP4'];
        }
        
        $ext = 'mp4';
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dirPath = UPLOAD_PATH . $directory;
        $fullPath = $dirPath . '/' . $filename;
        
        if (!is_dir($dirPath)) mkdir($dirPath, 0755, true);
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => false, 'error' => 'Failed to save video'];
        }
        chmod($fullPath, 0644);
        
        $checksum = md5_file($fullPath);
        
        $audit = new Audit($this->db);
        $audit->log($adminId, 'media_uploaded', 'media', null, null, [
            'file_name' => $filename, 'mime_type' => $mime, 'checksum_md5' => $checksum,
            'upload_path' => $directory . '/' . $filename
        ], $ip, $_SERVER['HTTP_USER_AGENT'] ?? null);
        
        return ['success' => true, 'path' => $directory . '/' . $filename, 'url' => UPLOAD_URL . $directory . '/' . $filename, 'checksum' => $checksum];
    }
    
    private function stripExif(string $path, string $mime): void {
        if (!function_exists('imagewebp')) return; // GD not available
        
        switch ($mime) {
            case 'image/jpeg':
                $img = imagecreatefromjpeg($path);
                if ($img) {
                    imagejpeg($img, $path, 92);
                    imagedestroy($img);
                }
                break;
            case 'image/png':
                $img = imagecreatefrompng($path);
                if ($img) {
                    imagepng($img, $path, 6);
                    imagedestroy($img);
                }
                break;
        }
    }
    
    private function convertToWebP(string $path, string $mime, string $dir): ?string {
        if (!function_exists('imagewebp')) return null;
        
        $img = null;
        switch ($mime) {
            case 'image/jpeg': $img = imagecreatefromjpeg($path); break;
            case 'image/png':  $img = imagecreatefrompng($path); break;
            case 'image/gif':  $img = imagecreatefromgif($path); break;
        }
        
        if (!$img) return null;
        
        $webpName = pathinfo($path, PATHINFO_FILENAME) . '.webp';
        $webpPath = $dir . '/webp/' . $webpName;
        
        if (!is_dir($dir . '/webp')) mkdir($dir . '/webp', 0755, true);
        
        imagewebp($img, $webpPath, 85);
        imagedestroy($img);
        chmod($webpPath, 0644);
        
        return $webpPath;
    }
    
    private function generateThumbnails(string $path, string $mime, string $dir, string $filename): void {
        if (!function_exists('imagecreatetruecolor')) return;
        
        $img = null;
        switch ($mime) {
            case 'image/jpeg': $img = imagecreatefromjpeg($path); break;
            case 'image/png':  $img = imagecreatefrompng($path); break;
            case 'image/gif':  $img = imagecreatefromgif($path); break;
        }
        if (!$img) return;
        
        $origW = imagesx($img);
        $origH = imagesy($img);
        
        $sizes = ['thumb' => [300,300], 'medium' => [600,400]];
        
        foreach ($sizes as $folder => [$maxW, $maxH]) {
            $ratio = min($maxW / $origW, $maxH / $origH, 1.0);
            $newW = (int)($origW * $ratio);
            $newH = (int)($origH * $ratio);
            
            $thumb = imagecreatetruecolor($newW, $newH);
            if ($mime === 'image/png' || $mime === 'image/gif') {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
            }
            imagecopyresampled($thumb, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            
            $thumbDir = $dir . '/' . $folder;
            if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);
            
            $thumbPath = $thumbDir . '/' . $filename;
            switch ($mime) {
                case 'image/jpeg': imagejpeg($thumb, $thumbPath, 85); break;
                case 'image/png':  imagepng($thumb, $thumbPath, 6); break;
                case 'image/gif':  imagegif($thumb, $thumbPath); break;
            }
            chmod($thumbPath, 0644);
            imagedestroy($thumb);
        }
        
        imagedestroy($img);
    }
    
    private function uploadError(int $code): string {
        return match($code) {
            UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'Partial upload',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            default => 'Error ' . $code
        };
    }
}