<?php
// app/views/master_data/units.php - Master Unit Pelayanan & Departemen RS Siloam Peduli
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=master&a=index') ?>" class="btn btn-outline btn-sm">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Pusat Master Data</span>
  </a>
</div>

<div class="grid-3" style="align-items: start;">
  <!-- Form Tambah Unit -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 style="margin: 0; font-size: 16px;">Tambah Unit Pelayanan</h3>
        <p style="margin: 4px 0 0; color: var(--muted); font-size: 12.5px;">Daftarkan departemen / unit kerja rumah sakit</p>
      </div>
      <i data-lucide="network" class="lucide-md" style="color: var(--blue-600);"></i>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" style="margin-bottom: 16px;">
        <i data-lucide="alert-triangle"></i>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= base_url('index.php?c=master&a=units') ?>">
      <div class="form-group">
        <label class="form-label" for="unit_code">Kode Unit *</label>
        <input type="text" id="unit_code" name="unit_code" class="form-control" placeholder="Contoh: UNIT-HEMO" required>
        <small style="color: var(--muted); font-size: 11px;">Identifikasi unik unit kerja</small>
      </div>

      <div class="form-group">
        <label class="form-label" for="unit_name">Nama Unit Pelayanan *</label>
        <input type="text" id="unit_name" name="unit_name" class="form-control" placeholder="Contoh: Unit Hemodialisa" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="category">Kategori Unit *</label>
        <select id="category" name="category" class="form-control" required>
          <option value="Klinis">Pelayanan Klinis (Poli, Bangsal, IGD, ICU)</option>
          <option value="Penunjang">Penunjang Medis (Farmasi, Lab, Radiologi, Gudang)</option>
          <option value="Administrasi">Administrasi &amp; Keuangan (Registrasi, Kasir, TI)</option>
          <option value="Manajemen">Direksi &amp; Manajemen Eksekutif</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary w-100" style="margin-top: 10px;">
        <i data-lucide="plus-circle"></i>
        <span>Simpan Unit Baru</span>
      </button>
    </form>
  </div>

  <!-- Daftar Unit Pelayanan RS -->
  <div class="card" style="grid-column: span 2;">
    <div class="card-header">
      <div>
        <h3 style="margin: 0; font-size: 16px;">Daftar Unit Pelayanan Terdaftar</h3>
        <p style="margin: 4px 0 0; color: var(--muted); font-size: 12.5px;">Unit kerja resmi yang menjadi acuan penempatan staf &amp; hak akses pengguna</p>
      </div>
      <span class="badge badge-primary">
        <?= count($units ?? []) ?> Unit
      </span>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th style="width: 50px;">No</th>
            <th>Kode</th>
            <th>Nama Unit Pelayanan</th>
            <th>Kategori</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($units)): ?>
            <?php foreach ($units as $i => $u): ?>
              <tr>
                <td style="color: var(--muted);"><?= $i + 1 ?></td>
                <td><code style="font-weight: 700;"><?= htmlspecialchars($u['unit_code']) ?></code></td>
                <td><b><?= htmlspecialchars($u['unit_name']) ?></b></td>
                <td>
                  <?php
                    $cat = $u['category'];
                    $catClass = 'badge-muted';
                    if ($cat === 'Klinis') $catClass = 'badge-primary';
                    elseif ($cat === 'Penunjang') $catClass = 'badge-info';
                    elseif ($cat === 'Administrasi') $catClass = 'badge-warning';
                    elseif ($cat === 'Manajemen') $catClass = 'badge-success';
                  ?>
                  <span class="badge <?= $catClass ?>"><?= htmlspecialchars($cat) ?></span>
                </td>
                <td>
                  <span class="badge badge-success">
                    <i data-lucide="check" style="width: 10px; height: 10px;"></i>
                    Aktif
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align: center; color: var(--muted); padding: 30px;">
                Belum ada data unit pelayanan.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

