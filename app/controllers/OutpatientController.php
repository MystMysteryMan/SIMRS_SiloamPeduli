<?php
// app/controllers/OutpatientController.php - Pelayanan Rawat Jalan / Outpatient (SOP/SIMRS/005/2026)

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/OutpatientModel.php';
require_once __DIR__ . '/../models/PatientModel.php';
require_once __DIR__ . '/../models/DoctorModel.php';
require_once __DIR__ . '/../models/PoliclinicModel.php';
require_once __DIR__ . '/../models/MasterDataModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';

class OutpatientController extends Controller {

    public function __construct() {
        $this->requireRole(['admin', 'registrasi', 'dokter', 'perawat', 'kasir']);
    }

    /**
     * Dashboard & Antrean Kunjungan Rawat Jalan
     */
    public function index(): void {
        $model = new OutpatientModel();
        $poliModel = new PoliclinicModel();

        $date = !empty($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
        $poliId = !empty($_GET['policlinic_id']) ? (int)$_GET['policlinic_id'] : null;
        $status = !empty($_GET['status']) ? trim($_GET['status']) : null;

        $queue = $model->getOutpatientQueue($date, $poliId, $status);
        $policlinics = $poliModel->getAllPoliclinics(true);

        // Hitung statistik antrean
        $totalVisits = count($queue);
        $waitingNurse = 0;
        $readyDoctor = 0;
        $completedVisits = 0;

        foreach ($queue as $q) {
            $st = strtoupper($q['status'] ?? '');
            if (in_array($st, ['TERDAFTAR', 'MENUNGGU_POLI', 'ASESMEN'])) {
                $waitingNurse++;
            } elseif (in_array($st, ['SIAP_DOKTER', 'SEDANG_DIPERIKSA', 'DIPERIKSA'])) {
                $readyDoctor++;
            } elseif ($st === 'SELESAI' || $st === 'COMPLETED') {
                $completedVisits++;
            }
        }

        $this->view('outpatient/index', [
            'queue'           => $queue,
            'policlinics'     => $policlinics,
            'selectedDate'    => $date,
            'selectedPoli'    => $poliId,
            'selectedStatus'  => $status,
            'totalVisits'     => $totalVisits,
            'waitingNurse'    => $waitingNurse,
            'readyDoctor'     => $readyDoctor,
            'completedVisits' => $completedVisits,
        ]);
    }

    /**
     * Form Pendaftaran / Aktivasi Kunjungan Rawat Jalan
     */
    public function create(): void {
        $this->requireRole(['admin', 'registrasi']);

        $patientModel = new PatientModel();
        $poliModel = new PoliclinicModel();
        $doctorModel = new DoctorModel();

        $patientId = !empty($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;
        $selectedPatient = $patientId ? $patientModel->getPatientById($patientId) : null;

        $policlinics = $poliModel->getAllPoliclinics(true);
        $doctors = $doctorModel->getAllDoctors(true);

        $this->view('outpatient/create', [
            'patient'     => $selectedPatient,
            'policlinics' => $policlinics,
            'doctors'     => $doctors,
        ]);
    }

    /**
     * Simpan Kunjungan Rawat Jalan Baru
     */
    public function store(): void {
        $this->requireRole(['admin', 'registrasi']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?c=outpatient&a=index');
            return;
        }

        $patientId = (int)($_POST['patient_id'] ?? 0);
        $doctorId = (int)($_POST['doctor_id'] ?? 0);
        $policlinicId = (int)($_POST['policlinic_id'] ?? 0);

        if (!$patientId || !$doctorId || !$policlinicId) {
            $this->setFlash('danger', 'Pasien, dokter, dan poliklinik tujuan wajib dipilih.');
            $this->redirect('index.php?c=outpatient&a=create');
            return;
        }

        $model = new OutpatientModel();
        try {
            $visitId = $model->registerVisit([
                'patient_id'        => $patientId,
                'doctor_id'         => $doctorId,
                'policlinic_id'     => $policlinicId,
                'visit_date'        => !empty($_POST['visit_date']) ? $_POST['visit_date'] : date('Y-m-d'),
                'payment_type'      => $_POST['payment_type'] ?? 'UMUM',
                'insurance_card_no' => $_POST['insurance_card_no'] ?? null,
                'referral_no'       => $_POST['referral_no'] ?? null,
                'notes'             => $_POST['notes'] ?? null,
                'status'            => 'TERDAFTAR',
            ]);

            AuditLogModel::log($_SESSION['user_id'] ?? null, 'CREATE_OUTPATIENT_VISIT', 'Rawat Jalan', "Kunjungan ID #$visitId berhasil didaftarkan");
            $this->setFlash('success', 'Kunjungan rawat jalan berhasil didaftarkan dan masuk antrean poliklinik.');
            $this->redirect('index.php?c=outpatient&a=index');
        } catch (Exception $e) {
            $this->setFlash('danger', 'Gagal mendaftarkan kunjungan: ' . $e->getMessage());
            $this->redirect('index.php?c=outpatient&a=create');
        }
    }

    /**
     * Form & Proses Asesmen Awal Keperawatan Poli (Perawat)
     */
    public function assessment(): void {
        $this->requireRole(['admin', 'perawat']);

        $visitId = (int)($_GET['id'] ?? ($_POST['visit_id'] ?? 0));
        $model = new OutpatientModel();
        $visit = $model->getVisitDetail($visitId);

        if (!$visit) {
            $this->setFlash('danger', 'Kunjungan tidak ditemukan.');
            $this->redirect('index.php?c=outpatient&a=index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nurseId = (int)($_SESSION['user_id'] ?? 1);
            $success = $model->saveNurseInitialAssessment($visitId, $_POST, $nurseId);

            if ($success) {
                AuditLogModel::log($nurseId, 'NURSE_ASSESSMENT', 'Rawat Jalan', "Asesmen awal perawat untuk kunjungan #$visitId selesai");
                $this->setFlash('success', 'Asesmen awal keperawatan tersimpan. Pasien kini berstatus Siap Diperiksa Dokter.');
                $this->redirect('index.php?c=outpatient&a=index');
                return;
            } else {
                $this->setFlash('danger', 'Gagal menyimpan asesmen keperawatan.');
            }
        }

        $this->view('outpatient/assessment', [
            'visit' => $visit,
        ]);
    }

    /**
     * Pemeriksaan Dokter Poli, Resep CPOE, Rujukan & Jadwal Kontrol
     */
    public function examine(): void {
        $this->requireRole(['admin', 'dokter']);

        $visitId = (int)($_GET['id'] ?? ($_POST['visit_id'] ?? 0));
        $model = new OutpatientModel();
        $visit = $model->getVisitDetail($visitId);

        if (!$visit) {
            $this->setFlash('danger', 'Kunjungan tidak ditemukan.');
            $this->redirect('index.php?c=outpatient&a=index');
            return;
        }

        // Ambil asesmen awal keperawatan yang telah diinput perawat
        require_once __DIR__ . '/../models/NursingModel.php';
        $nursingModel = new NursingModel();
        $assessment = $nursingModel->getAssessment((int)$visit['patient_id'], 'OUTPATIENT', $visitId);
        $vitalSigns = $nursingModel->getVitalSignsHistory((int)$visit['patient_id'], 'OUTPATIENT', $visitId);

        // Ambil katalog ICD-10 untuk autocomplete
        $masterModel = new MasterDataModel();
        $icdList = $masterModel->getAllIcd('', 200);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $icd10 = trim($_POST['icd10_code'] ?? '');

            // Kepatuhan SOP: Validasi Wajib Kode ICD-10
            if (empty($icd10)) {
                $this->setFlash('danger', 'Kode ICD-10 Wajib Diisi! Silakan pilih kode diagnosis.');
                $this->view('outpatient/examine', [
                    'visit'       => $visit,
                    'assessment'  => $assessment,
                    'vitalSigns'  => $vitalSigns,
                    'icdList'     => $icdList,
                    'postData'    => $_POST,
                ]);
                return;
            }

            try {
                // 1. Simpan SOAP & Diagnosis ke medical_records
                require_once __DIR__ . '/../models/EmrModel.php';
                $emrModel = new EmrModel();
                $emrModel->saveMedicalRecord([
                    'patient_id'     => (int)$visit['patient_id'],
                    'doctor_id'      => (int)($_SESSION['user_id'] ?? 1),
                    'subjective'     => $_POST['subjective'] ?? '',
                    'objective'      => $_POST['objective'] ?? '',
                    'assessment'     => $_POST['assessment'] ?? ($_POST['assessment_notes'] ?? ''),
                    'plan'           => $_POST['plan'] ?? ($_POST['plan_notes'] ?? ''),
                    'icd10_code'     => $icd10,
                    'diagnosis_name' => $_POST['diagnosis_name'] ?? '',
                    'status'         => 'COMPLETED',
                ]);

                // 2. Tindak Lanjut: Kontrol Ulang jika dipilih
                $controlDate = $_POST['control_date'] ?? ($_POST['next_control_date'] ?? null);
                $followUp = $_POST['follow_up'] ?? (!empty($controlDate) ? 'KONTROL' : 'SELESAI');
                if (!empty($controlDate)) {
                    $model->saveFollowUpControl([
                        'visit_id'     => $visitId,
                        'patient_id'   => (int)$visit['patient_id'],
                        'doctor_id'    => (int)$visit['doctor_id'],
                        'control_date' => $controlDate,
                        'notes'        => $_POST['control_notes'] ?? 'Kontrol evaluasi pengobatan',
                    ]);
                }

                // 3. Update status kunjungan menjadi 'SELESAI'
                $model->updateStatus($visitId, 'SELESAI');

                AuditLogModel::log($_SESSION['user_id'] ?? null, 'EXAMINE_OUTPATIENT', 'Rawat Jalan', "Pemeriksaan dokter kunjungan #$visitId selesai dengan ICD-10: $icd10");
                $this->setFlash('success', 'Pemeriksaan rawat jalan selesai disahkan. Data klinis dikunci ke RME.');
                
                if ($followUp === 'KONTROL') {
                    $this->redirect('index.php?c=outpatient&a=printControl&id=' . $visitId);
                } else {
                    $this->redirect('index.php?c=outpatient&a=index');
                }
                return;
            } catch (Exception $e) {
                $this->setFlash('danger', 'Gagal menyelesaikan pemeriksaan: ' . $e->getMessage());
            }
        }

        $this->view('outpatient/examine', [
            'visit'      => $visit,
            'assessment' => $assessment,
            'vitalSigns' => $vitalSigns,
            'icdList'    => $icdList,
        ]);
    }

    /**
     * Cetak Lembar Rencana Kontrol & Edukasi Pasien Pulang
     */
    public function printControl(): void {
        $visitId = (int)($_GET['id'] ?? ($_GET['visit_id'] ?? 0));
        $controlId = (int)($_GET['control_id'] ?? 0);
        $model = new OutpatientModel();

        $selectedControl = null;
        if ($controlId > 0) {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM public.outpatient_controls WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $controlId]);
            $selectedControl = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($selectedControl) {
                $visitId = (int)$selectedControl['visit_id'];
            }
        }

        $visit = $model->getVisitDetail($visitId);
        if (!$visit) {
            $this->setFlash('danger', 'Kunjungan tidak ditemukan.');
            $this->redirect('index.php?c=outpatient&a=index');
            return;
        }

        $controls = $model->getControlsByPatient((int)$visit['patient_id']);
        $latestControl = $selectedControl ?: (!empty($controls) ? $controls[0] : null);

        $this->view('outpatient/print_control', [
            'visit'   => $visit,
            'control' => $latestControl,
        ]);
    }
}
