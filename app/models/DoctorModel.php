<?php
// app/models/DoctorModel.php - Modul 16: Master Data Dokter & Jadwal Praktek

require_once __DIR__ . '/../../core/Model.php';

class DoctorModel extends Model {
    protected string $table = 'doctors';

    public function getAllDoctors(?int $policlinicId = null, ?bool $activeOnly = null): array {
        $sql = "SELECT d.*, d.full_name AS name, p.name AS policlinic_name, p.queue_prefix 
                FROM doctors d 
                LEFT JOIN policlinics p ON d.policlinic_id = p.id 
                WHERE 1=1";
        $params = [];

        if ($policlinicId !== null && $policlinicId > 0) {
            $sql .= " AND d.policlinic_id = :poli_id";
            $params[':poli_id'] = $policlinicId;
        }

        if ($activeOnly === true) {
            $sql .= " AND d.is_active = TRUE";
        }

        $sql .= " ORDER BY d.full_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getDoctorById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT d.*, d.full_name AS name, p.name AS policlinic_name, p.queue_prefix 
                                    FROM doctors d 
                                    LEFT JOIN policlinics p ON d.policlinic_id = p.id 
                                    WHERE d.id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createDoctor(array $data): int {
        $name = clean($data['full_name'] ?? $data['name'] ?? '');
        $spec = clean($data['specialization'] ?? 'Spesialis Umum');
        $fee = (float)($data['consultation_fee'] ?? 150000);

        $stmt = $this->db->prepare("INSERT INTO doctors (policlinic_id, sip_number, full_name, specialization, consultation_fee, is_active) 
                                    VALUES (:policlinic_id, :sip, :fn, :spec, :fee, :is_active)");
        $stmt->execute([
            ':policlinic_id' => $data['policlinic_id'],
            ':sip'           => clean($data['sip_number'] ?? 'SIP/TEMPORARY'),
            ':fn'            => $name,
            ':spec'          => $spec,
            ':fee'           => $fee,
            ':is_active'     => $data['is_active'] ?? 1
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateDoctor(int $id, array $data): bool {
        $name = clean($data['full_name'] ?? $data['name'] ?? '');
        $spec = clean($data['specialization'] ?? 'Spesialis');

        $stmt = $this->db->prepare("UPDATE doctors SET 
                                    policlinic_id = :policlinic_id,
                                    sip_number = :sip,
                                    full_name = :fn,
                                    specialization = :spec,
                                    is_active = :is_active
                                    WHERE id = :id");
        return $stmt->execute([
            ':id'            => $id,
            ':policlinic_id' => $data['policlinic_id'],
            ':sip'           => clean($data['sip_number'] ?? ''),
            ':fn'            => $name,
            ':spec'          => $spec,
            ':is_active'     => $data['is_active'] ?? 1
        ]);
    }

    public function toggleDoctorActive(int $id): bool {
        $doc = $this->getDoctorById($id);
        if (!$doc) return false;
        $newStatus = $doc['is_active'] ? 0 : 1;
        $stmt = $this->db->prepare("UPDATE doctors SET is_active = :status WHERE id = :id");
        return $stmt->execute([':status' => $newStatus, ':id' => $id]);
    }

    public function getSchedulesByDoctor(int $doctorId): array {
        $stmt = $this->db->prepare("SELECT id, doctor_id, day_name, day_name AS day_of_week, start_time, end_time, quota, quota AS quota_per_day, is_active 
                                    FROM doctor_schedules 
                                    WHERE doctor_id = :doc_id 
                                    ORDER BY CASE day_name 
                                        WHEN 'Senin' THEN 1 
                                        WHEN 'Selasa' THEN 2 
                                        WHEN 'Rabu' THEN 3 
                                        WHEN 'Kamis' THEN 4 
                                        WHEN 'Jumat' THEN 5 
                                        WHEN 'Sabtu' THEN 6 
                                        WHEN 'Minggu' THEN 7 
                                        ELSE 8 
                                    END, start_time ASC");
        $stmt->execute([':doc_id' => $doctorId]);
        return $stmt->fetchAll();
    }

    public function addSchedule(array $data): int {
        $dayName = $data['day_name'] ?? $data['day_of_week'] ?? 'Senin';
        $quota = (int)($data['quota'] ?? $data['quota_per_day'] ?? 20);

        $stmt = $this->db->prepare("INSERT INTO doctor_schedules (doctor_id, day_name, start_time, end_time, quota, is_active) 
                                    VALUES (:doctor_id, :day_name, :start_time, :end_time, :quota, TRUE)");
        $stmt->execute([
            ':doctor_id'   => $data['doctor_id'],
            ':day_name'    => $dayName,
            ':start_time'  => $data['start_time'],
            ':end_time'    => $data['end_time'],
            ':quota'       => $quota
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteSchedule(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM doctor_schedules WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
