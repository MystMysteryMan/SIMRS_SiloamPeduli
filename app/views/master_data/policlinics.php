<?php
// app/views/master_data/policlinics.php - Master Poliklinik & Awalan Antrean (Lucide Icons)
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=master&a=index') ?>" class="btn btn-outline btn-sm">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Pusat Master Data</span>
  </a>
</div>

<div class="grid-3" style="align-items: start;">
  <!-- Form Tambah -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3>Tambah Poliklinik</h3>
        <p>Tentukan prefix antrean tiket</p>
      </div>
      <i data-lucide="building-2" class="lucide-md" style="color: var(--blue-600);"></i>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" style="margin-bottom: 16px;">
        <i data-lucide="alert-triangle"></i>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= base_url('index.php?c=master&a=policlinics') ?>">
      <div class="form-group">
        <label class="form-label" for="code">Kode Poliklinik *</label>
        <input type="text" id="code" name="code" class="form-control" placeholder="Misal: POLI-INT" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="name">Nama Poliklinik *</label>
        <input type="text" id="name" name="name" class="form-control" placeholder="Misal: Poliklinik Penyakit Dalam" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="queue_prefix">Awalan Antrean (Prefix) *</label>
        <input type="text" id="queue_prefix" name="queue_prefix" class="form-control" placeholder="Misal: INT" maxlength="5" required>
        <small style="color: var(--muted); font-size: 11px;">Digunakan pada nomor antrean tiket (cth: INT-001)</small>
      </div>

      <div style="margin-top: 20px;">
        <button type="submit" class="btn btn-primary" style="width: 100%;">
          <i data-lucide="save"></i>
          <span>Simpan Poliklinik</span>
        </button>
      </div>
    </form>
  </div>

  <!-- Tabel Poliklinik -->
  <div class="card" style="grid-column: span 2;">
    <div class="card-header">
      <div>
        <h3>Daftar Poliklinik &amp; Awalan Antrean</h3>
        <p>Unit poliklinik rawat jalan aktif dalam sistem</p>
      </div>
      <span class="badge badge-primary"><?= count($policlinics) ?> Poli</span>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Nama Poliklinik</th>
            <th>Prefix Antrean</th>
            <th>Total Dokter</th>
            <th>Status</th>
            <th style="text-align: right;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($policlinics as $poli): ?>
            <tr>
              <td><code><?= htmlspecialchars($poli['code']) ?></code></td>
              <td><b><?= htmlspecialchars($poli['name']) ?></b></td>
              <td>
                <span class="badge badge-primary" style="font-weight: 800; font-family: monospace;">
                  <?= htmlspecialchars($poli['queue_prefix']) ?>-XXX
                </span>
              </td>
              <td><?= $poli['total_doctors'] ?> Dokter</td>
              <td>
                <span class="badge <?= $poli['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                  <?= $poli['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                </span>
              </td>
              <td style="text-align: right;">
                <a href="<?= base_url('index.php?c=master&a=togglePoli&id=' . $poli['id']) ?>" 
                   class="btn <?= $poli['is_active'] ? 'btn-danger' : 'btn-success' ?> btn-sm">
                  <i data-lucide="<?= $poli['is_active'] ? 'x-circle' : 'check-circle' ?>"></i>
                  <span><?= $poli['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></span>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
