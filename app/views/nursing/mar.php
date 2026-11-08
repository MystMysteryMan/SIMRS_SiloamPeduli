<?php
// app/views/nursing/mar.php - Lembar Administrasi Pemberian Obat (MAR 6-Benar) (SOP/SIMRS/013/2026)
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <div style="display: flex; align-items: center; gap: 8px;">
      <a href="<?= base_url('index.php?c=nursing&a=patient&patient_id=' . $patient['id'] . '&encounter_type=INPATIENT&encounter_id=' . $admissionId) ?>" class="btn btn-sm btn-outline">
        <i data-lucide="arrow-left"></i>
      </a>
      <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900);">
        Medication Administration Record (MAR - Prinsip 6-Benar)
      </h1>
    </div>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Pasien: <strong><?= htmlspecialchars($patient['name']) ?></strong> (RM: <?= htmlspecialchars($patient['no_rm']) ?>) • Admisi RI #<?= (int)$admissionId ?>
    </p>
  </div>
</div>

<div class="grid-2" style="gap: 24px; align-items: flex-start;">
  <!-- Form Catat Pemberian Obat Baru -->
  <div class="card" style="padding: 24px;">
    <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 700; color: var(--blue-900); border-bottom: 1px solid var(--border); padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
      <i data-lucide="shield-check" style="width: 18px; height: 18px; color: #16a34a;"></i>
      <span>Input Pemberian Obat (Verifikasi 6-Benar)</span>
    </h3>

    <form method="POST" action="<?= base_url('index.php?c=nursing&a=mar') ?>" onsubmit="return validate6Benar()">
      <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
      <input type="hidden" name="admission_id" value="<?= (int)$admissionId ?>">

      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Nama Obat <span style="color: red;">*</span></label>
        <input type="text" name="medicine_name" class="form-control" required placeholder="Contoh: Ceftriaxone 1 gr vial / Paracetamol 500 mg tab">
      </div>

      <div class="grid-2" style="margin-bottom: 14px;">
        <div>
          <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Dosis <span style="color: red;">*</span></label>
          <input type="text" name="dosage" class="form-control" required placeholder="Contoh: 1 gr / 500 mg">
        </div>
        <div>
          <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Rute Pemberian <span style="color: red;">*</span></label>
          <select name="route" class="form-control">
            <option value="Intravena (IV)">Intravena (IV)</option>
            <option value="Oral">Oral</option>
            <option value="Intramuskular (IM)">Intramuskular (IM)</option>
            <option value="Subkutan (SC)">Subkutan (SC)</option>
            <option value="Inhalasi / Nebulizer">Inhalasi / Nebulizer</option>
            <option value="Topikal">Topikal</option>
            <option value="Rektal">Rektal</option>
          </select>
        </div>
      </div>

      <div class="grid-2" style="margin-bottom: 14px;">
        <div>
          <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Jadwal / Waktu Direncanakan <span style="color: red;">*</span></label>
          <input type="datetime-local" name="planned_time" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
        </div>
        <div>
          <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Status Pemberian <span style="color: red;">*</span></label>
          <select name="status" class="form-control">
            <option value="GIVEN">GIVEN (Berhasil Diberikan)</option>
            <option value="DELAYED">DELAYED (Ditunda)</option>
            <option value="REFUSED">REFUSED (Ditolak Pasien/Keluarga)</option>
          </select>
        </div>
      </div>

      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Respon Pasien / Efek Samping</label>
        <input type="text" name="patient_response" class="form-control" placeholder="Contoh: Diterima baik, tidak timbul alergi atau keluhan nyeri suntik">
      </div>

      <!-- Checklist 6-Benar KARS -->
      <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px; margin-bottom: 20px;">
        <label style="display: block; font-size: 12px; font-weight: 700; color: #166534; margin-bottom: 8px;">
          Checklist Keselamatan Pasien (Prinsip 6-Benar KARS):
        </label>
        <div class="grid-2" style="gap: 8px; font-size: 12px; color: #14532d;">
          <label style="display: flex; align-items: center; gap: 6px;">
            <input type="checkbox" id="c1" required checked> 1. Benar Pasien (2 Identitas)
          </label>
          <label style="display: flex; align-items: center; gap: 6px;">
            <input type="checkbox" id="c2" required checked> 2. Benar Obat
          </label>
          <label style="display: flex; align-items: center; gap: 6px;">
            <input type="checkbox" id="c3" required checked> 3. Benar Dosis
          </label>
          <label style="display: flex; align-items: center; gap: 6px;">
            <input type="checkbox" id="c4" required checked> 4. Benar Rute Pemberian
          </label>
          <label style="display: flex; align-items: center; gap: 6px;">
            <input type="checkbox" id="c5" required checked> 5. Benar Waktu &amp; Frekuensi
          </label>
          <label style="display: flex; align-items: center; gap: 6px;">
            <input type="checkbox" id="c6" required checked> 6. Benar Dokumentasi (MAR)
          </label>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 8px;">
        <a href="<?= base_url('index.php?c=nursing&a=patient&patient_id=' . $patient['id'] . '&encounter_type=INPATIENT&encounter_id=' . $admissionId) ?>" class="btn btn-outline">
          Batal
        </a>
        <button type="submit" class="btn btn-primary">
          <i data-lucide="check"></i>
          <span>Verifikasi &amp; Simpan MAR</span>
        </button>
      </div>
    </form>
  </div>

  <!-- Riwayat MAR Pasien -->
  <div class="card" style="padding: 24px;">
    <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 700; color: var(--blue-900); border-bottom: 1px solid var(--border); padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
      <i data-lucide="history" style="width: 18px; height: 18px; color: var(--blue-700);"></i>
      <span>Riwayat Lembar MAR (Admisi Terkini)</span>
    </h3>

    <?php if (empty($marRecords)): ?>
      <p style="text-align: center; color: var(--muted); padding: 24px 0;">Belum ada riwayat obat diberikan pada admisi ini.</p>
    <?php else: ?>
      <div style="overflow-x: auto;">
        <table class="table" style="font-size: 13px;">
          <thead>
            <tr>
              <th>Waktu</th>
              <th>Nama Obat &amp; Dosis</th>
              <th>Rute</th>
              <th>Status</th>
              <th>Perawat</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($marRecords as $m): ?>
              <tr>
                <td><?= date('d/m H:i', strtotime($m['planned_time'])) ?></td>
                <td>
                  <strong><?= htmlspecialchars($m['medicine_name']) ?></strong><br>
                  <small style="color: var(--muted);"><?= htmlspecialchars($m['dosage']) ?></small>
                </td>
                <td><small><?= htmlspecialchars($m['route']) ?></small></td>
                <td>
                  <?php if ($m['status'] === 'GIVEN'): ?>
                    <span class="badge" style="background: #dcfce7; color: #15803d; font-size: 11px;">GIVEN</span>
                  <?php elseif ($m['status'] === 'DELAYED'): ?>
                    <span class="badge" style="background: #fef3c7; color: #b45309; font-size: 11px;">DELAYED</span>
                  <?php else: ?>
                    <span class="badge" style="background: #fee2e2; color: #b91c1c; font-size: 11px;">REFUSED</span>
                  <?php endif; ?>
                </td>
                <td><small><?= htmlspecialchars($m['nurse_name'] ?? 'Perawat') ?></small></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function validate6Benar() {
  for (var i = 1; i <= 6; i++) {
    var cb = document.getElementById('c' + i);
    if (!cb.checked) {
      alert('Wajib memverifikasi seluruh 6 prinsip keselamatan pemberian obat sebelum menyimpan.');
      return false;
    }
  }
  return true;
}
</script>

