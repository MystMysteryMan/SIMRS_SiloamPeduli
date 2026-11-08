<?php
// app/controllers/NursingController.php - Dokumentasi Asuhan Keperawatan Terpadu (SOP/SIMRS/013/2026)

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/NursingModel.php';
require_once __DIR__ . '/../models/InpatientModel.php';
require_once __DIR__ . '/../models/PatientModel.php';
require_once __DIR__ . '/../models/DoctorModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';

class NursingController extends Controller {

    public function __construct() {
        $this->requireRole(['admin', 'perawat', 'kepala_ruang', 'dokter']);
    }

    /**
     * Dashboard Nursing Station & Daftar Pasien Aktif
     */
    public function index(): void {
        $inpatientModel = new InpatientModel();
        $nursingModel = new NursingModel();

        // Ambil daftar pasien yang sedang dirawat inap di bangsal
        $activeInpatients = $inpatientModel->getAdmissions('DIRAWAT_BANGSAL');
        $escalations = $nursingModel->getEscalations(null, 'REPORTED');

        $this->view('nursing/index', [
            'activeInpatients' => $activeInpatients,
            'escalations'      => $escalations,
        ]);
    }

    /**
     * Lembar Asuhan Keperawatan Terpadu Pasien (Chart View)
     */
    public function patient(): void {
        $patientId = (int)($_GET['patient_id'] ?? 0);
        $encounterType = $_GET['encounter_type'] ?? 'INPATIENT';
        $encounterId = (int)($_GET['encounter_id'] ?? 0);

        $patientModel = new PatientModel();
        $patient = $patientModel->getPatientById($patientId);

        if (!$patient) {
            $this->setFlash('danger', 'Pasien tidak ditemukan.');
            $this->redirect('index.php?c=nursing&a=index');
            return;
        }

        $nursingModel = new NursingModel();
        $assessment = $nursingModel->getAssessment($patientId, $encounterType, $encounterId);
        $vitalSigns = $nursingModel->getVitalSignsHistory($patientId, $encounterType, $encounterId);
        $nursingNotes = $nursingModel->getNursingNotes($patientId, $encounterType, $encounterId);
        $marRecords = $nursingModel->getMarRecords($patientId, $encounterId);
        $escalations = $nursingModel->getEscalations($patientId);

        $this->view('nursing/patient', [
            'patient'       => $patient,
            'encounterType' => $encounterType,
            'encounterId'   => $encounterId,
            'assessment'    => $assessment,
            'vitalSigns'    => $vitalSigns,
            'nursingNotes'  => $nursingNotes,
            'marRecords'    => $marRecords,
            'escalations'   => $escalations,
        ]);
    }

    /**
     * Form & Simpan Pengkajian Keperawatan
     */
    public function assessment(): void {
        $this->requireRole(['admin', 'perawat', 'kepala_ruang']);

        $patientId = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
        $encounterType = $_GET['encounter_type'] ?? $_POST['encounter_type'] ?? 'INPATIENT';
        $encounterId = (int)($_GET['encounter_id'] ?? $_POST['encounter_id'] ?? 0);

        $patientModel = new PatientModel();
        $patient = $patientModel->getPatientById($patientId);

        if (!$patient) {
            $this->setFlash('danger', 'Pasien tidak ditemukan.');
            $this->redirect('index.php?c=nursing&a=index');
            return;
        }

        $nursingModel = new NursingModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $nursingModel->saveAssessment([
                    'patient_id'          => $patientId,
                    'encounter_type'      => $encounterType,
                    'encounter_id'        => $encounterId,
                    'nurse_id'            => $_SESSION['user_id'] ?? null,
                    'chief_complaint'     => $_POST['chief_complaint'] ?? 'Keluhan dalam perawatan',
                    'general_condition'   => $_POST['general_condition'] ?? 'Sedang',
                    'consciousness'       => $_POST['consciousness'] ?? 'COMPOS_MENTIS',
                    'fall_risk_score'     => (int)($_POST['fall_risk_score'] ?? 0),
                    'fall_risk_category'  => $_POST['fall_risk_category'] ?? 'RENDAH',
                    'pain_score'          => (int)($_POST['pain_score'] ?? 0),
                    'functional_status'   => $_POST['functional_status'] ?? 'MANDIRI',
                ]);

                AuditLogModel::log($_SESSION['user_id'] ?? null, 'NURSING_ASSESSMENT', 'Keperawatan', "Pengkajian keperawatan pasien RM {$patient['no_rm']} tersimpan");
                $this->setFlash('success', 'Pengkajian awal asuhan keperawatan berhasil disimpan.');
                $this->redirect("index.php?c=nursing&a=patient&patient_id=$patientId&encounter_type=$encounterType&encounter_id=$encounterId");
                return;
            } catch (Exception $e) {
                $this->setFlash('danger', 'Gagal menyimpan pengkajian: ' . $e->getMessage());
            }
        }

        $existingAssessment = $nursingModel->getAssessment($patientId, $encounterType, $encounterId);

        $this->view('nursing/assessment', [
            'patient'       => $patient,
            'encounterType' => $encounterType,
            'encounterId'   => $encounterId,
            'assessment'    => $existingAssessment,
        ]);
    }

    /**
     * Observasi Tanda-Tanda Vital Berkala
     */
    public function vitalSigns(): void {
        $this->requireRole(['admin', 'perawat', 'kepala_ruang']);

        $patientId = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
        $encounterType = $_GET['encounter_type'] ?? $_POST['encounter_type'] ?? 'INPATIENT';
        $encounterId = (int)($_GET['encounter_id'] ?? $_POST['encounter_id'] ?? 0);

        $patientModel = new PatientModel();
        $patient = $patientModel->getPatientById($patientId);

        if (!$patient) {
            $this->setFlash('danger', 'Pasien tidak ditemukan.');
            $this->redirect('index.php?c=nursing&a=index');
            return;
        }

        $nursingModel = new NursingModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $nursingModel->saveVitalSigns([
                    'patient_id'        => $patientId,
                    'encounter_type'    => $encounterType,
                    'encounter_id'      => $encounterId,
                    'nurse_id'          => $_SESSION['user_id'] ?? null,
                    'systolic_bp'       => (int)($_POST['systolic_bp'] ?? 120),
                    'diastolic_bp'      => (int)($_POST['diastolic_bp'] ?? 80),
                    'pulse_rate'        => (int)($_POST['pulse_rate'] ?? 80),
                    'respiratory_rate'  => (int)($_POST['respiratory_rate'] ?? 20),
                    'temperature'       => (float)($_POST['temperature'] ?? 36.5),
                    'spo2'              => (int)($_POST['spo2'] ?? 98),
                    'notes'             => $_POST['notes'] ?? null,
                ]);

                AuditLogModel::log($_SESSION['user_id'] ?? null, 'NURSING_VITAL_SIGNS', 'Keperawatan', "Observasi tanda vital pasien RM {$patient['no_rm']} tercatat");
                $this->setFlash('success', 'Observasi tanda-tanda vital berhasil dicatat.');
                $this->redirect("index.php?c=nursing&a=patient&patient_id=$patientId&encounter_type=$encounterType&encounter_id=$encounterId");
                return;
            } catch (Exception $e) {
                $this->setFlash('danger', 'Gagal mencatat tanda vital: ' . $e->getMessage());
            }
        }

        $history = $nursingModel->getVitalSignsHistory($patientId, $encounterType, $encounterId);

        $this->view('nursing/vital_signs', [
            'patient'       => $patient,
            'encounterType' => $encounterType,
            'encounterId'   => $encounterId,
            'history'       => $history,
        ]);
    }

    /**
     * Lembar Administrasi Pemberian Obat (MAR)
     */
    public function mar(): void {
        $this->requireRole(['admin', 'perawat', 'kepala_ruang']);

        $patientId = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
        $admissionId = (int)($_GET['admission_id'] ?? $_POST['admission_id'] ?? 0);

        $patientModel = new PatientModel();
        $patient = $patientModel->getPatientById($patientId);

        if (!$patient) {
            $this->setFlash('danger', 'Pasien tidak ditemukan.');
            $this->redirect('index.php?c=nursing&a=index');
            return;
        }

        $nursingModel = new NursingModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $nursingModel->saveMedicationAdministration([
                    'patient_id'       => $patientId,
                    'admission_id'     => $admissionId,
                    'nurse_id'         => $_SESSION['user_id'] ?? null,
                    'medicine_name'    => $_POST['medicine_name'] ?? '',
                    'dosage'           => $_POST['dosage'] ?? '',
                    'route'            => $_POST['route'] ?? 'Oral',
                    'planned_time'     => $_POST['planned_time'] ?? date('Y-m-d H:i:s'),
                    'patient_response' => $_POST['patient_response'] ?? 'Diterima baik',
                    'status'           => $_POST['status'] ?? 'GIVEN',
                    'notes'            => $_POST['notes'] ?? null,
                ]);

                AuditLogModel::log($_SESSION['user_id'] ?? null, 'NURSING_MAR', 'Keperawatan', "Pemberian obat pasien RM {$patient['no_rm']} tercatat pada lembar MAR");
                $this->setFlash('success', 'Pemberian obat berhasil dicatat pada lembar MAR dengan verifikasi 6-Benar.');
                $this->redirect("index.php?c=nursing&a=patient&patient_id=$patientId&encounter_type=INPATIENT&encounter_id=$admissionId");
                return;
            } catch (Exception $e) {
                $this->setFlash('danger', 'Gagal mencatat pemberian obat: ' . $e->getMessage());
            }
        }

        $marRecords = $nursingModel->getMarRecords($patientId, $admissionId);

        $this->view('nursing/mar', [
            'patient'     => $patient,
            'admissionId' => $admissionId,
            'marRecords'  => $marRecords,
        ]);
    }

    /**
     * Catatan Perkembangan Keperawatan (Nursing Notes SOAP)
     */
    public function notes(): void {
        $this->requireRole(['admin', 'perawat', 'kepala_ruang']);

        $patientId = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
        $encounterType = $_GET['encounter_type'] ?? $_POST['encounter_type'] ?? 'INPATIENT';
        $encounterId = (int)($_GET['encounter_id'] ?? $_POST['encounter_id'] ?? 0);

        $patientModel = new PatientModel();
        $patient = $patientModel->getPatientById($patientId);

        if (!$patient) {
            $this->setFlash('danger', 'Pasien tidak ditemukan.');
            $this->redirect('index.php?c=nursing&a=index');
            return;
        }

        $nursingModel = new NursingModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $nursingModel->saveNursingNote([
                    'patient_id'        => $patientId,
                    'encounter_type'    => $encounterType,
                    'encounter_id'      => $encounterId,
                    'nurse_id'          => $_SESSION['user_id'] ?? null,
                    'shift'             => $_POST['shift'] ?? 'PAGI',
                    'subjective'        => $_POST['subjective'] ?? '',
                    'objective'         => $_POST['objective'] ?? '',
                    'nursing_diagnosis' => $_POST['nursing_diagnosis'] ?? '',
                    'interventions'     => $_POST['interventions'] ?? '',
                    'evaluation'        => $_POST['evaluation'] ?? '',
                    'is_finalized'      => !empty($_POST['is_finalized']),
                ]);

                AuditLogModel::log($_SESSION['user_id'] ?? null, 'NURSING_NOTE', 'Keperawatan', "Catatan perkembangan perawat shift {$_POST['shift']} tersimpan");
                $this->setFlash('success', 'Catatan perkembangan keperawatan (SOAP) berhasil disimpan.');
                $this->redirect("index.php?c=nursing&a=patient&patient_id=$patientId&encounter_type=$encounterType&encounter_id=$encounterId");
                return;
            } catch (Exception $e) {
                $this->setFlash('danger', 'Gagal menyimpan catatan perkembangan: ' . $e->getMessage());
            }
        }

        $notesList = $nursingModel->getNursingNotes($patientId, $encounterType, $encounterId);

        $this->view('nursing/notes', [
            'patient'       => $patient,
            'encounterType' => $encounterType,
            'encounterId'   => $encounterId,
            'notesList'     => $notesList,
        ]);
    }

    /**
     * Form & Aksi Eskalasi Klinis ke Dokter DPJP
     */
    public function escalate(): void {
        $this->requireRole(['admin', 'perawat', 'kepala_ruang']);

        $patientId = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
        $encounterType = $_GET['encounter_type'] ?? $_POST['encounter_type'] ?? 'INPATIENT';
        $encounterId = (int)($_GET['encounter_id'] ?? $_POST['encounter_id'] ?? 0);

        $patientModel = new PatientModel();
        $patient = $patientModel->getPatientById($patientId);

        if (!$patient) {
            $this->setFlash('danger', 'Pasien tidak ditemukan.');
            $this->redirect('index.php?c=nursing&a=index');
            return;
        }

        $doctorModel = new DoctorModel();
        $doctors = $doctorModel->getAllDoctors(true);
        $nursingModel = new NursingModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $nursingModel->createEscalation([
                    'patient_id'           => $patientId,
                    'encounter_type'       => $encounterType,
                    'encounter_id'         => $encounterId,
                    'nurse_id'             => $_SESSION['user_id'] ?? null,
                    'doctor_id'            => (int)($_POST['doctor_id'] ?? 0),
                    'escalation_reason'    => $_POST['escalation_reason'] ?? 'Perburukan parameter klinis',
                    'vital_signs_summary'  => $_POST['vital_signs_summary'] ?? 'Tanda vital abnormal',
                    'doctor_instruction'   => $_POST['doctor_instruction'] ?? null,
                    'status'               => 'REPORTED',
                ]);

                AuditLogModel::log($_SESSION['user_id'] ?? null, 'CLINICAL_ESCALATION', 'Keperawatan', "Eskalasi klinis pasien RM {$patient['no_rm']} dilaporkan ke dokter");
                $this->setFlash('warning', 'Eskalasi klinis berhasil dilaporkan ke Dokter DPJP.');
                $this->redirect("index.php?c=nursing&a=patient&patient_id=$patientId&encounter_type=$encounterType&encounter_id=$encounterId");
                return;
            } catch (Exception $e) {
                $this->setFlash('danger', 'Gagal memproses eskalasi: ' . $e->getMessage());
            }
        }

        $this->view('nursing/escalate', [
            'patient'       => $patient,
            'encounterType' => $encounterType,
            'encounterId'   => $encounterId,
            'doctors'       => $doctors,
        ]);
    }

    /**
     * Respon Eskalasi Klinis oleh Dokter DPJP
     */
    public function respondEscalation(): void {
        $this->requireRole(['admin', 'dokter']);

        $escalationId = (int)($_POST['escalation_id'] ?? 0);
        $instruction  = trim($_POST['doctor_instruction'] ?? '');

        if (!$escalationId || empty($instruction)) {
            $this->setFlash('danger', 'Instruksi dokter wajib diisi.');
            $this->redirect('index.php?c=nursing&a=index');
            return;
        }

        $nursingModel = new NursingModel();
        try {
            $doctorId = (int)($_SESSION['user_id'] ?? 1);
            $nursingModel->respondEscalation($escalationId, $doctorId, $instruction);

            AuditLogModel::log($_SESSION['user_id'] ?? null, 'RESPOND_ESCALATION', 'Keperawatan', "Dokter merespon eskalasi #$escalationId dengan instruksi");
            $this->setFlash('success', 'Instruksi medis eskalasi berhasil diberikan.');
        } catch (Exception $e) {
            $this->setFlash('danger', 'Gagal merespon eskalasi: ' . $e->getMessage());
        }

        $this->redirect('index.php?c=nursing&a=index');
    }
}

