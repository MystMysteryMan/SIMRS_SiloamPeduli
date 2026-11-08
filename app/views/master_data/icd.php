<?php
// app/views/master_data/icd.php - Master Diagnosa ICD-10
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=master&a=index') ?>" class="btn btn-outline btn-sm">
    <i data-lucide="arrow-left" class="lucide-sm"></i> Kembali ke Pusat Master Data
  </a>
</div>

<div class="grid-3" style="align-items: start;">
  <!-- Form Tambah ICD-10 -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 style="display: flex; align-items: center; gap: 8px;">
          <i data-lucide="plus-circle" class="lucide-sm" style="color: var(--blue-700);"></i>
          Tambah Kode ICD-10
        </h3>
        <p>Standar diagnosa medis WHO</p>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" style="margin-bottom: 16px;">
        <i data-lucide="alert-circle" class="lucide-sm"></i>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= base_url('index.php?c=master&a=icd') ?>">
      <div class="form-group">
        <label class="form-label" for="code">Kode ICD-10 *</label>
        <input type="text" id="code" name="code" class="form-control" placeholder="Misal: J06.9" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="description_id">Deskripsi (Bahasa Indonesia) *</label>
        <input type="text" id="description_id" name="description_id" class="form-control" placeholder="Misal: Infeksi Saluran Pernapasan Akut" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="description_en">Deskripsi (English)</label>
        <input type="text" id="description_en" name="description_en" class="form-control" placeholder="Misal: Acute upper respiratory infection, unspecified">
      </div>

      <div style="margin-top: 20px;">
        <button type="submit" class="btn btn-primary" style="width: 100%;">
          <i data-lucide="save" class="lucide-sm"></i> Simpan ICD-10
        </button>
      </div>
    </form>
  </div>

  <!-- Tabel ICD-10 -->
  <div class="card" style="grid-column: span 2;">
    <div class="card-header">
      <div>
        <h3 style="display: flex; align-items: center; gap: 8px;">
          <i data-lucide="book-open" class="lucide-sm" style="color: var(--blue-700);"></i>
          Katalog ICD-10 Diagnosa
        </h3>
        <p>Referensi pengkodean penyakit standar rekam medis</p>
      </div>
      <form method="GET" action="<?= base_url('index.php') ?>" style="display: flex; gap: 8px; align-items: center;">
        <input type="hidden" name="c" value="master">
        <input type="hidden" name="a" value="icd">
        <input type="text" name="q" class="form-control" style="width: 180px; height: 34px; font-size: 12.5px;" placeholder="Cari kode/penyakit..." value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
          <i data-lucide="search" class="lucide-sm"></i> Cari
        </button>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th style="width: 110px;">Kode ICD</th>
            <th>Diagnosa (Indonesia)</th>
            <th>Deskripsi Medis Internasional</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($icdList)): ?>
            <?php foreach ($icdList as $icd): ?>
              <tr>
                <td>
                  <span class="badge badge-primary" style="font-family: monospace; font-size: 13px;">
                    <?= htmlspecialchars($icd['code']) ?>
                  </span>
                </td>
                <td><b><?= htmlspecialchars($icd['description_id']) ?></b></td>
                <td style="font-size: 12px; color: var(--muted);"><?= htmlspecialchars($icd['description_en'] ?? '-') ?></td>
                <td>
                  <span class="badge <?= $icd['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $icd['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" style="text-align: center; padding: 24px; color: var(--muted);">
                Tidak ada data diagnosa ICD-10 yang cocok.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

