<?php
// app/views/emr/index.php - Antrean Pelayanan Poliklinik & EMR
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="stethoscope" style="color: var(--blue-600); width: 22px; height: 22px;"></i>
      <span>Rekam Medis Elektronik (EMR)</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Antrean pemeriksaan pasien rawat jalan hari ini, pencatatan SOAP, tanda vital, diagnosis ICD-10, serta integrasi order penunjang.
    </p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= base_url('index.php?c=emr&a=index') ?>" class="btn btn-outline">
      <i data-lucide="refresh-cw"></i>
      <span>Segarkan Antrean</span>
    </a>
  </div>
</div>

<!-- KPI Cards -->
<div class="grid-4">
  <div class="stat-card">
    <div class="top">
      <span>Total Pasien Hari Ini</span>
    </div>
    <div class="num"><?= $stats['total'] ?></div>
    <div class="desc">
      <span class="badge badge-primary"><?= $stats['total'] ?> Pasien</span>
      <span>terdaftar di poliklinik</span>
    </div>
  </div>
  <div class="stat-card" style="border-left: 3px solid var(--amber-500);">
    <div class="top">
      <span>Menunggu Pemeriksaan</span>
    </div>
    <div class="num" style="color: var(--amber-700);"><?= $stats['scheduled'] ?></div>
    <div class="desc">
      <span class="badge badge-warning"><?= $stats['scheduled'] ?> Antrean</span>
      <span>siap dipanggil dokter</span>
    </div>
  </div>
  <div class="stat-card" style="border-left: 3px solid var(--blue-500);">
    <div class="top">
      <span>Sedang Diperiksa</span>
    </div>
    <div class="num" style="color: var(--blue-700);"><?= $stats['in_progress'] ?></div>
    <div class="desc">
      <span class="badge badge-info"><?= $stats['in_progress'] ?> Pasien</span>
      <span>anamnesis &amp; fisik</span>
    </div>
  </div>
  <div class="stat-card" style="border-left: 3px solid var(--green-500);">
    <div class="top">
      <span>Selesai Diperiksa</span>
    </div>
    <div class="num" style="color: var(--green-700);"><?= $stats['completed'] ?></div>
    <div class="desc">
      <span class="badge badge-success"><?= $stats['completed'] ?> Selesai</span>
      <span>EMR tersimpan</span>
    </div>
  </div>
</div>

<!-- Filter Bar -->
<div class="card" style="padding: 16px 20px; margin-bottom: 20px;">
  <form method="GET" action="<?= base_url('index.php') ?>" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
    <input type="hidden" name="c" value="emr">
    <input type="hidden" name="a" value="index">

    <div style="min-width: 240px; flex: 1;">
      <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Poliklinik</label>
      <select name="policlinic_id" class="form-control" onchange="this.form.submit()">
        <option value="">-- Semua Poliklinik --</option>
        <?php foreach ($policlinics as $poli): ?>
          <option value="<?= $poli['id'] ?>" <?= ($selectedPoli == $poli['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($poli['name']) ?> (<?= htmlspecialchars($poli['queue_prefix']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="min-width: 240px; flex: 1;">
      <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Dokter Pemeriksa</label>
      <select name="doctor_id" class="form-control" onchange="this.form.submit()">
        <option value="">-- Semua Dokter --</option>
        <?php foreach ($doctors as $doc): ?>
          <option value="<?= $doc['id'] ?>" <?= ($selectedDoctor == $doc['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($doc['full_name']) ?> (<?= htmlspecialchars($doc['specialization']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-primary" style="height: 38px;">
      <i data-lucide="filter"></i>
      <span>Terapkan Filter</span>
    </button>
    <a href="<?= base_url('index.php?c=emr&a=index') ?>" class="btn btn-outline" style="height: 38px;">
      <i data-lucide="rotate-ccw"></i>
      <span>Reset</span>
    </a>
  </form>
</div>

<!-- Tabel Antrean Pasien -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
    <div>
      <b style="color: var(--blue-900); font-size: 14.5px;">Antrean Pasien Poliklinik Hari Ini</b>
      <span style="font-size: 12px; color: var(--muted); margin-left: 8px;">(<?= date('d M Y') ?>)</span>
    </div>
    <span class="badge badge-primary"><?= count($queue) ?> Pasien Terdaftar</span>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th style="width: 80px; text-align: center;">No. Antre</th>
          <th style="width: 120px;">No. RM</th>
          <th>Identitas Pasien</th>
          <th>Poli / Dokter Spesialis</th>
          <th>Jaminan</th>
          <th style="width: 130px; text-align: center;">Status Kunjungan</th>
          <th>Diagnosa Terakhir</th>
          <th style="width: 160px; text-align: right;">Aksi Medis</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($queue)): ?>
          <tr>
            <td colspan="8" style="text-align: center; padding: 48px 20px; color: var(--muted);">
              <i data-lucide="inbox" style="width: 42px; height: 42px; stroke-width: 1.5; margin-bottom: 8px; display: inline-block;"></i>
              <div style="font-size: 14px; font-weight: 500;">Tidak ada antrean pasien untuk kriteria filter ini.</div>
              <div style="font-size: 12px;">Pilih poliklinik atau tanggal lain untuk melihat jadwal.</div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($queue as $q): ?>
            <?php
              $st = $q['status'] ?? 'SCHEDULED';
              $badgeClass = 'badge-secondary';
              $statusLabel = 'Menunggu';
              if ($st === 'SCHEDULED') {
                  $badgeClass = 'badge-warning';
                  $statusLabel = 'Menunggu';
              } elseif ($st === 'IN_PROGRESS') {
                  $badgeClass = 'badge-info';
                  $statusLabel = 'Sedang Diperiksa';
              } elseif ($st === 'COMPLETED') {
                  $badgeClass = 'badge-success';
                  $statusLabel = 'Selesai';
              }
            ?>
            <tr>
              <td style="text-align: center;">
                <span style="font-size: 15px; font-weight: 700; color: var(--blue-700); font-variant-numeric: tabular-nums;">
                  <?= htmlspecialchars($q['queue_number']) ?>
                </span>
              </td>
              <td>
                <span class="font-mono" style="font-weight: 600; color: var(--blue-950);">
                  <?= htmlspecialchars($q['no_rm']) ?>
                </span>
              </td>
              <td>
                <div style="font-weight: 600; color: var(--blue-900);">
                  <?= htmlspecialchars($q['patient_name']) ?>
                </div>
                <div style="font-size: 11.5px; color: var(--muted);">
                  <?= $q['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?> &bull; 
                  <?= !empty($q['birth_date']) ? (date('Y') - date('Y', strtotime($q['birth_date']))) . ' thn' : '-' ?>
                  <?php if (!empty($q['allergy_notes'])): ?>
                    &bull; <span style="color: var(--red-600); font-weight: 600;">Alergi: <?= htmlspecialchars($q['allergy_notes']) ?></span>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <div style="font-weight: 500;"><?= htmlspecialchars($q['policlinic_name']) ?></div>
                <div style="font-size: 11.5px; color: var(--muted);"><?= htmlspecialchars($q['doctor_name']) ?></div>
              </td>
              <td>
                <span class="badge <?= ($q['insurance_type'] === 'BPJS') ? 'badge-success' : 'badge-secondary' ?>">
                  <?= htmlspecialchars($q['insurance_type']) ?>
                </span>
              </td>
              <td style="text-align: center;">
                <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
              </td>
              <td>
                <?php if (!empty($q['icd10_code'])): ?>
                  <span class="badge badge-info font-mono"><?= htmlspecialchars($q['icd10_code']) ?></span>
                  <div style="font-size: 11.5px; color: var(--slate-700); margin-top: 2px;">
                    <?= htmlspecialchars($q['diagnosis_name'] ?? '') ?>
                  </div>
                <?php else: ?>
                  <span style="font-size: 12px; color: var(--muted); font-style: italic;">Belum diisi</span>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                  <a href="<?= base_url('index.php?c=emr&a=examine&id=' . $q['id']) ?>" 
                     class="btn <?= ($st === 'COMPLETED') ? 'btn-outline' : 'btn-primary' ?>" 
                     style="height: 32px; padding: 0 10px; font-size: 12px;"
                     title="<?= ($st === 'COMPLETED') ? 'Lihat / Edit Rekam Medis' : 'Mulai Pemeriksaan Dokter' ?>">
                    <i data-lucide="<?= ($st === 'COMPLETED') ? 'file-edit' : 'stethoscope' ?>"></i>
                    <span><?= ($st === 'COMPLETED') ? 'Edit EMR' : 'Periksa' ?></span>
                  </a>
                  <a href="<?= base_url('index.php?c=emr&a=history&patient_id=' . $q['patient_id']) ?>" 
                     class="btn btn-outline" 
                     style="height: 32px; padding: 0 10px; font-size: 12px;"
                     title="Lihat Riwayat Longitudinal Pasien">
                    <i data-lucide="history"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

