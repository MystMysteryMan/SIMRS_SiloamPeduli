<?php
// app/views/pharmacy/index.php - Antrean Resep Masuk Farmasi (SOP/SIMRS/010/2026)
?>

<div class="content-header">
  <div>
    <h1 class="page-title">Pelayanan Farmasi &amp; Dispensing Obat</h1>
    <p class="text-muted">Pengkajian telaah resep masuk dari Rawat Jalan, Rawat Inap, dan IGD serta dispensing obat aman.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= base_url('index.php?c=pharmacy&a=medicines') ?>" class="btn btn-secondary">
      <i data-lucide="boxes"></i>
      <span>Manajemen Stok Obat</span>
    </a>
  </div>
</div>

<!-- STATISTIK KARTU RESEP FARMASI -->
<div class="grid grid-4 mb-4">
  <div class="card p-3 stat-card">
    <div class="d-flex justify-between items-center">
      <div>
        <div class="text-xs text-muted text-uppercase fw-semibold">Antrean Resep Aktif</div>
        <div class="text-2xl fw-bold mt-1 text-primary"><?= number_format($stats['pending_rx'] ?? 0) ?></div>
      </div>
      <div class="stat-icon bg-blue-light text-primary">
        <i data-lucide="clipboard-list"></i>
      </div>
    </div>
  </div>

  <div class="card p-3 stat-card">
    <div class="d-flex justify-between items-center">
      <div>
        <div class="text-xs text-muted text-uppercase fw-semibold">Resep Selesai Hari Ini</div>
        <div class="text-2xl fw-bold mt-1 text-success"><?= number_format($stats['dispensed_today'] ?? 0) ?></div>
      </div>
      <div class="stat-icon bg-green-light text-success">
        <i data-lucide="check-circle-2"></i>
      </div>
    </div>
  </div>

  <div class="card p-3 stat-card">
    <div class="d-flex justify-between items-center">
      <div>
        <div class="text-xs text-muted text-uppercase fw-semibold">Stok Obat Menipis</div>
        <div class="text-2xl fw-bold mt-1 <?= ($stats['low_stock_count'] ?? 0) > 0 ? 'text-danger' : 'text-muted' ?>">
          <?= number_format($stats['low_stock_count'] ?? 0) ?>
        </div>
      </div>
      <div class="stat-icon bg-red-light text-danger">
        <i data-lucide="alert-triangle"></i>
      </div>
    </div>
  </div>

  <div class="card p-3 stat-card">
    <div class="d-flex justify-between items-center">
      <div>
        <div class="text-xs text-muted text-uppercase fw-semibold">Peringatan Kedaluwarsa</div>
        <div class="text-2xl fw-bold mt-1 <?= ($stats['expiry_warning_count'] ?? 0) > 0 ? 'text-amber' : 'text-muted' ?>">
          <?= number_format($stats['expiry_warning_count'] ?? 0) ?>
        </div>
      </div>
      <div class="stat-icon bg-amber-light text-amber">
        <i data-lucide="clock"></i>
      </div>
    </div>
  </div>
</div>

<!-- FILTER & PENCARIAN ANTREAN -->
<div class="card p-3 mb-4">
  <form method="GET" action="<?= base_url('index.php') ?>" class="d-flex gap-3 items-center flex-wrap">
    <input type="hidden" name="c" value="pharmacy">
    <input type="hidden" name="a" value="index">

    <div class="flex-1" style="min-width: 250px;">
      <div class="input-icon-wrapper">
        <i data-lucide="search" class="input-icon"></i>
        <input type="text" name="search" class="form-control" placeholder="Cari No. Resep, Nama Pasien, atau No. RM..." value="<?= htmlspecialchars($search) ?>">
      </div>
    </div>

    <div style="min-width: 160px;">
      <select name="status" class="form-control" onchange="this.form.submit()">
        <option value="ALL" <?= $currentStatus === 'ALL' ? 'selected' : '' ?>>Semua Status</option>
        <option value="PENDING" <?= $currentStatus === 'PENDING' ? 'selected' : '' ?>>Menunggu Telaah (Pending)</option>
        <option value="PROCESS" <?= $currentStatus === 'PROCESS' ? 'selected' : '' ?>>Sedang Disiapkan (Process)</option>
        <option value="COMPLETED" <?= $currentStatus === 'COMPLETED' ? 'selected' : '' ?>>Selesai Diserahkan (Completed)</option>
      </select>
    </div>

    <div style="min-width: 150px;">
      <select name="visit_type" class="form-control" onchange="this.form.submit()">
        <option value="">Semua Unit Asal</option>
        <option value="RAWAT_JALAN" <?= $visitType === 'RAWAT_JALAN' ? 'selected' : '' ?>>Rawat Jalan (Poli)</option>
        <option value="RAWAT_INAP" <?= $visitType === 'RAWAT_INAP' ? 'selected' : '' ?>>Rawat Inap</option>
        <option value="IGD" <?= $visitType === 'IGD' ? 'selected' : '' ?>>Gawat Darurat (IGD)</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">
      <i data-lucide="filter"></i>
      <span>Filter</span>
    </button>

    <?php if (!empty($search) || $currentStatus !== 'PENDING' || !empty($visitType)): ?>
      <a href="<?= base_url('index.php?c=pharmacy') ?>" class="btn btn-secondary" title="Reset filter">
        <i data-lucide="rotate-ccw"></i>
      </a>
    <?php endif; ?>
  </form>
</div>

<!-- TABEL ANTREAN RESEP -->
<div class="card overflow-hidden">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead>
        <tr>
          <th style="width: 130px;">No. Resep</th>
          <th>Pasien &amp; No. RM</th>
          <th>Dokter Peresep</th>
          <th>Unit Asal</th>
          <th class="text-center">Prioritas</th>
          <th class="text-center">Jumlah Obat</th>
          <th class="text-end">Total Biaya</th>
          <th>Waktu Order</th>
          <th class="text-center">Status</th>
          <th class="text-end" style="width: 140px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($prescriptions)): ?>
          <tr>
            <td colspan="10" class="text-center py-5 text-muted">
              <i data-lucide="inbox" style="width: 48px; height: 48px; stroke-width: 1.5; margin-bottom: 8px;"></i>
              <div>Tidak ada antrean resep yang sesuai dengan kriteria filter.</div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($prescriptions as $rx): ?>
            <tr>
              <td>
                <span class="fw-semibold text-primary font-mono"><?= htmlspecialchars($rx['order_number']) ?></span>
              </td>
              <td>
                <div class="fw-semibold text-dark"><?= htmlspecialchars($rx['patient_name']) ?></div>
                <div class="text-xs text-muted">
                  <span class="font-mono"><?= htmlspecialchars($rx['no_rm']) ?></span> &bull; 
                  <?= htmlspecialchars($rx['gender'] ?? '-') ?> &bull; 
                  <?= htmlspecialchars($rx['insurance_type'] ?? 'UMUM') ?>
                </div>
                <?php if (!empty($rx['allergy_notes'])): ?>
                  <span class="badge badge-danger text-xs mt-1" title="Riwayat Alergi Pasien">
                    <i data-lucide="alert-circle" style="width: 12px; height: 12px;"></i> Alergi: <?= htmlspecialchars($rx['allergy_notes']) ?>
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <div class="fw-semibold text-dark"><?= htmlspecialchars($rx['doctor_name']) ?></div>
                <div class="text-xs text-muted"><?= htmlspecialchars($rx['specialization'] ?? 'Dokter Umum') ?></div>
              </td>
              <td>
                <?php if ($rx['visit_type'] === 'IGD'): ?>
                  <span class="badge badge-danger">IGD</span>
                <?php elseif ($rx['visit_type'] === 'RAWAT_INAP'): ?>
                  <span class="badge badge-info">Rawat Inap</span>
                <?php else: ?>
                  <span class="badge badge-primary">Rawat Jalan</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if ($rx['priority'] === 'CITO'): ?>
                  <span class="badge badge-danger pulse">CITO</span>
                <?php else: ?>
                  <span class="badge badge-secondary">REGULER</span>
                <?php endif; ?>
              </td>
              <td class="text-center fw-semibold">
                <?= (int)$rx['item_count'] ?> R/
              </td>
              <td class="text-end font-mono">
                Rp <?= number_format((float)($rx['total_prescription_amount'] ?? 0), 0, ',', '.') ?>
              </td>
              <td class="text-xs text-muted">
                <?= date('d/m/Y H:i', strtotime($rx['created_at'])) ?>
              </td>
              <td class="text-center">
                <?php if ($rx['status'] === 'COMPLETED'): ?>
                  <span class="badge badge-success">Selesai &amp; Diserahkan</span>
                <?php elseif ($rx['status'] === 'PROCESS'): ?>
                  <span class="badge badge-warning">Sedang Diracik</span>
                <?php else: ?>
                  <span class="badge badge-primary">Menunggu Telaah</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <a href="<?= base_url('index.php?c=pharmacy&a=dispense&id=' . $rx['id']) ?>" 
                   class="btn btn-sm <?= $rx['status'] === 'COMPLETED' ? 'btn-secondary' : 'btn-primary' ?>"
                   title="Telaah &amp; Dispensing Resep">
                  <i data-lucide="<?= $rx['status'] === 'COMPLETED' ? 'eye' : 'check-square' ?>"></i>
                  <span><?= $rx['status'] === 'COMPLETED' ? 'Detail' : 'Telaah &amp; Serah' ?></span>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- PAGINATION -->
  <?php if ($totalPages > 1): ?>
    <div class="card-footer d-flex justify-between items-center p-3 border-top">
      <div class="text-xs text-muted">
        Menampilkan halaman <?= $page ?> dari <?= $totalPages ?> (Total <?= number_format($totalItems) ?> antrean resep)
      </div>
      <div class="d-flex gap-1">
        <?php if ($page > 1): ?>
          <a href="<?= base_url('index.php?c=pharmacy&a=index&status=' . urlencode($currentStatus) . '&search=' . urlencode($search) . '&visit_type=' . urlencode($visitType) . '&page=' . ($page - 1)) ?>" class="btn btn-sm btn-secondary">
            <i data-lucide="chevron-left"></i>
          </a>
        <?php endif; ?>

        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
          <a href="<?= base_url('index.php?c=pharmacy&a=index&status=' . urlencode($currentStatus) . '&search=' . urlencode($search) . '&visit_type=' . urlencode($visitType) . '&page=' . $p) ?>" 
             class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="<?= base_url('index.php?c=pharmacy&a=index&status=' . urlencode($currentStatus) . '&search=' . urlencode($search) . '&visit_type=' . urlencode($visitType) . '&page=' . ($page + 1)) ?>" class="btn btn-sm btn-secondary">
            <i data-lucide="chevron-right"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

