<?php
declare(strict_types=1);

class EmbedValidator {
    private static array $patterns = [
        'youtube.com'  => ['#(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]+)#', 'https://www.youtube.com/embed/'],
        'youtu.be'     => ['#youtu\.be/([a-zA-Z0-9_-]+)#', 'https://www.youtube.com/embed/'],
        'vimeo.com'    => ['#vimeo\.com/(\d+)#', 'https://player.vimeo.com/video/'],
    ];
    
    public static function validate(string $url): string|false {
        $parsed = parse_url($url);
        if (!isset($parsed['host'])) return false;
        
        $host = strtolower(str_replace('www.', '', $parsed['host']));
        
        if (!isset(self::$patterns[$host])) return false;
        
        [$pattern, $embedBase] = self::$patterns[$host];
        
        if (preg_match($pattern, $url, $matches)) {
            return $embedBase . $matches[1];
        }
        
        return false;
    }
}