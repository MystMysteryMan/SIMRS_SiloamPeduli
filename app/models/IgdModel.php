<?php
// app/models/IgdModel.php - Model Emergency Department (Modul 7 - SOP/SIMRS/007/2026)

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/AuditLogModel.php';

class IgdModel extends Model {
    protected string $table = 'emergency_visits';

    /**
     * Generate Nomor Kunjungan IGD unik: IGD-YYYYMMDD-XXXX
     */
    public function generateVisitNumber(): string {
        $prefix = 'IGD-' . date('Ymd') . '-';
        $sql = "SELECT visit_number FROM emergency_visits WHERE visit_number LIKE :prefix ORDER BY id DESC LIMIT 1";
        $row = $this->query($sql, [':prefix' => $prefix . '%']);

        if (!empty($row)) {
            $lastNumber = $row[0]['visit_number'];
            $seq = (int)substr($lastNumber, -4);
            $nextSeq = $seq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Registrasi Cepat Pasien IGD (Pasien Terdaftar atau Mr./Mrs. X)
     */
    public function createEmergencyVisit(array $data, int $userId): array {
        $visitNumber = $this->generateVisitNumber();

        $patientId = !empty($data['patient_id']) ? (int)$data['patient_id'] : null;
        $anonymousName = null;

        if (empty($patientId) && !empty($data['anonymous_name'])) {
            $anonymousName = trim($data['anonymous_name']);
        } elseif (empty($patientId)) {
            $anonymousName = 'Mr./Mrs. X (' . date('H:i') . ')';
        }

        $sql = "
            INSERT INTO emergency_visits (
                visit_number, patient_id, anonymous_name, arrival_time,
                transportation, accompanied_by, status, created_at
            ) VALUES (
                :vnum, :pid, :anon, NOW(), :trans, :accomp, 'TRIAGE', NOW()
            )
        ";

        $this->execute($sql, [
            ':vnum'   => $visitNumber,
            ':pid'    => $patientId,
            ':anon'   => $anonymousName,
            ':trans'  => $data['transportation'] ?? 'KENDARAAN_PRIBADI',
            ':accomp' => $data['accompanied_by'] ?? null
        ]);

        $visitId = (int)$this->db->lastInsertId();

        AuditLogModel::log(
            'REGISTER_IGD',
            'IGD',
            "Registrasi cepat pasien IGD berhasil: {$visitNumber} (ID: {$visitId})",
            $userId
        );

        return ['success' => true, 'visit_id' => $visitId, 'visit_number' => $visitNumber];
    }

    /**
     * Simpan Pengkajian Triase Klinis ESI (Merah, Kuning, Hijau, Hitam) & Skor GCS
     */
    public function saveTriage(array $data, int $officerId): array {
        $visitId = (int)$data['emergency_visit_id'];
        $triageLevel = strtoupper($data['triage_level'] ?? 'KUNING');
        $esiLevel = (int)($data['esi_level'] ?? 3);

        // GCS Score
        $eye = (int)($data['gcs_eye'] ?? 4);
        $verbal = (int)($data['gcs_verbal'] ?? 5);
        $motor = (int)($data['gcs_motor'] ?? 6);
        $gcsTotal = $eye + $verbal + $motor;

        $this->db->beginTransaction();
        try {
            $sql = "
                INSERT INTO emergency_triages (
                    emergency_visit_id, officer_id, triage_level, esi_level, chief_complaint,
                    airway_assessment, breathing_assessment, circulation_assessment,
                    gcs_eye, gcs_verbal, gcs_motor, gcs_total,
                    systolic_bp, diastolic_bp, pulse_rate, respiratory_rate, temperature, spo2, triage_time
                ) VALUES (
                    :vid, :oid, :tlevel, :esilvl, :complaint,
                    :airway, :breathing, :circulation,
                    :eye, :verbal, :motor, :gcs,
                    :sbp, :dbp, :pulse, :rr, :temp, :spo2, NOW()
                )
                ON CONFLICT (emergency_visit_id) DO UPDATE SET
                    triage_level = EXCLUDED.triage_level,
                    esi_level = EXCLUDED.esi_level,
                    chief_complaint = EXCLUDED.chief_complaint,
                    airway_assessment = EXCLUDED.airway_assessment,
                    breathing_assessment = EXCLUDED.breathing_assessment,
                    circulation_assessment = EXCLUDED.circulation_assessment,
                    gcs_eye = EXCLUDED.gcs_eye,
                    gcs_verbal = EXCLUDED.gcs_verbal,
                    gcs_motor = EXCLUDED.gcs_motor,
                    gcs_total = EXCLUDED.gcs_total,
                    systolic_bp = EXCLUDED.systolic_bp,
                    diastolic_bp = EXCLUDED.diastolic_bp,
                    pulse_rate = EXCLUDED.pulse_rate,
                    respiratory_rate = EXCLUDED.respiratory_rate,
                    temperature = EXCLUDED.temperature,
                    spo2 = EXCLUDED.spo2,
                    triage_time = NOW()
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':vid'         => $visitId,
                ':oid'         => $officerId,
                ':tlevel'      => $triageLevel,
                ':esilvl'      => $esiLevel,
                ':complaint'   => $data['chief_complaint'] ?? 'Keluhan darurat',
                ':airway'      => $data['airway_assessment'] ?? 'Paten',
                ':breathing'   => $data['breathing_assessment'] ?? 'Spontan',
                ':circulation' => $data['circulation_assessment'] ?? 'Nadi Teraba Kuat',
                ':eye'         => $eye,
                ':verbal'      => $verbal,
                ':motor'       => $motor,
                ':gcs'         => $gcsTotal,
                ':sbp'         => (int)($data['systolic_bp'] ?? 120),
                ':dbp'         => (int)($data['diastolic_bp'] ?? 80),
                ':pulse'       => (int)($data['pulse_rate'] ?? 80),
                ':rr'          => (int)($data['respiratory_rate'] ?? 20),
                ':temp'        => (float)($data['temperature'] ?? 36.5),
                ':spo2'        => (int)($data['spo2'] ?? 98)
            ]);

            // Update status kunjungan ke EXAMINATION dan pasang kategori triase
            $this->execute(
                "UPDATE emergency_visits SET triage_category = :tlevel, status = 'EXAMINATION' WHERE id = :vid",
                [':tlevel' => $triageLevel, ':vid' => $visitId]
            );

            $this->db->commit();

            AuditLogModel::log(
                'TRIAGE_ASSESSMENT',
                'IGD',
                "Triase {$triageLevel} (ESI {$esiLevel}, GCS {$gcsTotal}) disimpan untuk kunjungan IGD ID {$visitId}",
                $officerId
            );

            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Primary Survey ABCDE & Asesmen Dokter IGD (SOP: Wajib Kode ICD-10)
     */
    public function saveAssessment(array $data, int $doctorId, int $userId): array {
        // VALIDASI SOP KETAT: Kode ICD-10 Wajib Diisi!
        $icd10 = trim($data['icd10_code'] ?? '');
        if (empty($icd10)) {
            return [
                'success' => false,
                'message' => 'Kode ICD-10 Wajib Diisi!'
            ];
        }

        $visitId = (int)$data['emergency_visit_id'];

        $this->db->beginTransaction();
        try {
            $sql = "
                INSERT INTO emergency_assessments (
                    emergency_visit_id, doctor_id, primary_airway, primary_breathing,
                    primary_circulation, primary_disability, primary_exposure, secondary_survey,
                    icd10_code, diagnosis_name, therapy_plan, created_at
                ) VALUES (
                    :vid, :docid, :airway, :breathing,
                    :circ, :disab, :expo, :sec,
                    :icd, :dname, :plan, NOW()
                )
                ON CONFLICT (emergency_visit_id) DO UPDATE SET
                    doctor_id = EXCLUDED.doctor_id,
                    primary_airway = EXCLUDED.primary_airway,
                    primary_breathing = EXCLUDED.primary_breathing,
                    primary_circulation = EXCLUDED.primary_circulation,
                    primary_disability = EXCLUDED.primary_disability,
                    primary_exposure = EXCLUDED.primary_exposure,
                    secondary_survey = EXCLUDED.secondary_survey,
                    icd10_code = EXCLUDED.icd10_code,
                    diagnosis_name = EXCLUDED.diagnosis_name,
                    therapy_plan = EXCLUDED.therapy_plan
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':vid'       => $visitId,
                ':docid'     => $doctorId,
                ':airway'    => $data['primary_airway'] ?? 'Bebas, tidak ada sumbatan',
                ':breathing' => $data['primary_breathing'] ?? 'Spontan, simetris, vesikuler',
                ':circ'      => $data['primary_circulation'] ?? 'Nadi reguler kuat, akral hangat CRT < 2d',
                ':disab'     => $data['primary_disability'] ?? 'Pupil isokor, refleks cahaya (+)',
                ':expo'      => $data['primary_exposure'] ?? 'Tidak tampak jejas deformitas akut',
                ':sec'       => $data['secondary_survey'] ?? null,
                ':icd'       => $icd10,
                ':dname'     => $data['diagnosis_name'] ?? 'Diagnosis Emergensi',
                ':plan'      => $data['therapy_plan'] ?? 'Terapi & observasi'
            ]);

            // Update dokter jaga dan status observasi
            $this->execute(
                "UPDATE emergency_visits SET doctor_id = :docid, status = 'OBSERVATION' WHERE id = :vid",
                [':docid' => $doctorId, ':vid' => $visitId]
            );

            $this->db->commit();

            AuditLogModel::log(
                'PRIMARY_SURVEY',
                'IGD',
                "Asesmen dokter & survei ABCDE disimpan dengan diagnosis [{$icd10}] untuk IGD ID {$visitId}",
                $userId
            );

            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Catat Monitoring Tanda Vital Berkala dengan Deteksi Otomatis Abnormal
     */
    public function saveMonitoring(array $data, int $userId): array {
        $visitId = (int)$data['emergency_visit_id'];
        $sbp = (int)($data['systolic_bp'] ?? 120);
        $dbp = (int)($data['diastolic_bp'] ?? 80);
        $pulse = (int)($data['pulse_rate'] ?? 80);
        $rr = (int)($data['respiratory_rate'] ?? 20);
        $temp = (float)($data['temperature'] ?? 36.5);
        $spo2 = (int)($data['spo2'] ?? 98);
        $gcs = (int)($data['gcs_total'] ?? 15);

        // Ambang batas abnormalitas tanda vital
        $isAbnormal = 0;
        if ($sbp < 90 || $sbp > 180 || $spo2 < 95 || $pulse > 120 || $pulse < 50 || $temp > 38.5 || $temp < 35.5 || $gcs < 13) {
            $isAbnormal = 1;
        }

        $sql = "
            INSERT INTO emergency_monitorings (
                emergency_visit_id, recorded_by, monitoring_time,
                systolic_bp, diastolic_bp, pulse_rate, respiratory_rate,
                temperature, spo2, gcs_total, notes, is_abnormal
            ) VALUES (
                :vid, :uid, NOW(),
                :sbp, :dbp, :pulse, :rr,
                :temp, :spo2, :gcs, :notes, :abnormal
            )
        ";

        $this->execute($sql, [
            ':vid'      => $visitId,
            ':uid'      => $userId,
            ':sbp'      => $sbp,
            ':dbp'      => $dbp,
            ':pulse'    => $pulse,
            ':rr'       => $rr,
            ':temp'     => $temp,
            ':spo2'     => $spo2,
            ':gcs'      => $gcs,
            ':notes'    => $data['notes'] ?? null,
            ':abnormal' => $isAbnormal ? 'true' : 'false'
        ]);

        AuditLogModel::log(
            'MONITORING_TTV',
            'IGD',
            "Monitoring TTV dicatat untuk IGD ID {$visitId}" . ($isAbnormal ? " [PERINGATAN: KONDISI ABNORMAL]" : ""),
            $userId
        );

        return ['success' => true, 'is_abnormal' => (bool)$isAbnormal];
    }

    /**
     * Keputusan Disposisi Akhir Pasien IGD
     */
    public function saveDisposition(int $visitId, string $dispositionType, ?string $notes, int $userId): bool {
        $allowed = ['RAWAT_INAP', 'ICU', 'RUJUK', 'PULANG', 'MENINGGAL'];
        if (!in_array($dispositionType, $allowed)) {
            return false;
        }

        $sql = "
            UPDATE emergency_visits 
            SET status = 'DISPOSED', 
                disposition_type = :dtype, 
                disposition_notes = :notes, 
                disposition_time = NOW() 
            WHERE id = :vid
        ";

        $ok = $this->execute($sql, [
            ':dtype' => $dispositionType,
            ':notes' => $notes,
            ':vid'   => $visitId
        ]);

        if ($ok) {
            AuditLogModel::log(
                'DISPOSITION_IGD',
                'IGD',
                "Disposisi {$dispositionType} ditetapkan untuk kunjungan IGD ID {$visitId}",
                $userId
            );
        }

        return $ok;
    }

    /**
     * Ambil Kunjungan IGD Aktif & Riwayat
     */
    public function getAllVisits(array $filters = []): array {
        $sql = "
            SELECT ev.*,
                   p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.birth_date, p.insurance_type,
                   t.triage_level, t.esi_level, t.chief_complaint, t.gcs_total, t.spo2, t.pulse_rate, t.systolic_bp, t.diastolic_bp,
                   a.icd10_code, a.diagnosis_name,
                   d.full_name AS doctor_name,
                   (SELECT COUNT(*) FROM emergency_monitorings WHERE emergency_visit_id = ev.id) AS monitoring_count,
                   (SELECT is_abnormal FROM emergency_monitorings WHERE emergency_visit_id = ev.id ORDER BY id DESC LIMIT 1) AS last_monitoring_abnormal
            FROM emergency_visits ev
            LEFT JOIN patients p ON ev.patient_id = p.id
            LEFT JOIN emergency_triages t ON ev.id = t.emergency_visit_id
            LEFT JOIN emergency_assessments a ON ev.id = a.emergency_visit_id
            LEFT JOIN doctors d ON ev.doctor_id = d.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND ev.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['triage_category'])) {
            $sql .= " AND ev.triage_category = :triage";
            $params[':triage'] = $filters['triage_category'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (ev.visit_number LIKE :s OR ev.anonymous_name LIKE :s OR p.full_name LIKE :s OR p.no_rm LIKE :s)";
            $params[':s'] = '%' . $filters['search'] . '%';
        }

        // Urutan kegawatan: MERAH -> KUNING -> HIJAU -> HITAM -> ID DESC
        $sql .= "
            ORDER BY 
                CASE ev.triage_category
                    WHEN 'MERAH' THEN 1
                    WHEN 'KUNING' THEN 2
                    WHEN 'HIJAU' THEN 3
                    WHEN 'HITAM' THEN 4
                    ELSE 5
                END ASC,
                ev.id DESC
        ";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return $this->query($sql, $params);
    }

    /**
     * Ambil Kunjungan IGD dengan Pagination
     */
    public function paginateVisits(array $filters = [], int $page = 1, int $perPage = 15): array {
        $sql = "
            SELECT ev.*,
                   p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.birth_date, p.insurance_type,
                   t.triage_level, t.esi_level, t.chief_complaint, t.gcs_total, t.spo2, t.pulse_rate, t.systolic_bp, t.diastolic_bp,
                   a.icd10_code, a.diagnosis_name,
                   d.full_name AS doctor_name,
                   (SELECT COUNT(*) FROM emergency_monitorings WHERE emergency_visit_id = ev.id) AS monitoring_count,
                   (SELECT is_abnormal FROM emergency_monitorings WHERE emergency_visit_id = ev.id ORDER BY id DESC LIMIT 1) AS last_monitoring_abnormal
            FROM emergency_visits ev
            LEFT JOIN patients p ON ev.patient_id = p.id
            LEFT JOIN emergency_triages t ON ev.id = t.emergency_visit_id
            LEFT JOIN emergency_assessments a ON ev.id = a.emergency_visit_id
            LEFT JOIN doctors d ON ev.doctor_id = d.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND ev.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['triage_category'])) {
            $sql .= " AND ev.triage_category = :triage";
            $params[':triage'] = $filters['triage_category'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (ev.visit_number ILIKE :s OR ev.anonymous_name ILIKE :s OR p.full_name ILIKE :s OR p.no_rm ILIKE :s)";
            $params[':s'] = '%' . $filters['search'] . '%';
        }

        $sql .= "
            ORDER BY 
                CASE ev.triage_category
                    WHEN 'MERAH' THEN 1
                    WHEN 'KUNING' THEN 2
                    WHEN 'HIJAU' THEN 3
                    WHEN 'HITAM' THEN 4
                    ELSE 5
                END ASC,
                ev.id DESC
        ";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Ambil Detail Kunjungan Lengkap IGD
     */
    public function getVisitDetail(int $visitId): ?array {
        $sql = "
            SELECT ev.*,
                   p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.birth_date, p.blood_type, 
                   p.phone_number, p.address, p.insurance_type, p.insurance_number, p.allergy_notes,
                   d.full_name AS doctor_name, d.specialization
            FROM emergency_visits ev
            LEFT JOIN patients p ON ev.patient_id = p.id
            LEFT JOIN doctors d ON ev.doctor_id = d.id
            WHERE ev.id = :id
        ";
        $res = $this->query($sql, [':id' => $visitId]);
        if (empty($res)) return null;

        $visit = $res[0];
        $visit['triage'] = $this->query("SELECT * FROM emergency_triages WHERE emergency_visit_id = :id", [':id' => $visitId])[0] ?? null;
        $visit['assessment'] = $this->query("SELECT * FROM emergency_assessments WHERE emergency_visit_id = :id", [':id' => $visitId])[0] ?? null;
        $visit['monitorings'] = $this->query("SELECT * FROM emergency_monitorings WHERE emergency_visit_id = :id ORDER BY monitoring_time ASC", [':id' => $visitId]);
        $visit['cpoe_orders'] = $this->query("SELECT * FROM cpoe_orders WHERE visit_type = 'IGD' AND reference_id = :id ORDER BY id DESC", [':id' => $visitId]);

        return $visit;
    }

    /**
     * Statistik Modul IGD
     */
    public function getIgdStats(): array {
        $sql = "
            SELECT 
                COUNT(*) FILTER (WHERE DATE(created_at) = CURRENT_DATE) AS total_today,
                COUNT(*) FILTER (WHERE triage_category = 'MERAH' AND DATE(created_at) = CURRENT_DATE) AS red,
                COUNT(*) FILTER (WHERE triage_category = 'KUNING' AND DATE(created_at) = CURRENT_DATE) AS yellow,
                COUNT(*) FILTER (WHERE triage_category = 'HIJAU' AND DATE(created_at) = CURRENT_DATE) AS green,
                COUNT(*) FILTER (WHERE triage_category = 'HITAM' AND DATE(created_at) = CURRENT_DATE) AS black,
                COUNT(*) FILTER (WHERE status IN ('TRIAGE', 'EXAMINATION', 'OBSERVATION')) AS in_progress,
                COUNT(*) FILTER (WHERE status = 'DISPOSED' AND DATE(disposition_time) = CURRENT_DATE) AS disposed
            FROM emergency_visits
        ";
        $row = $this->query($sql)[0] ?? [];
        return [
            'total_today' => (int)($row['total_today'] ?? 0),
            'red'         => (int)($row['red'] ?? 0),
            'yellow'      => (int)($row['yellow'] ?? 0),
            'green'       => (int)($row['green'] ?? 0),
            'black'       => (int)($row['black'] ?? 0),
            'in_progress' => (int)($row['in_progress'] ?? 0),
            'disposed'    => (int)($row['disposed'] ?? 0),
        ];
    }
}
