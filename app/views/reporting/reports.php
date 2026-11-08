<?php
// app/views/reporting/reports.php - Pembangun Laporan Kustom (Custom Report Builder)
?>

<div class="content-header">
  <div>
    <h1 class="page-title">Pembangun Laporan Kustom (Report Builder)</h1>
    <p class="text-muted">Kustomisasi laporan analitik rumah sakit, filter multivariat, visualisasi data, cetak resmi &amp; unduh CSV.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= base_url('index.php?c=report&a=index') ?>" class="btn btn-secondary">
      <i data-lucide="arrow-left"></i>
      <span>Kembali ke Dashboard</span>
    </a>
  </div>
</div>

<!-- FORMULIR KUSTOMISASI LAPORAN (CUSTOM REPORT BUILDER) -->
<div class="report-builder-card">
  <form method="GET" action="<?= base_url('index.php') ?>" class="report-form-grid">
    <input type="hidden" name="c" value="report">
    <input type="hidden" name="a" value="reports">

    <!-- 1. Jenis Data -->
    <div>
      <label class="form-label text-xs fw-bold text-muted text-uppercase">Jenis Data:</label>
      <select name="report_type" class="form-control">
        <option value="VISITS" <?= $reportType === 'VISITS' ? 'selected' : '' ?>>Kunjungan Pasien</option>
        <option value="REVENUE" <?= $reportType === 'REVENUE' ? 'selected' : '' ?>>Pendapatan Kasir &amp; Billing</option>
        <option value="MORBIDITY" <?= $reportType === 'MORBIDITY' ? 'selected' : '' ?>>Morbiditas ICD-10</option>
        <option value="MEDICINES" <?= $reportType === 'MEDICINES' ? 'selected' : '' ?>>Mutasi Obat Farmasi</option>
      </select>
    </div>

    <!-- 2. Periode Awal -->
    <div>
      <label class="form-label text-xs fw-bold text-muted text-uppercase">Periode Awal:</label>
      <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
    </div>

    <!-- 3. Periode Akhir -->
    <div>
      <label class="form-label text-xs fw-bold text-muted text-uppercase">Periode Akhir:</label>
      <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
    </div>

    <!-- 4. Parameter Pelayanan -->
    <div>
      <label class="form-label text-xs fw-bold text-muted text-uppercase">Parameter Pelayanan:</label>
      <select name="parameter" class="form-control">
        <option value="ALL" <?= $parameter === 'ALL' ? 'selected' : '' ?>>Semua Pelayanan</option>
        <option value="POLI" <?= $parameter === 'POLI' ? 'selected' : '' ?>>Rawat Jalan (Poli)</option>
        <option value="RAWAT_INAP" <?= $parameter === 'RAWAT_INAP' ? 'selected' : '' ?>>Rawat Inap</option>
        <option value="IGD" <?= $parameter === 'IGD' ? 'selected' : '' ?>>Gawat Darurat (IGD)</option>
      </select>
    </div>

    <!-- 5. Visualisasi -->
    <div>
      <label class="form-label text-xs fw-bold text-muted text-uppercase">Visualisasi:</label>
      <select name="visual_type" class="form-control">
        <option value="TABLE" <?= $visualType === 'TABLE' ? 'selected' : '' ?>>Tabel Data</option>
        <option value="BAR" <?= $visualType === 'BAR' ? 'selected' : '' ?>>Bar Graph</option>
        <option value="LINE" <?= $visualType === 'LINE' ? 'selected' : '' ?>>Line Graph</option>
      </select>
    </div>

    <!-- Tombol Eksekusi -->
    <div>
      <button type="submit" class="btn btn-primary w-100" style="padding: 10px 14px;">
        <i data-lucide="play"></i>
        <span>Buat Laporan</span>
      </button>
    </div>
  </form>
</div>

<!-- PANEL HASIL LAPORAN -->
<div class="card overflow-hidden mb-4">
  <div class="card-header p-3 border-bottom d-flex justify-between items-center bg-slate-light flex-wrap gap-2">
    <div>
      <h3 class="m-0 text-sm fw-bold text-dark d-flex items-center gap-2">
        <i data-lucide="file-check" style="color: var(--blue-600); width: 18px; height: 18px;"></i>
        <span>Hasil Laporan: <?= htmlspecialchars($reportType) ?> (<?= htmlspecialchars($parameter) ?>)</span>
      </h3>
      <span class="text-xs text-muted">Periode: <?= date('d/m/Y', strtotime($startDate)) ?> &ndash; <?= date('d/m/Y', strtotime($endDate)) ?></span>
    </div>

    <!-- Tombol Aksi: Cetak & Unduh -->
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()" title="Cetak Lembar Laporan">
        <i data-lucide="printer"></i>
        <span>Cetak</span>
      </button>
      <a href="<?= base_url('index.php?c=report&a=exportCsv&report_type=' . urlencode($reportType) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate) . '&parameter=' . urlencode($parameter)) ?>" 
         class="btn btn-primary btn-sm" title="Unduh File CSV / Spreadsheet">
        <i data-lucide="download"></i>
        <span>Unduh Data (CSV)</span>
      </a>
    </div>
  </div>

  <!-- KARTU RINGKASAN METRIK STATISTIK -->
  <div class="p-3 border-bottom bg-white">
    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
      <?php foreach ($reportResult['summary'] as $label => $val): ?>
        <div style="padding: 10px 14px; background: #f8fafd; border: 1px solid var(--line); border-radius: 8px; min-width: 160px; flex: 1;">
          <div style="font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase;"><?= htmlspecialchars($label) ?></div>
          <div style="font-size: 16px; font-weight: 800; color: var(--blue-900); margin-top: 3px;"><?= htmlspecialchars($val) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- VISUALISASI GRAFIK JIKA DIPILIH BAR ATAU LINE -->
  <?php if ($visualType === 'BAR' || $visualType === 'LINE'): ?>
    <?php
      $rowsData = $reportResult['rows'];
      $maxRowVal = 1;
      foreach ($rowsData as $r) {
        if (($r['raw_val'] ?? 0) > $maxRowVal) $maxRowVal = $r['raw_val'];
      }
      $chartRows = array_slice($rowsData, 0, 15);
      $cRowsCount = count($chartRows);
      $w = 640;
      $h = 180;
      $padX = 35;
      $padY = 20;
      $cW = $w - ($padX * 2);
      $cH = $h - ($padY * 2);
    ?>
    <div class="p-4 border-bottom bg-white">
      <div style="font-size: 12.5px; font-weight: 700; color: var(--blue-900); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
        <i data-lucide="bar-chart-2" style="width: 16px; height: 16px; color: var(--blue-600);"></i>
        <span>Visualisasi Representatif (Top <?= $cRowsCount ?> Data):</span>
      </div>
      <div class="svg-chart-wrapper" style="height: 180px;">
        <svg class="svg-chart" viewBox="0 0 <?= $w ?> <?= $h ?>" preserveAspectRatio="none">
          <line x1="<?= $padX ?>" y1="<?= $padY ?>" x2="<?= $w - $padX ?>" y2="<?= $padY ?>" stroke="#dde5ee" stroke-dasharray="4" />
          <line x1="<?= $padX ?>" y1="<?= $h - $padY ?>" x2="<?= $w - $padX ?>" y2="<?= $h - $padY ?>" stroke="#dde5ee" />

          <?php if ($visualType === 'BAR'): ?>
            <?php
              $barW = max(8, min(30, ($cW / max(1, $cRowsCount)) - 6));
              foreach ($chartRows as $idx => $r):
                $barH = max(4, (($r['raw_val'] ?? 1) / $maxRowVal) * $cH);
                $x = $padX + ($idx * ($cW / max(1, $cRowsCount))) + 2;
                $y = ($h - $padY) - $barH;
            ?>
              <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $barW ?>" height="<?= $barH ?>" rx="3" fill="#1d65ad" opacity="0.85">
                <title><?= htmlspecialchars($r['item']) ?>: <?= htmlspecialchars($r['value']) ?></title>
              </rect>
            <?php endforeach; ?>
          <?php else: ?>
            <?php
              $linePts = [];
              foreach ($chartRows as $idx => $r) {
                $x = $padX + ($cRowsCount > 1 ? ($idx / ($cRowsCount - 1)) * $cW : $cW / 2);
                $y = ($h - $padY) - ((($r['raw_val'] ?? 1) / $maxRowVal) * $cH);
                $linePts[] = "{$x},{$y}";
              }
            ?>
            <polyline points="<?= implode(' ', $linePts) ?>" fill="none" stroke="#1d65ad" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
            <?php foreach ($chartRows as $idx => $r): ?>
              <?php
                $x = $padX + ($cRowsCount > 1 ? ($idx / ($cRowsCount - 1)) * $cW : $cW / 2);
                $y = ($h - $padY) - ((($r['raw_val'] ?? 1) / $maxRowVal) * $cH);
              ?>
              <circle cx="<?= $x ?>" cy="<?= $y ?>" r="3.5" fill="#fff" stroke="#1d65ad" stroke-width="2">
                <title><?= htmlspecialchars($r['item']) ?>: <?= htmlspecialchars($r['value']) ?></title>
              </circle>
            <?php endforeach; ?>
          <?php endif; ?>
        </svg>
      </div>
    </div>
  <?php endif; ?>

  <!-- TABEL REKAPITULASI DATA TERSTRUKTUR -->
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th style="width: 45px;" class="text-center">No</th>
          <th>Uraian Data / Entitas</th>
          <th>Kategori / Layanan</th>
          <th>Nilai / Hasil</th>
          <th>Tanggal / Periode</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reportResult['rows'])): ?>
          <tr>
            <td colspan="5" class="text-center text-muted p-4">Tidak ada data transaksi atau kunjungan pada rentang parameter ini.</td>
          </tr>
        <?php else: ?>
          <?php $no = 1; ?>
          <?php foreach ($reportResult['rows'] as $row): ?>
            <tr>
              <td class="text-center text-muted"><?= $no++ ?></td>
              <td class="fw-semibold text-dark"><?= htmlspecialchars($row['item']) ?></td>
              <td><span class="badge badge-info"><?= htmlspecialchars($row['category']) ?></span></td>
              <td class="font-mono fw-bold text-primary"><?= htmlspecialchars($row['value']) ?></td>
              <td class="text-muted text-xs font-mono"><?= htmlspecialchars($row['date']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

