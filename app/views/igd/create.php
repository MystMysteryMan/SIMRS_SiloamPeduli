<?php
// app/views/igd/create.php - Registrasi Cepat Pasien IGD (SOP/SIMRS/007/2026)
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="user-plus" style="color: #dc2626; width: 22px; height: 22px;"></i>
      <span>Registrasi Cepat Pasien Gawat Darurat (IGD)</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Pendaftaran kilat pasien darurat untuk mendapatkan nomor kunjungan IGD seketika tanpa menunda tindakan medis darurat.
    </p>
  </div>
  <div>
    <a href="<?= base_url('index.php?c=igd&a=index') ?>" class="btn btn-outline">
      <i data-lucide="arrow-left"></i>
      <span>Kembali ke IGD</span>
    </a>
  </div>
</div>

<form method="POST" action="<?= base_url('index.php?c=igd&a=store') ?>" id="igdRegForm">
  <?= csrf_field() ?>

  <div class="card" style="margin-bottom: 20px;">
    <h3 style="margin: 0 0 16px; font-size: 14px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="id-card" class="lucide-md" style="color: var(--blue-600);"></i>
      <span>Identifikasi Pasien IGD</span>
    </h3>

    <!-- Opsi Pasien Terdaftar vs Pasien Darurat Mr./Mrs. X -->
    <div style="margin-bottom: 18px; padding: 12px 16px; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-sm);">
      <label style="display: flex; align-items: center; gap: 10px; font-weight: 700; color: #92400e; cursor: pointer;">
        <input type="checkbox" name="is_anonymous" id="isAnonymous" value="1" onchange="toggleAnonymous(this.checked)" style="width: 18px; height: 18px;">
        <span>PASIEN DARURAT TANPA IDENTITAS / KOMA (MR./MRS. X)</span>
      </label>
      <div style="font-size: 11.5px; color: #b45309; margin-top: 4px; margin-left: 28px;">
        Centang opsi ini jika pasien tidak sadar, korban kecelakaan tanpa kartu identitas, atau tidak didampingi keluarga agar triase ESI dapat segera dilakukan.
      </div>
    </div>

    <!-- Pilihan Pasien Terdaftar -->
    <div id="sectionRegisteredPatient">
      <label class="form-label" style="font-weight: 600;">Cari / Pilih Pasien Terdaftar</label>
      <select name="patient_id" id="patientSelect" class="form-control">
        <option value="">-- Pilih Pasien Terdaftar di Rumah Sakit --</option>
        <?php foreach ($patients as $p): ?>
          <option value="<?= $p['id'] ?>" <?= (isset($_GET['patient_id']) && (int)$_GET['patient_id'] === (int)$p['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['no_rm']) ?> - <?= htmlspecialchars($p['full_name']) ?> (NIK: <?= htmlspecialchars($p['nik']) ?> &bull; <?= htmlspecialchars($p['insurance_type']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <div style="margin-top: 12px; padding: 10px 14px; background: #fff5f5; border: 1px dashed #fca5a5; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <div style="font-size: 12.5px; color: #991b1b;">
          <b>Pasien Belum Memiliki Nomor RM?</b> Pembuatan Nomor RM hanya dilakukan di Unit Registrasi dan Pembayaran.
        </div>
        <a href="<?= base_url('index.php?c=patient&a=create&source=igd') ?>" class="btn btn-outline" style="border-color: #dc2626; color: #dc2626; background: #fff; font-weight: 700; font-size: 12px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;">
          <i data-lucide="user-plus" style="width: 14px; height: 14px;"></i>
          <span>Pindah ke Tambah Pasien Baru (Alur IGD) &raquo;</span>
        </a>
      </div>
    </div>

    <!-- Input Tambahan untuk Mr./Mrs. X -->
    <div id="sectionAnonymous" style="display: none; margin-top: 16px;">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
        <div>
          <label class="form-label" style="font-weight: 600;">Label / Nama Darurat</label>
          <input type="text" name="anonymous_name" id="anonName" class="form-control" placeholder="Contoh: Mr. X (Korban Laka Lantas)">
        </div>
        <div>
          <label class="form-label" style="font-weight: 600;">Estimasi Umur</label>
          <input type="number" name="estimated_age" class="form-control" placeholder="Contoh: 35" min="1" max="120">
        </div>
        <div>
          <label class="form-label" style="font-weight: 600;">Perkiraan Jenis Kelamin</label>
          <select name="gender" class="form-control">
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Moda Kedatangan & Pengantar Card -->
  <div class="card" style="margin-bottom: 20px;">
    <h3 style="margin: 0 0 16px; font-size: 14px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="ambulance" class="lucide-md" style="color: #dc2626;"></i>
      <span>Informasi Kedatangan &amp; Transportasi</span>
    </h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
      <div>
        <label class="form-label" style="font-weight: 600;">Moda Transportasi Kedatangan</label>
        <select name="transportation" class="form-control" required>
          <option value="AMBULANS">Ambulans Gawat Darurat (SPGDT / 118)</option>
          <option value="KENDARAAN_PRIBADI" selected>Kendaraan Pribadi (Mobil / Motor)</option>
          <option value="JALAN_KAKI">Jalan Kaki / Datang Sendiri</option>
          <option value="LAINNYA">Lainnya (Polisi / Petugas Damkar)</option>
        </select>
      </div>

      <div>
        <label class="form-label" style="font-weight: 600;">Didampingi / Pengantar Pasien</label>
        <input type="text" name="accompanied_by" class="form-control" placeholder="Misal: Keluarga (Istri/Suami), Polisi, Warga Sekitar">
      </div>
    </div>
  </div>

  <!-- Action Buttons -->
  <div style="display: flex; gap: 12px; justify-content: flex-end;">
    <a href="<?= base_url('index.php?c=igd&a=index') ?>" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary" style="background: #dc2626; border-color: #b91c1c; padding: 10px 24px; font-weight: 700;">
      <i data-lucide="arrow-right"></i>
      <span>Daftarkan &amp; Lanjut Skrining Triase</span>
    </button>
  </div>
</form>

<script>
function toggleAnonymous(checked) {
  const regSec = document.getElementById('sectionRegisteredPatient');
  const anonSec = document.getElementById('sectionAnonymous');
  const patientSelect = document.getElementById('patientSelect');
  const anonName = document.getElementById('anonName');

  if (checked) {
    regSec.style.display = 'none';
    anonSec.style.display = 'block';
    patientSelect.value = '';
    if (!anonName.value) anonName.value = 'Mr. X (~35 th)';
  } else {
    regSec.style.display = 'block';
    anonSec.style.display = 'none';
  }
}
</script>

