<?php
// app/views/outpatient/examine.php - Pemeriksaan Dokter Poliklinik & Rencana Kontrol (SOP/SIMRS/005/2026)
$latestVital = !empty($vitalSigns) ? $vitalSigns[0] : null;
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=outpatient&a=index') ?>" class="btn btn-outline" style="margin-bottom: 12px;">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Antrean Rawat Jalan</span>
  </a>

  <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
    <i data-lucide="stethoscope" style="color: var(--blue-700); width: 22px; height: 22px;"></i>
    <span>Pemeriksaan Medis Dokter Poliklinik</span>
  </h1>
  <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
    Anamnesis SOAP terintegrasi, penegakan diagnosis (Wajib ICD-10), instruksi tindakan &amp; resep, serta penetapan rencana kontrol ulang.
  </p>
</div>

<!-- Header Pasien & Data Asesmen Perawat -->
<div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 16px; margin-bottom: 20px;">
  <!-- Profil Pasien -->
  <div class="card" style="padding: 16px 20px; margin: 0; background: var(--blue-50); border: 1px solid var(--blue-200);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <span class="badge" style="background: var(--blue-700); color: #fff; margin-bottom: 6px;">
          Antrean <?= htmlspecialchars($visit['queue_number']) ?>
        </span>
        <h2 style="margin: 0; font-size: 17px; font-weight: 700; color: var(--blue-900);">
          <?= htmlspecialchars($visit['patient_name']) ?>
        </h2>
        <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
          No. RM: <b><?= htmlspecialchars($visit['no_rm']) ?></b> | NIK: <?= htmlspecialchars($visit['nik']) ?>
        </div>
        <div style="font-size: 12px; color: #475569; margin-top: 4px;">
          Poli: <b><?= htmlspecialchars($visit['policlinic_name']) ?></b> | Dokter: <?= htmlspecialchars($visit['doctor_name']) ?>
        </div>
      </div>
      <div>
        <span class="badge" style="background: #e0f2fe; color: #0369a1; font-weight: 600;">
          <?= htmlspecialchars($visit['payment_type'] ?? 'UMUM') ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Ringkasan TTV & Pengkajian Perawat -->
  <div class="card" style="padding: 16px 20px; margin: 0; border: 1px solid #fed7aa; background: #fffaf5;">
    <div style="font-size: 12px; font-weight: 700; color: #c2410c; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
      <i data-lucide="activity" style="width: 14px; height: 14px;"></i>
      <span>Hasil Asesmen Perawat &amp; TTV Awal</span>
    </div>
    <?php if ($latestVital): ?>
      <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; font-size: 12px;">
        <div>TD: <b><?= $latestVital['systolic_bp'] ?>/<?= $latestVital['diastolic_bp'] ?></b> mmHg</div>
        <div>Nadi: <b><?= $latestVital['pulse_rate'] ?></b> bpm</div>
        <div>Suhu: <b><?= $latestVital['temperature'] ?></b> &deg;C</div>
        <div>RR: <b><?= $latestVital['respiratory_rate'] ?></b> x/m</div>
        <div>SpO2: <b><?= $latestVital['spo2'] ?></b> %</div>
        <div>Nyeri: <b><?= $assessment['pain_score'] ?? 0 ?>/10</b></div>
      </div>
      <?php if (!empty($assessment['chief_complaint'])): ?>
        <div style="margin-top: 6px; font-size: 11px; color: var(--muted); border-top: 1px dashed #fed7aa; padding-top: 4px;">
          Keluhan: <i><?= htmlspecialchars($assessment['chief_complaint']) ?></i>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div style="font-size: 12px; color: var(--muted);">Belum ada data tanda vital tercatat.</div>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="padding: 24px; max-width: 900px;">
  <form method="POST" action="<?= base_url('index.php?c=outpatient&a=examine&id=' . $visit['id']) ?>">
    
    <!-- 1. Catatan SOAP Medis -->
    <h3 style="font-size: 15px; font-weight: 700; color: var(--blue-900); margin: 0 0 16px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
      1. Catatan Rekam Medis (SOAP Klinis)
    </h3>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          S - Subjective (Keluhan / Riwayat Pasien) <span style="color: #dc2626;">*</span>
        </label>
        <textarea name="subjective" class="form-control" rows="3" placeholder="Keluhan utama, riwayat penyakit sekarang & dahulu..." required><?= htmlspecialchars($postData['subjective'] ?? ($assessment['chief_complaint'] ?? '')) ?></textarea>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          O - Objective (Pemeriksaan Fisik Dokter) <span style="color: #dc2626;">*</span>
        </label>
        <textarea name="objective" class="form-control" rows="3" placeholder="Temuan klinis fisik kepala, toraks, abdomen, ekstremitas..." required><?= htmlspecialchars($postData['objective'] ?? '') ?></textarea>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          A - Assessment (Catatan Klinis Dokter)
        </label>
        <textarea name="assessment_notes" class="form-control" rows="2" placeholder="Analisis diagnosis banding klinis..."><?= htmlspecialchars($postData['assessment_notes'] ?? '') ?></textarea>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          P - Plan (Instruksi Rencana Terapi Medis)
        </label>
        <textarea name="plan_notes" class="form-control" rows="2" placeholder="Rencana pengobatan, edukasi diet, hidrasi..."><?= htmlspecialchars($postData['plan_notes'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- 2. Penegakan Diagnosis (Kepatuhan Wajib ICD-10 Sesuai SOP) -->
    <h3 style="font-size: 15px; font-weight: 700; color: var(--blue-900); margin: 0 0 16px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
      2. Penegakan Diagnosis Utama (ICD-10)
    </h3>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
      <div style="display: grid; grid-template-columns: 200px 1fr; gap: 16px;">
        <div>
          <label style="display: block; font-size: 13px; font-weight: 700; color: #b91c1c; margin-bottom: 6px;">
            Kode ICD-10 <span style="color: #dc2626;">* (Wajib)</span>
          </label>
          <input type="text" name="icd10_code" list="icdListOptions" class="form-control" placeholder="Misal: I10, E11, J00" value="<?= htmlspecialchars($postData['icd10_code'] ?? '') ?>" required style="font-weight: 700; border-color: #cbd5e1;">
          <datalist id="icdListOptions">
            <?php foreach ($icdList as $icd): ?>
              <option value="<?= htmlspecialchars($icd['code']) ?>"><?= htmlspecialchars($icd['description']) ?></option>
            <?php endforeach; ?>
          </datalist>
          <div style="font-size: 11px; color: #b91c1c; margin-top: 4px;">
            * Sesuai SOP, form ditolak jika kosong.
          </div>
        </div>

        <div>
          <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
            Nama / Deskripsi Diagnosis Klinis
          </label>
          <input type="text" name="diagnosis_name" class="form-control" placeholder="Misal: Hipertensi Primer / Essensial" value="<?= htmlspecialchars($postData['diagnosis_name'] ?? '') ?>">
        </div>
      </div>
    </div>

    <!-- 3. Rencana Tindak Lanjut & Kontrol Ulang -->
    <h3 style="font-size: 15px; font-weight: 700; color: var(--blue-900); margin: 0 0 16px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
      3. Rencana Tindak Lanjut &amp; Kontrol Ulang
    </h3>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
      <div style="grid-column: span 2;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">
          Tindak Lanjut Pasien <span style="color: #dc2626;">*</span>
        </label>
        <div style="display: flex; gap: 20px;">
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px;">
            <input type="radio" name="follow_up" value="KONTROL" checked onclick="toggleControlForm(true)">
            <span>Jadwalkan Kontrol Ulang</span>
          </label>
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px;">
            <input type="radio" name="follow_up" value="RUJUK" onclick="toggleControlForm(false)">
            <span>Rujukan (Internal/Eksternal)</span>
          </label>
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px;">
            <input type="radio" name="follow_up" value="SELESAI" onclick="toggleControlForm(false)">
            <span>Selesai Pengobatan Tanpa Kontrol</span>
          </label>
        </div>
      </div>

      <div id="controlSectionDate">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Tanggal Rencana Kontrol Ulang
        </label>
        <input type="date" name="control_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
      </div>

      <div id="controlSectionNotes">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Instruksi Kontrol / Edukasi Pasien
        </label>
        <input type="text" name="control_notes" class="form-control" placeholder="Misal: Evaluasi tensi darah & laboratorium darah rutin">
      </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border); padding-top: 16px;">
      <a href="<?= base_url('index.php?c=outpatient&a=index') ?>" class="btn btn-outline">Batal</a>
      <button type="submit" class="btn btn-primary" style="background: #0284c7; border-color: #0369a1;">
        <i data-lucide="check-circle-2"></i>
        <span>Sahkan Pemeriksaan &amp; Kunci RME</span>
      </button>
    </div>
  </form>
</div>

<script>
function toggleControlForm(show) {
  document.getElementById('controlSectionDate').style.display = show ? 'block' : 'none';
  document.getElementById('controlSectionNotes').style.display = show ? 'block' : 'none';
}
</script>

