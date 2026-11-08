<?php
// app/views/dashboard/index.php - Stasiun Operasional SIMRS Siloam Peduli (UI Design Taste & Anti-Slop)
$userRole = strtolower($_SESSION['role'] ?? '');
$isClinical = in_array($userRole, ['admin', 'dokter', 'perawat']);
$isOutpatient = in_array($userRole, ['admin', 'dokter', 'perawat', 'registrasi']);
$isInpatient  = in_array($userRole, ['admin', 'petugas_rawat_inap', 'dokter', 'perawat', 'kepala_ruang']);
$isNursing    = in_array($userRole, ['admin', 'perawat', 'kepala_ruang', 'dokter']);
$isLab      = in_array($userRole, ['admin', 'analis_laboratorium', 'dokter']);
$isRad      = in_array($userRole, ['admin', 'radiografer', 'dokter_radiologi', 'dokter']);
$isIgd      = in_array($userRole, ['admin', 'triase', 'dokter', 'perawat']);
$isPharmacy = in_array($userRole, ['admin', 'apoteker', 'dokter']);
$isCashier  = in_array($userRole, ['admin', 'kasir']);

$daysIndo = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
];
$todayFullIndo = ($daysIndo[date('l')] ?? date('l')) . ', ' . format_date_id(date('Y-m-d'));
?>

<!-- STASIUN OPERASIONAL WORKSTATION -->
<div class="hello">
  <div class="hello-text">
    <div class="hello-badge">
      <span class="dot-live"></span>
      <span>STASIUN OPERASIONAL RS</span>
    </div>
    <h1>SIMRS Siloam Peduli &mdash; Stasiun Terpadu Pelayanan Medis</h1>
    <p>
      Pusat monitoring antrean poliklinik, IGD &amp; Triase CITO, EMR rekam medis, CPOE, laboratorium, radiologi, serta kasir &amp; billing asuransi terpadu.
    </p>
  </div>
  <div class="workstation-meta-card">
    <div class="meta-date-row">
      <i data-lucide="calendar"></i>
      <span><?= $todayFullIndo ?></span>
    </div>
    <div class="meta-chips-row">
      <div class="meta-chip">
        <i data-lucide="clock"></i>
        <span>Shift: <b>Pagi (07:00 &ndash; 15:00 WIB)</b></span>
      </div>
      <div class="meta-chip online">
        <span class="pulse-green"></span>
        <span>Status Sistem: <b>Online &bull; Terhubung</b></span>
      </div>
    </div>
  </div>
</div>

<!-- TOMBOL AKSI CEPAT DENGAN VERBA AKTIF -->
<div style="margin-bottom: 18px; display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap;">
  <?php if ($isIgd): ?>
    <a href="<?= base_url('index.php?c=igd&a=index') ?>" class="btn btn-outline" style="border-color: var(--rose-600); color: var(--rose-700);">
      <i data-lucide="siren" class="lucide-sm"></i>
      <span>Stasiun IGD (<?= ($igdStats['in_progress'] ?? 0) ?> Aktif)</span>
    </a>
  <?php endif; ?>

  <?php if ($isOutpatient): ?>
    <a href="<?= base_url('index.php?c=outpatient&a=index') ?>" class="btn btn-outline">
      <i data-lucide="stethoscope" class="lucide-sm"></i>
      <span>Rawat Jalan</span>
    </a>
  <?php endif; ?>

  <?php if ($isInpatient): ?>
    <a href="<?= base_url('index.php?c=inpatient&a=index') ?>" class="btn btn-outline">
      <i data-lucide="bed-double" class="lucide-sm"></i>
      <span>Rawat Inap</span>
    </a>
  <?php endif; ?>

  <?php if ($isNursing): ?>
    <a href="<?= base_url('index.php?c=nursing&a=index') ?>" class="btn btn-outline">
      <i data-lucide="heart-pulse" class="lucide-sm"></i>
      <span>Nursing Station</span>
    </a>
  <?php endif; ?>

  <?php if ($isPharmacy): ?>
    <a href="<?= base_url('index.php?c=cpoe&a=pharmacy') ?>" class="btn btn-outline">
      <i data-lucide="pill" class="lucide-sm"></i>
      <span>Farmasi CPOE (<?= ($cpoeStats['pending_pharmacy'] ?? 0) ?>)</span>
    </a>
  <?php endif; ?>

  <?php if ($isCashier): ?>
    <a href="<?= base_url('index.php?c=billing&a=index') ?>" class="btn btn-outline">
      <i data-lucide="wallet" class="lucide-sm"></i>
      <span>Kasir &amp; Billing (<?= ($billingStats['unpaid_count'] ?? 0) ?>)</span>
    </a>
  <?php endif; ?>

  <?php if ($isClinical): ?>
    <a href="<?= base_url('index.php?c=emr&a=index') ?>" class="btn btn-primary">
      <i data-lucide="stethoscope" class="lucide-sm"></i>
      <span>Antrean EMR Pasien</span>
    </a>
  <?php endif; ?>

  <?php if ($isLab): ?>
    <a href="<?= base_url('index.php?c=laboratory&a=index') ?>" class="btn btn-outline">
      <i data-lucide="flask-conical" class="lucide-sm"></i>
      <span>Laboratorium (<?= $labPendingCount ?>)</span>
    </a>
  <?php endif; ?>

  <?php if ($isRad): ?>
    <a href="<?= base_url('index.php?c=radiology&a=index') ?>" class="btn btn-outline">
      <i data-lucide="film" class="lucide-sm"></i>
      <span>Radiologi (<?= $radPendingCount ?>)</span>
    </a>
  <?php endif; ?>

  <a href="<?= base_url('index.php?c=patient&a=create') ?>" class="btn btn-outline">
    <i data-lucide="user-plus" class="lucide-sm"></i>
    <span>Pasien Baru</span>
  </a>
  <a href="<?= base_url('index.php?c=appointment&a=create') ?>" class="btn btn-success">
    <i data-lucide="calendar-plus" class="lucide-sm"></i>
    <span>Tiket Antrean</span>
  </a>
</div>

<!-- 4 KARTU METRIK KPI (TYPOGRAPHY-FIRST & TABULAR-NUMS) -->
<div class="grid-4">
  <!-- REGISTRASI PASIEN -->
  <a href="<?= base_url('index.php?c=patient&a=index') ?>" class="stat-card">
    <div class="top">
      <span>Total Pasien Terdaftar</span>
    </div>
    <div class="num"><?= number_format($patientStats['total_patients'] ?? 0) ?></div>
    <div class="desc">
      <span class="badge badge-success">+<?= number_format($patientStats['today_patients'] ?? 0) ?></span>
      <span>pasien baru hari ini</span>
    </div>
  </a>

  <!-- ANTREAN POLIKLINIK -->
  <a href="<?= base_url('index.php?c=appointment&a=index') ?>" class="stat-card">
    <div class="top">
      <span>Antrean Poli Hari Ini</span>
    </div>
    <div class="num"><?= number_format($apptStats['total_today'] ?? 0) ?></div>
    <div class="desc">
      <span class="badge badge-warning"><?= number_format($apptStats['waiting'] ?? 0) ?></span>
      <span>pasien sedang menunggu</span>
    </div>
  </a>

  <!-- DOKTER & FASILITAS BED -->
  <a href="<?= base_url('index.php?c=master&a=doctors') ?>" class="stat-card">
    <div class="top">
      <span>Dokter &amp; Ketersediaan Bed</span>
    </div>
    <div class="num"><?= number_format($activeDoctors ?? 0) ?> Dokter</div>
    <div class="desc">
      <span class="badge badge-primary"><?= number_format($bedStats['available_beds'] ?? 0) ?>/<?= number_format($bedStats['total_beds'] ?? 0) ?></span>
      <span>bed rawat inap siap pakai</span>
    </div>
  </a>

  <!-- USER & RBAC -->
  <a href="<?= base_url('index.php?c=user&a=index') ?>" class="stat-card">
    <div class="top">
      <span>Pengguna Sistem Aktif</span>
    </div>
    <div class="num"><?= number_format($userStats['active'] ?? 0) ?> User</div>
    <div class="desc">
      <span class="badge badge-muted"><?= number_format($userStats['total'] ?? 0) ?> Total</span>
      <span>akun terdaftar dalam sistem</span>
    </div>
  </a>
</div>

<!-- KARTU METRIK OPERASIONAL IGD, CPOE & BILLING (LAYANAN TERPADU) -->
<div class="grid-4" style="margin-top: 14px; margin-bottom: 22px;">
  <!-- IGD & TRIASE CITO -->
  <a href="<?= base_url('index.php?c=igd&a=index') ?>" class="stat-card" style="border-left: 3px solid var(--rose-500);">
    <div class="top">
      <span>IGD &amp; Triase CITO Hari Ini</span>
    </div>
    <div class="num" style="color: var(--rose-700);"><?= number_format($igdStats['total_today'] ?? 0) ?> Kunjungan</div>
    <div class="desc">
      <span class="badge badge-danger"><?= number_format($igdStats['red'] ?? 0) ?> Merah</span>
      <span class="badge badge-warning"><?= number_format($igdStats['yellow'] ?? 0) ?> Kuning</span>
      <span><?= number_format($igdStats['in_progress'] ?? 0) ?> dirawat</span>
    </div>
  </a>

  <!-- CPOE ORDER -->
  <a href="<?= base_url('index.php?c=cpoe&a=index') ?>" class="stat-card" style="border-left: 3px solid var(--blue-500);">
    <div class="top">
      <span>Order CPOE Terpadu</span>
    </div>
    <div class="num"><?= number_format($cpoeStats['total_today'] ?? 0) ?> Order</div>
    <div class="desc">
      <span class="badge badge-primary"><?= number_format($cpoeStats['completed_today'] ?? 0) ?> Selesai</span>
      <span>resep obat, lab &amp; rad</span>
    </div>
  </a>

  <!-- FARMASI DISPENSING -->
  <a href="<?= base_url('index.php?c=cpoe&a=pharmacy') ?>" class="stat-card" style="border-left: 3px solid var(--amber-500);">
    <div class="top">
      <span>Antrean Farmasi Resep</span>
    </div>
    <div class="num" style="color: var(--amber-700);"><?= number_format($cpoeStats['pending_pharmacy'] ?? 0) ?> Resep</div>
    <div class="desc">
      <span class="badge badge-warning">Menunggu</span>
      <span>telaah &amp; peracikan obat</span>
    </div>
  </a>

  <!-- KASIR & KLAIM ASURANSI -->
  <a href="<?= base_url('index.php?c=billing&a=index') ?>" class="stat-card" style="border-left: 3px solid var(--green-600);">
    <div class="top">
      <span>Kasir &amp; Klaim Hari Ini</span>
    </div>
    <div class="num" style="color: var(--green-700); font-size: 20px;">Rp <?= number_format($billingStats['revenue_today'] ?? 0, 0, ',', '.') ?></div>
    <div class="desc">
      <span class="badge badge-warning"><?= number_format($billingStats['unpaid_count'] ?? 0) ?> Tagihan</span>
      <span><?= number_format($billingStats['claims_pending'] ?? 0) ?> klaim asuransi</span>
    </div>
  </a>
</div>

<!-- ANTREAN HARI INI TERBARU -->
<div class="card">
  <div class="card-header">
    <div>
      <h3>Antrean Poliklinik Terkini (<?= date('d M Y') ?>)</h3>
      <p>5 Antrean teratas yang terdaftar pada sistem poliklinik rawat jalan</p>
    </div>
    <a href="<?= base_url('index.php?c=appointment&a=index') ?>" class="btn btn-outline btn-sm">
      <span>Semua Antrean</span>
      <i data-lucide="chevron-right" class="lucide-sm"></i>
    </a>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>No. Antrean</th>
          <th>No. RM</th>
          <th>Nama Pasien</th>
          <th>Poliklinik</th>
          <th>Dokter Spesialis</th>
          <th>Status</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($recentAppointments)): ?>
          <?php foreach ($recentAppointments as $row): ?>
            <tr>
              <td>
                <span class="badge badge-primary" style="font-size: 12px; font-weight: 700; padding: 3px 7px;">
                  <?= htmlspecialchars($row['queue_number']) ?>
                </span>
              </td>
              <td><code><?= htmlspecialchars($row['no_rm']) ?></code></td>
              <td><b><?= htmlspecialchars($row['patient_name']) ?></b></td>
              <td><?= htmlspecialchars($row['policlinic_name']) ?></td>
              <td><?= htmlspecialchars($row['doctor_name']) ?></td>
              <td>
                <?php
                $dispStatus = $row['status_label'] ?? $row['status'];
                $badgeClass = 'badge-muted';
                if (in_array($dispStatus, ['Menunggu', 'SCHEDULED'])) $badgeClass = 'badge-warning';
                if (in_array($dispStatus, ['Sedang Dilayani'])) $badgeClass = 'badge-primary';
                if (in_array($dispStatus, ['Selesai', 'COMPLETED'])) $badgeClass = 'badge-success';
                if (in_array($dispStatus, ['Batal', 'CANCELLED'])) $badgeClass = 'badge-danger';
                if (in_array($dispStatus, ['Dijadwal Ulang', 'RESCHEDULED'])) $badgeClass = 'badge-primary';
                ?>
                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($dispStatus) ?></span>
                <?php if (($dispStatus === 'Batal' || $dispStatus === 'CANCELLED' || $row['status'] === 'CANCELLED') && !empty($row['cancel_reason'])): ?>
                  <div style="font-size: 11px; color: #dc2626; margin-top: 3px;" title="<?= htmlspecialchars($row['cancel_reason']) ?>">
                    <span><?= htmlspecialchars($row['cancel_reason']) ?></span>
                  </div>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <a href="<?= base_url('index.php?c=appointment&a=printTicket&id=' . $row['id']) ?>" target="_blank" class="btn btn-outline btn-sm">
                  <i data-lucide="printer" class="lucide-sm"></i>
                  <span>Cetak Tiket</span>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 36px; color: var(--muted);">
              Belum ada antrean terdaftar untuk hari ini.
              <div style="margin-top: 10px;">
                <a href="<?= base_url('index.php?c=appointment&a=create') ?>" class="btn btn-primary btn-sm">
                  <i data-lucide="calendar-plus" class="lucide-sm"></i>
                  <span>Buat Janji Temu Sekarang</span>
                </a>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
