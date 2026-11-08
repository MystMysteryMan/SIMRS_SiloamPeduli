<?php
// app/views/igd/monitoring.php - Monitoring Tanda Vital Berkala Pasien IGD
$monitorings = $visit['monitorings'] ?? [];
$t = $visit['triage'] ?? [];
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="heart-pulse" style="color: #dc2626; width: 22px; height: 22px;"></i>
      <span>Lembar Monitoring Tanda Vital Berkala (IGD)</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Pemantauan serial tanda vital, hemodinamik, saturasi O2, kesadaran GCS, dan deteksi dini perburukan kondisi pasien.
    </p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= base_url('index.php?c=igd&a=disposition&id=' . $visit['id']) ?>" class="btn btn-primary" style="background: #10b981; border-color: #047857;">
      <i data-lucide="log-out"></i>
      <span>Tentukan Disposisi Pasien</span>
    </a>
    <a href="<?= base_url('index.php?c=igd&a=index') ?>" class="btn btn-outline">
      <i data-lucide="arrow-left"></i>
      <span>Kembali ke IGD</span>
    </a>
  </div>
</div>

<!-- Patient Header -->
<div class="card" style="margin-bottom: 20px; background: #fff; padding: 14px 20px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <span class="badge badge-triage-<?= strtolower($visit['triage_category'] ?? 'kuning') ?>">
        TRIAGE: <?= $visit['triage_category'] ?? 'KUNING' ?>
      </span>
      <span style="font-family: ui-monospace, monospace; font-size: 13px; font-weight: 700; color: var(--blue-900); margin-left: 6px;">
        <?= htmlspecialchars($visit['visit_number']) ?>
      </span>
      <h3 style="margin: 4px 0 0; font-size: 16px; font-weight: 700; color: var(--ink);">
        <?= htmlspecialchars($visit['patient_name'] ?? $visit['anonymous_name']) ?>
      </h3>
    </div>
    <div style="font-size: 12px; color: var(--muted); text-align: right;">
      <div>Dokter Jaga: <b><?= htmlspecialchars($visit['doctor_name'] ?? 'Dokter Jaga IGD') ?></b></div>
      <div>Diagnosis: <b>[<?= htmlspecialchars($visit['assessment']['icd10_code'] ?? 'Observasi') ?>] <?= htmlspecialchars($visit['assessment']['diagnosis_name'] ?? 'Belum ada diagnosis') ?></b></div>
    </div>
  </div>
</div>

<!-- Form Tambah Catatan Monitoring Baru -->
<div class="card" style="margin-bottom: 24px; border-left: 4px solid var(--blue-600);">
  <h3 style="margin: 0 0 14px; font-size: 14px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
    <i data-lucide="plus-circle" class="lucide-md"></i>
    <span>Catat Monitoring Tanda Vital Baru</span>
  </h3>

  <form method="POST" action="<?= base_url('index.php?c=igd&a=storeMonitoring') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="emergency_visit_id" value="<?= $visit['id'] ?>">

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 14px;">
      <div>
        <label class="form-label" style="font-size: 11.5px;">TD Sistolik (mmHg)</label>
        <input type="number" name="systolic_bp" class="form-control num" value="120" required>
      </div>
      <div>
        <label class="form-label" style="font-size: 11.5px;">TD Diastolik (mmHg)</label>
        <input type="number" name="diastolic_bp" class="form-control num" value="80" required>
      </div>
      <div>
        <label class="form-label" style="font-size: 11.5px;">Nadi (x/mnt)</label>
        <input type="number" name="pulse_rate" class="form-control num" value="80" required>
      </div>
      <div>
        <label class="form-label" style="font-size: 11.5px;">Laju Napas (x/mnt)</label>
        <input type="number" name="respiratory_rate" class="form-control num" value="20" required>
      </div>
      <div>
        <label class="form-label" style="font-size: 11.5px;">Suhu (&deg;C)</label>
        <input type="number" step="0.1" name="temperature" class="form-control num" value="36.5" required>
      </div>
      <div>
        <label class="form-label" style="font-size: 11.5px;">SpO2 (%)</label>
        <input type="number" name="spo2" class="form-control num" value="98" required>
      </div>
      <div>
        <label class="form-label" style="font-size: 11.5px;">Skor GCS</label>
        <input type="number" name="gcs_total" class="form-control num" value="15" min="3" max="15" required>
      </div>
    </div>

    <div style="margin-bottom: 14px;">
      <label class="form-label" style="font-size: 11.5px;">Catatan Observasi / Respon Terapi</label>
      <input type="text" name="notes" class="form-control" placeholder="Misal: Pasien mengeluh pusing berkurang, infus menetes lancar, mual reda...">
    </div>

    <div style="display: flex; justify-content: flex-end;">
      <button type="submit" class="btn btn-primary">
        <i data-lucide="save"></i>
        <span>Simpan Catatan Monitoring</span>
      </button>
    </div>
  </form>
</div>

<!-- Tabel Flowsheet Monitoring Serial -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 14px 18px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
    <h3 style="margin: 0; font-size: 14px; font-weight: 600; color: var(--ink);">
      Riwayat Flowsheet Monitoring TTV Pasien
    </h3>
    <span class="badge badge-muted"><?= count($monitorings) ?> Data Terekam</span>
  </div>

  <div style="overflow-x: auto;">
    <table class="table" style="margin: 0;">
      <thead>
        <tr>
          <th>Waktu Observasi</th>
          <th style="text-align: right;">Tekanan Darah</th>
          <th style="text-align: right;">Nadi</th>
          <th style="text-align: right;">Pernapasan (RR)</th>
          <th style="text-align: right;">Suhu</th>
          <th style="text-align: right;">SpO2</th>
          <th style="text-align: right;">GCS</th>
          <th>Status Hemodinamik</th>
          <th>Catatan Evaluasi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($monitorings)): ?>
          <tr>
            <td colspan="9" style="text-align: center; padding: 28px; color: var(--muted);">
              Belum ada data monitoring berkala yang dicatat untuk kunjungan ini.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($monitorings as $m): ?>
            <tr style="<?= !empty($m['is_abnormal']) ? 'background: #fef2f2;' : '' ?>">
              <td style="font-size: 12px; white-space: nowrap;">
                <i data-lucide="clock" class="lucide-sm" style="color: var(--muted);"></i>
                <?= date('d M Y, H:i:s', strtotime($m['monitoring_time'])) ?>
              </td>
              <td class="num" style="font-weight: 600; <?= ($m['systolic_bp'] < 90 || $m['systolic_bp'] > 180) ? 'color: #dc2626; font-weight: 800;' : '' ?>">
                <?= $m['systolic_bp'] ?>/<?= $m['diastolic_bp'] ?> <span style="font-size: 10px; color: var(--muted);">mmHg</span>
              </td>
              <td class="num" style="<?= ($m['pulse_rate'] > 120 || $m['pulse_rate'] < 50) ? 'color: #dc2626; font-weight: 800;' : '' ?>">
                <?= $m['pulse_rate'] ?> <span style="font-size: 10px; color: var(--muted);">x/mnt</span>
              </td>
              <td class="num" style="<?= ($m['respiratory_rate'] > 28 || $m['respiratory_rate'] < 10) ? 'color: #dc2626; font-weight: 800;' : '' ?>">
                <?= $m['respiratory_rate'] ?> <span style="font-size: 10px; color: var(--muted);">x/mnt</span>
              </td>
              <td class="num" style="<?= ($m['temperature'] > 38.5 || $m['temperature'] < 35.5) ? 'color: #dc2626; font-weight: 800;' : '' ?>">
                <?= number_format($m['temperature'], 1) ?> &deg;C
              </td>
              <td class="num" style="<?= ($m['spo2'] < 95) ? 'color: #dc2626; font-weight: 800;' : '' ?>">
                <?= $m['spo2'] ?> %
              </td>
              <td class="num">
                <b><?= $m['gcs_total'] ?></b>/15
              </td>
              <td>
                <?php if (!empty($m['is_abnormal'])): ?>
                  <span class="badge badge-danger">
                    <i data-lucide="alert-triangle" class="lucide-sm"></i> ABNORMAL
                  </span>
                <?php else: ?>
                  <span class="badge badge-success">
                    <i data-lucide="check" class="lucide-sm"></i> STABIL
                  </span>
                <?php endif; ?>
              </td>
              <td style="font-size: 12px; color: var(--ink);">
                <?= htmlspecialchars($m['notes'] ?? '-') ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

