<?php
// app/views/inpatient/admit.php - Form Pendaftaran Admisi Rawat Inap & Alokasi Tempat Tidur
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=inpatient&a=index') ?>" class="btn btn-outline" style="margin-bottom: 12px;">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Rawat Inap</span>
  </a>

  <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
    <i data-lucide="user-plus" style="color: var(--blue-700); width: 22px; height: 22px;"></i>
    <span>Pendaftaran Admisi Rawat Inap &amp; Alokasi Tempat Tidur</span>
  </h1>
  <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
    Verifikasi SPRI, dokter DPJP, dan pilih tempat tidur yang berstatus Available untuk membuat episode opname baru.
  </p>
</div>

<div class="card" style="max-width: 800px; padding: 24px;">
  <form method="POST" action="<?= base_url('index.php?c=inpatient&a=storeAdmission') ?>">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
      
      <!-- Nomor Registrasi Rawat Inap -->
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Nomor Registrasi Rawat Inap (Otomatis)
        </label>
        <input type="text" name="admission_number" class="form-control" value="<?= htmlspecialchars($admNumber) ?>" readonly style="font-weight: 700; background: #f8fafc;">
      </div>

      <!-- Asal Rujukan / Perintah Mondok -->
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Asal Perintah Rawat Inap (SPRI) <span style="color: #dc2626;">*</span>
        </label>
        <select name="source_type" class="form-control" required>
          <option value="POLI">Poliklinik Rawat Jalan</option>
          <option value="IGD">Instalasi Gawat Darurat (IGD)</option>
          <option value="RUJUKAN_LUAR">Rujukan Faskes Luar / Dokter Luar</option>
        </select>
      </div>

      <!-- Pasien -->
      <div style="grid-column: span 2;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Pasien Rawat Inap <span style="color: #dc2626;">*</span>
        </label>
        <?php if ($patient): ?>
          <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
          <div style="padding: 12px 16px; background: var(--blue-50); border: 1px solid var(--blue-200); border-radius: 6px;">
            <div style="font-weight: 700; color: var(--blue-900);"><?= htmlspecialchars($patient['full_name']) ?></div>
            <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
              No. RM: <b><?= htmlspecialchars($patient['no_rm']) ?></b> | NIK: <?= htmlspecialchars($patient['nik']) ?> | Penjamin: <?= htmlspecialchars($patient['insurance_type']) ?>
            </div>
          </div>
        <?php else: ?>
          <div style="display: flex; gap: 8px;">
            <input type="number" name="patient_id" class="form-control" placeholder="Masukkan ID Pasien (atau cari via menu Pasien)" required>
            <a href="<?= base_url('index.php?c=patient&a=index') ?>" class="btn btn-outline" target="_blank">
              <i data-lucide="search"></i>
              <span>Cari Pasien</span>
            </a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Dokter Penanggung Jawab Pelayanan (DPJP) -->
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Dokter DPJP Rawat Inap <span style="color: #dc2626;">*</span>
        </label>
        <select name="doctor_id" class="form-control" required>
          <option value="">-- Pilih Dokter DPJP --</option>
          <?php foreach ($doctors as $doc): ?>
            <option value="<?= $doc['id'] ?>">
              <?= htmlspecialchars($doc['full_name']) ?> (<?= htmlspecialchars($doc['specialization']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Penjamin / Asuransi -->
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Jenis Penjamin Pasien <span style="color: #dc2626;">*</span>
        </label>
        <select name="insurance_type" class="form-control" required>
          <option value="UMUM">UMUM (Pribadi)</option>
          <option value="BPJS">BPJS Kesehatan</option>
          <option value="ASURANSI">Asuransi Swasta / Perusahaan</option>
        </select>
      </div>

      <!-- Alokasi Kamar & Bed (Wajib Status Available) -->
      <div style="grid-column: span 2;">
        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--blue-900); margin-bottom: 6px;">
          Alokasi Tempat Tidur (Bed) yang Tersedia <span style="color: #dc2626;">*</span>
        </label>
        <?php if (empty($availableBeds)): ?>
          <div style="padding: 16px; background: #fee2e2; border: 1px solid #fecaca; border-radius: 6px; color: #b91c1c; font-size: 13px;">
            <i data-lucide="alert-circle" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle;"></i>
            Saat ini tidak ada tempat tidur yang berstatus TERSEDIA di seluruh ruangan. Silakan tambahkan bed di Master Data atau tunggu proses discharge.
          </div>
        <?php else: ?>
          <select name="bed_id" class="form-control" required style="font-weight: 600;">
            <option value="">-- Pilih Kamar &amp; Tempat Tidur Tersedia --</option>
            <?php foreach ($availableBeds as $bed): ?>
              <option value="<?= $bed['id'] ?>">
                <?= htmlspecialchars($bed['room_name']) ?> (<?= htmlspecialchars($bed['room_code']) ?>) • Bed <?= htmlspecialchars($bed['bed_number']) ?> • <?= htmlspecialchars(str_replace('_', ' ', $bed['class_type'])) ?> • Rp <?= number_format($bed['daily_rate'], 0, ',', '.') ?>/hari
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>

      <!-- Alasan Masuk Rawat Inap -->
      <div style="grid-column: span 2;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Indikasi / Alasan Rawat Inap <span style="color: #dc2626;">*</span>
        </label>
        <textarea name="admission_reason" class="form-control" rows="3" placeholder="Indikasi medis opname, hasil pemeriksaan penunjang, atau diagnosis kerja awal..." required></textarea>
      </div>

    </div>

    <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border); padding-top: 16px;">
      <a href="<?= base_url('index.php?c=inpatient&a=index') ?>" class="btn btn-outline">Batal</a>
      <button type="submit" class="btn btn-primary" <?= empty($availableBeds) ? 'disabled' : '' ?>>
        <i data-lucide="check"></i>
        <span>Proses Admisi &amp; Alokasikan Tempat Tidur</span>
      </button>
    </div>
  </form>
</div>

