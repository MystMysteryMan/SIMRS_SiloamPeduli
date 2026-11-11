<?php
// app/models/EmrModel.php - Model Electronic Medical Record (Modul 3 - SOP/SIMRS/001/2026)

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/AuditLogModel.php';

class EmrModel extends Model {
    protected string $table = 'medical_records';

    public static function isNursingRole(): bool {
        $role = strtolower($_SESSION['role'] ?? '');
        return str_contains($role, 'perawat') || str_contains($role, 'kepala_ruang');
    }

    /**
     * Ambil antrean pasien poliklinik hari ini yang siap dilayani
     */
    public function getTodayQueue(?int $doctorId = null, ?int $policlinicId = null): array {
        $today = date('Y-m-d');
        $sql = "
            SELECT a.*, 
                   p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.birth_date, p.blood_type, p.insurance_type, p.allergy_notes,
                   d.full_name AS doctor_name, pol.name AS policlinic_name, pol.code AS policlinic_code,
                   mr.id AS medical_record_id, mr.icd10_code, mr.diagnosis_name, mr.status AS emr_status
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            JOIN policlinics pol ON a.policlinic_id = pol.id
            LEFT JOIN medical_records mr ON a.id = mr.appointment_id
            WHERE a.appointment_date = :dt
              AND a.status IN ('SCHEDULED', 'IN_PROGRESS', 'COMPLETED')
        ";
        $params = [':dt' => $today];

        if ($doctorId !== null) {
            $sql .= " AND a.doctor_id = :doc_id";
            $params[':doc_id'] = $doctorId;
        }

        if ($policlinicId !== null) {
            $sql .= " AND a.policlinic_id = :poli_id";
            $params[':poli_id'] = $policlinicId;
        }

        $sql .= " ORDER BY a.queue_number ASC";
        return $this->query($sql, $params);
    }

    /**
     * Ambil detail janji temu / kunjungan berdasarkan ID
     */
    public function getAppointmentDetail(int $appointmentId): ?array {
        $sql = "
            SELECT a.*, 
                   p.id AS patient_id, p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.birth_date, 
                   p.blood_type, p.phone_number, p.address, p.insurance_type, p.insurance_number, p.allergy_notes,
                   d.id AS doctor_id, d.full_name AS doctor_name, d.specialization,
                   pol.id AS policlinic_id, pol.name AS policlinic_name, pol.queue_prefix
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            JOIN policlinics pol ON a.policlinic_id = pol.id
            WHERE a.id = :id
        ";
        $res = $this->query($sql, [':id' => $appointmentId]);
        return $res[0] ?? null;
    }

    /**
     * Ambil data rekam medis berdasarkan ID Kunjungan/Appointment
     */
    public function getRecordByAppointment(int $appointmentId): ?array {
        $sql = "
            SELECT mr.*, 
                   u.full_name AS doctor_user_name,
                   p.full_name AS patient_name, p.no_rm, p.nik
            FROM medical_records mr
            JOIN patients p ON mr.patient_id = p.id
            LEFT JOIN users u ON mr.doctor_id = u.id
            WHERE mr.appointment_id = :aid
            LIMIT 1
        ";
        $res = $this->query($sql, [':aid' => $appointmentId]);
        return $res[0] ?? null;
    }

    /**
     * Ambil rekam medis spesifik berdasarkan ID Rekam Medis
     */
    public function getRecordById(int $recordId): ?array {
        $sql = "
            SELECT mr.*, 
                   p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.birth_date, p.blood_type, p.insurance_type,
                   u.full_name AS doctor_name,
                   a.queue_number, a.appointment_date,
                   pol.name AS policlinic_name
            FROM medical_records mr
            JOIN patients p ON mr.patient_id = p.id
            LEFT JOIN users u ON mr.doctor_id = u.id
            LEFT JOIN appointments a ON mr.appointment_id = a.id
            LEFT JOIN policlinics pol ON a.policlinic_id = pol.id
            WHERE mr.id = :id
            LIMIT 1
        ";
        $res = $this->query($sql, [':id' => $recordId]);
        return $res[0] ?? null;
    }

    /**
     * Ambil seluruh riwayat rekam medis pasien terdahulu (Medical Timeline)
     */
    public function getPatientEmrHistory(int $patientId): array {
        $sql = "
            SELECT mr.*, 
                   u.full_name AS doctor_name,
                   pol.name AS policlinic_name,
                   a.appointment_date, a.queue_number
            FROM medical_records mr
            LEFT JOIN users u ON mr.doctor_id = u.id
            LEFT JOIN appointments a ON mr.appointment_id = a.id
            LEFT JOIN policlinics pol ON a.policlinic_id = pol.id
            WHERE mr.patient_id = :pid
            ORDER BY mr.created_at DESC
        ";
        $records = $this->query($sql, [':pid' => $patientId]);

        // Batch ambil order lab & radiologi terkait (Eliminasi N+1 query)
        if (!empty($records)) {
            $mrIds = array_column($records, 'id');
            $pIds = implode(',', array_map('intval', $mrIds));

            $allLabOrders = $this->query(
                "SELECT * FROM lab_orders WHERE medical_record_id IN ({$pIds}) ORDER BY created_at DESC"
            );
            $labMap = [];
            foreach ($allLabOrders as $lo) {
                $labMap[$lo['medical_record_id']][] = $lo;
            }

            $allRadOrders = $this->query(
                "SELECT ro.*, rr.image_path, rr.expertise_conclusion, rr.validated_at 
                 FROM radiology_orders ro 
                 LEFT JOIN radiology_results rr ON ro.id = rr.radiology_order_id
                 WHERE ro.medical_record_id IN ({$pIds}) 
                 ORDER BY ro.created_at DESC"
            );
            $radMap = [];
            foreach ($allRadOrders as $ro) {
                $radMap[$ro['medical_record_id']][] = $ro;
            }

            foreach ($records as &$rec) {
                $rec['lab_orders'] = $labMap[$rec['id']] ?? [];
                $rec['radiology_orders'] = $radMap[$rec['id']] ?? [];
            }
            unset($rec);
        }

        return $records;
    }

    /**
     * Simpan / Perbarui Rekam Medis Pasien (SOP EMR - Langkah 4, 5, 6, 8)
     */
    public function saveMedicalRecord(array $data): array {
        $role = strtolower($_SESSION['role'] ?? '');
        $isNurse = self::isNursingRole();
        if (empty($_SESSION['user_id']) || (!$isNurse &&
            !in_array($role, ['dokter', 'dokter spesialis', 'dpjp'], true) &&
            !str_contains($role, 'admin'))) {
            return ['success' => false, 'message' => 'Akses penyimpanan EMR ditolak.'];
        }

        $appointmentId = (int)($data['appointment_id'] ?? 0);
        if ($appointmentId <= 0) {
            return ['success' => false, 'message' => 'Kunjungan pasien tidak valid.'];
        }

        try {
            $this->db->beginTransaction();
            // Semua penyimpanan EMR mengunci kunjungan yang sama agar tidak membuat catatan ganda.
            $rows = $this->query('SELECT * FROM appointments WHERE id = :id FOR UPDATE', [':id' => $appointmentId]);
            $appointment = $rows[0] ?? null;
            if (!$appointment || (int)$appointment['patient_id'] !== (int)($data['patient_id'] ?? 0)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Pasien dan kunjungan tidak cocok.'];
            }
            $doctor = $this->query('SELECT user_id FROM doctors WHERE id = :id', [':id' => $appointment['doctor_id']]);
            if (empty($doctor[0]['user_id'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Akun dokter pada kunjungan belum tersedia.'];
            }
            // Identitas dokter berasal dari kunjungan, bukan input tersembunyi pada formulir.
            $data['doctor_id'] = (int)$doctor[0]['user_id'];
            $result = $isNurse ? $this->saveNursingRecord($data) : $this->saveDoctorRecord($data);
            if ($result['success']) {
                $this->db->commit();
            } else {
                $this->db->rollBack();
            }
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('EMR save failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Rekam medis belum tersimpan. Silakan coba kembali.'];
        }
    }

    private function saveNursingRecord(array $data): array {
        $patientId = (int)$data['patient_id'];
        $appointmentId = (int)$data['appointment_id'];
        $bp = trim($data['vital_bp'] ?? '');
        if ($bp !== '' && !preg_match('/^\d{1,3}\s*\/\s*\d{1,3}$/', $bp)) {
            return ['success' => false, 'message' => 'Format tekanan darah tidak valid. Gunakan format 120/80.'];
        }
        $values = [];
        foreach (['vital_temp', 'vital_pulse', 'vital_rr', 'weight_kg', 'height_cm'] as $field) {
            $value = trim((string)($data[$field] ?? ''));
            $number = $value === '' ? null : filter_var($value, FILTER_VALIDATE_FLOAT);
            if ($number === false || ($number !== null && (!is_finite($number) || $number <= 0)) ||
                (in_array($field, ['vital_pulse', 'vital_rr'], true) && $number !== null && floor($number) != $number)) {
                return ['success' => false, 'message' => 'Format nilai tanda vital tidak valid.'];
            }
            $values[$field] = $number;
        }
        $bmi = $values['weight_kg'] && $values['height_cm']
            ? round($values['weight_kg'] / (($values['height_cm'] / 100) ** 2), 1) : null;
        $params = [
            ':bp' => clean($bp), ':temp' => $values['vital_temp'],
            ':pulse' => $values['vital_pulse'], ':rr' => $values['vital_rr'],
            ':weight' => $values['weight_kg'], ':height' => $values['height_cm'], ':bmi' => $bmi,
            ':notes' => clean($data['clinical_notes'] ?? ''), ':nurse' => (int)$_SESSION['user_id']
        ];
        $existing = $this->getRecordByAppointment($appointmentId);
        if ($existing && (int)$existing['patient_id'] !== $patientId) {
            return ['success' => false, 'message' => 'Pasien dan rekam medis tidak cocok.'];
        }
        if ($existing) {
            $recordId = (int)$existing['id'];
            // Daftar kolom terbatas: SOAP, diagnosis, dokter dan status pemeriksaan tidak ikut diperbarui.
            $this->execute("UPDATE medical_records SET
                vital_bp = :bp, vital_temp = :temp, vital_pulse = :pulse, vital_rr = :rr,
                weight_kg = :weight, height_cm = :height, bmi = :bmi,
                clinical_notes = :notes, nurse_id = :nurse, assessed_at = NOW(), updated_at = NOW()
                WHERE id = :id", $params + [':id' => $recordId]);
        } else {
            $recordId = (int)$this->insert("INSERT INTO medical_records (
                patient_id, appointment_id, doctor_id,
                vital_bp, vital_temp, vital_pulse, vital_rr, weight_kg, height_cm, bmi,
                clinical_notes, nurse_id, assessed_at, subjective, objective, assessment, plan,
                icd10_code, diagnosis_name, status
            ) VALUES (:pid, :aid, :doctor, :bp, :temp, :pulse, :rr, :weight, :height, :bmi,
                :notes, :nurse, NOW(), '', '', '', '', '', '', 'IN_PROGRESS')", $params + [
                    ':pid' => $patientId, ':aid' => $appointmentId, ':doctor' => (int)$data['doctor_id']
                ]);
        }
        if (!empty($data['new_allergy'])) {
            $severity = $data['allergy_severity'] ?? 'SEDANG';
            if (!in_array($severity, ['RINGAN', 'SEDANG', 'BERAT'], true)) {
                return ['success' => false, 'message' => 'Tingkat keparahan alergi tidak valid.'];
            }
            $this->execute('INSERT INTO patient_allergies (patient_id, allergen_name, severity, reaction)
                VALUES (:pid, :name, :severity, :reaction)', [
                    ':pid' => $patientId, ':name' => clean($data['new_allergy']),
                    ':severity' => $severity, ':reaction' => clean($data['allergy_reaction'] ?? '')
                ]);
        }
        AuditLogModel::log($existing ? 'UPDATE_EMR_NURSING' : 'CREATE_EMR_NURSING', 'EMR',
            "Pencatatan perawat untuk pasien #{$patientId}, kunjungan #{$appointmentId}, rekam medis #{$recordId}",
            (int)$_SESSION['user_id']);
        return ['success' => true, 'message' => 'Catatan perawat berhasil disimpan.', 'record_id' => $recordId];
    }

    private function saveDoctorRecord(array $data): array {
        // VALIDASI ATURAN KETAT SOP LANGKAH 6: Kode ICD-10 Wajib Diisi!
        $icdCode = trim($data['icd10_code'] ?? '');
        if (empty($icdCode)) {
            return [
                'success' => false,
                'message' => 'Kode ICD-10 Wajib Diisi!'
            ];
        }

        $patientId     = (int)($data['patient_id'] ?? 0);
        $appointmentId = !empty($data['appointment_id']) ? (int)$data['appointment_id'] : null;
        $doctorId      = !empty($data['doctor_id']) ? (int)$data['doctor_id'] : ($_SESSION['user_id'] ?? null);

        if ($patientId <= 0) {
            return ['success' => false, 'message' => 'Identitas Pasien tidak valid.'];
        }

        // Kalkulasi BMI
        $weight = !empty($data['weight_kg']) ? (float)$data['weight_kg'] : null;
        $height = !empty($data['height_cm']) ? (float)$data['height_cm'] : null;
        $bmi = null;
        if ($weight && $height && $height > 0) {
            $heightM = $height / 100;
            $bmi = round($weight / ($heightM * $heightM), 1);
        }

        // Ambil nama diagnosa dari ICD-10 jika belum diinput
        $diagName = trim($data['diagnosis_name'] ?? '');
        if (empty($diagName)) {
            $icdRows = $this->query("SELECT description_id FROM icd_diagnoses WHERE code = :c LIMIT 1", [':c' => $icdCode]);
            $diagName = $icdRows[0]['description_id'] ?? 'Diagnosis Medis Terverifikasi';
        }

        // Cek apakah sudah ada record untuk appointment ini
        $existing = null;
        if ($appointmentId) {
            $existing = $this->getRecordByAppointment($appointmentId);
        }

        if ($existing) {
            // UPDATE
            $recordId = (int)$existing['id'];
            $sql = "
                UPDATE medical_records SET
                    doctor_id      = :doc_id,
                    vital_bp       = :bp,
                    vital_temp     = :temp,
                    vital_pulse    = :pulse,
                    vital_rr       = :rr,
                    weight_kg      = :weight,
                    height_cm      = :height,
                    bmi            = :bmi,
                    subjective     = :subj,
                    objective      = :obj,
                    assessment     = :assess,
                    plan           = :plan,
                    icd10_code     = :icd,
                    diagnosis_name = :dname,
                    clinical_notes = :notes,
                    status         = 'COMPLETED',
                    examined_at    = NOW(),
                    updated_at     = NOW()
                WHERE id = :id
            ";
            $this->execute($sql, [
                ':doc_id' => $doctorId,
                ':bp'     => clean($data['vital_bp'] ?? ''),
                ':temp'   => $data['vital_temp'] ?? null,
                ':pulse'  => $data['vital_pulse'] ?? null,
                ':rr'     => $data['vital_rr'] ?? null,
                ':weight' => $weight,
                ':height' => $height,
                ':bmi'    => $bmi,
                ':subj'   => clean($data['subjective'] ?? ''),
                ':obj'    => clean($data['objective'] ?? ''),
                ':assess' => clean($data['assessment'] ?? ''),
                ':plan'   => clean($data['plan'] ?? ''),
                ':icd'    => $icdCode,
                ':dname'  => $diagName,
                ':notes'  => clean($data['clinical_notes'] ?? ''),
                ':id'     => $recordId
            ]);
            $action = 'UPDATE_EMR';
        } else {
            // INSERT BARU
            $sql = "
                INSERT INTO medical_records (
                    patient_id, appointment_id, doctor_id,
                    vital_bp, vital_temp, vital_pulse, vital_rr,
                    weight_kg, height_cm, bmi,
                    subjective, objective, assessment, plan,
                    icd10_code, diagnosis_name, clinical_notes,
                    status, examined_at
                ) VALUES (
                    :pid, :aid, :doc_id,
                    :bp, :temp, :pulse, :rr,
                    :weight, :height, :bmi,
                    :subj, :obj, :assess, :plan,
                    :icd, :dname, :notes,
                    'COMPLETED', NOW()
                )
            ";
            $recordId = (int)$this->insert($sql, [
                ':pid'    => $patientId,
                ':aid'    => $appointmentId,
                ':doc_id' => $doctorId,
                ':bp'     => clean($data['vital_bp'] ?? ''),
                ':temp'   => $data['vital_temp'] ?? null,
                ':pulse'  => $data['vital_pulse'] ?? null,
                ':rr'     => $data['vital_rr'] ?? null,
                ':weight' => $weight,
                ':height' => $height,
                ':bmi'    => $bmi,
                ':subj'   => clean($data['subjective'] ?? ''),
                ':obj'    => clean($data['objective'] ?? ''),
                ':assess' => clean($data['assessment'] ?? ''),
                ':plan'   => clean($data['plan'] ?? ''),
                ':icd'    => $icdCode,
                ':dname'  => $diagName,
                ':notes'  => clean($data['clinical_notes'] ?? '')
            ]);
            $action = 'CREATE_EMR';
        }

        // Tandai status janji temu menjadi COMPLETED jika appointment_id ada
        if ($appointmentId) {
            $this->execute("UPDATE appointments SET status = 'COMPLETED' WHERE id = :aid", [':aid' => $appointmentId]);
        }

        // Simpan Alergi jika ada input alergi baru
        if (!empty($data['new_allergy'])) {
            $this->execute(
                "INSERT INTO patient_allergies (patient_id, allergen_name, severity, reaction) VALUES (:pid, :alg, :sev, :reac)",
                [
                    ':pid'  => $patientId,
                    ':alg'  => clean($data['new_allergy']),
                    ':sev'  => clean($data['allergy_severity'] ?? 'SEDANG'),
                    ':reac' => clean($data['allergy_reaction'] ?? '')
                ]
            );
        }

        // Buat Order Laboratorium jika dokter memilih pemeriksaan lab di bagian Plan
        $labOrderId = null;
        if (!empty($data['order_lab']) && !empty($data['lab_tests']) && is_array($data['lab_tests'])) {
            $labModel = new LaboratoryModel();
            $labRes = $labModel->createLabOrder([
                'patient_id'        => $patientId,
                'appointment_id'    => $appointmentId,
                'medical_record_id' => $recordId,
                'doctor_id'         => $doctorId,
                'clinical_priority' => $data['lab_priority'] ?? 'REGULER',
                'clinical_notes'    => $data['lab_notes'] ?? ($diagName . ' - Permintaan dari EMR'),
                'tests'             => $data['lab_tests']
            ]);
            if ($labRes['success']) {
                $labOrderId = $labRes['order_id'];
            }
        }

        // Buat Order Radiologi jika dokter memilih pemeriksaan radiologi di bagian Plan
        $radOrderId = null;
        if (!empty($data['order_radiology']) && !empty($data['rad_modality'])) {
            $radModel = new RadiologyModel();
            $radRes = $radModel->createRadiologyOrder([
                'patient_id'          => $patientId,
                'appointment_id'      => $appointmentId,
                'medical_record_id'   => $recordId,
                'doctor_id'           => $doctorId,
                'modality_type'       => $data['rad_modality'],
                'clinical_indication' => !empty($data['rad_indication']) ? $data['rad_indication'] : ($diagName . ' - Indikasi pemeriksaan radiologi'),
                'clinical_priority'   => $data['rad_priority'] ?? 'REGULER'
            ]);
            if ($radRes['success']) {
                $radOrderId = $radRes['order_id'];
            }
        }

        // Catat Audit Trail
        AuditLogModel::log(
            $action,
            'EMR',
            "Pencatatan EMR Pasien ID #{$patientId} dengan Diagnosis ICD-10 [{$icdCode}] {$diagName}",
            $_SESSION['user_id'] ?? null
        );

        return [
            'success'   => true,
            'message'   => 'Rekam Medis Pasien Berhasil Disimpan.',
            'record_id' => $recordId
        ];
    }

    /**
     * Ambil riwayat alergi pasien
     */
    public function getPatientAllergies(int $patientId): array {
        return $this->query(
            "SELECT * FROM patient_allergies WHERE patient_id = :pid ORDER BY recorded_at DESC",
            [':pid' => $patientId]
        );
    }
}

