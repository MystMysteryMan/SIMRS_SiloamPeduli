<?php
// app/models/PoliclinicModel.php - Modul 16: Master Data Poliklinik & Awalan Antrean

require_once __DIR__ . '/../../core/Model.php';

class PoliclinicModel extends Model {
    protected string $table = 'policlinics';

    public function getAllPoliclinics(?bool $activeOnly = null): array {
        $sql = "SELECT p.*, COUNT(d.id) AS total_doctors 
                FROM policlinics p 
                LEFT JOIN doctors d ON p.id = d.policlinic_id AND d.is_active = TRUE
                WHERE 1=1";
        if ($activeOnly === true) {
            $sql .= " AND p.is_active = TRUE";
        }
        $sql .= " GROUP BY p.id ORDER BY p.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getPoliclinicById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM policlinics WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createPoliclinic(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO policlinics (code, name, queue_prefix, is_active) 
                                    VALUES (:code, :name, :queue_prefix, :is_active)");
        $stmt->execute([
            ':code'         => strtoupper(trim($data['code'])),
            ':name'         => trim($data['name']),
            ':queue_prefix' => strtoupper(trim($data['queue_prefix'])),
            ':is_active'    => $data['is_active'] ?? 1
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updatePoliclinic(int $id, array $data): bool {
        $stmt = $this->db->prepare("UPDATE policlinics SET 
                                    code = :code,
                                    name = :name,
                                    queue_prefix = :queue_prefix,
                                    is_active = :is_active
                                    WHERE id = :id");
        return $stmt->execute([
            ':id'           => $id,
            ':code'         => strtoupper(trim($data['code'])),
            ':name'         => trim($data['name']),
            ':queue_prefix' => strtoupper(trim($data['queue_prefix'])),
            ':is_active'    => $data['is_active'] ?? 1
        ]);
    }

    public function togglePoliclinicActive(int $id): bool {
        $poli = $this->getPoliclinicById($id);
        if (!$poli) return false;
        $newStatus = $poli['is_active'] ? 0 : 1;
        $stmt = $this->db->prepare("UPDATE policlinics SET is_active = :status WHERE id = :id");
        return $stmt->execute([':status' => $newStatus, ':id' => $id]);
    }
}

