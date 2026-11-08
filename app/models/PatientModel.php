<?php
// app/models/PatientModel.php - Model Pendaftaran & Rekam Medis Pasien (Modul 2 - SOP-002)

require_once __DIR__ . '/../../core/Model.php';

class PatientModel extends Model {
    protected string $table = 'patients';

    private function mapPatient(array $p): array {
        $p['name'] = $p['full_name'] ?? '';
        $p['phone'] = $p['phone_number'] ?? '';
        return $p;
    }

    public function paginatePatients(string $keyword = '', int $page = 1, int $perPage = 15): array {
        $sql = "SELECT * FROM patients WHERE 1=1";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (no_rm ILIKE :s OR nik ILIKE :s OR full_name ILIKE :s OR phone_number ILIKE :s)";
            $params[':s'] = "%{$keyword}%";
        }

        $sql .= " ORDER BY id DESC";
        $result = $this->paginate($sql, $params, $page, $perPage);
        $result['data'] = array_map([$this, 'mapPatient'], $result['data']);
        return $result;
    }

    public function searchPatients(string $keyword = '', int $limit = 50): array {
        $sql = "SELECT * FROM patients WHERE 1=1";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (no_rm LIKE :s OR nik LIKE :s OR full_name LIKE :s OR phone_number LIKE :s)";
            $params[':s'] = "%{$keyword}%";
        }

        $sql .= " ORDER BY id DESC LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        return array_map([$this, 'mapPatient'], $rows);
    }

    public function getPatientById(int $id): ?array {
        $row = $this->findBy('id', $id);
        return $row ? $this->mapPatient($row) : null;
    }

    public function isNikRegistered(string $nik, int $excludeId = 0): bool {
        $sql = "SELECT id FROM patients WHERE nik = :nik";
        $params = [':nik' => trim($nik)];
        if ($excludeId > 0) {
            $sql .= " AND id != :exId";
            $params[':exId'] = $excludeId;
        }
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    public function generateNextRM(): string {
        $stmtPref = $this->db->prepare("SELECT config_value FROM system_configurations WHERE config_key = 'rm_prefix' LIMIT 1");
        $stmtPref->execute();
        $prefix = $stmtPref->fetchColumn() ?: 'RM';
        $ym = date('Ym');

        $stmtMax = $this->db->query("SELECT MAX(id) FROM patients");
        $maxId = (int)$stmtMax->fetchColumn();
        $counter = str_pad((string)($maxId + 1), 4, '0', STR_PAD_LEFT);

        return sprintf('%s-%s-%s', $prefix, $ym, $counter);
    }

    public function createPatient(array $data): int {
        $noRM = $this->generateNextRM();
        $fullName = clean($data['full_name'] ?? $data['name'] ?? '');
        $phone = clean($data['phone_number'] ?? $data['phone'] ?? '');
        $insType = strtoupper(trim($data['insurance_type'] ?? 'UMUM'));
        if (str_contains($insType, 'ASURANSI')) {
            $insType = 'ASURANSI';
        } elseif (str_contains($insType, 'BPJS')) {
            $insType = 'BPJS';
        } else {
            $insType = 'UMUM';
        }

        $stmt = $this->db->prepare("
            INSERT INTO patients (no_rm, nik, full_name, gender, birth_date, blood_type, phone_number, email, address, allergy_notes, insurance_type, insurance_number)
            VALUES (:rm, :nik, :fn, :g, :bd, :bt, :p, :em, :addr, :al, :it, :in)
        ");
        $stmt->execute([
            ':rm'   => $noRM,
            ':nik'  => trim($data['nik']),
            ':fn'   => $fullName,
            ':g'    => $data['gender'],
            ':bd'   => $data['birth_date'],
            ':bt'   => !empty($data['blood_type']) ? $data['blood_type'] : '-',
            ':p'    => $phone,
            ':em'   => clean($data['email'] ?? ''),
            ':addr' => clean($data['address']),
            ':al'   => clean($data['allergy_notes'] ?? 'Tidak ada'),
            ':it'   => $insType,
            ':in'   => clean($data['insurance_number'] ?? '')
        ]);
        return $this->lastInsertId();
    }

    public function updatePatient(int $id, array $data): bool {
        $fullName = clean($data['full_name'] ?? $data['name'] ?? '');
        $phone = clean($data['phone_number'] ?? $data['phone'] ?? '');
        $insType = strtoupper(trim($data['insurance_type'] ?? 'UMUM'));
        if (str_contains($insType, 'ASURANSI')) {
            $insType = 'ASURANSI';
        } elseif (str_contains($insType, 'BPJS')) {
            $insType = 'BPJS';
        } else {
            $insType = 'UMUM';
        }

        $stmt = $this->db->prepare("
            UPDATE patients
            SET full_name = :fn, gender = :g, birth_date = :bd, blood_type = :bt,
                phone_number = :p, email = :em, address = :addr, allergy_notes = :al,
                insurance_type = :it, insurance_number = :in, updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            ':fn'   => $fullName,
            ':g'    => $data['gender'],
            ':bd'   => $data['birth_date'],
            ':bt'   => !empty($data['blood_type']) ? $data['blood_type'] : '-',
            ':p'    => $phone,
            ':em'   => clean($data['email'] ?? ''),
            ':addr' => clean($data['address']),
            ':al'   => clean($data['allergy_notes'] ?? 'Tidak ada'),
            ':it'   => $insType,
            ':in'   => clean($data['insurance_number'] ?? ''),
            ':id'   => $id
        ]);
    }
}
