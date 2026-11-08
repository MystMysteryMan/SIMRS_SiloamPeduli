<?php
// app/views/outpatient/create.php - Form Pendaftaran Kunjungan Rawat Jalan
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=outpatient&a=index') ?>" class="btn btn-outline" style="margin-bottom: 12px;">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Antrean Rawat Jalan</span>
  </a>

  <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
    <i data-lucide="user-plus" style="color: var(--blue-700); width: 22px; height: 22px;"></i>
    <span>Pendaftaran Kunjungan Rawat Jalan (Poliklinik)</span>
  </h1>
  <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
    Pilih pasien, poliklinik spesialis tujuan, dan dokter penanggung jawab untuk menerbitkan nomor antrean kunjungan.
  </p>
</div>

<div class="card" style="max-width: 800px; padding: 24px;">
  <form method="POST" action="<?= base_url('index.php?c=outpatient&a=store') ?>">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
      
      <!-- Pilih Pasien -->
      <div style="grid-column: span 2;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Pasien Terdaftar <span style="color: #dc2626;">*</span>
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
            <a href="<?= base_url('index.php?c=patient&a=index') ?>" class="btn btn-outline" target="_blank" title="Buka Data Pasien">
              <i data-lucide="search"></i>
              <span>Cari Pasien</span>
            </a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Tanggal Kunjungan -->
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Tanggal Kunjungan <span style="color: #dc2626;">*</span>
        </label>
        <input type="date" name="visit_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
      </div>

      <!-- Poliklinik Tujuan -->
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Poliklinik Tujuan <span style="color: #dc2626;">*</span>
        </label>
        <select name="policlinic_id" class="form-control" required>
          <option value="">-- Pilih Poliklinik --</option>
          <?php foreach ($policlinics as $poli): ?>
            <option value="<?= $poli['id'] ?>">
              <?= htmlspecialchars($poli['name']) ?> (Prefix <?= htmlspecialchars($poli['queue_prefix']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Dokter Spesialis -->
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Dokter Penanggung Jawab <span style="color: #dc2626;">*</span>
        </label>
        <select name="doctor_id" class="form-control" required>
          <option value="">-- Pilih Dokter --</option>
          <?php foreach ($doctors as $doc): ?>
            <option value="<?= $doc['id'] ?>">
              <?= htmlspecialchars($doc['full_name']) ?> (<?= htmlspecialchars($doc['specialization']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Tipe Penjamin / Pembayaran -->
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Jenis Penjamin <span style="color: #dc2626;">*</span>
        </label>
        <select name="payment_type" class="form-control" required>
          <option value="UMUM">UMUM (Biaya Mandiri)</option>
          <option value="BPJS">BPJS Kesehatan (JKN)</option>
          <option value="ASURANSI">Asuransi Swasta / Korporat</option>
        </select>
      </div>

      <!-- Nomor Kartu Asuransi / BPJS -->
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Nomor Kartu BPJS / Polis Asuransi
        </label>
        <input type="text" name="insurance_card_no" class="form-control" placeholder="Opsional jika bukan UMUM">
      </div>

      <!-- Nomor Rujukan Faskes -->
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Nomor Surat Rujukan Faskes 1
        </label>
        <input type="text" name="referral_no" class="form-control" placeholder="Contoh: 0115R0011126P0001">
      </div>

      <!-- Catatan Keluhan / Pengantar -->
      <div style="grid-column: span 2;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Catatan / Alasan Kunjungan
        </label>
        <textarea name="notes" class="form-control" rows="3" placeholder="Keluhan utama atau catatan pengantar rujukan..."></textarea>
      </div>

    </div>

    <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border); padding-top: 16px;">
      <a href="<?= base_url('index.php?c=outpatient&a=index') ?>" class="btn btn-outline">Batal</a>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="check-circle-2"></i>
        <span>Daftarkan &amp; Terbitkan Nomor Antrean</span>
      </button>
    </div>
  </form>
</div>

