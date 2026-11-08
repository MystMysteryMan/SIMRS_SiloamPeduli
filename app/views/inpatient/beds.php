<?php
// app/views/inpatient/beds.php - Visual Okupansi Kamar & Bed Management
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=inpatient&a=index') ?>" class="btn btn-outline" style="margin-bottom: 12px;">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Rawat Inap</span>
  </a>

  <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
    <i data-lucide="layout-grid" style="color: var(--blue-700); width: 22px; height: 22px;"></i>
    <span>Matriks Okupansi Kamar &amp; Manajemen Tempat Tidur</span>
  </h1>
  <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
    Pemantauan visual real-time status keterisian tempat tidur di setiap ruangan dan kelas perawatan rumah sakit.
  </p>
</div>

<div class="grid-3" style="margin-bottom: 24px;">
  <?php foreach ($occupancyOverview as $room): 
    $total = (int)$room['total_beds'];
    $occ = (int)$room['occupied_beds'];
    $avail = (int)$room['available_beds'];
    $pct = $total > 0 ? round(($occ / $total) * 100) : 0;
  ?>
    <div class="card" style="margin: 0; padding: 20px; border-left: 4px solid var(--blue-600);">
      <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
          <span class="badge" style="background: var(--blue-50); color: var(--blue-800); font-size: 11px; margin-bottom: 4px;">
            <?= htmlspecialchars(str_replace('_', ' ', $room['class_type'])) ?>
          </span>
          <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--blue-900);">
            <?= htmlspecialchars($room['room_name']) ?>
          </h3>
          <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">
            Kode: <b><?= htmlspecialchars($room['room_code']) ?></b> • Rp <?= number_format($room['daily_rate'], 0, ',', '.') ?>/hari
          </div>
        </div>
        <div style="text-align: right;">
          <div style="font-size: 20px; font-weight: 800; color: <?= $pct > 80 ? '#dc2626' : 'var(--blue-900)' ?>;">
            <?= $pct ?>%
          </div>
          <div style="font-size: 10px; color: var(--muted); text-transform: uppercase;">Okupansi</div>
        </div>
      </div>

      <!-- Progress Bar -->
      <div style="margin: 16px 0 12px; background: #e2e8f0; border-radius: 99px; height: 8px; overflow: hidden;">
        <div style="width: <?= $pct ?>%; height: 100%; background: <?= $pct > 80 ? '#dc2626' : ($pct > 50 ? '#0284c7' : '#16a34a') ?>;"></div>
      </div>

      <div style="display: flex; justify-content: space-between; font-size: 12px;">
        <div>Total: <b><?= $total ?> Bed</b></div>
        <div style="color: #dc2626;">Terisi: <b><?= $occ ?> Bed</b></div>
        <div style="color: #16a34a;">Tersedia: <b><?= $avail ?> Bed</b></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Daftar Detail Bed Tersedia -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 16px 20px; border-bottom: 1px solid var(--border);">
    <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--blue-900);">
      Daftar Tempat Tidur Siap Ditempati (Available Beds: <?= count($availableBeds) ?>)
    </h3>
  </div>

  <table class="table" style="width: 100%; margin: 0;">
    <thead>
      <tr>
        <th>Ruangan</th>
        <th>Kelas Perawatan</th>
        <th>Nomor Bed</th>
        <th>Tarif Harian</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($availableBeds)): ?>
        <tr>
          <td colspan="5" style="text-align: center; padding: 30px; color: var(--muted);">
            Seluruh tempat tidur saat ini terisi penuh.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($availableBeds as $b): ?>
          <tr>
            <td><b><?= htmlspecialchars($b['room_name']) ?></b> (<?= htmlspecialchars($b['room_code']) ?>)</td>
            <td><?= htmlspecialchars(str_replace('_', ' ', $b['class_type'])) ?></td>
            <td><span class="badge" style="background: #e0f2fe; color: #0369a1; font-weight: 700;">Bed <?= htmlspecialchars($b['bed_number']) ?></span></td>
            <td>Rp <?= number_format($b['daily_rate'], 0, ',', '.') ?></td>
            <td><span class="badge" style="background: #dcfce7; color: #15803d;">TERSEDIA</span></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

