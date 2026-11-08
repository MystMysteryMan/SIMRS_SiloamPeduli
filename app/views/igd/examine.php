<?php
// app/views/igd/examine.php - Primary Survey ABCDE & Asesmen Dokter IGD
$a = $visit['assessment'] ?? [];
$t = $visit['triage'] ?? [];
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="stethoscope" style="color: #dc2626; width: 22px; height: 22px;"></i>
      <span>Pemeriksaan Klinis Dokter &amp; Primary Survey ABCDE (IGD)</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Asesmen kegawatdaruratan trauma/non-trauma, penegakan diagnosis kerja ICD-10 terstandar, dan instruksi terapi emergensi.
    </p>
  </div>
  <div style="display: flex; gap: 8px;">
    <?php if (!empty($visit['patient_id'])): ?>
      <a href="<?= base_url('index.php?c=cpoe&a=create&visit_type=IGD&reference_id=' . $visit['id'] . '&patient_id=' . $visit['patient_id']) ?>" class="btn btn-outline" style="border-color: #dc2626; color: #b91c1c;">
        <i data-lucide="clipboard-plus"></i>
        <span>Order Emergensi CITO (CPOE)</span>
      </a>
    <?php endif; ?>
    <a href="<?= base_url('index.php?c=igd&a=index') ?>" class="btn btn-outline">
      <i data-lucide="arrow-left"></i>
      <span>Kembali ke IGD</span>
    </a>
  </div>
</div>

<!-- Patient & Triage Header -->
<div class="card" style="margin-bottom: 20px; background: #fff; border-left: 4px solid <?= ($visit['triage_category'] === 'MERAH') ? '#dc2626' : (($visit['triage_category'] === 'KUNING') ? '#d97706' : '#16a34a') ?>; padding: 16px 20px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
    <div>
      <div style="display: flex; align-items: center; gap: 8px;">
        <span class="badge badge-triage-<?= strtolower($visit['triage_category'] ?? 'kuning') ?>">
          TRIAGE: <?= $visit['triage_category'] ?? 'KUNING' ?> (ESI <?= $t['esi_level'] ?? '-' ?>)
        </span>
        <span style="font-family: ui-monospace, monospace; font-size: 13px; font-weight: 700; color: var(--blue-900);">
          <?= htmlspecialchars($visit['visit_number']) ?>
        </span>
      </div>
      <h2 style="margin: 6px 0 2px; font-size: 18px; font-weight: 700; color: var(--ink);">
        <?= htmlspecialchars($visit['patient_name'] ?? $visit['anonymous_name']) ?>
      </h2>
      <div style="font-size: 12px; color: var(--muted); display: flex; gap: 10px; flex-wrap: wrap;">
        <span>No. RM: <b><?= htmlspecialchars($visit['no_rm'] ?? 'MR/MRS. X') ?></b></span>
        <span>&bull;</span>
        <span>Penjamin: <b><?= htmlspecialchars($visit['insurance_type'] ?? 'UMUM') ?></b></span>
        <span>&bull;</span>
        <span>Keluhan: <b><?= htmlspecialchars($t['chief_complaint'] ?? '-') ?></b></span>
      </div>
    </div>
    <div style="text-align: right; font-size: 12px; color: var(--muted);">
      <div>Tiba: <b><?= date('d M Y, H:i', strtotime($visit['arrival_time'])) ?></b></div>
      <div style="margin-top: 2px;">GCS: <b style="color: var(--blue-900);"><?= $t['gcs_total'] ?? '15' ?></b> &bull; TD: <b><?= $t['systolic_bp'] ?? '-' ?>/<?= $t['diastolic_bp'] ?? '-' ?></b> &bull; SpO2: <b><?= $t['spo2'] ?? '-' ?>%</b></div>
    </div>
  </div>
</div>

<form method="POST" action="<?= base_url('index.php?c=igd&a=storeExamine') ?>" id="examineForm">
  <?= csrf_field() ?>
  <input type="hidden" name="emergency_visit_id" value="<?= $visit['id'] ?>">

  <!-- 1. Primary Survey ABCDE Card -->
  <div class="card" style="margin-bottom: 20px;">
    <h3 style="margin: 0 0 16px; font-size: 14px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="check-square" class="lucide-md" style="color: #dc2626;"></i>
      <span>1. Primary Survey ABCDE (Asesmen Primer Kegawatdaruratan)</span>
    </h3>

    <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
      <!-- A: Airway -->
      <div style="padding: 12px 14px; background: var(--surface-alt); border-radius: var(--radius-sm); border-left: 3px solid var(--blue-600);">
        <label class="form-label" style="font-weight: 700; color: var(--blue-900); margin-bottom: 4px;">
          A – Airway with Cervical Spine Control (Jalan Napas)
        </label>
        <textarea name="primary_airway" class="form-control" rows="2" placeholder="Evaluasi kepatenan jalan napas, stridor/gurgling/snoring, fiksasi servikal neck collar jika trauma..." required><?= htmlspecialchars($a['primary_airway'] ?? 'Bebas, tidak ada sumbatan benda asing atau cairan. Kontrol servikal stabil.') ?></textarea>
      </div>

      <!-- B: Breathing -->
      <div style="padding: 12px 14px; background: var(--surface-alt); border-radius: var(--radius-sm); border-left: 3px solid var(--blue-600);">
        <label class="form-label" style="font-weight: 700; color: var(--blue-900); margin-bottom: 4px;">
          B – Breathing &amp; Ventilation (Pernapasan &amp; Oksigenasi)
        </label>
        <textarea name="primary_breathing" class="form-control" rows="2" placeholder="Evaluasi gerakan dada simetris, deviasi trakea, suara napas vesikuler/ronki/wheezing, laju napas..." required><?= htmlspecialchars($a['primary_breathing'] ?? 'Gerakan napas simetris, tidak ada retraksi dinding dada. Suara napas vesikuler kanan=kiri, rhonki (-), wheezing (-).') ?></textarea>
      </div>

      <!-- C: Circulation -->
      <div style="padding: 12px 14px; background: var(--surface-alt); border-radius: var(--radius-sm); border-left: 3px solid var(--blue-600);">
        <label class="form-label" style="font-weight: 700; color: var(--blue-900); margin-bottom: 4px;">
          C – Circulation with Hemorrhage Control (Sirkulasi &amp; Kontrol Perdarahan)
        </label>
        <textarea name="primary_circulation" class="form-control" rows="2" placeholder="Evaluasi nadi perifer & sentral, CRT, akral hangat/dingin, perdarahan eksternal aktif..." required><?= htmlspecialchars($a['primary_circulation'] ?? 'Nadi teraba kuat dan teratur. Akral hangat, kering, merah, CRT < 2 detik. Tidak tampak perdarahan eksternal aktif.') ?></textarea>
      </div>

      <!-- D: Disability -->
      <div style="padding: 12px 14px; background: var(--surface-alt); border-radius: var(--radius-sm); border-left: 3px solid var(--blue-600);">
        <label class="form-label" style="font-weight: 700; color: var(--blue-900); margin-bottom: 4px;">
          D – Disability (Status Neurologis &amp; Pupil)
        </label>
        <textarea name="primary_disability" class="form-control" rows="2" placeholder="Pupil isokor/anisokor, diameter, refleks cahaya (+/+), lateralisasi motorik..." required><?= htmlspecialchars($a['primary_disability'] ?? 'Pupil isokor diameter 3mm/3mm, refleks cahaya langsung (+/+), refleks cahaya konsensuil (+/+). Motorik ekstremitas bebas.') ?></textarea>
      </div>

      <!-- E: Exposure -->
      <div style="padding: 12px 14px; background: var(--surface-alt); border-radius: var(--radius-sm); border-left: 3px solid var(--blue-600);">
        <label class="form-label" style="font-weight: 700; color: var(--blue-900); margin-bottom: 4px;">
          E – Exposure &amp; Environmental Control (Pemeriksaan Tubuh &amp; Suhu)
        </label>
        <textarea name="primary_exposure" class="form-control" rows="2" placeholder="Log roll, jejas trauma, deformitas, luka bakar, pencegahan hipotermia..." required><?= htmlspecialchars($a['primary_exposure'] ?? 'Tidak tampak deformitas, jejas hematom mayor, atau luka terbuka luas. Suhu tubuh terjaga dengan selimut hangat.') ?></textarea>
      </div>
    </div>
  </div>

  <!-- 2. Secondary Survey & Anamnesis Khusus -->
  <div class="card" style="margin-bottom: 20px;">
    <h3 style="margin: 0 0 14px; font-size: 14px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="file-text" class="lucide-md" style="color: var(--blue-600);"></i>
      <span>2. Secondary Survey (Riwayat SAMPLE &amp; Pemeriksaan Fisik Tambahan)</span>
    </h3>

    <div>
      <textarea name="secondary_survey" class="form-control" rows="3" placeholder="S (Signs/Symptoms), A (Allergies), M (Medications), P (Past history), L (Last meal), E (Events leading to injury/illness)..."><?= htmlspecialchars($a['secondary_survey'] ?? '') ?></textarea>
    </div>
  </div>

  <!-- 3. Diagnosis Klinis (Wajib ICD-10) & Rencana Terapi -->
  <div class="card" style="margin-bottom: 20px; border-top: 3px solid #dc2626;">
    <h3 style="margin: 0 0 14px; font-size: 14px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="bookmark" class="lucide-md" style="color: #dc2626;"></i>
      <span>3. Penegakan Diagnosis Kerja Emergensi (Standar SOP: Wajib Kode ICD-10) <span style="color: var(--danger);">*</span></span>
    </h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; margin-bottom: 14px;">
      <!-- Pilihan ICD-10 -->
      <div>
        <label class="form-label" style="font-weight: 700;">Kode ICD-10 <span style="color: var(--danger);">*</span></label>
        <select name="icd10_code" id="icdSelect" class="form-control" required onchange="syncDiagnosisName(this)">
          <option value="">-- Pilih Kode Diagnosis ICD-10 --</option>
          <?php foreach ($diagnoses as $diag): ?>
            <option value="<?= htmlspecialchars($diag['code']) ?>" data-name="<?= htmlspecialchars($diag['description_id']) ?>" <?= (($a['icd10_code'] ?? '') === $diag['code']) ? 'selected' : '' ?>>
              [<?= htmlspecialchars($diag['code']) ?>] <?= htmlspecialchars($diag['description_id']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div style="font-size: 11px; color: var(--danger); margin-top: 3px;">
          *Sistem akan menolak penyimpanan asesmen jika kode ICD-10 dikosongkan.
        </div>
      </div>

      <!-- Nama Diagnosis -->
      <div>
        <label class="form-label" style="font-weight: 700;">Nama Diagnosis / Kesimpulan Klinis</label>
        <input type="text" name="diagnosis_name" id="diagnosisName" class="form-control" placeholder="Contoh: Hipertensi Emergensi / Cedera Kepala Ringan" value="<?= htmlspecialchars($a['diagnosis_name'] ?? '') ?>" required>
      </div>

      <!-- Dokter Jaga IGD -->
      <div>
        <label class="form-label" style="font-weight: 700;">Dokter Jaga Pemeriksa</label>
        <select name="doctor_id" class="form-control">
          <?php foreach ($doctors as $doc): ?>
            <option value="<?= $doc['id'] ?>" <?= (($visit['doctor_id'] ?? 1) == $doc['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($doc['full_name']) ?> (<?= htmlspecialchars($doc['specialization']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Rencana Terapi Emergensi -->
    <div>
      <label class="form-label" style="font-weight: 700;">Rencana Terapi, Tindakan &amp; Resusitasi</label>
      <textarea name="therapy_plan" class="form-control" rows="3" placeholder="Instruksi cairan infus, analgetik/antibiotik darurat, oksigenasi, tirah baring, atau rencana transfer rawat..." required><?= htmlspecialchars($a['therapy_plan'] ?? 'Pasang IVFD RL 20 tpm, observasi tanda vital per 15 menit, evaluasi ulang kondisi.') ?></textarea>
    </div>
  </div>

  <!-- Submit Buttons -->
  <div style="display: flex; gap: 12px; justify-content: flex-end;">
    <a href="<?= base_url('index.php?c=igd&a=index') ?>" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary" style="background: #dc2626; border-color: #b91c1c; padding: 10px 24px; font-weight: 700;">
      <i data-lucide="check"></i>
      <span>Simpan Asesmen &amp; Buka Lembar Monitoring</span>
    </button>
  </div>
</form>

<script>
function syncDiagnosisName(select) {
  const opt = select.options[select.selectedIndex];
  if (opt && opt.dataset.name) {
    document.getElementById('diagnosisName').value = opt.dataset.name;
  }
}
</script>

