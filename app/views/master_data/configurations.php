<?php
// app/views/master_data/configurations.php - Konfigurasi Sistem
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=master&a=index') ?>" class="btn btn-outline btn-sm">
    <i data-lucide="arrow-left" class="lucide-sm"></i> Kembali ke Pusat Master Data
  </a>
</div>

<div class="card" style="max-width: 720px; margin: 0 auto;">
  <div class="card-header">
    <div>
      <h3 style="display: flex; align-items: center; gap: 8px;">
        <i data-lucide="settings" class="lucide-sm" style="color: var(--blue-700);"></i>
        Konfigurasi Sistem & Parameter Operasional
      </h3>
      <p>Pengaturan global yang mempengaruhi alur kerja pelayanan SIMRS</p>
    </div>
    <span class="badge badge-primary">Sistem SIMRS</span>
  </div>

  <form method="POST" action="<?= base_url('index.php?c=master&a=configurations') ?>">
    <div class="form-group">
      <label class="form-label" for="hospital_name">Nama Resmi Rumah Sakit</label>
      <input type="text" id="hospital_name" name="configs[hospital_name]" class="form-control" 
             value="<?= htmlspecialchars($configurations['hospital_name']['config_value'] ?? 'Siloam Peduli') ?>" required>
      <small style="color: var(--muted); font-size: 11px;">Tercetak pada Kartu Berobat Pasien dan Tiket Antrean Poliklinik</small>
    </div>

    <div class="form-group">
      <label class="form-label" for="hospital_address">Alamat & Kontak Rumah Sakit</label>
      <input type="text" id="hospital_address" name="configs[hospital_address]" class="form-control" 
             value="<?= htmlspecialchars($configurations['hospital_address']['config_value'] ?? 'Jl. Garnisun No. 2, Semanggi, Jakarta Selatan') ?>" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="rm_format">Format Penomoran Rekam Medis (No. RM)</label>
      <input type="text" id="rm_format" name="configs[rm_format]" class="form-control" 
             value="<?= htmlspecialchars($configurations['rm_format']['config_value'] ?? 'RM-YYYYMM-XXXX') ?>" required>
      <small style="color: var(--muted); font-size: 11px;">Format standar auto-increment No. RM (Contoh hasil: RM-202611-0001)</small>
    </div>

    <div class="grid-2">
      <div class="form-group">
        <label class="form-label" for="default_quota">Default Kuota Pasien per Dokter</label>
        <input type="number" id="default_quota" name="configs[default_quota_per_doctor]" class="form-control" 
               value="<?= htmlspecialchars($configurations['default_quota_per_doctor']['config_value'] ?? '20') ?>" min="1" max="100" required>
        <small style="color: var(--muted); font-size: 11px;">Batas antrean harian jika jadwal tidak menspesifikasikan kuota</small>
      </div>

      <div class="form-group">
        <label class="form-label" for="registration_hours">Jam Buka Pendaftaran</label>
        <input type="text" id="registration_hours" name="configs[registration_opening_hours]" class="form-control" 
               value="<?= htmlspecialchars($configurations['registration_opening_hours']['config_value'] ?? '07:00 - 15:00 WIB') ?>" required>
      </div>
    </div>

    <div style="margin-top: 28px; display: flex; justify-content: flex-end; gap: 12px;">
      <a href="<?= base_url('index.php?c=master&a=index') ?>" class="btn btn-outline">Batal</a>
      <button type="submit" class="btn btn-primary" style="min-width: 160px;">
        <i data-lucide="save" class="lucide-sm"></i> Simpan Perubahan
      </button>
    </div>
  </form>
</div>

