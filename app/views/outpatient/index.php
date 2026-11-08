<?php
// app/views/outpatient/index.php - Pelayanan Rawat Jalan / Outpatient
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="stethoscope" style="color: var(--blue-700); width: 22px; height: 22px;"></i>
      <span>Instalasi Rawat Jalan (Outpatient)</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Manajemen alur pelayanan poliklinik terpadu: admisi, asesmen perawat, pemeriksaan dokter, CPOE, hingga rencana kontrol ulang.
    </p>
  </div>
  <div style="display: flex; gap: 8px;">
    <?php if (in_array($_SESSION['role'], ['admin', 'registrasi'])): ?>
      <a href="<?= base_url('index.php?c=outpatient&a=create') ?>" class="btn btn-primary">
        <i data-lucide="user-plus"></i>
        <span>Pendaftaran Kunjungan Poli</span>
      </a>
    <?php endif; ?>
    <a href="<?= base_url('index.php?c=outpatient&a=index') ?>" class="btn btn-outline">
      <i data-lucide="refresh-cw"></i>
      <span>Segarkan</span>
    </a>
  </div>
</div>

<!-- KPI Cards Antrean Rawat Jalan -->
<div class="grid-4" style="margin-bottom: 20px;">
  <div class="stat-card">
    <div class="top">
      <span>Total Pasien Hari Ini</span>
      <i data-lucide="users" style="color: var(--blue-600); width: 18px; height: 18px;"></i>
    </div>
    <div class="num" style="color: var(--blue-900);"><?= $totalVisits ?></div>
    <div class="desc">
      <span>Kunjungan poliklinik terdaftar</span>
    </div>
  </div>

  <div class="stat-card">
    <div class="top">
      <span>Menunggu Asesmen Perawat</span>
      <i data-lucide="activity" style="color: #d97706; width: 18px; height: 18px;"></i>
    </div>
    <div class="num" style="color: #d97706;"><?= $waitingNurse ?></div>
    <div class="desc">
      <span class="badge" style="background: #fef3c7; color: #b45309;">Menunggu Poli</span>
    </div>
  </div>

  <div class="stat-card">
    <div class="top">
      <span>Siap Diperiksa Dokter</span>
      <i data-lucide="user-check" style="color: #0284c7; width: 18px; height: 18px;"></i>
    </div>
    <div class="num" style="color: #0284c7;"><?= $readyDoctor ?></div>
    <div class="desc">
      <span class="badge" style="background: #e0f2fe; color: #0369a1;">TTV Terverifikasi</span>
    </div>
  </div>

  <div class="stat-card">
    <div class="top">
      <span>Selesai Pelayanan</span>
      <i data-lucide="check-circle-2" style="color: #16a34a; width: 18px; height: 18px;"></i>
    </div>
    <div class="num" style="color: #16a34a;"><?= $completedVisits ?></div>
    <div class="desc">
      <span class="badge" style="background: #dcfce7; color: #15803d;">RME Terkunci</span>
    </div>
  </div>
</div>

<!-- Filter Box -->
<div class="card" style="margin-bottom: 20px; padding: 16px;">
  <form method="GET" action="<?= base_url('index.php') ?>" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
    <input type="hidden" name="c" value="outpatient">
    <input type="hidden" name="a" value="index">

    <div style="flex: 1; min-width: 160px;">
      <label style="display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Tanggal Kunjungan</label>
      <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($selectedDate) ?>">
    </div>

    <div style="flex: 1; min-width: 200px;">
      <label style="display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Poliklinik</label>
      <select name="policlinic_id" class="form-control">
        <option value="">-- Seluruh Poliklinik --</option>
        <?php foreach ($policlinics as $p): ?>
          <option value="<?= $p['id'] ?>" <?= $selectedPoli == $p['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['queue_prefix']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="flex: 1; min-width: 160px;">
      <label style="display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Status Antrean</label>
      <select name="status" class="form-control">
        <option value="">-- Semua Status --</option>
        <option value="TERDAFTAR" <?= $selectedStatus === 'TERDAFTAR' ? 'selected' : '' ?>>Menunggu Poli (Terdaftar)</option>
        <option value="DIPERIKSA" <?= ($selectedStatus === 'DIPERIKSA' || $selectedStatus === 'SIAP_DOKTER') ? 'selected' : '' ?>>Siap Diperiksa Dokter</option>
        <option value="SELESAI" <?= $selectedStatus === 'SELESAI' ? 'selected' : '' ?>>Selesai (Completed)</option>
      </select>
    </div>

    <div>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="filter"></i>
        <span>Terapkan Filter</span>
      </button>
    </div>
  </form>
</div>

<!-- Daftar Antrean Rawat Jalan -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
    <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--blue-900);">
      Daftar Pasien Rawat Jalan (<?= count($queue) ?>)
    </h3>
  </div>

  <div style="overflow-x: auto;">
    <table class="table" style="width: 100%; margin: 0;">
      <thead>
        <tr>
          <th style="width: 90px;">No. Antrean</th>
          <th>Pasien &amp; No. RM</th>
          <th>Poliklinik &amp; Dokter</th>
          <th>Penjamin</th>
          <th>Status Kunjungan</th>
          <th style="text-align: right; width: 220px;">Aksi Pelayanan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($queue)): ?>
          <tr>
            <td colspan="6" style="text-align: center; padding: 40px; color: var(--muted);">
              <i data-lucide="inbox" style="width: 40px; height: 40px; opacity: 0.4; margin-bottom: 8px;"></i>
              <p style="margin: 0; font-size: 14px;">Tidak ada data kunjungan rawat jalan untuk filter ini.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($queue as $row): 
            $st = strtoupper($row['status'] ?? '');
          ?>
            <tr>
              <td>
                <span class="badge" style="background: var(--blue-50); color: var(--blue-800); font-weight: 700; font-size: 13px; padding: 4px 8px;">
                  <?= htmlspecialchars($row['queue_number']) ?>
                </span>
              </td>
              <td>
                <div style="font-weight: 600; color: var(--blue-900);">
                  <?= htmlspecialchars($row['patient_name']) ?>
                </div>
                <div style="font-size: 12px; color: var(--muted); display: flex; gap: 8px; margin-top: 2px;">
                  <span>RM: <b><?= htmlspecialchars($row['no_rm']) ?></b></span>
                  <span>•</span>
                  <span><?= htmlspecialchars($row['gender'] === 'L' ? 'Laki-laki' : 'Perempuan') ?></span>
                </div>
              </td>
              <td>
                <div style="font-weight: 600; color: #1e293b;">
                  <?= htmlspecialchars($row['policlinic_name']) ?>
                </div>
                <div style="font-size: 12px; color: var(--muted);">
                  <?= htmlspecialchars($row['doctor_name']) ?>
                </div>
              </td>
              <td>
                <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 600;">
                  <?= htmlspecialchars($row['payment_type'] ?? 'UMUM') ?>
                </span>
              </td>
              <td>
                <?php if (in_array($st, ['TERDAFTAR', 'MENUNGGU_POLI', 'ASESMEN'])): ?>
                  <span class="badge" style="background: #fef3c7; color: #b45309;">
                    <i data-lucide="clock" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i>
                    Menunggu Asesmen Perawat
                  </span>
                <?php elseif (in_array($st, ['SIAP_DOKTER', 'SEDANG_DIPERIKSA', 'DIPERIKSA'])): ?>
                  <span class="badge" style="background: #e0f2fe; color: #0369a1;">
                    <i data-lucide="stethoscope" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i>
                    Siap Diperiksa Dokter
                  </span>
                <?php elseif ($st === 'SELESAI' || $st === 'COMPLETED'): ?>
                  <span class="badge" style="background: #dcfce7; color: #15803d;">
                    <i data-lucide="check" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i>
                    Selesai (Completed)
                  </span>
                <?php else: ?>
                  <span class="badge" style="background: #f1f5f9; color: #64748b;"><?= htmlspecialchars($st) ?></span>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 6px;">
                  <?php if (in_array($_SESSION['role'], ['admin', 'perawat']) && in_array($st, ['TERDAFTAR', 'MENUNGGU_POLI', 'ASESMEN'])): ?>
                    <a href="<?= base_url('index.php?c=outpatient&a=assessment&id=' . $row['id']) ?>" class="btn btn-sm btn-primary" title="Input Asesmen Keperawatan & TTV">
                      <i data-lucide="clipboard-check"></i>
                      <span>Asesmen Awal</span>
                    </a>
                  <?php endif; ?>

                  <?php if (in_array($_SESSION['role'], ['admin', 'dokter']) && in_array($st, ['SIAP_DOKTER', 'SEDANG_DIPERIKSA', 'DIPERIKSA'])): ?>
                    <a href="<?= base_url('index.php?c=outpatient&a=examine&id=' . $row['id']) ?>" class="btn btn-sm btn-primary" style="background: #0284c7; border-color: #0369a1;" title="Periksa Dokter & Resep">
                      <i data-lucide="stethoscope"></i>
                      <span>Periksa Dokter</span>
                    </a>
                  <?php endif; ?>

                  <?php if ($st === 'SELESAI' || $st === 'COMPLETED'): ?>
                    <a href="<?= base_url('index.php?c=outpatient&a=printControl&id=' . $row['id']) ?>" class="btn btn-sm btn-outline" title="Cetak Slip Kontrol & Edukasi">
                      <i data-lucide="printer"></i>
                      <span>Slip Kontrol</span>
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
</div>
