<?php
// app/views/igd/triage.php - Formulir Pengkajian Triase Klinis ESI & Skor GCS
$t = $visit['triage'] ?? [];
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="activity" style="color: #dc2626; width: 22px; height: 22px;"></i>
      <span>Pengkajian Triase Klinis IGD (ESI / Emergency Severity Index)</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Penetapan tingkat kegawatan pasien, penilaian jalan napas, survei sirkulasi, dan kalkulasi Glasgow Coma Scale (GCS).
    </p>
  </div>
  <div>
    <a href="<?= base_url('index.php?c=igd&a=index') ?>" class="btn btn-outline">
      <i data-lucide="arrow-left"></i>
      <span>Kembali ke IGD</span>
    </a>
  </div>
</div>

<!-- Patient Header Banner -->
<div class="card" style="margin-bottom: 20px; background: var(--blue-50); border-color: rgba(26,73,123,.2); padding: 16px 20px;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <span class="badge badge-primary" style="font-family: ui-monospace, monospace; font-size: 13px; font-weight: 700;">
        <?= htmlspecialchars($visit['visit_number']) ?>
      </span>
      <h2 style="margin: 6px 0 2px; font-size: 18px; font-weight: 700; color: var(--blue-950);">
        <?= htmlspecialchars($visit['patient_name'] ?? $visit['anonymous_name']) ?>
      </h2>
      <div style="font-size: 12px; color: var(--ink-light); display: flex; gap: 12px; flex-wrap: wrap;">
        <span>No. RM: <b><?= htmlspecialchars($visit['no_rm'] ?? 'MR/MRS. X') ?></b></span>
        <span>Penjamin: <b><?= htmlspecialchars($visit['insurance_type'] ?? 'UMUM') ?></b></span>
        <span>Tiba: <b><?= date('d M Y, H:i', strtotime($visit['arrival_time'])) ?></b></span>
        <span>Transportasi: <b><?= htmlspecialchars($visit['transportation']) ?></b></span>
      </div>
    </div>
    <div>
      <?php if (!empty($visit['triage_category'])): ?>
        <span class="badge badge-triage-<?= strtolower($visit['triage_category']) ?>" style="font-size: 14px; padding: 6px 14px;">
          TRIAGE: <?= $visit['triage_category'] ?> (ESI <?= $t['esi_level'] ?? '-' ?>)
        </span>
      <?php endif; ?>
    </div>
  </div>
</div>

<form method="POST" action="<?= base_url('index.php?c=igd&a=storeTriage') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="emergency_visit_id" value="<?= $visit['id'] ?>">

  <!-- 1. Kategori Triase ESI Card -->
  <div class="card" style="margin-bottom: 20px;">
    <h3 style="margin: 0 0 14px; font-size: 14px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="shield-alert" class="lucide-md" style="color: #dc2626;"></i>
      <span>1. Penentuan Kategori Triase ESI &amp; Zona Kegawatan <span style="color: var(--danger);">*</span></span>
    </h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px;">
      <!-- Merah -->
      <label class="card" style="padding: 14px; cursor: pointer; border-left: 4px solid #dc2626; background: #fff;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
          <input type="radio" name="triage_level" value="MERAH" <?= (($t['triage_level'] ?? '') === 'MERAH') ? 'checked' : '' ?> required onchange="setEsiDefault(1)">
          <span class="badge badge-triage-merah" style="font-size: 12px;">ZONA MERAH (ESI 1-2)</span>
        </div>
        <div style="font-size: 12px; font-weight: 600; color: #b91c1c;">Prioritas 0 Menit (Resusitasi)</div>
        <div style="font-size: 11px; color: var(--muted); margin-top: 4px;">Henti jantung, henti napas, syok anafilaksis, luka bakar luas.</div>
      </label>

      <!-- Kuning -->
      <label class="card" style="padding: 14px; cursor: pointer; border-left: 4px solid #d97706; background: #fff;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
          <input type="radio" name="triage_level" value="KUNING" <?= (($t['triage_level'] ?? 'KUNING') === 'KUNING') ? 'checked' : '' ?> required onchange="setEsiDefault(3)">
          <span class="badge badge-triage-kuning" style="font-size: 12px;">ZONA KUNING (ESI 3)</span>
        </div>
        <div style="font-size: 12px; font-weight: 600; color: #b45309;">Prioritas &le; 30 Menit (Urgent)</div>
        <div style="font-size: 11px; color: var(--muted); margin-top: 4px;">Fraktur tertutup, kolik abdomen akut, febris tinggi, asma sedang.</div>
      </label>

      <!-- Hijau -->
      <label class="card" style="padding: 14px; cursor: pointer; border-left: 4px solid #16a34a; background: #fff;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
          <input type="radio" name="triage_level" value="HIJAU" <?= (($t['triage_level'] ?? '') === 'HIJAU') ? 'checked' : '' ?> required onchange="setEsiDefault(4)">
          <span class="badge badge-triage-hijau" style="font-size: 12px;">ZONA HIJAU (ESI 4-5)</span>
        </div>
        <div style="font-size: 12px; font-weight: 600; color: #15803d;">Prioritas &le; 60 Menit (Non-Urgent)</div>
        <div style="font-size: 11px; color: var(--muted); margin-top: 4px;">Luka lecet, batuk pilek ringan, kontrol luka jahitan.</div>
      </label>

      <!-- Hitam -->
      <label class="card" style="padding: 14px; cursor: pointer; border-left: 4px solid #0f172a; background: #fff;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
          <input type="radio" name="triage_level" value="HITAM" <?= (($t['triage_level'] ?? '') === 'HITAM') ? 'checked' : '' ?> required onchange="setEsiDefault(5)">
          <span class="badge badge-triage-hitam" style="font-size: 12px;">ZONA HITAM (DOA)</span>
        </div>
        <div style="font-size: 12px; font-weight: 600; color: #0f172a;">Dead on Arrival</div>
        <div style="font-size: 11px; color: var(--muted); margin-top: 4px;">Tanda kematian biologis pasti sebelum tiba di rumah sakit.</div>
      </label>
    </div>

    <div style="margin-top: 14px; display: flex; align-items: center; gap: 12px;">
      <label class="form-label" style="font-weight: 600; margin: 0;">Level ESI Spesifik (1 s/d 5):</label>
      <select name="esi_level" id="esiLevelSelect" class="form-control" style="width: 140px;" required>
        <option value="1" <?= (($t['esi_level'] ?? 3) == 1) ? 'selected' : '' ?>>Level 1 (Resusitasi)</option>
        <option value="2" <?= (($t['esi_level'] ?? 3) == 2) ? 'selected' : '' ?>>Level 2 (Emergent)</option>
        <option value="3" <?= (($t['esi_level'] ?? 3) == 3) ? 'selected' : '' ?>>Level 3 (Urgent)</option>
        <option value="4" <?= (($t['esi_level'] ?? 3) == 4) ? 'selected' : '' ?>>Level 4 (Semi-Urgent)</option>
        <option value="5" <?= (($t['esi_level'] ?? 3) == 5) ? 'selected' : '' ?>>Level 5 (Non-Urgent)</option>
      </select>
    </div>
  </div>

  <!-- 2. Keluhan & Penilaian ABC Awal Card -->
  <div class="card" style="margin-bottom: 20px;">
    <h3 style="margin: 0 0 14px; font-size: 14px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="clipboard-check" class="lucide-md" style="color: var(--blue-600);"></i>
      <span>2. Keluhan Utama &amp; Skrining ABC Awal</span>
    </h3>

    <div style="margin-bottom: 14px;">
      <label class="form-label" style="font-weight: 600;">Keluhan Utama Pasien <span style="color: var(--danger);">*</span></label>
      <textarea name="chief_complaint" class="form-control" rows="2" placeholder="Uraikan keluhan utama, mekanisme cedera (laka/trauma), atau waktu onset gejala..." required><?= htmlspecialchars($t['chief_complaint'] ?? '') ?></textarea>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px;">
      <div>
        <label class="form-label" style="font-weight: 600;">Airway (Jalan Napas)</label>
        <select name="airway_assessment" class="form-control">
          <option value="Paten" <?= (($t['airway_assessment'] ?? '') === 'Paten') ? 'selected' : '' ?>>Paten (Bebas)</option>
          <option value="Stridor" <?= (($t['airway_assessment'] ?? '') === 'Stridor') ? 'selected' : '' ?>>Stridor (Sumbatan Laring)</option>
          <option value="Gurgling" <?= (($t['airway_assessment'] ?? '') === 'Gurgling') ? 'selected' : '' ?>>Gurgling (Cairan/Darah)</option>
          <option value="Obstruksi Total" <?= (($t['airway_assessment'] ?? '') === 'Obstruksi Total') ? 'selected' : '' ?>>Obstruksi Total</option>
        </select>
      </div>

      <div>
        <label class="form-label" style="font-weight: 600;">Breathing (Pola Napas)</label>
        <select name="breathing_assessment" class="form-control">
          <option value="Spontan" <?= (($t['breathing_assessment'] ?? '') === 'Spontan') ? 'selected' : '' ?>>Spontan &amp; Reguler</option>
          <option value="Sesak Berat" <?= (($t['breathing_assessment'] ?? '') === 'Sesak Berat') ? 'selected' : '' ?>>Sesak Berat (Takipnea/Cuping)</option>
          <option value="Bradipnea" <?= (($t['breathing_assessment'] ?? '') === 'Bradipnea') ? 'selected' : '' ?>>Bradipnea (&lt; 10x/m)</option>
          <option value="Apnea" <?= (($t['breathing_assessment'] ?? '') === 'Apnea') ? 'selected' : '' ?>>Apnea (Henti Napas)</option>
        </select>
      </div>

      <div>
        <label class="form-label" style="font-weight: 600;">Circulation (Perfusi &amp; Nadi)</label>
        <select name="circulation_assessment" class="form-control">
          <option value="Nadi Teraba Kuat" <?= (($t['circulation_assessment'] ?? '') === 'Nadi Teraba Kuat') ? 'selected' : '' ?>>Nadi Teraba Kuat, Hangat</option>
          <option value="Nadi Lemah & Cepat" <?= (($t['circulation_assessment'] ?? '') === 'Nadi Lemah & Cepat') ? 'selected' : '' ?>>Nadi Lemah &amp; Cepat (Presyok)</option>
          <option value="Syok / Akral Dingin" <?= (($t['circulation_assessment'] ?? '') === 'Syok / Akral Dingin') ? 'selected' : '' ?>>Syok / Akral Dingin Basah</option>
          <option value="Nadi Karotis Tak Teraba" <?= (($t['circulation_assessment'] ?? '') === 'Nadi Karotis Tak Teraba') ? 'selected' : '' ?>>Henti Jantung (Nadi Tak Teraba)</option>
        </select>
      </div>
    </div>
  </div>

  <!-- 3. Glasgow Coma Scale (GCS) & Tanda Vital Card -->
  <div class="card" style="margin-bottom: 20px;">
    <h3 style="margin: 0 0 14px; font-size: 14px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="brain" class="lucide-md" style="color: var(--blue-600);"></i>
      <span>3. Glasgow Coma Scale (GCS) &amp; Tanda Vital Triase</span>
    </h3>

    <!-- GCS Selector -->
    <div style="background: var(--surface-alt); padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 16px;">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
        <div>
          <label class="form-label" style="font-weight: 600;">Eye Response (E)</label>
          <select name="gcs_eye" id="gcsEye" class="form-control" onchange="calcGcs()">
            <option value="4" <?= (($t['gcs_eye'] ?? 4) == 4) ? 'selected' : '' ?>>4 - Spontan buka mata</option>
            <option value="3" <?= (($t['gcs_eye'] ?? 4) == 3) ? 'selected' : '' ?>>3 - Respon terhadap suara</option>
            <option value="2" <?= (($t['gcs_eye'] ?? 4) == 2) ? 'selected' : '' ?>>2 - Respon terhadap nyeri</option>
            <option value="1" <?= (($t['gcs_eye'] ?? 4) == 1) ? 'selected' : '' ?>>1 - Tidak membuka mata</option>
          </select>
        </div>

        <div>
          <label class="form-label" style="font-weight: 600;">Verbal Response (V)</label>
          <select name="gcs_verbal" id="gcsVerbal" class="form-control" onchange="calcGcs()">
            <option value="5" <?= (($t['gcs_verbal'] ?? 5) == 5) ? 'selected' : '' ?>>5 - Orientasi baik &amp; jelas</option>
            <option value="4" <?= (($t['gcs_verbal'] ?? 5) == 4) ? 'selected' : '' ?>>4 - Bingung (Confused)</option>
            <option value="3" <?= (($t['gcs_verbal'] ?? 5) == 3) ? 'selected' : '' ?>>3 - Kata-kata tidak tepat</option>
            <option value="2" <?= (($t['gcs_verbal'] ?? 5) == 2) ? 'selected' : '' ?>>2 - Suara tanpa arti (mengerang)</option>
            <option value="1" <?= (($t['gcs_verbal'] ?? 5) == 1) ? 'selected' : '' ?>>1 - Tidak bersuara</option>
          </select>
        </div>

        <div>
          <label class="form-label" style="font-weight: 600;">Motor Response (M)</label>
          <select name="gcs_motor" id="gcsMotor" class="form-control" onchange="calcGcs()">
            <option value="6" <?= (($t['gcs_motor'] ?? 6) == 6) ? 'selected' : '' ?>>6 - Mengikuti perintah</option>
            <option value="5" <?= (($t['gcs_motor'] ?? 6) == 5) ? 'selected' : '' ?>>5 - Melokalisir nyeri</option>
            <option value="4" <?= (($t['gcs_motor'] ?? 6) == 4) ? 'selected' : '' ?>>4 - Menarik diri dari nyeri</option>
            <option value="3" <?= (($t['gcs_motor'] ?? 6) == 3) ? 'selected' : '' ?>>3 - Fleksi abnormal (dekortikasi)</option>
            <option value="2" <?= (($t['gcs_motor'] ?? 6) == 2) ? 'selected' : '' ?>>2 - Ekstensi abnormal (deserebrasi)</option>
            <option value="1" <?= (($t['gcs_motor'] ?? 6) == 1) ? 'selected' : '' ?>>1 - Tidak ada gerakan</option>
          </select>
        </div>

        <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; background: #fff; border-radius: var(--radius-sm); border: 1px solid var(--line); padding: 8px;">
          <span style="font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Total Skor GCS</span>
          <span id="gcsTotalDisplay" style="font-size: 24px; font-weight: 800; color: var(--blue-900);">
            <?= $t['gcs_total'] ?? 15 ?>
          </span>
          <span id="gcsNote" style="font-size: 11px; color: var(--success); font-weight: 600;">Compos Mentis</span>
        </div>
      </div>
    </div>

    <!-- Tanda Vital -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px;">
      <div>
        <label class="form-label" style="font-size: 11.5px;">TD Sistolik (mmHg)</label>
        <input type="number" name="systolic_bp" class="form-control num" value="<?= $t['systolic_bp'] ?? 120 ?>" required>
      </div>
      <div>
        <label class="form-label" style="font-size: 11.5px;">TD Diastolik (mmHg)</label>
        <input type="number" name="diastolic_bp" class="form-control num" value="<?= $t['diastolic_bp'] ?? 80 ?>" required>
      </div>
      <div>
        <label class="form-label" style="font-size: 11.5px;">Nadi (x/mnt)</label>
        <input type="number" name="pulse_rate" class="form-control num" value="<?= $t['pulse_rate'] ?? 80 ?>" required>
      </div>
      <div>
        <label class="form-label" style="font-size: 11.5px;">Laju Napas / RR (x/mnt)</label>
        <input type="number" name="respiratory_rate" class="form-control num" value="<?= $t['respiratory_rate'] ?? 20 ?>" required>
      </div>
      <div>
        <label class="form-label" style="font-size: 11.5px;">Suhu Tubuh (&deg;C)</label>
        <input type="number" step="0.1" name="temperature" class="form-control num" value="<?= $t['temperature'] ?? 36.5 ?>" required>
      </div>
      <div>
        <label class="form-label" style="font-size: 11.5px;">Saturasi SpO2 (%)</label>
        <input type="number" name="spo2" class="form-control num" value="<?= $t['spo2'] ?? 98 ?>" required>
      </div>
    </div>
  </div>

  <!-- Submit Buttons -->
  <div style="display: flex; gap: 12px; justify-content: flex-end;">
    <a href="<?= base_url('index.php?c=igd&a=index') ?>" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary" style="background: #dc2626; border-color: #b91c1c; padding: 10px 24px; font-weight: 700;">
      <i data-lucide="check"></i>
      <span>Simpan Pengkajian Triase</span>
    </button>
  </div>
</form>

<script>
function setEsiDefault(esi) {
  document.getElementById('esiLevelSelect').value = esi;
}

function calcGcs() {
  const e = parseInt(document.getElementById('gcsEye').value) || 4;
  const v = parseInt(document.getElementById('gcsVerbal').value) || 5;
  const m = parseInt(document.getElementById('gcsMotor').value) || 6;
  const total = e + v + m;

  document.getElementById('gcsTotalDisplay').innerText = total;
  const note = document.getElementById('gcsNote');

  if (total >= 14) {
    note.innerText = 'Compos Mentis (Sadar Penuh)';
    note.style.color = 'var(--success)';
  } else if (total >= 12) {
    note.innerText = 'Apatis / Somnolen';
    note.style.color = '#d97706';
  } else if (total >= 9) {
    note.innerText = 'Sopor';
    note.style.color = '#dc2626';
  } else {
    note.innerText = 'Koma (Kritis)';
    note.style.color = '#dc2626';
  }
}
calcGcs();
</script>

