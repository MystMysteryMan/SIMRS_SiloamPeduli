<?php
// app/views/patient_registration/create.php - Pendaftaran Pasien Baru & Penerbitan No. RM (SOP/SIMRS/002/2026)
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=patient&a=index') ?>" class="btn btn-outline btn-sm">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Pencarian Pasien</span>
  </a>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
  <div class="card-header">
    <div>
      <h3>Pendaftaran Pasien Baru</h3>
      <p>Nomor Rekam Medis (No. RM) akan digenerate otomatis secara berurutan oleh sistem</p>
    </div>
    <span class="badge badge-primary">
      <i data-lucide="hash"></i>
      <span>No. RM Otomatis</span>
    </span>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
      <i data-lucide="alert-triangle"></i>
      <div><?= htmlspecialchars($error) ?></div>
    </div>
  <?php endif; ?>

  <?php $src = $_GET['source'] ?? $_POST['source'] ?? ''; ?>
  <?php if ($src === 'igd'): ?>
    <div class="alert alert-warning mb-3">
      <i data-lucide="siren" style="color: #dc2626;"></i>
      <div><b>Registrasi Pasien Gawat Darurat (Alur IGD):</b> Setelah formulir disimpan, sistem akan secara otomatis membuat No. RM baru dan kembali ke formulir Triase IGD.</div>
    </div>
  <?php elseif ($src === 'billing'): ?>
    <div class="alert alert-info mb-3">
      <i data-lucide="receipt"></i>
      <div><b>Registrasi Pasien Unit Kasir &amp; Pembayaran:</b> Pendaftaran pasien baru untuk penerbitan No. RM dan deposit tagihan.</div>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?= base_url('index.php?c=patient&a=create') ?>">
    <input type="hidden" name="source" value="<?= htmlspecialchars($src) ?>">
    <h4 style="margin: 0 0 14px; font-size: 14px; color: var(--blue-900); border-bottom: 1px solid var(--line); padding-bottom: 6px;">
      1. Identitas Kependudukan &amp; Pribadi
    </h4>

    <div class="grid-2">
      <div class="form-group">
        <label class="form-label" for="nik">Nomor Induk Kependudukan (NIK) *</label>
        <input type="text" id="nik" name="nik" class="form-control" placeholder="Tepat 16 digit angka KTP/KK" maxlength="16" pattern="[0-9]{16}" value="<?= htmlspecialchars($formData['nik'] ?? '') ?>" required autofocus>
        <small style="color: var(--muted); font-size: 11px;">Wajib 16 digit angka valid sesuai KTP/Dukcapil</small>
      </div>

      <div class="form-group">
        <label class="form-label" for="name">Nama Lengkap Pasien *</label>
        <input type="text" id="name" name="name" class="form-control" placeholder="Nama sesuai identitas resmi" value="<?= htmlspecialchars($formData['name'] ?? '') ?>" required>
      </div>
    </div>

    <div class="grid-3">
      <div class="form-group">
        <label class="form-label" for="gender">Jenis Kelamin *</label>
        <select id="gender" name="gender" class="form-control" required>
          <option value="">-- Pilih --</option>
          <option value="L" <?= (($formData['gender'] ?? '') === 'L') ? 'selected' : '' ?>>Laki-laki</option>
          <option value="P" <?= (($formData['gender'] ?? '') === 'P') ? 'selected' : '' ?>>Perempuan</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="birth_date">Tanggal Lahir *</label>
        <input type="date" id="birth_date" name="birth_date" class="form-control" value="<?= htmlspecialchars($formData['birth_date'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="blood_type">Golongan Darah</label>
        <select id="blood_type" name="blood_type" class="form-control">
          <option value="">-</option>
          <option value="A" <?= (($formData['blood_type'] ?? '') === 'A') ? 'selected' : '' ?>>A</option>
          <option value="B" <?= (($formData['blood_type'] ?? '') === 'B') ? 'selected' : '' ?>>B</option>
          <option value="AB" <?= (($formData['blood_type'] ?? '') === 'AB') ? 'selected' : '' ?>>AB</option>
          <option value="O" <?= (($formData['blood_type'] ?? '') === 'O') ? 'selected' : '' ?>>O</option>
        </select>
      </div>
    </div>

    <div class="grid-2">
      <div class="form-group">
        <label class="form-label" for="phone">Nomor Handphone / WhatsApp *</label>
        <input type="tel" id="phone" name="phone" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($formData['phone'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="address">Alamat Tempat Tinggal *</label>
        <input type="text" id="address" name="address" class="form-control" placeholder="Jalan, RT/RW, Kelurahan, Kota" value="<?= htmlspecialchars($formData['address'] ?? '') ?>" required>
      </div>
    </div>

    <h4 style="margin: 20px 0 14px; font-size: 14px; color: var(--blue-900); border-bottom: 1px solid var(--line); padding-bottom: 6px;">
      2. Penjamin &amp; Asuransi Kesehatan
    </h4>

    <div class="grid-2">
      <div class="form-group">
        <label class="form-label" for="insurance_type">Jenis Penjamin *</label>
        <select id="insurance_type" name="insurance_type" class="form-control">
          <option value="Umum" <?= (($formData['insurance_type'] ?? 'Umum') === 'Umum') ? 'selected' : '' ?>>Umum / Bayar Mandiri</option>
          <option value="BPJS" <?= (($formData['insurance_type'] ?? '') === 'BPJS') ? 'selected' : '' ?>>BPJS Kesehatan</option>
          <option value="Asuransi Swasta" <?= (($formData['insurance_type'] ?? '') === 'Asuransi Swasta') ? 'selected' : '' ?>>Asuransi Swasta / Korporasi</option>
        </select>
      </div>

      <div class="form-group" id="insuranceNumGroup">
        <label class="form-label" for="insurance_number">Nomor Kartu BPJS / Polis Asuransi</label>
        <input type="text" id="insurance_number" name="insurance_number" class="form-control" placeholder="Nomor kartu penjamin" value="<?= htmlspecialchars($formData['insurance_number'] ?? '') ?>">
      </div>
    </div>

    <h4 style="margin: 20px 0 14px; font-size: 14px; color: var(--blue-900); border-bottom: 1px solid var(--line); padding-bottom: 6px;">
      3. Kontak Darurat (Keluarga Terdekat)
    </h4>

    <div class="grid-2">
      <div class="form-group">
        <label class="form-label" for="emergency_contact_name">Nama Kontak Darurat</label>
        <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="form-control" placeholder="Nama suami/istri/orang tua" value="<?= htmlspecialchars($formData['emergency_contact_name'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="emergency_contact_phone">No. Telp Kontak Darurat</label>
        <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" class="form-control" placeholder="Nomor kontak darurat" value="<?= htmlspecialchars($formData['emergency_contact_phone'] ?? '') ?>">
      </div>
    </div>

    <div style="margin-top: 28px; display: flex; justify-content: flex-end; gap: 12px;">
      <a href="<?= base_url('index.php?c=patient&a=index') ?>" class="btn btn-outline">Batal</a>
      <button type="submit" class="btn btn-primary" style="min-width: 170px;">
        <i data-lucide="save"></i>
        <span>Daftarkan &amp; Cetak Kartu</span>
      </button>
    </div>
  </form>
</div>
