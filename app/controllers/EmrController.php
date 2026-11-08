<?php
// app/controllers/EmrController.php - Rekam Medis Elektronik (SOP/SIMRS/001/2026)

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/EmrModel.php';
require_once __DIR__ . '/../models/PatientModel.php';
require_once __DIR__ . '/../models/DoctorModel.php';
require_once __DIR__ . '/../models/PoliclinicModel.php';
require_once __DIR__ . '/../models/LaboratoryModel.php';
require_once __DIR__ . '/../models/RadiologyModel.php';

class EmrController extends Controller {

    public function __construct() {
        $this->requireRole(['admin', 'dokter', 'dokter_radiologi', 'perawat', 'triase']);
    }

    /**
     * Antrean Pasien Poliklinik Hari Ini untuk Pelayanan Medis
     */
    public function index(): void {
        $emrModel = new EmrModel();
        $poliModel = new PoliclinicModel();
        $doctorModel = new DoctorModel();

        $selectedPoli = !empty($_GET['policlinic_id']) ? (int)$_GET['policlinic_id'] : null;
        $selectedDoctor = !empty($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;

        // Jika dokter yang login, default dokter_id ke dokter terkait jika ada
        $userRole = strtolower($_SESSION['role'] ?? '');
        if ($userRole === 'dokter' && empty($selectedDoctor)) {
            $docRow = (new DoctorModel())->query("SELECT id FROM doctors WHERE user_id = :uid LIMIT 1", [':uid' => $_SESSION['user_id']]);
            if (!empty($docRow)) {
                $selectedDoctor = (int)$docRow[0]['id'];
            }
        }

        $queue = $emrModel->getTodayQueue($selectedDoctor, $selectedPoli);
        $policlinics = $poliModel->getAllPoliclinics(true);
        $doctors = $doctorModel->getAllDoctors(true);

        // Ringkasan statistik antrean hari ini
        $stats = [
            'total'       => count($queue),
            'scheduled'   => 0,
            'in_progress' => 0,
            'completed'   => 0
        ];
        foreach ($queue as $q) {
            $st = $q['status'] ?? 'SCHEDULED';
            if ($st === 'SCHEDULED') $stats['scheduled']++;
            elseif ($st === 'IN_PROGRESS') $stats['in_progress']++;
            elseif ($st === 'COMPLETED') $stats['completed']++;
        }

        $this->render('emr/index', [
            'pageTitle'      => 'Antrean Rawat Jalan & EMR - SIMRS',
            'queue'          => $queue,
            'policlinics'    => $policlinics,
            'doctors'        => $doctors,
            'selectedPoli'   => $selectedPoli,
            'selectedDoctor' => $selectedDoctor,
            'stats'          => $stats
        ]);
    }

    /**
     * Form Pengisian Rekam Medis (SOAP, Tanda Vital, ICD-10, Order Lab & Rad)
     */
    public function examine(): void {
        $this->requireRole(['admin', 'dokter']);
        $appointmentId = (int)($_GET['id'] ?? $_GET['appointment_id'] ?? 0);
        if ($appointmentId <= 0) {
            $this->setFlash('danger', 'ID Kunjungan / Antrean tidak valid.');
            $this->redirect('index.php?c=emr&a=index');
        }

        $emrModel = new EmrModel();
        $appointment = $emrModel->getAppointmentDetail($appointmentId);

        if (!$appointment) {
            $this->setFlash('danger', 'Data antrean / kunjungan pasien tidak ditemukan.');
            $this->redirect('index.php?c=emr&a=index');
        }

        // Jika status antrean masih SCHEDULED, ubah ke IN_PROGRESS tanda dokter sedang memeriksa
        if ($appointment['status'] === 'SCHEDULED') {
            $emrModel->execute("UPDATE appointments SET status = 'IN_PROGRESS' WHERE id = :id", [':id' => $appointmentId]);
            $appointment['status'] = 'IN_PROGRESS';
        }

        $patientId = (int)$appointment['patient_id'];
        $existingRecord = $emrModel->getRecordByAppointment($appointmentId);
        $allergies = $emrModel->getPatientAllergies($patientId);
        $history = $emrModel->getPatientEmrHistory($patientId);

        // Ambil data referensi ICD-10 & Master Lab Tests
        $icdList = $emrModel->query("SELECT code, description_id FROM icd_diagnoses WHERE is_active = TRUE ORDER BY code ASC");
        $labTests = $emrModel->query("SELECT id, test_code, test_name, tariff, unit, normal_range FROM lab_tests WHERE is_active = TRUE ORDER BY test_name ASC");

        $this->render('emr/examine', [
            'pageTitle'      => 'Pemeriksaan EMR Pasien - ' . $appointment['patient_name'],
            'appointment'    => $appointment,
            'record'         => $existingRecord,
            'allergies'      => $allergies,
            'history'        => $history,
            'icdList'        => $icdList,
            'labTests'       => $labTests
        ]);
    }

    /**
     * Simpan Rekam Medis (Enforce Aturan Validasi SOP: ICD-10 Wajib Diisi)
     */
    public function save(): void {
        $this->requireRole(['admin', 'dokter']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?c=emr&a=index');
        }

        if (!verify_csrf()) {
            $this->setFlash('danger', 'Token keamanan CSRF tidak valid. Silakan coba kembali.');
            $this->redirect('index.php?c=emr&a=index');
        }

        $appointmentId = (int)($_POST['appointment_id'] ?? 0);
        $patientId     = (int)($_POST['patient_id'] ?? 0);
        $icd10Code     = trim($_POST['icd10_code'] ?? '');

        // ATURAN KETAT SOP EMR LANGKAH 6: Jika Kode ICD-10 Kosong, Abort & Tampilkan Alert!
        if (empty($icd10Code)) {
            $this->setFlash('danger', 'Kode ICD-10 Wajib Diisi!');
            $this->redirect('index.php?c=emr&a=examine&id=' . $appointmentId);
        }

        $emrModel = new EmrModel();
        $result = $emrModel->saveMedicalRecord($_POST);

        if ($result['success']) {
            $this->setFlash('success', $result['message']);
            $this->redirect('index.php?c=emr&a=history&patient_id=' . $patientId);
        } else {
            $this->setFlash('danger', $result['message']);
            $this->redirect('index.php?c=emr&a=examine&id=' . $appointmentId);
        }
    }

    /**
     * Riwayat Longitudinal Rekam Medis Pasien (SOP EMR Langkah 9)
     */
    public function history(): void {
        $patientId = (int)($_GET['patient_id'] ?? 0);
        if ($patientId <= 0) {
            $this->setFlash('danger', 'Identitas Pasien tidak valid.');
            $this->redirect('index.php?c=emr&a=index');
        }

        $patientModel = new PatientModel();
        $patient = $patientModel->getPatientById($patientId);

        if (!$patient) {
            $this->setFlash('danger', 'Data pasien tidak ditemukan.');
            $this->redirect('index.php?c=emr&a=index');
        }

        $emrModel = new EmrModel();
        $labModel = new LaboratoryModel();
        $radModel = new RadiologyModel();

        $records = $emrModel->getPatientEmrHistory($patientId);
        $allergies = $emrModel->getPatientAllergies($patientId);
        $labHistory = $labModel->getPatientLabHistory($patientId);
        $radHistory = $radModel->getPatientRadiologyHistory($patientId);

        $this->render('emr/history', [
            'pageTitle'   => 'Rekam Medis Lengkap - ' . $patient['full_name'],
            'patient'     => $patient,
            'records'     => $records,
            'allergies'   => $allergies,
            'labHistory'  => $labHistory,
            'radHistory'  => $radHistory
        ]);
    }

    /**
     * Autocomplete / Pencarian ICD-10 via JSON
     */
    public function icd10Search(): void {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 1) {
            $this->json([]);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT code, description_id, description_en 
            FROM icd_diagnoses 
            WHERE is_active = TRUE AND (code LIKE :q OR description_id LIKE :q OR description_en LIKE :q)
            ORDER BY code ASC
            LIMIT 20
        ");
        $stmt->execute([':q' => "%{$q}%"]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json($items);
    }
}
