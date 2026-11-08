<?php
// app/models/NursingModel.php - Nursing Care & Station Data Access Model
// Target: Supabase PostgreSQL 17

require_once __DIR__ . '/../../core/Model.php';

class NursingModel extends Model {
    protected string $table = 'nursing_assessments';

    /**
     * Menyimpan Pengkajian Keperawatan Lengkap
     */
    public function saveAssessment(array $data): int {
        $sql = "INSERT INTO public.nursing_assessments (
                    patient_id, encounter_type, encounter_id, nurse_id,
                    chief_complaint, general_condition, consciousness,
                    fall_risk_score, fall_risk_category, pain_score, functional_status,
                    created_at, updated_at
                ) VALUES (
                    :patient_id, :encounter_type, :encounter_id, :nurse_id,
                    :chief_complaint, :general_condition, :consciousness,
                    :fall_risk_score, :fall_risk_category, :pain_score, :functional_status,
                    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':patient_id'          => (int)$data['patient_id'],
            ':encounter_type'      => $data['encounter_type'] ?? 'INPATIENT',
            ':encounter_id'        => (int)$data['encounter_id'],
            ':nurse_id'            => !empty($data['nurse_id']) ? (int)$data['nurse_id'] : null,
            ':chief_complaint'     => $data['chief_complaint'],
            ':general_condition'   => $data['general_condition'] ?? 'Sedang',
            ':consciousness'       => $data['consciousness'] ?? 'COMPOS_MENTIS',
            ':fall_risk_score'     => (int)($data['fall_risk_score'] ?? 0),
            ':fall_risk_category'  => $data['fall_risk_category'] ?? 'RENDAH',
            ':pain_score'          => (int)($data['pain_score'] ?? 0),
            ':functional_status'   => $data['functional_status'] ?? 'MANDIRI',
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Mengambil data pengkajian keperawatan terakhir pasien
     */
    public function getAssessment(int $patientId, string $encounterType, int $encounterId): ?array {
        $stmt = $this->db->prepare("SELECT a.*, u.full_name AS nurse_name
                                    FROM public.nursing_assessments a
                                    LEFT JOIN public.users u ON a.nurse_id = u.id
                                    WHERE a.patient_id = :pid AND a.encounter_type = :etype AND a.encounter_id = :eid
                                    ORDER BY a.created_at DESC LIMIT 1");
        $stmt->execute([
            ':pid'   => $patientId,
            ':etype' => $encounterType,
            ':eid'   => $encounterId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Menyimpan Observasi Tanda-Tanda Vital (TTV)
     */
    public function saveVitalSigns(array $data): int {
        $sql = "INSERT INTO public.nursing_vital_signs (
                    patient_id, encounter_type, encounter_id, nurse_id,
                    systolic_bp, diastolic_bp, pulse_rate, respiratory_rate,
                    temperature, spo2, recorded_at, notes
                ) VALUES (
                    :patient_id, :encounter_type, :encounter_id, :nurse_id,
                    :systolic_bp, :diastolic_bp, :pulse_rate, :respiratory_rate,
                    :temperature, :spo2, CURRENT_TIMESTAMP, :notes
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':patient_id'        => (int)$data['patient_id'],
            ':encounter_type'    => $data['encounter_type'] ?? 'INPATIENT',
            ':encounter_id'      => (int)$data['encounter_id'],
            ':nurse_id'          => !empty($data['nurse_id']) ? (int)$data['nurse_id'] : null,
            ':systolic_bp'       => (int)$data['systolic_bp'],
            ':diastolic_bp'      => (int)$data['diastolic_bp'],
            ':pulse_rate'        => (int)$data['pulse_rate'],
            ':respiratory_rate'  => (int)$data['respiratory_rate'],
            ':temperature'       => (string)number_format((float)($data['temperature'] ?? 36.5), 1, '.', ''),
            ':spo2'              => (int)$data['spo2'],
            ':notes'             => $data['notes'] ?? null,
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Mengambil riwayat observasi tanda-tanda vital pasien
     */
    public function getVitalSignsHistory(int $patientId, string $encounterType, int $encounterId): array {
        $stmt = $this->db->prepare("SELECT v.*, u.full_name AS nurse_name
                                    FROM public.nursing_vital_signs v
                                    LEFT JOIN public.users u ON v.nurse_id = u.id
                                    WHERE v.patient_id = :pid AND v.encounter_type = :etype AND v.encounter_id = :eid
                                    ORDER BY v.recorded_at DESC");
        $stmt->execute([
            ':pid'   => $patientId,
            ':etype' => $encounterType,
            ':eid'   => $encounterId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Menyimpan Catatan Perkembangan Keperawatan (Nursing Notes / SOAP)
     */
    public function saveNursingNote(array $data): int {
        $sql = "INSERT INTO public.nursing_notes (
                    patient_id, encounter_type, encounter_id, nurse_id,
                    shift, subjective, objective, nursing_diagnosis,
                    interventions, evaluation, recorded_at, is_finalized
                ) VALUES (
                    :patient_id, :encounter_type, :encounter_id, :nurse_id,
                    :shift, :subjective, :objective, :nursing_diagnosis,
                    :interventions, :evaluation, CURRENT_TIMESTAMP, :is_finalized
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':patient_id'         => (int)$data['patient_id'],
            ':encounter_type'     => $data['encounter_type'] ?? 'INPATIENT',
            ':encounter_id'       => (int)$data['encounter_id'],
            ':nurse_id'           => !empty($data['nurse_id']) ? (int)$data['nurse_id'] : null,
            ':shift'              => $data['shift'] ?? 'PAGI',
            ':subjective'         => $data['subjective'],
            ':objective'          => $data['objective'],
            ':nursing_diagnosis'  => $data['nursing_diagnosis'],
            ':interventions'      => $data['interventions'],
            ':evaluation'         => $data['evaluation'],
            ':is_finalized'       => !empty($data['is_finalized']) ? 'true' : 'false',
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Mengambil seluruh Catatan Perkembangan Keperawatan
     */
    public function getNursingNotes(int $patientId, string $encounterType, int $encounterId): array {
        $stmt = $this->db->prepare("SELECT n.*, u.full_name AS nurse_name
                                    FROM public.nursing_notes n
                                    LEFT JOIN public.users u ON n.nurse_id = u.id
                                    WHERE n.patient_id = :pid AND n.encounter_type = :etype AND n.encounter_id = :eid
                                    ORDER BY n.recorded_at DESC");
        $stmt->execute([
            ':pid'   => $patientId,
            ':etype' => $encounterType,
            ':eid'   => $encounterId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Menyimpan catatan pemberian obat (Medication Administration Record - MAR)
     */
    public function saveMedicationAdministration(array $data): int {
        $sql = "INSERT INTO public.medication_administrations (
                    patient_id, admission_id, nurse_id, medicine_name,
                    dosage, route, planned_time, given_time, patient_response,
                    status, notes
                ) VALUES (
                    :patient_id, :admission_id, :nurse_id, :medicine_name,
                    :dosage, :route, :planned_time, CURRENT_TIMESTAMP, :patient_response,
                    :status, :notes
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':patient_id'        => (int)$data['patient_id'],
            ':admission_id'      => !empty($data['admission_id']) ? (int)$data['admission_id'] : null,
            ':nurse_id'          => !empty($data['nurse_id']) ? (int)$data['nurse_id'] : null,
            ':medicine_name'     => $data['medicine_name'],
            ':dosage'            => $data['dosage'],
            ':route'             => $data['route'] ?? 'Oral',
            ':planned_time'      => !empty($data['planned_time']) ? $data['planned_time'] : date('Y-m-d H:i:s'),
            ':patient_response'  => $data['patient_response'] ?? 'Tidak ada reaksi alergi/efek samping',
            ':status'            => $data['status'] ?? 'GIVEN',
            ':notes'             => $data['notes'] ?? null,
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Mengambil riwayat lembar MAR pasien rawat inap
     */
    public function getMarRecords(int $patientId, ?int $admissionId = null): array {
        $sql = "SELECT m.*, u.full_name AS nurse_name
                FROM public.medication_administrations m
                LEFT JOIN public.users u ON m.nurse_id = u.id
                WHERE m.patient_id = :pid";

        $params = [':pid' => $patientId];

        if (!empty($admissionId)) {
            $sql .= " AND m.admission_id = :aid";
            $params[':aid'] = $admissionId;
        }

        $sql .= " ORDER BY m.planned_time DESC, m.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Membuat eskalasi klinis keperawatan ke dokter DPJP
     */
    public function createEscalation(array $data): int {
        $sql = "INSERT INTO public.clinical_escalations (
                    patient_id, encounter_type, encounter_id, nurse_id,
                    doctor_id, escalation_reason, vital_signs_summary,
                    doctor_instruction, escalated_at, status
                ) VALUES (
                    :patient_id, :encounter_type, :encounter_id, :nurse_id,
                    :doctor_id, :escalation_reason, :vital_signs_summary,
                    :doctor_instruction, CURRENT_TIMESTAMP, :status
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':patient_id'           => (int)$data['patient_id'],
            ':encounter_type'       => $data['encounter_type'] ?? 'INPATIENT',
            ':encounter_id'         => (int)$data['encounter_id'],
            ':nurse_id'             => !empty($data['nurse_id']) ? (int)$data['nurse_id'] : null,
            ':doctor_id'            => !empty($data['doctor_id']) ? (int)$data['doctor_id'] : null,
            ':escalation_reason'    => $data['escalation_reason'],
            ':vital_signs_summary'  => $data['vital_signs_summary'],
            ':doctor_instruction'   => $data['doctor_instruction'] ?? null,
            ':status'               => $data['status'] ?? 'REPORTED',
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Menanggapi eskalasi klinis oleh dokter DPJP
     */
    public function respondEscalation(int $escalationId, int $doctorId, string $instruction): bool {
        $stmt = $this->db->prepare("UPDATE public.clinical_escalations 
                                    SET doctor_id = :did, doctor_instruction = :inst, status = 'RESOLVED'
                                    WHERE id = :id");
        return $stmt->execute([
            ':did'  => $doctorId,
            ':inst' => $instruction,
            ':id'   => $escalationId,
        ]);
    }

    /**
     * Mengambil daftar eskalasi klinis
     */
    public function getEscalations(?int $patientId = null, ?string $status = null): array {
        $sql = "SELECT e.*, 
                       p.full_name AS patient_name, p.no_rm,
                       u.full_name AS nurse_name,
                       d.full_name AS doctor_name
                FROM public.clinical_escalations e
                JOIN public.patients p ON e.patient_id = p.id
                LEFT JOIN public.users u ON e.nurse_id = u.id
                LEFT JOIN public.doctors d ON e.doctor_id = d.id
                WHERE 1=1";

        $params = [];

        if (!empty($patientId)) {
            $sql .= " AND e.patient_id = :pid";
            $params[':pid'] = $patientId;
        }

        if (!empty($status)) {
            $sql .= " AND e.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY e.escalated_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
