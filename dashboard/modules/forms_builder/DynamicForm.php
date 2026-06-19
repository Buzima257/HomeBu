<?php
declare(strict_types=1);

class DynamicForm {
    private PDO $db;
    public function __construct(PDO $db) { $this->db = $db; }
    
    public function findAll(): array {
        $stmt = $this->db->query("SELECT * FROM dynamic_forms ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM dynamic_forms WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    public function create(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO dynamic_forms (title, slug, type, description, status, success_message_en, success_message_fr, notification_email, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['title'], $data['slug'], $data['type'] ?? 'custom', $data['description'] ?? null,
            $data['status'] ?? 'inactive', $data['success_message_en'] ?? null, $data['success_message_fr'] ?? null,
            $data['notification_email'] ?? null, $data['created_by'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }
    public function update(int $id, array $data): bool {
        $fields = []; $values = [];
        foreach (['title','slug','type','description','status','success_message_en','success_message_fr','notification_email'] as $k) {
            if (array_key_exists($k, $data)) { $fields[] = "$k = ?"; $values[] = $data[$k]; }
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $stmt = $this->db->prepare("UPDATE dynamic_forms SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM dynamic_forms WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // Fields
    public function getFields(int $formId): array {
        $stmt = $this->db->prepare("SELECT * FROM dynamic_form_fields WHERE form_id = ? ORDER BY sort_order, id");
        $stmt->execute([$formId]);
        return $stmt->fetchAll();
    }
    public function addField(int $formId, array $data): int {
        $stmt = $this->db->prepare("INSERT INTO dynamic_form_fields (form_id, field_name, field_label_en, field_label_fr, field_type, is_required, options, validation_rules, placeholder_en, placeholder_fr, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $formId, $data['field_name'], $data['field_label_en'] ?? null, $data['field_label_fr'] ?? null,
            $data['field_type'], $data['is_required'] ?? 0,
            isset($data['options']) ? json_encode($data['options']) : null,
            isset($data['validation_rules']) ? json_encode($data['validation_rules']) : null,
            $data['placeholder_en'] ?? null, $data['placeholder_fr'] ?? null, $data['sort_order'] ?? 0
        ]);
        return (int)$this->db->lastInsertId();
    }
    public function deleteField(int $fieldId): bool {
        $stmt = $this->db->prepare("DELETE FROM dynamic_form_fields WHERE id = ?");
        return $stmt->execute([$fieldId]);
    }
    
    // Submissions
    public function getSubmissions(int $formId, string $status = '', int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        $where = $status ? "AND status = ?" : "";
        $params = $status ? [$formId, $status, $perPage, $offset] : [$formId, $perPage, $offset];
        $stmt = $this->db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM dynamic_form_submissions WHERE form_id = ? $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $total = (int)$this->db->query("SELECT FOUND_ROWS()")->fetchColumn();
        return ['rows' => $rows, 'total' => $total, 'pages' => (int)ceil($total / $perPage)];
    }
    public function findSubmissionById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM dynamic_form_submissions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    public function updateSubmissionStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("UPDATE dynamic_form_submissions SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
}