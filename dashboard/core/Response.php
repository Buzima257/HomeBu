<?php
declare(strict_types=1);

class Response {
    public static function json(array $data, int $status = 200): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit;
    }
    
    public static function error(string $message, int $status = 400): never {
        self::json(['success' => false, 'error' => $message], $status);
    }
    
    public static function success(?array $data = null, string $message = 'Success'): never {
        self::json(['success' => true, 'message' => $message, 'data' => $data]);
    }
}