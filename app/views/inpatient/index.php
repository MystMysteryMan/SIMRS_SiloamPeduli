<?php
// app/views/inpatient/index.php - Pelayanan Rawat Inap & Bed Management (SOP/SIMRS/006/2026)
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="bed-double" style="color: var(--blue-700); width: 22px; height: 22px;"></i>
      <span>Instalasi Rawat Inap (Inpatient) &amp; Bed Management</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Admisi rawat inap (RI-YYYYMM-XXXX), manajemen keterisian tempat tidur, penerimaan bangsal, mutasi ruangan, hingga resume kepulangan.
    </p>
  </div>
  <div style="display: flex; gap: 8px;">
    <?php if (in_array($_SESSION['role'], ['admin', 'registrasi', 'petugas_rawat_inap'])): ?>
      <a href="<?= base_url('index.php?c=inpatient&a=admit') ?>" class="btn btn-primary">
        <i data-lucide="user-plus"></i>
        <span>Admisi Pasien Baru</span>
      </a>
    <?php endif; ?>
    <a href="<?= base_url('index.php?c=inpatient&a=beds') ?>" class="btn btn-outline">
      <i data-lucide="layout-grid"></i>
      <span>Status Kamar &amp; Bed</span>
    </a>
  </div>
</div>

<!-- KPI Cards Okupansi Bed Rawat Inap -->
<div class="grid-4" style="margin-bottom: 20px;">
  <div class="stat-card">
    <div class="top">
      <span>Total Kapasitas Tempat Tidur</span>
      <i data-lucide="bed" style="color: var(--blue-600); width: 18px; height: 18px;"></i>
    </div>
    <div class="num" style="color: var(--blue-900);"><?= $totalBeds ?></div>
    <div class="desc">
      <span>Seluruh bangsal aktif</span>
    </div>
  </div>

  <div class="stat-card">
    <div class="top">
      <span>Tempat Tidur Terisi (Occupied)</span>
      <i data-lucide="user-check" style="color: #dc2626; width: 18px; height: 18px;"></i>
    </div>
    <div class="num" style="color: #dc2626;"><?= $totalOccupied ?></div>
    <div class="desc">
      <span class="badge" style="background: #fee2e2; color: #b91c1c;">Pasien Dirawat</span>
    </div>
  </div>

  <div class="stat-card">
    <div class="top">
      <span>Tempat Tidur Tersedia (Available)</span>
      <i data-lucide="check-circle" style="color: #16a34a; width: 18px; height: 18px;"></i>
    </div>
    <div class="num" style="color: #16a34a;"><?= $totalAvailable ?></div>
    <div class="desc">
      <span class="badge" style="background: #dcfce7; color: #15803d;">Siap Ditempati</span>
    </div>
  </div>

  <div class="stat-card">
    <div class="top">
      <span>Bed Occupancy Rate (BOR)</span>
      <i data-lucide="pie-chart" style="color: #0284c7; width: 18px; height: 18px;"></i>
    </div>
    <div class="num" style="color: #0284c7;"><?= $bor ?>%</div>
    <div class="desc">
      <span>Target Standar KARS: 60 - 85%</span>
    </div>
  </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 20px; padding: 16px;">
  <form method="GET" action="<?= base_url('index.php') ?>" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
    <input type="hidden" name="c" value="inpatient">
    <input type="hidden" name="a" value="index">

    <div style="flex: 2; min-width: 240px;">
      <label style="display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Cari Pasien (Nama, No. RM, No. Registrasi)</label>
      <input type="text" name="search" class="form-control" placeholder="Cari nama atau No. RM..." value="<?= htmlspecialchars($search ?? '') ?>">
    </div>

    <div style="flex: 1; min-width: 180px;">
      <label style="display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Status Pasien</label>
      <select name="status" class="form-control">
        <option value="">-- Semua Status --</option>
        <option value="DIRAWAT" <?= $selectedStatus === 'DIRAWAT' ? 'selected' : '' ?>>Admisi Baru (Belum ke Bangsal)</option>
        <option value="DIRAWAT_BANGSAL" <?= $selectedStatus === 'DIRAWAT_BANGSAL' ? 'selected' : '' ?>>Dirawat di Bangsal</option>
        <option value="DISCHARGED" <?= $selectedStatus === 'DISCHARGED' ? 'selected' : '' ?>>Selesai Pulang (Discharged)</option>
      </select>
    </div>

    <div>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="search"></i>
        <span>Filter Data</span>
      </button>
    </div>
  </form>
</div>

<!-- Tabel Daftar Pasien Rawat Inap -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
    <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--blue-900);">
      Daftar Pasien Rawat Inap (<?= count($admissions) ?>)
    </h3>
  </div>

  <div style="overflow-x: auto;">
    <table class="table" style="width: 100%; margin: 0;">
      <thead>
        <tr>
          <th>No. Registrasi</th>
          <th>Pasien &amp; No. RM</th>
          <th>Kamar &amp; Bed</th>
          <th>Dokter DPJP</th>
          <th>Tanggal Masuk</th>
          <th>Status</th>
          <th style="text-align: right; width: 260px;">Aksi Rawat Inap</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($admissions)): ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 40px; color: var(--muted);">
              <i data-lucide="inbox" style="width: 40px; height: 40px; opacity: 0.4; margin-bottom: 8px;"></i>
              <p style="margin: 0; font-size: 14px;">Tidak ada data pasien rawat inap yang ditemukan.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($admissions as $adm): 
            $st = strtoupper($adm['status'] ?? '');
          ?>
            <tr>
              <td>
                <span class="badge" style="background: var(--blue-50); color: var(--blue-900); font-weight: 700; font-family: monospace;">
                  <?= htmlspecialchars($adm['admission_number']) ?>
                </span>
                <div style="font-size: 11px; color: var(--muted); margin-top: 2px;">
                  Asal: <?= htmlspecialchars($adm['source_type'] ?? 'POLI') ?>
                </div>
              </td>
              <td>
                <div style="font-weight: 700; color: var(--blue-900);">
                  <?= htmlspecialchars($adm['patient_name']) ?>
                </div>
                <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">
                  RM: <b><?= htmlspecialchars($adm['no_rm']) ?></b> | <?= htmlspecialchars($adm['insurance_type']) ?>
                </div>
              </td>
              <td>
                <div style="font-weight: 700; color: #1e293b;">
                  <?= htmlspecialchars($adm['room_name']) ?>
                </div>
                <div style="font-size: 12px; color: var(--muted); display: flex; gap: 6px; align-items: center; margin-top: 2px;">
                  <span class="badge" style="background: #e2e8f0; color: #334155; font-size: 11px;">
                    Bed: <?= htmlspecialchars($adm['bed_number']) ?>
                  </span>
                  <span>•</span>
                  <span><?= htmlspecialchars(str_replace('_', ' ', $adm['class_type'])) ?></span>
                </div>
              </td>
              <td>
                <div style="font-weight: 600; color: #1e293b;">
                  <?= htmlspecialchars($adm['doctor_name']) ?>
                </div>
                <div style="font-size: 11px; color: var(--muted);">
                  <?= htmlspecialchars($adm['specialization']) ?>
                </div>
              </td>
              <td>
                <div style="font-size: 12px; color: #1e293b; font-weight: 500;">
                  <?= date('d/m/Y H:i', strtotime($adm['admission_date'])) ?>
                </div>
              </td>
              <td>
                <?php if ($st === 'DIRAWAT'): ?>
                  <span class="badge" style="background: #fef3c7; color: #b45309;">
                    <i data-lucide="clock" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i>
                    Menunggu Bangsal
                  </span>
                <?php elseif ($st === 'DIRAWAT_BANGSAL'): ?>
                  <span class="badge" style="background: #dbeafe; color: #1d4ed8;">
                    <i data-lucide="bed" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i>
                    Dirawat di Bangsal
                  </span>
                <?php elseif ($st === 'DISCHARGED'): ?>
                  <span class="badge" style="background: #dcfce7; color: #15803d;">
                    <i data-lucide="check" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle;"></i>
                    Discharged (Pulang)
                  </span>
                <?php else: ?>
                  <span class="badge" style="background: #f1f5f9; color: #64748b;"><?= htmlspecialchars($st) ?></span>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                  <!-- Perawat Bangsal: Konfirmasi Penerimaan -->
                  <?php if ($st === 'DIRAWAT' && in_array($_SESSION['role'], ['admin', 'perawat', 'petugas_rawat_inap'])): ?>
                    <form method="POST" action="<?= base_url('index.php?c=inpatient&a=wardReception') ?>" style="display: inline;">
                      <input type="hidden" name="admission_id" value="<?= $adm['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-primary" title="Terima Pasien di Bangsal">
                        <i data-lucide="check"></i>
                        <span>Terima di Bangsal</span>
                      </button>
                    </form>
                  <?php endif; ?>

                  <!-- Transfer Bed -->
                  <?php if ($st === 'DIRAWAT_BANGSAL' && in_array($_SESSION['role'], ['admin', 'perawat', 'petugas_rawat_inap'])): ?>
                    <a href="<?= base_url('index.php?c=inpatient&a=transfer&id=' . $adm['id']) ?>" class="btn btn-sm btn-outline" title="Pindah Kamar / Transfer Bed">
                      <i data-lucide="arrow-left-right"></i>
                      <span>Transfer Bed</span>
                    </a>
                  <?php endif; ?>

                  <!-- Dokter DPJP: Resume Medis & Pemulangan -->
                  <?php if ($st === 'DIRAWAT_BANGSAL' && in_array($_SESSION['role'], ['admin', 'dokter'])): ?>
                    <a href="<?= base_url('index.php?c=inpatient&a=discharge&id=' . $adm['id']) ?>" class="btn btn-sm btn-primary" style="background: #0284c7; border-color: #0369a1;" title="Isi Resume Medis & Pulangkan">
                      <i data-lucide="file-text"></i>
                      <span>Resume &amp; Discharge</span>
                    </a>
                  <?php endif; ?>

                  <!-- Kasir / Petugas Ranap: Konfirmasi Pulang Mandiri jika resume ada -->
                  <?php if ($st === 'DIRAWAT_BANGSAL' && in_array($_SESSION['role'], ['admin', 'petugas_rawat_inap', 'kasir'])): ?>
                    <a href="<?= base_url('index.php?c=inpatient&a=discharge&id=' . $adm['id']) ?>" class="btn btn-sm btn-outline" title="Tinjau Resume Medis & Kepulangan">
                      <i data-lucide="log-out"></i>
                      <span>Discharge</span>
                    </a>
                  <?php endif; ?>

                  <!-- Cetak Resume Medis jika sudah pulang -->
                  <?php if ($st === 'DISCHARGED'): ?>
                    <a href="<?= base_url('index.php?c=inpatient&a=printSummary&id=' . $adm['id']) ?>" class="btn btn-sm btn-outline" title="Cetak Lembar Resume Medis">
                      <i data-lucide="printer"></i>
                      <span>Resume Medis</span>
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

