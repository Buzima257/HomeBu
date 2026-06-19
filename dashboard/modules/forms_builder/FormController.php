<?php
declare(strict_types=1);

require_once __DIR__ . '/DynamicForm.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Audit.php';

class FormController {
    private PDO $db;
    private DynamicForm $model;
    private Audit $audit;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->model = new DynamicForm($db);
        $this->audit = new Audit($db);
    }
    public function list(): array { return $this->model->findAll(); }
    public function get(int $id): ?array { return $this->model->findById($id); }
    public function getFields(int $id): array { return $this->model->getFields($id); }
    
    public function store(array $data, int $actorId, string $ip, string $ua): array {
        $title = trim($data['title'] ?? '');
        $slug = Validator::slug($data['slug'] ?? $title);
        if ($title === '' || $slug === '') return ['success' => false, 'error' => 'Title and slug required'];
        
        // unique slug
        $existing = $this->db->prepare("SELECT id FROM dynamic_forms WHERE slug = ?")->execute([$slug]) ? $this->db->prepare("SELECT id FROM dynamic_forms WHERE slug = ?")->fetch() : false;
        if ($existing) $slug .= '-' . bin2hex(random_bytes(2));
        
        $id = $this->model->create([
            'title' => $title, 'slug' => $slug, 'type' => $data['type'] ?? 'custom',
            'description' => $data['description'] ?? null, 'status' => $data['status'] ?? 'inactive',
            'success_message_en' => $data['success_message_en'] ?? null,
            'success_message_fr' => $data['success_message_fr'] ?? null,
            'notification_email' => $data['notification_email'] ?? null,
            'created_by' => $actorId
        ]);
        $this->audit->log($actorId, 'form_created', 'dynamic_form', $id, null, ['title' => $title, 'slug' => $slug], $ip, $ua);
        return ['success' => true, 'id' => $id];
    }
    
    public function update(int $id, array $data, int $actorId, string $ip, string $ua): array {
        $f = $this->model->findById($id);
        if (!$f) return ['success' => false, 'error' => 'Not found'];
        $update = [];
        if (isset($data['title'])) $update['title'] = trim($data['title']);
        if (isset($data['status'])) $update['status'] = $data['status'];
        if (isset($data['description'])) $update['description'] = trim($data['description']);
        if (isset($data['success_message_en'])) $update['success_message_en'] = $data['success_message_en'];
        if (isset($data['success_message_fr'])) $update['success_message_fr'] = $data['success_message_fr'];
        if (isset($data['notification_email'])) $update['notification_email'] = $data['notification_email'] ?: null;
        $this->model->update($id, $update);
        $this->audit->log($actorId, 'form_updated', 'dynamic_form', $id, null, $update, $ip, $ua);
        return ['success' => true];
    }
    
    public function addField(int $formId, array $data, int $actorId, string $ip, string $ua): array {
        $name = trim($data['field_name'] ?? '');
        $type = $data['field_type'] ?? '';
        if ($name === '' || !in_array($type, ['text','email','tel','textarea','select','radio','checkbox','file','date','number'])) {
            return ['success' => false, 'error' => 'Field name and valid type required'];
        }
        $machineName = preg_replace('/[^a-z0-9_]/', '_', strtolower($name));
        $options = [];
        if (in_array($type, ['select','radio','checkbox']) && !empty($data['options'])) {
            $options = array_map('trim', explode("\n", $data['options']));
        }
        $id = $this->model->addField($formId, [
            'field_name' => $machineName, 'field_label_en' => $name, 'field_label_fr' => $data['field_label_fr'] ?? null,
            'field_type' => $type, 'is_required' => (int)($data['is_required'] ?? 0),
            'options' => $options, 'sort_order' => (int)($data['sort_order'] ?? 0),
            'placeholder_en' => $data['placeholder_en'] ?? null
        ]);
        $this->audit->log($actorId, 'form_field_added', 'dynamic_form_field', $id, null, ['form_id' => $formId, 'name' => $machineName], $ip, $ua);
        return ['success' => true, 'id' => $id];
    }
    
    public function deleteField(int $fieldId, int $actorId, string $ip, string $ua): array {
        $this->model->deleteField($fieldId);
        $this->audit->log($actorId, 'form_field_deleted', 'dynamic_form_field', $fieldId, null, null, $ip, $ua);
        return ['success' => true];
    }
    
    public function delete(int $id, int $actorId, string $ip, string $ua): array {
        $f = $this->model->findById($id);
        if (!$f) return ['success' => false, 'error' => 'Not found'];
        $this->model->delete($id);
        $this->audit->log($actorId, 'form_deleted', 'dynamic_form', $id, ['title' => $f['title']], null, $ip, $ua);
        return ['success' => true];
    }

        public function getSubmissions(int $formId, string $status, int $page): array {
        return $this->model->getSubmissions($formId, $status, $page);
    }
}