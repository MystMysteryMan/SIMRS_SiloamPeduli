<?php
// app/models/LaboratoryModel.php - Model Laboratorium (Modul 8 - SOP/SIMRS/004/2026)

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/AuditLogModel.php';
require_once __DIR__ . '/../services/ClinicalWorkflow.php';

class LaboratoryModel extends Model {
    protected string $table = 'lab_orders';

    /**
     * Generate Nomor Permintaan Lab unik: LAB-YYYYMMDD-XXXX
     */
    public function generateOrderNumber(): string {
        if ($this->db->inTransaction()) $this->query("SELECT pg_advisory_xact_lock(hashtext('SIMRS_LABORATORY_NUMBER'))");
        $prefix = 'LAB-' . date('Ymd') . '-';
        $sql = "SELECT order_number FROM lab_orders WHERE order_number LIKE :prefix ORDER BY id DESC LIMIT 1";
        $row = $this->query($sql, [':prefix' => $prefix . '%']);

        if (!empty($row)) {
            $lastNum = (int)substr($row[0]['order_number'], -4);
            $newNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNum = '0001';
        }

        return $prefix . $newNum;
    }

    /**
     * Buat Order Laboratorium Baru dari EMR / Dokter
     */
    public function createLabOrder(array $data): array {
        $patientId       = (int)($data['patient_id'] ?? 0);
        $appointmentId   = !empty($data['appointment_id']) ? (int)$data['appointment_id'] : null;
        $medicalRecordId = !empty($data['medical_record_id']) ? (int)$data['medical_record_id'] : null;
        $doctorId        = !empty($data['doctor_id']) ? (int)$data['doctor_id'] : ($_SESSION['user_id'] ?? null);
        $priority        = in_array($data['clinical_priority'] ?? '', ['CITO', 'REGULER']) ? $data['clinical_priority'] : 'REGULER';
        $clinicalNotes   = clean($data['clinical_notes'] ?? '');
        $tests           = $data['tests'] ?? []; // Array of test_id or test_name

        if ($patientId <= 0) {
            return ['success' => false, 'message' => 'Identitas Pasien tidak valid untuk order laboratorium.'];
        }

        if (empty($tests)) {
            return ['success' => false, 'message' => 'Pilih minimal satu jenis pemeriksaan laboratorium.'];
        }

        $orderNumber = $this->generateOrderNumber();

        $sql = "
            INSERT INTO lab_orders (
                order_number, patient_id, appointment_id, medical_record_id, doctor_id,
                clinical_priority, status, clinical_notes, created_at
            ) VALUES (
                :order_num, :pid, :aid, :mrid, :doc_id,
                :priority, 'PENDING', :notes, NOW()
            )
        ";
        $orderId = (int)$this->insert($sql, [
            ':order_num' => $orderNumber,
            ':pid'       => $patientId,
            ':aid'       => $appointmentId,
            ':mrid'      => $medicalRecordId,
            ':doc_id'    => $doctorId,
            ':priority'  => $priority,
            ':notes'     => $clinicalNotes
        ]);

        // Insert Order Items
        foreach ($tests as $t) {
            $testId = null;
            $testName = '';

            if (is_numeric($t)) {
                $testId = (int)$t;
                $tRow = $this->query("SELECT test_name FROM lab_tests WHERE id = :tid LIMIT 1", [':tid' => $testId]);
                $testName = $tRow[0]['test_name'] ?? ('Pemeriksaan #' . $testId);
            } elseif (is_string($t) && !empty(trim($t))) {
                $testName = trim($t);
                $tRow = $this->query("SELECT id FROM lab_tests WHERE test_name = :tname LIMIT 1", [':tname' => $testName]);
                if (!empty($tRow)) {
                    $testId = (int)$tRow[0]['id'];
                }
            }

            if (!empty($testName)) {
                $this->execute(
                    "INSERT INTO lab_order_items (lab_order_id, lab_test_id, test_name) VALUES (:oid, :tid, :tname)",
                    [
                        ':oid'   => $orderId,
                        ':tid'   => $testId,
                        ':tname' => $testName
                    ]
                );
            }
        }

        AuditLogModel::log(
            'CREATE_LAB_ORDER',
            'LABORATORY',
            "Penerbitan Order Lab #{$orderNumber} (Prioritas: {$priority}) untuk Pasien ID #{$patientId}",
            $doctorId
        );

        return [
            'success'      => true,
            'message'      => "Order Laboratorium {$orderNumber} berhasil diterbitkan.",
            'order_id'     => $orderId,
            'order_number' => $orderNumber
        ];
    }

    /**
     * Ambil daftar semua order laboratorium (dengan filter status/prioritas)
     */
    public function getAllOrders(?string $status = null, ?string $priority = null, ?string $search = null): array {
        $sql = "
            SELECT lo.*,
                   p.full_name AS patient_name, p.no_rm, p.gender, p.birth_date, p.insurance_type,
                   u_doc.full_name AS doctor_name,
                   u_an.full_name AS analyst_name,
                   (SELECT COUNT(*) FROM lab_order_items WHERE lab_order_id = lo.id) AS item_count
            FROM lab_orders lo
            JOIN patients p ON lo.patient_id = p.id
            LEFT JOIN users u_doc ON lo.doctor_id = u_doc.id
            LEFT JOIN users u_an ON lo.analyst_id = u_an.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($status)) {
            $sql .= " AND lo.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($priority)) {
            $sql .= " AND lo.clinical_priority = :priority";
            $params[':priority'] = $priority;
        }

        if (!empty($search)) {
            $sql .= " AND (lo.order_number LIKE :search OR p.full_name LIKE :search OR p.no_rm LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $sql .= "
            ORDER BY 
                CASE WHEN lo.clinical_priority = 'CITO' AND lo.status IN ('PENDING', 'PROCESS', 'REVIEW', 'VALIDATED') THEN 1 ELSE 2 END,
                lo.created_at DESC
        ";

        return $this->query($sql, $params);
    }

    /**
     * Ambil statistik antrean laboratorium dengan 1 agregasi query SQL
     */
    public function getLabStats(): array {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status = 'PENDING') AS pending,
                COUNT(*) FILTER (WHERE status IN ('PROCESS', 'REVIEW', 'VALIDATED')) AS process,
                COUNT(*) FILTER (WHERE status = 'VALID') AS valid,
                COUNT(*) FILTER (WHERE clinical_priority = 'CITO' AND status IN ('PENDING', 'PROCESS', 'REVIEW', 'VALIDATED')) AS cito
            FROM lab_orders
        ";
        $row = $this->query($sql)[0] ?? [];
        return [
            'total'   => (int)($row['total'] ?? 0),
            'pending' => (int)($row['pending'] ?? 0),
            'process' => (int)($row['process'] ?? 0),
            'valid'   => (int)($row['valid'] ?? 0),
            'cito'    => (int)($row['cito'] ?? 0)
        ];
    }

    /**
     * Ambil daftar order laboratorium dengan pagination
     */
    public function paginateOrders(?string $status = null, ?string $priority = null, ?string $search = null, int $page = 1, int $perPage = 15): array {
        $sql = "
            SELECT lo.*,
                   p.full_name AS patient_name, p.no_rm, p.gender, p.birth_date, p.insurance_type,
                   u_doc.full_name AS doctor_name,
                   u_an.full_name AS analyst_name,
                   (SELECT COUNT(*) FROM lab_order_items WHERE lab_order_id = lo.id) AS item_count
            FROM lab_orders lo
            JOIN patients p ON lo.patient_id = p.id
            LEFT JOIN users u_doc ON lo.doctor_id = u_doc.id
            LEFT JOIN users u_an ON lo.analyst_id = u_an.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($status)) {
            $sql .= " AND lo.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($priority)) {
            $sql .= " AND lo.clinical_priority = :priority";
            $params[':priority'] = $priority;
        }

        if (!empty($search)) {
            $sql .= " AND (lo.order_number ILIKE :search OR p.full_name ILIKE :search OR p.no_rm ILIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $sql .= "
            ORDER BY 
                CASE WHEN lo.clinical_priority = 'CITO' AND lo.status IN ('PENDING', 'PROCESS', 'REVIEW', 'VALIDATED') THEN 1 ELSE 2 END,
                lo.created_at DESC
        ";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Ambil detail lengkap order lab beserta data pasien & dokter
     */
    public function getOrderById(int $orderId): ?array {
        $sql = "
            SELECT lo.*,
                   p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.birth_date, 
                   p.blood_type, p.phone_number, p.insurance_type, p.allergy_notes,
                   u_doc.full_name AS doctor_name,
                   u_an.full_name AS analyst_name,
                   mr.icd10_code, mr.diagnosis_name
            FROM lab_orders lo
            JOIN patients p ON lo.patient_id = p.id
            LEFT JOIN users u_doc ON lo.doctor_id = u_doc.id
            LEFT JOIN users u_an ON lo.analyst_id = u_an.id
            LEFT JOIN medical_records mr ON lo.medical_record_id = mr.id
            WHERE lo.id = :id
            LIMIT 1
        ";
        $res = $this->query($sql, [':id' => $orderId]);
        return $res[0] ?? null;
    }

    /**
     * Ambil item pemeriksaan beserta hasil (jika sudah ada)
     */
    public function getOrderItemsWithResults(int $orderId): array {
        $sql = "
            SELECT loi.*,
                   lt.test_code, lt.tariff,
                   COALESCE(lr.normal_range, lt.normal_range) AS default_normal_range,
                   COALESCE(lr.unit, lt.unit) AS default_unit,
                   lr.id AS result_id,
                   lr.result_value,
                   lr.normal_range AS result_normal_range,
                   lr.unit AS result_unit,
                   lr.result_flag,
                   lr.analyst_notes,
                   lr.validated_at
            FROM lab_order_items loi
            LEFT JOIN lab_tests lt ON loi.lab_test_id = lt.id
            LEFT JOIN lab_results lr ON loi.id = lr.lab_order_item_id
            WHERE loi.lab_order_id = :oid
            ORDER BY loi.id ASC
        ";
        return $this->query($sql, [':oid' => $orderId]);
    }

    /**
     * Analis menerima & memproses order laboratorium (Status: PENDING -> PROCESS)
     */
    private function lockedOrder(int $id, int $actorId, ?int $revision): array {
        ClinicalWorkflow::requireRole(['analis_laboratorium']);
        if ($actorId !== (int)$_SESSION['user_id']) throw new DomainException('Identitas analis tidak sesuai sesi.');
        $row = $this->query('SELECT * FROM lab_orders WHERE id=? FOR UPDATE', [$id])[0] ?? null;
        if (!$row) throw new DomainException('Order laboratorium tidak ditemukan.');
        ClinicalWorkflow::revision($row,$revision);
        ClinicalWorkflow::orderLinks($this->db,$row);
        return $row;
    }
    public function processOrder(int $orderId, int $analystId, ?int $revision = null, string $noRm = ''): array {
        return ClinicalWorkflow::transaction($this->db,function () use ($orderId,$analystId,$revision,$noRm) {
            $order=$this->lockedOrder($orderId,$analystId,$revision);
            if ($order['status'] !== 'PENDING') throw new DomainException('Hanya order menunggu yang dapat mulai diproses.');
            $patient=$this->query('SELECT no_rm FROM patients WHERE id=?',[$order['patient_id']])[0] ?? [];
            if (!hash_equals((string)($patient['no_rm'] ?? ''),trim($noRm)) || trim($noRm)==='') throw new DomainException('Nomor RM tidak cocok. Verifikasi kembali identitas pasien dan pemeriksaan.');
            $this->execute("UPDATE lab_orders SET status='PROCESS',analyst_id=?,revision=revision+1 WHERE id=?",[$analystId,$orderId]);
            ClinicalWorkflow::audit($this->db,'VERIFY_PROCESS_LAB','LABORATORY',"Verifikasi pasien #{$order['patient_id']}; order #$orderId");
            return ['success'=>true,'message'=>'Identitas dan order terverifikasi. Pemeriksaan dalam proses.'];
        });
    }
    public function saveResults(int $orderId, array $resultsData, int $analystId, ?int $revision = null): array {
        return ClinicalWorkflow::transaction($this->db,function () use ($orderId,$resultsData,$analystId,$revision) {
            $order=$this->lockedOrder($orderId,$analystId,$revision);
            if ($order['status'] !== 'PROCESS') throw new DomainException('Input hanya tersedia saat pemeriksaan dalam proses.');
            $items=$this->getOrderItemsWithResults($orderId);
            $ids=array_map('intval',array_column($items,'id')); sort($ids);
            $submitted=array_map('intval',array_keys($resultsData)); sort($submitted);
            if (!$ids || $ids !== $submitted) throw new DomainException('Item hasil harus sesuai seluruh pemeriksaan pada order ini.');
            $prepared=[];
            foreach ($items as $item) {
                $input=$resultsData[$item['id']];
                if (!is_array($input)) throw new DomainException('Format hasil tidak valid.');
                $value=ClinicalWorkflow::text($input['result_value'] ?? '',100);
                if ($value==='') throw new DomainException("Nilai hasil {$item['test_name']} wajib diisi.");
                $unit=strtolower(trim((string)$item['default_unit']));
                $numericUnit=in_array($unit,['g/dl','mg/dl','mmol/l','u/l','iu/l','%','/µl','/μl','/ul','10^3/µl','10^3/ul'],true);
                if ($numericUnit && (!is_numeric($value) || !is_finite((float)$value) || (float)$value < 0)) throw new DomainException('Format nilai hasil pemeriksaan harus berupa angka tidak negatif.');
                $flag=$input['result_flag'] ?? 'NORMAL';
                if (!in_array($flag,['NORMAL','HIGH','LOW','CRITICAL'],true)) throw new DomainException('Flag hasil tidak valid.');
                $prepared[]=['id'=>$item['id'],'value'=>$value,'range'=>ClinicalWorkflow::text($input['normal_range'] ?? $item['default_normal_range'],100),'unit'=>ClinicalWorkflow::text($input['unit'] ?? $item['default_unit'],30),'flag'=>$flag,'notes'=>ClinicalWorkflow::text($input['analyst_notes'] ?? '')];
            }
            foreach ($prepared as $p) {
                $exists=$this->query('SELECT id FROM lab_results WHERE lab_order_item_id=?',[$p['id']]);
                if ($exists) $this->execute('UPDATE lab_results SET result_value=?,normal_range=?,unit=?,result_flag=?,analyst_notes=?,validated_at=NULL WHERE lab_order_item_id=?',[$p['value'],$p['range'],$p['unit'],$p['flag'],$p['notes'],$p['id']]);
                else $this->execute('INSERT INTO lab_results (lab_order_item_id,result_value,normal_range,unit,result_flag,analyst_notes,validated_at) VALUES (?,?,?,?,?,?,NULL)',[$p['id'],$p['value'],$p['range'],$p['unit'],$p['flag'],$p['notes']]);
            }
            $this->execute("UPDATE lab_orders SET status='REVIEW',analyst_id=?,revision=revision+1 WHERE id=?",[$analystId,$orderId]);
            ClinicalWorkflow::audit($this->db,'SAVE_LAB_DRAFT','LABORATORY',"Draf order #$orderId; belum hasil final");
            return ['success'=>true,'message'=>'Draf hasil tersimpan. Tinjau seluruh hasil sebelum validasi.'];
        });
    }
    public function reviewResults(int $orderId, int $analystId, int $revision, bool $approve, string $reason = ''): array {
        return ClinicalWorkflow::transaction($this->db,function () use ($orderId,$analystId,$revision,$approve,$reason) {
            $order=$this->lockedOrder($orderId,$analystId,$revision);
            if ($order['status'] !== 'REVIEW') throw new DomainException('Hasil belum siap ditinjau atau sudah diterbitkan.');
            if ($approve) {
                $items=$this->getOrderItemsWithResults($orderId);
                if (!$items) throw new DomainException('Order tidak memiliki item pemeriksaan.');
                foreach ($items as $item) if (!$item['result_id'] || trim((string)$item['result_value'])==='') throw new DomainException('Seluruh hasil harus terisi sebelum validasi.');
                $this->execute("UPDATE lab_orders SET status='VALIDATED',validated_at=NOW(),validated_by=?,revision=revision+1 WHERE id=?",[$analystId,$orderId]);
            } else {
                $reason=ClinicalWorkflow::text($reason);
                if ($reason==='') throw new DomainException('Alasan perbaikan wajib diisi.');
                $this->execute("UPDATE lab_orders SET status='PROCESS',review_notes=?,revision=revision+1 WHERE id=?",[$reason,$orderId]);
            }
            ClinicalWorkflow::audit($this->db,$approve?'VALIDATE_LAB_RESULTS':'RETURN_LAB_DRAFT','LABORATORY',"Order #$orderId");
            return ['success'=>true,'message'=>$approve?'Hasil dinyatakan valid. Klik Simpan hasil pemeriksaan agar tersedia di EMR.':'Hasil dikembalikan ke formulir input untuk diperbaiki.'];
        });
    }

    public function finalizeResults(int $orderId, int $analystId, int $revision): array {
        return ClinicalWorkflow::transaction($this->db,function () use ($orderId,$analystId,$revision) {
            $order=$this->lockedOrder($orderId,$analystId,$revision);
            if ($order['status'] !== 'VALIDATED') throw new DomainException('Hasil harus divalidasi sebelum disimpan ke rekam medis.');
            $items=$this->getOrderItemsWithResults($orderId);
            if (!$items) throw new DomainException('Order tidak memiliki hasil.');
            foreach ($items as $item) if (!$item['result_id'] || trim((string)$item['result_value'])==='') throw new DomainException('Seluruh hasil harus terisi.');
            $this->execute('UPDATE lab_results SET validated_at=? WHERE lab_order_item_id IN (SELECT id FROM lab_order_items WHERE lab_order_id=?)',[$order['validated_at'],$orderId]);
            $this->execute("UPDATE lab_orders SET status='VALID',completed_at=NOW(),saved_by=?,analyst_id=?,revision=revision+1 WHERE id=?",[$analystId,$analystId,$orderId]);
            ClinicalWorkflow::audit($this->db,'SAVE_LAB_RESULTS','LABORATORY',"Hasil tersimpan untuk pasien #{$order['patient_id']}; order #$orderId");
            return ['success'=>true,'message'=>'Hasil pemeriksaan berhasil disimpan ke rekam medis pasien. Laporan siap dicetak.'];
        });
    }

    /**
     * Ambil riwayat hasil laboratorium untuk riwayat pasien di EMR
     */
    public function getPatientLabHistory(int $patientId): array {
        $orders = $this->query(
            "SELECT lo.*, u_doc.full_name AS doctor_name, u_an.full_name AS analyst_name
             FROM lab_orders lo
             LEFT JOIN users u_doc ON lo.doctor_id = u_doc.id
             LEFT JOIN users u_an ON lo.analyst_id = u_an.id
             WHERE lo.patient_id = :pid
             ORDER BY lo.created_at DESC",
            [':pid' => $patientId]
        );

        if (!empty($orders)) {
            $orderIds = array_column($orders, 'id');
            $pIds = implode(',', array_map('intval', $orderIds));
            $sqlItems = "
                SELECT loi.*, 
                       lr.id AS result_id, lr.result_value, lr.unit AS result_unit, lr.normal_range AS result_normal_range,
                       lr.result_flag, lr.analyst_notes,
                       lt.test_name, lt.unit AS default_unit
                FROM lab_order_items loi
                LEFT JOIN lab_results lr ON loi.id = lr.lab_order_item_id
                LEFT JOIN lab_tests lt ON loi.lab_test_id = lt.id
                WHERE loi.lab_order_id IN ({$pIds})
                ORDER BY loi.id ASC
            ";
            $allItems = $this->query($sqlItems);
            $itemsMap = [];
            foreach ($allItems as $it) {
                $itemsMap[$it['lab_order_id']][] = $it;
            }
            foreach ($orders as &$ord) {
                $ord['items'] = $ord['status'] === 'VALID' ? ($itemsMap[$ord['id']] ?? []) : [];
            }
            unset($ord);
        }

        return $orders;
    }
}

