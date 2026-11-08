<?php
// app/views/outpatient/assessment.php - Asesmen Awal Keperawatan Poliklinik (SOP/SIMRS/005/2026)
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=outpatient&a=index') ?>" class="btn btn-outline" style="margin-bottom: 12px;">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Antrean Rawat Jalan</span>
  </a>

  <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
    <i data-lucide="clipboard-check" style="color: var(--blue-700); width: 22px; height: 22px;"></i>
    <span>Asesmen Awal Keperawatan &amp; Tanda Vital Poliklinik</span>
  </h1>
  <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
    SOP/SIMRS/005/2026: Verifikasi minimal 2 identitas pasien, skrining keluhan, tanda vital lengkap sebelum diteruskan ke Dokter Spesialis.
  </p>
</div>

<!-- Informasi Pasien & Verifikasi 2 Identitas -->
<div class="card" style="margin-bottom: 20px; padding: 16px 20px; background: var(--blue-50); border: 1px solid var(--blue-200);">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <span class="badge" style="background: var(--blue-600); color: #fff; margin-bottom: 6px;">
        Antrean <?= htmlspecialchars($visit['queue_number']) ?>
      </span>
      <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--blue-900);">
        <?= htmlspecialchars($visit['patient_name']) ?>
      </h2>
      <div style="font-size: 13px; color: var(--muted); margin-top: 4px; display: flex; gap: 12px; flex-wrap: wrap;">
        <span>No. RM: <b><?= htmlspecialchars($visit['no_rm']) ?></b></span>
        <span>•</span>
        <span>NIK: <?= htmlspecialchars($visit['nik']) ?></span>
        <span>•</span>
        <span>Tgl Lahir: <?= htmlspecialchars($visit['birth_date']) ?></span>
        <span>•</span>
        <span>Poli: <b><?= htmlspecialchars($visit['policlinic_name']) ?></b></span>
        <span>•</span>
        <span>Dokter: <?= htmlspecialchars($visit['doctor_name']) ?></span>
      </div>
    </div>
    <div>
      <span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 12px; padding: 6px 12px; border: 1px solid #bae6fd;">
        <i data-lucide="shield-check" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;"></i>
        Verifikasi 2 Identitas Pasien Wajib Sesuai
      </span>
    </div>
  </div>
</div>

<div class="card" style="padding: 24px; max-width: 900px;">
  <form method="POST" action="<?= base_url('index.php?c=outpatient&a=assessment&id=' . $visit['id']) ?>">
    
    <!-- 1. Skrining Keluhan & Kondisi Fisik -->
    <h3 style="font-size: 15px; font-weight: 700; color: var(--blue-900); margin: 0 0 16px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
      1. Pengkajian &amp; Kondisi Umum
    </h3>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
      <div style="grid-column: span 2;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Keluhan Utama Saat Ini <span style="color: #dc2626;">*</span>
        </label>
        <textarea name="chief_complaint" class="form-control" rows="2" placeholder="Keluhan utama yang dirasakan pasien..." required><?= htmlspecialchars($visit['notes'] ?? '') ?></textarea>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Kondisi Umum Pasien
        </label>
        <select name="general_condition" class="form-control">
          <option value="Baik">Tampak Baik / Tenang</option>
          <option value="Sedang" selected>Tampak Sakit Sedang</option>
          <option value="Berat">Tampak Sakit Berat / Lemah</option>
        </select>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Tingkat Kesadaran (GCS)
        </label>
        <select name="consciousness" class="form-control">
          <option value="COMPOS_MENTIS" selected>Compos Mentis (Sadar Penuh)</option>
          <option value="APATIS">Apatis (Acuh / Mengantuk)</option>
          <option value="SOMNOLEN">Somnolen (Mudah Tertidur)</option>
          <option value="SOPOR">Sopor (Respon Rangsang Kuat)</option>
          <option value="KOMA">Koma</option>
        </select>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Skrining Skala Nyeri (NRS 0 - 10)
        </label>
        <select name="pain_score" class="form-control">
          <option value="0">0 - Tidak Ada Nyeri</option>
          <option value="2">1-3 - Nyeri Ringan (Masih bisa beraktivitas)</option>
          <option value="5">4-6 - Nyeri Sedang (Mengganggu aktivitas)</option>
          <option value="8">7-9 - Nyeri Berat (Tidak bisa beraktivitas)</option>
          <option value="10">10 - Nyeri Paling Hebat / Tak Tertahankan</option>
        </select>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Skrining Resiko Jatuh (Morse Scale)
        </label>
        <select name="fall_risk_category" class="form-control">
          <option value="RENDAH" selected>Resiko Rendah (Skor 0 - 24)</option>
          <option value="SEDANG">Resiko Sedang (Skor 25 - 44)</option>
          <option value="TINGGI">Resiko Tinggi (Skor &ge; 45) [Pasang Pita Kuning]</option>
        </select>
      </div>
    </div>

    <!-- 2. Pengukuran Tanda-Tanda Vital (TTV) -->
    <h3 style="font-size: 15px; font-weight: 700; color: var(--blue-900); margin: 0 0 16px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
      2. Pengukuran Tanda-Tanda Vital (Vital Signs)
    </h3>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Tekanan Darah Sistolik (mmHg) <span style="color: #dc2626;">*</span>
        </label>
        <input type="number" name="systolic_bp" class="form-control" value="120" min="50" max="300" required>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Tekanan Darah Diastolik (mmHg) <span style="color: #dc2626;">*</span>
        </label>
        <input type="number" name="diastolic_bp" class="form-control" value="80" min="30" max="200" required>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Frekuensi Nadi (x/menit) <span style="color: #dc2626;">*</span>
        </label>
        <input type="number" name="pulse_rate" class="form-control" value="80" min="30" max="250" required>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Laju Pernapasan (x/menit) <span style="color: #dc2626;">*</span>
        </label>
        <input type="number" name="respiratory_rate" class="form-control" value="20" min="8" max="60" required>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Suhu Tubuh (&deg;C) <span style="color: #dc2626;">*</span>
        </label>
        <input type="number" step="0.1" name="temperature" class="form-control" value="36.5" min="30" max="45" required>
      </div>

      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Saturasi Oksigen SpO2 (%) <span style="color: #dc2626;">*</span>
        </label>
        <input type="number" name="spo2" class="form-control" value="98" min="50" max="100" required>
      </div>

      <div style="grid-column: span 3;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Catatan Tambahan Keperawatan
        </label>
        <input type="text" name="vital_notes" class="form-control" placeholder="Alergi obat, diet khusus, atau kondisi klinis khusus...">
      </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border); padding-top: 16px;">
      <a href="<?= base_url('index.php?c=outpatient&a=index') ?>" class="btn btn-outline">Batal</a>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="check"></i>
        <span>Simpan Asesmen &amp; Set Siap Diperiksa Dokter</span>
      </button>
    </div>
  </form>
</div>

