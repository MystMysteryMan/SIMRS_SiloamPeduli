<?php
// app/models/ReportModel.php - Model Pelaporan & Dashboard Manajemen (Modul 15 - SOP/SIMRS/015/2026)

require_once __DIR__ . '/../../core/Model.php';

class ReportModel extends Model {
    protected string $table = 'invoices';

    /**
     * Ambil Indikator Kinerja Utama Eksekutif (Executive KPIs)
     */
    public function getExecutiveKpis(string $startDate, string $endDate, string $unit = 'ALL'): array {
        $start = $startDate . ' 00:00:00';
        $end = $endDate . ' 23:59:59';

        // 1. Kunjungan Pasien
        $outpatientCount = 0;
        $inpatientCount = 0;
        $emergencyCount = 0;

        if ($unit === 'ALL' || $unit === 'POLI') {
            $sqlPoli = "SELECT COUNT(*) AS total FROM visits WHERE created_at BETWEEN :start AND :end";
            $res = $this->query($sqlPoli, [':start' => $start, ':end' => $end]);
            $outpatientCount = (int)($res[0]['total'] ?? 0);
        }

        if ($unit === 'ALL' || $unit === 'RAWAT_INAP') {
            $sqlInap = "SELECT COUNT(*) AS total FROM inpatient_admissions WHERE admission_date BETWEEN :start AND :end";
            $res = $this->query($sqlInap, [':start' => $start, ':end' => $end]);
            $inpatientCount = (int)($res[0]['total'] ?? 0);
        }

        if ($unit === 'ALL' || $unit === 'IGD') {
            $sqlIgd = "SELECT COUNT(*) AS total FROM emergency_visits WHERE arrival_time BETWEEN :start AND :end";
            $res = $this->query($sqlIgd, [':start' => $start, ':end' => $end]);
            $emergencyCount = (int)($res[0]['total'] ?? 0);
        }

        $totalVisits = $outpatientCount + $inpatientCount + $emergencyCount;

        // 2. Kinerja Finansial (Billing & Revenue)
        $sqlFin = "
            SELECT 
                COALESCE(SUM(total_gross), 0) AS total_billed,
                COALESCE((SELECT SUM(amount_paid) FROM payments WHERE payment_time BETWEEN :pstart AND :pend), 0) AS total_paid,
                COALESCE(SUM(insurance_coverage), 0) AS total_insurance,
                COALESCE(SUM(CASE WHEN status != 'PAID' THEN patient_amount ELSE 0 END), 0) AS total_receivable
            FROM invoices
            WHERE created_at BETWEEN :start AND :end
        ";
        $fin = $this->query($sqlFin, [
            ':start'  => $start, 
            ':end'    => $end,
            ':pstart' => $start,
            ':pend'   => $end
        ])[0] ?? [];

        // 3. Pemakaian Obat / Farmasi
        $sqlRx = "
            SELECT 
                COUNT(*) AS total_prescriptions,
                COALESCE(SUM(total_amount), 0) AS total_pharmacy_revenue
            FROM pharmacy_dispensings
            WHERE dispensed_at BETWEEN :start AND :end
        ";
        $rx = $this->query($sqlRx, [':start' => $start, ':end' => $end])[0] ?? [];

        return [
            'total_visits'             => $totalVisits,
            'outpatient_count'         => $outpatientCount,
            'inpatient_count'          => $inpatientCount,
            'emergency_count'          => $emergencyCount,
            'total_billed'             => (float)($fin['total_billed'] ?? 0),
            'total_paid'               => (float)($fin['total_paid'] ?? 0),
            'total_insurance'          => (float)($fin['total_insurance'] ?? 0),
            'total_receivable'         => (float)($fin['total_receivable'] ?? 0),
            'total_prescriptions'      => (int)($rx['total_prescriptions'] ?? 0),
            'total_pharmacy_revenue'   => (float)($rx['total_pharmacy_revenue'] ?? 0)
        ];
    }

    /**
     * Hitung Indikator Efisiensi Tempat Tidur Rawat Inap (Kemenkes / KARS: BOR, ALOS)
     */
    public function getInpatientEfficiencyMetrics(string $startDate, string $endDate): array {
        $start = $startDate . ' 00:00:00';
        $end = $endDate . ' 23:59:59';

        // Hitung jumlah hari dalam periode analisis
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate);
        $daysInPeriod = max(1, (int)round(($endTimestamp - $startTimestamp) / 86400) + 1);

        // 1. Kapasitas Tempat Tidur Aktif
        $bedSql = "
            SELECT 
                COUNT(*) AS total_beds,
                COUNT(*) FILTER (WHERE status IN ('Occupied', 'TERISI')) AS occupied_beds,
                COUNT(*) FILTER (WHERE status IN ('Available', 'TERSEDIA')) AS available_beds
            FROM beds
        ";
        $bedRes = $this->query($bedSql)[0] ?? [];
        $totalBeds = (int)($bedRes['total_beds'] ?? 0);
        $occupiedBeds = (int)($bedRes['occupied_beds'] ?? 0);
        $availableBeds = (int)($bedRes['available_beds'] ?? 0);

        // 2. Hari Perawatan Pasien (Patient Inpatient Days dalam periode)
        $staySql = "
            SELECT 
                COUNT(*) AS total_admissions,
                COUNT(*) FILTER (WHERE status = 'DISCHARGED') AS discharged_count,
                COALESCE(SUM(
                    CASE 
                        WHEN status = 'DISCHARGED' AND discharge_date IS NOT NULL THEN
                            GREATEST(1, EXTRACT(DAY FROM (discharge_date - admission_date)))
                        ELSE
                            GREATEST(1, EXTRACT(DAY FROM (CURRENT_TIMESTAMP - admission_date)))
                    END
                ), 0) AS total_stay_days
            FROM inpatient_admissions
            WHERE admission_date BETWEEN :start AND :end
               OR (discharge_date IS NOT NULL AND discharge_date BETWEEN :start AND :end)
               OR status = 'DIRAWAT'
        ";
        $stayRes = $this->query($staySql, [':start' => $start, ':end' => $end])[0] ?? [];
        $totalStayDays = (float)($stayRes['total_stay_days'] ?? 0);
        $dischargedCount = (int)($stayRes['discharged_count'] ?? 0);

        // 3. Formula BOR (Bed Occupancy Rate %):
        // BOR = (Hari Perawatan / (Jumlah Tempat Tidur Tersedia * Periode Hari)) * 100
        $bedDenominator = $totalBeds * $daysInPeriod;
        $bor = ($bedDenominator > 0) ? round(($totalStayDays / $bedDenominator) * 100, 2) : 0.0;

        // 4. Formula ALOS (Average Length of Stay hari):
        // ALOS = Total Hari Rawat Pasien Keluar / Jumlah Pasien Keluar
        $alos = ($dischargedCount > 0) ? round($totalStayDays / $dischargedCount, 1) : 0.0;

        // Rasio bed terisi saat ini
        $currentOccupancyRatio = ($totalBeds > 0) ? round(($occupiedBeds / $totalBeds) * 100, 1) : 0.0;

        return [
            'days_in_period'          => $daysInPeriod,
            'total_beds'              => $totalBeds,
            'occupied_beds'           => $occupiedBeds,
            'available_beds'          => $availableBeds,
            'current_occupancy_ratio' => $currentOccupancyRatio,
            'total_stay_days'         => $totalStayDays,
            'discharged_count'        => $dischargedCount,
            'bor'                     => $bor,
            'bor_status'              => ($bor >= 60.0 && $bor <= 85.0) ? 'IDEAL' : (($bor > 85.0) ? 'OVERCAPACITY' : 'LOW'),
            'alos'                    => $alos,
            'alos_status'             => ($alos >= 6.0 && $alos <= 9.0) ? 'IDEAL' : (($alos > 9.0) ? 'PROLONGED' : 'FAST')
        ];
    }

    /**
     * Statistik 10 Besar Morbiditas Penyakit (Top 10 ICD-10 Diagnoses)
     * Mengagregasikan diagnosis dari EMR Rawat Jalan, Triase/Asesmen IGD, dan Resume Medis Rawat Inap
     */
    public function getTop10Morbidity(string $startDate, string $endDate, int $limit = 10): array {
        $start = $startDate . ' 00:00:00';
        $end = $endDate . ' 23:59:59';

        $sql = "
            WITH combined_diagnoses AS (
                -- Dari EMR Rawat Jalan
                SELECT icd10_code AS icd_code
                FROM medical_records
                WHERE icd10_code IS NOT NULL AND icd10_code != ''
                  AND created_at BETWEEN :start1 AND :end1
                
                UNION ALL
                
                -- Dari Asesmen Gawat Darurat (IGD)
                SELECT icd10_code AS icd_code
                FROM emergency_assessments
                WHERE icd10_code IS NOT NULL AND icd10_code != ''
                  AND created_at BETWEEN :start2 AND :end2
                
                UNION ALL
                
                -- Dari Resume Medis Rawat Inap (Discharge Summary)
                SELECT discharge_diagnosis_icd10 AS icd_code
                FROM inpatient_discharge_summaries
                WHERE discharge_diagnosis_icd10 IS NOT NULL AND discharge_diagnosis_icd10 != ''
                  AND finalized_at BETWEEN :start3 AND :end3
            )
            SELECT 
                cd.icd_code,
                COALESCE(icd.description_id, icd.description_en, 'Diagnosis Medis Tidak Terdefinisi') AS diagnosis_name,
                COUNT(*) AS total_cases
            FROM combined_diagnoses cd
            LEFT JOIN icd_diagnoses icd ON cd.icd_code = icd.code
            GROUP BY cd.icd_code, icd.description_id, icd.description_en
            ORDER BY total_cases DESC
            LIMIT :limit
        ";

        $rows = $this->query($sql, [
            ':start1' => $start, ':end1' => $end,
            ':start2' => $start, ':end2' => $end,
            ':start3' => $start, ':end3' => $end,
            ':limit'  => $limit
        ]);

        // Hitung total seluruh kasus untuk persentase
        $totalAllCases = array_sum(array_column($rows, 'total_cases'));

        $rank = 1;
        $results = [];
        foreach ($rows as $row) {
            $cases = (int)$row['total_cases'];
            $pct = ($totalAllCases > 0) ? round(($cases / $totalAllCases) * 100, 1) : 0;
            $results[] = [
                'rank'           => $rank++,
                'icd_code'       => $row['icd_code'],
                'diagnosis_name' => $row['diagnosis_name'],
                'total_cases'    => $cases,
                'percentage'     => $pct
            ];
        }

        return $results;
    }

    /**
     * Analisis Obat Terlaris / Fast-Moving Medicines
     */
    public function getFastMovingMedicines(string $startDate, string $endDate, int $limit = 10): array {
        $start = $startDate . ' 00:00:00';
        $end = $endDate . ' 23:59:59';

        $sql = "
            SELECT 
                m.id, m.medicine_code AS code, m.name AS medicine_name, m.category AS form, m.unit,
                SUM(oi.quantity) AS total_dispensed_qty,
                COUNT(DISTINCT oi.cpoe_order_id) AS total_prescriptions,
                SUM(oi.subtotal) AS total_sales_amount
            FROM cpoe_order_items oi
            JOIN cpoe_orders o ON oi.cpoe_order_id = o.id
            JOIN medicines m ON oi.item_id = m.id
            WHERE o.order_type = 'RESEP_OBAT'
              AND o.status = 'COMPLETED'
              AND o.created_at BETWEEN :start AND :end
            GROUP BY m.id, m.medicine_code, m.name, m.category, m.unit
            ORDER BY total_dispensed_qty DESC
            LIMIT :limit
        ";

        return $this->query($sql, [':start' => $start, ':end' => $end, ':limit' => $limit]);
    }

    /**
     * Paket Data Lengkap untuk Format Cetak Laporan Rekapitulasi Eksekutif
     */
    public function getReportPrintData(string $startDate, string $endDate, string $unit = 'ALL'): array {
        return [
            'period'     => ['start_date' => $startDate, 'end_date' => $endDate, 'unit' => $unit],
            'kpis'       => $this->getExecutiveKpis($startDate, $endDate, $unit),
            'inpatient'  => $this->getInpatientEfficiencyMetrics($startDate, $endDate),
            'morbidity'  => $this->getTop10Morbidity($startDate, $endDate, 10),
            'medicines'  => $this->getFastMovingMedicines($startDate, $endDate, 10)
        ];
    }

    /**
     * Data Tren Temporal Kunjungan / Pendapatan untuk Grafik Interaktif
     */
    public function getVisitTrendData(string $startDate, string $endDate, string $unit = 'ALL', string $displayType = 'VISITS'): array {
        $start = $startDate . ' 00:00:00';
        $end = $endDate . ' 23:59:59';

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate);
        $diffDays = max(1, (int)round(($endTimestamp - $startTimestamp) / 86400));

        // Format grouping: bila lebih dari 45 hari, group by Bulan, bila <= 45 hari, group by Tanggal/Hari
        $points = [];
        if ($diffDays <= 45) {
            // Harian
            $cur = $startTimestamp;
            while ($cur <= $endTimestamp) {
                $d = date('Y-m-d', $cur);
                $points[$d] = [
                    'label' => date('d M', $cur),
                    'date'  => $d,
                    'value' => 0
                ];
                $cur += 86400;
            }
        } else {
            // Bulanan
            $cur = $startTimestamp;
            while ($cur <= $endTimestamp) {
                $ym = date('Y-m', $cur);
                $points[$ym] = [
                    'label' => date('M Y', $cur),
                    'date'  => $ym,
                    'value' => 0
                ];
                // maju 1 bulan
                $cur = strtotime('+1 month', strtotime($ym . '-01'));
            }
        }

        if ($displayType === 'REVENUE') {
            // Pendapatan Rumah Sakit (Payments)
            $sql = "SELECT payment_time, amount_paid FROM payments WHERE payment_time BETWEEN :start AND :end";
            $rows = $this->query($sql, [':start' => $start, ':end' => $end]);
            foreach ($rows as $r) {
                $t = strtotime($r['payment_time']);
                $key = ($diffDays <= 45) ? date('Y-m-d', $t) : date('Y-m', $t);
                if (isset($points[$key])) {
                    $points[$key]['value'] += (float)$r['amount_paid'];
                }
            }
        } else {
            // Kunjungan Pasien
            if ($unit === 'ALL' || $unit === 'POLI') {
                $sqlPoli = "SELECT created_at FROM visits WHERE created_at BETWEEN :start AND :end";
                $rows = $this->query($sqlPoli, [':start' => $start, ':end' => $end]);
                foreach ($rows as $r) {
                    $t = strtotime($r['created_at']);
                    $key = ($diffDays <= 45) ? date('Y-m-d', $t) : date('Y-m', $t);
                    if (isset($points[$key])) $points[$key]['value'] += 1;
                }
            }
            if ($unit === 'ALL' || $unit === 'RAWAT_INAP') {
                $sqlInap = "SELECT admission_date FROM inpatient_admissions WHERE admission_date BETWEEN :start AND :end";
                $rows = $this->query($sqlInap, [':start' => $start, ':end' => $end]);
                foreach ($rows as $r) {
                    $t = strtotime($r['admission_date']);
                    $key = ($diffDays <= 45) ? date('Y-m-d', $t) : date('Y-m', $t);
                    if (isset($points[$key])) $points[$key]['value'] += 1;
                }
            }
            if ($unit === 'ALL' || $unit === 'IGD') {
                $sqlIgd = "SELECT arrival_time FROM emergency_visits WHERE arrival_time BETWEEN :start AND :end";
                $rows = $this->query($sqlIgd, [':start' => $start, ':end' => $end]);
                foreach ($rows as $r) {
                    $t = strtotime($r['arrival_time']);
                    $key = ($diffDays <= 45) ? date('Y-m-d', $t) : date('Y-m', $t);
                    if (isset($points[$key])) $points[$key]['value'] += 1;
                }
            }
        }

        return array_values($points);
    }

    /**
     * Data Komposisi Kunjungan Pasien untuk Donut Chart
     */
    public function getVisitComposition(string $startDate, string $endDate): array {
        $start = $startDate . ' 00:00:00';
        $end = $endDate . ' 23:59:59';

        $poli = (int)($this->query("SELECT COUNT(*) AS c FROM visits WHERE created_at BETWEEN :start AND :end", [':start' => $start, ':end' => $end])[0]['c'] ?? 0);
        $inap = (int)($this->query("SELECT COUNT(*) AS c FROM inpatient_admissions WHERE admission_date BETWEEN :start AND :end", [':start' => $start, ':end' => $end])[0]['c'] ?? 0);
        $igd  = (int)($this->query("SELECT COUNT(*) AS c FROM emergency_visits WHERE arrival_time BETWEEN :start AND :end", [':start' => $start, ':end' => $end])[0]['c'] ?? 0);

        $total = $poli + $inap + $igd;
        $poliPct = $total > 0 ? round(($poli / $total) * 100, 1) : 0;
        $inapPct = $total > 0 ? round(($inap / $total) * 100, 1) : 0;
        $igdPct  = $total > 0 ? round(($igd / $total) * 100, 1) : 0;

        return [
            'total'          => $total,
            'outpatient'     => ['count' => $poli, 'pct' => $poliPct, 'label' => 'Rawat Jalan', 'color' => '#1d65ad'],
            'inpatient'      => ['count' => $inap, 'pct' => $inapPct, 'label' => 'Rawat Inap', 'color' => '#4f9a3f'],
            'emergency'      => ['count' => $igd,  'pct' => $igdPct,  'label' => 'Gawat Darurat (IGD)', 'color' => '#c0392b'],
        ];
    }

    /**
     * Ambil Master Indikator Mutu RS
     */
    public function getIndicators(): array {
        try {
            return $this->query("SELECT * FROM hospital_indicators ORDER BY id ASC");
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Tambah Indikator Mutu Baru
     */
    public function saveIndicator(array $data): bool {
        $sql = "INSERT INTO hospital_indicators (name, data_type, parameter, current_value, status) VALUES (:name, :type, :param, :val, :status)";
        return $this->execute($sql, [
            ':name'   => $data['name'],
            ':type'   => $data['data_type'],
            ':param'  => $data['parameter'],
            ':val'    => $data['current_value'] ?? '0',
            ':status' => $data['status'] ?? 'AKTIF'
        ]);
    }

    /**
     * Hapus Indikator Mutu
     */
    public function deleteIndicator(int $id): bool {
        return $this->execute("DELETE FROM hospital_indicators WHERE id = :id", [':id' => $id]);
    }

    /**
     * Data untuk Custom Report Builder
     */
    public function getCustomReport(string $reportType, string $startDate, string $endDate, string $parameter = 'ALL'): array {
        $start = $startDate . ' 00:00:00';
        $end = $endDate . ' 23:59:59';
        $rows = [];
        $summary = [];

        if ($reportType === 'REVENUE') {
            $sql = "
                SELECT COALESCE(p.receipt_number, ('KASIR-' || p.id)) AS code, p.payment_method, p.amount_paid AS val, p.payment_time AS date_col,
                       i.patient_amount, i.insurance_coverage
                FROM payments p
                LEFT JOIN invoices i ON p.invoice_id = i.id
                WHERE p.payment_time BETWEEN :start AND :end
                ORDER BY p.payment_time DESC
                LIMIT 50
            ";
            $raw = $this->query($sql, [':start' => $start, ':end' => $end]);
            $sumVal = 0;
            foreach ($raw as $r) {
                $sumVal += (float)$r['val'];
                $rows[] = [
                    'item'     => 'Kuitansi ' . $r['code'],
                    'category' => 'Pembayaran ' . ($r['payment_method'] ?? 'TUNAI'),
                    'value'    => 'Rp ' . number_format((float)$r['val'], 0, ',', '.'),
                    'raw_val'  => (float)$r['val'],
                    'date'     => date('d/m/Y H:i', strtotime($r['date_col']))
                ];
            }
            $summary = [
                'Total Penerimaan' => 'Rp ' . number_format($sumVal, 0, ',', '.'),
                'Jumlah Transaksi' => count($rows) . ' Transaksi',
                'Periode'          => date('d M Y', strtotime($startDate)) . ' - ' . date('d M Y', strtotime($endDate))
            ];
        } elseif ($reportType === 'MORBIDITY') {
            $morb = $this->getTop10Morbidity($startDate, $endDate, 15);
            $totalCases = 0;
            foreach ($morb as $m) {
                $cases = (int)$m['total_cases'];
                $totalCases += $cases;
                $rows[] = [
                    'item'     => $m['icd_code'] . ' - ' . $m['icd_name'],
                    'category' => 'ICD-10',
                    'value'    => $cases . ' Kasus',
                    'raw_val'  => $cases,
                    'date'     => 'Periode ' . date('M Y', strtotime($startDate))
                ];
            }
            $summary = [
                'Total Kasus Terdata' => $totalCases . ' Pasien',
                'Diagnosa Terbanyak'  => $morb[0]['icd_name'] ?? '-',
                'Total Macam Penyakit'=> count($rows) . ' Jenis ICD-10'
            ];
        } elseif ($reportType === 'MEDICINES') {
            $meds = $this->getFastMovingMedicines($startDate, $endDate, 20);
            $totalPcs = 0;
            $totalVal = 0;
            foreach ($meds as $m) {
                $qty = (int)$m['total_dispensed_qty'];
                $sales = (float)$m['total_sales_amount'];
                $totalPcs += $qty;
                $totalVal += $sales;
                $rows[] = [
                    'item'     => $m['code'] . ' - ' . $m['medicine_name'],
                    'category' => $m['form'] . ' (' . $m['unit'] . ')',
                    'value'    => number_format($qty) . ' ' . $m['unit'] . ' (Rp ' . number_format($sales, 0, ',', '.') . ')',
                    'raw_val'  => $qty,
                    'date'     => 'Terpenuhi'
                ];
            }
            $summary = [
                'Total Kuantitas Obat' => number_format($totalPcs) . ' Satuan',
                'Total Nilai Penjualan'=> 'Rp ' . number_format($totalVal, 0, ',', '.'),
                'Variasi Obat Keluar'  => count($rows) . ' Macam'
            ];
        } else {
            // Default: VISITS (Kunjungan Pasien)
            $sql = "
                SELECT COALESCE(v.booking_code, v.queue_number, ('KUNJ-' || v.id)) AS code, 
                       p.full_name AS patient_name, p.no_rm, 
                       COALESCE(v.payment_type, 'Rawat Jalan') AS category, 
                       v.created_at AS date_col
                FROM visits v
                JOIN patients p ON v.patient_id = p.id
                WHERE v.created_at BETWEEN :start AND :end
                ORDER BY v.created_at DESC
                LIMIT 50
            ";
            $raw = $this->query($sql, [':start' => $start, ':end' => $end]);
            foreach ($raw as $r) {
                $rows[] = [
                    'item'     => $r['patient_name'] . ' (' . $r['no_rm'] . ')',
                    'category' => $r['category'] ?? 'Rawat Jalan',
                    'value'    => $r['code'],
                    'raw_val'  => 1,
                    'date'     => date('d/m/Y H:i', strtotime($r['date_col']))
                ];
            }
            $summary = [
                'Total Kunjungan Pasien' => count($rows) . ' Pasien',
                'Parameter Pelayanan'    => ($parameter === 'ALL' ? 'Semua Pelayanan' : $parameter),
                'Periode Laporan'        => date('d M Y', strtotime($startDate)) . ' - ' . date('d M Y', strtotime($endDate))
            ];
        }

        return [
            'rows'    => $rows,
            'summary' => $summary
        ];
    }
}
