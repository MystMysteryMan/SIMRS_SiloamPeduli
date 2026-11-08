<?php
// app/views/user_management/audit_log.php - Audit Trail / Log Aktivitas (Lucide Icons)
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900);">
      Audit Trail &amp; Rekam Jejak Sistem
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Merekam setiap aktivitas login, perubahan data pasien, pembuatan jadwal antrean, dan konfigurasi master data.
    </p>
  </div>
  <a href="<?= base_url('index.php?c=user&a=index') ?>" class="btn btn-outline">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Kelola Pengguna</span>
  </a>
</div>

<div class="card">
  <!-- Filter Form -->
  <form method="GET" action="<?= base_url('index.php') ?>" style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; align-items: flex-end;">
    <input type="hidden" name="c" value="user">
    <input type="hidden" name="a" value="auditLog">

    <div style="width: 220px;">
      <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Filter Layanan / Fitur</label>
      <select name="module" class="form-control" onchange="this.form.submit()">
        <option value="">-- Semua Layanan --</option>
        <option value="Autentikasi" <?= ($filterModule === 'Autentikasi') ? 'selected' : '' ?>>Autentikasi</option>
        <option value="Registrasi Pasien" <?= ($filterModule === 'Registrasi Pasien') ? 'selected' : '' ?>>Registrasi Pasien</option>
        <option value="Penjadwalan" <?= ($filterModule === 'Penjadwalan') ? 'selected' : '' ?>>Penjadwalan</option>
        <option value="Manajemen Pengguna" <?= ($filterModule === 'Manajemen Pengguna') ? 'selected' : '' ?>>Manajemen Pengguna</option>
        <option value="Master Data" <?= ($filterModule === 'Master Data') ? 'selected' : '' ?>>Master Data</option>
        <option value="Konfigurasi Sistem" <?= ($filterModule === 'Konfigurasi Sistem') ? 'selected' : '' ?>>Konfigurasi Sistem</option>
      </select>
    </div>

    <div style="width: 180px;">
      <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Tanggal Log</label>
      <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filterDate ?? '') ?>" onchange="this.form.submit()">
    </div>

    <button type="submit" class="btn btn-primary" style="height: 38px;">
      <i data-lucide="filter"></i>
      <span>Filter</span>
    </button>
    <?php if (!empty($filterModule) || !empty($filterDate)): ?>
      <a href="<?= base_url('index.php?c=user&a=auditLog') ?>" class="btn btn-outline" style="color: var(--danger); height: 38px;">
        <i data-lucide="rotate-ccw"></i>
        <span>Reset</span>
      </a>
    <?php endif; ?>
  </form>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th style="width: 160px;">Waktu</th>
          <th>Pengguna (Operator)</th>
          <th>Layanan / Fitur</th>
          <th>Aksi</th>
          <th>Rincian Aktivitas</th>
          <th>IP Address</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($logs)): ?>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td style="font-size: 12px; color: var(--muted); white-space: nowrap;">
                <i data-lucide="clock" class="lucide-sm" style="margin-right: 4px;"></i>
                <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
              </td>
              <td>
                <b><?= htmlspecialchars($log['user_name'] ?? 'Sistem / Anonim') ?></b>
                <?php if (!empty($log['username'])): ?>
                  <span style="font-size: 11px; color: var(--muted); display: block;">@<?= htmlspecialchars($log['username']) ?></span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-primary"><?= htmlspecialchars($log['module']) ?></span>
              </td>
              <td><code><?= htmlspecialchars($log['action']) ?></code></td>
              <td><?= htmlspecialchars($log['details'] ?? '-') ?></td>
              <td style="font-size: 12px; font-family: monospace; color: var(--muted);">
                <?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align: center; padding: 32px; color: var(--muted);">
              Tidak ada log aktivitas yang sesuai dengan kriteria filter.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php include __DIR__ . '/../components/pagination.php'; ?>
</div>
