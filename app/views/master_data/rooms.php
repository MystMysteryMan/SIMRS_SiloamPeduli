<?php
// app/views/master_data/rooms.php - Master Ruangan & Tempat Tidur
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=master&a=index') ?>" class="btn btn-outline btn-sm">
    <i data-lucide="arrow-left" class="lucide-sm"></i> Kembali ke Pusat Master Data
  </a>
</div>

<div class="grid-3" style="align-items: start;">
  <!-- Form Tambah Ruangan -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 style="display: flex; align-items: center; gap: 8px;">
          <i data-lucide="bed-double" class="lucide-sm" style="color: var(--blue-700);"></i>
          Tambah Ruangan Baru
        </h3>
        <p>Tempat tidur akan otomatis digenerate</p>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" style="margin-bottom: 16px;">
        <i data-lucide="alert-circle" class="lucide-sm"></i>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= base_url('index.php?c=master&a=rooms') ?>">
      <div class="form-group">
        <label class="form-label" for="room_code">Kode Ruangan *</label>
        <input type="text" id="room_code" name="room_code" class="form-control" placeholder="Misal: R-MELATI" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="name">Nama Ruangan *</label>
        <input type="text" id="name" name="name" class="form-control" placeholder="Misal: Bangsal Melati Rawat Inap" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="class">Kelas Perawatan *</label>
        <select id="class" name="class" class="form-control" required>
          <option value="VVIP">VVIP</option>
          <option value="VIP">VIP</option>
          <option value="Kelas 1">Kelas 1</option>
          <option value="Kelas 2">Kelas 2</option>
          <option value="Kelas 3">Kelas 3</option>
          <option value="ICU">ICU / ICCU</option>
          <option value="Isolasi">Isolasi</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="total_beds">Jumlah Tempat Tidur (Bed) *</label>
        <input type="number" id="total_beds" name="total_beds" class="form-control" value="4" min="1" max="20" required>
        <small style="color: var(--muted); font-size: 11px;">Nomor bed akan otomatis digenerate (cth: R-MELATI-01, dst)</small>
      </div>

      <div style="margin-top: 20px;">
        <button type="submit" class="btn btn-primary" style="width: 100%;">
          <i data-lucide="save" class="lucide-sm"></i> Simpan Ruangan & Generate Bed
        </button>
      </div>
    </form>
  </div>

  <!-- Tabel Ruangan -->
  <div class="card" style="grid-column: span 2;">
    <div class="card-header">
      <div>
        <h3 style="display: flex; align-items: center; gap: 8px;">
          <i data-lucide="bed" class="lucide-sm" style="color: var(--blue-700);"></i>
          Katalog Ruangan Rawat Inap & Tempat Tidur
        </h3>
        <p>Manajemen ketersediaan bed pasien rawat inap</p>
      </div>
      <span class="badge badge-primary"><?= count($rooms) ?> Ruangan</span>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Nama Ruangan</th>
            <th>Kelas</th>
            <th>Total Bed</th>
            <th>Tersedia</th>
            <th>Daftar Tempat Tidur</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rooms as $room): ?>
            <?php
              $beds = $room['beds'] ?? [];
            ?>
            <tr>
              <td><code><?= htmlspecialchars($room['room_code']) ?></code></td>
              <td><b><?= htmlspecialchars($room['name']) ?></b></td>
              <td><span class="badge badge-primary"><?= htmlspecialchars($room['class']) ?></span></td>
              <td><?= $room['total_beds_count'] ?> Bed</td>
              <td>
                <b style="color: var(--green-600);"><?= $room['available_beds'] ?></b> Siap Pakai
              </td>
              <td>
                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                  <?php foreach ($beds as $b): ?>
                    <span class="badge <?= $b['status'] === 'Tersedia' ? 'badge-success' : 'badge-danger' ?>" style="font-size: 10px; font-family: monospace;">
                      <?= htmlspecialchars($b['bed_number']) ?> (<?= $b['status'] ?>)
                    </span>
                  <?php endforeach; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

