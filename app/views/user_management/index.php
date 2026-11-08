<?php
// app/views/user_management/index.php - Daftar Pengguna & Hak Akses (Lucide Icons)
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900);">
      Manajemen Pengguna &amp; Hak Akses
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Kelola akun operator, role perizinan (Admin Sistem vs Petugas Registrasi), dan reset password.
    </p>
  </div>
  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <a href="<?= base_url('index.php?c=user&a=auditLog') ?>" class="btn btn-outline">
      <i data-lucide="shield-check"></i>
      <span>Audit Trail / Log</span>
    </a>
    <a href="<?= base_url('index.php?c=user&a=create') ?>" class="btn btn-primary">
      <i data-lucide="user-plus"></i>
      <span>Tambah Pengguna Baru</span>
    </a>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>Daftar Pengguna Sistem</h3>
    <p>Semua pengguna dengan hak akses terhadap aplikasi SIMRS</p>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nama Lengkap</th>
          <th>Username</th>
          <th>Peran (Role)</th>
          <th>Unit / Bagian</th>
          <th>Status</th>
          <th>Dibuat Pada</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td>#<?= $user['id'] ?></td>
            <td><b><?= htmlspecialchars($user['full_name']) ?></b></td>
            <td><code><?= htmlspecialchars($user['username']) ?></code></td>
            <td>
              <span class="badge <?= in_array($user['role'], ['admin', 'Admin Sistem']) ? 'badge-primary' : 'badge-success' ?>">
                <i data-lucide="<?= in_array($user['role'], ['admin', 'Admin Sistem']) ? 'shield-check' : 'user' ?>" class="lucide-sm"></i>
                <span><?= htmlspecialchars($user['role_label'] ?? $user['role']) ?></span>
              </span>
            </td>
            <td><?= htmlspecialchars($user['unit'] ?? '-') ?></td>
            <td>
              <?php if ($user['is_active']): ?>
                <span class="badge badge-success">
                  <i data-lucide="check" class="lucide-sm"></i>
                  <span>Aktif</span>
                </span>
              <?php else: ?>
                <span class="badge badge-danger">
                  <i data-lucide="x" class="lucide-sm"></i>
                  <span>Nonaktif</span>
                </span>
              <?php endif; ?>
            </td>
            <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
            <td style="text-align: right;">
              <div style="display: inline-flex; gap: 6px;">
                <button type="button" class="btn btn-outline btn-sm" onclick="openResetModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>')">
                  <i data-lucide="key"></i>
                  <span>Reset Pass</span>
                </button>

                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                  <a href="<?= base_url('index.php?c=user&a=toggleStatus&id=' . $user['id']) ?>" 
                     class="btn <?= $user['is_active'] ? 'btn-danger' : 'btn-success' ?> btn-sm"
                     onclick="return confirm('Apakah Anda yakin ingin mengubah status aktif pengguna ini?')">
                    <i data-lucide="<?= $user['is_active'] ? 'user-x' : 'user-check' ?>"></i>
                    <span><?= $user['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></span>
                  </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Reset Password -->
<div id="resetModal" class="modal-backdrop hidden" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100;">
  <div style="background: #fff; width: 100%; max-width: 400px; border-radius: 14px; padding: 24px; box-shadow: var(--shadow-lg);">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; color: var(--blue-900);">
      <i data-lucide="key" class="lucide-lg"></i>
      <h3 style="margin: 0; font-size: 16px;">Reset Kata Sandi Pengguna</h3>
    </div>
    <p id="resetUsernameText" style="font-size: 13px; color: var(--muted); margin-bottom: 16px;"></p>

    <form method="POST" action="<?= base_url('index.php?c=user&a=resetPassword') ?>">
      <input type="hidden" id="resetUserId" name="user_id">
      <div class="form-group">
        <label class="form-label" for="new_password">Kata Sandi Baru</label>
        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimal 6 karakter" required>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
        <button type="button" class="btn btn-outline" onclick="closeResetModal()">Batal</button>
        <button type="submit" class="btn btn-primary">
          <i data-lucide="save"></i>
          <span>Simpan Kata Sandi</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openResetModal(userId, username) {
  document.getElementById('resetUserId').value = userId;
  document.getElementById('resetUsernameText').innerText = 'Mereset kata sandi untuk akun: ' + username;
  document.getElementById('resetModal').classList.remove('hidden');
  if (typeof lucide !== 'undefined') lucide.createIcons();
}
function closeResetModal() {
  document.getElementById('resetModal').classList.add('hidden');
}
</script>
