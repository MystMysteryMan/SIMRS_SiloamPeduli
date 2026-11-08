<?php
// app/views/layouts/sidebar.php - Navigasi Sidebar Utama SIMRS Siloam Peduli (Lucide Icons, Zero Emoji)

$currentController = $_GET['c'] ?? 'dashboard';
$currentAction     = $_GET['a'] ?? 'index';
$userRole          = strtolower($_SESSION['role'] ?? '');

$isAdmin        = in_array($userRole, ['admin', 'admin sistem']);
$isRegistrasi   = $isAdmin || str_contains($userRole, 'registrasi');
// Dokter klinis poliklinik / rawat inap / IGD (BUKAN dokter_radiologi)
$isDoctor       = $isAdmin || ($userRole === 'dokter' || ($userRole !== 'dokter_radiologi' && str_contains($userRole, 'dokter')));
// Dokter spesialis radiologi (spesifik instalasi radiologi & EMR)
$isRadDoctor    = $isAdmin || $userRole === 'dokter_radiologi';
$isRadiographer = $isAdmin || str_contains($userRole, 'radiografer');
$isNurse        = $isAdmin || str_contains($userRole, 'perawat') || str_contains($userRole, 'kepala_ruang');
$isClinical     = $isAdmin || $isDoctor || $isNurse;
$isOutpatient   = $isAdmin || $isDoctor || $isNurse || $isRegistrasi;
$isInpatient    = $isAdmin || $isDoctor || $isNurse || str_contains($userRole, 'rawat_inap');
$isNursing      = $isAdmin || $isNurse || $isDoctor;
$isLab          = $isAdmin || str_contains($userRole, 'analis') || str_contains($userRole, 'lab');
$isRad          = $isAdmin || $isRadiographer || $isRadDoctor;
$isCpoe         = $isAdmin || $isDoctor || $isNurse || str_contains($userRole, 'apoteker');
$isIgd          = $isAdmin || str_contains($userRole, 'triase') || str_contains($userRole, 'igd') || $isDoctor || $isNurse;
$isBilling      = $isAdmin || str_contains($userRole, 'kasir') || str_contains($userRole, 'billing');
$isPharmacy     = $isAdmin || str_contains($userRole, 'apoteker') || str_contains($userRole, 'farmasi');
$isWarehouse    = $isAdmin || str_contains($userRole, 'gudang') || str_contains($userRole, 'logistik');
$isReporting    = $isAdmin || str_contains($userRole, 'manajemen') || str_contains($userRole, 'direksi');
?>

<aside id="side">
  <div class="brand">
    <img class="brand-logo-img" src="<?= base_url('assets/img/logo.svg') ?>" alt="Logo Siloam Peduli">
    <div class="brand-text">
      <b>SILOAM PEDULI</b>
      <span>Sistem Informasi RS</span>
    </div>
  </div>

  <nav id="nav">
    <!-- SEKSI 1: UTAMA -->
    <div class="nav-label">UTAMA</div>
    <a href="<?= base_url('index.php?c=dashboard') ?>" class="nav-link <?= $currentController === 'dashboard' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="layout-dashboard"></i></span>
      <span class="tx">Dashboard</span>
    </a>

    <!-- SEKSI 2: PELAYANAN KLINIS -->
    <?php if ($isIgd || $isOutpatient || $isInpatient || $isNursing || $isClinical || $isCpoe || $isLab || $isRad): ?>
    <div class="nav-label">PELAYANAN KLINIS</div>

    <?php if ($isIgd): ?>
    <a href="<?= base_url('index.php?c=igd&a=index') ?>" class="nav-link <?= $currentController === 'igd' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="siren" style="color: #dc2626;"></i></span>
      <span class="tx">Gawat Darurat (IGD)</span>
    </a>
    <?php endif; ?>

    <?php if ($isOutpatient): ?>
    <a href="<?= base_url('index.php?c=outpatient&a=index') ?>" class="nav-link <?= $currentController === 'outpatient' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="stethoscope"></i></span>
      <span class="tx">Rawat Jalan (Poliklinik)</span>
    </a>
    <?php endif; ?>

    <?php if ($isInpatient): ?>
    <a href="<?= base_url('index.php?c=inpatient&a=index') ?>" class="nav-link <?= $currentController === 'inpatient' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="bed-double"></i></span>
      <span class="tx">Rawat Inap (Inpatient)</span>
    </a>
    <?php endif; ?>

    <?php if ($isNursing): ?>
    <a href="<?= base_url('index.php?c=nursing&a=index') ?>" class="nav-link <?= $currentController === 'nursing' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="heart-pulse"></i></span>
      <span class="tx">Nursing Station</span>
    </a>
    <?php endif; ?>

    <?php if ($isClinical || $isRadDoctor): ?>
    <a href="<?= base_url('index.php?c=emr&a=index') ?>" class="nav-link <?= $currentController === 'emr' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="file-text"></i></span>
      <span class="tx">Rekam Medis (EMR)</span>
    </a>
    <?php endif; ?>

    <?php if ($isCpoe): ?>
    <a href="<?= base_url('index.php?c=cpoe&a=index') ?>" class="nav-link <?= $currentController === 'cpoe' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="clipboard-list"></i></span>
      <span class="tx">Order Klinis (CPOE)</span>
    </a>
    <?php endif; ?>

    <?php if ($isLab): ?>
    <a href="<?= base_url('index.php?c=laboratory&a=index') ?>" class="nav-link <?= $currentController === 'laboratory' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="flask-conical"></i></span>
      <span class="tx">Laboratorium Diagnostik</span>
    </a>
    <?php endif; ?>

    <?php if ($isRad): ?>
    <a href="<?= base_url('index.php?c=radiology&a=index') ?>" class="nav-link <?= $currentController === 'radiology' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="film"></i></span>
      <span class="tx">Radiologi Imaging</span>
    </a>
    <?php endif; ?>
    <?php endif; ?>

    <!-- SEKSI 3: FARMASI & LOGISTIK GUDANG -->
    <?php if ($isPharmacy || $isWarehouse): ?>
    <div class="nav-label">FARMASI &amp; LOGISTIK</div>

    <?php if ($isPharmacy): ?>
    <a href="<?= base_url('index.php?c=pharmacy&a=index') ?>" class="nav-link <?= $currentController === 'pharmacy' && $currentAction !== 'medicines' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="pill"></i></span>
      <span class="tx">Pelayanan Farmasi</span>
    </a>
    <a href="<?= base_url('index.php?c=pharmacy&a=medicines') ?>" class="nav-link <?= $currentController === 'pharmacy' && $currentAction === 'medicines' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="boxes"></i></span>
      <span class="tx">Stok Obat Farmasi</span>
    </a>
    <?php endif; ?>

    <?php if ($isWarehouse): ?>
    <a href="<?= base_url('index.php?c=inventory&a=index') ?>" class="nav-link <?= $currentController === 'inventory' && $currentAction === 'index' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="warehouse"></i></span>
      <span class="tx">Gudang &amp; Persediaan</span>
    </a>
    <a href="<?= base_url('index.php?c=inventory&a=purchaseRequest') ?>" class="nav-link <?= $currentController === 'inventory' && $currentAction === 'purchaseRequest' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="shopping-cart"></i></span>
      <span class="tx">Purchase Request (PR)</span>
    </a>
    <a href="<?= base_url('index.php?c=inventory&a=goodsIn') ?>" class="nav-link <?= $currentController === 'inventory' && $currentAction === 'goodsIn' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="package-plus"></i></span>
      <span class="tx">Barang Masuk</span>
    </a>
    <a href="<?= base_url('index.php?c=inventory&a=goodsOut') ?>" class="nav-link <?= $currentController === 'inventory' && $currentAction === 'goodsOut' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="package-minus"></i></span>
      <span class="tx">Barang Keluar</span>
    </a>
    <a href="<?= base_url('index.php?c=inventory&a=stockMutation') ?>" class="nav-link <?= $currentController === 'inventory' && $currentAction === 'stockMutation' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="arrow-left-right"></i></span>
      <span class="tx">Mutasi Persediaan</span>
    </a>
    <?php endif; ?>
    <?php endif; ?>

    <!-- SEKSI 4: PENDAFTARAN & KEUANGAN -->
    <?php if ($isRegistrasi || $isBilling): ?>
    <div class="nav-label">PENDAFTARAN &amp; KEUANGAN</div>

    <?php if ($isRegistrasi || $isBilling): ?>
    <a href="<?= base_url('index.php?c=patient&a=index') ?>" class="nav-link <?= $currentController === 'patient' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="users"></i></span>
      <span class="tx">Pendaftaran Pasien</span>
    </a>
    <?php endif; ?>

    <?php if ($isRegistrasi): ?>
    <a href="<?= base_url('index.php?c=appointment&a=index') ?>" class="nav-link <?= $currentController === 'appointment' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="calendar"></i></span>
      <span class="tx">Antrean &amp; Janji Temu</span>
    </a>
    <?php endif; ?>

    <?php if ($isBilling): ?>
    <a href="<?= base_url('index.php?c=billing&a=index') ?>" class="nav-link <?= $currentController === 'billing' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="receipt"></i></span>
      <span class="tx">Billing &amp; Kasir</span>
    </a>
    <?php endif; ?>
    <?php endif; ?>

    <!-- SEKSI 5: EKSEKUTIF & PELAPORAN MANAJEMEN -->
    <?php if ($isReporting || $isAdmin): ?>
    <div class="nav-label">EKSEKUTIF &amp; PELAPORAN</div>

    <a href="<?= base_url('index.php?c=report&a=index') ?>" class="nav-link <?= $currentController === 'report' && $currentAction === 'index' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="pie-chart"></i></span>
      <span class="tx">Dashboard Eksekutif</span>
    </a>

    <a href="<?= base_url('index.php?c=report&a=reports') ?>" class="nav-link <?= $currentController === 'report' && $currentAction === 'reports' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="file-spreadsheet"></i></span>
      <span class="tx">Laporan Kustom RS</span>
    </a>

    <a href="<?= base_url('index.php?c=report&a=indicators') ?>" class="nav-link <?= $currentController === 'report' && $currentAction === 'indicators' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="target"></i></span>
      <span class="tx">Indikator Mutu RS</span>
    </a>

    <a href="<?= base_url('index.php?c=report&a=morbidity') ?>" class="nav-link <?= $currentController === 'report' && $currentAction === 'morbidity' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="bar-chart-3"></i></span>
      <span class="tx">10 Besar Morbiditas</span>
    </a>
    <?php endif; ?>

    <!-- SEKSI 6: PENGATURAN SISTEM -->
    <?php if ($isAdmin): ?>
    <div class="nav-label">PENGATURAN SISTEM</div>

    <a href="<?= base_url('index.php?c=user&a=index') ?>" class="nav-link <?= $currentController === 'user' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="user-cog"></i></span>
      <span class="tx">Pengguna &amp; Keamanan</span>
    </a>

    <a href="<?= base_url('index.php?c=master&a=index') ?>" class="nav-link <?= $currentController === 'master' ? 'active' : '' ?>">
      <span class="ic"><i data-lucide="database"></i></span>
      <span class="tx">Master Data &amp; Konfigurasi</span>
    </a>
    <?php endif; ?>
  </nav>

  <div class="foot">
    <a href="<?= base_url('index.php?c=auth&a=logout') ?>" class="btn-sidebar-logout" onclick="askLogout(event)" title="Keluar dari sistem SIMRS">
      <i data-lucide="log-out"></i>
      <span>Keluar dari Sistem</span>
    </a>
  </div>
</aside>
