<?php
// app/controllers/PharmacyController.php - Pelayanan Farmasi (SOP/SIMRS/010/2026)

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/PharmacyModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';

class PharmacyController extends Controller {

    public function __construct() {
        $this->requireRole(['admin', 'apoteker']);
    }

    /**
     * Antrean Resep Masuk & Pengkajian Farmasi (SOP Poin 1)
     */
    public function index(): void {
        $model = new PharmacyModel();

        $status = $_GET['status'] ?? 'PENDING';
        $search = trim($_GET['search'] ?? '');
        $visitType = $_GET['visit_type'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $filters = [];
        if ($status !== 'ALL') {
            $filters['status'] = $status;
        }
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if (!empty($visitType)) {
            $filters['visit_type'] = $visitType;
        }

        $prescriptions = $model->getPrescriptionQueue($filters, $limit, $offset);
        $totalItems = $model->countPrescriptionQueue($filters);
        $totalPages = max(1, (int)ceil($totalItems / $limit));
        $stats = $model->getPharmacyStats();

        $this->view('pharmacy/index', [
            'pageTitle'     => 'Pelayanan Farmasi - Antrean Resep',
            'prescriptions' => $prescriptions,
            'stats'         => $stats,
            'currentStatus' => $status,
            'search'        => $search,
            'visitType'     => $visitType,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'totalItems'    => $totalItems
        ]);
    }

    /**
     * Telaah Resep & Eksekusi Dispensing "Selesai & Potong Stok" (SOP Poin 2-6)
     */
    public function dispense(): void {
        $this->requireRole(['admin', 'apoteker']);
        $model = new PharmacyModel();
        $orderId = (int)($_GET['id'] ?? $_POST['order_id'] ?? 0);

        if ($orderId <= 0) {
            $this->setFlash('danger', 'ID Resep tidak valid.');
            $this->redirect('index.php?c=pharmacy');
        }

        $prescription = $model->getPrescriptionDetail($orderId);
        if (!$prescription) {
            $this->setFlash('danger', 'Resep obat tidak ditemukan.');
            $this->redirect('index.php?c=pharmacy');
        }

        // Handle POST: Selesai & Potong Stok
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $user = $this->currentUser();
            $notes = trim($_POST['notes'] ?? '');

            try {
                $res = $model->dispenseAndDeductStock($orderId, (int)$user['id'], $notes);
                $this->setFlash('success', "Resep {$res['order_number']} berhasil diserahkan dan stok obat telah dipotong otomatis. Tagihan diteruskan ke Billing kasir.");
                $this->redirect('index.php?c=pharmacy');
            } catch (Exception $e) {
                $this->setFlash('danger', 'Gagal memproses resep: ' . $e->getMessage());
                $this->redirect("index.php?c=pharmacy&a=dispense&id={$orderId}");
            }
        }

        // GET: Tampilkan telaah resep & validasi stok
        $stockCheck = $model->checkStockSufficiency($orderId);

        $this->view('pharmacy/dispense', [
            'pageTitle'    => 'Telaah & Dispensing Resep: ' . $prescription['order_number'],
            'prescription' => $prescription,
            'stockCheck'   => $stockCheck
        ]);
    }

    /**
     * Apoteker menyimpan perubahan kuantitas obat pada telaah resep
     */
    public function updateQuantity(): void {
        $this->requireRole(['admin', 'apoteker']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?c=pharmacy');
        }

        $this->validateCsrf();
        $user = $this->currentUser();
        $orderId = (int)($_POST['order_id'] ?? 0);
        $items = $_POST['items'] ?? [];

        if ($orderId <= 0 || empty($items)) {
            $this->setFlash('danger', 'Data perubahan kuantitas resep tidak valid.');
            $this->redirect('index.php?c=pharmacy');
        }

        try {
            $model = new PharmacyModel();
            $model->updatePrescriptionQuantities($orderId, $items, (int)$user['id']);
            $this->setFlash('success', 'Perubahan jumlah obat dan kalkulasi total tagihan resep berhasil disimpan.');
        } catch (Exception $e) {
            $this->setFlash('danger', 'Gagal memperbarui kuantitas obat: ' . $e->getMessage());
        }

        $this->redirect("index.php?c=pharmacy&a=dispense&id={$orderId}");
    }

    /**
     * Manajemen Stok Obat Farmasi: Monitoring, Tambah Stok & Obat Baru (SOP Poin 7)
     */
    public function medicines(): void {
        $model = new PharmacyModel();
        $user = $this->currentUser();

        // Handle POST: Tambah Stok atau Tambah Obat Baru
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $action = $_POST['form_action'] ?? '';

            if ($action === 'add_stock') {
                $medId = (int)($_POST['medicine_id'] ?? 0);
                $qty = (int)($_POST['quantity'] ?? 0);
                $notes = trim($_POST['notes'] ?? '');

                try {
                    $model->addMedicineStock($medId, $qty, (int)$user['id'], $notes);
                    $this->setFlash('success', "Stok obat berhasil ditambahkan sebanyak {$qty}.");
                } catch (Exception $e) {
                    $this->setFlash('danger', 'Gagal menambah stok: ' . $e->getMessage());
                }
                $this->redirect('index.php?c=pharmacy&a=medicines');
            } elseif ($action === 'create_medicine') {
                try {
                    $newMed = $model->createMedicine($_POST, (int)$user['id']);
                    $this->setFlash('success', "Obat baru '{$newMed['name']}' berhasil didaftarkan ke katalog farmasi.");
                } catch (Exception $e) {
                    $this->setFlash('danger', 'Gagal menambah obat baru: ' . $e->getMessage());
                }
                $this->redirect('index.php?c=pharmacy&a=medicines');
            }
        }

        // GET: Tampilkan daftar stok obat
        $search = trim($_GET['search'] ?? '');
        $stockStatus = $_GET['stock_status'] ?? '';
        $expiryStatus = $_GET['expiry_status'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $filters = [];
        if (!empty($search)) $filters['search'] = $search;
        if (!empty($stockStatus)) $filters['stock_status'] = $stockStatus;
        if (!empty($expiryStatus)) $filters['expiry_status'] = $expiryStatus;

        $medicines = $model->getMedicines($filters, $limit, $offset);
        $totalItems = $model->countMedicines($filters);
        $totalPages = max(1, (int)ceil($totalItems / $limit));
        $stats = $model->getPharmacyStats();

        $this->view('pharmacy/medicines', [
            'pageTitle'    => 'Manajemen Stok Obat Farmasi',
            'medicines'    => $medicines,
            'stats'        => $stats,
            'search'       => $search,
            'stockStatus'  => $stockStatus,
            'expiryStatus' => $expiryStatus,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'totalItems'   => $totalItems
        ]);
    }
}
