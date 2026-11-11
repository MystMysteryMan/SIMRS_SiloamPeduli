<?php
// app/views/emr/examine.php - Lembar Pemeriksaan EMR Terintegrasi
$isNurse = $isNurse ?? EmrModel::isNursingRole();
$doctorReadonly = $isNurse ? 'readonly aria-readonly="true"' : '';
$patAge = !empty($appointment['birth_date']) ? (date('Y') - date('Y', strtotime($appointment['birth_date']))) : '-';
?>

<div style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <div style="display: flex; align-items: center; gap: 8px;">
      <a href="<?= base_url('index.php?c=emr&a=index') ?>" class="btn btn-outline" style="height: 32px; padding: 0 10px; font-size: 12px;">
        <i data-lucide="arrow-left"></i>
        <span>Antrean</span>
      </a>
      <h1 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--blue-900);">
        Pemeriksaan Medis Rawat Jalan (EMR)
      </h1>
    </div>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Nomor Antrean: <b style="color: var(--blue-700);"><?= htmlspecialchars($appointment['queue_number']) ?></b> &bull; Poliklinik: <b><?= htmlspecialchars($appointment['policlinic_name']) ?></b> &bull; Dokter: <b><?= htmlspecialchars($appointment['doctor_name']) ?></b>
    </p>
  </div>
  <div>
    <a href="<?= base_url('index.php?c=emr&a=history&patient_id=' . $appointment['patient_id']) ?>" target="_blank" class="btn btn-outline" style="font-size: 13px;">
      <i data-lucide="history"></i>
      <span>Buka Riwayat Lengkap Pasien</span>
    </a>
  </div>
</div>

<!-- Banner Identitas Pasien -->
<div class="card" style="padding: 16px 20px; margin-bottom: 20px; background: linear-gradient(to right, #f8fafc, #ffffff); border-left: 4px solid var(--blue-600);">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 18px; font-weight: 700; color: var(--blue-950);">
          <?= htmlspecialchars($appointment['patient_name']) ?>
        </span>
        <span class="badge badge-primary font-mono" style="font-size: 12px;">
          No. RM: <?= htmlspecialchars($appointment['no_rm']) ?>
        </span>
        <span class="badge <?= ($appointment['insurance_type'] === 'BPJS') ? 'badge-success' : 'badge-secondary' ?>">
          <?= htmlspecialchars($appointment['insurance_type']) ?> 
          <?= !empty($appointment['insurance_number']) ? '(' . htmlspecialchars($appointment['insurance_number']) . ')' : '' ?>
        </span>
      </div>
      <div style="font-size: 13px; color: var(--slate-600); margin-top: 6px; display: flex; gap: 16px; flex-wrap: wrap;">
        <span><b>NIK:</b> <?= htmlspecialchars($appointment['nik']) ?></span>
        <span><b>JK/Usia:</b> <?= $appointment['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?> / <?= $patAge ?> tahun</span>
        <span><b>Gol. Darah:</b> <?= htmlspecialchars($appointment['blood_type'] ?? '-') ?></span>
        <span><b>Telepon:</b> <?= htmlspecialchars($appointment['phone_number'] ?? '-') ?></span>
      </div>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 600;">Status Pelayanan</div>
      <span class="badge badge-info" style="font-size: 12px; margin-top: 2px;">
        <?= htmlspecialchars($appointment['status']) ?>
      </span>
    </div>
  </div>

  <!-- Peringatan Alergi Pasien -->
  <?php if (!empty($appointment['allergy_notes']) || !empty($allergies)): ?>
    <div style="margin-top: 12px; padding: 8px 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; display: flex; align-items: center; gap: 8px; color: #991b1b; font-size: 13px;">
      <i data-lucide="alert-triangle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
      <div>
        <b>PERINGATAN ALERGI PASIEN:</b>
        <?= htmlspecialchars($appointment['allergy_notes'] ?? '') ?>
        <?php foreach ($allergies as $alg): ?>
          <span class="badge badge-danger" style="margin-left: 4px;">
            <?= htmlspecialchars($alg['allergen_name']) ?> (<?= htmlspecialchars($alg['severity']) ?>)
          </span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Layout Dua Kolom: Form Input Medis & Panel Riwayat Pasien -->
<div style="display: grid; grid-template-columns: 2.1fr 1fr; gap: 20px; align-items: flex-start;">

  <!-- KOLOM UTAMA: FORM EMR -->
  <div>
    <form method="POST" action="<?= base_url('index.php?c=emr&a=save') ?>" id="emrForm">
      <?= csrf_field() ?>
      <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
      <input type="hidden" name="patient_id" value="<?= $appointment['patient_id'] ?>">
      <input type="hidden" name="doctor_id" value="<?= $appointment['doctor_id'] ?>">

      <!-- BAGIAN 1: TANDA-TANDA VITAL & ANTROPOMETRI -->
      <div class="card" style="margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
          <i data-lucide="activity" style="color: var(--blue-600); width: 18px; height: 18px;"></i>
          <b style="color: var(--blue-900); font-size: 14.5px;">1. Tanda-Tanda Vital &amp; Antropometri</b>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 14px;">
          <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 12px;">Tekanan Darah (mmHg)</label>
            <input type="text" name="vital_bp" class="form-control" placeholder="Contoh: 120/80" 
                   value="<?= htmlspecialchars($record['vital_bp'] ?? '120/80') ?>">
          </div>
          <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 12px;">Suhu Tubuh (&deg;C)</label>
            <input type="number" step="0.1" name="vital_temp" class="form-control" placeholder="Contoh: 36.5" 
                   value="<?= htmlspecialchars($record['vital_temp'] ?? '36.5') ?>">
          </div>
          <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 12px;">Frekuensi Nadi (x/mnt)</label>
            <input type="number" name="vital_pulse" class="form-control" placeholder="Contoh: 80" 
                   value="<?= htmlspecialchars($record['vital_pulse'] ?? '80') ?>">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;">
          <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 12px;">Laju Pernapasan (x/mnt)</label>
            <input type="number" name="vital_rr" class="form-control" placeholder="Contoh: 18" 
                   value="<?= htmlspecialchars($record['vital_rr'] ?? '18') ?>">
          </div>
          <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 12px;">Berat Badan (kg)</label>
            <input type="number" step="0.1" id="weight_kg" name="weight_kg" class="form-control" placeholder="Contoh: 65" 
                   value="<?= htmlspecialchars($record['weight_kg'] ?? '') ?>" oninput="calculateBMI()">
          </div>
          <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 12px;">Tinggi Badan (cm)</label>
            <input type="number" step="0.1" id="height_cm" name="height_cm" class="form-control" placeholder="Contoh: 170" 
                   value="<?= htmlspecialchars($record['height_cm'] ?? '') ?>" oninput="calculateBMI()">
          </div>
        </div>

        <div style="margin-top: 12px; display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 12px; color: var(--muted);">Indeks Massa Tubuh (BMI):</span>
          <span id="bmiDisplay" class="badge badge-secondary font-mono" style="font-size: 12px;">
            <?= !empty($record['bmi']) ? $record['bmi'] . ' kg/m&sup2;' : '-' ?>
          </span>
        </div>
      </div>

      <!-- BAGIAN 2: SOAP (SUBJEKTIF, OBJEKTIF, ASESMEN, PLAN) -->
      <div class="card" style="margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
          <i data-lucide="clipboard-list" style="color: var(--blue-600); width: 18px; height: 18px;"></i>
          <b style="color: var(--blue-900); font-size: 14.5px;">2. Catatan Medis SOAP</b>
          <?php if ($isNurse): ?><span style="font-size: 12px; color: var(--muted);">Hanya dapat diubah dokter</span><?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label" style="font-size: 12px;">
            <b>S - Subjektif (Anamnesis / Keluhan Utama):</b>
          </label>
          <textarea <?= $doctorReadonly ?> name="subjective" class="form-control" rows="3" placeholder="Pasien mengeluh demam sejak 3 hari yang lalu disertai batuk berdahak dan badan lemas..."><?= htmlspecialchars($record['subjective'] ?? $appointment['chief_complaint'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label class="form-label" style="font-size: 12px;">
            <b>O - Objektif (Hasil Pemeriksaan Fisik &amp; Penunjang):</b>
          </label>
          <textarea <?= $doctorReadonly ?> name="objective" class="form-control" rows="3" placeholder="Keadaan umum: Tampak sakit sedang, compos mentis. Suara napas vesikuler, tidak ada ronki maupun wheezing..."><?= htmlspecialchars($record['objective'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label class="form-label" style="font-size: 12px;">
            <b>A - Asesmen Klinis Dokter:</b>
          </label>
          <textarea <?= $doctorReadonly ?> name="assessment" class="form-control" rows="2" placeholder="Kecurigaan infeksi saluran pernapasan atas akut dengan demam..."><?= htmlspecialchars($record['assessment'] ?? '') ?></textarea>
        </div>

        <div class="form-group" style="margin: 0;">
          <label class="form-label" style="font-size: 12px;">
            <b>P - Plan (Rencana Tatalaksana, Resep, &amp; Edukasi):</b>
          </label>
          <textarea <?= $doctorReadonly ?> name="plan" class="form-control" rows="3" placeholder="Terapi simtomatik, anjuran hidrasi adekuat, istirahat cukup, edukasi tanda bahaya..."><?= htmlspecialchars($record['plan'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- BAGIAN 3: DIAGNOSIS ICD-10 -->
      <div class="card" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <i data-lucide="book-open" style="color: var(--blue-600); width: 18px; height: 18px;"></i>
            <b style="color: var(--blue-900); font-size: 14.5px;">3. Kodifikasi Diagnosa ICD-10</b>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 180px 1fr; gap: 14px; margin-bottom: 14px;">
          <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 12px;">Kode ICD-10 <?php if (!$isNurse): ?><span style="color: var(--red-600);">*</span><?php endif; ?></label>
            <input type="text" id="icd10_code" name="icd10_code" list="icdListOptions" class="form-control font-mono" 
                   placeholder="Contoh: J06.9" <?= $isNurse ? $doctorReadonly : 'required' ?> value="<?= htmlspecialchars($record['icd10_code'] ?? '') ?>"
                   onchange="autoFillDiagnosisName(this.value)">
            <datalist id="icdListOptions">
              <?php foreach ($icdList as $icd): ?>
                <option value="<?= htmlspecialchars($icd['code']) ?>"><?= htmlspecialchars($icd['description_id']) ?></option>
              <?php endforeach; ?>
            </datalist>
          </div>

          <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 12px;">Nama Diagnosis Klinis</label>
            <input type="text" <?= $doctorReadonly ?> id="diagnosis_name" name="diagnosis_name" class="form-control"
                   placeholder="Nama diagnosis otomatis terisi sesuai kode ICD-10" 
                   value="<?= htmlspecialchars($record['diagnosis_name'] ?? '') ?>">
          </div>
        </div>

        <?php if (!$isNurse): ?>
        <!-- Pilihan Cepat ICD-10 -->
        <div style="margin-top: 8px;">
          <span style="font-size: 11.5px; color: var(--muted); margin-bottom: 4px; display: block;">Diagnosa Umum Sering Digunakan:</span>
          <div style="display: flex; gap: 6px; flex-wrap: wrap;">
            <?php foreach (array_slice($icdList, 0, 6) as $icd): ?>
              <button type="button" class="badge badge-secondary" style="cursor: pointer; border: 1px solid var(--border);" 
                      onclick="selectQuickIcd('<?= $icd['code'] ?>', '<?= addslashes($icd['description_id']) ?>')">
                <span class="font-mono"><?= $icd['code'] ?></span> - <?= htmlspecialchars($icd['description_id']) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <?php endif; ?>
        <div class="form-group" style="margin-top: 14px; margin-bottom: 0;">
          <label class="form-label" style="font-size: 12px;">Catatan Klinis Tambahan / Instruksi Khusus</label>
          <input type="text" name="clinical_notes" class="form-control" placeholder="Contoh: Rencana kontrol 5 hari lagi jika keluhan belum membaik" 
                 value="<?= htmlspecialchars($record['clinical_notes'] ?? '') ?>">
        </div>
      </div>

      <?php if (!$isNurse): ?>
      <!-- BAGIAN 4: ORDER PENUNJANG (INTEGRASI PENUNJANG) -->
      <div class="card" style="margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
          <i data-lucide="layers" style="color: var(--blue-600); width: 18px; height: 18px;"></i>
          <b style="color: var(--blue-900); font-size: 14.5px;">4. Integrasi Order Penunjang Medis (Plan)</b>
        </div>

        <!-- Order Laboratorium Accordion/Toggle -->
        <div style="border: 1px solid var(--border); border-radius: 8px; padding: 14px; margin-bottom: 14px; background: #fafafa;">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0; font-weight: 600; color: var(--blue-950);">
              <input type="checkbox" id="checkOrderLab" name="order_lab" value="1" onchange="toggleLabOrder(this.checked)">
              <i data-lucide="flask-conical" style="color: var(--blue-600); width: 18px; height: 18px;"></i>
              <span>Buat Permintaan Uji Laboratorium Sentral</span>
            </label>
            <span class="badge badge-secondary" id="labBadge">Tidak Aktif</span>
          </div>

          <div id="labOrderSection" style="display: none; margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--border);">
            <div style="margin-bottom: 10px; font-size: 12px; font-weight: 600; color: var(--slate-700);">Pilih Pemeriksaan Laboratorium:</div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 12px;">
              <?php foreach ($labTests as $lt): ?>
                <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 12.5px; background: #fff; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; cursor: pointer;">
                  <input type="checkbox" name="lab_tests[]" value="<?= $lt['id'] ?>" style="margin-top: 2px;">
                  <div>
                    <b><?= htmlspecialchars($lt['test_name']) ?></b>
                    <div style="font-size: 11px; color: var(--muted);">Tarif: Rp <?= number_format($lt['tariff'], 0, ',', '.') ?> &bull; Unit: <?= htmlspecialchars($lt['unit'] ?? '-') ?></div>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>

            <div style="display: grid; grid-template-columns: 140px 1fr; gap: 12px;">
              <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-size: 12px;">Prioritas Lab</label>
                <select name="lab_priority" class="form-control">
                  <option value="REGULER">REGULER</option>
                  <option value="CITO">CITO (Urgent)</option>
                </select>
              </div>
              <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-size: 12px;">Catatan Klinis untuk Analis</label>
                <input type="text" name="lab_notes" class="form-control" placeholder="Contoh: Pasien puasa sejak pkl 22.00">
              </div>
            </div>
          </div>
        </div>

        <!-- Order Radiologi Accordion/Toggle -->
        <div style="border: 1px solid var(--border); border-radius: 8px; padding: 14px; background: #fafafa;">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0; font-weight: 600; color: var(--blue-950);">
              <input type="checkbox" id="checkOrderRad" name="order_radiology" value="1" onchange="toggleRadOrder(this.checked)">
              <i data-lucide="film" style="color: var(--blue-600); width: 18px; height: 18px;"></i>
              <span>Buat Permintaan Pemeriksaan Radiologi</span>
            </label>
            <span class="badge badge-secondary" id="radBadge">Tidak Aktif</span>
          </div>

          <div id="radOrderSection" style="display: none; margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--border);">
            <div style="display: grid; grid-template-columns: 1fr 140px; gap: 12px; margin-bottom: 12px;">
              <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-size: 12px;">Jenis Modalitas / Tindakan Radiologi</label>
                <select name="rad_modality" class="form-control">
                  <option value="X-RAY Thorax AP/PA">Rontgen Thorax AP/PA</option>
                  <option value="X-RAY Abdomen 3 Posisi">Rontgen Abdomen 3 Posisi</option>
                  <option value="X-RAY Extremitas Bawah/Atas">Rontgen Ekstremitas</option>
                  <option value="CT-Scan Kepala Non-Kontras">CT-Scan Kepala Non-Kontras</option>
                  <option value="USG Abdomen Upper/Lower">USG Abdomen Lengkap</option>
                </select>
              </div>
              <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-size: 12px;">Prioritas</label>
                <select name="rad_priority" class="form-control">
                  <option value="REGULER">REGULER</option>
                  <option value="CITO">CITO (Urgent)</option>
                </select>
              </div>
            </div>

            <div class="form-group" style="margin: 0;">
              <label class="form-label" style="font-size: 12px;">Indikasi Klinis / Kecurigaan Radiologis</label>
              <input type="text" name="rad_indication" class="form-control" placeholder="Contoh: Batuk kronis > 2 minggu, evaluasi infiltrat paru / pneumonia">
            </div>
          </div>
        </div>
      </div>

      <?php endif; ?>
      <!-- BAGIAN 5: TAMBAH CATATAN ALERGI BARU (JIKA ADA) -->
      <div class="card" style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
          <i data-lucide="shield-alert" style="color: var(--red-600); width: 18px; height: 18px;"></i>
          <b style="color: var(--blue-900); font-size: 14.5px;"><?= $isNurse ? '4' : '5' ?>. Tambah Alergi Pasien (Jika Ada)</b>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 140px 1.5fr; gap: 12px;">
          <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 12px;">Nama Alergen (Obat/Makanan)</label>
            <input type="text" name="new_allergy" class="form-control" placeholder="Contoh: Amoxicillin / Paracetamol">
          </div>
          <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 12px;">Tingkat Keparahan</label>
            <select name="allergy_severity" class="form-control">
              <option value="SEDANG">SEDANG</option>
              <option value="RINGAN">RINGAN</option>
              <option value="BERAT">BERAT</option>
            </select>
          </div>
          <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 12px;">Reaksi Klinis</label>
            <input type="text" name="allergy_reaction" class="form-control" placeholder="Contoh: Ruam kulit gatal, urtikaria">
          </div>
        </div>
      </div>

      <!-- ACTION BUTTONS -->
      <div style="display: flex; gap: 12px; align-items: center;">
        <button type="submit" class="btn btn-primary" style="height: 44px; padding: 0 24px; font-size: 14px; font-weight: 600;">
          <i data-lucide="check-circle-2"></i>
          <span><?= $isNurse ? 'Simpan Catatan Perawat' : 'Simpan &amp; Selesaikan Pemeriksaan' ?></span>
        </button>
        <a href="<?= base_url('index.php?c=emr&a=index') ?>" class="btn btn-outline" style="height: 44px; padding: 0 18px;">
          <i data-lucide="x"></i>
          <span>Batal</span>
        </a>
      </div>
    </form>
  </div>

  <!-- KOLOM KANAN: RIWAYAT KUNJUNGAN PASIEN TERDAHULU (TIMELINE) -->
  <div>
    <div class="card" style="padding: 16px;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
        <div style="display: flex; align-items: center; gap: 6px;">
          <i data-lucide="history" style="color: var(--blue-600); width: 16px; height: 16px;"></i>
          <b style="color: var(--blue-900); font-size: 13.5px;">Riwayat Kunjungan Terdahulu</b>
        </div>
        <span class="badge badge-secondary"><?= count($history) ?> Kunjungan</span>
      </div>

      <?php if (empty($history)): ?>
        <div style="padding: 24px 10px; text-align: center; color: var(--muted); font-size: 12px;">
          <i data-lucide="file-text" style="width: 32px; height: 32px; stroke-width: 1.5; margin-bottom: 6px; display: inline-block;"></i>
          <div>Belum ada riwayat rekam medis terdahulu untuk pasien ini.</div>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 12px; max-height: 720px; overflow-y: auto;">
          <?php foreach ($history as $h): ?>
            <div style="border: 1px solid var(--border); border-radius: 6px; padding: 12px; background: #fff; font-size: 12px;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                <span style="font-weight: 600; color: var(--blue-950);">
                  <?= format_datetime_id($h['examined_at'] ?? $h['created_at']) ?>
                </span>
                <span class="badge badge-primary font-mono"><?= htmlspecialchars($h['icd10_code'] ?? '-') ?></span>
              </div>
              <div style="color: var(--blue-700); font-weight: 600;">
                <?= htmlspecialchars($h['diagnosis_name'] ?? 'Diagnosis Medis') ?>
              </div>
              <div style="color: var(--muted); font-size: 11px; margin-top: 2px;">
                Dokter: <?= htmlspecialchars($h['doctor_name'] ?? 'Dokter Poliklinik') ?>
              </div>
              <?php if (!empty($h['subjective'])): ?>
                <div style="margin-top: 6px; color: var(--slate-700); font-size: 11.5px; background: #f8fafc; padding: 6px; border-radius: 4px;">
                  <b>S:</b> <?= htmlspecialchars($h['subjective']) ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<script>
function calculateBMI() {
  const w = parseFloat(document.getElementById('weight_kg').value);
  const h = parseFloat(document.getElementById('height_cm').value);
  const display = document.getElementById('bmiDisplay');
  if (w > 0 && h > 0) {
    const hM = h / 100;
    const bmi = (w / (hM * hM)).toFixed(1);
    display.innerHTML = bmi + ' kg/m²';
  } else {
    display.innerHTML = '-';
  }
}

function selectQuickIcd(code, name) {
  if (document.getElementById('icd10_code').readOnly) return;
  document.getElementById('icd10_code').value = code;
  document.getElementById('diagnosis_name').value = name;
}

function autoFillDiagnosisName(code) {
  if (document.getElementById('icd10_code').readOnly) return;
  const datalist = document.getElementById('icdListOptions');
  const options = datalist.querySelectorAll('option');
  for (let opt of options) {
    if (opt.value.trim().toUpperCase() === code.trim().toUpperCase()) {
      document.getElementById('diagnosis_name').value = opt.text;
      break;
    }
  }
}

function toggleLabOrder(checked) {
  document.getElementById('labOrderSection').style.display = checked ? 'block' : 'none';
  const badge = document.getElementById('labBadge');
  badge.textContent = checked ? 'Aktif' : 'Tidak Aktif';
  badge.className = checked ? 'badge badge-primary' : 'badge badge-secondary';
}

function toggleRadOrder(checked) {
  document.getElementById('radOrderSection').style.display = checked ? 'block' : 'none';
  const badge = document.getElementById('radBadge');
  badge.textContent = checked ? 'Aktif' : 'Tidak Aktif';
  badge.className = checked ? 'badge badge-primary' : 'badge badge-secondary';
}

// Require a diagnosis code before saving.
document.getElementById('emrForm').addEventListener('submit', function(e) {
  const icd = document.getElementById('icd10_code').value.trim();
  if (document.getElementById('icd10_code').required && !icd) {
    e.preventDefault();
    alert('Kode ICD-10 Wajib Diisi!');
    document.getElementById('icd10_code').focus();
  }
});
</script>
