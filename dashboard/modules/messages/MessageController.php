<?php
declare(strict_types=1);

require_once __DIR__ . '/Message.php';
require_once __DIR__ . '/../../core/Audit.php';

class MessageController {
    private PDO $db;
    private Message $model;
    private Audit $audit;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->model = new Message($db);
        $this->audit = new Audit($db);
    }
    
    public function list(string $status = '', string $type = '', int $page = 1): array {
        return $this->model->findAll($status, $type, $page);
    }
    
    public function get(int $id): ?array {
        return $this->model->findById($id);
    }
    
    public function markRead(int $id, int $actorId, string $ip, string $ua): array {
        $msg = $this->model->findById($id);
        if (!$msg) return ['success' => false, 'error' => 'Not found'];
        $this->model->updateStatus($id, 'read');
        $this->audit->log($actorId, 'message_read', 'message', $id, ['status' => $msg['status']], ['status' => 'read'], $ip, $ua);
        return ['success' => true];
    }
    
    public function assign(int $id, ?int $adminId, int $actorId, string $ip, string $ua): array {
        $msg = $this->model->findById($id);
        if (!$msg) return ['success' => false, 'error' => 'Not found'];
        $old = $msg['assigned_to'];
        $this->model->assign($id, $adminId);
        $this->audit->log($actorId, 'message_assigned', 'message', $id, ['assigned_to' => $old], ['assigned_to' => $adminId], $ip, $ua);
        return ['success' => true];
    }
    
    public function archive(int $id, int $actorId, string $ip, string $ua): array {
        $msg = $this->model->findById($id);
        if (!$msg) return ['success' => false, 'error' => 'Not found'];
        $this->model->updateStatus($id, 'archived');
        $this->audit->log($actorId, 'message_archived', 'message', $id, ['status' => $msg['status']], ['status' => 'archived'], $ip, $ua);
        return ['success' => true];
    }
    
    public function saveNotes(int $id, string $notes, int $actorId, string $ip, string $ua): array {
        $msg = $this->model->findById($id);
        if (!$msg) return ['success' => false, 'error' => 'Not found'];
        $this->model->updateNotes($id, $notes);
        return ['success' => true];
    }
    
    public function delete(int $id, int $actorId, string $ip, string $ua): array {
        $msg = $this->model->findById($id);
        if (!$msg) return ['success' => false, 'error' => 'Not found'];
        $this->model->delete($id);
        $this->audit->log($actorId, 'message_deleted', 'message', $id, ['subject' => $msg['subject']], null, $ip, $ua);
        return ['success' => true];
    }

        public function countByStatus(string $status): int {
        return $this->model->countByStatus($status);
    }
}