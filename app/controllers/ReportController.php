<?php
// app/controllers/ReportController.php - Pelaporan & Dashboard Manajemen Eksekutif (SOP/SIMRS/015/2026)

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/ReportModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';

class ReportController extends Controller {

    public function __construct() {
        $this->requireRole(['admin', 'manajemen', 'direksi']);
    }

    /**
     * Dashboard Eksekutif Manajemen Rumah Sakit dengan Visualisasi Grafik Interaktif
     */
    public function index(): void {
        $model = new ReportModel();

        // Parameter Kontrol Filter Atas (Sesuai Revisi Klien)
        $displayType = $_GET['display_type'] ?? 'VISITS'; // VISITS / REVENUE
        $periodMonth = $_GET['period_month'] ?? date('Y-m'); // e.g. 2026-09
        $unit = $_GET['unit'] ?? 'ALL'; // ALL, POLI, RAWAT_INAP, IGD
        $chartType = $_GET['chart_type'] ?? 'line'; // line / bar

        // Rentang tanggal berdasarkan pilihan bulan
        $startDate = $periodMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $kpis = $model->getExecutiveKpis($startDate, $endDate, $unit);
        $inpatient = $model->getInpatientEfficiencyMetrics($startDate, $endDate);
        $topMorbidity = $model->getTop10Morbidity($startDate, $endDate, 5);
        $fastMedicines = $model->getFastMovingMedicines($startDate, $endDate, 5);

        // Ambil data temporal tren & komposisi kunjungan
        $trendData = $model->getVisitTrendData($startDate, $endDate, $unit, $displayType);
        $compositionData = $model->getVisitComposition($startDate, $endDate);

        // Hitung total unique patients
        $uniquePatients = (int)($model->query("SELECT COUNT(DISTINCT patient_id) AS c FROM visits WHERE created_at BETWEEN :s AND :e", [
            ':s' => $startDate . ' 00:00:00',
            ':e' => $endDate . ' 23:59:59'
        ])[0]['c'] ?? $kpis['total_visits']);

        $this->view('reporting/index', [
            'pageTitle'        => 'Dashboard Eksekutif & Indikator Kinerja Manajemen',
            'startDate'        => $startDate,
            'endDate'          => $endDate,
            'periodMonth'      => $periodMonth,
            'displayType'      => $displayType,
            'unit'             => $unit,
            'chartType'        => $chartType,
            'kpis'             => $kpis,
            'inpatient'        => $inpatient,
            'uniquePatients'   => $uniquePatients,
            'topMorbidity'     => $topMorbidity,
            'fastMedicines'    => $fastMedicines,
            'trendData'        => $trendData,
            'compositionData'  => $compositionData
        ]);
    }

    /**
     * Submenu Laporan Kustom RS (Custom Report Builder)
     */
    public function reports(): void {
        $model = new ReportModel();

        $reportType = $_GET['report_type'] ?? 'VISITS'; // VISITS, REVENUE, MORBIDITY, MEDICINES
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $parameter = $_GET['parameter'] ?? 'ALL'; // ALL, POLI, RAWAT_INAP, IGD
        $visualType = $_GET['visual_type'] ?? 'BAR'; // BAR, LINE, TABLE

        if (strtotime($startDate) > strtotime($endDate)) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        $reportResult = $model->getCustomReport($reportType, $startDate, $endDate, $parameter);

        $this->view('reporting/reports', [
            'pageTitle'    => 'Pembangun Laporan Kustom (Custom Report Builder)',
            'reportType'   => $reportType,
            'startDate'    => $startDate,
            'endDate'      => $endDate,
            'parameter'    => $parameter,
            'visualType'   => $visualType,
            'reportResult' => $reportResult
        ]);
    }

    /**
     * Unduh Laporan Kustom dalam format CSV
     */
    public function exportCsv(): void {
        $model = new ReportModel();
        $reportType = $_GET['report_type'] ?? 'VISITS';
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $parameter = $_GET['parameter'] ?? 'ALL';

        $reportResult = $model->getCustomReport($reportType, $startDate, $endDate, $parameter);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Laporan_' . $reportType . '_' . date('Ymd_His') . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['No', 'Data / Item', 'Kategori', 'Nilai / Hasil', 'Tanggal / Periode']);

        $no = 1;
        foreach ($reportResult['rows'] as $r) {
            fputcsv($out, [
                $no++,
                $r['item'] ?? '',
                $r['category'] ?? '',
                $r['value'] ?? '',
                $r['date'] ?? ''
            ]);
        }
        fclose($out);
        exit;
    }

    /**
     * Submenu Indikator Mutu & Pelayanan RS
     */
    public function indicators(): void {
        $model = new ReportModel();
        $indicators = $model->getIndicators();

        $this->view('reporting/indicators', [
            'pageTitle'  => 'Manajemen Master Indikator Mutu & Pelayanan RS',
            'indicators' => $indicators
        ]);
    }

    /**
     * Simpan Indikator Baru
     */
    public function saveIndicator(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?c=report&a=indicators');
        }

        $this->validateCsrf();
        $user = $this->currentUser();

        $name = trim($_POST['name'] ?? '');
        $dataType = trim($_POST['data_type'] ?? '');
        $param = trim($_POST['parameter'] ?? 'Semua Pelayanan');
        $status = trim($_POST['status'] ?? 'AKTIF');
        $val = trim($_POST['current_value'] ?? '0');

        if (empty($name) || empty($dataType)) {
            $this->setFlash('danger', 'Nama indikator dan jenis data wajib diisi.');
            $this->redirect('index.php?c=report&a=indicators');
        }

        try {
            $model = new ReportModel();
            $model->saveIndicator([
                'name'          => $name,
                'data_type'     => $dataType,
                'parameter'     => $param,
                'current_value' => $val,
                'status'        => $status
            ]);

            AuditLogModel::log((int)$user['id'], 'CREATE_INDICATOR', 'Pelaporan Manajemen', "Tambah indikator mutu RS: {$name}");
            $this->setFlash('success', "Indikator mutu '{$name}' berhasil didaftarkan.");
        } catch (Exception $e) {
            $this->setFlash('danger', 'Gagal menyimpan indikator: ' . $e->getMessage());
        }

        $this->redirect('index.php?c=report&a=indicators');
    }

    /**
     * Hapus Indikator
     */
    public function deleteIndicator(): void {
        $this->validateCsrf();
        $user = $this->currentUser();
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $model = new ReportModel();
            $model->deleteIndicator($id);
            AuditLogModel::log((int)$user['id'], 'DELETE_INDICATOR', 'Pelaporan Manajemen', "Hapus indikator mutu ID: {$id}");
            $this->setFlash('success', 'Indikator mutu berhasil dihapus.');
        }

        $this->redirect('index.php?c=report&a=indicators');
    }

    /**
     * Laporan 10 Besar Morbiditas Penyakit (Top 10 ICD-10 Diagnoses)
     */
    public function morbidity(): void {
        $model = new ReportModel();

        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        if (strtotime($startDate) > strtotime($endDate)) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        $morbidityList = $model->getTop10Morbidity($startDate, $endDate, 10);

        $this->view('reporting/morbidity', [
            'pageTitle'     => 'Statistik 10 Besar Morbiditas Penyakit (Top 10 ICD-10)',
            'startDate'     => $startDate,
            'endDate'       => $endDate,
            'morbidityList' => $morbidityList
        ]);
    }

    /**
     * Cetak Lembar Laporan Rekapitulasi Eksekutif Resmi (Print Preview Siloam)
     */
    public function printReport(): void {
        $model = new ReportModel();

        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $unit = $_GET['unit'] ?? 'ALL';

        if (strtotime($startDate) > strtotime($endDate)) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        $reportData = $model->getReportPrintData($startDate, $endDate, $unit);

        // Cetak tanpa layout default (layout khusus print)
        $this->view('reporting/print_report', [
            'pageTitle'  => 'Cetak Laporan Rekapitulasi Eksekutif - Siloam Peduli',
            'reportData' => $reportData,
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'unit'       => $unit
        ], false);
    }
}
