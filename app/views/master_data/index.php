<?php
// app/views/master_data/index.php - Ringkasan Master Data & Konfigurasi Sistem (Lucide Icons)
?>

<div style="margin-bottom: 24px;">
  <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900);">
    Pusat Master Data &amp; Konfigurasi Sistem
  </h1>
  <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
    Manajemen parameter operasional SIMRS, standarisasi referensi medis, tarif, dan aturan alur antrean.
  </p>
</div>

<div class="grid-3">
  <!-- 1. Poliklinik -->
  <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
    <div>
      <div style="width: 44px; height: 44px; border-radius: 12px; background: var(--blue-50); color: var(--blue-700); display: grid; place-items: center; margin-bottom: 12px;">
        <i data-lucide="building-2" class="lucide-lg"></i>
      </div>
      <h3 style="margin: 0 0 6px; font-size: 16px;">Poliklinik &amp; Awalan Antrean</h3>
      <p style="font-size: 12.5px; color: var(--muted); margin: 0 0 14px;">
        Kelola unit poliklinik rawat jalan dan prefix kode antrean tiket (misal: INT, KDG, ANA).
      </p>
      <div style="font-size: 13px; font-weight: 700; color: var(--blue-700);">
        <?= count($policlinics) ?> Poliklinik Terdaftar
      </div>
    </div>
    <div style="margin-top: 16px;">
      <a href="<?= base_url('index.php?c=master&a=policlinics') ?>" class="btn btn-outline btn-sm" style="width: 100%;">
        <span>Kelola Poliklinik</span>
        <i data-lucide="arrow-right"></i>
      </a>
    </div>
  </div>

  <!-- 2. Dokter & Jadwal -->
  <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
    <div>
      <div style="width: 44px; height: 44px; border-radius: 12px; background: var(--blue-50); color: var(--blue-700); display: grid; place-items: center; margin-bottom: 12px;">
        <i data-lucide="stethoscope" class="lucide-lg"></i>
      </div>
      <h3 style="margin: 0 0 6px; font-size: 16px;">Dokter &amp; Jadwal Praktek</h3>
      <p style="font-size: 12.5px; color: var(--muted); margin: 0 0 14px;">
        Data staf medis, nomor SIP, pemetaan poli, hari praktek, dan kuota maksimum pasien per hari.
      </p>
      <div style="font-size: 13px; font-weight: 700; color: var(--blue-700);">
        <?= count($doctors) ?> Dokter Terdaftar
      </div>
    </div>
    <div style="margin-top: 16px;">
      <a href="<?= base_url('index.php?c=master&a=doctors') ?>" class="btn btn-outline btn-sm" style="width: 100%;">
        <span>Kelola Dokter &amp; Jadwal</span>
        <i data-lucide="arrow-right"></i>
      </a>
    </div>
  </div>

  <!-- 3. Ruangan & Bed -->
  <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
    <div>
      <div style="width: 44px; height: 44px; border-radius: 12px; background: var(--blue-50); color: var(--blue-700); display: grid; place-items: center; margin-bottom: 12px;">
        <i data-lucide="bed" class="lucide-lg"></i>
      </div>
      <h3 style="margin: 0 0 6px; font-size: 16px;">Ruangan &amp; Tempat Tidur</h3>
      <p style="font-size: 12.5px; color: var(--muted); margin: 0 0 14px;">
        Katalog kamar rawat inap (VVIP, VIP, Kelas 1, 2, 3, ICU) dan status ketersediaan bed.
      </p>
      <div style="font-size: 13px; font-weight: 700; color: var(--blue-700);">
        <?= count($rooms) ?> Ruangan Rawat Inap
      </div>
    </div>
    <div style="margin-top: 16px;">
      <a href="<?= base_url('index.php?c=master&a=rooms') ?>" class="btn btn-outline btn-sm" style="width: 100%;">
        <span>Kelola Ruangan &amp; Bed</span>
        <i data-lucide="arrow-right"></i>
      </a>
    </div>
  </div>

  <!-- 4. Tarif Layanan -->
  <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
    <div>
      <div style="width: 44px; height: 44px; border-radius: 12px; background: var(--blue-50); color: var(--blue-700); display: grid; place-items: center; margin-bottom: 12px;">
        <i data-lucide="banknote" class="lucide-lg"></i>
      </div>
      <h3 style="margin: 0 0 6px; font-size: 16px;">Tarif Tindakan &amp; Konsultasi</h3>
      <p style="font-size: 12.5px; color: var(--muted); margin: 0 0 14px;">
        Standar biaya konsultasi dokter spesialis, tindakan medis umum, admisi, dan laboratorium.
      </p>
      <div style="font-size: 13px; font-weight: 700; color: var(--blue-700);">
        <?= count($tariffs) ?> Item Tarif Layanan
      </div>
    </div>
    <div style="margin-top: 16px;">
      <a href="<?= base_url('index.php?c=master&a=tariffs') ?>" class="btn btn-outline btn-sm" style="width: 100%;">
        <span>Kelola Tarif</span>
        <i data-lucide="arrow-right"></i>
      </a>
    </div>
  </div>

  <!-- 5. ICD-10 -->
  <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
    <div>
      <div style="width: 44px; height: 44px; border-radius: 12px; background: var(--blue-50); color: var(--blue-700); display: grid; place-items: center; margin-bottom: 12px;">
        <i data-lucide="book-open" class="lucide-lg"></i>
      </div>
      <h3 style="margin: 0 0 6px; font-size: 16px;">Klasifikasi Diagnosa ICD-10</h3>
      <p style="font-size: 12.5px; color: var(--muted); margin: 0 0 14px;">
        Katalog kode diagnosa penyakit standar WHO dan Kemenkes RI untuk kodifikasi rekam medis.
      </p>
      <div style="font-size: 13px; font-weight: 700; color: var(--blue-700);">
        Katalog ICD-10 Aktif
      </div>
    </div>
    <div style="margin-top: 16px;">
      <a href="<?= base_url('index.php?c=master&a=icd') ?>" class="btn btn-outline btn-sm" style="width: 100%;">
        <span>Katalog ICD-10</span>
        <i data-lucide="arrow-right"></i>
      </a>
    </div>
  </div>

  <!-- 6. Master Obat -->
  <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
    <div>
      <div style="width: 44px; height: 44px; border-radius: 12px; background: var(--blue-50); color: var(--blue-700); display: grid; place-items: center; margin-bottom: 12px;">
        <i data-lucide="pill" class="lucide-lg"></i>
      </div>
      <h3 style="margin: 0 0 6px; font-size: 16px;">Formularium Obat &amp; Alkes</h3>
      <p style="font-size: 12.5px; color: var(--muted); margin: 0 0 14px;">
        Daftar obat, kategori, satuan dosis, saldo persediaan dasar, dan harga satuan.
      </p>
      <div style="font-size: 13px; font-weight: 700; color: var(--blue-700);">
        <?= count($medicines) ?> Jenis Obat Terdaftar
      </div>
    </div>
    <div style="margin-top: 16px;">
      <a href="<?= base_url('index.php?c=master&a=medicines') ?>" class="btn btn-outline btn-sm" style="width: 100%;">
        <span>Kelola Master Obat</span>
        <i data-lucide="arrow-right"></i>
      </a>
    </div>
  </div>

  <!-- 7. Unit Pelayanan RS -->
  <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
    <div>
      <div style="width: 44px; height: 44px; border-radius: 12px; background: var(--blue-50); color: var(--blue-700); display: grid; place-items: center; margin-bottom: 12px;">
        <i data-lucide="network" class="lucide-lg"></i>
      </div>
      <h3 style="margin: 0 0 6px; font-size: 16px;">Unit Pelayanan &amp; Departemen</h3>
      <p style="font-size: 12.5px; color: var(--muted); margin: 0 0 14px;">
        Daftar unit kerja resmi rumah sakit (Klinis, Penunjang, Administrasi) untuk penempatan akun staf.
      </p>
      <div style="font-size: 13px; font-weight: 700; color: var(--blue-700);">
        Master Departemen RS
      </div>
    </div>
    <div style="margin-top: 16px;">
      <a href="<?= base_url('index.php?c=master&a=units') ?>" class="btn btn-outline btn-sm" style="width: 100%;">
        <span>Kelola Unit Pelayanan</span>
        <i data-lucide="arrow-right"></i>
      </a>
    </div>
  </div>

  <!-- 8. Konfigurasi Sistem -->
  <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; grid-column: span 3;">
    <div style="display: flex; gap: 16px; align-items: center;">
      <div style="width: 48px; height: 48px; border-radius: 12px; background: var(--blue-50); color: var(--blue-700); display: grid; place-items: center; flex-shrink: 0;">
        <i data-lucide="settings" class="lucide-lg"></i>
      </div>
      <div>
        <h3 style="margin: 0 0 4px; font-size: 16px;">Konfigurasi Sistem &amp; Parameter Operasional</h3>
        <p style="font-size: 12.5px; color: var(--muted); margin: 0;">
          Pengaturan global format penomoran No. RM (<code>RM-YYYYMM-XXXX</code>), nama resmi rumah sakit, jam operasional pendaftaran, dan kuota default dokter.
        </p>
      </div>
    </div>
    <div style="margin-top: 16px; align-self: flex-end;">
      <a href="<?= base_url('index.php?c=master&a=configurations') ?>" class="btn btn-primary">
        <i data-lucide="settings"></i>
        <span>Buka Konfigurasi Sistem</span>
      </a>
    </div>
  </div>
</div>
