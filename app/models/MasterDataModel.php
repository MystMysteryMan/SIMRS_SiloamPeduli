<?php
// app/models/MasterDataModel.php - Modul 16: Master Ruangan, Bed, Tarif, ICD-10, Obat, Lab & Konfigurasi

require_once __DIR__ . '/../../core/Model.php';

class MasterDataModel extends Model {

    // ==========================================
    // 1. RUANGAN & TEMPAT TIDUR
    // ==========================================
    public function getAllRooms(): array {
        $stmt = $this->db->prepare("SELECT r.*, r.room_name AS name, r.class_type AS class, r.daily_rate AS price,
                                    COUNT(b.id) AS total_beds_count,
                                    SUM(CASE WHEN b.status = 'TERSEDIA' THEN 1 ELSE 0 END) AS available_beds
                                    FROM rooms r
                                    LEFT JOIN beds b ON r.id = b.room_id
                                    GROUP BY r.id
                                    ORDER BY r.class_type ASC, r.room_name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createRoom(array $data): int {
        $class = strtoupper(str_replace(' ', '_', $data['class'] ?? $data['class_type'] ?? 'KELAS_1'));
        if (!in_array($class, ['VVIP', 'VIP', 'KELAS_1', 'KELAS_2', 'KELAS_3', 'ICU', 'ISOLASI'])) {
            $class = 'KELAS_1';
        }

        $stmt = $this->db->prepare("INSERT INTO rooms (room_code, room_name, class_type, daily_rate, is_active) 
                                    VALUES (:rc, :rn, :ct, :dr, :act)");
        $stmt->execute([
            ':rc'  => strtoupper(trim($data['room_code'])),
            ':rn'  => clean($data['room_name'] ?? $data['name'] ?? ''),
            ':ct'  => $class,
            ':dr'  => (float)($data['daily_rate'] ?? $data['price'] ?? 350000),
            ':act' => $data['is_active'] ?? 1
        ]);
        $roomId = (int)$this->db->lastInsertId();

        // Auto-generate beds
        $total = (int)($data['total_beds'] ?? 2);
        for ($i = 1; $i <= $total; $i++) {
            $bedNumber = sprintf('BED-%s-%02d', strtoupper(trim($data['room_code'])), $i);
            $bedStmt = $this->db->prepare("INSERT INTO beds (room_id, bed_number, status) VALUES (:room_id, :bed_number, 'TERSEDIA')");
            $bedStmt->execute([':room_id' => $roomId, ':bed_number' => $bedNumber]);
        }

        return $roomId;
    }

    public function getBedsByRoom(int $roomId): array {
        $stmt = $this->db->prepare("SELECT id, room_id, bed_number, status FROM beds WHERE room_id = :room_id ORDER BY bed_number ASC");
        $stmt->execute([':room_id' => $roomId]);
        return $stmt->fetchAll();
    }

    public function updateBedStatus(int $bedId, string $status): bool {
        $stmt = $this->db->prepare("UPDATE beds SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => strtoupper($status), ':id' => $bedId]);
    }

    // ==========================================
    // 2. TARIF LAYANAN
    // ==========================================
    public function getAllTariffs(?string $category = null): array {
        $sql = "SELECT id, code, name, name AS service_name, category, amount, amount AS price, description, is_active 
                FROM tariffs WHERE 1=1";
        $params = [];
        if (!empty($category)) {
            $sql .= " AND category = :cat";
            $params[':cat'] = strtoupper($category);
        }
        $sql .= " ORDER BY category ASC, name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createTariff(array $data): int {
        $cat = strtoupper(trim($data['category'] ?? 'TINDAKAN'));
        if (!in_array($cat, ['KONSULTASI', 'TINDAKAN', 'LABORATORIUM', 'RADIOLOGI', 'ADMINISTRASI'])) {
            $cat = 'TINDAKAN';
        }

        $stmt = $this->db->prepare("INSERT INTO tariffs (code, name, category, amount, description, is_active) 
                                    VALUES (:c, :n, :cat, :amt, :d, :act)");
        $stmt->execute([
            ':c'   => strtoupper(trim($data['code'])),
            ':n'   => clean($data['name'] ?? $data['service_name'] ?? ''),
            ':cat' => $cat,
            ':amt' => (float)($data['amount'] ?? $data['price'] ?? 0),
            ':d'   => clean($data['description'] ?? ''),
            ':act' => $data['is_active'] ?? 1
        ]);
        return (int)$this->db->lastInsertId();
    }

    // ==========================================
    // 3. ICD-10 DIAGNOSA
    // ==========================================
    public function getAllIcd(string $search = '', int $limit = 100): array {
        $sql = "SELECT * FROM icd_diagnoses WHERE 1=1";
        $params = [];
        if (!empty($search)) {
            $sql .= " AND (code LIKE :q OR description_id LIKE :q OR description_en LIKE :q)";
            $params[':q'] = "%{$search}%";
        }
        $sql .= " ORDER BY code ASC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createIcd(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO icd_diagnoses (code, description_id, description_en, is_active) 
                                    VALUES (:code, :did, :den, :act)");
        $stmt->execute([
            ':code' => strtoupper(trim($data['code'])),
            ':did'  => clean($data['description_id']),
            ':den'  => clean($data['description_en'] ?? ''),
            ':act'  => $data['is_active'] ?? 1
        ]);
        return (int)$this->db->lastInsertId();
    }

    // ==========================================
    // 4. MASTER OBAT & FARMASI
    // ==========================================
    public function getAllMedicines(string $search = ''): array {
        $sql = "SELECT id, medicine_code, medicine_code AS code, name, category, unit, stock, unit_price, expiry_date, is_active 
                FROM medicines WHERE 1=1";
        $params = [];
        if (!empty($search)) {
            $sql .= " AND (medicine_code LIKE :q OR name LIKE :q OR category LIKE :q)";
            $params[':q'] = "%{$search}%";
        }
        $sql .= " ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function paginateMedicines(string $search = '', int $page = 1, int $perPage = 15): array {
        $sql = "SELECT id, medicine_code, medicine_code AS code, name, category, unit, stock, unit_price, expiry_date, is_active 
                FROM medicines WHERE 1=1";
        $params = [];
        if (!empty($search)) {
            $sql .= " AND (medicine_code ILIKE :q OR name ILIKE :q OR category ILIKE :q)";
            $params[':q'] = "%{$search}%";
        }
        $sql .= " ORDER BY name ASC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function createMedicine(array $data): int {
        $exp = !empty($data['expiry_date']) ? $data['expiry_date'] : date('Y-m-d', strtotime('+2 years'));
        $stmt = $this->db->prepare("INSERT INTO medicines (medicine_code, name, category, unit, stock, unit_price, expiry_date, is_active) 
                                    VALUES (:mc, :n, :cat, :u, :st, :up, :exp, :act)");
        $stmt->execute([
            ':mc'  => strtoupper(trim($data['medicine_code'] ?? $data['code'] ?? '')),
            ':n'   => clean($data['name']),
            ':cat' => clean($data['category']),
            ':u'   => clean($data['unit']),
            ':st'  => (int)($data['stock'] ?? 0),
            ':up'  => (float)($data['unit_price'] ?? 0),
            ':exp' => $exp,
            ':act' => $data['is_active'] ?? 1
        ]);
        return (int)$this->db->lastInsertId();
    }

    // ==========================================
    // 5. MASTER PEMERIKSAAN LAB
    // ==========================================
    public function getAllLabTests(): array {
        $stmt = $this->db->prepare("SELECT id, test_code, test_code AS code, test_name, test_name AS name, normal_range, unit, tariff, tariff AS price, is_active 
                                    FROM lab_tests ORDER BY test_name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createLabTest(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO lab_tests (test_code, test_name, normal_range, unit, tariff, is_active) 
                                    VALUES (:tc, :tn, :nr, :u, :tar, :act)");
        $stmt->execute([
            ':tc'  => strtoupper(trim($data['test_code'] ?? $data['code'] ?? '')),
            ':tn'  => clean($data['test_name'] ?? $data['name'] ?? ''),
            ':nr'  => clean($data['normal_range'] ?? ''),
            ':u'   => clean($data['unit'] ?? ''),
            ':tar' => (float)($data['tariff'] ?? $data['price'] ?? 0),
            ':act' => $data['is_active'] ?? 1
        ]);
        return (int)$this->db->lastInsertId();
    }

    // ==========================================
    // 6. KONFIGURASI SISTEM
    // ==========================================
    public function getAllConfigurations(): array {
        $stmt = $this->db->prepare("SELECT * FROM system_configurations ORDER BY config_key ASC");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $configs = [];
        foreach ($rows as $row) {
            $configs[$row['config_key']] = $row;
        }
        return $configs;
    }

    public function getConfigValue(string $key, string $default = ''): string {
        $stmt = $this->db->prepare("SELECT config_value FROM system_configurations WHERE config_key = :k");
        $stmt->execute([':k' => $key]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? (string)$val : $default;
    }

    public function updateConfig(string $key, string $value): bool {
        $stmt = $this->db->prepare("INSERT INTO system_configurations (config_key, config_value, updated_at) 
                                    VALUES (:k, :v, NOW()) 
                                    ON CONFLICT (config_key) DO UPDATE SET config_value = :v2, updated_at = NOW()");
        return $stmt->execute([':k' => $key, ':v' => $value, ':v2' => $value]);
    }

    public function getAllHospitalUnits(bool $activeOnly = true): array {
        $sql = "SELECT * FROM hospital_units";
        if ($activeOnly) {
            $sql .= " WHERE is_active = true";
        }
        $sql .= " ORDER BY category ASC, unit_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createHospitalUnit(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO hospital_units (unit_code, unit_name, category, is_active, created_at)
            VALUES (:code, :name, :cat, :act, NOW())
        ");
        $stmt->execute([
            ':code' => clean($data['unit_code']),
            ':name' => clean($data['unit_name']),
            ':cat'  => clean($data['category'] ?? 'Klinis'),
            ':act'  => $data['is_active'] ?? true
        ]);
        return (int)$this->db->lastInsertId();
    }
}
