<?php
// app/views/nursing/assessment.php - Form Pengkajian Awal Asuhan Keperawatan (SOP/SIMRS/013/2026)
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <div style="display: flex; align-items: center; gap: 8px;">
      <a href="<?= base_url('index.php?c=nursing&a=patient&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-sm btn-outline">
        <i data-lucide="arrow-left"></i>
      </a>
      <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900);">
        Pengkajian Awal Keperawatan (Morse, Nyeri &amp; Fungsional)
      </h1>
    </div>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Pasien: <strong><?= htmlspecialchars($patient['name']) ?></strong> (RM: <?= htmlspecialchars($patient['no_rm']) ?>)
    </p>
  </div>
</div>

<div class="card" style="max-width: 900px; padding: 24px;">
  <form method="POST" action="<?= base_url('index.php?c=nursing&a=assessment') ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <input type="hidden" name="encounter_type" value="<?= htmlspecialchars($encounterType) ?>">
    <input type="hidden" name="encounter_id" value="<?= (int)$encounterId ?>">

    <!-- Bagian 1: Kondisi Umum & Kesadaran -->
    <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 700; color: var(--blue-900); border-bottom: 1px solid var(--border); padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
      <i data-lucide="user-check" style="width: 18px; height: 18px; color: var(--blue-700);"></i>
      <span>1. Kondisi Umum &amp; Keluhan Utama</span>
    </h3>

    <div style="margin-bottom: 16px;">
      <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Keluhan Utama Keperawatan <span style="color: red;">*</span></label>
      <textarea name="chief_complaint" rows="3" class="form-control" required placeholder="Contoh: Pasien mengeluh nyeri perut kanan bawah skala 6, lemas, nafsu makan berkurang..."><?= htmlspecialchars($assessment['chief_complaint'] ?? '') ?></textarea>
    </div>

    <div class="grid-2" style="margin-bottom: 24px;">
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Keadaan Umum</label>
        <select name="general_condition" class="form-control">
          <option value="Baik" <?= ($assessment['general_condition'] ?? '') === 'Baik' ? 'selected' : '' ?>>Baik (Tampak Tenang)</option>
          <option value="Sedang" <?= ($assessment['general_condition'] ?? 'Sedang') === 'Sedang' ? 'selected' : '' ?>>Sedang (Tampak Kesakitan/Lemah)</option>
          <option value="Buruk" <?= ($assessment['general_condition'] ?? '') === 'Buruk' ? 'selected' : '' ?>>Buruk (Gawat / Sangat Lemah)</option>
        </select>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Tingkat Kesadaran</label>
        <select name="consciousness" class="form-control">
          <option value="COMPOS_MENTIS" <?= ($assessment['consciousness'] ?? 'COMPOS_MENTIS') === 'COMPOS_MENTIS' ? 'selected' : '' ?>>Compos Mentis (Sadar Penuh)</option>
          <option value="APATIS" <?= ($assessment['consciousness'] ?? '') === 'APATIS' ? 'selected' : '' ?>>Apatis (Acuh tak acuh)</option>
          <option value="SOMNOLEN" <?= ($assessment['consciousness'] ?? '') === 'SOMNOLEN' ? 'selected' : '' ?>>Somnolen (Mengantuk berat)</option>
          <option value="SOPOR" <?= ($assessment['consciousness'] ?? '') === 'SOPOR' ? 'selected' : '' ?>>Sopor (Rangsang nyeri kuat)</option>
          <option value="COMA" <?= ($assessment['consciousness'] ?? '') === 'COMA' ? 'selected' : '' ?>>Coma (Tidak ada respon)</option>
        </select>
      </div>
    </div>

    <!-- Bagian 2: Morse Fall Scale (KARS Standard) -->
    <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 700; color: var(--blue-900); border-bottom: 1px solid var(--border); padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
      <i data-lucide="shield-alert" style="width: 18px; height: 18px; color: #d97706;"></i>
      <span>2. Pengkajian Risiko Jatuh Dewasa (Morse Fall Scale)</span>
    </h3>

    <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
      <p style="margin: 0 0 12px; font-size: 12px; color: #92400e; font-weight: 600;">
        Kriteria Morse: Skor &lt; 25 = Risiko Rendah | 25 - 44 = Risiko Sedang | &ge; 45 = Risiko Tinggi
      </p>

      <div class="grid-2" style="gap: 12px; font-size: 13px;">
        <div>
          <label style="display: block; font-weight: 600; margin-bottom: 4px;">1. Riwayat jatuh (3 bulan terakhir)</label>
          <select id="mfs_history" class="form-control mfs-calc" onchange="calculateMorse()">
            <option value="0">Tidak (0)</option>
            <option value="25">Ya (25)</option>
          </select>
        </div>

        <div>
          <label style="display: block; font-weight: 600; margin-bottom: 4px;">2. Diagnosis medis sekunder (&ge; 2 diagnosa)</label>
          <select id="mfs_secondary" class="form-control mfs-calc" onchange="calculateMorse()">
            <option value="0">Tidak (0)</option>
            <option value="15">Ya (15)</option>
          </select>
        </div>

        <div>
          <label style="display: block; font-weight: 600; margin-bottom: 4px;">3. Alat bantu berjalan</label>
          <select id="mfs_aids" class="form-control mfs-calc" onchange="calculateMorse()">
            <option value="0">Tidak ada / Bed rest / Dibantu perawat (0)</option>
            <option value="15">Tongkat / Kruk / Walker (15)</option>
            <option value="30">Berpegangan pada perabot/dinding (30)</option>
          </select>
        </div>

        <div>
          <label style="display: block; font-weight: 600; margin-bottom: 4px;">4. Terpasang akses IV / Terapi heparin</label>
          <select id="mfs_iv" class="form-control mfs-calc" onchange="calculateMorse()">
            <option value="0">Tidak (0)</option>
            <option value="20" selected>Ya (20)</option>
          </select>
        </div>

        <div>
          <label style="display: block; font-weight: 600; margin-bottom: 4px;">5. Gaya berjalan / berpindah</label>
          <select id="mfs_gait" class="form-control mfs-calc" onchange="calculateMorse()">
            <option value="0">Normal / Bed rest / Kursi roda (0)</option>
            <option value="10">Lemah (Langkah kecil/lambat) (10)</option>
            <option value="20">Terganggu (Kehilangan keseimbangan) (20)</option>
          </select>
        </div>

        <div>
          <label style="display: block; font-weight: 600; margin-bottom: 4px;">6. Status mental</label>
          <select id="mfs_mental" class="form-control mfs-calc" onchange="calculateMorse()">
            <option value="0">Menyadari kemampuan sendiri (0)</option>
            <option value="15">Lupa keterbatasan / Over-estimate (15)</option>
          </select>
        </div>
      </div>

      <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #fde68a; display: flex; align-items: center; justify-content: space-between;">
        <div>
          <span style="font-size: 13px; font-weight: 600; color: #78350f;">Hasil Kalkulasi Skor Morse:</span>
          <span id="displayScore" style="font-size: 18px; font-weight: 700; color: #b45309; margin-left: 8px;">20</span>
        </div>
        <div>
          <span style="font-size: 13px; font-weight: 600; color: #78350f;">Kategori Risiko:</span>
          <span id="displayCategory" class="badge" style="background: #dcfce7; color: #15803d; font-size: 13px; margin-left: 8px;">RENDAH</span>
        </div>
      </div>

      <input type="hidden" name="fall_risk_score" id="fall_risk_score" value="<?= (int)($assessment['fall_risk_score'] ?? 20) ?>">
      <input type="hidden" name="fall_risk_category" id="fall_risk_category" value="<?= htmlspecialchars($assessment['fall_risk_category'] ?? 'RENDAH') ?>">
    </div>

    <!-- Bagian 3: Skala Nyeri & Status Fungsional -->
    <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 700; color: var(--blue-900); border-bottom: 1px solid var(--border); padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
      <i data-lucide="activity" style="width: 18px; height: 18px; color: var(--blue-700);"></i>
      <span>3. Penilaian Derajat Nyeri &amp; Status Fungsional</span>
    </h3>

    <div class="grid-2" style="margin-bottom: 24px;">
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">
          Skala Nyeri (Numeric Rating Scale 0 - 10)
        </label>
        <div style="display: flex; align-items: center; gap: 12px;">
          <input type="range" name="pain_score" id="pain_slider" min="0" max="10" value="<?= (int)($assessment['pain_score'] ?? 0) ?>" class="form-control" style="flex: 1;" oninput="document.getElementById('painValue').innerText = this.value">
          <span id="painValue" style="font-size: 18px; font-weight: 700; color: var(--blue-900); min-width: 32px; text-align: center;">
            <?= (int)($assessment['pain_score'] ?? 0) ?>
          </span>
        </div>
        <small style="color: var(--muted); display: block; margin-top: 4px;">0: Tidak Nyeri | 1-3: Ringan | 4-6: Sedang | 7-10: Berat</small>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Status Fungsional (Barthel Index)</label>
        <select name="functional_status" class="form-control">
          <option value="MANDIRI" <?= ($assessment['functional_status'] ?? 'MANDIRI') === 'MANDIRI' ? 'selected' : '' ?>>MANDIRI (Aktivitas Harian Penuh Tanpa Bantuan)</option>
          <option value="PARSIAL" <?= ($assessment['functional_status'] ?? '') === 'PARSIAL' ? 'selected' : '' ?>>PARSIAL (Membutuhkan Bantuan Sebagian)</option>
          <option value="TOTAL" <?= ($assessment['functional_status'] ?? '') === 'TOTAL' ? 'selected' : '' ?>>TOTAL (Tergantung Penuh pada Perawat / Keluarga)</option>
        </select>
      </div>
    </div>

    <!-- Aksi -->
    <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--border); padding-top: 16px;">
      <a href="<?= base_url('index.php?c=nursing&a=patient&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-outline">
        Batal
      </a>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="save"></i>
        <span>Simpan Pengkajian Keperawatan</span>
      </button>
    </div>
  </form>
</div>

<script>
function calculateMorse() {
  var s1 = parseInt(document.getElementById('mfs_history').value) || 0;
  var s2 = parseInt(document.getElementById('mfs_secondary').value) || 0;
  var s3 = parseInt(document.getElementById('mfs_aids').value) || 0;
  var s4 = parseInt(document.getElementById('mfs_iv').value) || 0;
  var s5 = parseInt(document.getElementById('mfs_gait').value) || 0;
  var s6 = parseInt(document.getElementById('mfs_mental').value) || 0;

  var total = s1 + s2 + s3 + s4 + s5 + s6;
  document.getElementById('fall_risk_score').value = total;
  document.getElementById('displayScore').innerText = total;

  var cat = 'RENDAH';
  var catBadge = document.getElementById('displayCategory');
  if (total >= 45) {
    cat = 'TINGGI';
    catBadge.style.background = '#fee2e2';
    catBadge.style.color = '#b91c1c';
  } else if (total >= 25) {
    cat = 'SEDANG';
    catBadge.style.background = '#fef3c7';
    catBadge.style.color = '#b45309';
  } else {
    cat = 'RENDAH';
    catBadge.style.background = '#dcfce7';
    catBadge.style.color = '#15803d';
  }

  document.getElementById('fall_risk_category').value = cat;
  catBadge.innerText = cat;
}

// Initial calc on load
window.addEventListener('DOMContentLoaded', function() {
  calculateMorse();
});
</script>

