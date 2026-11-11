<?php
// app/models/RadiologyModel.php - Model Radiologi (Modul 9 - SOP/SIMRS/005/2026)

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/AuditLogModel.php';

class RadiologyModel extends Model {
    protected string $table = 'radiology_orders';

    /**
     * Generate Nomor Permintaan Radiologi unik: RAD-YYYYMMDD-XXXX
     */
    public function generateOrderNumber(): string {
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
                CASE WHEN ro.clinical_priority = 'CITO' AND ro.status IN ('PENDING', 'PROCESS') THEN 1 ELSE 2 END,
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
                COUNT(*) FILTER (WHERE status = 'PROCESS') AS process,
                COUNT(*) FILTER (WHERE status = 'VALID') AS valid,
                COUNT(*) FILTER (WHERE clinical_priority = 'CITO' AND status IN ('PENDING', 'PROCESS')) AS cito
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
                CASE WHEN ro.clinical_priority = 'CITO' AND ro.status IN ('PENDING', 'PROCESS') THEN 1 ELSE 2 END,
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
    public function processOrder(int $orderId, int $radiographerId): array {
        $order = $this->getOrderById($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Order Radiologi tidak ditemukan.'];
        }

        if (!in_array($order['status'], ['PENDING', 'PROCESS'], true)) {
            return ['success' => false, 'message' => 'Order ini tidak dapat diproses ulang.'];
        }

        $this->execute(
            "UPDATE radiology_orders SET status = 'PROCESS', radiographer_id = :rid WHERE id = :id",
            [':rid' => $radiographerId, ':id' => $orderId]
        );

        AuditLogModel::log(
            'PROCESS_RADIOLOGY_ORDER',
            'RADIOLOGY',
            "Radiografer mempersiapkan pasien dan memproses eksposur Order #{$order['order_number']}",
            $radiographerId
        );

        return ['success' => true, 'message' => "Order #{$order['order_number']} dalam proses pemotretan radiologi."];
    }

    /** Store original image bytes in the existing PostgreSQL database.
     * Vercel has no durable local upload directory and GD is optional.
     */
    private function ensureImageStorage(): void {
        if ($this->db->query("SELECT to_regclass('public.radiology_image_files')")->fetchColumn()) {
            return;
        }
        // Additive, module-local initialization; no existing rows are changed.
        $this->db->exec("CREATE TABLE IF NOT EXISTS public.radiology_image_files (
            radiology_order_id INTEGER PRIMARY KEY REFERENCES radiology_orders(id),
            mime_type VARCHAR(20) NOT NULL CHECK (mime_type IN ('image/jpeg','image/png','image/webp')),
            image_data TEXT NOT NULL,
            byte_size INTEGER NOT NULL CHECK (byte_size > 0 AND byte_size <= 1048576),
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        // Files are served through the authenticated controller, never the public REST API.
        $this->db->exec("ALTER TABLE public.radiology_image_files ENABLE ROW LEVEL SECURITY");
        $this->db->exec("REVOKE ALL ON public.radiology_image_files FROM PUBLIC");
        foreach (['anon', 'authenticated'] as $role) {
            if ($this->query('SELECT 1 FROM pg_roles WHERE rolname = :role', [':role' => $role])) {
                $this->db->exec('REVOKE ALL ON public.radiology_image_files FROM ' . $role);
            }
        }
    }

    public function getStoredImage(int $orderId): ?array {
        if (!$this->db->query("SELECT to_regclass('public.radiology_image_files')")->fetchColumn()) {
            return null;
        }
        $rows = $this->query("SELECT f.mime_type, f.image_data, f.byte_size, rr.image_filename
            FROM public.radiology_image_files f
            JOIN radiology_results rr ON rr.radiology_order_id = f.radiology_order_id
            WHERE f.radiology_order_id = :id LIMIT 1", [':id' => $orderId]);
        return $rows[0] ?? null;
    }

    public static function hasImage(array $result): bool {
        $path = $result['image_path'] ?? '';
        return $path !== '' && (
            preg_match('~^index\.php\?c=radiology&a=image&id=[1-9][0-9]*$~D', $path) === 1
            || preg_match('~^https?://~i', $path) === 1
            || is_file(__DIR__ . '/../../' . $path)
        );
    }

    public function uploadImage(int $orderId, array $file, string $technicalNotes, int $radiographerId): array {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || !is_uploaded_file($file['tmp_name'] ?? '')) {
            return ['success' => false, 'message' => 'Pilih berkas citra radiologi yang valid.'];
        }
        $size = filesize($file['tmp_name']);
        if (!$size || $size > 1048576) {
            return ['success' => false, 'message' => 'Ukuran citra maksimal 1 MB. Pilih berkas yang lebih kecil.'];
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $dimensions = @getimagesize($file['tmp_name']);
        if (!isset($extensions[$mime]) || !$dimensions || ($dimensions['mime'] ?? '') !== $mime) {
            return ['success' => false, 'message' => 'Format citra harus berupa JPG, PNG, atau WebP yang dapat dibaca.'];
        }
        $bytes = file_get_contents($file['tmp_name']);
        if ($bytes === false || strlen($bytes) !== $size) {
            return ['success' => false, 'message' => 'Berkas citra tidak dapat dibaca. Silakan unggah kembali.'];
        }
        try {
            $this->db->beginTransaction();
            $rows = $this->query('SELECT id, order_number, status FROM radiology_orders WHERE id = :id FOR UPDATE', [':id' => $orderId]);
            $order = $rows[0] ?? null;
            if (!$order || $order['status'] !== 'PROCESS') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Citra hanya dapat diunggah pada order yang sedang diproses.'];
            }
            $this->ensureImageStorage();
            $this->execute("INSERT INTO public.radiology_image_files (radiology_order_id, mime_type, image_data, byte_size)
                VALUES (:id, :mime, :data, :size)
                ON CONFLICT (radiology_order_id) DO UPDATE SET mime_type = EXCLUDED.mime_type,
                image_data = EXCLUDED.image_data, byte_size = EXCLUDED.byte_size, updated_at = CURRENT_TIMESTAMP",
                [':id' => $orderId, ':mime' => $mime, ':data' => base64_encode($bytes), ':size' => $size]);
            $path = 'index.php?c=radiology&a=image&id=' . $orderId;
            $filename = 'rad_' . $orderId . '.' . $extensions[$mime];
            $params = [':oid' => $orderId, ':path' => $path, ':fname' => $filename, ':notes' => clean($technicalNotes)];
            if ($this->query('SELECT id FROM radiology_results WHERE radiology_order_id = :oid', [':oid' => $orderId])) {
                $this->execute("UPDATE radiology_results SET image_path = :path, image_filename = :fname,
                    technical_notes = :notes, uploaded_at = NOW() WHERE radiology_order_id = :oid", $params);
            } else {
                $this->execute("INSERT INTO radiology_results (radiology_order_id, image_path, image_filename, technical_notes, uploaded_at)
                    VALUES (:oid, :path, :fname, :notes, NOW())", $params);
            }
            $this->execute('UPDATE radiology_orders SET radiographer_id = :rid WHERE id = :id', [':rid' => $radiographerId, ':id' => $orderId]);
            AuditLogModel::log('UPLOAD_RADIOLOGY_IMAGE', 'RADIOLOGY',
                "Citra radiologi tersimpan untuk Order #{$order['order_number']} ({$size} bytes)", $radiographerId);
            $this->db->commit();
            return ['success' => true, 'message' => 'Citra radiologi berhasil diunggah dan disimpan.', 'image_path' => $path];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('Radiology image storage failed: ' . get_class($e) . ' [' . $e->getCode() . ']');
            return ['success' => false, 'message' => 'Citra belum tersimpan. Silakan coba kembali atau hubungi administrator.'];
        }
    }

    /**
     * Dokter Spesialis Radiologi memberikan ekspertise klinis & validasi (PROCESS -> VALID)
     */
    public function saveExpertise(int $orderId, string $findings, string $conclusion, int $radiologistId): array {
        $order = $this->getOrderById($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Order Radiologi tidak ditemukan.'];
        }

        if (empty(trim($findings))) {
            return ['success' => false, 'message' => 'Deskripsi temuan ekspertise radiologi wajib diisi.'];
        }

        if (empty(trim($conclusion))) {
            return ['success' => false, 'message' => 'Kesimpulan diagnosis ekspertise radiologi wajib diisi.'];
        }

        // Cek keberadaan hasil citra
        $existingRes = $this->query("SELECT id FROM radiology_results WHERE radiology_order_id = :oid LIMIT 1", [':oid' => $orderId]);
        if (empty($existingRes)) {
            return ['success' => false, 'message' => 'Citra radiologi belum diunggah oleh radiografer.'];
        }

        $this->execute(
            "UPDATE radiology_results SET
                expertise_findings   = :findings,
                expertise_conclusion = :conclusion,
                validated_at         = NOW()
             WHERE radiology_order_id = :oid",
            [
                ':findings'   => clean($findings),
                ':conclusion' => clean($conclusion),
                ':oid'        => $orderId
            ]
        );

        $this->execute(
            "UPDATE radiology_orders SET status = 'VALID', completed_at = NOW(), radiologist_id = :spid WHERE id = :id",
            [':spid' => $radiologistId, ':id' => $orderId]
        );

        AuditLogModel::log(
            'VALIDATE_RADIOLOGY_EXPERTISE',
            'RADIOLOGY',
            "Dokter Spesialis Radiologi menyelesaikan ekspertise dan memvalidasi Order #{$order['order_number']}",
            $radiologistId
        );

        return [
            'success' => true,
            'message' => "Ekspertise radiologi Order #{$order['order_number']} berhasil divalidasi dan terbit."
        ];
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
                    rr.image_path, rr.image_filename, rr.technical_notes,
                    rr.expertise_findings, rr.expertise_conclusion, rr.validated_at
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

