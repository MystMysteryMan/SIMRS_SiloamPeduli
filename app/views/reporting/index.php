<?php
// app/views/reporting/index.php - Pelaporan & Dashboard Manajemen Eksekutif (SOP/SIMRS/015/2026)
?>

<div class="content-header">
  <div>
    <h1 class="page-title">Dashboard Eksekutif &amp; Analitik Manajemen</h1>
    <p class="text-muted">Indikator kinerja utama rumah sakit, tren kunjungan, efisiensi pelayanan &amp; pendapatan terintegrasi.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= base_url('index.php?c=report&a=reports') ?>" class="btn btn-secondary">
      <i data-lucide="file-spreadsheet"></i>
      <span>Laporan Kustom</span>
    </a>
    <a href="<?= base_url('index.php?c=report&a=indicators') ?>" class="btn btn-secondary">
      <i data-lucide="target"></i>
      <span>Indikator RS</span>
    </a>
    <a href="<?= base_url('index.php?c=report&a=printReport&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate) . '&unit=' . urlencode($unit)) ?>" 
       class="btn btn-primary" target="_blank">
      <i data-lucide="printer"></i>
      <span>Cetak Laporan</span>
    </a>
  </div>
</div>

<!-- KONTROL FILTER ATAS (PILIHAN VISUALISASI, PERIODE & PARAMETER) -->
<div class="card p-3 mb-4">
  <form method="GET" action="<?= base_url('index.php') ?>" class="d-flex gap-3 items-center flex-wrap">
    <input type="hidden" name="c" value="report">
    <input type="hidden" name="a" value="index">

    <!-- 1. Tampilkan: Kunjungan Pasien / Pendapatan RS -->
    <div>
      <label class="text-xs fw-bold text-muted text-uppercase d-block mb-1">Tampilkan:</label>
      <select name="display_type" class="form-control" style="min-width: 170px;" onchange="this.form.submit()">
        <option value="VISITS" <?= $displayType === 'VISITS' ? 'selected' : '' ?>>Kunjungan Pasien</option>
        <option value="REVENUE" <?= $displayType === 'REVENUE' ? 'selected' : '' ?>>Pendapatan RS</option>
      </select>
    </div>

    <!-- 2. Periode: Bulan / Tahun -->
    <div>
      <label class="text-xs fw-bold text-muted text-uppercase d-block mb-1">Periode:</label>
      <input type="month" name="period_month" class="form-control" value="<?= htmlspecialchars($periodMonth) ?>" onchange="this.form.submit()">
    </div>

    <!-- 3. Parameter: Semua Pelayanan / Rawat Jalan / Inap / IGD -->
    <div>
      <label class="text-xs fw-bold text-muted text-uppercase d-block mb-1">Parameter Pelayanan:</label>
      <select name="unit" class="form-control" style="min-width: 170px;" onchange="this.form.submit()">
        <option value="ALL" <?= $unit === 'ALL' ? 'selected' : '' ?>>Semua Pelayanan</option>
        <option value="POLI" <?= $unit === 'POLI' ? 'selected' : '' ?>>Rawat Jalan (Poli)</option>
        <option value="RAWAT_INAP" <?= $unit === 'RAWAT_INAP' ? 'selected' : '' ?>>Rawat Inap</option>
        <option value="IGD" <?= $unit === 'IGD' ? 'selected' : '' ?>>Gawat Darurat (IGD)</option>
      </select>
    </div>

    <!-- 4. Visualisasi: Line Graph / Bar Graph -->
    <div>
      <label class="text-xs fw-bold text-muted text-uppercase d-block mb-1">Visualisasi:</label>
      <select name="chart_type" class="form-control" style="min-width: 140px;" onchange="this.form.submit()">
        <option value="line" <?= $chartType === 'line' ? 'selected' : '' ?>>Line Graph</option>
        <option value="bar" <?= $chartType === 'bar' ? 'selected' : '' ?>>Bar Graph</option>
      </select>
    </div>

    <div class="align-self-end">
      <button type="submit" class="btn btn-primary">
        <i data-lucide="filter"></i>
        <span>Terapkan</span>
      </button>
    </div>
  </form>
</div>

<!-- 4 KARTU KPI ATAS (METRIK UTAMA EKSEKUTIF) -->
<div class="summary-grid-4">
  <!-- 1. JUMLAH PASIEN -->
  <div class="summary-kpi-card">
    <div class="kpi-label">
      <span class="summary-dot blue"></span>
      <span>Jumlah Pasien</span>
    </div>
    <div class="kpi-num"><?= number_format($uniquePatients) ?></div>
    <div class="kpi-sub">Pasien unik dalam periode terpilih</div>
  </div>

  <!-- 2. JUMLAH KUNJUNGAN -->
  <div class="summary-kpi-card">
    <div class="kpi-label">
      <span class="summary-dot green"></span>
      <span>Jumlah Kunjungan</span>
    </div>
    <div class="kpi-num" style="color: var(--blue-700);"><?= number_format($kpis['total_visits']) ?></div>
    <div class="kpi-sub">Total kunjungan (Poli, Inap &amp; IGD)</div>
  </div>

  <!-- 3. PENDAPATAN RUMAH SAKIT -->
  <div class="summary-kpi-card">
    <div class="kpi-label">
      <span class="summary-dot orange"></span>
      <span>Pendapatan RS</span>
    </div>
    <div class="kpi-num" style="color: var(--green-600); font-size: 22px;">
      Rp <?= number_format($kpis['total_paid'], 0, ',', '.') ?>
    </div>
    <div class="kpi-sub">Total penerimaan kasir &amp; lunas</div>
  </div>

  <!-- 4. BOR (BED OCCUPANCY RATE) -->
  <div class="summary-kpi-card">
    <div class="kpi-label">
      <span class="summary-dot red"></span>
      <span>BOR (Keterisian Bed)</span>
    </div>
    <div class="kpi-num" style="color: var(--blue-900);"><?= number_format($inpatient['bor'] ?? 0, 1) ?>%</div>
    <div class="kpi-sub">Standar Kemenkes: 60% &ndash; 85%</div>
  </div>
</div>

<!-- KOMPONEN GRAFIK VISUALISASI EKSEKUTIF (GRID 2 KOLOM) -->
<div class="chart-grid">
  <!-- GRAFIK KIRI: TREN KUNJUNGAN / PENDAPATAN -->
  <div class="chart-card">
    <div class="chart-card-header">
      <div>
        <h3 class="chart-card-title">
          <i data-lucide="<?= $chartType === 'line' ? 'trending-up' : 'bar-chart-2' ?>" style="color: var(--blue-600);"></i>
          <span>Grafik Tren <?= $displayType === 'REVENUE' ? 'Pendapatan' : 'Kunjungan Pasien' ?></span>
        </h3>
        <p class="chart-card-sub">Distribusi temporal periode <?= date('F Y', strtotime($startDate)) ?> (<?= count($trendData) ?> titik waktu)</p>
      </div>
      <div>
        <span class="badge badge-info font-mono"><?= strtoupper($chartType) ?> CHART</span>
      </div>
    </div>

    <!-- AREA VISUALISASI SVG NATIVE CHART -->
    <div class="svg-chart-wrapper">
      <?php
        $maxVal = 1;
        foreach ($trendData as $pt) {
          if ($pt['value'] > $maxVal) $maxVal = $pt['value'];
        }
        $ptCount = count($trendData);
        $w = 640;
        $h = 200;
        $padX = 35;
        $padY = 25;
        $chartW = $w - ($padX * 2);
        $chartH = $h - ($padY * 2);
      ?>
      <svg class="svg-chart" viewBox="0 0 <?= $w ?> <?= $h ?>" preserveAspectRatio="none">
        <!-- Grid horizontal lines -->
        <line x1="<?= $padX ?>" y1="<?= $padY ?>" x2="<?= $w - $padX ?>" y2="<?= $padY ?>" stroke="#dde5ee" stroke-dasharray="4" />
        <line x1="<?= $padX ?>" y1="<?= $padY + ($chartH / 2) ?>" x2="<?= $w - $padX ?>" y2="<?= $padY + ($chartH / 2) ?>" stroke="#dde5ee" stroke-dasharray="4" />
        <line x1="<?= $padX ?>" y1="<?= $h - $padY ?>" x2="<?= $w - $padX ?>" y2="<?= $h - $padY ?>" stroke="#dde5ee" />

        <?php if ($chartType === 'line'): ?>
          <?php
            // Calculate coordinates for SVG polyline
            $pointsCoord = [];
            foreach ($trendData as $idx => $pt) {
              $x = $padX + ($ptCount > 1 ? ($idx / ($ptCount - 1)) * $chartW : $chartW / 2);
              $y = ($h - $padY) - (($pt['value'] / $maxVal) * $chartH);
              $pointsCoord[] = "{$x},{$y}";
            }
            $ptsString = implode(' ', $pointsCoord);
          ?>
          <!-- Gradient Fill Under Area -->
          <defs>
            <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#1d65ad" stop-opacity="0.35" />
              <stop offset="100%" stop-color="#1d65ad" stop-opacity="0.02" />
            </linearGradient>
          </defs>
          <polygon points="<?= $padX ?>,<?= $h - $padY ?> <?= $ptsString ?> <?= $w - $padX ?>,<?= $h - $padY ?>" fill="url(#chartGrad)" />
          <polyline points="<?= $ptsString ?>" fill="none" stroke="#1d65ad" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />

          <!-- Data Points Circles -->
          <?php foreach ($trendData as $idx => $pt): ?>
            <?php
              $x = $padX + ($ptCount > 1 ? ($idx / ($ptCount - 1)) * $chartW : $chartW / 2);
              $y = ($h - $padY) - (($pt['value'] / $maxVal) * $chartH);
            ?>
            <circle cx="<?= $x ?>" cy="<?= $y ?>" r="3.5" fill="#ffffff" stroke="#1d65ad" stroke-width="2">
              <title><?= $pt['label'] ?>: <?= $displayType === 'REVENUE' ? 'Rp ' . number_format($pt['value'], 0, ',', '.') : $pt['value'] . ' Kunjungan' ?></title>
            </circle>
          <?php endforeach; ?>

        <?php else: ?>
          <!-- BAR GRAPH -->
          <?php
            $barW = max(6, min(24, ($chartW / max(1, $ptCount)) - 6));
            foreach ($trendData as $idx => $pt):
              $barH = max(3, ($pt['value'] / $maxVal) * $chartH);
              $x = $padX + ($idx * ($chartW / max(1, $ptCount))) + 2;
              $y = ($h - $padY) - $barH;
          ?>
            <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $barW ?>" height="<?= $barH ?>" rx="3" fill="#1d65ad" opacity="0.85">
              <title><?= $pt['label'] ?>: <?= $displayType === 'REVENUE' ? 'Rp ' . number_format($pt['value'], 0, ',', '.') : $pt['value'] . ' Kunjungan' ?></title>
            </rect>
          <?php endforeach; ?>
        <?php endif; ?>
      </svg>

      <!-- Labels Axis X -->
      <div style="display: flex; justify-content: space-between; padding: 6px <?= $padX ?>px 0; font-size: 10.5px; color: var(--muted);">
        <span><?= $trendData[0]['label'] ?? '-' ?></span>
        <?php if ($ptCount > 2): ?>
          <span><?= $trendData[(int)floor($ptCount / 2)]['label'] ?? '' ?></span>
        <?php endif; ?>
        <span><?= $trendData[$ptCount - 1]['label'] ?? '-' ?></span>
      </div>
    </div>
  </div>

  <!-- GRAFIK KANAN: KOMPOSISI KUNJUNGAN (DONUT CHART) -->
  <div class="chart-card">
    <div class="chart-card-header">
      <div>
        <h3 class="chart-card-title">
          <i data-lucide="pie-chart" style="color: var(--green-600);"></i>
          <span>Komposisi Kunjungan</span>
        </h3>
        <p class="chart-card-sub">Distribusi Poli, Rawat Inap &amp; IGD</p>
      </div>
    </div>

    <!-- AREA SVG DONUT CHART -->
    <div class="donut-container">
      <div class="donut-svg-wrapper">
        <?php
          $cTotal = max(1, $compositionData['total']);
          $r = 60;
          $cx = 90;
          $cy = 90;
          $circ = 2 * M_PI * $r; // ~ 376.99

          $poliLen = ($compositionData['outpatient']['count'] / $cTotal) * $circ;
          $inapLen = ($compositionData['inpatient']['count'] / $cTotal) * $circ;
          $igdLen  = ($compositionData['emergency']['count'] / $cTotal) * $circ;

          $inapOffset = -$poliLen;
          $igdOffset  = -($poliLen + $inapLen);
        ?>
        <svg class="donut-svg" viewBox="0 0 180 180">
          <!-- Background track -->
          <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke="#f1f5f9" stroke-width="20" />
          
          <!-- Outpatient Segment -->
          <?php if ($poliLen > 0): ?>
            <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke="#1d65ad" stroke-width="20"
                    stroke-dasharray="<?= $poliLen ?> <?= $circ ?>" stroke-dashoffset="0" />
          <?php endif; ?>

          <!-- Inpatient Segment -->
          <?php if ($inapLen > 0): ?>
            <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke="#4f9a3f" stroke-width="20"
                    stroke-dasharray="<?= $inapLen ?> <?= $circ ?>" stroke-dashoffset="<?= $inapOffset ?>" />
          <?php endif; ?>

          <!-- Emergency Segment -->
          <?php if ($igdLen > 0): ?>
            <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke="#c0392b" stroke-width="20"
                    stroke-dasharray="<?= $igdLen ?> <?= $circ ?>" stroke-dashoffset="<?= $igdOffset ?>" />
          <?php endif; ?>
        </svg>

        <!-- Center Indicator Text -->
        <div class="donut-hole-text">
          <div class="donut-center-total"><?= number_format($compositionData['total']) ?></div>
          <div class="donut-center-label">Kunjungan</div>
        </div>
      </div>

      <!-- Donut Legend Items -->
      <div class="donut-legend">
        <!-- Rawat Jalan -->
        <div class="donut-legend-item">
          <div class="donut-legend-left">
            <span class="donut-legend-dot" style="background: #1d65ad;"></span>
            <span>Rawat Jalan (Poli)</span>
          </div>
          <div class="donut-legend-pct">
            <?= $compositionData['outpatient']['count'] ?> (<?= $compositionData['outpatient']['pct'] ?>%)
          </div>
        </div>

        <!-- Rawat Inap -->
        <div class="donut-legend-item">
          <div class="donut-legend-left">
            <span class="donut-legend-dot" style="background: #4f9a3f;"></span>
            <span>Rawat Inap</span>
          </div>
          <div class="donut-legend-pct">
            <?= $compositionData['inpatient']['count'] ?> (<?= $compositionData['inpatient']['pct'] ?>%)
          </div>
        </div>

        <!-- IGD -->
        <div class="donut-legend-item">
          <div class="donut-legend-left">
            <span class="donut-legend-dot" style="background: #c0392b;"></span>
            <span>Gawat Darurat (IGD)</span>
          </div>
          <div class="donut-legend-pct">
            <?= $compositionData['emergency']['count'] ?> (<?= $compositionData['emergency']['pct'] ?>%)
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ROW 3: TABEL EFISIENSI & MORBIDITAS -->
<div class="grid grid-2 mb-4">
  <!-- INDIKATOR RAWAT INAP KEMENKES -->
  <div class="card p-4">
    <div class="d-flex justify-between items-center mb-3">
      <h3 class="card-title m-0">Efisiensi Tempat Tidur Rawat Inap (Kemenkes)</h3>
      <span class="badge badge-primary font-mono">SOP/SIMRS/006</span>
    </div>
    <table class="table mb-0">
      <tbody>
        <tr>
          <td>Kapasitas Tempat Tidur Aktif</td>
          <td class="text-end fw-bold font-mono"><?= $inpatient['total_beds'] ?> Bed</td>
        </tr>
        <tr>
          <td>Tempat Tidur Terisi (Occupied)</td>
          <td class="text-end fw-bold font-mono text-primary"><?= $inpatient['occupied_beds'] ?> Bed</td>
        </tr>
        <tr>
          <td>Tempat Tidur Tersedia (Available)</td>
          <td class="text-end fw-bold font-mono text-success"><?= $inpatient['available_beds'] ?> Bed</td>
        </tr>
        <tr>
          <td>Total Hari Rawat (Bed-Days)</td>
          <td class="text-end fw-bold font-mono"><?= $inpatient['total_bed_days'] ?? $inpatient['total_stay_days'] ?? 0 ?> Hari</td>
        </tr>
        <tr>
          <td>Average Length of Stay (ALOS)</td>
          <td class="text-end fw-bold font-mono"><?= $inpatient['alos'] ?> Hari</td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- TOP 5 MORBIDITAS PENYAKIT (ICD-10) -->
  <div class="card p-4">
    <div class="d-flex justify-between items-center mb-3">
      <h3 class="card-title m-0">Morbiditas Penyakit Terbanyak (Top 5 ICD-10)</h3>
      <a href="<?= base_url('index.php?c=report&a=morbidity') ?>" class="btn-link text-xs fw-bold">Lihat Semua &raquo;</a>
    </div>
    <table class="table mb-0">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Nama Diagnosa</th>
          <th class="text-end">Jumlah Kasus</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($topMorbidity)): ?>
          <tr><td colspan="3" class="text-center text-muted p-3">Belum ada data diagnosa dalam periode ini.</td></tr>
        <?php else: ?>
          <?php foreach ($topMorbidity as $m): ?>
            <tr>
            <td class="font-mono fw-bold text-primary"><?= htmlspecialchars($m['icd_code'] ?? '-') ?></td>
<td><?= htmlspecialchars($m['icd_name'] ?? '-') ?></td>
<td class="text-end fw-bold font-mono"><?= (int)($m['total_cases'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
