<?php
// app/views/nursing/vital_signs.php - Form & Riwayat Observasi Tanda-Tanda Vital (SOP/SIMRS/013/2026)
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <div style="display: flex; align-items: center; gap: 8px;">
      <a href="<?= base_url('index.php?c=nursing&a=patient&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-sm btn-outline">
        <i data-lucide="arrow-left"></i>
      </a>
      <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900);">
        Monitoring Tanda-Tanda Vital (TTV)
      </h1>
    </div>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Pasien: <strong><?= htmlspecialchars($patient['name']) ?></strong> (RM: <?= htmlspecialchars($patient['no_rm']) ?>)
    </p>
  </div>
</div>

<div class="grid-2" style="gap: 24px; align-items: flex-start;">
  <!-- Form Input TTV Baru -->
  <div class="card" style="padding: 24px;">
    <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 700; color: var(--blue-900); border-bottom: 1px solid var(--border); padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
      <i data-lucide="activity" style="width: 18px; height: 18px; color: var(--blue-700);"></i>
      <span>Catat Observasi TTV Baru</span>
    </h3>

    <form method="POST" action="<?= base_url('index.php?c=nursing&a=vitalSigns') ?>">
      <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
      <input type="hidden" name="encounter_type" value="<?= htmlspecialchars($encounterType) ?>">
      <input type="hidden" name="encounter_id" value="<?= (int)$encounterId ?>">

      <div class="grid-2" style="margin-bottom: 14px;">
        <div>
          <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Sistolik (mmHg) <span style="color: red;">*</span></label>
          <input type="number" name="systolic_bp" class="form-control" required value="120" min="40" max="260">
        </div>
        <div>
          <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Diastolik (mmHg) <span style="color: red;">*</span></label>
          <input type="number" name="diastolic_bp" class="form-control" required value="80" min="20" max="180">
        </div>
      </div>

      <div class="grid-2" style="margin-bottom: 14px;">
        <div>
          <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Frekuensi Nadi (x/menit) <span style="color: red;">*</span></label>
          <input type="number" name="pulse_rate" class="form-control" required value="80" min="20" max="220">
        </div>
        <div>
          <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Frekuensi Pernapasan (x/menit) <span style="color: red;">*</span></label>
          <input type="number" name="respiratory_rate" class="form-control" required value="20" min="5" max="60">
        </div>
      </div>

      <div class="grid-2" style="margin-bottom: 14px;">
        <div>
          <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Suhu Tubuh (&deg;C) <span style="color: red;">*</span></label>
          <input type="number" step="0.1" name="temperature" class="form-control" required value="36.5" min="30" max="45">
        </div>
        <div>
          <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Saturasi Oksigen / SpO2 (%) <span style="color: red;">*</span></label>
          <input type="number" name="spo2" class="form-control" required value="98" min="50" max="100">
        </div>
      </div>

      <div style="margin-bottom: 20px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Catatan Tambahan / Terapi Oksigen</label>
        <input type="text" name="notes" class="form-control" placeholder="Contoh: Terpasang kanul nasal 3 lpm, pasien mengeluh pusing...">
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 8px;">
        <a href="<?= base_url('index.php?c=nursing&a=patient&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-outline">
          Batal
        </a>
        <button type="submit" class="btn btn-primary">
          <i data-lucide="check-circle"></i>
          <span>Simpan Observasi TTV</span>
        </button>
      </div>
    </form>
  </div>

  <!-- Riwayat Observasi TTV -->
  <div class="card" style="padding: 24px;">
    <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 700; color: var(--blue-900); border-bottom: 1px solid var(--border); padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
      <i data-lucide="history" style="width: 18px; height: 18px; color: var(--blue-700);"></i>
      <span>Riwayat Pengukuran Terkini</span>
    </h3>

    <?php if (empty($history)): ?>
      <p style="text-align: center; color: var(--muted); padding: 24px 0;">Belum ada riwayat tanda vital tersimpan.</p>
    <?php else: ?>
      <div style="overflow-x: auto;">
        <table class="table" style="font-size: 13px;">
          <thead>
            <tr>
              <th>Waktu</th>
              <th>TD (mmHg)</th>
              <th>HR (x/m)</th>
              <th>RR (x/m)</th>
              <th>Suhu</th>
              <th>SpO2</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($history as $h): ?>
              <tr>
                <td><?= date('d/m H:i', strtotime($h['recorded_at'])) ?></td>
                <td>
                  <strong style="color: <?= ($h['systolic_bp'] > 140 || $h['systolic_bp'] < 90) ? '#dc2626' : 'var(--blue-900)' ?>;">
                    <?= (int)$h['systolic_bp'] ?>/<?= (int)$h['diastolic_bp'] ?>
                  </strong>
                </td>
                <td><?= (int)$h['pulse_rate'] ?></td>
                <td><?= (int)$h['respiratory_rate'] ?></td>
                <td><?= number_format((float)$h['temperature'], 1) ?>&deg;C</td>
                <td>
                  <strong style="color: <?= $h['spo2'] < 95 ? '#dc2626' : '#16a34a' ?>;">
                    <?= (int)$h['spo2'] ?>%
                  </strong>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

