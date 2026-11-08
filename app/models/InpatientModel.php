<?php
// app/models/InpatientModel.php - Inpatient (Rawat Inap & Bed Management) Data Access Model
// Target: Supabase PostgreSQL 17

require_once __DIR__ . '/../../core/Model.php';

class InpatientModel extends Model {
    protected string $table = 'inpatient_admissions';

    /**
     * Auto-generate nomor registrasi rawat inap unik: RI-YYYYMM-XXXX
     */
    public function generateAdmissionNumber(): string {
        $prefix = 'RI-' . date('Ym') . '-';
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM public.inpatient_admissions WHERE admission_number LIKE :prefix");
        $stmt->execute([':prefix' => $prefix . '%']);
        $count = (int)$stmt->fetchColumn();
        return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Mendaftarkan admisi rawat inap baru & mengalokasikan bed
     */
    public function createAdmission(array $data): int {
        $this->db->beginTransaction();
        try {
            $roomId = (int)$data['room_id'];
            $bedId  = (int)$data['bed_id'];

            // 1. Cek apakah bed yang dipilih benar-benar TERSEDIA
            $stmtCheck = $this->db->prepare("SELECT status FROM public.beds WHERE id = :bid FOR UPDATE");
            $stmtCheck->execute([':bid' => $bedId]);
            $currentStatus = $stmtCheck->fetchColumn();

            if ($currentStatus !== 'TERSEDIA') {
                throw new Exception("Tempat tidur yang dipilih sedang terisi atau tidak tersedia.");
            }

            // 2. Generate nomor registrasi rawat inap jika belum diset
            $admNumber = !empty($data['admission_number']) ? $data['admission_number'] : $this->generateAdmissionNumber();

            // 3. Simpan ke inpatient_admissions
            $sql = "INSERT INTO public.inpatient_admissions (
                        admission_number, patient_id, doctor_id, room_id, bed_id,
                        care_class, insurance_type, admission_reason, source_type,
                        status, created_by, admission_date
                    ) VALUES (
                        :adm_no, :patient_id, :doctor_id, :room_id, :bed_id,
                        :care_class, :insurance_type, :admission_reason, :source_type,
                        :status, :created_by, CURRENT_TIMESTAMP
                    ) RETURNING id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':adm_no'           => $admNumber,
                ':patient_id'       => (int)$data['patient_id'],
                ':doctor_id'        => (int)$data['doctor_id'],
                ':room_id'          => $roomId,
                ':bed_id'           => $bedId,
                ':care_class'       => $data['care_class'] ?? 'KELAS_1',
                ':insurance_type'   => $data['insurance_type'] ?? 'UMUM',
                ':admission_reason' => $data['admission_reason'] ?? 'Perawatan Rawat Inap',
                ':source_type'      => $data['source_type'] ?? 'POLI',
                ':status'           => $data['status'] ?? 'DIRAWAT',
                ':created_by'       => $data['created_by'] ?? null,
            ]);

            $admissionId = (int)$stmt->fetchColumn();

            // 4. Update status bed menjadi TERISI
            $stmtBed = $this->db->prepare("UPDATE public.beds SET status = 'TERISI' WHERE id = :bid");
            $stmtBed->execute([':bid' => $bedId]);

            $this->db->commit();
            return $admissionId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error createAdmission: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mengambil daftar seluruh admisi rawat inap dengan filter status dan pencarian
     */
    public function getAdmissions(?string $status = null, ?string $search = null): array {
        $sql = "SELECT a.*, 
                       p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.birth_date, p.phone_number,
                       d.full_name AS doctor_name, d.specialization,
                       r.room_name, r.room_code, r.class_type, r.daily_rate,
                       b.bed_number
                FROM public.inpatient_admissions a
                JOIN public.patients p ON a.patient_id = p.id
                JOIN public.doctors d ON a.doctor_id = d.id
                JOIN public.rooms r ON a.room_id = r.id
                JOIN public.beds b ON a.bed_id = b.id
                WHERE 1=1";

        $params = [];

        if (!empty($status)) {
            $sql .= " AND a.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($search)) {
            $sql .= " AND (p.full_name ILIKE :search OR p.no_rm ILIKE :search OR a.admission_number ILIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY a.admission_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil daftar admisi rawat inap dengan pagination
     */
    public function paginateAdmissions(?string $status = null, ?string $search = null, int $page = 1, int $perPage = 15): array {
        $sql = "SELECT a.*, 
                       p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.birth_date, p.phone_number,
                       d.full_name AS doctor_name, d.specialization,
                       r.room_name, r.room_code, r.class_type, r.daily_rate,
                       b.bed_number
                FROM public.inpatient_admissions a
                JOIN public.patients p ON a.patient_id = p.id
                JOIN public.doctors d ON a.doctor_id = d.id
                JOIN public.rooms r ON a.room_id = r.id
                JOIN public.beds b ON a.bed_id = b.id
                WHERE 1=1";

        $params = [];

        if (!empty($status)) {
            $sql .= " AND a.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($search)) {
            $sql .= " AND (p.full_name ILIKE :search OR p.no_rm ILIKE :search OR a.admission_number ILIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY a.admission_date DESC";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Mengambil detail satu episode admisi rawat inap
     */
    public function getAdmissionById(int $admissionId): ?array {
        $sql = "SELECT a.*, 
                       p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.birth_date, p.phone_number,
                       p.address, p.allergy_notes, p.insurance_type AS patient_insurance, p.insurance_number,
                       d.full_name AS doctor_name, d.specialization, d.sip_number,
                       r.room_name, r.room_code, r.class_type, r.daily_rate,
                       b.bed_number
                FROM public.inpatient_admissions a
                JOIN public.patients p ON a.patient_id = p.id
                JOIN public.doctors d ON a.doctor_id = d.id
                JOIN public.rooms r ON a.room_id = r.id
                JOIN public.beds b ON a.bed_id = b.id
                WHERE a.id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $admissionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Konfirmasi penerimaan pasien di bangsal oleh perawat
     */
    public function confirmWardReception(int $admissionId, ?int $nurseId = null): bool {
        $stmt = $this->db->prepare("UPDATE public.inpatient_admissions 
                                    SET status = 'DIRAWAT_BANGSAL', updated_at = CURRENT_TIMESTAMP 
                                    WHERE id = :id");
        return $stmt->execute([':id' => $admissionId]);
    }

    /**
     * Mutasi pasien / Transfer bed antar-ruangan
     */
    public function transferBed(int $admissionId, int $toRoomId, int $toBedId, string $reason, int $transferredBy, ?string $notes = null): bool {
        $this->db->beginTransaction();
        try {
            $adm = $this->getAdmissionById($admissionId);
            if (!$adm) {
                throw new Exception("Admisi rawat inap tidak ditemukan.");
            }

            $fromRoomId = (int)$adm['room_id'];
            $fromBedId  = (int)$adm['bed_id'];

            // 1. Cek bed tujuan apakah TERSEDIA
            $stmtCheck = $this->db->prepare("SELECT status FROM public.beds WHERE id = :tbid FOR UPDATE");
            $stmtCheck->execute([':tbid' => $toBedId]);
            $targetStatus = $stmtCheck->fetchColumn();

            if ($targetStatus !== 'TERSEDIA') {
                throw new Exception("Bed tujuan transfer tidak tersedia atau sudah terisi.");
            }

            // 2. Insert ke riwayat transfer
            $stmtTrans = $this->db->prepare("INSERT INTO public.inpatient_transfers (
                admission_id, patient_id, from_room_id, from_bed_id, to_room_id, to_bed_id,
                transfer_reason, transferred_by, transferred_at, notes
            ) VALUES (
                :adm_id, :patient_id, :from_r, :from_b, :to_r, :to_b,
                :reason, :trans_by, CURRENT_TIMESTAMP, :notes
            )");

            $stmtTrans->execute([
                ':adm_id'     => $admissionId,
                ':patient_id' => $adm['patient_id'],
                ':from_r'     => $fromRoomId,
                ':from_b'     => $fromBedId,
                ':to_r'       => $toRoomId,
                ':to_b'       => $toBedId,
                ':reason'     => $reason,
                ':trans_by'   => $transferredBy,
                ':notes'      => $notes,
            ]);

            // 3. Bebaskan bed lama
            $stmtFreeOld = $this->db->prepare("UPDATE public.beds SET status = 'TERSEDIA' WHERE id = :fbid");
            $stmtFreeOld->execute([':fbid' => $fromBedId]);

            // 4. Isi bed baru
            $stmtOccupyNew = $this->db->prepare("UPDATE public.beds SET status = 'TERISI' WHERE id = :tbid");
            $stmtOccupyNew->execute([':tbid' => $toBedId]);

            // 5. Update data lokasi pasien pada inpatient_admissions
            $stmtUpdateAdm = $this->db->prepare("UPDATE public.inpatient_admissions 
                                                 SET room_id = :to_r, bed_id = :to_b, updated_at = CURRENT_TIMESTAMP 
                                                 WHERE id = :id");
            $stmtUpdateAdm->execute([
                ':to_r' => $toRoomId,
                ':to_b' => $toBedId,
                ':id'   => $admissionId,
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error transferBed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Menyimpan Resume Medis / Discharge Summary oleh Dokter DPJP
     */
    public function saveDischargeSummary(array $data): int {
        // Validasi wajib kode diagnosis ICD-10 akhir
        if (empty($data['discharge_diagnosis_icd10'])) {
            throw new InvalidArgumentException("Diagnosis akhir ICD-10 wajib diisi!");
        }

        $sql = "INSERT INTO public.inpatient_discharge_summaries (
                    admission_id, patient_id, doctor_id, admission_diagnosis_icd10,
                    discharge_diagnosis_icd10, procedures_icd9cm, hospital_course,
                    condition_at_discharge, discharge_instructions, take_home_medications,
                    control_date, finalized_at
                ) VALUES (
                    :admission_id, :patient_id, :doctor_id, :adm_icd,
                    :disc_icd, :procedures, :course,
                    :condition, :instructions, :meds,
                    :control_date, CURRENT_TIMESTAMP
                )
                ON CONFLICT (admission_id) DO UPDATE SET
                    discharge_diagnosis_icd10 = EXCLUDED.discharge_diagnosis_icd10,
                    procedures_icd9cm = EXCLUDED.procedures_icd9cm,
                    hospital_course = EXCLUDED.hospital_course,
                    condition_at_discharge = EXCLUDED.condition_at_discharge,
                    discharge_instructions = EXCLUDED.discharge_instructions,
                    take_home_medications = EXCLUDED.take_home_medications,
                    control_date = EXCLUDED.control_date,
                    finalized_at = CURRENT_TIMESTAMP
                RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':admission_id' => (int)$data['admission_id'],
            ':patient_id'   => (int)$data['patient_id'],
            ':doctor_id'    => (int)$data['doctor_id'],
            ':adm_icd'      => $data['admission_diagnosis_icd10'] ?? null,
            ':disc_icd'     => $data['discharge_diagnosis_icd10'],
            ':procedures'   => $data['procedures_icd9cm'] ?? null,
            ':course'       => $data['hospital_course'] ?? 'Perawatan selesai dengan perbaikan klinis.',
            ':condition'    => $data['condition_at_discharge'] ?? 'MEMBAIK',
            ':instructions' => $data['discharge_instructions'] ?? null,
            ':meds'         => $data['take_home_medications'] ?? null,
            ':control_date' => !empty($data['control_date']) ? $data['control_date'] : null,
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Mengambil resume medis kepulangan rawat inap
     */
    public function getDischargeSummary(int $admissionId): ?array {
        $stmt = $this->db->prepare("SELECT s.*, COALESCE(icd.description_id, icd.description_en) AS diagnosis_description
                                    FROM public.inpatient_discharge_summaries s
                                    LEFT JOIN public.icd_diagnoses icd ON s.discharge_diagnosis_icd10 = icd.code
                                    WHERE s.admission_id = :aid LIMIT 1");
        $stmt->execute([':aid' => $admissionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Konfirmasi kepulangan pasien (Discharge) & pembebasan bed otomatis
     */
    public function confirmDischarge(int $admissionId): bool {
        $this->db->beginTransaction();
        try {
            $adm = $this->getAdmissionById($admissionId);
            if (!$adm) {
                throw new Exception("Admisi rawat inap tidak ditemukan.");
            }

            // 1. Cek apakah Resume Medis sudah ada
            $summary = $this->getDischargeSummary($admissionId);
            if (!$summary) {
                throw new Exception("Resume Medis / Discharge Summary wajib diisi oleh dokter DPJP sebelum kepulangan dikonfirmasi.");
            }

            $bedId = (int)$adm['bed_id'];

            // 2. Update status admisi menjadi DISCHARGED
            $stmtAdm = $this->db->prepare("UPDATE public.inpatient_admissions 
                                           SET status = 'DISCHARGED', discharge_date = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP 
                                           WHERE id = :id");
            $stmtAdm->execute([':id' => $admissionId]);

            // 3. Bebaskan tempat tidur menjadi TERSEDIA
            $stmtBed = $this->db->prepare("UPDATE public.beds SET status = 'TERSEDIA' WHERE id = :bid");
            $stmtBed->execute([':bid' => $bedId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error confirmDischarge: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mengambil daftar tempat tidur yang tersedia
     */
    public function getAvailableBeds(?string $class = null, ?int $roomId = null): array {
        $sql = "SELECT b.*, r.room_name, r.room_code, r.class_type, r.daily_rate
                FROM public.beds b
                JOIN public.rooms r ON b.room_id = r.id
                WHERE b.status = 'TERSEDIA' AND r.is_active = TRUE";

        $params = [];

        if (!empty($class)) {
            $sql .= " AND r.class_type = :class";
            $params[':class'] = $class;
        }

        if (!empty($roomId)) {
            $sql .= " AND b.room_id = :rid";
            $params[':rid'] = $roomId;
        }

        $sql .= " ORDER BY r.class_type ASC, r.room_name ASC, b.bed_number ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ringkasan okupansi tempat tidur rumah sakit
     */
    public function getBedOccupancyOverview(): array {
        $sql = "SELECT r.id AS room_id, r.room_code, r.room_name, r.class_type, r.daily_rate,
                       COUNT(b.id) AS total_beds,
                       SUM(CASE WHEN b.status = 'TERISI' THEN 1 ELSE 0 END) AS occupied_beds,
                       SUM(CASE WHEN b.status = 'TERSEDIA' THEN 1 ELSE 0 END) AS available_beds
                FROM public.rooms r
                LEFT JOIN public.beds b ON r.id = b.room_id
                WHERE r.is_active = TRUE
                GROUP BY r.id, r.room_code, r.room_name, r.class_type, r.daily_rate
                ORDER BY r.class_type ASC, r.room_code ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
