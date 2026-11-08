<?php
// app/controllers/InpatientController.php - Pelayanan Rawat Inap & Bed Management (SOP/SIMRS/006/2026)

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/InpatientModel.php';
require_once __DIR__ . '/../models/PatientModel.php';
require_once __DIR__ . '/../models/DoctorModel.php';
require_once __DIR__ . '/../models/MasterDataModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';

class InpatientController extends Controller {

    public function __construct() {
        $this->requireRole(['admin', 'registrasi', 'petugas_rawat_inap', 'dokter', 'perawat', 'kasir']);
    }

    /**
     * Dashboard Rawat Inap & Daftar Pasien Mondok Aktif
     */
    public function index(): void {
        $model = new InpatientModel();

        $statusFilter = !empty($_GET['status']) ? trim($_GET['status']) : null;
        $search = !empty($_GET['search']) ? trim($_GET['search']) : null;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $pagination = $model->paginateAdmissions($statusFilter, $search, $page, 15);
        $admissions = $pagination['data'];
        $occupancyOverview = $model->getBedOccupancyOverview();

        // Hitung total kapasitas bed
        $totalBeds = 0;
        $totalOccupied = 0;
        $totalAvailable = 0;

        foreach ($occupancyOverview as $occ) {
            $totalBeds += (int)$occ['total_beds'];
            $totalOccupied += (int)$occ['occupied_beds'];
            $totalAvailable += (int)$occ['available_beds'];
        }

        $bor = $totalBeds > 0 ? round(($totalOccupied / $totalBeds) * 100, 1) : 0;

        $this->view('inpatient/index', [
            'admissions'        => $admissions,
            'pagination'        => $pagination,
            'occupancyOverview' => $occupancyOverview,
            'totalBeds'         => $totalBeds,
            'totalOccupied'     => $totalOccupied,
            'totalAvailable'    => $totalAvailable,
            'bor'               => $bor,
            'selectedStatus'    => $statusFilter,
            'search'            => $search,
        ]);
    }

    /**
     * Form Pendaftaran Admisi Rawat Inap Baru
     */
    public function admit(): void {
        $this->requireRole(['admin', 'registrasi', 'petugas_rawat_inap']);

        $patientModel = new PatientModel();
        $doctorModel = new DoctorModel();
        $inpatientModel = new InpatientModel();

        $patientId = !empty($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;
        $selectedPatient = $patientId ? $patientModel->getPatientById($patientId) : null;

        $doctors = $doctorModel->getAllDoctors(true);
        $availableBeds = $inpatientModel->getAvailableBeds();
        $admNumber = $inpatientModel->generateAdmissionNumber();

        $this->view('inpatient/admit', [
            'patient'       => $selectedPatient,
            'doctors'       => $doctors,
            'availableBeds' => $availableBeds,
            'admNumber'     => $admNumber,
        ]);
    }

    /**
     * Simpan Admisi Rawat Inap Baru
     */
    public function storeAdmission(): void {
        $this->requireRole(['admin', 'registrasi', 'petugas_rawat_inap']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?c=inpatient&a=index');
            return;
        }

        $patientId = (int)($_POST['patient_id'] ?? 0);
        $doctorId  = (int)($_POST['doctor_id'] ?? 0);
        $bedId     = (int)($_POST['bed_id'] ?? 0);

        if (!$patientId || !$doctorId || !$bedId) {
            $this->setFlash('danger', 'Data pasien, dokter DPJP, dan tempat tidur wajib dipilih.');
            $this->redirect('index.php?c=inpatient&a=admit');
            return;
        }

        $model = new InpatientModel();

        // Cari room_id dari bed_id
        $masterModel = new MasterDataModel();
        $bedRow = $masterModel->find('beds', $bedId);
        if (!$bedRow) {
            $this->setFlash('danger', 'Tempat tidur tidak valid.');
            $this->redirect('index.php?c=inpatient&a=admit');
            return;
        }

        $roomRow = $masterModel->find('rooms', (int)$bedRow['room_id']);
        $careClass = $roomRow['class_type'] ?? 'KELAS_1';

        try {
            $admissionId = $model->createAdmission([
                'admission_number' => $_POST['admission_number'] ?? '',
                'patient_id'       => $patientId,
                'doctor_id'        => $doctorId,
                'room_id'          => (int)$bedRow['room_id'],
                'bed_id'           => $bedId,
                'care_class'       => $careClass,
                'insurance_type'   => $_POST['insurance_type'] ?? 'UMUM',
                'admission_reason' => $_POST['admission_reason'] ?? 'Perawatan Rawat Inap',
                'source_type'      => $_POST['source_type'] ?? 'POLI',
                'created_by'       => $_SESSION['user_id'] ?? null,
                'status'           => 'DIRAWAT',
            ]);

            AuditLogModel::log($_SESSION['user_id'] ?? null, 'CREATE_INPATIENT_ADMISSION', 'Rawat Inap', "Admisi rawat inap ID #$admissionId berhasil dibuat");
            $this->setFlash('success', 'Admisi rawat inap berhasil diproses. Tempat tidur telah dialokasikan.');
            $this->redirect('index.php?c=inpatient&a=index');
        } catch (Exception $e) {
            $this->setFlash('danger', 'Gagal memproses admisi: ' . $e->getMessage());
            $this->redirect('index.php?c=inpatient&a=admit');
        }
    }

    /**
     * Konfirmasi Penerimaan Fisik Pasien di Bangsal oleh Perawat
     */
    public function wardReception(): void {
        $this->requireRole(['admin', 'perawat', 'petugas_rawat_inap']);

        $admissionId = (int)($_POST['admission_id'] ?? ($_GET['id'] ?? ($_GET['admission_id'] ?? 0)));
        $model = new InpatientModel();

        try {
            $model->confirmWardReception($admissionId, (int)($_SESSION['user_id'] ?? 1));
            AuditLogModel::log($_SESSION['user_id'] ?? null, 'WARD_RECEPTION', 'Rawat Inap', "Pasien admisi #$admissionId telah diterima di bangsal");
            $this->setFlash('success', 'Pasien telah diverifikasi dan diterima di bangsal perawatan.');
        } catch (Exception $e) {
            $this->setFlash('danger', 'Gagal memverifikasi penerimaan bangsal: ' . $e->getMessage());
        }

        $this->redirect('index.php?c=inpatient&a=index');
    }

    /**
     * Form & Mutasi Pasien Antar-Ruangan / Bed Transfer
     */
    public function transfer(): void {
        $this->requireRole(['admin', 'perawat', 'petugas_rawat_inap']);

        $admissionId = (int)($_GET['id'] ?? ($_POST['admission_id'] ?? 0));
        $model = new InpatientModel();
        $admission = $model->getAdmissionById($admissionId);

        if (!$admission) {
            $this->setFlash('danger', 'Data rawat inap tidak ditemukan.');
            $this->redirect('index.php?c=inpatient&a=index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $toBedId = (int)($_POST['to_bed_id'] ?? 0);
            $reason  = trim($_POST['transfer_reason'] ?? '');

            if (!$toBedId || empty($reason)) {
                $this->setFlash('danger', 'Bed tujuan dan alasan mutasi/transfer wajib diisi.');
            } else {
                $masterModel = new MasterDataModel();
                $targetBed = $masterModel->find('beds', $toBedId);

                if (!$targetBed) {
                    $this->setFlash('danger', 'Tempat tidur tujuan tidak valid.');
                } else {
                    try {
                        $model->transferBed(
                            $admissionId,
                            (int)$targetBed['room_id'],
                            $toBedId,
                            $reason,
                            (int)($_SESSION['user_id'] ?? 1),
                            $_POST['notes'] ?? null
                        );

                        AuditLogModel::log($_SESSION['user_id'] ?? null, 'TRANSFER_INPATIENT_BED', 'Rawat Inap', "Transfer pasien admisi #$admissionId ke Bed ID #$toBedId berhasil");
                        $this->setFlash('success', 'Pasien berhasil dipindahkan ke tempat tidur baru. Status bed lama telah dibebaskan.');
                        $this->redirect('index.php?c=inpatient&a=index');
                        return;
                    } catch (Exception $e) {
                        $this->setFlash('danger', 'Gagal melakukan transfer: ' . $e->getMessage());
                    }
                }
            }
        }

        $availableBeds = $model->getAvailableBeds();

        $this->view('inpatient/transfer', [
            'admission'     => $admission,
            'availableBeds' => $availableBeds,
        ]);
    }

    /**
     * Form Resume Medis / Discharge Summary Dokter DPJP
     */
    public function discharge(): void {
        $this->requireRole(['admin', 'dokter', 'petugas_rawat_inap']);

        $admissionId = (int)($_GET['id'] ?? ($_POST['admission_id'] ?? 0));
        $model = new InpatientModel();
        $admission = $model->getAdmissionById($admissionId);

        if (!$admission) {
            $this->setFlash('danger', 'Data rawat inap tidak ditemukan.');
            $this->redirect('index.php?c=inpatient&a=index');
            return;
        }

        $existingSummary = $model->getDischargeSummary($admissionId);

        // Ambil master ICD-10
        $masterModel = new MasterDataModel();
        $icdList = $masterModel->getAllIcd('', 200);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $icd10 = trim($_POST['discharge_diagnosis_icd10'] ?? '');

            // Kepatuhan SOP KARS: Diagnosis akhir ICD-10 wajib diisi
            if (empty($icd10)) {
                $this->setFlash('danger', 'Diagnosis Akhir ICD-10 Wajib Diisi pada Resume Medis!');
                $this->view('inpatient/discharge', [
                    'admission' => $admission,
                    'summary'   => $existingSummary,
                    'icdList'   => $icdList,
                    'postData'  => $_POST,
                ]);
                return;
            }

            try {
                $model->saveDischargeSummary([
                    'admission_id'              => $admissionId,
                    'patient_id'                => (int)$admission['patient_id'],
                    'doctor_id'                 => (int)($_SESSION['user_id'] ?? $admission['doctor_id']),
                    'admission_diagnosis_icd10' => $_POST['admission_diagnosis_icd10'] ?? null,
                    'discharge_diagnosis_icd10' => $icd10,
                    'procedures_icd9cm'         => $_POST['procedures_icd9cm'] ?? null,
                    'hospital_course'           => $_POST['hospital_course'] ?? 'Kondisi klinis membaik setelah terapi adekuat.',
                    'condition_at_discharge'    => $_POST['condition_at_discharge'] ?? 'MEMBAIK',
                    'discharge_instructions'    => $_POST['discharge_instructions'] ?? null,
                    'take_home_medications'     => $_POST['take_home_medications'] ?? null,
                    'control_date'              => !empty($_POST['control_date']) ? $_POST['control_date'] : null,
                ]);

                AuditLogModel::log($_SESSION['user_id'] ?? null, 'SAVE_DISCHARGE_SUMMARY', 'Rawat Inap', "Resume medis admisi #$admissionId berhasil disimpan");
                $this->setFlash('success', 'Resume Medis / Discharge Summary berhasil disimpan oleh Dokter DPJP.');

                // Jika ada aksi confirm_discharge sekaligus
                if (!empty($_POST['confirm_discharge_now'])) {
                    $model->confirmDischarge($admissionId);
                    AuditLogModel::log($_SESSION['user_id'] ?? null, 'CONFIRM_DISCHARGE', 'Rawat Inap', "Pasien admisi #$admissionId dipulangkan & bed dibebaskan");
                    $this->setFlash('success', 'Pasien berhasil dipulangkan (Discharged). Tempat tidur telah otomatis dibebaskan.');
                    $this->redirect('index.php?c=inpatient&a=index');
                    return;
                }

                $this->redirect('index.php?c=inpatient&a=printSummary&id=' . $admissionId);
                return;
            } catch (Exception $e) {
                $this->setFlash('danger', 'Gagal menyimpan resume medis: ' . $e->getMessage());
            }
        }

        $this->view('inpatient/discharge', [
            'admission' => $admission,
            'summary'   => $existingSummary,
            'icdList'   => $icdList,
        ]);
    }

    /**
     * Konfirmasi Discharge & Pembebasan Bed Mandiri
     */
    public function confirmDischarge(): void {
        $this->requireRole(['admin', 'petugas_rawat_inap', 'kasir']);

        $admissionId = (int)($_POST['admission_id'] ?? 0);
        $model = new InpatientModel();

        try {
            $model->confirmDischarge($admissionId);
            AuditLogModel::log($_SESSION['user_id'] ?? null, 'CONFIRM_DISCHARGE', 'Rawat Inap', "Pasien admisi #$admissionId dipulangkan & bed dibebaskan");
            $this->setFlash('success', 'Status pasien telah diubah menjadi Discharged dan tempat tidur telah kembali TERSEDIA.');
        } catch (Exception $e) {
            $this->setFlash('danger', 'Gagal memproses kepulangan: ' . $e->getMessage());
        }

        $this->redirect('index.php?c=inpatient&a=index');
    }

    /**
     * Tinjau Visual Okupansi Tempat Tidur (Bed Management Matrix)
     */
    public function beds(): void {
        $model = new InpatientModel();
        $occupancyOverview = $model->getBedOccupancyOverview();
        $availableBeds = $model->getAvailableBeds();

        $this->view('inpatient/beds', [
            'occupancyOverview' => $occupancyOverview,
            'availableBeds'     => $availableBeds,
        ]);
    }

    /**
     * Cetak Lembar Resmi Resume Medis / Discharge Summary
     */
    public function printSummary(): void {
        $admissionId = (int)($_GET['id'] ?? 0);
        $model = new InpatientModel();
        $admission = $model->getAdmissionById($admissionId);

        if (!$admission) {
            $this->setFlash('danger', 'Data rawat inap tidak ditemukan.');
            $this->redirect('index.php?c=inpatient&a=index');
            return;
        }

        $summary = $model->getDischargeSummary($admissionId);

        $this->view('inpatient/print_summary', [
            'admission' => $admission,
            'summary'   => $summary,
        ]);
    }
}
