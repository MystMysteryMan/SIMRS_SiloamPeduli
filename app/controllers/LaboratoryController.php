<?php
// app/controllers/LaboratoryController.php - Laboratorium (SOP/SIMRS/004/2026)

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/LaboratoryModel.php';
require_once __DIR__ . '/../models/PatientModel.php';
require_once __DIR__ . '/../models/MasterDataModel.php';

class LaboratoryController extends Controller {

    public function __construct() {
        $this->requireRole(['admin', 'analis_laboratorium']);
    }

    /**
     * Daftar Permintaan Laboratorium & Antrean Pengujian
     */
    public function index(): void {
        $labModel = new LaboratoryModel();

        $status = $_GET['status'] ?? null;
        $priority = $_GET['priority'] ?? null;
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));

        $pagination = $labModel->paginateOrders($status, $priority, $search, $page, 15);
        $stats = $labModel->getLabStats();

        $this->render('laboratory/index', [
            'pageTitle'        => 'Pemeriksaan Laboratorium Sentral - SIMRS',
            'orders'           => $pagination['data'],
            'pagination'       => $pagination,
            'selectedStatus'   => $status,
            'selectedPriority' => $priority,
            'search'           => $search,
            'stats'            => $stats
        ]);
    }

    /**
     * Halaman Detail & Input Hasil Pemeriksaan Laboratorium
     */
    public function detail(): void {
        $orderId = (int)($_GET['id'] ?? 0);
        if ($orderId <= 0) {
            $this->setFlash('danger', 'ID Order Laboratorium tidak valid.');
            $this->redirect('index.php?c=laboratory&a=index');
        }

        $labModel = new LaboratoryModel();
        $order = $labModel->getOrderById($orderId);

        if (!$order) {
            $this->setFlash('danger', 'Data Order Laboratorium tidak ditemukan.');
            $this->redirect('index.php?c=laboratory&a=index');
        }

        $items = $labModel->getOrderItemsWithResults($orderId);

        $this->render('laboratory/process', [
            'pageTitle' => 'Proses Pengujian Lab - ' . $order['order_number'],
            'order'     => $order,
            'items'     => $items
        ]);
    }

    /**
     * Terima Permintaan & Ambil Sampel (PENDING -> PROCESS)
     */
    public function process(): void {
        $this->requireRole(['admin', 'analis_laboratorium']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?c=laboratory&a=index');
        }

        if (!verify_csrf()) {
            $this->setFlash('danger', 'Token keamanan CSRF tidak valid.');
            $this->redirect('index.php?c=laboratory&a=index');
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $analystId = (int)($_SESSION['user_id'] ?? 0);

        $labModel = new LaboratoryModel();
        $result = $labModel->processOrder($orderId, $analystId);

        if ($result['success']) {
            $this->setFlash('success', $result['message']);
        } else {
            $this->setFlash('danger', $result['message']);
        }

        $this->redirect('index.php?c=laboratory&a=detail&id=' . $orderId);
    }

    /**
     * Simpan Hasil Pengujian & Validasi Akhir (PROCESS -> VALID)
     */
    public function saveResult(): void {
        $this->requireRole(['admin', 'analis_laboratorium']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?c=laboratory&a=index');
        }

        if (!verify_csrf()) {
            $this->setFlash('danger', 'Token keamanan CSRF tidak valid.');
            $this->redirect('index.php?c=laboratory&a=index');
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $results = $_POST['results'] ?? [];
        $analystId = (int)($_SESSION['user_id'] ?? 0);

        if ($orderId <= 0 || empty($results)) {
            $this->setFlash('danger', 'Data hasil pemeriksaan tidak lengkap.');
            $this->redirect('index.php?c=laboratory&a=detail&id=' . $orderId);
        }

        $labModel = new LaboratoryModel();
        $res = $labModel->saveResults($orderId, $results, $analystId);

        if ($res['success']) {
            $this->setFlash('success', $res['message']);
            $this->redirect('index.php?c=laboratory&a=detail&id=' . $orderId);
        } else {
            $this->setFlash('danger', $res['message']);
            $this->redirect('index.php?c=laboratory&a=detail&id=' . $orderId);
        }
    }

    /**
     * Cetak Lembar Hasil Pemeriksaan Laboratorium (Resmi & Terverifikasi)
     */
    public function printResult(): void {
        $orderId = (int)($_GET['id'] ?? 0);
        if ($orderId <= 0) {
            die('ID Order tidak valid.');
        }

        $labModel = new LaboratoryModel();
        $order = $labModel->getOrderById($orderId);

        if (!$order) {
            die('Order Laboratorium tidak ditemukan.');
        }

        $items = $labModel->getOrderItemsWithResults($orderId);

        $this->render('laboratory/print_result', [
            'pageTitle' => 'Cetak Hasil Laboratorium - ' . $order['order_number'],
            'order'     => $order,
            'items'     => $items
        ], false);
    }
}

