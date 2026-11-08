<?php
// app/controllers/InventoryController.php - Pengelolaan Persediaan & Gudang Logistik (SOP/SIMRS/014/2026)

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/InventoryModel.php';
require_once __DIR__ . '/../models/PharmacyModel.php';

class InventoryController extends Controller {

    public function __construct() {
        $this->requireRole(['admin', 'petugas_gudang']);
    }

    /**
     * Monitoring Data Stok & Peringatan Kedaluwarsa (SOP Poin 1)
     */
    public function index(): void {
        $model = new InventoryModel();

        $search = trim($_GET['search'] ?? '');
        $location = trim($_GET['location'] ?? '');
        $stockStatus = $_GET['stock_status'] ?? '';
        $expiryStatus = $_GET['expiry_status'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $filters = [];
        if (!empty($search)) $filters['search'] = $search;
        if (!empty($location)) $filters['location'] = $location;
        if (!empty($stockStatus)) $filters['stock_status'] = $stockStatus;
        if (!empty($expiryStatus)) $filters['expiry_status'] = $expiryStatus;

        $items = $model->getStockList($filters, $limit, $offset);
        $totalItems = $model->countStockList($filters);
        $totalPages = max(1, (int)ceil($totalItems / $limit));
        $stats = $model->getInventoryStats();

        $this->view('inventory/index', [
            'pageTitle'    => 'Pengelolaan Persediaan & Gudang Logistik',
            'items'        => $items,
            'stats'        => $stats,
            'search'       => $search,
            'location'     => $location,
            'stockStatus'  => $stockStatus,
            'expiryStatus' => $expiryStatus,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'totalItems'   => $totalItems
        ]);
    }

    /**
     * Pengajuan Pembelian / Purchase Request (PR) (SOP Poin 2)
     */
    public function purchaseRequest(): void {
        $model = new InventoryModel();
        $user = $this->currentUser();

        // Handle POST: Create PR or Update Status
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $action = $_POST['form_action'] ?? 'create';

            if ($action === 'create') {
                try {
                    $res = $model->createPurchaseRequest($_POST, (int)$user['id']);
                    $this->setFlash('success', "Purchase Request {$res['pr_number']} berhasil diajukan dengan status 'Diajukan'.");
                } catch (Exception $e) {
                    $this->setFlash('danger', 'Gagal mengajukan PR: ' . $e->getMessage());
                }
            } elseif ($action === 'update_status') {
                $prId = (int)($_POST['pr_id'] ?? 0);
                $status = $_POST['status'] ?? 'APPROVED';
                try {
                    $model->updatePRStatus($prId, $status, (int)$user['id']);
                    $this->setFlash('success', "Status Purchase Request berhasil diperbarui menjadi {$status}.");
                } catch (Exception $e) {
                    $this->setFlash('danger', 'Gagal memperbarui status: ' . $e->getMessage());
                }
            }
            $this->redirect('index.php?c=inventory&a=purchaseRequest');
        }

        // GET: Tampilkan daftar PR
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $filters = [];
        if (!empty($search)) $filters['search'] = $search;
        if (!empty($status)) $filters['status'] = $status;

        $requests = $model->getPurchaseRequests($filters, $limit, $offset);
        $totalItems = $model->countPurchaseRequests($filters);
        $totalPages = max(1, (int)ceil($totalItems / $limit));

        // Ambil list obat untuk dropdown PR
        $pharmacyModel = new PharmacyModel();
        $medicines = $pharmacyModel->getMedicines([], 100, 0);

        $this->view('inventory/purchase_request', [
            'pageTitle'  => 'Purchase Request (Pengajuan Pembelian)',
            'requests'   => $requests,
            'medicines'  => $medicines,
            'search'     => $search,
            'status'     => $status,
            'page'       => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems
        ]);
    }

    /**
     * Penerimaan Barang Masuk (Goods In) & Expiry Date (SOP Poin 3)
     */
    public function goodsIn(): void {
        $model = new InventoryModel();
        $pharmacyModel = new PharmacyModel();
        $user = $this->currentUser();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            try {
                $res = $model->receiveGoods($_POST, (int)$user['id']);
                $this->setFlash('success', "Penerimaan barang masuk berhasil diproses ({$res['transaction_number']}). Saldo stok terbarui: {$res['new_stock']}.");
                $this->redirect('index.php?c=inventory&a=goodsIn');
            } catch (Exception $e) {
                $this->setFlash('danger', 'Gagal menerima barang: ' . $e->getMessage());
                $this->redirect('index.php?c=inventory&a=goodsIn');
            }
        }

        // GET: Form penerimaan & riwayat penerimaan terbaru
        $medicines = $pharmacyModel->getMedicines([], 100, 0);
        $recentTransactions = $model->getTransactions(['transaction_type' => 'MASUK'], 10, 0);

        $this->view('inventory/goods_in', [
            'pageTitle'          => 'Penerimaan Barang Masuk (Goods Receipt)',
            'medicines'          => $medicines,
            'recentTransactions' => $recentTransactions
        ]);
    }

    /**
     * Pengeluaran Barang ke Unit (Goods Out) (SOP Poin 4)
     */
    public function goodsOut(): void {
        $model = new InventoryModel();
        $pharmacyModel = new PharmacyModel();
        $user = $this->currentUser();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            try {
                $res = $model->issueGoods($_POST, (int)$user['id']);
                $this->setFlash('success', "Pengeluaran barang ke unit berhasil dicatat ({$res['transaction_number']}). Sisa stok: {$res['new_stock']}.");
                $this->redirect('index.php?c=inventory&a=goodsOut');
            } catch (Exception $e) {
                $this->setFlash('danger', 'Gagal mengeluarkan barang: ' . $e->getMessage());
                $this->redirect('index.php?c=inventory&a=goodsOut');
            }
        }

        // GET: Form pengeluaran & riwayat pengeluaran terbaru
        $medicines = $pharmacyModel->getMedicines([], 100, 0);
        $recentTransactions = $model->getTransactions(['transaction_type' => 'KELUAR'], 10, 0);

        $this->view('inventory/goods_out', [
            'pageTitle'          => 'Pengeluaran Barang ke Unit (Goods Issue)',
            'medicines'          => $medicines,
            'recentTransactions' => $recentTransactions
        ]);
    }

    /**
     * Mutasi Stok Antar-Lokasi & Buku Mutasi Persediaan (SOP Poin 4 & Buku Mutasi)
     */
    public function stockMutation(): void {
        $model = new InventoryModel();
        $pharmacyModel = new PharmacyModel();
        $user = $this->currentUser();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            try {
                $res = $model->mutateStock($_POST, (int)$user['id']);
                $this->setFlash('success', "Mutasi stok persediaan {$res['item_name']} berhasil dicatat dengan nomor transaksi {$res['transaction_number']}.");
                $this->redirect('index.php?c=inventory&a=stockMutation');
            } catch (Exception $e) {
                $this->setFlash('danger', 'Gagal memproses mutasi stok: ' . $e->getMessage());
                $this->redirect('index.php?c=inventory&a=stockMutation');
            }
        }

        // GET: Form mutasi & Buku Mutasi Lengkap
        $search = trim($_GET['search'] ?? '');
        $type = $_GET['type'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $filters = [];
        if (!empty($search)) $filters['search'] = $search;
        if (!empty($type)) $filters['transaction_type'] = $type;

        $medicines = $pharmacyModel->getMedicines([], 100, 0);
        $transactions = $model->getTransactions($filters, $limit, $offset);
        $totalItems = $model->countTransactions($filters);
        $totalPages = max(1, (int)ceil($totalItems / $limit));

        $this->view('inventory/stock_mutation', [
            'pageTitle'    => 'Mutasi Persediaan & Buku Mutasi',
            'medicines'    => $medicines,
            'transactions' => $transactions,
            'search'       => $search,
            'currentType'  => $type,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'totalItems'   => $totalItems
        ]);
    }
}
