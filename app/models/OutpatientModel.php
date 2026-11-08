<?php
// app/models/OutpatientModel.php - Outpatient (Rawat Jalan) Data Access Model
// Target: Supabase PostgreSQL 17

require_once __DIR__ . '/../../core/Model.php';

class OutpatientModel extends Model {
    protected string $table = 'visits';
    public ?string $lastError = null;

    /**
     * Mengambil seluruh antrean kunjungan rawat jalan dengan filter tanggal, poli, atau status
     */
    public function getOutpatientQueue(?string $date = null, ?int $poliId = null, ?string $status = null): array {
        $sql = "SELECT v.*, 
                       p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.birth_date, p.insurance_type AS patient_insurance,
                       d.full_name AS doctor_name, d.specialization,
                       pol.name AS policlinic_name, pol.queue_prefix
                FROM public.visits v
                JOIN public.patients p ON v.patient_id = p.id
                JOIN public.doctors d ON v.doctor_id = d.id
                JOIN public.policlinics pol ON v.policlinic_id = pol.id
                WHERE 1=1";

        $params = [];

        if (!empty($date)) {
            $sql .= " AND v.visit_date = :vdate";
            $params[':vdate'] = $date;
        } else {
            $sql .= " AND v.visit_date = CURRENT_DATE";
        }

        if (!empty($poliId)) {
            $sql .= " AND v.policlinic_id = :poli";
            $params[':poli'] = $poliId;
        }

        if (!empty($status)) {
            $sql .= " AND UPPER(v.status) = UPPER(:status)";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY v.queue_number ASC, v.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil detail satu kunjungan rawat jalan
     */
    public function getVisitDetail(int $visitId): ?array {
        $sql = "SELECT v.*, 
                       p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.birth_date, p.phone_number, p.address,
                       p.allergy_notes, p.insurance_type AS patient_insurance, p.insurance_number AS patient_insurance_no,
                       d.full_name AS doctor_name, d.specialization, d.sip_number,
                       pol.name AS policlinic_name, pol.queue_prefix
                FROM public.visits v
                JOIN public.patients p ON v.patient_id = p.id
                JOIN public.doctors d ON v.doctor_id = d.id
                JOIN public.policlinics pol ON v.policlinic_id = pol.id
                WHERE v.id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $visitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Mendaftarkan / mengaktifkan kunjungan rawat jalan baru
     */
    public function registerVisit(array $data): int {
        // Generate booking code & queue number jika belum ada
        $date = $data['visit_date'] ?? date('Y-m-d');
        $policlinicId = (int)$data['policlinic_id'];

        // Ambil prefix poli
        $stmtPrefix = $this->db->prepare("SELECT queue_prefix FROM public.policlinics WHERE id = :pid");
        $stmtPrefix->execute([':pid' => $policlinicId]);
        $prefix = $stmtPrefix->fetchColumn() ?: 'A';

        // Hitung nomor antrean hari ini
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM public.visits WHERE visit_date = :vdate AND policlinic_id = :pid");
        $stmtCount->execute([':vdate' => $date, ':pid' => $policlinicId]);
        $queueIndex = ((int)$stmtCount->fetchColumn()) + 1;
        $queueNumber = $prefix . '-' . str_pad($queueIndex, 3, '0', STR_PAD_LEFT);
        $bookingCode = 'OPD-' . date('Ymd', strtotime($date)) . '-' . strtoupper(substr(uniqid(), -5));

        $sql = "INSERT INTO public.visits (
                    booking_code, queue_number, patient_id, doctor_id, policlinic_id,
                    visit_date, payment_type, insurance_card_no, referral_no, status, notes
                ) VALUES (
                    :booking_code, :queue_number, :patient_id, :doctor_id, :policlinic_id,
                    :visit_date, :payment_type, :insurance_card_no, :referral_no, :status, :notes
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':booking_code'       => $bookingCode,
            ':queue_number'       => $queueNumber,
            ':patient_id'         => $data['patient_id'],
            ':doctor_id'          => $data['doctor_id'],
            ':policlinic_id'      => $policlinicId,
            ':visit_date'         => $date,
            ':payment_type'       => $data['payment_type'] ?? 'UMUM',
            ':insurance_card_no'  => $data['insurance_card_no'] ?? null,
            ':referral_no'        => $data['referral_no'] ?? null,
            ':status'             => $data['status'] ?? 'TERDAFTAR',
            ':notes'              => $data['notes'] ?? null,
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Memperbarui status kunjungan
     */
    public function updateStatus(int $visitId, string $status): bool {
        $stmt = $this->db->prepare("UPDATE public.visits SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $visitId]);
    }

    /**
     * Menyimpan Asesmen Awal Keperawatan Poli & Vital Signs
     */
    public function saveNurseInitialAssessment(int $visitId, array $data, int $nurseId): bool {
        $this->db->beginTransaction();
        try {
            $visit = $this->getVisitDetail($visitId);
            if (!$visit) {
                throw new Exception("Kunjungan tidak ditemukan.");
            }

            $patientId = $visit['patient_id'];

            // 1. Simpan ke tabel nursing_assessments
            $stmtAss = $this->db->prepare("INSERT INTO public.nursing_assessments (
                patient_id, encounter_type, encounter_id, nurse_id,
                chief_complaint, general_condition, consciousness,
                fall_risk_score, fall_risk_category, pain_score, functional_status
            ) VALUES (
                :patient_id, 'OUTPATIENT', :encounter_id, :nurse_id,
                :chief_complaint, :general_condition, :consciousness,
                :fall_risk_score, :fall_risk_category, :pain_score, :functional_status
            )");

            $resAss = $stmtAss->execute([
                ':patient_id'          => $patientId,
                ':encounter_id'        => $visitId,
                ':nurse_id'            => $nurseId,
                ':chief_complaint'     => $data['chief_complaint'] ?? 'Keluhan rawat jalan',
                ':general_condition'   => $data['general_condition'] ?? 'Baik',
                ':consciousness'       => $data['consciousness'] ?? 'COMPOS_MENTIS',
                ':fall_risk_score'     => (int)($data['fall_risk_score'] ?? 0),
                ':fall_risk_category'  => $data['fall_risk_category'] ?? 'RENDAH',
                ':pain_score'          => (int)($data['pain_score'] ?? 0),
                ':functional_status'   => $data['functional_status'] ?? 'MANDIRI',
            ]);
            if (!$resAss) {
                throw new Exception("Step 1 failed: " . json_encode($stmtAss->errorInfo()));
            }

            // 2. Simpan ke tabel nursing_vital_signs
            $stmtVs = $this->db->prepare("INSERT INTO public.nursing_vital_signs (
                patient_id, encounter_type, encounter_id, nurse_id,
                systolic_bp, diastolic_bp, pulse_rate, respiratory_rate, temperature, spo2, notes
            ) VALUES (
                :patient_id, 'OUTPATIENT', :encounter_id, :nurse_id,
                :systolic, :diastolic, :pulse, :rr, :temp, :spo2, :notes
            )");

            $resVs = $stmtVs->execute([
                ':patient_id' => $patientId,
                ':encounter_id' => $visitId,
                ':nurse_id'   => $nurseId,
                ':systolic'   => (int)($data['systolic_bp'] ?? 120),
                ':diastolic'  => (int)($data['diastolic_bp'] ?? 80),
                ':pulse'      => (int)($data['pulse_rate'] ?? 80),
                ':rr'         => (int)($data['respiratory_rate'] ?? 20),
                ':temp'       => (string)number_format((float)($data['temperature'] ?? 36.5), 1, '.', ''),
                ':spo2'       => (int)($data['spo2'] ?? 98),
                ':notes'      => $data['vital_notes'] ?? null,
            ]);
            if (!$resVs) {
                throw new Exception("Step 2 failed: " . json_encode($stmtVs->errorInfo()));
            }

            // 3. Update status kunjungan menjadi 'DIPERIKSA'
            $resUp = $this->updateStatus($visitId, 'DIPERIKSA');
            if (!$resUp) {
                throw new Exception("Step 3 failed: " . json_encode($this->db->errorInfo()));
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            $this->lastError = $e->getMessage();
            error_log("Error saveNurseInitialAssessment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Membuat rencana jadwal kontrol ulang
     */
    public function saveFollowUpControl(array $data): int {
        $sql = "INSERT INTO public.outpatient_controls (
                    visit_id, patient_id, doctor_id, control_date, notes, status
                ) VALUES (
                    :visit_id, :patient_id, :doctor_id, :control_date, :notes, :status
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':visit_id'     => $data['visit_id'] ?? null,
            ':patient_id'   => $data['patient_id'],
            ':doctor_id'    => $data['doctor_id'],
            ':control_date' => $data['control_date'],
            ':notes'        => $data['notes'] ?? null,
            ':status'       => $data['status'] ?? 'SCHEDULED',
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Mengambil daftar rencana kontrol pasien
     */
    public function getControlsByPatient(int $patientId): array {
        $sql = "SELECT c.*, d.full_name AS doctor_name, d.specialization, pol.name AS policlinic_name
                FROM public.outpatient_controls c
                JOIN public.doctors d ON c.doctor_id = d.id
                JOIN public.policlinics pol ON d.policlinic_id = pol.id
                WHERE c.patient_id = :pid
                ORDER BY c.control_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
