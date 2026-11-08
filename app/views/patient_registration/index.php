<?php
// app/views/patient_registration/index.php - Pencarian & Daftar Pasien (Lucide Icons)
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900);">
      Registrasi & Identifikasi Pasien
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Pencarian data rekam medis pasien lama berdasarkan NIK, No. RM, Nama, atau No. Handphone.
    </p>
  </div>
  <a href="<?= base_url('index.php?c=patient&a=create') ?>" class="btn btn-primary">
    <i data-lucide="user-plus"></i>
    <span>Registrasi Pasien Baru</span>
  </a>
</div>

<div class="card">
  <!-- Search Box -->
  <form method="GET" action="<?= base_url('index.php') ?>" style="margin-bottom: 20px;">
    <input type="hidden" name="c" value="patient">
    <input type="hidden" name="a" value="index">
    <div style="display: flex; gap: 10px; max-width: 600px;">
      <div style="position: relative; flex: 1;">
        <input type="text" name="q" class="form-control" placeholder="Cari No. RM / NIK (16 digit) / Nama Pasien / No. HP..." value="<?= htmlspecialchars($keyword ?? '') ?>" autofocus>
      </div>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="search"></i>
        <span>Cari</span>
      </button>
      <?php if (!empty($keyword)): ?>
        <a href="<?= base_url('index.php?c=patient&a=index') ?>" class="btn btn-outline">
          <i data-lucide="rotate-ccw"></i>
          <span>Reset</span>
        </a>
      <?php endif; ?>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>No. RM</th>
          <th>NIK</th>
          <th>Nama Pasien</th>
          <th>Jenis Kelamin</th>
          <th>Tgl Lahir / Usia</th>
          <th>No. Telepon</th>
          <th>Penjamin</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($patients)): ?>
          <?php foreach ($patients as $p): ?>
            <?php
              $birthDate = new DateTime($p['birth_date']);
              $today = new DateTime();
              $age = $today->diff($birthDate)->y;
            ?>
            <tr>
              <td>
                <span class="badge badge-primary" style="font-size: 13px; font-family: monospace;">
                  <?= htmlspecialchars($p['no_rm']) ?>
                </span>
              </td>
              <td style="font-family: monospace; font-size: 12px;"><?= htmlspecialchars($p['nik']) ?></td>
              <td><b><?= htmlspecialchars($p['name']) ?></b></td>
              <td><?= $p['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
              <td><?= date('d/m/Y', strtotime($p['birth_date'])) ?> (<?= $age ?> thn)</td>
              <td><?= htmlspecialchars($p['phone'] ?? '-') ?></td>
              <td>
                <span class="badge <?= $p['insurance_type'] === 'BPJS' ? 'badge-success' : ($p['insurance_type'] === 'UMUM' ? 'badge-muted' : 'badge-primary') ?>">
                  <?= htmlspecialchars($p['insurance_type']) ?>
                </span>
                <?php if (!empty($p['insurance_number'])): ?>
                  <span style="display: block; font-size: 11px; color: var(--muted);"><?= htmlspecialchars($p['insurance_number']) ?></span>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 6px;">
                  <a href="<?= base_url('index.php?c=appointment&a=create&patient_id=' . $p['id']) ?>" class="btn btn-success btn-sm" title="Daftarkan ke Janji Temu Poliklinik">
                    <i data-lucide="calendar-plus"></i>
                    <span>Janji</span>
                  </a>
                  <a href="<?= base_url('index.php?c=patient&a=printCard&id=' . $p['id']) ?>" target="_blank" class="btn btn-outline btn-sm" title="Cetak Kartu Berobat">
                    <i data-lucide="printer"></i>
                    <span>Kartu</span>
                  </a>
                  <a href="<?= base_url('index.php?c=patient&a=edit&id=' . $p['id']) ?>" class="btn btn-outline btn-sm" title="Edit Data">
                    <i data-lucide="edit-2"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" style="text-align: center; padding: 36px; color: var(--muted);">
              Tidak ada data pasien yang ditemukan.
              <div style="margin-top: 10px;">
                <a href="<?= base_url('index.php?c=patient&a=create') ?>" class="btn btn-primary btn-sm">
                  <i data-lucide="user-plus"></i>
                  <span>Daftarkan Pasien Baru</span>
                </a>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php include __DIR__ . '/../components/pagination.php'; ?>
</div>
