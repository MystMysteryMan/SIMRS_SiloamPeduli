<?php
// app/controllers/BillingController.php - Billing & Insurance (SOP/SIMRS/011/2026)

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/BillingModel.php';
require_once __DIR__ . '/../models/PatientModel.php';
require_once __DIR__ . '/../models/DoctorModel.php';

class BillingController extends Controller {

    public function __construct() {
        $this->requireRole(['admin', 'kasir']);
    }

    /**
     * Dashboard Billing: Antrean Pasien Belum Terbit Invoice / Belum Lunas
     */
    public function index(): void {
        $billingModel = new BillingModel();

        $unbilled = $billingModel->getUnbilledVisits();
        $stats = $billingModel->getBillingStats();

        // Ambil juga riwayat invoice terbaru
        $recentInvoices = $billingModel->query("
            SELECT inv.*, 
                   p.full_name AS patient_name, p.no_rm,
                   COALESCE(d.full_name, 'Pelayanan Medis') AS doctor_name,
                   u.full_name AS cashier_name
            FROM invoices inv
            JOIN patients p ON inv.patient_id = p.id
            LEFT JOIN doctors d ON inv.doctor_id = d.id
            LEFT JOIN users u ON inv.cashier_id = u.id
            ORDER BY inv.id DESC
            LIMIT 20
        ");

        $this->render('billing/index', [
            'pageTitle'      => 'Billing, Kasir & Klaim Asuransi - SIMRS Siloam Peduli',
            'unbilled'       => $unbilled,
            'recentInvoices' => $recentInvoices,
            'stats'          => $stats
        ]);
    }

    /**
     * Pratinjau Agregasi Biaya & Pembuatan Faktur (Invoice Preview)
     */
    public function invoice(): void {
        $visitType = $_GET['visit_type'] ?? ($_GET['type'] ?? 'RAWAT_JALAN');
        $referenceId = (int)($_GET['reference_id'] ?? ($_GET['id'] ?? 0));

        if (!$referenceId) {
            $this->setFlash('danger', 'Parameter kunjungan tidak valid');
            $this->redirect('index.php?c=billing');
        }

        $billingModel = new BillingModel();
        $calc = $billingModel->aggregateVisitCosts($visitType, $referenceId);

        if (!$calc['success']) {
            $this->setFlash('danger', $calc['message']);
            $this->redirect('index.php?c=billing');
        }

        $this->render('billing/invoice', [
            'pageTitle' => 'Rincian Agregasi Tagihan Pasien - SIMRS Siloam Peduli',
            'calc'      => $calc
        ]);
    }

    /**
     * Penerbitan Invoice Resmi Tagihan Pasien
     */
    public function generateInvoice(): void {
        $this->validateCsrf();

        $data = [
            'visit_type'         => $_POST['visit_type'] ?? 'RAWAT_JALAN',
            'reference_id'       => (int)($_POST['reference_id'] ?? 0),
            'patient_id'         => (int)($_POST['patient_id'] ?? 0),
            'doctor_id'          => !empty($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : null,
            'patient_type'       => $_POST['patient_type'] ?? 'UMUM',
            'insurance_number'   => trim($_POST['insurance_number'] ?? ''),
            'icd10_code'         => trim($_POST['icd10_code'] ?? 'Z00.0'),
            'total_gross'        => (float)($_POST['total_gross'] ?? 0.00),
            'insurance_coverage' => (float)($_POST['insurance_coverage'] ?? 0.00),
            'patient_amount'     => (float)($_POST['patient_amount'] ?? 0.00),
        ];

        // Item-item invoice
        $itemTypes = $_POST['item_type'] ?? [];
        $itemNames = $_POST['item_name'] ?? [];
        $qtys = $_POST['quantity'] ?? [];
        $uprices = $_POST['unit_price'] ?? [];
        $subtotals = $_POST['subtotal'] ?? [];
        $refTables = $_POST['reference_table'] ?? [];
        $refIds = $_POST['reference_id_item'] ?? [];

        $items = [];
        for ($i = 0; $i < count($itemNames); $i++) {
            if (!empty($itemNames[$i])) {
                $items[] = [
                    'item_type'       => $itemTypes[$i] ?? 'TINDAKAN',
                    'item_name'       => $itemNames[$i],
                    'quantity'        => (int)($qtys[$i] ?? 1),
                    'unit_price'      => (float)($uprices[$i] ?? 0.00),
                    'subtotal'        => (float)($subtotals[$i] ?? 0.00),
                    'reference_table' => $refTables[$i] ?? null,
                    'reference_id'    => !empty($refIds[$i]) ? (int)$refIds[$i] : null
                ];
            }
        }
        if (empty($items) && !empty($data['reference_id'])) {
            $calcModel = new BillingModel();
            $calc = $calcModel->aggregateVisitCosts($data['visit_type'], $data['reference_id']);
            $items = $calc['items'] ?? [];
            if ($data['total_gross'] <= 0) {
                $data['total_gross'] = $calc['total_gross'] ?? 0.00;
                $data['patient_amount'] = $calc['total_gross'] ?? 0.00;
            }
        }
        $data['items'] = $items;

        $billingModel = new BillingModel();
        $user = $this->currentUser();

        $res = $billingModel->generateInvoice($data, $user['id']);

        if ($res['success']) {
            $this->setFlash('success', "Invoice tagihan {$res['invoice_number']} berhasil diterbitkan!");
            // Arahkan langsung ke form pembayaran kasir
            $this->redirect('index.php?c=billing&a=payment&id=' . $res['invoice_id']);
        } else {
            $this->setFlash('danger', 'Gagal menerbitkan invoice: ' . $res['message']);
            $this->redirect('index.php?c=billing');
        }
    }

    /**
     * Form Pembayaran Kasir & Verifikasi Tagihan
     */
    public function payment(): void {
        $invoiceId = (int)($_GET['id'] ?? 0);
        $billingModel = new BillingModel();
        $invoice = $billingModel->getInvoiceDetail($invoiceId);

        if (!$invoice) {
            $this->setFlash('danger', 'Invoice tagihan tidak ditemukan');
            $this->redirect('index.php?c=billing');
        }

        $this->render('billing/payment', [
            'pageTitle' => 'Kasir & Pembayaran Tagihan - SIMRS Siloam Peduli',
            'invoice'   => $invoice
        ]);
    }

    /**
     * Eksekusi Pembayaran Kasir & Penutupan Tagihan (PAID)
     */
    public function processPayment(): void {
        $this->validateCsrf();

        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        if (!$invoiceId) {
            $this->setFlash('danger', 'ID Invoice tidak valid');
            $this->redirect('index.php?c=billing');
        }

        $billingModel = new BillingModel();
        $user = $this->currentUser();

        $res = $billingModel->processPayment($invoiceId, [
            'payment_method' => $_POST['payment_method'] ?? 'TUNAI',
            'amount_paid'    => (float)($_POST['amount_paid'] ?? 0.00),
            'notes'          => trim($_POST['notes'] ?? '')
        ], $user['id']);

        if ($res['success']) {
            $this->setFlash('success', "Pembayaran berhasil diterima (Kuitansi: {$res['receipt_number']})! Tagihan LUNAS.");
            $this->redirect('index.php?c=billing&a=printReceipt&id=' . $invoiceId);
        } else {
            $this->setFlash('danger', $res['message']);
            $this->redirect('index.php?c=billing&a=payment&id=' . $invoiceId);
        }
    }

    /**
     * Manajemen & Pengajuan Klaim Asuransi/BPJS
     */
    public function claims(): void {
        $billingModel = new BillingModel();

        $filters = [
            'status'         => $_GET['status'] ?? null,
            'insurance_type' => $_GET['insurance_type'] ?? null,
            'search'         => trim($_GET['search'] ?? '')
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = $billingModel->paginateInsuranceClaims($filters, $page, 15);
        $stats = $billingModel->getBillingStats();

        $this->render('billing/claims', [
            'pageTitle'  => 'Pengajuan & Verifikasi Klaim Asuransi - SIMRS Siloam Peduli',
            'claims'     => $pagination['data'],
            'pagination' => $pagination,
            'stats'      => $stats,
            'filters'    => $filters
        ]);
    }

    /**
     * Update Status Klaim Asuransi (APPROVED / REJECTED / PENDING)
     */
    public function updateClaim(): void {
        $this->validateCsrf();

        $claimId = (int)($_POST['claim_id'] ?? 0);
        $status = $_POST['status'] ?? 'APPROVED';
        $approvedAmount = isset($_POST['approved_amount']) ? (float)$_POST['approved_amount'] : null;
        $notes = trim($_POST['notes'] ?? '');

        if (!$claimId) {
            $this->setFlash('danger', 'ID Klaim tidak valid');
            $this->redirect('index.php?c=billing&a=claims');
        }

        $billingModel = new BillingModel();
        $user = $this->currentUser();

        if ($billingModel->updateClaimStatus($claimId, $status, $approvedAmount, $notes, $user['id'])) {
            $this->setFlash('success', "Status klaim berhasil diperbarui menjadi {$status}!");
        } else {
            $this->setFlash('danger', 'Gagal memperbarui status klaim.');
        }

        $this->redirect('index.php?c=billing&a=claims');
    }

    /**
     * Cetak Lembar Kuitansi Pembayaran Sah Rumah Sakit Siloam (Tanpa Layout)
     */
    public function printReceipt(): void {
        $invoiceId = (int)($_GET['id'] ?? 0);
        $billingModel = new BillingModel();
        $invoice = $billingModel->getInvoiceDetail($invoiceId);

        if (!$invoice) {
            $this->setFlash('danger', 'Invoice tagihan tidak ditemukan');
            $this->redirect('index.php?c=billing');
        }

        $this->render('billing/print_receipt', [
            'pageTitle' => 'Kuitansi Pembayaran Sah - ' . ($invoice['payment']['receipt_number'] ?? $invoice['invoice_number']),
            'invoice'   => $invoice
        ], false);
    }
}
