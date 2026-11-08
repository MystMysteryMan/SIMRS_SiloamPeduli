<?php
// app/views/auth/login.php - Tampilan Masuk SIMRS (Persis Sesuai Acuan Resmi Siloam Peduli)
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Masuk - SIMRS Siloam Peduli') ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/logo.svg') ?>">
  <link rel="alternate icon" type="image/png" href="<?= base_url('assets/img/logo.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css?v=2.2') ?>">
  <script src="<?= base_url('assets/js/lucide.min.js') ?>"></script>
  <style>
    /* Styling Khusus Modal Demo Akun */
    .demo-modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(4px);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    .demo-modal-backdrop.active {
      display: flex;
    }
    .demo-modal-box {
      background: #ffffff;
      border-radius: 16px;
      max-width: 520px;
      width: 100%;
      padding: 24px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
      max-height: 90vh;
      overflow-y: auto;
      border: 1px solid #e2e8f0;
    }
    .demo-role-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      margin-top: 14px;
    }
    .demo-role-btn {
      text-align: left;
      padding: 8px 12px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      background: #f8fafc;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .demo-role-btn:hover {
      background: #f0fdf4;
      border-color: #22c55e;
      transform: translateY(-1px);
    }
    .demo-role-btn b {
      display: block;
      font-size: 12px;
      color: #0f2f57;
    }
    .demo-role-btn span {
      font-size: 11px;
      color: #64748b;
      font-family: monospace;
    }
  </style>
</head>
<body>

<section id="login" aria-label="Halaman masuk">
  <!-- SISI KIRI: FORM LOGIN RESMI -->
  <div class="login-left">
    <div class="login-card">
      <div class="brand">
        <img class="brand-logo-img" src="<?= base_url('assets/img/logo.svg') ?>" alt="Logo Siloam Peduli">
        <div>
          <b>SIMRS Siloam Peduli</b>
          <span>Sistem Informasi Manajemen Rumah Sakit</span>
        </div>
      </div>

      <h1>Masuk ke sistem</h1>
      <p class="lead">Masuk dengan akun yang diberikan oleh rumah sakit.</p>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom: 16px; padding: 10px 14px; font-size: 12.5px; border-radius: 8px;">
          <i data-lucide="alert-circle" style="width: 16px; height: 16px;"></i>
          <div><?= htmlspecialchars($error) ?></div>
        </div>
      <?php endif; ?>

      <?php if (!empty($flash)): ?>
        <div class="alert alert-warning" style="margin-bottom: 16px; padding: 10px 14px; font-size: 12.5px; border-radius: 8px;">
          <i data-lucide="info" style="width: 16px; height: 16px;"></i>
          <div><?= htmlspecialchars($flash['text']) ?></div>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= base_url('index.php?c=auth&a=login') ?>">
        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
        </div>

        <div class="form-group" style="margin-bottom: 6px;">
          <label class="form-label" for="password">Password</label>
          <div style="position: relative;">
            <input type="password" id="password" name="password" class="form-control" style="padding-right: 86px;" placeholder="Masukkan password" required>
            <button type="button" id="togglePasswordBtn" onclick="togglePasswordVisibility()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); border: 0; background: none; color: #0284c7; font-size: 12.5px; font-weight: 600; cursor: pointer; padding: 4px;">
              Tampilkan
            </button>
          </div>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-bottom: 18px;">
          <a href="javascript:void(0)" onclick="openForgotModal()" style="color: #0284c7; font-size: 12px; font-weight: 600; text-decoration: none;">
            Lupa password?
          </a>
        </div>

        <div>
          <button type="submit" class="btn-submit">
            Masuk
          </button>
        </div>
      </form>

      <!-- LOGIN CEPAT (DROPDOWN & MODAL PILIHAN AKUN DEMO) -->
      <div style="margin-top: 18px; padding-top: 14px; border-top: 1px dashed #e2e8f0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
          <span style="font-size: 11.5px; font-weight: 600; color: #64748b; display: flex; align-items: center; gap: 4px;">
            <i data-lucide="zap" style="width: 12px; height: 12px; color: #eab308;"></i>
            <span>Login Cepat (Akun Demo)</span>
          </span>
          <button type="button" onclick="openDemoModal()" style="border: none; background: none; color: #0284c7; font-size: 11px; font-weight: 600; cursor: pointer; padding: 0;">
            Lihat Semua &rarr;
          </button>
        </div>

        <select id="quickLoginSelect" onchange="applyQuickDemo(this.value)" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 12.5px; color: #334155; background: #f8fafc; cursor: pointer; outline: none;">
          <option value="">-- Pilih Akun Demo untuk Masuk Cepat --</option>
          <option value="admin|admin123">Administrator Sistem (admin)</option>
          <option value="registrasi|registrasi123">Petugas Registrasi (registrasi)</option>
          <option value="dokter|dokter123">Dokter Poliklinik / DPJP (dokter)</option>
          <option value="drad|drad123">dr. Spesialis Radiologi (drad)</option>
          <option value="perawat|perawat123">Perawat Rawat Jalan (perawat)</option>
          <option value="triase|triase123">Petugas Triase IGD (triase)</option>
          <option value="apoteker|apoteker123">Apoteker &amp; Farmasi (apoteker)</option>
          <option value="analis|analis123">Analis Laboratorium (analis)</option>
          <option value="radiografer|radiografer123">Radiografer Imaging (radiografer)</option>
          <option value="kasir|kasir123">Kasir &amp; Billing (kasir)</option>
          <option value="gudang|gudang123">Petugas Gudang (gudang)</option>
          <option value="manajemen|manajemen123">Direksi &amp; Manajemen (manajemen)</option>
        </select>
      </div>

      <div style="margin-top: 18px; font-size: 11.5px; color: #64748b; line-height: 1.5; border-top: 1px solid #f1f5f9; padding-top: 14px;">
        Butuh bantuan? Hubungi <b>Unit TI &amp; SIMRS</b> di ext. <b>2100</b> (24 jam) atau datang ke Ruang SIMRS, Lantai 1.
      </div>
    </div>
  </div>

  <!-- SISI KANAN: HERO VISUAL RESMI SESUAI ACUAN DESAIN -->
  <div class="login-right">
    <div class="deco-circle-1"></div>
    <div class="deco-circle-2"></div>

    <div class="visual">
      <h2>Melayani dengan sepenuh<br>hati.</h2>
      <p>
        Rumah Sakit Siloam Peduli berkomitmen memberikan pelayanan kesehatan yang aman, cepat, dan penuh empati bagi setiap pasien dan keluarganya.
      </p>

      <div class="values">
        <span>Keselamatan pasien</span>
        <span>Pelayanan 24 jam</span>
        <span>Tenaga kesehatan profesional</span>
      </div>
    </div>
  </div>
</section>

<!-- MODAL PILIHAN AKUN DEMO LENGKAP -->
<div id="demoModalBackdrop" class="demo-modal-backdrop" onclick="closeDemoModalOnBackdrop(event)">
  <div class="demo-modal-box">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
      <div>
        <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f2f57;">Pilih Akun Demo (1-Click Login)</h3>
        <p style="margin: 2px 0 0; font-size: 11.5px; color: #64748b;">Klik salah satu peran di bawah untuk mengisi form login secara otomatis.</p>
      </div>
      <button type="button" onclick="closeDemoModal()" style="border: 0; background: #f1f5f9; border-radius: 50%; width: 28px; height: 28px; font-weight: bold; cursor: pointer; color: #64748b;">&times;</button>
    </div>

    <div style="margin-top: 14px; font-size: 11.5px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Administrasi &amp; Penunjang Front Office</div>
    <div class="demo-role-grid">
      <button type="button" class="demo-role-btn" onclick="fillAndClose('admin','admin123')">
        <b>Admin Sistem</b>
        <span>admin / admin123</span>
      </button>
      <button type="button" class="demo-role-btn" onclick="fillAndClose('registrasi','registrasi123')">
        <b>Petugas Registrasi</b>
        <span>registrasi / registrasi123</span>
      </button>
      <button type="button" class="demo-role-btn" onclick="fillAndClose('kasir','kasir123')">
        <b>Kasir &amp; Billing</b>
        <span>kasir / kasir123</span>
      </button>
      <button type="button" class="demo-role-btn" onclick="fillAndClose('gudang','gudang123')">
        <b>Gudang Medis</b>
        <span>gudang / gudang123</span>
      </button>
    </div>

    <div style="margin-top: 14px; font-size: 11.5px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Pelayanan Medis &amp; Klinis</div>
    <div class="demo-role-grid">
      <button type="button" class="demo-role-btn" onclick="fillAndClose('dokter','dokter123')">
        <b>Dokter Poliklinik (DPJP)</b>
        <span>dokter / dokter123</span>
      </button>
      <button type="button" class="demo-role-btn" onclick="fillAndClose('perawat','perawat123')">
        <b>Perawat Rawat Jalan</b>
        <span>perawat / perawat123</span>
      </button>
      <button type="button" class="demo-role-btn" onclick="fillAndClose('triase','triase123')">
        <b>Petugas Triase IGD</b>
        <span>triase / triase123</span>
      </button>
      <button type="button" class="demo-role-btn" onclick="fillAndClose('manajemen','manajemen123')">
        <b>Direksi &amp; Manajemen RS</b>
        <span>manajemen / manajemen123</span>
      </button>
    </div>

    <div style="margin-top: 14px; font-size: 11.5px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Farmasi &amp; Penunjang Diagnostik</div>
    <div class="demo-role-grid">
      <button type="button" class="demo-role-btn" onclick="fillAndClose('apoteker','apoteker123')">
        <b>Apoteker (CPOE)</b>
        <span>apoteker / apoteker123</span>
      </button>
      <button type="button" class="demo-role-btn" onclick="fillAndClose('analis','analis123')">
        <b>Analis Laboratorium</b>
        <span>analis / analis123</span>
      </button>
      <button type="button" class="demo-role-btn" onclick="fillAndClose('radiografer','radiografer123')">
        <b>Radiografer Imaging</b>
        <span>radiografer / radiografer123</span>
      </button>
      <button type="button" class="demo-role-btn" onclick="fillAndClose('drad','drad123')">
        <b>dr. Spesialis Radiologi</b>
        <span>drad / drad123</span>
      </button>
    </div>

    <div style="margin-top: 18px; text-align: right;">
      <button type="button" class="btn btn-secondary" onclick="closeDemoModal()" style="font-size: 12px; padding: 6px 16px;">Tutup</button>
    </div>
  </div>
</div>

<script>
if (typeof lucide !== 'undefined') {
  lucide.createIcons();
}

function togglePasswordVisibility() {
  const pw = document.getElementById('password');
  const btn = document.getElementById('togglePasswordBtn');
  if (pw.type === 'password') {
    pw.type = 'text';
    btn.innerText = 'Sembunyikan';
  } else {
    pw.type = 'password';
    btn.innerText = 'Tampilkan';
  }
}

function applyQuickDemo(val) {
  if (!val) return;
  const parts = val.split('|');
  if (parts.length === 2) {
    document.getElementById('username').value = parts[0];
    document.getElementById('password').value = parts[1];
  }
}

function fillAndClose(u, p) {
  document.getElementById('username').value = u;
  document.getElementById('password').value = p;
  closeDemoModal();
}

function openDemoModal() {
  document.getElementById('demoModalBackdrop').classList.add('active');
}

function closeDemoModal() {
  document.getElementById('demoModalBackdrop').classList.remove('active');
}

function closeDemoModalOnBackdrop(e) {
  if (e.target.id === 'demoModalBackdrop') {
    closeDemoModal();
  }
}

function openForgotModal() {
  alert('Silakan hubungi Unit TI & SIMRS di ext. 2100 (24 Jam) atau kunjungi Ruang Server SIMRS Lantai 1 untuk melakukan reset kata sandi.');
}
</script>
</body>
</html>
