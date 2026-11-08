<?php
// app/views/master_data/medicines.php - Master Formularium Obat & Alkes
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=master&a=index') ?>" class="btn btn-outline btn-sm">
    <i data-lucide="arrow-left" class="lucide-sm"></i> Kembali ke Pusat Master Data
  </a>
</div>

<div class="grid-3" style="align-items: start;">
  <!-- Form Tambah Obat -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 style="display: flex; align-items: center; gap: 8px;">
          <i data-lucide="plus-circle" class="lucide-sm" style="color: var(--blue-700);"></i>
          Tambah Obat / Alkes
        </h3>
        <p>Katalog farmasi rumah sakit</p>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" style="margin-bottom: 16px;">
        <i data-lucide="alert-circle" class="lucide-sm"></i>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= base_url('index.php?c=master&a=medicines') ?>">
      <div class="form-group">
        <label class="form-label" for="code">Kode Obat *</label>
        <input type="text" id="code" name="code" class="form-control" placeholder="Misal: OBT-PCT-500" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="name">Nama Obat & Dosis *</label>
        <input type="text" id="name" name="name" class="form-control" placeholder="Misal: Paracetamol 500mg Tablet" required>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label class="form-label" for="category">Kategori *</label>
          <select id="category" name="category" class="form-control" required>
            <option value="Obat Bebas">Obat Bebas</option>
            <option value="Obat Keras">Obat Keras</option>
            <option value="Antibiotik">Antibiotik</option>
            <option value="Injeksi">Injeksi / Cairan</option>
            <option value="Alkes">Alat Kesehatan</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="unit">Satuan *</label>
          <input type="text" id="unit" name="unit" class="form-control" placeholder="Tablet / Strip / Botol" required>
        </div>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label class="form-label" for="stock">Stok Awal</label>
          <input type="number" id="stock" name="stock" class="form-control" value="100" min="0">
        </div>

        <div class="form-group">
          <label class="form-label" for="unit_price">Harga Jual (Rp)</label>
          <input type="number" id="unit_price" name="unit_price" class="form-control" value="5000" min="0" step="500">
        </div>
      </div>

      <div style="margin-top: 20px;">
        <button type="submit" class="btn btn-primary" style="width: 100%;">
          <i data-lucide="save" class="lucide-sm"></i> Simpan Obat
        </button>
      </div>
    </form>
  </div>

  <!-- Tabel Obat -->
  <div class="card" style="grid-column: span 2;">
    <div class="card-header">
      <div>
        <h3 style="display: flex; align-items: center; gap: 8px;">
          <i data-lucide="pill" class="lucide-sm" style="color: var(--blue-700);"></i>
          Daftar Formularium Obat
        </h3>
        <p>Persediaan dasar obat dan tarif farmasi</p>
      </div>
      <form method="GET" action="<?= base_url('index.php') ?>" style="display: flex; gap: 8px; align-items: center;">
        <input type="hidden" name="c" value="master">
        <input type="hidden" name="a" value="medicines">
        <input type="text" name="q" class="form-control" style="width: 180px; height: 34px; font-size: 12.5px;" placeholder="Cari nama obat..." value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
          <i data-lucide="search" class="lucide-sm"></i> Cari
        </button>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Nama Obat</th>
            <th>Kategori</th>
            <th>Satuan</th>
            <th>Stok</th>
            <th>Harga Satuan</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($medicines)): ?>
            <?php foreach ($medicines as $m): ?>
              <tr>
                <td><code><?= htmlspecialchars($m['code']) ?></code></td>
                <td><b><?= htmlspecialchars($m['name']) ?></b></td>
                <td><span class="badge badge-primary"><?= htmlspecialchars($m['category']) ?></span></td>
                <td><?= htmlspecialchars($m['unit']) ?></td>
                <td>
                  <b style="color: <?= $m['stock'] > 10 ? 'var(--ink)' : 'var(--danger)' ?>;">
                    <?= number_format($m['stock']) ?>
                  </b>
                </td>
                <td style="font-weight: 600;"><?= format_rupiah($m['unit_price']) ?></td>
                <td>
                  <span class="badge <?= $m['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $m['is_active'] ? 'Tersedia' : 'Kosong' ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 24px; color: var(--muted);">
                Tidak ada data obat yang sesuai kriteria pencarian.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php include __DIR__ . '/../components/pagination.php'; ?>
  </div>
</div>

