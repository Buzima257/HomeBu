<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/StaticContent.php';
require_once __DIR__ . '/../../core/Upload.php';
require_once __DIR__ . '/../../core/Audit.php';

$auth = new Auth($db);
$auth->requireRole(['super_admin','admin']);

if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
    header('Location: content_form.php?error=Invalid token');
    exit;
}

$model = new StaticContent($db);
$upload = new Upload($db);
$audit = new Audit($db);
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$actorId = $auth->id();

$action = $_POST['action'] ?? '';

if ($action === 'update_all') {
    $content = $_POST['content'] ?? [];
    
    foreach ($content as $key => $value) {
        $model->update($key, ['value_en' => $value, 'value_fr' => $value], $actorId);
    }
    
    // Hero media
    if (!empty($_FILES['hero_media_file']['tmp_name'])) {
        $type = $_POST['hero_media_type'] ?? 'image';
        if ($type === 'image') {
            $up = $upload->image($_FILES['hero_media_file'], 'static', $actorId, $ip);
        } elseif ($type === 'short_video') {
            $up = $upload->shortVideo($_FILES['hero_media_file'], 'short-videos', $actorId, $ip);
        }
        if (!empty($up['success'])) {
            $model->update('hero_media', ['media_path' => $up['path'], 'media_type' => $type], $actorId);
        }
    } elseif (!empty($_POST['hero_media_url'])) {
        require_once __DIR__ . '/../../core/EmbedValidator.php';
        $clean = EmbedValidator::validate($_POST['hero_media_url']);
        if ($clean) {
            $model->update('hero_media', ['media_path' => $clean, 'media_type' => 'video_embed'], $actorId);
        }
    }
    
    $audit->log($actorId, 'static_content_updated', 'static_content', null, null, ['keys' => array_keys($content)], $ip, $ua);
    
    header('Location: content_form.php?success=Content saved');
    exit;
}

header('Location: content_form.php?error=Unknown action');
exit;