<?php
// app/controllers/IgdController.php - IGD / Emergency Department (SOP/SIMRS/007/2026)

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/IgdModel.php';
require_once __DIR__ . '/../models/PatientModel.php';
require_once __DIR__ . '/../models/DoctorModel.php';
require_once __DIR__ . '/../models/CpoeModel.php';

class IgdController extends Controller {

    public function __construct() {
        $this->requireRole(['admin', 'dokter', 'perawat', 'triase', 'registrasi']);
    }

    /**
     * Dashboard Pelayanan IGD: Antrean Kunjungan & Skrining Triase Aktif
     */
    public function index(): void {
        $igdModel = new IgdModel();

        $filters = [
            'status'          => $_GET['status'] ?? null,
            'triage_category' => $_GET['triage_category'] ?? null,
            'search'          => trim($_GET['search'] ?? '')
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = $igdModel->paginateVisits($filters, $page, 15);
        $stats = $igdModel->getIgdStats();

        $this->render('igd/index', [
            'pageTitle'  => 'Instalasi Gawat Darurat (IGD) - SIMRS Siloam Peduli',
            'visits'     => $pagination['data'],
            'pagination' => $pagination,
            'stats'      => $stats,
            'filters'    => $filters
        ]);
    }

    /**
     * Form Registrasi Cepat Pasien IGD
     */
    public function create(): void {
        $this->requireRole(['admin', 'registrasi', 'triase']);
        $patientModel = new PatientModel();
        $patients = $patientModel->findAll(['limit' => 50]);

        $this->render('igd/create', [
            'pageTitle' => 'Registrasi Cepat Pasien IGD - SIMRS Siloam Peduli',
            'patients'  => $patients
        ]);
    }

    /**
     * Simpan Registrasi Cepat IGD
     */
    public function store(): void {
        $this->requireRole(['admin', 'registrasi', 'triase']);
        $this->validateCsrf();

        $patientId = !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
        $isAnonymous = !empty($_POST['is_anonymous']);
        $anonymousName = trim($_POST['anonymous_name'] ?? '');

        if (!$patientId && !$isAnonymous && empty($anonymousName)) {
            $this->setFlash('danger', 'Silakan pilih pasien terdaftar atau centang opsi Pasien Darurat Tanpa Identitas (Mr./Mrs. X)!');
            $this->redirect('index.php?c=igd&a=create');
        }

        if ($isAnonymous && empty($anonymousName)) {
            $anonymousName = 'Mr. X (~' . ($_POST['estimated_age'] ?? '35') . ' th, ' . ($_POST['gender'] ?? 'L') . ')';
        }

        $igdModel = new IgdModel();
        $user = $this->currentUser();

        $res = $igdModel->createEmergencyVisit([
            'patient_id'     => $patientId,
            'anonymous_name' => $anonymousName,
            'transportation' => $_POST['transportation'] ?? 'KENDARAAN_PRIBADI',
            'accompanied_by' => trim($_POST['accompanied_by'] ?? '')
        ], $user['id']);

        if ($res['success']) {
            $this->setFlash('success', "Pasien berhasil didaftarkan di IGD dengan nomor {$res['visit_number']}!");
            // Langsung arahkan ke form pengkajian triase
            $this->redirect('index.php?c=igd&a=triage&id=' . $res['visit_id']);
        } else {
            $this->setFlash('danger', 'Gagal mendaftarkan pasien IGD.');
            $this->redirect('index.php?c=igd&a=create');
        }
    }

    /**
     * Form Pengkajian Triase Klinis ESI (Merah, Kuning, Hijau, Hitam) & Skor GCS
     */
    public function triage(): void {
        $this->requireRole(['admin', 'triase', 'perawat']);

        $visitId = (int)($_GET['id'] ?? 0);
        $igdModel = new IgdModel();
        $visit = $igdModel->getVisitDetail($visitId);

        if (!$visit) {
            $this->setFlash('danger', 'Kunjungan IGD tidak ditemukan');
            $this->redirect('index.php?c=igd');
        }

        $this->render('igd/triage', [
            'pageTitle' => 'Skrining Triase Klinis IGD (ESI) - SIMRS Siloam Peduli',
            'visit'     => $visit
        ]);
    }

    /**
     * Simpan Hasil Pengkajian Triase
     */
    public function storeTriage(): void {
        $this->requireRole(['admin', 'triase', 'perawat']);
        $this->validateCsrf();

        $visitId = (int)($_POST['emergency_visit_id'] ?? 0);
        if (!$visitId) {
            $this->setFlash('danger', 'ID Kunjungan IGD tidak valid');
            $this->redirect('index.php?c=igd');
        }

        $igdModel = new IgdModel();
        $user = $this->currentUser();

        $res = $igdModel->saveTriage($_POST, $user['id']);

        if ($res['success']) {
            $this->setFlash('success', "Pengkajian triase berhasil disimpan! Kategori: {$_POST['triage_level']}.");
            // Arahkan ke dashboard IGD atau ke ruang pemeriksaan dokter
            if (in_array($user['role'], ['dokter'])) {
                $this->redirect('index.php?c=igd&a=examine&id=' . $visitId);
            } else {
                $this->redirect('index.php?c=igd');
            }
        } else {
            $this->setFlash('danger', 'Gagal menyimpan triase: ' . $res['message']);
            $this->redirect('index.php?c=igd&a=triage&id=' . $visitId);
        }
    }

    /**
     * Form Primary Survey ABCDE & Asesmen Medis Dokter IGD
     */
    public function examine(): void {
        $this->requireRole(['admin', 'dokter']);

        $visitId = (int)($_GET['id'] ?? 0);
        $igdModel = new IgdModel();
        $visit = $igdModel->getVisitDetail($visitId);

        if (!$visit) {
            $this->setFlash('danger', 'Kunjungan IGD tidak ditemukan');
            $this->redirect('index.php?c=igd');
        }

        $diagnoses = $igdModel->query("SELECT code, description_id, description_en FROM icd_diagnoses WHERE is_active = TRUE ORDER BY code ASC");

        $doctorModel = new DoctorModel();
        $doctors = $doctorModel->findAll(['is_active' => 1]);

        $this->render('igd/examine', [
            'pageTitle' => 'Primary Survey ABCDE & Asesmen Dokter IGD - SIMRS Siloam Peduli',
            'visit'     => $visit,
            'diagnoses' => $diagnoses,
            'doctors'   => $doctors
        ]);
    }

    /**
     * Simpan Asesmen Dokter IGD (SOP: Validasi Wajib Kode ICD-10)
     */
    public function storeExamine(): void {
        $this->requireRole(['admin', 'dokter']);
        $this->validateCsrf();

        $visitId = (int)($_POST['emergency_visit_id'] ?? 0);
        $doctorId = (int)($_POST['doctor_id'] ?? 0);

        // Jika dokter login, gunakan ID dokter terkait
        $user = $this->currentUser();
        if ($user['role'] === 'dokter') {
            $doc = (new IgdModel())->query("SELECT id FROM doctors WHERE user_id = :uid LIMIT 1", [':uid' => $user['id']]);
            if (!empty($doc)) {
                $doctorId = (int)$doc[0]['id'];
            }
        }

        if (!$doctorId) {
            $doctorId = 1; // Default DPJP jaga IGD
        }

        $icd10 = trim($_POST['icd10_code'] ?? '');
        if (empty($icd10)) {
            $this->setFlash('danger', 'Kode ICD-10 Wajib Diisi!');
            $this->redirect('index.php?c=igd&a=examine&id=' . $visitId);
        }

        $igdModel = new IgdModel();
        $res = $igdModel->saveAssessment($_POST, $doctorId, $user['id']);

        if ($res['success']) {
            $this->setFlash('success', 'Asesmen Primary Survey ABCDE dan diagnosis emergensi berhasil disimpan!');
            $this->redirect('index.php?c=igd&a=monitoring&id=' . $visitId);
        } else {
            $this->setFlash('danger', $res['message']);
            $this->redirect('index.php?c=igd&a=examine&id=' . $visitId);
        }
    }

    /**
     * Lembar Monitoring Tanda Vital Berkala
     */
    public function monitoring(): void {
        $visitId = (int)($_GET['id'] ?? 0);
        $igdModel = new IgdModel();
        $visit = $igdModel->getVisitDetail($visitId);

        if (!$visit) {
            $this->setFlash('danger', 'Kunjungan IGD tidak ditemukan');
            $this->redirect('index.php?c=igd');
        }

        $this->render('igd/monitoring', [
            'pageTitle' => 'Monitoring Tanda Vital Berkala IGD - SIMRS Siloam Peduli',
            'visit'     => $visit
        ]);
    }

    /**
     * Simpan Monitoring Tanda Vital Berkala
     */
    public function storeMonitoring(): void {
        $this->validateCsrf();

        $visitId = (int)($_POST['emergency_visit_id'] ?? 0);
        if (!$visitId) {
            $this->setFlash('danger', 'ID Kunjungan IGD tidak valid');
            $this->redirect('index.php?c=igd');
        }

        $igdModel = new IgdModel();
        $user = $this->currentUser();

        $res = $igdModel->saveMonitoring($_POST, $user['id']);

        if ($res['success']) {
            if ($res['is_abnormal']) {
                $this->setFlash('warning', 'PERINGATAN: Tanda vital abnormal terdeteksi! Dokter jaga IGD telah ternotifikasi.');
            } else {
                $this->setFlash('success', 'Catatan monitoring tanda vital berhasil disimpan (Kondisi Stabil).');
            }
        } else {
            $this->setFlash('danger', 'Gagal menyimpan monitoring tanda vital.');
        }

        $this->redirect('index.php?c=igd&a=monitoring&id=' . $visitId);
    }

    /**
     * Form Keputusan Disposisi Pasien IGD
     */
    public function disposition(): void {
        $this->requireRole(['admin', 'dokter']);

        $visitId = (int)($_GET['id'] ?? 0);
        $igdModel = new IgdModel();
        $visit = $igdModel->getVisitDetail($visitId);

        if (!$visit) {
            $this->setFlash('danger', 'Kunjungan IGD tidak ditemukan');
            $this->redirect('index.php?c=igd');
        }

        $this->render('igd/disposition', [
            'pageTitle' => 'Disposisi Akhir Pasien IGD - SIMRS Siloam Peduli',
            'visit'     => $visit
        ]);
    }

    /**
     * Simpan Keputusan Disposisi Akhir Pasien IGD
     */
    public function storeDisposition(): void {
        $this->requireRole(['admin', 'dokter']);
        $this->validateCsrf();

        $visitId = (int)($_POST['emergency_visit_id'] ?? 0);
        $dispositionType = $_POST['disposition_type'] ?? '';
        $notes = trim($_POST['disposition_notes'] ?? '');

        if (!$visitId || empty($dispositionType)) {
            $this->setFlash('danger', 'Keputusan disposisi pasien wajib dipilih!');
            $this->redirect('index.php?c=igd&a=disposition&id=' . $visitId);
        }

        $igdModel = new IgdModel();
        $user = $this->currentUser();

        if ($igdModel->saveDisposition($visitId, $dispositionType, $notes, $user['id'])) {
            $this->setFlash('success', "Disposisi pasien [{$dispositionType}] berhasil ditetapkan. Layanan IGD selesai!");
            $this->redirect('index.php?c=igd&a=printDisposition&id=' . $visitId);
        } else {
            $this->setFlash('danger', 'Gagal menyimpan disposisi pasien.');
            $this->redirect('index.php?c=igd&a=disposition&id=' . $visitId);
        }
    }

    /**
     * Cetak Lembar Resume Medis & Disposisi Pasien IGD (Tanpa Layout Master)
     */
    public function printDisposition(): void {
        $visitId = (int)($_GET['id'] ?? 0);
        $igdModel = new IgdModel();
        $visit = $igdModel->getVisitDetail($visitId);

        if (!$visit) {
            $this->setFlash('danger', 'Kunjungan IGD tidak ditemukan');
            $this->redirect('index.php?c=igd');
        }

        $this->render('igd/print_disposition', [
            'pageTitle' => 'Surat Disposisi & Resume Pelayanan IGD - ' . $visit['visit_number'],
            'visit'     => $visit
        ], false);
    }
}
