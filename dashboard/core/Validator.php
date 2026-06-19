<?php
declare(strict_types=1);

class Validator {
    public static function email(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function url(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false 
            && preg_match('#^https?://#i', $url);
    }
    
    public static function slug(string $string): string {
        return preg_replace('/[^a-z0-9-]/', '-', strtolower(trim($string)));
    }
    
    public static function int(mixed $value, ?int $min = null, ?int $max = null): bool {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        if ($int === false) return false;
        if ($min !== null && $int < $min) return false;
        if ($max !== null && $int > $max) return false;
        return true;
    }
    
    public static function clean(string $text): string {
        return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
    }
}