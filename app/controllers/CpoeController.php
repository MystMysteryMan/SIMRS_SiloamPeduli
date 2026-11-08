<?php
// app/controllers/CpoeController.php - CPOE / Order Management (SOP/SIMRS/004/2026)

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/CpoeModel.php';
require_once __DIR__ . '/../models/PatientModel.php';
require_once __DIR__ . '/../models/DoctorModel.php';
require_once __DIR__ . '/../models/MasterDataModel.php';

class CpoeController extends Controller {

    public function __construct() {
        $this->requireRole(['admin', 'dokter', 'perawat', 'apoteker']);
    }

    /**
     * Dashboard CPOE: Daftar Order & Pelacakan Status Transparan
     */
    public function index(): void {
        $cpoeModel = new CpoeModel();

        $filters = [
            'status'     => $_GET['status'] ?? null,
            'order_type' => $_GET['order_type'] ?? null,
            'priority'   => $_GET['priority'] ?? null,
            'search'     => trim($_GET['search'] ?? '')
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = $cpoeModel->paginateOrders($filters, $page, 15);
        $stats = $cpoeModel->getCpoeStats();

        $this->render('cpoe/index', [
            'pageTitle'  => 'CPOE / Order Management - SIMRS Siloam Peduli',
            'orders'     => $pagination['data'],
            'pagination' => $pagination,
            'stats'      => $stats,
            'filters'    => $filters
        ]);
    }

    /**
     * Form Pembuatan Order CPOE Baru (E-Resep / Tindakan Medis)
     */
    public function create(): void {
        $this->requireRole(['admin', 'dokter']);
        $patientModel = new PatientModel();
        $doctorModel = new DoctorModel();
        $masterModel = new MasterDataModel();

        $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;
        $selectedPatient = null;
        if ($patientId) {
            $selectedPatient = $patientModel->findById($patientId);
        }

        $patients = $patientModel->findAll(['limit' => 50]);
        $doctors = $doctorModel->findAll(['is_active' => 1]);
        $medicines = $masterModel->getAllMedicines();
        $tariffs = $masterModel->getAllTariffs('TINDAKAN');

        // Cek riwayat alergi pasien jika pasien sudah dipilih
        $allergies = [];
        if ($selectedPatient) {
            $cpoeModel = new CpoeModel();
            $allergies = $cpoeModel->query("SELECT * FROM patient_allergies WHERE patient_id = :pid", [':pid' => $patientId]);
        }

        $this->render('cpoe/create', [
            'pageTitle'        => 'Buat Clinical Order (CPOE) - SIMRS Siloam Peduli',
            'selectedPatient'  => $selectedPatient,
            'patients'         => $patients,
            'doctors'          => $doctors,
            'medicines'        => $medicines,
            'tariffs'          => $tariffs,
            'allergies'        => $allergies,
            'visitType'        => $_GET['visit_type'] ?? 'RAWAT_JALAN',
            'referenceId'      => $_GET['reference_id'] ?? null
        ]);
    }

    /**
     * Simpan Order CPOE Baru
     */
    public function store(): void {
        $this->requireRole(['admin', 'dokter']);
        $this->validateCsrf();

        $patientId = (int)($_POST['patient_id'] ?? 0);
        $doctorId = (int)($_POST['doctor_id'] ?? 0);
        $orderType = $_POST['order_type'] ?? 'RESEP_OBAT';
        $priority = $_POST['priority'] ?? 'REGULER';
        $visitType = $_POST['visit_type'] ?? 'RAWAT_JALAN';
        $referenceId = !empty($_POST['reference_id']) ? (int)$_POST['reference_id'] : null;
        $clinicalNotes = trim($_POST['clinical_notes'] ?? '');

        if (!$patientId || !$doctorId) {
            $this->setFlash('danger', 'Pasien dan Dokter DPJP wajib dipilih!');
            $this->redirect('index.php?c=cpoe&a=create');
        }

        // Susun item order
        $items = [];
        if ($orderType === 'RESEP_OBAT') {
            $medIds = $_POST['medicine_id'] ?? [];
            $qtys = $_POST['med_quantity'] ?? [];
            $dosages = $_POST['med_dosage'] ?? [];
            $freqs = $_POST['med_frequency'] ?? [];
            $routes = $_POST['med_route'] ?? [];
            $insts = $_POST['med_instructions'] ?? [];

            for ($i = 0; $i < count($medIds); $i++) {
                if (!empty($medIds[$i])) {
                    $items[] = [
                        'item_type'    => 'MEDICINE',
                        'item_id'      => (int)$medIds[$i],
                        'quantity'     => (int)($qtys[$i] ?? 1),
                        'dosage'       => trim($dosages[$i] ?? ''),
                        'frequency'    => trim($freqs[$i] ?? ''),
                        'route'        => trim($routes[$i] ?? 'Oral'),
                        'instructions' => trim($insts[$i] ?? '')
                    ];
                }
            }
        } elseif ($orderType === 'TINDAKAN_MEDIS') {
            $tarIds = $_POST['tariff_id'] ?? [];
            $qtys = $_POST['proc_quantity'] ?? [];
            $insts = $_POST['proc_instructions'] ?? [];

            for ($i = 0; $i < count($tarIds); $i++) {
                if (!empty($tarIds[$i])) {
                    $items[] = [
                        'item_type'    => 'PROCEDURE',
                        'item_id'      => (int)$tarIds[$i],
                        'quantity'     => (int)($qtys[$i] ?? 1),
                        'instructions' => trim($insts[$i] ?? '')
                    ];
                }
            }
        }

        if (empty($items)) {
            $this->setFlash('danger', 'Minimal harus menambahkan 1 item pelayanan pada order CPOE!');
            $this->redirect('index.php?c=cpoe&a=create&patient_id=' . $patientId);
        }

        $cpoeModel = new CpoeModel();
        $user = $this->currentUser();

        $res = $cpoeModel->createOrder([
            'patient_id'     => $patientId,
            'doctor_id'      => $doctorId,
            'visit_type'     => $visitType,
            'reference_id'   => $referenceId,
            'order_type'     => $orderType,
            'priority'       => $priority,
            'clinical_notes' => $clinicalNotes
        ], $items, $user['id']);

        if ($res['success']) {
            $this->setFlash('success', "Order CPOE berhasil diterbitkan dengan nomor {$res['order_number']}!");
            if ($orderType === 'RESEP_OBAT' && in_array($user['role'], ['apoteker'])) {
                $this->redirect('index.php?c=cpoe&a=pharmacyQueue');
            } else {
                $this->redirect('index.php?c=cpoe&a=index');
            }
        } else {
            $this->setFlash('danger', 'Gagal membuat order: ' . $res['message']);
            $this->redirect('index.php?c=cpoe&a=create&patient_id=' . $patientId);
        }
    }

    /**
     * Antrean Farmasi / Apotek (Dispensing Resep Obat)
     */
    public function pharmacy(): void {
        $this->pharmacyQueue();
    }

    public function pharmacyQueue(): void {
        $this->requireRole(['admin', 'apoteker']);

        $cpoeModel = new CpoeModel();
        $queue = $cpoeModel->getPharmacyQueue();
        $stats = $cpoeModel->getCpoeStats();

        $this->render('cpoe/pharmacy_queue', [
            'pageTitle' => 'Antrean Farmasi & Dispensing Obat - SIMRS Siloam Peduli',
            'queue'     => $queue,
            'stats'     => $stats
        ]);
    }

    /**
     * Tindakan Dispensing Selesai oleh Apoteker
     */
    public function dispense(): void {
        $this->requireRole(['admin', 'apoteker']);
        $this->validateCsrf();

        $orderId = (int)($_POST['order_id'] ?? 0);
        if (!$orderId) {
            $this->setFlash('danger', 'ID Order tidak valid');
            $this->redirect('index.php?c=cpoe&a=pharmacyQueue');
        }

        $cpoeModel = new CpoeModel();
        $user = $this->currentUser();

        if ($cpoeModel->dispensePrescription($orderId, $user['id'])) {
            $this->setFlash('success', 'Obat berhasil disiapkan dan diserahkan ke pasien (Status: DISPENSED/COMPLETED)!');
        } else {
            $this->setFlash('danger', 'Gagal memproses dispensing obat.');
        }

        $this->redirect('index.php?c=cpoe&a=pharmacyQueue');
    }

    /**
     * Tindakan Keperawatan Selesai oleh Perawat
     */
    public function executeNurse(): void {
        $this->requireRole(['admin', 'perawat']);
        $this->validateCsrf();

        $orderId = (int)($_POST['order_id'] ?? 0);
        if (!$orderId) {
            $this->setFlash('danger', 'ID Order tidak valid');
            $this->redirect('index.php?c=cpoe&a=index');
        }

        $cpoeModel = new CpoeModel();
        $user = $this->currentUser();

        if ($cpoeModel->executeNursingOrder($orderId, $user['id'])) {
            $this->setFlash('success', 'Tindakan medis/keperawatan berhasil dilaksanakan dan tercatat selesai!');
        } else {
            $this->setFlash('danger', 'Gagal memperbarui status tindakan keperawatan.');
        }

        $this->redirect('index.php?c=cpoe&a=index');
    }

    /**
     * Batalkan Order CPOE
     */
    public function cancel(): void {
        $this->requireRole(['admin', 'dokter']);
        $this->validateCsrf();

        $orderId = (int)($_POST['order_id'] ?? 0);
        $reason = trim($_POST['cancel_reason'] ?? 'Dibatalkan oleh staf medis');

        if (!$orderId) {
            $this->setFlash('danger', 'ID Order tidak valid');
            $this->redirect('index.php?c=cpoe&a=index');
        }

        $cpoeModel = new CpoeModel();
        $user = $this->currentUser();

        if ($cpoeModel->cancelOrder($orderId, $user['id'], $reason)) {
            $this->setFlash('success', 'Order CPOE berhasil dibatalkan.');
        } else {
            $this->setFlash('danger', 'Gagal membatalkan order.');
        }

        $this->redirect('index.php?c=cpoe&a=index');
    }

    /**
     * Detail Modal / Pratinjau Order CPOE
     */
    public function detail(): void {
        $orderId = (int)($_GET['id'] ?? 0);
        $cpoeModel = new CpoeModel();
        $order = $cpoeModel->getOrderDetail($orderId);

        if (!$order) {
            $this->setFlash('danger', 'Order tidak ditemukan');
            $this->redirect('index.php?c=cpoe&a=index');
        }

        $this->json(['success' => true, 'order' => $order]);
    }
}
