<?php
// app/models/RadiologyModel.php - Model Radiologi (Modul 9 - SOP/SIMRS/005/2026)

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/AuditLogModel.php';
require_once __DIR__ . '/../services/ClinicalWorkflow.php';

class RadiologyModel extends Model {
    protected string $table = 'radiology_orders';

    /**
     * Generate Nomor Permintaan Radiologi unik: RAD-YYYYMMDD-XXXX
     */
    public function generateOrderNumber(): string {
        if ($this->db->inTransaction()) $this->query("SELECT pg_advisory_xact_lock(hashtext('SIMRS_RADIOLOGY_NUMBER'))");
        $prefix = 'RAD-' . date('Ymd') . '-';
        $sql = "SELECT order_number FROM radiology_orders WHERE order_number LIKE :prefix ORDER BY id DESC LIMIT 1";
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
     * Buat Order Radiologi Baru dari EMR / Dokter
     */
    public function createRadiologyOrder(array $data): array {
        $patientId           = (int)($data['patient_id'] ?? 0);
        $appointmentId       = !empty($data['appointment_id']) ? (int)$data['appointment_id'] : null;
        $medicalRecordId     = !empty($data['medical_record_id']) ? (int)$data['medical_record_id'] : null;
        $doctorId            = !empty($data['doctor_id']) ? (int)$data['doctor_id'] : ($_SESSION['user_id'] ?? null);
        $modalityType        = clean($data['modality_type'] ?? 'X-RAY Thorax AP/PA');
        $clinicalIndication  = clean($data['clinical_indication'] ?? '');
        $priority            = in_array($data['clinical_priority'] ?? '', ['CITO', 'REGULER']) ? $data['clinical_priority'] : 'REGULER';

        if ($patientId <= 0) {
            return ['success' => false, 'message' => 'Identitas Pasien tidak valid untuk order radiologi.'];
        }

        if (empty($modalityType)) {
            return ['success' => false, 'message' => 'Pilih jenis modalitas / tindakan radiologi.'];
        }

        if (empty($clinicalIndication)) {
            return ['success' => false, 'message' => 'Indikasi klinis / kecurigaan radiologi wajib diisi.'];
        }

        $orderNumber = $this->generateOrderNumber();

        $sql = "
            INSERT INTO radiology_orders (
                order_number, patient_id, appointment_id, medical_record_id, doctor_id,
                modality_type, clinical_indication, clinical_priority, status, created_at
            ) VALUES (
                :order_num, :pid, :aid, :mrid, :doc_id,
                :modality, :indication, :priority, 'PENDING', NOW()
            )
        ";

        $orderId = (int)$this->insert($sql, [
            ':order_num'   => $orderNumber,
            ':pid'         => $patientId,
            ':aid'         => $appointmentId,
            ':mrid'        => $medicalRecordId,
            ':doc_id'      => $doctorId,
            ':modality'    => $modalityType,
            ':indication'  => $clinicalIndication,
            ':priority'    => $priority
        ]);

        AuditLogModel::log(
            'CREATE_RADIOLOGY_ORDER',
            'RADIOLOGY',
            "Penerbitan Order Radiologi #{$orderNumber} ({$modalityType}, Prioritas: {$priority}) untuk Pasien ID #{$patientId}",
            $doctorId
        );

        return [
            'success'      => true,
            'message'      => "Order Radiologi {$orderNumber} berhasil diterbitkan.",
            'order_id'     => $orderId,
            'order_number' => $orderNumber
        ];
    }

    /**
     * Ambil daftar semua order radiologi dengan filter
     */
    public function getAllOrders(?string $status = null, ?string $priority = null, ?string $search = null): array {
        $sql = "
            SELECT ro.*,
                   p.full_name AS patient_name, p.no_rm, p.gender, p.birth_date, p.insurance_type,
                   u_doc.full_name AS doctor_name,
                   u_rad.full_name AS radiographer_name,
                   u_sp.full_name AS radiologist_name,
                   rr.id AS result_id,
                   rr.image_path,
                   rr.image_filename,
                   rr.validated_at AS result_validated_at
            FROM radiology_orders ro
            JOIN patients p ON ro.patient_id = p.id
            LEFT JOIN users u_doc ON ro.doctor_id = u_doc.id
            LEFT JOIN users u_rad ON ro.radiographer_id = u_rad.id
            LEFT JOIN users u_sp ON ro.radiologist_id = u_sp.id
            LEFT JOIN radiology_results rr ON ro.id = rr.radiology_order_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($status)) {
            $sql .= " AND ro.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($priority)) {
            $sql .= " AND ro.clinical_priority = :priority";
            $params[':priority'] = $priority;
        }

        if (!empty($search)) {
            $sql .= " AND (ro.order_number LIKE :search OR p.full_name LIKE :search OR p.no_rm LIKE :search OR ro.modality_type LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $sql .= "
            ORDER BY 
                CASE WHEN ro.clinical_priority = 'CITO' AND ro.status IN ('PENDING', 'PROCESS', 'REVIEW') THEN 1 ELSE 2 END,
                ro.created_at DESC
        ";

        return $this->query($sql, $params);
    }

    /**
     * Ambil statistik antrean radiologi dengan 1 agregasi query SQL
     */
    public function getRadiologyStats(): array {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status = 'PENDING') AS pending,
                COUNT(*) FILTER (WHERE status IN ('PROCESS', 'REVIEW')) AS process,
                COUNT(*) FILTER (WHERE status = 'VALID') AS valid,
                COUNT(*) FILTER (WHERE clinical_priority = 'CITO' AND status IN ('PENDING', 'PROCESS', 'REVIEW')) AS cito
            FROM radiology_orders
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
     * Ambil daftar order radiologi dengan pagination
     */
    public function paginateOrders(?string $status = null, ?string $priority = null, ?string $search = null, int $page = 1, int $perPage = 15): array {
        $sql = "
            SELECT ro.*,
                   p.full_name AS patient_name, p.no_rm, p.gender, p.birth_date, p.insurance_type,
                   u_doc.full_name AS doctor_name,
                   u_rad.full_name AS radiographer_name,
                   u_sp.full_name AS radiologist_name,
                   rr.id AS result_id,
                   rr.image_path,
                   rr.expertise_findings
            FROM radiology_orders ro
            JOIN patients p ON ro.patient_id = p.id
            LEFT JOIN users u_doc ON ro.doctor_id = u_doc.id
            LEFT JOIN users u_rad ON ro.radiographer_id = u_rad.id
            LEFT JOIN users u_sp ON ro.radiologist_id = u_sp.id
            LEFT JOIN radiology_results rr ON ro.id = rr.radiology_order_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($status)) {
            $sql .= " AND ro.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($priority)) {
            $sql .= " AND ro.clinical_priority = :priority";
            $params[':priority'] = $priority;
        }

        if (!empty($search)) {
            $sql .= " AND (ro.order_number ILIKE :search OR p.full_name ILIKE :search OR p.no_rm ILIKE :search OR ro.modality_type ILIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $sql .= "
            ORDER BY 
                CASE WHEN ro.clinical_priority = 'CITO' AND ro.status IN ('PENDING', 'PROCESS', 'REVIEW') THEN 1 ELSE 2 END,
                ro.created_at DESC
        ";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Ambil detail lengkap order radiologi beserta hasil foto dan ekspertise
     */
    public function getOrderById(int $orderId): ?array {
        $sql = "
            SELECT ro.*,
                   p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.birth_date, 
                   p.blood_type, p.phone_number, p.insurance_type, p.allergy_notes,
                   u_doc.full_name AS doctor_name,
                   u_rad.full_name AS radiographer_name,
                   u_sp.full_name AS radiologist_name,
                   mr.icd10_code, mr.diagnosis_name,
                   rr.id AS result_id,
                   rr.image_path,
                   rr.image_filename,
                   rr.technical_notes,
                   rr.expertise_findings,
                   rr.expertise_conclusion,
                   rr.uploaded_at,
                   rr.validated_at
            FROM radiology_orders ro
            JOIN patients p ON ro.patient_id = p.id
            LEFT JOIN users u_doc ON ro.doctor_id = u_doc.id
            LEFT JOIN users u_rad ON ro.radiographer_id = u_rad.id
            LEFT JOIN users u_sp ON ro.radiologist_id = u_sp.id
            LEFT JOIN medical_records mr ON ro.medical_record_id = mr.id
            LEFT JOIN radiology_results rr ON ro.id = rr.radiology_order_id
            WHERE ro.id = :id
            LIMIT 1
        ";
        $res = $this->query($sql, [':id' => $orderId]);
        return $res[0] ?? null;
    }

    /**
     * Radiografer memulai pemrosesan order (PENDING -> PROCESS)
     */
    private function lockedOrder(int $id, int $actorId, ?int $revision, array $roles): array {
        ClinicalWorkflow::requireRole($roles);
        if ($actorId !== (int)$_SESSION['user_id']) throw new DomainException('Identitas pengguna tidak sesuai sesi.');
        $row=$this->query('SELECT * FROM radiology_orders WHERE id=? FOR UPDATE',[$id])[0] ?? null;
        if (!$row) throw new DomainException('Order radiologi tidak ditemukan.');
        ClinicalWorkflow::revision($row,$revision);
        ClinicalWorkflow::orderLinks($this->db,$row);
        return $row;
    }
    public function processOrder(int $orderId, int $radiographerId, ?int $revision = null, string $noRm = '', string $schedule = ''): array {
        return ClinicalWorkflow::transaction($this->db,function () use ($orderId,$radiographerId,$revision,$noRm,$schedule) {
            $order=$this->lockedOrder($orderId,$radiographerId,$revision,['radiografer']);
            if ($order['status']!=='PENDING') throw new DomainException('Hanya order menunggu yang dapat mulai diproses.');
            $patient=$this->query('SELECT no_rm FROM patients WHERE id=?',[$order['patient_id']])[0] ?? [];
            if (trim($noRm)==='' || !hash_equals((string)($patient['no_rm'] ?? ''),trim($noRm))) throw new DomainException('Nomor RM tidak cocok. Verifikasi kembali pasien dan pemeriksaan.');
            $date=$schedule!=='' ? DateTime::createFromFormat('!Y-m-d\TH:i',$schedule) : null;
            if ($schedule!=='' && (!$date || $date->format('Y-m-d\TH:i')!==$schedule)) throw new DomainException('Jadwal pemeriksaan tidak valid.');
            $this->execute("UPDATE radiology_orders SET status='PROCESS',radiographer_id=?,scheduled_at=?,revision=revision+1 WHERE id=?",[$radiographerId,$date?$date->format('Y-m-d H:i:s'):null,$orderId]);
            ClinicalWorkflow::audit($this->db,'VERIFY_PROCESS_RADIOLOGY','RADIOLOGY',"Verifikasi pasien #{$order['patient_id']}; order #$orderId");
            return ['success'=>true,'message'=>'Pasien dan order terverifikasi. Pemeriksaan radiologi dalam proses.'];
        });
    }
    public function uploadImage(int $orderId, array $file, string $technicalNotes, int $radiographerId, ?int $revision = null): array {
        return ClinicalWorkflow::transaction($this->db,function () use ($orderId,$file,$technicalNotes,$radiographerId,$revision) {
            $order=$this->lockedOrder($orderId,$radiographerId,$revision,['radiografer']);
            if ($order['status']!=='PROCESS') throw new DomainException('Unggah citra hanya tersedia saat pemeriksaan dalam proses.');
            $f=ClinicalWorkflow::file($file);
            $notes=ClinicalWorkflow::text($technicalNotes);
            if ($notes==='') throw new DomainException('Catatan hasil pemeriksaan/teknis radiografer wajib diisi.');
            $this->execute('INSERT INTO radiology_image_files (radiology_order_id,mime_type,filename,content_base64,byte_size,uploaded_by) VALUES (?,?,?,?,?,?) ON CONFLICT (radiology_order_id) DO UPDATE SET mime_type=EXCLUDED.mime_type,filename=EXCLUDED.filename,content_base64=EXCLUDED.content_base64,byte_size=EXCLUDED.byte_size,uploaded_by=EXCLUDED.uploaded_by,uploaded_at=NOW()',[$orderId,$f['mime_type'],$f['filename'],$f['content_base64'],$f['byte_size'],$radiographerId]);
            $path='index.php?c=radiology&a=image&id='.$orderId;
            $exists=$this->query('SELECT id FROM radiology_results WHERE radiology_order_id=?',[$orderId]);
            if ($exists) $this->execute('UPDATE radiology_results SET image_path=?,image_filename=?,technical_notes=?,uploaded_at=NOW(),expertise_findings=NULL,expertise_conclusion=NULL,validated_at=NULL WHERE radiology_order_id=?',[$path,$f['filename'],$notes,$orderId]);
            else $this->execute('INSERT INTO radiology_results (radiology_order_id,image_path,image_filename,technical_notes) VALUES (?,?,?,?)',[$orderId,$path,$f['filename'],$notes]);
            $this->execute("UPDATE radiology_orders SET status='REVIEW',radiographer_id=?,radiologist_id=NULL,completed_at=NULL,review_notes=NULL,revision=revision+1 WHERE id=?",[$radiographerId,$orderId]);
            ClinicalWorkflow::audit($this->db,'UPLOAD_RADIOLOGY_IMAGE','RADIOLOGY',"Citra tersimpan untuk pasien #{$order['patient_id']}; order #$orderId");
            return ['success'=>true,'message'=>'Citra dan catatan tersimpan. Menunggu tinjauan dokter radiologi.','image_path'=>$path];
        });
    }
    public function saveExpertise(int $orderId, string $findings, string $conclusion, int $radiologistId, ?int $revision = null): array {
        return ClinicalWorkflow::transaction($this->db,function () use ($orderId,$findings,$conclusion,$radiologistId,$revision) {
            $order=$this->lockedOrder($orderId,$radiologistId,$revision,['dokter_radiologi']);
            if ($order['status']!=='REVIEW') throw new DomainException('Order belum siap ditinjau atau sudah diterbitkan.');
            $findings=ClinicalWorkflow::text($findings); $conclusion=ClinicalWorkflow::text($conclusion);
            if ($findings==='' || $conclusion==='') throw new DomainException('Temuan dan kesimpulan radiologi wajib diisi.');
            $result=$this->getOrderById($orderId);
            if (empty($result['image_path'])) throw new DomainException('Citra pemeriksaan belum tersedia.');
            $this->execute('UPDATE radiology_results SET expertise_findings=?,expertise_conclusion=?,validated_at=NULL WHERE radiology_order_id=?',[$findings,$conclusion,$orderId]);
            $this->execute('UPDATE radiology_orders SET radiologist_id=?,revision=revision+1 WHERE id=?',[$radiologistId,$orderId]);
            ClinicalWorkflow::audit($this->db,'SAVE_RADIOLOGY_DRAFT','RADIOLOGY',"Draf ekspertise order #$orderId");
            return ['success'=>true,'message'=>'Draf ekspertise tersimpan. Tinjau sebelum validasi akhir.'];
        });
    }
    public function validateExpertise(int $orderId, int $radiologistId, int $revision, bool $approve, string $reason = ''): array {
        return ClinicalWorkflow::transaction($this->db,function () use ($orderId,$radiologistId,$revision,$approve,$reason) {
            $order=$this->lockedOrder($orderId,$radiologistId,$revision,['dokter_radiologi']);
            if ($order['status']!=='REVIEW') throw new DomainException('Order belum siap divalidasi atau sudah diterbitkan.');
            if (!$approve) {
                $reason=ClinicalWorkflow::text($reason);
                if ($reason==='') throw new DomainException('Alasan pengembalian untuk perbaikan wajib diisi.');
                $this->execute("UPDATE radiology_orders SET status='PROCESS',review_notes=?,radiologist_id=NULL,revision=revision+1 WHERE id=?",[$reason,$orderId]);
                $this->execute('UPDATE radiology_results SET expertise_findings=NULL,expertise_conclusion=NULL,validated_at=NULL WHERE radiology_order_id=?',[$orderId]);
            } else {
                $result=$this->getOrderById($orderId);
                if (empty($result['image_path']) || trim((string)$result['expertise_findings'])==='' || trim((string)$result['expertise_conclusion'])==='') throw new DomainException('Citra, temuan, dan kesimpulan harus lengkap sebelum validasi.');
                $this->execute('UPDATE radiology_results SET validated_at=NOW() WHERE radiology_order_id=?',[$orderId]);
                $this->execute("UPDATE radiology_orders SET status='VALID',completed_at=NOW(),radiologist_id=?,review_notes=NULL,revision=revision+1 WHERE id=?",[$radiologistId,$orderId]);
            }
            ClinicalWorkflow::audit($this->db,$approve?'VALIDATE_RADIOLOGY_EXPERTISE':'RETURN_RADIOLOGY','RADIOLOGY',"Order #$orderId" . ($approve?'':'; '.$reason));
            return ['success'=>true,'message'=>$approve?'Hasil radiologi VALID dan tersedia di EMR.':'Order dikembalikan kepada radiografer untuk perbaikan.'];
        });
    }

    /**
     * Ambil riwayat hasil radiologi pasien untuk riwayat medis EMR
     */
    public function getPatientRadiologyHistory(int $patientId): array {
        return $this->query(
            "SELECT ro.*, 
                    u_doc.full_name AS doctor_name, 
                    u_rad.full_name AS radiographer_name,
                    u_sp.full_name AS radiologist_name,
                    CASE WHEN ro.status='VALID' THEN rr.image_path END AS image_path, rr.image_filename, rr.technical_notes,
                    CASE WHEN ro.status='VALID' THEN rr.expertise_findings END AS expertise_findings, CASE WHEN ro.status='VALID' THEN rr.expertise_conclusion END AS expertise_conclusion, rr.validated_at
             FROM radiology_orders ro
             LEFT JOIN users u_doc ON ro.doctor_id = u_doc.id
             LEFT JOIN users u_rad ON ro.radiographer_id = u_rad.id
             LEFT JOIN users u_sp ON ro.radiologist_id = u_sp.id
             LEFT JOIN radiology_results rr ON ro.id = rr.radiology_order_id
             WHERE ro.patient_id = :pid
             ORDER BY ro.created_at DESC",
            [':pid' => $patientId]
        );
    }
}

