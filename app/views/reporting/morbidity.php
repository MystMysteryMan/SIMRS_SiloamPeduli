<?php
// app/views/reporting/morbidity.php - Statistik 10 Besar Morbiditas Penyakit (SOP/SIMRS/015/2026)
?>

<div class="content-header">
  <div class="d-flex items-center gap-3">
    <a href="<?= base_url('index.php?c=report') ?>" class="btn btn-secondary btn-sm" title="Kembali ke Dashboard Eksekutif">
      <i data-lucide="arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title">Statistik 10 Besar Morbiditas Penyakit (Top 10 ICD-10)</h1>
      <p class="text-muted">Laporan epidemiologi pola penyakit rumah sakit berdasarkan agregasi rekam medis rawat jalan, IGD, dan rawat inap.</p>
    </div>
  </div>
  <div>
    <button type="button" class="btn btn-primary" onclick="window.print()">
      <i data-lucide="printer"></i>
      <span>Cetak Halaman Ini</span>
    </button>
  </div>
</div>

<!-- FILTER PERIODE -->
<div class="card p-3 mb-4">
  <form method="GET" action="<?= base_url('index.php') ?>" class="d-flex gap-3 items-center flex-wrap">
    <input type="hidden" name="c" value="report">
    <input type="hidden" name="a" value="morbidity">

    <div class="d-flex items-center gap-2">
      <label class="text-xs fw-bold text-muted text-uppercase">Periode Analisis:</label>
      <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
      <span class="text-muted">&mdash;</span>
      <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
    </div>

    <button type="submit" class="btn btn-primary">
      <i data-lucide="filter"></i>
      <span>Filter Periode</span>
    </button>
  </form>
</div>

<!-- TABEL 10 BESAR MORBIDITAS -->
<div class="card overflow-hidden">
  <div class="card-header p-3 border-bottom d-flex justify-between items-center bg-slate-light">
    <div class="fw-bold text-dark d-flex items-center gap-2">
      <i data-lucide="activity" style="width: 18px; height: 18px;"></i>
      <span>Peringkat 10 Penyakit Terbanyak (ICD-10 Morbidity Surveillance)</span>
    </div>
    <div class="text-xs text-muted">
      Periode: <b><?= date('d/m/Y', strtotime($startDate)) ?> &mdash; <?= date('d/m/Y', strtotime($endDate)) ?></b>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th style="width: 60px;" class="text-center">Rank</th>
          <th style="width: 110px;">Kode ICD-10</th>
          <th>Nama Diagnosis Penyakit</th>
          <th class="text-center" style="width: 130px;">Jumlah Kasus</th>
          <th style="width: 250px;">Distribusi Frekuensi (%)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($morbidityList)): ?>
          <tr>
            <td colspan="5" class="text-center py-5 text-muted">
              <i data-lucide="file-question" style="width: 48px; height: 48px; margin-bottom: 8px;"></i>
              <div>Belum ada data diagnosis penyakit yang terekam pada periode ini.</div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($morbidityList as $morb): ?>
            <tr>
              <td class="text-center">
                <?php if ($morb['rank'] === 1): ?>
                  <span class="badge badge-danger fw-bold">#1</span>
                <?php elseif ($morb['rank'] === 2): ?>
                  <span class="badge badge-warning fw-bold">#2</span>
                <?php elseif ($morb['rank'] === 3): ?>
                  <span class="badge badge-primary fw-bold">#3</span>
                <?php else: ?>
                  <span class="badge badge-secondary">#<?= $morb['rank'] ?></span>
                <?php endif; ?>
              </td>
              <td>
                <span class="font-mono fw-bold text-primary"><?= htmlspecialchars($morb['icd_code']) ?></span>
              </td>
              <td>
                <div class="fw-semibold text-dark"><?= htmlspecialchars($morb['diagnosis_name']) ?></div>
              </td>
              <td class="text-center font-mono fw-bold text-base">
                <?= number_format($morb['total_cases']) ?>
              </td>
              <td>
                <div class="d-flex items-center gap-2">
                  <div class="progress-bar-bg flex-1" style="height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                    <div style="width: <?= min(100, $morb['percentage'] * 2) ?>%; height: 100%; background: var(--blue-600); border-radius: 4px;"></div>
                  </div>
                  <span class="font-mono text-xs fw-bold text-muted" style="width: 45px; text-align: right;">
                    <?= $morb['percentage'] ?>%
                  </span>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

