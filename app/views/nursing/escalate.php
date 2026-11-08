<?php
// app/views/nursing/escalate.php - Form Lapor Eskalasi Klinis ke Dokter DPJP (SOP/SIMRS/013/2026)
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <div style="display: flex; align-items: center; gap: 8px;">
      <a href="<?= base_url('index.php?c=nursing&a=patient&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-sm btn-outline">
        <i data-lucide="arrow-left"></i>
      </a>
      <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: #b91c1c; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="alert-triangle" style="width: 22px; height: 22px;"></i>
        <span>Lapor Eskalasi Klinis ke Dokter DPJP</span>
      </h1>
    </div>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Protokol Komunikasi Efektif SBAR (Situation, Background, Assessment, Recommendation) untuk perburukan klinis pasien.
    </p>
  </div>
</div>

<div class="card" style="max-width: 800px; padding: 24px; border-top: 4px solid #dc2626;">
  <!-- Info Pasien Singkat -->
  <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 14px; margin-bottom: 20px;">
    <div style="font-weight: 700; color: #991b1b; font-size: 14px; margin-bottom: 4px;">
      Identitas Pasien Terlapor:
    </div>
    <div style="font-size: 13px; color: #7f1d1d; display: flex; gap: 16px; flex-wrap: wrap;">
      <span><strong>Nama:</strong> <?= htmlspecialchars($patient['name']) ?></span>
      <span><strong>No. RM:</strong> <?= htmlspecialchars($patient['no_rm']) ?></span>
      <span><strong>Episode Perawatan:</strong> <?= htmlspecialchars($encounterType) ?> #<?= (int)$encounterId ?></span>
    </div>
  </div>

  <form method="POST" action="<?= base_url('index.php?c=nursing&a=escalate') ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <input type="hidden" name="encounter_type" value="<?= htmlspecialchars($encounterType) ?>">
    <input type="hidden" name="encounter_id" value="<?= (int)$encounterId ?>">

    <div style="margin-bottom: 16px;">
      <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">
        Dokter DPJP / Spesialis Penerima Laporan <span style="color: red;">*</span>
      </label>
      <select name="doctor_id" class="form-control" required>
        <option value="">-- Pilih Dokter DPJP --</option>
        <?php foreach ($doctors as $doc): ?>
          <option value="<?= (int)$doc['id'] ?>">
            <?= htmlspecialchars($doc['name']) ?> (<?= htmlspecialchars($doc['specialization'] ?? 'Spesialis') ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="margin-bottom: 16px;">
      <label style="display: block; font-size: 13px; font-weight: 600; color: #b91c1c; margin-bottom: 4px;">
        Alasan Eskalasi Klinis (Situation &amp; Background) <span style="color: red;">*</span>
      </label>
      <textarea name="escalation_reason" rows="3" class="form-control" required placeholder="Contoh: Terjadi penurunan kesadaran tiba-tiba (E3V3M5), sesak napas memberat, dan nyeri dada menjalar..."></textarea>
      <small style="color: var(--muted); display: block; margin-top: 4px;">Deskripsikan perubahan kondisi yang memicu eskalasi.</small>
    </div>

    <div style="margin-bottom: 16px;">
      <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">
        Ringkasan Parameter Tanda Vital Abnormal (Assessment) <span style="color: red;">*</span>
      </label>
      <input type="text" name="vital_signs_summary" class="form-control" required placeholder="Contoh: TD: 85/50 mmHg, Nadi: 124x/m lemah, SpO2: 88% room air, Suhu: 39.2 C">
    </div>

    <div style="margin-bottom: 24px;">
      <label style="display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">
        Rekomendasi Tindakan Sementara / Advice Awal Perawat
      </label>
      <textarea name="doctor_instruction" rows="2" class="form-control" placeholder="Contoh: Oksigenasi NRM 10 lpm terpasang, posisi semi fowler, mohon instruksi terapi medis selanjutnya..."></textarea>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 8px;">
      <a href="<?= base_url('index.php?c=nursing&a=patient&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-outline">
        Batal
      </a>
      <button type="submit" class="btn btn-primary" style="background: #dc2626; border-color: #b91c1c;">
        <i data-lucide="send"></i>
        <span>Kirim Laporan Eskalasi</span>
      </button>
    </div>
  </form>
</div>

