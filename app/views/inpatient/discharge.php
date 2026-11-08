<?php
// app/views/inpatient/discharge.php - Form Resume Medis / Discharge Summary DPJP (SOP/SIMRS/006/2026)
$daysStay = max(1, ceil((time() - strtotime($admission['admission_date'])) / 86400));
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=inpatient&a=index') ?>" class="btn btn-outline" style="margin-bottom: 12px;">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Rawat Inap</span>
  </a>

  <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
    <i data-lucide="file-text" style="color: var(--blue-700); width: 22px; height: 22px;"></i>
    <span>Resume Medis / Discharge Summary Pasien Rawat Inap</span>
  </h1>
  <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
    SOP/SIMRS/006/2026: Dokter DPJP wajib mengisi resume kepulangan (diagnosis akhir ICD-10) sebelum administrasi kasir ditutup dan tempat tidur dibebaskan.
  </p>
</div>

<!-- Header Pasien & Ringkasan Perawatan -->
<div class="card" style="margin-bottom: 20px; padding: 20px; background: var(--blue-50); border: 1px solid var(--blue-200); max-width: 900px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
      <span class="badge" style="background: var(--blue-700); color: #fff; margin-bottom: 6px;">
        Registrasi <?= htmlspecialchars($admission['admission_number']) ?>
      </span>
      <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--blue-900);">
        <?= htmlspecialchars($admission['patient_name']) ?>
      </h2>
      <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
        No. RM: <b><?= htmlspecialchars($admission['no_rm']) ?></b> | NIK: <?= htmlspecialchars($admission['nik']) ?> | Penjamin: <b><?= htmlspecialchars($admission['insurance_type']) ?></b>
      </div>
    </div>
    <div style="display: flex; gap: 12px; text-align: right;">
      <div style="padding: 10px 14px; background: #fff; border: 1px solid var(--blue-200); border-radius: 6px;">
        <div style="font-size: 11px; color: var(--muted);">Ruang / Bed</div>
        <div style="font-size: 13px; font-weight: 700; color: var(--blue-900);">
          <?= htmlspecialchars($admission['room_name']) ?> (Bed <?= htmlspecialchars($admission['bed_number']) ?>)
        </div>
      </div>
      <div style="padding: 10px 14px; background: #fff; border: 1px solid var(--blue-200); border-radius: 6px;">
        <div style="font-size: 11px; color: var(--muted);">Lama Rawat (LOS)</div>
        <div style="font-size: 13px; font-weight: 800; color: #0284c7;">
          <?= $daysStay ?> Hari
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card" style="max-width: 900px; padding: 24px;">
  <form method="POST" action="<?= base_url('index.php?c=inpatient&a=discharge&id=' . $admission['id']) ?>">
    
    <!-- 1. Diagnosis Rawat Inap (Kepatuhan Wajib ICD-10 KARS) -->
    <h3 style="font-size: 15px; font-weight: 700; color: var(--blue-900); margin: 0 0 16px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
      1. Penegakan Diagnosis Kepulangan
    </h3>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Diagnosis Awal Saat Masuk (ICD-10)
        </label>
        <input type="text" name="admission_diagnosis_icd10" list="icdOptions" class="form-control" placeholder="Contoh: A09, J18.9" value="<?= htmlspecialchars($postData['admission_diagnosis_icd10'] ?? ($summary['admission_diagnosis_icd10'] ?? '')) ?>">
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 700; color: #b91c1c; margin-bottom: 6px;">
          Diagnosis Akhir / Keluar (ICD-10) <span style="color: #dc2626;">* (Wajib)</span>
        </label>
        <input type="text" name="discharge_diagnosis_icd10" list="icdOptions" class="form-control" placeholder="Pilih kode ICD-10..." value="<?= htmlspecialchars($postData['discharge_diagnosis_icd10'] ?? ($summary['discharge_diagnosis_icd10'] ?? '')) ?>" required style="font-weight: 700; border-color: #cbd5e1;">
        <datalist id="icdOptions">
          <?php foreach ($icdList as $icd): ?>
            <option value="<?= htmlspecialchars($icd['code']) ?>"><?= htmlspecialchars($icd['description']) ?></option>
          <?php endforeach; ?>
        </datalist>
      </div>

      <div style="grid-column: span 2;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Tindakan Medis / Prosedur Operatif (ICD-9-CM)
        </label>
        <input type="text" name="procedures_icd9cm" class="form-control" placeholder="Contoh: 99.04 Transfusi darah, 38.93 Pemasangan kateter vena sentral" value="<?= htmlspecialchars($postData['procedures_icd9cm'] ?? ($summary['procedures_icd9cm'] ?? '')) ?>">
      </div>
    </div>

    <!-- 2. Ringkasan Perjalanan Penyakit & Terapi -->
    <h3 style="font-size: 15px; font-weight: 700; color: var(--blue-900); margin: 0 0 16px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
      2. Ringkasan Klinis &amp; Terapi Pulang
    </h3>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
      <div style="grid-column: span 2;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Ringkasan Perjalanan Penyakit Selama Dirawat (Hospital Course) <span style="color: #dc2626;">*</span>
        </label>
        <textarea name="hospital_course" class="form-control" rows="3" placeholder="Ringkasan anamnesis penting, respon terapi antibiotik, hasil lab/rad kunci..." required><?= htmlspecialchars($postData['hospital_course'] ?? ($summary['hospital_course'] ?? 'Kondisi klinis stabil dan menunjukkan perbaikan adekuat selama perawatan bangsal.')) ?></textarea>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Kondisi Saat Dipulangkan <span style="color: #dc2626;">*</span>
        </label>
        <select name="condition_at_discharge" class="form-control" required>
          <?php $curCond = $postData['condition_at_discharge'] ?? ($summary['condition_at_discharge'] ?? 'MEMBAIK'); ?>
          <option value="MEMBAIK" <?= $curCond === 'MEMBAIK' ? 'selected' : '' ?>>Membaik (Perbaikan Klinis)</option>
          <option value="SEMBUH" <?= $curCond === 'SEMBUH' ? 'selected' : '' ?>>Sembuh Total</option>
          <option value="BELUM_SEMBUH" <?= $curCond === 'BELUM_SEMBUH' ? 'selected' : '' ?>>Belum Sembuh (Perawatan Lanjut di Rumah)</option>
          <option value="RUJUK" <?= $curCond === 'RUJUK' ? 'selected' : '' ?>>Dirujuk ke RS Tingkat Lebih Tinggi</option>
          <option value="APS" <?= $curCond === 'APS' ? 'selected' : '' ?>>Pulang Atas Permintaan Sendiri (APS / Paksa)</option>
          <option value="MENINGGAL" <?= $curCond === 'MENINGGAL' ? 'selected' : '' ?>>Meninggal Dunia</option>
        </select>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Tanggal Rencana Kontrol Poliklinik
        </label>
        <input type="date" name="control_date" class="form-control" value="<?= htmlspecialchars($postData['control_date'] ?? ($summary['control_date'] ?? date('Y-m-d', strtotime('+7 days')))) ?>">
      </div>

      <div style="grid-column: span 2;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Terapi &amp; Obat-Obatan Pulang (Take-Home Medications)
        </label>
        <textarea name="take_home_medications" class="form-control" rows="2" placeholder="Daftar obat yang dibawa pulang pasien, dosis, dan aturan minum..."><?= htmlspecialchars($postData['take_home_medications'] ?? ($summary['take_home_medications'] ?? '')) ?></textarea>
      </div>

      <div style="grid-column: span 2;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Instruksi Khusus / Perawatan di Rumah / Tanda Bahaya (Edukasi Pasien)
        </label>
        <textarea name="discharge_instructions" class="form-control" rows="2" placeholder="Diet khusus, batasan aktivitas fisik, tanda bahaya yang mengharuskan segera ke IGD..."><?= htmlspecialchars($postData['discharge_instructions'] ?? ($summary['discharge_instructions'] ?? 'Istirahat cukup, hindari makanan berlemak tinggi, minum obat teratur, segera ke IGD jika demam tinggi atau sesak.')) ?></textarea>
      </div>
    </div>

    <!-- Opsi Konfirmasi Kepulangan -->
    <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
      <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
        <input type="checkbox" name="confirm_discharge_now" value="1" style="margin-top: 3px;" checked>
        <div>
          <b style="color: var(--blue-900); font-size: 13px;">Konfirmasi Kepulangan Pasien (Discharge) &amp; Bebaskan Tempat Tidur Sekarang</b>
          <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">
            Tempat tidur <b>Bed <?= htmlspecialchars($admission['bed_number']) ?></b> pada ruangan <b><?= htmlspecialchars($admission['room_name']) ?></b> akan otomatis berubah status menjadi <b>TERSEDIA</b> sehingga dapat digunakan pasien lain.
          </div>
        </div>
      </label>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border); padding-top: 16px;">
      <a href="<?= base_url('index.php?c=inpatient&a=index') ?>" class="btn btn-outline">Batal</a>
      <button type="submit" class="btn btn-primary" style="background: #0284c7; border-color: #0369a1;">
        <i data-lucide="check-circle-2"></i>
        <span>Sahkan Resume Medis &amp; Selesaikan Rawat Inap</span>
      </button>
    </div>
  </form>
</div>

