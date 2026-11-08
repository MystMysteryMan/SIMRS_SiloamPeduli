<?php
// app/views/igd/index.php - Dashboard Instalasi Gawat Darurat (IGD) (SOP/SIMRS/007/2026)
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="siren" style="color: #dc2626; width: 22px; height: 22px;"></i>
      <span>Instalasi Gawat Darurat (IGD)</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Triase klinis ESI/START, primary survey ABCDE, tindakan cito emergensi, monitoring berkala, dan disposisi rawat.
    </p>
  </div>
  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <?php if (in_array($_SESSION['role'], ['admin', 'registrasi', 'triase', 'perawat', 'dokter'])): ?>
      <a href="<?= base_url('index.php?c=patient&a=create&source=igd') ?>" class="btn btn-outline" style="border-color: #dc2626; color: #dc2626;" title="Daftarkan Pasien Baru di Unit Registrasi untuk Alur IGD">
        <i data-lucide="user-plus"></i>
        <span>+ Tambah Pasien Baru (IGD)</span>
      </a>
      <a href="<?= base_url('index.php?c=igd&a=create') ?>" class="btn btn-primary" style="background: #dc2626; border-color: #b91c1c;">
        <i data-lucide="activity"></i>
        <span>Registrasi Cepat Triase IGD</span>
      </a>
    <?php endif; ?>
    <a href="<?= base_url('index.php?c=igd&a=index') ?>" class="btn btn-outline">
      <i data-lucide="refresh-cw"></i>
      <span>Segarkan</span>
    </a>
  </div>
</div>

<!-- KPI Cards Triase Klinis -->
<div class="grid-4" style="margin-bottom: 20px;">
  <div class="stat-card triage-merah">
    <div class="top">
      <span>Zona Merah (Resusitasi 0 Min)</span>
    </div>
    <div class="num" style="color: #dc2626;"><?= $stats['red'] ?></div>
    <div class="desc">
      <span class="badge badge-triage-merah">ESI 1 - 2</span>
      <span>Henti napas / syok berat</span>
    </div>
  </div>

  <div class="stat-card triage-kuning">
    <div class="top">
      <span>Zona Kuning (Urgent &le; 30 Min)</span>
    </div>
    <div class="num" style="color: #d97706;"><?= $stats['yellow'] ?></div>
    <div class="desc">
      <span class="badge badge-triage-kuning">ESI 3</span>
      <span>Fraktur / febris / nyeri akut</span>
    </div>
  </div>

  <div class="stat-card triage-hijau">
    <div class="top">
      <span>Zona Hijau (Non-Urgent)</span>
    </div>
    <div class="num" style="color: #16a34a;"><?= $stats['green'] ?></div>
    <div class="desc">
      <span class="badge badge-triage-hijau">ESI 4 - 5</span>
      <span>Luka lecet / rawat jalan ringan</span>
    </div>
  </div>

  <div class="stat-card triage-hitam">
    <div class="top">
      <span>Zona Hitam (Dead on Arrival)</span>
    </div>
    <div class="num" style="color: #0f172a;"><?= $stats['black'] ?></div>
    <div class="desc">
      <span class="badge badge-triage-hitam">DOA</span>
      <span>Meninggal sebelum tiba IGD</span>
    </div>
  </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 18px; padding: 14px 18px;">
  <form method="GET" action="<?= base_url('index.php') ?>" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
    <input type="hidden" name="c" value="igd">
    <input type="hidden" name="a" value="index">

    <div style="flex: 1; min-width: 220px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Cari No. Kunjungan / No. RM / Nama</label>
      <input type="text" name="search" class="form-control" placeholder="Ketik nomor IGD atau nama pasien..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
    </div>

    <div style="width: 170px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Kategori Triase</label>
      <select name="triage_category" class="form-control">
        <option value="">Semua Kategori</option>
        <option value="MERAH" <?= ($filters['triage_category'] ?? '') === 'MERAH' ? 'selected' : '' ?>>Merah (Resusitasi)</option>
        <option value="KUNING" <?= ($filters['triage_category'] ?? '') === 'KUNING' ? 'selected' : '' ?>>Kuning (Urgent)</option>
        <option value="HIJAU" <?= ($filters['triage_category'] ?? '') === 'HIJAU' ? 'selected' : '' ?>>Hijau (Non-Urgent)</option>
        <option value="HITAM" <?= ($filters['triage_category'] ?? '') === 'HITAM' ? 'selected' : '' ?>>Hitam (DOA)</option>
      </select>
    </div>

    <div style="width: 170px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Status Layanan</label>
      <select name="status" class="form-control">
        <option value="">Semua Status</option>
        <option value="TRIAGE" <?= ($filters['status'] ?? '') === 'TRIAGE' ? 'selected' : '' ?>>Menunggu Triase</option>
        <option value="EXAMINATION" <?= ($filters['status'] ?? '') === 'EXAMINATION' ? 'selected' : '' ?>>Pemeriksaan Dokter</option>
        <option value="OBSERVATION" <?= ($filters['status'] ?? '') === 'OBSERVATION' ? 'selected' : '' ?>>Masa Observasi TTV</option>
        <option value="DISPOSED" <?= ($filters['status'] ?? '') === 'DISPOSED' ? 'selected' : '' ?>>Selesai Disposisi</option>
      </select>
    </div>

    <div>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="filter"></i>
        <span>Filter</span>
      </button>
      <a href="<?= base_url('index.php?c=igd&a=index') ?>" class="btn btn-outline" title="Reset Filter">
        <i data-lucide="rotate-ccw"></i>
      </a>
    </div>
  </form>
</div>

<!-- Table Antrean IGD -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 14px 18px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
    <h3 style="margin: 0; font-size: 14px; font-weight: 600; color: var(--ink);">
      Daftar Kunjungan Pasien Instalasi Gawat Darurat
    </h3>
    <span class="badge badge-muted"><?= count($visits) ?> Pasien Terdata</span>
  </div>

  <div style="overflow-x: auto;">
    <table class="table" style="margin: 0;">
      <thead>
        <tr>
          <th>No. Kunjungan</th>
          <th>Waktu Masuk</th>
          <th>Identitas Pasien</th>
          <th>Triase (ESI)</th>
          <th>Keluhan &amp; TTV Awal</th>
          <th>Dokter Jaga</th>
          <th>Status</th>
          <th style="text-align: right;">Aksi Pelayanan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($visits)): ?>
          <tr>
            <td colspan="8" style="text-align: center; padding: 36px; color: var(--muted);">
              <i data-lucide="shield-alert" style="width: 32px; height: 32px; margin-bottom: 8px; opacity: 0.5;"></i>
              <p style="margin: 0;">Tidak ada antrean pasien IGD yang cocok dengan filter saat ini.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($visits as $v): ?>
            <tr style="<?= !empty($v['last_monitoring_abnormal']) ? 'background: #fef2f2;' : '' ?>">
              <td>
                <span style="font-family: ui-monospace, monospace; font-weight: 700; color: var(--blue-900);">
                  <?= htmlspecialchars($v['visit_number']) ?>
                </span>
                <div style="font-size: 10.5px; color: var(--muted); margin-top: 2px;">
                  Transport: <?= htmlspecialchars($v['transportation']) ?>
                </div>
              </td>
              <td style="font-size: 12px; white-space: nowrap;">
                <?= date('d M Y, H:i', strtotime($v['arrival_time'])) ?>
              </td>
              <td>
                <div style="font-weight: 700; color: var(--ink);">
                  <?= htmlspecialchars($v['patient_name'] ?? $v['anonymous_name']) ?>
                </div>
                <div style="font-size: 11px; color: var(--muted); margin-top: 2px;">
                  No. RM: <b><?= htmlspecialchars($v['no_rm'] ?? 'MR/MRS. X') ?></b> &bull; 
                  <?= htmlspecialchars($v['insurance_type'] ?? 'UMUM') ?>
                </div>
              </td>
              <td>
                <?php if ($v['triage_category'] === 'MERAH'): ?>
                  <span class="badge badge-triage-merah">
                    <i data-lucide="flame" class="lucide-sm"></i> MERAH (ESI <?= $v['esi_level'] ?? '1' ?>)
                  </span>
                <?php elseif ($v['triage_category'] === 'KUNING'): ?>
                  <span class="badge badge-triage-kuning">
                    <i data-lucide="alert-circle" class="lucide-sm"></i> KUNING (ESI <?= $v['esi_level'] ?? '3' ?>)
                  </span>
                <?php elseif ($v['triage_category'] === 'HIJAU'): ?>
                  <span class="badge badge-triage-hijau">
                    <i data-lucide="check" class="lucide-sm"></i> HIJAU (ESI <?= $v['esi_level'] ?? '4' ?>)
                  </span>
                <?php elseif ($v['triage_category'] === 'HITAM'): ?>
                  <span class="badge badge-triage-hitam">
                    <i data-lucide="skull" class="lucide-sm"></i> HITAM (DOA)
                  </span>
                <?php else: ?>
                  <span class="badge badge-muted">Belum Triase</span>
                <?php endif; ?>
              </td>
              <td style="max-width: 220px;">
                <div style="font-size: 12px; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($v['chief_complaint'] ?? '') ?>">
                  <?= htmlspecialchars($v['chief_complaint'] ?? 'Belum ada pengkajian') ?>
                </div>
                <?php if (!empty($v['gcs_total'])): ?>
                  <div style="font-size: 11px; color: var(--muted); margin-top: 2px;">
                    GCS: <b><?= $v['gcs_total'] ?></b> &bull; TD: <?= $v['systolic_bp'] ?>/<?= $v['diastolic_bp'] ?> &bull; SpO2: <?= $v['spo2'] ?>%
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight: 500; font-size: 12px; color: var(--ink);">
                  <?= htmlspecialchars($v['doctor_name'] ?? 'Menunggu Dokter') ?>
                </div>
                <?php if (!empty($v['icd10_code'])): ?>
                  <span class="badge badge-primary" style="font-size: 10px; margin-top: 2px;">
                    <?= htmlspecialchars($v['icd10_code']) ?>
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($v['status'] === 'TRIAGE'): ?>
                  <span class="badge badge-warning">Triase</span>
                <?php elseif ($v['status'] === 'EXAMINATION'): ?>
                  <span class="badge badge-primary">Pemeriksaan</span>
                <?php elseif ($v['status'] === 'OBSERVATION'): ?>
                  <span class="badge badge-info">Observasi</span>
                <?php elseif ($v['status'] === 'DISPOSED'): ?>
                  <span class="badge badge-success">
                    <?= htmlspecialchars($v['disposition_type'] ?? 'Disposisi') ?>
                  </span>
                <?php endif; ?>

                <?php if (!empty($v['last_monitoring_abnormal'])): ?>
                  <span class="badge badge-danger" style="margin-top: 2px;" title="Tanda vital abnormal terdeteksi!">
                    <i data-lucide="alert-octagon" class="lucide-sm"></i> TTV KRITIS
                  </span>
                <?php endif; ?>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <div style="display: inline-flex; gap: 4px;">
                  <!-- Tombol Triase -->
                  <a href="<?= base_url('index.php?c=igd&a=triage&id=' . $v['id']) ?>" class="btn btn-sm btn-outline" title="Pengkajian Triase ESI">
                    <i data-lucide="activity"></i> Triase
                  </a>

                  <!-- Tombol Asesmen Dokter -->
                  <a href="<?= base_url('index.php?c=igd&a=examine&id=' . $v['id']) ?>" class="btn btn-sm btn-primary" title="Primary Survey ABCDE & Diagnosis">
                    <i data-lucide="stethoscope"></i> Periksa
                  </a>

                  <!-- Tombol Monitoring TTV -->
                  <a href="<?= base_url('index.php?c=igd&a=monitoring&id=' . $v['id']) ?>" class="btn btn-sm btn-outline" title="Monitoring TTV Berkala">
                    <i data-lucide="heart-pulse"></i> TTV
                  </a>

                  <!-- Tombol Disposisi -->
                  <a href="<?= base_url('index.php?c=igd&a=disposition&id=' . $v['id']) ?>" class="btn btn-sm btn-outline" style="border-color: #10b981; color: #047857;" title="Disposisi Akhir Pasien">
                    <i data-lucide="log-out"></i> Disposisi
                  </a>
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

