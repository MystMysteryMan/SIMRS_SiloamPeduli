<?php
// app/controllers/RadiologyController.php - Radiologi (SOP/SIMRS/005/2026)

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/RadiologyModel.php';
require_once __DIR__ . '/../models/PatientModel.php';

class RadiologyController extends Controller {

    public function __construct() {
        // Images are also read from the existing EMR by doctors and nurses.
        $roles = ['admin', 'radiografer', 'dokter_radiologi'];
        if (strtolower((string)($_GET['a'] ?? '')) === 'image') {
            $roles = array_merge($roles, ['dokter', 'perawat']);
        }
        $this->requireRole($roles);
    }

    public function image(): void {
        $image = (new RadiologyModel())->getStoredImage((int)($_GET['id'] ?? 0));
        $bytes = $image ? base64_decode($image['image_data'], true) : false;
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
        if (!$image || $bytes === false || strlen($bytes) !== (int)$image['byte_size']) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Citra radiologi tidak ditemukan.';
            return;
        }
        header('Content-Type: ' . $image['mime_type']);
        header('Content-Length: ' . strlen($bytes));
        header('Content-Disposition: inline');
        echo $bytes;
    }

    /**
     * Daftar Permintaan Pemeriksaan Radiologi & Antrean Tindakan
     */
    public function index(): void {
        $radModel = new RadiologyModel();

        $status = $_GET['status'] ?? null;
        $priority = $_GET['priority'] ?? null;
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));

        $pagination = $radModel->paginateOrders($status, $priority, $search, $page, 15);
        $stats = $radModel->getRadiologyStats();

        $this->render('radiology/index', [
            'pageTitle'        => 'Instalasi Radiologi & Diagnostik Imaging - SIMRS',
            'orders'           => $pagination['data'],
            'pagination'       => $pagination,
            'selectedStatus'   => $status,
            'selectedPriority' => $priority,
            'search'           => $search,
            'stats'            => $stats
        ]);
    }

    /**
     * Detail Order, Unggah Citra Medis (Radiografer), dan Ekspertise (Spesialis Radiologi)
     */
    public function detail(): void {
        $orderId = (int)($_GET['id'] ?? 0);
        if ($orderId <= 0) {
            $this->setFlash('danger', 'ID Order Radiologi tidak valid.');
            $this->redirect('index.php?c=radiology&a=index');
        }

        $radModel = new RadiologyModel();
        $order = $radModel->getOrderById($orderId);

        if (!$order) {
            $this->setFlash('danger', 'Data Order Radiologi tidak ditemukan.');
            $this->redirect('index.php?c=radiology&a=index');
        }

        $userRole = strtolower($_SESSION['role'] ?? '');
        $canUpload = in_array($userRole, ['admin', 'radiografer']);
        $canExpertise = in_array($userRole, ['admin', 'dokter_radiologi', 'dokter']);

        $this->render('radiology/process', [
            'pageTitle'    => 'Pemeriksaan Radiologi - ' . $order['order_number'],
            'order'        => $order,
            'canUpload'    => $canUpload,
            'canExpertise' => $canExpertise
        ]);
    }

    /**
     * Radiografer memulai tindakan pemotretan (PENDING -> PROCESS)
     */
    public function process(): void {
        $this->requireRole(['admin', 'radiografer']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?c=radiology&a=index');
        }

        if (!verify_csrf()) {
            $this->setFlash('danger', 'Token keamanan CSRF tidak valid.');
            $this->redirect('index.php?c=radiology&a=index');
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $radiographerId = (int)($_SESSION['user_id'] ?? 0);

        $radModel = new RadiologyModel();
        $result = $radModel->processOrder($orderId, $radiographerId);

        if ($result['success']) {
            $this->setFlash('success', $result['message']);
        } else {
            $this->setFlash('danger', $result['message']);
        }

        $this->redirect('index.php?c=radiology&a=detail&id=' . $orderId);
    }

    /**
     * Radiografer Mengunggah Hasil Citra Radiologi (JPG, PNG, WebP <= 1MB)
     */
    public function upload(): void {
        $this->requireRole(['admin', 'radiografer']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?c=radiology&a=index');
        }

        if (!verify_csrf()) {
            $this->setFlash('danger', 'Token keamanan CSRF tidak valid.');
            $this->redirect('index.php?c=radiology&a=index');
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $technicalNotes = trim($_POST['technical_notes'] ?? '');
        $radiographerId = (int)($_SESSION['user_id'] ?? 0);

        if ($orderId <= 0 || empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('danger', 'Pilih berkas citra radiologi yang valid (JPG, PNG, atau WebP).');
            $this->redirect('index.php?c=radiology&a=detail&id=' . $orderId);
        }

        $radModel = new RadiologyModel();
        $result = $radModel->uploadImage($orderId, $_FILES['image'], $technicalNotes, $radiographerId);

        if ($result['success']) {
            $this->setFlash('success', $result['message']);
        } else {
            $this->setFlash('danger', $result['message']);
        }

        $this->redirect('index.php?c=radiology&a=detail&id=' . $orderId);
    }

    /**
     * Dokter Spesialis Radiologi Mengisi Bacaan Ekspertise & Validasi (PROCESS -> VALID)
     */
    public function saveExpertise(): void {
        $this->requireRole(['admin', 'dokter_radiologi']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?c=radiology&a=index');
        }

        if (!verify_csrf()) {
            $this->setFlash('danger', 'Token keamanan CSRF tidak valid.');
            $this->redirect('index.php?c=radiology&a=index');
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $findings = trim($_POST['expertise_findings'] ?? '');
        $conclusion = trim($_POST['expertise_conclusion'] ?? '');
        $radiologistId = (int)($_SESSION['user_id'] ?? 0);

        if ($orderId <= 0 || empty($findings) || empty($conclusion)) {
            $this->setFlash('danger', 'Temuan radiologi dan kesimpulan ekspertise wajib diisi lengkap.');
            $this->redirect('index.php?c=radiology&a=detail&id=' . $orderId);
        }

        $radModel = new RadiologyModel();
        $result = $radModel->saveExpertise($orderId, $findings, $conclusion, $radiologistId);

        if ($result['success']) {
            $this->setFlash('success', $result['message']);
        } else {
            $this->setFlash('danger', $result['message']);
        }

        $this->redirect('index.php?c=radiology&a=detail&id=' . $orderId);
    }

    /**
     * Cetak Lembar Hasil & Ekspertise Radiologi dengan Citra WebP
     */
    public function printResult(): void {
        $orderId = (int)($_GET['id'] ?? 0);
        if ($orderId <= 0) {
            die('ID Order tidak valid.');
        }

        $radModel = new RadiologyModel();
        $order = $radModel->getOrderById($orderId);

        if (!$order) {
            die('Order Radiologi tidak ditemukan.');
        }

        $this->render('radiology/print_result', [
            'pageTitle' => 'Cetak Ekspertise Radiologi - ' . $order['order_number'],
            'order'     => $order
        ], false);
    }
}

