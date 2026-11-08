<?php
// app/controllers/MasterController.php - Master Data & Konfigurasi Sistem

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/MasterDataModel.php';
require_once __DIR__ . '/../models/DoctorModel.php';
require_once __DIR__ . '/../models/PoliclinicModel.php';

class MasterController extends Controller {

    public MasterDataModel $masterModel;
    public DoctorModel $doctorModel;
    public PoliclinicModel $poliModel;

    public function __construct() {
        $this->requireRole('Admin Sistem');
        $this->masterModel = new MasterDataModel();
        $this->doctorModel = new DoctorModel();
        $this->poliModel = new PoliclinicModel();
    }

    public function index(): void {
        $policlinics = $this->poliModel->getAllPoliclinics();
        $doctors = $this->doctorModel->getAllDoctors();
        $rooms = $this->masterModel->getAllRooms();
        $tariffs = $this->masterModel->getAllTariffs();
        $medicines = $this->masterModel->getAllMedicines();

        $this->render('master_data/index', [
            'pageTitle'    => 'Pusat Master Data & Konfigurasi - SIMRS',
            'policlinics'  => $policlinics,
            'doctors'      => $doctors,
            'rooms'        => $rooms,
            'tariffs'      => $tariffs,
            'medicines'    => $medicines
        ]);
    }

    // 1. Poliklinik
    public function policlinics(): void {
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim($_POST['code'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $prefix = trim($_POST['queue_prefix'] ?? '');

            if (empty($code) || empty($name) || empty($prefix)) {
                $error = 'Kode, Nama Poli, dan Prefix Antrean wajib diisi.';
            } else {
                $this->poliModel->createPoliclinic([
                    'code'         => $code,
                    'name'         => $name,
                    'queue_prefix' => $prefix
                ]);
                $this->logAudit('CREATE_POLI', 'Master Data', "Menambahkan poliklinik baru {$code} - {$name}");
                $this->setFlash('success', "Poliklinik {$name} berhasil ditambahkan!");
                $this->redirect('index.php?c=master&a=policlinics');
            }
        }

        $policlinics = $this->poliModel->getAllPoliclinics();
        $this->render('master_data/policlinics', [
            'pageTitle'   => 'Master Poliklinik & Prefix Antrean',
            'policlinics' => $policlinics,
            'error'       => $error
        ]);
    }

    public function togglePoli(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->poliModel->togglePoliclinicActive($id);
            $this->setFlash('success', 'Status poliklinik berhasil diubah.');
        }
        $this->redirect('index.php?c=master&a=policlinics');
    }

    // 2. Dokter & Jadwal
    public function doctors(): void {
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $actionType = $_POST['action_type'] ?? 'create_doctor';

            if ($actionType === 'create_doctor') {
                $name = trim($_POST['name'] ?? '');
                $poliId = (int)($_POST['policlinic_id'] ?? 0);
                $sip = trim($_POST['sip_number'] ?? '');
                $phone = trim($_POST['phone'] ?? '');

                if (empty($name) || $poliId <= 0) {
                    $error = 'Nama Dokter dan Poliklinik wajib diisi.';
                } else {
                    $this->doctorModel->createDoctor([
                        'name'          => $name,
                        'policlinic_id' => $poliId,
                        'sip_number'    => $sip,
                        'phone'         => $phone,
                        'email'         => trim($_POST['email'] ?? '')
                    ]);
                    $this->logAudit('CREATE_DOCTOR', 'Master Data', "Menambahkan dokter baru: {$name}");
                    $this->setFlash('success', "Dokter {$name} berhasil ditambahkan!");
                    $this->redirect('index.php?c=master&a=doctors');
                }
            } elseif ($actionType === 'add_schedule') {
                $docId = (int)($_POST['doctor_id'] ?? 0);
                $day = trim($_POST['day_of_week'] ?? '');
                $startTime = trim($_POST['start_time'] ?? '');
                $endTime = trim($_POST['end_time'] ?? '');
                $quota = (int)($_POST['quota_per_day'] ?? 20);

                if ($docId <= 0 || empty($day) || empty($startTime) || empty($endTime)) {
                    $error = 'Dokter, Hari, Jam Mulai, dan Jam Selesai wajib diisi.';
                } else {
                    $this->doctorModel->addSchedule([
                        'doctor_id'     => $docId,
                        'day_of_week'   => $day,
                        'start_time'    => $startTime,
                        'end_time'      => $endTime,
                        'quota_per_day' => $quota
                    ]);
                    $this->logAudit('ADD_DOCTOR_SCHEDULE', 'Master Data', "Menambahkan jadwal dokter ID {$docId} pada hari {$day}");
                    $this->setFlash('success', 'Jadwal praktek dokter berhasil ditambahkan.');
                    $this->redirect('index.php?c=master&a=doctors');
                }
            }
        }

        $doctors = $this->doctorModel->getAllDoctors();
        if (!empty($doctors)) {
            $db = Database::getConnection();
            $docIds = array_column($doctors, 'id');
            $pIds = implode(',', array_map('intval', $docIds));
            $allScheds = $db->query("
                SELECT ds.id, ds.doctor_id, ds.day_name, ds.day_name AS day_of_week, 
                       ds.start_time, ds.end_time, ds.quota, ds.quota AS quota_per_day, ds.is_active,
                       pol.name AS policlinic_name
                FROM doctor_schedules ds
                JOIN doctors doc ON ds.doctor_id = doc.id
                LEFT JOIN policlinics pol ON doc.policlinic_id = pol.id
                WHERE ds.doctor_id IN ({$pIds})
                ORDER BY ds.id ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
            $schedMap = [];
            foreach ($allScheds as $sc) {
                $schedMap[$sc['doctor_id']][] = $sc;
            }
            foreach ($doctors as &$doc) {
                $doc['schedules'] = $schedMap[$doc['id']] ?? [];
            }
            unset($doc);
        }
        $policlinics = $this->poliModel->getAllPoliclinics(true);

        $this->render('master_data/doctors', [
            'pageTitle'   => 'Master Dokter & Jadwal Praktek',
            'doctors'     => $doctors,
            'policlinics' => $policlinics,
            'error'       => $error
        ]);
    }

    public function toggleDoctor(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->doctorModel->toggleDoctorActive($id);
            $this->setFlash('success', 'Status dokter berhasil diubah.');
        }
        $this->redirect('index.php?c=master&a=doctors');
    }

    // 3. Ruangan & Tempat Tidur
    public function rooms(): void {
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roomCode = trim($_POST['room_code'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $class = trim($_POST['class'] ?? '');
            $totalBeds = (int)($_POST['total_beds'] ?? 1);

            if (empty($roomCode) || empty($name) || empty($class) || $totalBeds < 1) {
                $error = 'Semua field ruangan dan minimal 1 tempat tidur wajib diisi.';
            } else {
                $this->masterModel->createRoom([
                    'room_code'  => $roomCode,
                    'name'       => $name,
                    'class'      => $class,
                    'total_beds' => $totalBeds
                ]);
                $this->logAudit('CREATE_ROOM', 'Master Data', "Menambahkan ruangan {$roomCode} - {$name} (Kapasitas: {$totalBeds} bed)");
                $this->setFlash('success', "Ruangan {$name} beserta {$totalBeds} tempat tidur berhasil didaftarkan!");
                $this->redirect('index.php?c=master&a=rooms');
            }
        }

        $rooms = $this->masterModel->getAllRooms();
        if (!empty($rooms)) {
            $db = Database::getConnection();
            $roomIds = array_column($rooms, 'id');
            $pIds = implode(',', array_map('intval', $roomIds));
            $allBeds = $db->query("SELECT id, room_id, bed_number, status FROM beds WHERE room_id IN ({$pIds}) ORDER BY bed_number ASC")->fetchAll(PDO::FETCH_ASSOC);
            $bedMap = [];
            foreach ($allBeds as $bd) {
                $bedMap[$bd['room_id']][] = $bd;
            }
            foreach ($rooms as &$room) {
                $room['beds'] = $bedMap[$room['id']] ?? [];
            }
            unset($room);
        }
        $this->render('master_data/rooms', [
            'pageTitle' => 'Master Ruangan & Tempat Tidur',
            'rooms'     => $rooms,
            'error'     => $error
        ]);
    }

    // 4. Tarif Layanan
    public function tariffs(): void {
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim($_POST['code'] ?? '');
            $name = trim($_POST['service_name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $price = (float)($_POST['price'] ?? 0);

            if (empty($code) || empty($name) || empty($category) || $price < 0) {
                $error = 'Semua field tarif tindakan wajib diisi dengan benar.';
            } else {
                $this->masterModel->createTariff([
                    'code'         => $code,
                    'service_name' => $name,
                    'category'     => $category,
                    'price'        => $price
                ]);
                $this->logAudit('CREATE_TARIFF', 'Master Data', "Menambahkan tarif {$code} - {$name} (Rp " . number_format($price, 0, ',', '.') . ")");
                $this->setFlash('success', "Tarif {$name} berhasil ditambahkan!");
                $this->redirect('index.php?c=master&a=tariffs');
            }
        }

        $tariffs = $this->masterModel->getAllTariffs();
        $this->render('master_data/tariffs', [
            'pageTitle' => 'Master Tarif Tindakan & Layanan',
            'tariffs'   => $tariffs,
            'error'     => $error
        ]);
    }

    // 5. ICD-10 Diagnosa
    public function icd(): void {
        $error = null;
        $search = trim($_GET['q'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim($_POST['code'] ?? '');
            $descId = trim($_POST['description_id'] ?? '');
            $descEn = trim($_POST['description_en'] ?? '');

            if (empty($code) || empty($descId)) {
                $error = 'Kode ICD dan Deskripsi Bahasa Indonesia wajib diisi.';
            } else {
                $this->masterModel->createIcd([
                    'code'           => $code,
                    'description_id' => $descId,
                    'description_en' => $descEn
                ]);
                $this->logAudit('CREATE_ICD', 'Master Data', "Menambahkan ICD-10: {$code} - {$descId}");
                $this->setFlash('success', "Kode ICD-10 {$code} berhasil ditambahkan!");
                $this->redirect('index.php?c=master&a=icd');
            }
        }

        $icdList = $this->masterModel->getAllIcd($search);
        $this->render('master_data/icd', [
            'pageTitle' => 'Master Klasifikasi Penyakit ICD-10',
            'icdList'   => $icdList,
            'search'    => $search,
            'error'     => $error
        ]);
    }

    // 6. Master Obat
    public function medicines(): void {
        $error = null;
        $search = trim($_GET['q'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim($_POST['code'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $unit = trim($_POST['unit'] ?? '');
            $stock = (int)($_POST['stock'] ?? 0);
            $price = (float)($_POST['unit_price'] ?? 0);

            if (empty($code) || empty($name) || empty($category) || empty($unit)) {
                $error = 'Semua field obat wajib diisi.';
            } else {
                $this->masterModel->createMedicine([
                    'code'       => $code,
                    'name'       => $name,
                    'category'   => $category,
                    'unit'       => $unit,
                    'stock'      => $stock,
                    'unit_price' => $price
                ]);
                $this->logAudit('CREATE_MEDICINE', 'Master Data', "Menambahkan obat {$code} - {$name}");
                $this->setFlash('success', "Obat {$name} berhasil didaftarkan!");
                $this->redirect('index.php?c=master&a=medicines');
            }
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = $this->masterModel->paginateMedicines($search, $page, 15);

        $this->render('master_data/medicines', [
            'pageTitle'  => 'Master Formularium Obat & Alkes',
            'medicines'  => $pagination['data'],
            'pagination' => $pagination,
            'search'     => $search,
            'error'      => $error
        ]);
    }

    // 7. Konfigurasi Sistem
    public function configurations(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $configs = $_POST['configs'] ?? [];
            foreach ($configs as $k => $v) {
                $this->masterModel->updateConfig(trim($k), trim($v));
            }
            $this->logAudit('UPDATE_CONFIG', 'Konfigurasi Sistem', "Memperbarui konfigurasi sistem");
            $this->setFlash('success', 'Konfigurasi sistem berhasil disimpan.');
            $this->redirect('index.php?c=master&a=configurations');
        }

        $configurations = $this->masterModel->getAllConfigurations();
        $this->render('master_data/configurations', [
            'pageTitle'      => 'Konfigurasi Sistem & Parameter Alur',
            'configurations' => $configurations
        ]);
    }

    // 8. Master Unit Pelayanan RS
    public function units(): void {
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim($_POST['unit_code'] ?? '');
            $name = trim($_POST['unit_name'] ?? '');
            $cat  = trim($_POST['category'] ?? 'Klinis');

            if (empty($code) || empty($name)) {
                $error = 'Kode Unit dan Nama Unit Pelayanan wajib diisi.';
            } else {
                try {
                    $this->masterModel->createHospitalUnit([
                        'unit_code' => $code,
                        'unit_name' => $name,
                        'category'  => $cat,
                        'is_active' => true
                    ]);
                    $this->logAudit('CREATE_UNIT', 'Master Data', "Menambahkan Unit Pelayanan RS: [{$code}] {$name} ({$cat})");
                    $this->setFlash('success', "Unit Pelayanan '{$name}' berhasil ditambahkan.");
                    $this->redirect('index.php?c=master&a=units');
                } catch (\Exception $e) {
                    $error = 'Gagal menyimpan unit: ' . $e->getMessage();
                }
            }
        }

        $units = $this->masterModel->getAllHospitalUnits(false);
        $this->render('master_data/units', [
            'pageTitle' => 'Master Data Unit Pelayanan RS - SIMRS Siloam Peduli',
            'units'     => $units,
            'error'     => $error
        ]);
    }
}

