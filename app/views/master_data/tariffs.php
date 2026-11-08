<?php
// app/views/master_data/tariffs.php - Master Tarif Tindakan & Layanan
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=master&a=index') ?>" class="btn btn-outline btn-sm">
    <i data-lucide="arrow-left" class="lucide-sm"></i> Kembali ke Pusat Master Data
  </a>
</div>

<div class="grid-3" style="align-items: start;">
  <!-- Form Tambah Tarif -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 style="display: flex; align-items: center; gap: 8px;">
          <i data-lucide="plus-circle" class="lucide-sm" style="color: var(--blue-700);"></i>
          Tambah Tarif Layanan
        </h3>
        <p>Standar tarif tindakan RS</p>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" style="margin-bottom: 16px;">
        <i data-lucide="alert-circle" class="lucide-sm"></i>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= base_url('index.php?c=master&a=tariffs') ?>">
      <div class="form-group">
        <label class="form-label" for="code">Kode Tarif *</label>
        <input type="text" id="code" name="code" class="form-control" placeholder="Misal: TAR-KONS-01" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="service_name">Nama Tindakan / Layanan *</label>
        <input type="text" id="service_name" name="service_name" class="form-control" placeholder="Misal: Konsultasi Dokter Spesialis" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="category">Kategori Layanan *</label>
        <select id="category" name="category" class="form-control" required>
          <option value="Konsultasi">Konsultasi Dokter</option>
          <option value="Tindakan Medis">Tindakan Medis & Keperawatan</option>
          <option value="Admisi">Registrasi & Admisi</option>
          <option value="Laboratorium">Laboratorium</option>
          <option value="Radiologi">Radiologi & Imaging</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="price">Tarif (Rp) *</label>
        <input type="number" id="price" name="price" class="form-control" placeholder="Contoh: 150000" min="0" step="500" required>
      </div>

      <div style="margin-top: 20px;">
        <button type="submit" class="btn btn-primary" style="width: 100%;">
          <i data-lucide="save" class="lucide-sm"></i> Simpan Tarif
        </button>
      </div>
    </form>
  </div>

  <!-- Tabel Tarif -->
  <div class="card" style="grid-column: span 2;">
    <div class="card-header">
      <div>
        <h3 style="display: flex; align-items: center; gap: 8px;">
          <i data-lucide="banknote" class="lucide-sm" style="color: var(--blue-700);"></i>
          Katalog Standar Tarif RS
        </h3>
        <p>Daftar harga pelayanan dan tindakan terverifikasi</p>
      </div>
      <span class="badge badge-primary"><?= count($tariffs) ?> Tarif</span>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Nama Layanan / Tindakan</th>
            <th>Kategori</th>
            <th>Tarif (Rupiah)</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tariffs as $t): ?>
            <tr>
              <td><code><?= htmlspecialchars($t['code']) ?></code></td>
              <td><b><?= htmlspecialchars($t['service_name']) ?></b></td>
              <td><span class="badge badge-primary"><?= htmlspecialchars($t['category']) ?></span></td>
              <td style="font-weight: 700; color: var(--blue-900);">
                <?= format_rupiah($t['price']) ?>
              </td>
              <td>
                <span class="badge <?= $t['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                  <?= $t['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

