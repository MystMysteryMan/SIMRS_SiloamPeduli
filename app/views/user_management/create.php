<?php
// app/views/user_management/create.php - Tambah Pengguna Baru (Lucide Icons)
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=user&a=index') ?>" class="btn btn-outline btn-sm">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Daftar Pengguna</span>
  </a>
</div>

<div class="card" style="max-width: 640px; margin: 0 auto;">
  <div class="card-header">
    <div>
      <h3>Tambah Pengguna &amp; Role Baru</h3>
      <p>Daftarkan akun pengguna baru dengan hak akses yang sesuai</p>
    </div>
    <span class="badge badge-primary">
      <i data-lucide="user-plus"></i>
      <span>Pengguna Baru</span>
    </span>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
      <i data-lucide="alert-triangle"></i>
      <div><?= htmlspecialchars($error) ?></div>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?= base_url('index.php?c=user&a=create') ?>">
    <div class="form-group">
      <label class="form-label" for="full_name">Nama Lengkap Pengguna *</label>
      <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Contoh: Budi Santoso, S.Kom" required>
    </div>

    <div class="grid-2">
      <div class="form-group">
        <label class="form-label" for="username">Username *</label>
        <input type="text" id="username" name="username" class="form-control" placeholder="Huruf kecil tanpa spasi" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Kata Sandi *</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="Kata sandi akun" required>
      </div>
    </div>

    <div class="grid-2">
      <div class="form-group">
        <label class="form-label" for="role">Hak Akses / Peran (Role) *</label>
        <select id="role" name="role" class="form-control" required onchange="handleRoleChange(this.value)">
          <option value="">-- Pilih Peran / Role Operasional --</option>
          <option value="registrasi">Petugas Registrasi &amp; Admisi Pasien</option>
          <option value="dokter">Dokter Spesialis / Dokter Poliklinik</option>
          <option value="dokter_radiologi">Dokter Spesialis Radiologi</option>
          <option value="perawat">Perawat Pelayanan &amp; Rawat Inap</option>
          <option value="triase">Petugas Triase IGD</option>
          <option value="apoteker">Apoteker / Farmasi</option>
          <option value="petugas_gudang">Petugas Gudang &amp; Logistik Medis</option>
          <option value="kasir">Kasir &amp; Billing Sentral</option>
          <option value="analis_laboratorium">Analis Laboratorium Sentral</option>
          <option value="radiografer">Radiografer Pelaksana Imaging</option>
          <option value="manajemen">Direksi &amp; Manajemen Rumah Sakit</option>
        </select>
        <small style="color: var(--muted); font-size: 11px;">Akun Administrator TI dilindungi &amp; dikelola langsung oleh superadmin.</small>
      </div>

      <div class="form-group">
        <label class="form-label" for="unit">Unit / Departemen Penempatan *</label>
        <select id="unit" name="unit" class="form-control" required>
          <option value="">-- Pilih Unit Pelayanan RS --</option>
          <?php if (!empty($hospitalUnits)): ?>
            <?php foreach ($hospitalUnits as $hu): ?>
              <option value="<?= htmlspecialchars($hu['unit_name']) ?>" data-category="<?= htmlspecialchars($hu['category']) ?>">
                [<?= htmlspecialchars($hu['category']) ?>] <?= htmlspecialchars($hu['unit_name']) ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div style="margin-top: 6px; font-size: 11px; display: flex; justify-content: space-between; align-items: center;">
          <span style="color: var(--muted);">Unit belum terdaftar?</span>
          <a href="<?= base_url('index.php?c=master&a=units') ?>" target="_blank" style="font-weight: 600; color: var(--blue-700);">
            + Buat Unit Baru &raquo;
          </a>
        </div>
      </div>
    </div>

    <script>
      function handleRoleChange(role) {
        const unitSelect = document.getElementById('unit');
        if (!unitSelect) return;
        const roleMap = {
          'registrasi': 'Registrasi & Admisi Pasien',
          'dokter': 'Poliklinik Penyakit Dalam',
          'dokter_radiologi': 'Instalasi Radiologi',
          'perawat': 'Keperawatan Rawat Jalan',
          'triase': 'Instalasi Gawat Darurat (IGD)',
          'apoteker': 'Instalasi Farmasi & Apotek',
          'petugas_gudang': 'Gudang & Logistik Medis',
          'kasir': 'Kasir & Billing Sentral',
          'analis_laboratorium': 'Laboratorium Sentral',
          'radiografer': 'Instalasi Radiologi',
          'manajemen': 'Direksi & Manajemen Rumah Sakit'
        };
        const target = roleMap[role];
        if (target) {
          for (let i = 0; i < unitSelect.options.length; i++) {
            if (unitSelect.options[i].value === target || unitSelect.options[i].value.includes(target)) {
              unitSelect.selectedIndex = i;
              break;
            }
          }
        }
      }
    </script>

    <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 10px;">
      <a href="<?= base_url('index.php?c=user&a=index') ?>" class="btn btn-outline">Batal</a>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="save"></i>
        <span>Simpan Pengguna</span>
      </button>
    </div>
  </form>
</div>
