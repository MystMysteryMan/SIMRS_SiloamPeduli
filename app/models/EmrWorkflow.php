<?php
require_once __DIR__ . '/../services/ClinicalWorkflow.php';

/** New EMR workflow; legacy saveMedicalRecord remains unchanged for Outpatient. */
trait EmrWorkflow {
    public function searchPatients(string $search, int $page = 1): array {
        return $this->paginate("SELECT p.*, (SELECT COUNT(*) FROM appointments a WHERE a.patient_id=p.id) AS visit_count
            FROM patients p WHERE (p.no_rm ILIKE :rm OR p.nik ILIKE :nik OR p.full_name ILIKE :name)
            ORDER BY p.full_name, p.id", [':rm' => "%$search%", ':nik' => "%$search%", ':name' => "%$search%"], $page, 15);
    }
    public function clinicalQueue(array $filters, int $page = 1): array {
        $where='WHERE TRUE'; $params=[];
        foreach (['policlinic_id'=>'a.policlinic_id','doctor_id'=>'a.doctor_id','date'=>'a.appointment_date','status'=>'a.status'] as $key=>$column) {
            if (!empty($filters[$key])) { $where.=" AND $column=:$key"; $params[":$key"]=$filters[$key]; }
        }
        if (($filters['q'] ?? '') !== '') {
            $where.=' AND (p.full_name ILIKE :name OR p.no_rm ILIKE :rm OR p.nik ILIKE :nik)';
            foreach (['name','rm','nik'] as $key) $params[":$key"]='%'.$filters['q'].'%';
        }
        return $this->paginate("SELECT a.*,p.no_rm,p.full_name AS patient_name,p.gender,p.birth_date,p.insurance_type,
            d.full_name AS doctor_name,pol.name AS policlinic_name,mr.icd10_code,mr.diagnosis_name
            FROM appointments a JOIN patients p ON p.id=a.patient_id JOIN doctors d ON d.id=a.doctor_id
            JOIN policlinics pol ON pol.id=a.policlinic_id
            LEFT JOIN LATERAL (SELECT icd10_code,diagnosis_name FROM medical_records WHERE appointment_id=a.id ORDER BY id DESC LIMIT 1) mr ON TRUE
            $where ORDER BY a.appointment_date DESC,a.queue_number,a.id",$params,$page,15);
    }
    public function clinicalStats(): array {
        return $this->query("SELECT (SELECT COUNT(*) FROM patients) AS total,
            COUNT(*) FILTER(WHERE status='SCHEDULED') AS pending,
            COUNT(*) FILTER(WHERE status='IN_PROGRESS') AS process,
            COUNT(*) FILTER(WHERE status='COMPLETED') AS completed FROM appointments")[0];
    }
    public function getPatientVisits(int $patientId): array {
        return $this->query("SELECT a.*, d.full_name AS doctor_name, d.user_id AS doctor_user_id,
            p.name AS policlinic_name, mr.id AS record_id, mr.status AS record_status
            FROM appointments a JOIN doctors d ON d.id=a.doctor_id JOIN policlinics p ON p.id=a.policlinic_id
            LEFT JOIN LATERAL (SELECT id,status FROM medical_records WHERE appointment_id=a.id ORDER BY id DESC LIMIT 1) mr ON TRUE
            WHERE a.patient_id=:pid ORDER BY a.appointment_date DESC, a.id DESC", [':pid' => $patientId]);
    }
    public function canDocument(array $appointment): bool {
        if ($appointment['status'] === 'CANCELLED') return false;
        if (ClinicalWorkflow::nurse()) return true;
        if (!ClinicalWorkflow::doctor()) return false;
        $rows = $this->query('SELECT user_id FROM doctors WHERE id = ?', [(int)$appointment['doctor_id']]);
        return (int)($rows[0]['user_id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0);
    }
    public function clinicalInput(array $data): array {
        $appointment = $this->getAppointmentDetail((int)($data['appointment_id'] ?? 0));
        if (!$appointment || (int)$appointment['patient_id'] !== (int)($data['patient_id'] ?? 0)) throw new DomainException('Pasien dan kunjungan tidak cocok. Pilih ulang kunjungan pasien.');
        if (!$this->canDocument($appointment)) throw new DomainException('Kunjungan ini tidak dapat dicatat oleh peran/dokter yang sedang masuk.');
        $nurse = ClinicalWorkflow::nurse();
        $out = ClinicalWorkflow::vitals($data);
        $out['appointment_id'] = (int)$appointment['id'];
        $out['patient_id'] = (int)$appointment['patient_id'];
        $out['record_version'] = ClinicalWorkflow::text($data['record_version'] ?? '', 100);
        if ($nurse) {
            foreach (['subjective', 'objective', 'assessment', 'plan', 'icd10_code', 'order_lab', 'order_radiology'] as $key) {
                if (!empty($data[$key])) throw new DomainException('SOAP, diagnosis, dan order penunjang hanya dapat diisi dokter.');
            }
            $out['nurse_notes'] = ClinicalWorkflow::text($data['nurse_notes'] ?? '');
            if ($out['nurse_notes'] === '') throw new DomainException('Catatan informasi medis/keperawatan wajib diisi.');
        } else {
            $out['icd10_code'] = ClinicalWorkflow::text($data['icd10_code'] ?? '', 255);
            if ($out['icd10_code'] === '') throw new DomainException('Kode ICD-10 Wajib Diisi!');
            $icd = $this->query('SELECT description_id FROM icd_diagnoses WHERE code=? AND is_active=TRUE', [$out['icd10_code']]);
            if (!$icd) throw new DomainException('Pilih kode ICD-10 yang tersedia pada referensi diagnosis.');
            foreach (['subjective' => 'Subjective', 'objective' => 'Objective', 'assessment' => 'Assessment', 'plan' => 'Plan'] as $key => $label) {
                $out[$key] = ClinicalWorkflow::text($data[$key] ?? '');
                if ($out[$key] === '') throw new DomainException("Bagian SOAP $label wajib diisi.");
            }
            $out['diagnosis_name'] = ClinicalWorkflow::text($data['diagnosis_name'] ?? '', 255) ?: $icd[0]['description_id'];
            $out['clinical_notes'] = ClinicalWorkflow::text($data['clinical_notes'] ?? '');
        }
        $out['new_allergy'] = ClinicalWorkflow::text($data['new_allergy'] ?? '', 150);
        $out['allergy_reaction'] = ClinicalWorkflow::text($data['allergy_reaction'] ?? '');
        $out['allergy_severity'] = in_array($data['allergy_severity'] ?? '', ['RINGAN','SEDANG','BERAT'], true) ? $data['allergy_severity'] : 'SEDANG';
        return $out;
    }
    public function saveClinicalRecord(array $data): array {
        return ClinicalWorkflow::transaction($this->db, function () use ($data) {
            $this->query('SELECT id FROM appointments WHERE id=? FOR UPDATE', [(int)($data['appointment_id'] ?? 0)]);
            $input = $this->clinicalInput($data);
            $existing = $this->getRecordByAppointment($input['appointment_id']);
            if ($existing && (int)$existing['patient_id'] !== $input['patient_id']) throw new DomainException('Identitas rekam medis tidak cocok dengan pasien kunjungan.');
            if (($existing['updated_at'] ?? '') !== $input['record_version']) throw new DomainException('Rekam medis telah berubah. Buka kembali formulir dan tinjau data terbaru.');
            $nurse = ClinicalWorkflow::nurse();
            $columns = ['vital_bp','vital_temp','vital_pulse','vital_rr','weight_kg','height_cm','bmi'];
            $columns = array_merge($columns, $nurse ? ['nurse_notes'] : ['subjective','objective','assessment','plan','icd10_code','diagnosis_name','clinical_notes']);
            $values = array_map(fn($key) => $input[$key], $columns);
            $uid = (int)$_SESSION['user_id'];
            if ($existing) {
                $sets = implode(', ', array_map(fn($key) => "$key=?", $columns));
                $actor = $nurse ? 'nurse_id=?, assessed_at=NOW()' : "doctor_id=?, examined_at=NOW(), status='COMPLETED'";
                $this->execute("UPDATE medical_records SET $sets, $actor, updated_at=NOW() WHERE id=?", array_merge($values, [$uid, $existing['id']]));
                $recordId = (int)$existing['id'];
            } else {
                $appointment = $this->getAppointmentDetail($input['appointment_id']);
                $doctor = $this->query('SELECT user_id FROM doctors WHERE id=?', [$appointment['doctor_id']])[0]['user_id'] ?? null;
                if (!$doctor) throw new DomainException('Dokter kunjungan belum terhubung dengan akun pengguna.');
                $columns = array_merge($columns, ['patient_id','appointment_id','doctor_id','status']);
                $values = array_merge($values, [$input['patient_id'], $input['appointment_id'], $nurse ? $doctor : $uid, $nurse ? 'DRAFT' : 'COMPLETED']);
                $q = $this->db->prepare('INSERT INTO medical_records (' . implode(',', $columns) . ') VALUES (' . implode(',', array_fill(0,count($values),'?')) . ') RETURNING id');
                $q->execute($values); $recordId = (int)$q->fetchColumn();
                $this->execute($nurse ? 'UPDATE medical_records SET nurse_id=?,assessed_at=NOW() WHERE id=?' : 'UPDATE medical_records SET doctor_id=?,examined_at=NOW() WHERE id=?', [$uid,$recordId]);
            }
            if ($input['new_allergy'] !== '') {
                $this->execute('INSERT INTO patient_allergies (patient_id,allergen_name,severity,reaction) SELECT ?,?,?,? WHERE NOT EXISTS (SELECT 1 FROM patient_allergies WHERE patient_id=? AND allergen_name=? AND reaction=?)', [$input['patient_id'],$input['new_allergy'],$input['allergy_severity'],$input['allergy_reaction'],$input['patient_id'],$input['new_allergy'],$input['allergy_reaction']]);
            }
            if (!$nurse) $this->execute("UPDATE appointments SET status='COMPLETED',updated_at=NOW() WHERE id=?", [$input['appointment_id']]);
            ClinicalWorkflow::audit($this->db, $nurse ? 'SAVE_EMR_NURSING' : 'SAVE_EMR', 'EMR', "Pasien #{$input['patient_id']}; kunjungan #{$input['appointment_id']}; EMR #$recordId");
            return ['success'=>true,'record_id'=>$recordId,'message'=>'Rekam medis berhasil disimpan.'];
        });
    }
    public function getAttachments(int $patientId, ?int $appointmentId = null): array {
        return $this->query('SELECT f.id,f.filename,f.byte_size,f.created_at,f.appointment_id,u.full_name AS uploader FROM emr_attachments f JOIN users u ON u.id=f.uploaded_by WHERE f.patient_id=?' . ($appointmentId ? ' AND f.appointment_id=?' : '') . ' ORDER BY f.id DESC', $appointmentId ? [$patientId,$appointmentId] : [$patientId]);
    }
    public function saveAttachment(int $appointmentId, int $patientId, array $file): array {
        return ClinicalWorkflow::transaction($this->db, function () use ($appointmentId,$patientId,$file) {
            $appointment = $this->getAppointmentDetail($appointmentId);
            if (!$appointment || (int)$appointment['patient_id'] !== $patientId || !$this->canDocument($appointment)) throw new DomainException('Pasien/kunjungan atau kewenangan tidak sesuai.');
            $f = ClinicalWorkflow::file($file, true);
            $this->execute('INSERT INTO emr_attachments (patient_id,appointment_id,uploaded_by,filename,mime_type,content_base64,byte_size) VALUES (?,?,?,?,?,?,?)', [$patientId,$appointmentId,$_SESSION['user_id'],$f['filename'],$f['mime_type'],$f['content_base64'],$f['byte_size']]);
            ClinicalWorkflow::audit($this->db,'UPLOAD_EMR_ATTACHMENT','EMR',"Lampiran pasien #$patientId / kunjungan #$appointmentId");
            return ['success'=>true,'message'=>'Lampiran berhasil disimpan dan terhubung dengan kunjungan pasien.'];
        });
    }
}
