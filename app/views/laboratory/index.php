<?php
// app/views/laboratory/index.php - Daftar Order & Antrean Pengujian Laboratorium
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="flask-conical" style="color: var(--blue-600); width: 22px; height: 22px;"></i>
      <span>Laboratorium Sentral</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Penerimaan sampel darah &amp; spesimen klinis, pengujian analitik, evaluasi nilai kritis, dan penerbitan lembar hasil resmi.
    </p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= base_url('index.php?c=laboratory&a=index') ?>" class="btn btn-outline">
      <i data-lucide="refresh-cw"></i>
      <span>Segarkan</span>
    </a>
  </div>
</div>

<!-- KPI Cards -->
<div class="grid-4">
  <div class="stat-card">
    <div class="top">
      <span>Total Permintaan Lab</span>
    </div>
    <div class="num"><?= $stats['total'] ?></div>
    <div class="desc">
      <span class="badge badge-primary"><?= $stats['total'] ?> Order</span>
      <span>tercatat di laboratorium</span>
    </div>
  </div>
  <div class="stat-card" style="border-left: 3px solid var(--amber-500);">
    <div class="top">
      <span>Menunggu Sampel</span>
    </div>
    <div class="num" style="color: var(--amber-700);"><?= $stats['pending'] ?></div>
    <div class="desc">
      <span class="badge badge-warning"><?= $stats['pending'] ?> Order</span>
      <span>flebotomi &amp; spesimen</span>
    </div>
  </div>
  <div class="stat-card" style="border-left: 3px solid var(--blue-500);">
    <div class="top">
      <span>Sedang Diuji (Analitik)</span>
    </div>
    <div class="num" style="color: var(--blue-700);"><?= $stats['process'] ?></div>
    <div class="desc">
      <span class="badge badge-info"><?= $stats['process'] ?> Spesimen</span>
      <span>pemeriksaan instrumen</span>
    </div>
  </div>
  <div class="stat-card" style="border-left: 3px solid var(--red-500);">
    <div class="top">
      <span>Permintaan CITO (Darurat)</span>
    </div>
    <div class="num" style="color: var(--red-700);"><?= $stats['cito'] ?></div>
    <div class="desc">
      <span class="badge badge-danger"><?= $stats['cito'] ?> CITO</span>
      <span>prioritas penanganan</span>
    </div>
  </div>
</div>

<!-- Filter Bar -->
<div class="card" style="padding: 16px 20px; margin-bottom: 20px;">
  <form method="GET" action="<?= base_url('index.php') ?>" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
    <input type="hidden" name="c" value="laboratory">
    <input type="hidden" name="a" value="index">

    <div style="min-width: 200px; flex: 1;">
      <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Pencarian (No. Order / Nama / No. RM)</label>
      <input type="text" name="search" class="form-control" placeholder="Ketik kata kunci..." value="<?= htmlspecialchars($search) ?>">
    </div>

    <div style="width: 180px;">
      <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Status Order</label>
      <select name="status" class="form-control" onchange="this.form.submit()">
        <option value="">-- Semua Status --</option>
        <option value="PENDING" <?= ($selectedStatus === 'PENDING') ? 'selected' : '' ?>>PENDING (Menunggu)</option>
        <option value="PROCESS" <?= ($selectedStatus === 'PROCESS') ? 'selected' : '' ?>>PROCESS (Pengujian)</option>
        <option value="VALID" <?= ($selectedStatus === 'VALID') ? 'selected' : '' ?>>VALID (Selesai)</option>
      </select>
    </div>

    <div style="width: 160px;">
      <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Prioritas</label>
      <select name="priority" class="form-control" onchange="this.form.submit()">
        <option value="">-- Semua Prioritas --</option>
        <option value="REGULER" <?= ($selectedPriority === 'REGULER') ? 'selected' : '' ?>>REGULER</option>
        <option value="CITO" <?= ($selectedPriority === 'CITO') ? 'selected' : '' ?>>CITO (Urgent)</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary" style="height: 38px;">
      <i data-lucide="filter"></i>
      <span>Filter</span>
    </button>
    <a href="<?= base_url('index.php?c=laboratory&a=index') ?>" class="btn btn-outline" style="height: 38px;">
      <i data-lucide="rotate-ccw"></i>
      <span>Reset</span>
    </a>
  </form>
</div>

<!-- Tabel Permintaan Lab -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
    <div>
      <b style="color: var(--blue-900); font-size: 14.5px;">Daftar Permintaan Uji Laboratorium</b>
    </div>
    <span class="badge badge-primary"><?= count($orders) ?> Order Terdata</span>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th style="width: 150px;">No. Order Lab</th>
          <th style="width: 90px; text-align: center;">Prioritas</th>
          <th style="width: 120px;">No. RM</th>
          <th>Nama Pasien</th>
          <th>Dokter Pengirim</th>
          <th style="width: 100px; text-align: center;">Item Uji</th>
          <th style="width: 120px; text-align: center;">Status</th>
          <th style="width: 140px; text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr>
            <td colspan="8" style="text-align: center; padding: 48px 20px; color: var(--muted);">
              <i data-lucide="flask-conical" style="width: 42px; height: 42px; stroke-width: 1.5; margin-bottom: 8px; display: inline-block;"></i>
              <div style="font-size: 14px; font-weight: 500;">Tidak ada antrean pemeriksaan laboratorium ditemukan.</div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($orders as $o): ?>
            <?php
              $isCito = ($o['clinical_priority'] === 'CITO');
              $st = $o['status'];
              $badgeClass = 'badge-secondary';
              $statusLabel = 'Menunggu';
              if ($st === 'PENDING') {
                  $badgeClass = 'badge-warning';
                  $statusLabel = 'Menunggu Sampel';
              } elseif ($st === 'PROCESS') {
                  $badgeClass = 'badge-info';
                  $statusLabel = 'Sedang Diuji';
              } elseif ($st === 'VALID') {
                  $badgeClass = 'badge-success';
                  $statusLabel = 'Valid (Selesai)';
              }
            ?>
            <tr style="<?= ($isCito && $st !== 'VALID') ? 'background: #fffdf5;' : '' ?>">
              <td>
                <span class="font-mono" style="font-weight: 700; color: var(--blue-950);">
                  <?= htmlspecialchars($o['order_number']) ?>
                </span>
                <div style="font-size: 11px; color: var(--muted);">
                  <?= format_datetime_id($o['created_at']) ?>
                </div>
              </td>
              <td style="text-align: center;">
                <span class="badge <?= $isCito ? 'badge-danger' : 'badge-secondary' ?>" style="font-size: 11px;">
                  <?= htmlspecialchars($o['clinical_priority']) ?>
                </span>
              </td>
              <td>
                <span class="font-mono" style="font-weight: 600; color: var(--blue-900);">
                  <?= htmlspecialchars($o['no_rm']) ?>
                </span>
              </td>
              <td>
                <div style="font-weight: 600; color: var(--blue-900);">
                  <?= htmlspecialchars($o['patient_name']) ?>
                </div>
                <div style="font-size: 11.5px; color: var(--muted);">
                  <?= $o['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?> &bull; <?= htmlspecialchars($o['insurance_type']) ?>
                </div>
              </td>
              <td>
                <div style="font-weight: 500; font-size: 13px;"><?= htmlspecialchars($o['doctor_name'] ?? 'Dokter Poliklinik') ?></div>
                <?php if (!empty($o['clinical_notes'])): ?>
                  <div style="font-size: 11px; color: var(--muted); max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?= htmlspecialchars($o['clinical_notes']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td style="text-align: center;">
                <span class="badge badge-secondary"><?= $o['item_count'] ?> Parameter</span>
              </td>
              <td style="text-align: center;">
                <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
              </td>
              <td style="text-align: right;">
                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                  <a href="<?= base_url('index.php?c=laboratory&a=detail&id=' . $o['id']) ?>" 
                     class="btn <?= ($st === 'VALID') ? 'btn-outline' : 'btn-primary' ?>" 
                     style="height: 32px; padding: 0 10px; font-size: 12px;">
                    <i data-lucide="<?= ($st === 'VALID') ? 'eye' : 'edit-3' ?>"></i>
                    <span><?= ($st === 'PENDING') ? 'Ambil Sampel' : (($st === 'PROCESS') ? 'Input Hasil' : 'Detail') ?></span>
                  </a>
                  <?php if ($st === 'VALID'): ?>
                    <a href="<?= base_url('index.php?c=laboratory&a=printResult&id=' . $o['id']) ?>" 
                       target="_blank" class="btn btn-outline" style="height: 32px; padding: 0 8px;" title="Cetak Hasil Lab">
                      <i data-lucide="printer"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php include __DIR__ . '/../components/pagination.php'; ?>
</div>

