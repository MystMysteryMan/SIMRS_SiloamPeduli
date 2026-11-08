<?php
// app/views/patient_registration/edit.php - Edit Data Pasien (Lucide Icons)
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
      <h3>Ubah Identitas Pasien: <?= htmlspecialchars($patient['no_rm']) ?></h3>
      <p>Perbarui informasi kependudukan, penjamin, atau nomor kontak darurat pasien</p>
    </div>
    <span class="badge badge-primary"><?= htmlspecialchars($patient['no_rm']) ?></span>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
      <i data-lucide="alert-triangle"></i>
      <div><?= htmlspecialchars($error) ?></div>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?= base_url('index.php?c=patient&a=edit&id=' . $patient['id']) ?>">
    <h4 style="margin: 0 0 14px; font-size: 14px; color: var(--blue-900); border-bottom: 1px solid var(--line); padding-bottom: 6px;">
      1. Identitas Kependudukan &amp; Pribadi
    </h4>

    <div class="grid-2">
      <div class="form-group">
        <label class="form-label" for="nik">Nomor Induk Kependudukan (NIK) *</label>
        <input type="text" id="nik" name="nik" class="form-control" maxlength="16" pattern="[0-9]{16}" value="<?= htmlspecialchars($patient['nik']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="name">Nama Lengkap Pasien *</label>
        <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($patient['name']) ?>" required>
      </div>
    </div>

    <div class="grid-3">
      <div class="form-group">
        <label class="form-label" for="gender">Jenis Kelamin *</label>
        <select id="gender" name="gender" class="form-control" required>
          <option value="L" <?= $patient['gender'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
          <option value="P" <?= $patient['gender'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="birth_date">Tanggal Lahir *</label>
        <input type="date" id="birth_date" name="birth_date" class="form-control" value="<?= htmlspecialchars($patient['birth_date']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="blood_type">Golongan Darah</label>
        <select id="blood_type" name="blood_type" class="form-control">
          <option value="">-</option>
          <option value="A" <?= $patient['blood_type'] === 'A' ? 'selected' : '' ?>>A</option>
          <option value="B" <?= $patient['blood_type'] === 'B' ? 'selected' : '' ?>>B</option>
          <option value="AB" <?= $patient['blood_type'] === 'AB' ? 'selected' : '' ?>>AB</option>
          <option value="O" <?= $patient['blood_type'] === 'O' ? 'selected' : '' ?>>O</option>
        </select>
      </div>
    </div>

    <div class="grid-2">
      <div class="form-group">
        <label class="form-label" for="phone">Nomor Handphone / WhatsApp *</label>
        <input type="tel" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($patient['phone']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="address">Alamat Tempat Tinggal *</label>
        <input type="text" id="address" name="address" class="form-control" value="<?= htmlspecialchars($patient['address']) ?>" required>
      </div>
    </div>

    <h4 style="margin: 20px 0 14px; font-size: 14px; color: var(--blue-900); border-bottom: 1px solid var(--line); padding-bottom: 6px;">
      2. Penjamin &amp; Asuransi Kesehatan
    </h4>

    <div class="grid-2">
      <div class="form-group">
        <label class="form-label" for="insurance_type">Jenis Penjamin *</label>
        <select id="insurance_type" name="insurance_type" class="form-control">
          <option value="UMUM" <?= strtoupper($patient['insurance_type']) === 'UMUM' ? 'selected' : '' ?>>Umum / Bayar Mandiri</option>
          <option value="BPJS" <?= strtoupper($patient['insurance_type']) === 'BPJS' ? 'selected' : '' ?>>BPJS Kesehatan</option>
          <option value="ASURANSI" <?= strtoupper($patient['insurance_type']) === 'ASURANSI' ? 'selected' : '' ?>>Asuransi Swasta / Korporasi</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="insurance_number">Nomor Kartu BPJS / Polis Asuransi</label>
        <input type="text" id="insurance_number" name="insurance_number" class="form-control" value="<?= htmlspecialchars($patient['insurance_number'] ?? '') ?>">
      </div>
    </div>

    <h4 style="margin: 20px 0 14px; font-size: 14px; color: var(--blue-900); border-bottom: 1px solid var(--line); padding-bottom: 6px;">
      3. Kontak Darurat
    </h4>

    <div class="grid-2">
      <div class="form-group">
        <label class="form-label" for="emergency_contact_name">Nama Kontak Darurat</label>
        <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="form-control" value="<?= htmlspecialchars($patient['emergency_contact_name'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="emergency_contact_phone">No. Telp Kontak Darurat</label>
        <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" class="form-control" value="<?= htmlspecialchars($patient['emergency_contact_phone'] ?? '') ?>">
      </div>
    </div>

    <div style="margin-top: 28px; display: flex; justify-content: flex-end; gap: 12px;">
      <a href="<?= base_url('index.php?c=patient&a=index') ?>" class="btn btn-outline">Batal</a>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="save"></i>
        <span>Perbarui Data Pasien</span>
      </button>
    </div>
  </form>
</div>
