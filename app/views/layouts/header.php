<?php
// app/views/layouts/header.php - Header Layout Template
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'SIMRS Siloam Peduli') ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/logo.svg') ?>">
  <link rel="alternate icon" type="image/png" href="<?= base_url('assets/img/logo.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css?v=2.2') ?>">
  <script src="<?= base_url('assets/js/lucide.min.js') ?>"></script>
  <?php
    $currentUserId = (int)($_SESSION['user_id'] ?? 0);
    $cookieRead = isset($_COOKIE['simrs_notifs_read_' . $currentUserId]);
  ?>
  <script>
    (function() {
      try {
        var uid = <?= json_encode((string)$currentUserId) ?>;
        window.__CURRENT_USER_ID = uid;
        if (localStorage.getItem('simrs_notifs_read_' + uid) === '1' || document.cookie.indexOf('simrs_notifs_read_' + uid + '=1') !== -1) {
          document.documentElement.classList.add('notifs-read');
        }
      } catch(e) {}
    })();
  </script>
  <style>
    html.notifs-read #bellN { display: none !important; }
    html.notifs-read .notif-item { opacity: 0.8; }
  </style>
</head>
<body>
<div id="app">
  <?php require __DIR__ . '/sidebar.php'; ?>
  <div id="sidebarOverlay" class="sidebar-overlay"></div>

  <div id="main">
    <header id="top">
      <button id="menuBtn" type="button" title="Toggle Sidebar (Buka/Tutup)" aria-label="Toggle Sidebar">
        <i data-lucide="menu"></i>
      </button>
      <div id="ctx">
        <b><?= htmlspecialchars($pageTitle ?? 'SIMRS Siloam Peduli') ?></b>
        <span>Sistem Informasi Manajemen Rumah Sakit - Versi Terpadu</span>
      </div>

      <div class="top-actions">
        <!-- Lonceng Notifikasi & Pengingat Sesuai Peran -->
        <button class="bell" id="bell" type="button" aria-label="Notifikasi &amp; Pengingat" onclick="toggleNotificationPanel(event)" title="Notifikasi &amp; Pengingat">
          <i data-lucide="bell" style="width: 18px; height: 18px;"></i>
          <span class="bell-badge" id="bellN" style="<?= $cookieRead ? 'display: none;' : '' ?>">1</span>
        </button>

        <!-- Panel Notifikasi Popover -->
        <div id="notificationPanel" class="notification-panel hidden" onclick="event.stopPropagation()">
          <div class="notif-header">
            <div>
              <b>Notifikasi &amp; Pengingat</b>
              <span class="notif-sub" id="notifSub"><?= $cookieRead ? '0 belum dibaca &bull; Seluruh pengingat telah ditandai' : '1 belum dibaca &bull; Peran: ' . htmlspecialchars($u['role_label'] ?? $u['role'] ?? 'Staff') ?></span>
            </div>
            <button type="button" class="notif-btn-clear" onclick="markAllNotificationsRead(event)">Tandai semua dibaca</button>
          </div>
          <div class="notif-list" id="notifList">
            <?php
              $currRole = strtolower($_SESSION['role'] ?? '');
              $notifItems = [];

              if (str_contains($currRole, 'registrasi')) {
                $notifItems[] = [
                  'type' => 'warn',
                  'icon' => 'calendar',
                  'title' => 'Antrean Pendaftaran Pasien',
                  'desc' => 'Verifikasi identitas pasien & kartu penjamin pada antrean pendaftaran poliklinik.',
                  'time' => 'Baru saja',
                  'link' => base_url('index.php?c=appointment&a=index')
                ];
                $notifItems[] = [
                  'type' => 'succ',
                  'icon' => 'user-check',
                  'title' => 'Master Pasien & No. RM',
                  'desc' => 'Pencarian data rekam medis & pendaftaran pasien baru siap digunakan.',
                  'time' => '15 menit yang lalu',
                  'link' => base_url('index.php?c=patient&a=index')
                ];
              } elseif (str_contains($currRole, 'apoteker') || str_contains($currRole, 'farmasi')) {
                $notifItems[] = [
                  'type' => 'warn',
                  'icon' => 'pill',
                  'title' => 'Resep Masuk Menunggu Telaah',
                  'desc' => 'Terdapat resep obat CPOE baru yang membutuhkan telaah administratif & dispensing.',
                  'time' => '10 menit yang lalu',
                  'link' => base_url('index.php?c=pharmacy&a=index')
                ];
                $notifItems[] = [
                  'type' => 'dan',
                  'icon' => 'alert-triangle',
                  'title' => 'Peringatan Stok Obat Menipis',
                  'desc' => 'Sebagian item obat di apotek mendekati atau berada di bawah batas minimum.',
                  'time' => '1 jam yang lalu',
                  'link' => base_url('index.php?c=pharmacy&a=medicines')
                ];
              } elseif (str_contains($currRole, 'dokter')) {
                $notifItems[] = [
                  'type' => 'succ',
                  'icon' => 'stethoscope',
                  'title' => 'Pasien Poliklinik Menunggu',
                  'desc' => 'Pasien telah selesai verifikasi 2-ID & asesmen perawat, siap diperiksa dokter.',
                  'time' => '5 menit yang lalu',
                  'link' => base_url('index.php?c=outpatient&a=index')
                ];
                $notifItems[] = [
                  'type' => 'warn',
                  'icon' => 'file-text',
                  'title' => 'Rekam Medis (EMR) Aktif',
                  'desc' => 'Buka antrean rekam medis terpadu poliklinik spesialis.',
                  'time' => '30 menit yang lalu',
                  'link' => base_url('index.php?c=emr&a=index')
                ];
              } elseif (str_contains($currRole, 'perawat')) {
                $notifItems[] = [
                  'type' => 'warn',
                  'icon' => 'heart-pulse',
                  'title' => 'Asesmen Keperawatan Menunggu',
                  'desc' => 'Pengkajian tanda vital dan keluhan awal pasien antrean poliklinik.',
                  'time' => '5 menit yang lalu',
                  'link' => base_url('index.php?c=outpatient&a=index')
                ];
                $notifItems[] = [
                  'type' => 'succ',
                  'icon' => 'activity',
                  'title' => 'Dokumentasi Asuhan Terpadu',
                  'desc' => 'Observasi tanda vital berkala dan catatan keperawatan bangsal.',
                  'time' => '20 menit yang lalu',
                  'link' => base_url('index.php?c=nursing&a=index')
                ];
              } elseif (str_contains($currRole, 'gudang') || str_contains($currRole, 'logistik')) {
                $notifItems[] = [
                  'type' => 'warn',
                  'icon' => 'shopping-cart',
                  'title' => 'Purchase Request (PR) Menunggu',
                  'desc' => 'Terdapat permintaan pengadaan barang dari unit pelayanan untuk diverifikasi.',
                  'time' => '25 menit yang lalu',
                  'link' => base_url('index.php?c=inventory&a=purchaseRequest')
                ];
                $notifItems[] = [
                  'type' => 'succ',
                  'icon' => 'warehouse',
                  'title' => 'Penerimaan Barang Masuk',
                  'desc' => 'Monitoring persediaan fisik gudang logistik farmasi dan alkes.',
                  'time' => '1 jam yang lalu',
                  'link' => base_url('index.php?c=inventory&a=goodsIn')
                ];
              } elseif (str_contains($currRole, 'kasir') || str_contains($currRole, 'billing')) {
                $notifItems[] = [
                  'type' => 'warn',
                  'icon' => 'receipt',
                  'title' => 'Tagihan Pending Menunggu Pembayaran',
                  'desc' => 'Faktur rincian biaya pelayanan rawat jalan siap dilunasi kasir.',
                  'time' => '15 menit yang lalu',
                  'link' => base_url('index.php?c=billing&a=index')
                ];
                $notifItems[] = [
                  'type' => 'succ',
                  'icon' => 'shield-check',
                  'title' => 'Klaim Asuransi & BPJS',
                  'desc' => 'Verifikasi kelengkapan berkas klaim penjamin asuransi.',
                  'time' => '45 menit yang lalu',
                  'link' => base_url('index.php?c=billing&a=claims')
                ];
              } elseif (str_contains($currRole, 'triase') || str_contains($currRole, 'igd')) {
                $notifItems[] = [
                  'type' => 'dan',
                  'icon' => 'siren',
                  'title' => 'Antrean Pasien IGD',
                  'desc' => 'Monitoring berkala dan pengkajian triase ESI gawat darurat aktif.',
                  'time' => 'Baru saja',
                  'link' => base_url('index.php?c=igd&a=index')
                ];
              } elseif (str_contains($currRole, 'analis') || str_contains($currRole, 'lab')) {
                $notifItems[] = [
                  'type' => 'warn',
                  'icon' => 'flask-conical',
                  'title' => 'Order Pemeriksaan Lab Masuk',
                  'desc' => 'Permintaan pengujian darah & kimia klinik menunggu pengambilan sampel.',
                  'time' => '10 menit yang lalu',
                  'link' => base_url('index.php?c=laboratory&a=index')
                ];
              } elseif (str_contains($currRole, 'rad')) {
                $notifItems[] = [
                  'type' => 'warn',
                  'icon' => 'film',
                  'title' => 'Order Radiologi Baru',
                  'desc' => 'Pemeriksaan citra X-Ray / Rontgen menunggu pelaksanaan tindakan.',
                  'time' => '15 menit yang lalu',
                  'link' => base_url('index.php?c=radiology&a=index')
                ];
              } elseif (str_contains($currRole, 'manajemen') || str_contains($currRole, 'direksi') || str_contains($currRole, 'admin')) {
                $notifItems[] = [
                  'type' => 'succ',
                  'icon' => 'pie-chart',
                  'title' => 'Laporan Eksekutif Diperbarui',
                  'desc' => 'Indikator kinerja rumah sakit, BOR, dan tren kunjungan telah diperbarui.',
                  'time' => 'Hari ini',
                  'link' => base_url('index.php?c=report&a=index')
                ];
              } else {
                $notifItems[] = [
                  'type' => 'succ',
                  'icon' => 'activity',
                  'title' => 'Sistem Terintegrasi Normal',
                  'desc' => 'Seluruh layanan rumah sakit aktif dan database cloud terhubung.',
                  'time' => 'Hari ini',
                  'link' => base_url('index.php?c=dashboard')
                ];
              }
            ?>
            <?php foreach ($notifItems as $i => $item): ?>
              <a href="<?= $item['link'] ?>" class="notif-item <?= $i > 0 ? 'read' : '' ?>" onclick="handleNotifItemClick(event)">
                <div class="notif-icon <?= $item['type'] ?>">
                  <i data-lucide="<?= $item['icon'] ?>" style="width: 16px; height: 16px;"></i>
                </div>
                <div class="notif-content">
                  <b><?= htmlspecialchars($item['title']) ?></b>
                  <p><?= htmlspecialchars($item['desc']) ?></p>
                  <time><?= htmlspecialchars($item['time']) ?></time>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="prof-badge">
          <div class="avatar"><?= strtoupper(substr($u['full_name'] ?? 'U', 0, 1)) ?></div>
          <div class="who">
            <b><?= htmlspecialchars($u['full_name'] ?? 'Pengguna') ?></b>
            <span><?= htmlspecialchars($u['role_label'] ?? $u['role'] ?? 'Staff') ?> <?= !empty($u['unit']) ? '• ' . htmlspecialchars($u['unit']) : '' ?></span>
          </div>
        </div>

        <button type="button" class="btn-out" onclick="askLogout(event)" aria-label="Keluar">
          <i data-lucide="log-out" style="width: 14px; height: 14px;"></i>
          <span>Keluar</span>
        </button>
      </div>
    </header>

    <div id="stage">
      <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'warning') ?>">
          <?php if ($flash['type'] === 'danger'): ?>
            <i data-lucide="alert-triangle"></i>
          <?php elseif ($flash['type'] === 'success'): ?>
            <i data-lucide="check-circle"></i>
          <?php else: ?>
            <i data-lucide="info"></i>
          <?php endif; ?>
          <div><?= htmlspecialchars($flash['text']) ?></div>
        </div>
      <?php endif; ?>
