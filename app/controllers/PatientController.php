<?php
// app/controllers/PatientController.php - Registrasi & Admisi Pasien (SOP/SIMRS/002/2026)

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/PatientModel.php';

class PatientController extends Controller {

    public function __construct() {
        // Hak akses pendaftaran pasien (Registrasi, Kasir/Pembayaran, IGD Triase, dan Admin Sistem)
        $this->requireRole(['admin', 'admin sistem', 'petugas registrasi', 'registrasi', 'kasir', 'triase']);
    }

    public function index(): void {
        $patientModel = new PatientModel();
        $keyword = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = $patientModel->paginatePatients($keyword, $page, 15);

        $this->render('patient_registration/index', [
            'pageTitle'  => 'Registrasi & Pencarian Pasien - SIMRS',
            'patients'   => $pagination['data'],
            'pagination' => $pagination,
            'keyword'    => $keyword
        ]);
    }

    public function create(): void {
        $error = null;
        $formData = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formData = $_POST;
            $nik = trim($_POST['nik'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $birthDate = trim($_POST['birth_date'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $insuranceType = trim($_POST['insurance_type'] ?? 'Umum');
            $insuranceNumber = trim($_POST['insurance_number'] ?? '');
            $bloodType = trim($_POST['blood_type'] ?? '');
            $emergencyContactName = trim($_POST['emergency_contact_name'] ?? '');
            $emergencyContactPhone = trim($_POST['emergency_contact_phone'] ?? '');

            // Validasi NIK 16 digit angka
            if (!preg_match('/^[0-9]{16}$/', $nik)) {
                $error = 'NIK KTP wajib terdiri dari tepat 16 digit angka.';
            } elseif (empty($name) || empty($gender) || empty($birthDate) || empty($address) || empty($phone)) {
                $error = 'Nama Lengkap, Jenis Kelamin, Tanggal Lahir, Alamat, dan No. HP wajib diisi.';
            } else {
                $patientModel = new PatientModel();

                // Cek duplikasi NIK
                if ($patientModel->isNikRegistered($nik)) {
                    $error = "NIK {$nik} sudah terdaftar di sistem! Silakan cari data pasien pada menu pencarian pasien lama.";
                } else {
                    $patientId = $patientModel->createPatient([
                        'nik'                     => $nik,
                        'name'                    => $name,
                        'gender'                  => $gender,
                        'birth_date'              => $birthDate,
                        'blood_type'              => $bloodType,
                        'address'                 => $address,
                        'phone'                   => $phone,
                        'insurance_type'          => $insuranceType,
                        'insurance_number'        => $insuranceNumber,
                        'emergency_contact_name'  => $emergencyContactName,
                        'emergency_contact_phone' => $emergencyContactPhone
                    ]);

                    $patient = $patientModel->getPatientById($patientId);
                    $source = trim($_POST['source'] ?? $_GET['source'] ?? '');

                    $this->logAudit('REGISTER_PATIENT', 'Registrasi Pasien', "Mendaftarkan pasien baru No. RM {$patient['no_rm']} - {$name} (NIK: {$nik})");

                    if ($source === 'igd') {
                        $this->setFlash('success', "Pasien baru berhasil didaftarkan: {$patient['no_rm']} - {$name}. Melanjutkan alur IGD...");
                        $this->redirect('index.php?c=igd&a=create&patient_id=' . $patientId);
                    } elseif ($source === 'billing') {
                        $this->setFlash('success', "Pasien baru No. RM {$patient['no_rm']} berhasil didaftarkan untuk kasir.");
                        $this->redirect('index.php?c=billing&a=index');
                    } else {
                        $this->setFlash('success', "Pasien berhasil didaftarkan dengan Nomor Rekam Medis: {$patient['no_rm']}");
                        $this->redirect('index.php?c=patient&a=printCard&id=' . $patientId);
                    }
                }
            }
        }

        $this->render('patient_registration/create', [
            'pageTitle' => 'Pendaftaran Pasien Baru - SIMRS',
            'error'     => $error,
            'formData'  => $formData
        ]);
    }

    public function edit(): void {
        $patientId = (int)($_GET['id'] ?? 0);
        $patientModel = new PatientModel();
        $patient = $patientModel->getPatientById($patientId);

        if (!$patient) {
            $this->setFlash('danger', 'Data pasien tidak ditemukan.');
            $this->redirect('index.php?c=patient&a=index');
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nik = trim($_POST['nik'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $birthDate = trim($_POST['birth_date'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if (!preg_match('/^[0-9]{16}$/', $nik)) {
                $error = 'NIK KTP wajib terdiri dari tepat 16 digit angka.';
            } elseif (empty($name) || empty($gender) || empty($birthDate) || empty($address) || empty($phone)) {
                $error = 'Nama Lengkap, Jenis Kelamin, Tanggal Lahir, Alamat, dan No. HP wajib diisi.';
            } else {
                // Cek duplikasi NIK jika diubah
                if ($patientModel->isNikRegistered($nik, $patientId)) {
                    $error = "NIK {$nik} sudah digunakan oleh pasien lain.";
                } else {
                    $patientModel->updatePatient($patientId, [
                        'nik'                     => $nik,
                        'name'                    => $name,
                        'gender'                  => $gender,
                        'birth_date'              => $birthDate,
                        'blood_type'              => trim($_POST['blood_type'] ?? ''),
                        'address'                 => $address,
                        'phone'                   => $phone,
                        'insurance_type'          => trim($_POST['insurance_type'] ?? 'Umum'),
                        'insurance_number'        => trim($_POST['insurance_number'] ?? ''),
                        'emergency_contact_name'  => trim($_POST['emergency_contact_name'] ?? ''),
                        'emergency_contact_phone' => trim($_POST['emergency_contact_phone'] ?? '')
                    ]);

                    $this->logAudit('UPDATE_PATIENT', 'Registrasi Pasien', "Memperbarui identitas pasien No. RM {$patient['no_rm']} - {$name}");
                    $this->setFlash('success', "Data pasien {$patient['no_rm']} berhasil diperbarui.");
                    $this->redirect('index.php?c=patient&a=index');
                }
            }
        }

        $this->render('patient_registration/edit', [
            'pageTitle' => 'Edit Data Pasien - ' . $patient['no_rm'],
            'patient'   => $patient,
            'error'     => $error
        ]);
    }

    public function printCard(): void {
        $patientId = (int)($_GET['id'] ?? 0);
        $patientModel = new PatientModel();
        $patient = $patientModel->getPatientById($patientId);

        if (!$patient) {
            $this->setFlash('danger', 'Data pasien tidak ditemukan.');
            $this->redirect('index.php?c=patient&a=index');
        }

        $this->render('patient_registration/print_card', [
            'pageTitle' => 'Cetak Kartu Berobat - ' . $patient['no_rm'],
            'patient'   => $patient
        ], false); // No standard layout, clean printable card
    }

    public function apiSearch(): void {
        $keyword = trim($_GET['q'] ?? '');
        $patientModel = new PatientModel();
        $patients = $patientModel->searchPatients($keyword, 20);
        $this->json(['status' => 'success', 'data' => $patients]);
    }
}

