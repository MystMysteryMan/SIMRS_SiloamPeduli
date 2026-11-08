<?php
// app/views/cpoe/index.php - CPOE / Order Management Dashboard
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="clipboard-list" style="color: var(--blue-600); width: 22px; height: 22px;"></i>
      <span>CPOE (Computerized Physician Order Entry)</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Sentralisasi penerbitan order klinis: E-Resep Farmasi, Instruksi Tindakan Keperawatan, dan Pelacakan Status Pelayanan Terpadu.
    </p>
  </div>
  <div style="display: flex; gap: 8px;">
    <?php if (in_array($_SESSION['role'], ['admin', 'dokter'])): ?>
      <a href="<?= base_url('index.php?c=cpoe&a=create') ?>" class="btn btn-primary">
        <i data-lucide="plus-circle"></i>
        <span>Buat Order Baru</span>
      </a>
    <?php endif; ?>
    <?php if (in_array($_SESSION['role'], ['admin', 'apoteker'])): ?>
      <a href="<?= base_url('index.php?c=cpoe&a=pharmacyQueue') ?>" class="btn btn-outline" style="border-color: #10b981; color: #047857;">
        <i data-lucide="pill"></i>
        <span>Antrean Farmasi (<?= $stats['pending_pharmacy'] ?>)</span>
      </a>
    <?php endif; ?>
    <a href="<?= base_url('index.php?c=cpoe&a=index') ?>" class="btn btn-outline">
      <i data-lucide="refresh-cw"></i>
      <span>Segarkan</span>
    </a>
  </div>
</div>

<!-- KPI Cards -->
<div class="grid-4" style="margin-bottom: 20px;">
  <div class="stat-card">
    <div class="top">
      <span>Total Order Hari Ini</span>
    </div>
    <div class="num"><?= $stats['total_today'] ?></div>
    <div class="desc">
      <span class="badge badge-primary"><?= $stats['total_today'] ?> Order</span>
      <span>terdaftar sistem</span>
    </div>
  </div>
  <div class="stat-card" style="border-left: 3px solid #10b981;">
    <div class="top">
      <span>Menunggu Farmasi</span>
    </div>
    <div class="num" style="color: #047857;"><?= $stats['pending_pharmacy'] ?></div>
    <div class="desc">
      <span class="badge badge-success"><?= $stats['pending_pharmacy'] ?> Resep</span>
      <span>siap disiapkan apoteker</span>
    </div>
  </div>
  <div class="stat-card" style="border-left: 3px solid #f59e0b;">
    <div class="top">
      <span>Menunggu Tindakan Perawat</span>
    </div>
    <div class="num" style="color: #b45309;"><?= $stats['pending_nursing'] ?></div>
    <div class="desc">
      <span class="badge badge-warning"><?= $stats['pending_nursing'] ?> Order</span>
      <span>asuhan keperawatan / infus</span>
    </div>
  </div>
  <div class="stat-card" style="border-left: 3px solid #3b82f6;">
    <div class="top">
      <span>Selesai Dilayani Hari Ini</span>
    </div>
    <div class="num" style="color: #1d4ed8;"><?= $stats['completed_today'] ?></div>
    <div class="desc">
      <span class="badge badge-info"><?= $stats['completed_today'] ?> Selesai</span>
      <span>obat &amp; tindakan medis</span>
    </div>
  </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 18px; padding: 14px 18px;">
  <form method="GET" action="<?= base_url('index.php') ?>" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
    <input type="hidden" name="c" value="cpoe">
    <input type="hidden" name="a" value="index">

    <div style="flex: 1; min-width: 220px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Cari No. Order / No. RM / Nama</label>
      <input type="text" name="search" class="form-control" placeholder="Ketik nomor order atau nama pasien..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
    </div>

    <div style="width: 170px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Jenis Pelayanan</label>
      <select name="order_type" class="form-control">
        <option value="">Semua Jenis</option>
        <option value="RESEP_OBAT" <?= ($filters['order_type'] ?? '') === 'RESEP_OBAT' ? 'selected' : '' ?>>Resep Obat (Farmasi)</option>
        <option value="TINDAKAN_MEDIS" <?= ($filters['order_type'] ?? '') === 'TINDAKAN_MEDIS' ? 'selected' : '' ?>>Tindakan Medis</option>
        <option value="LABORATORIUM" <?= ($filters['order_type'] ?? '') === 'LABORATORIUM' ? 'selected' : '' ?>>Laboratorium</option>
        <option value="RADIOLOGI" <?= ($filters['order_type'] ?? '') === 'RADIOLOGI' ? 'selected' : '' ?>>Radiologi</option>
      </select>
    </div>

    <div style="width: 150px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Prioritas</label>
      <select name="priority" class="form-control">
        <option value="">Semua</option>
        <option value="REGULER" <?= ($filters['priority'] ?? '') === 'REGULER' ? 'selected' : '' ?>>Reguler</option>
        <option value="CITO" <?= ($filters['priority'] ?? '') === 'CITO' ? 'selected' : '' ?>>CITO (Darurat)</option>
      </select>
    </div>

    <div style="width: 160px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Status Order</label>
      <select name="status" class="form-control">
        <option value="">Semua Status</option>
        <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>Pending (Menunggu)</option>
        <option value="PROCESS" <?= ($filters['status'] ?? '') === 'PROCESS' ? 'selected' : '' ?>>Diproses</option>
        <option value="COMPLETED" <?= ($filters['status'] ?? '') === 'COMPLETED' ? 'selected' : '' ?>>Selesai (Completed)</option>
        <option value="CANCELLED" <?= ($filters['status'] ?? '') === 'CANCELLED' ? 'selected' : '' ?>>Dibatalkan</option>
      </select>
    </div>

    <div>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="filter"></i>
        <span>Terapkan</span>
      </button>
      <a href="<?= base_url('index.php?c=cpoe&a=index') ?>" class="btn btn-outline" title="Reset Filter">
        <i data-lucide="rotate-ccw"></i>
      </a>
    </div>
  </form>
</div>

<!-- Order Table -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 14px 18px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
    <h3 style="margin: 0; font-size: 14px; font-weight: 600; color: var(--ink);">
      Daftar Pelacakan Order Klinis
    </h3>
    <span class="badge badge-muted"><?= count($orders) ?> Order Ditemukan</span>
  </div>

  <div style="overflow-x: auto;">
    <table class="table" style="margin: 0;">
      <thead>
        <tr>
          <th>No. Order</th>
          <th>Waktu Order</th>
          <th>Pasien &amp; Penjamin</th>
          <th>Jenis Layanan</th>
          <th>Rincian Item</th>
          <th>Dokter DPJP</th>
          <th>Prioritas</th>
          <th>Status</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr>
            <td colspan="9" style="text-align: center; padding: 36px; color: var(--muted);">
              <i data-lucide="inbox" style="width: 32px; height: 32px; margin-bottom: 8px; opacity: 0.5;"></i>
              <p style="margin: 0;">Belum ada order CPOE yang cocok dengan filter pencarian.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($orders as $ord): ?>
            <tr>
              <td>
                <span style="font-family: ui-monospace, monospace; font-weight: 700; color: var(--blue-900);">
                  <?= htmlspecialchars($ord['order_number']) ?>
                </span>
                <div style="font-size: 10.5px; color: var(--muted); margin-top: 2px;">
                  Unit: <?= htmlspecialchars($ord['visit_type']) ?>
                </div>
              </td>
              <td style="font-size: 12px; white-space: nowrap;">
                <?= date('d M Y, H:i', strtotime($ord['created_at'])) ?>
              </td>
              <td>
                <div style="font-weight: 600; color: var(--ink);">
                  <?= htmlspecialchars($ord['patient_name']) ?>
                </div>
                <div style="font-size: 11px; color: var(--muted); display: flex; gap: 6px; align-items: center; margin-top: 2px;">
                  <span style="font-family: ui-monospace, monospace;"><?= htmlspecialchars($ord['no_rm']) ?></span>
                  <span>&bull;</span>
                  <span class="badge badge-muted" style="padding: 1px 4px; font-size: 10px;"><?= htmlspecialchars($ord['insurance_type']) ?></span>
                </div>
              </td>
              <td>
                <?php if ($ord['order_type'] === 'RESEP_OBAT'): ?>
                  <span class="badge" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                    <i data-lucide="pill" class="lucide-sm"></i> Resep Obat
                  </span>
                <?php elseif ($ord['order_type'] === 'TINDAKAN_MEDIS'): ?>
                  <span class="badge" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
                    <i data-lucide="activity" class="lucide-sm"></i> Tindakan Medis
                  </span>
                <?php elseif ($ord['order_type'] === 'LABORATORIUM'): ?>
                  <span class="badge badge-info">
                    <i data-lucide="flask-conical" class="lucide-sm"></i> Laboratorium
                  </span>
                <?php else: ?>
                  <span class="badge badge-primary">
                    <i data-lucide="scan" class="lucide-sm"></i> Radiologi
                  </span>
                <?php endif; ?>
              </td>
              <td style="max-width: 240px;">
                <div style="font-size: 12px; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($ord['item_names_preview'] ?? '') ?>">
                  <?= htmlspecialchars($ord['item_names_preview'] ?? 'Tidak ada item') ?>
                </div>
                <div style="font-size: 10.5px; color: var(--muted); margin-top: 2px;">
                  <?= (int)$ord['item_count'] ?> item tercatat
                </div>
              </td>
              <td>
                <div style="font-weight: 500; font-size: 12.5px; color: var(--ink);">
                  <?= htmlspecialchars($ord['doctor_name']) ?>
                </div>
                <div style="font-size: 11px; color: var(--muted);">
                  <?= htmlspecialchars($ord['specialization'] ?? 'Spesialis') ?>
                </div>
              </td>
              <td>
                <?php if ($ord['priority'] === 'CITO'): ?>
                  <span class="badge badge-cito">CITO</span>
                <?php else: ?>
                  <span class="badge badge-reguler">REGULER</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($ord['status'] === 'PENDING'): ?>
                  <span class="badge badge-warning">
                    <i data-lucide="clock" class="lucide-sm"></i> Menunggu
                  </span>
                <?php elseif ($ord['status'] === 'PROCESS'): ?>
                  <span class="badge badge-info">
                    <i data-lucide="refresh-cw" class="lucide-sm"></i> Diproses
                  </span>
                <?php elseif ($ord['status'] === 'COMPLETED'): ?>
                  <span class="badge badge-success">
                    <i data-lucide="check-circle" class="lucide-sm"></i> Selesai
                  </span>
                <?php else: ?>
                  <span class="badge badge-danger">
                    <i data-lucide="x-circle" class="lucide-sm"></i> Batal
                  </span>
                <?php endif; ?>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <div style="display: inline-flex; gap: 4px;">
                  <!-- Action perawat eksekusi tindakan jika status pending & jenis tindakan -->
                  <?php if ($ord['order_type'] === 'TINDAKAN_MEDIS' && in_array($ord['status'], ['PENDING', 'PROCESS']) && in_array($_SESSION['role'], ['admin', 'perawat'])): ?>
                    <form method="POST" action="<?= base_url('index.php?c=cpoe&a=executeNurse') ?>" style="display:inline;" onsubmit="return confirm('Konfirmasi bahwa tindakan medis / keperawatan ini telah selesai dilaksanakan?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-success" title="Selesaikan Tindakan">
                        <i data-lucide="check"></i> Selesaikan
                      </button>
                    </form>
                  <?php endif; ?>

                  <!-- Action apoteker jika resep obat & status pending -->
                  <?php if ($ord['order_type'] === 'RESEP_OBAT' && in_array($ord['status'], ['PENDING', 'PROCESS']) && in_array($_SESSION['role'], ['admin', 'apoteker'])): ?>
                    <form method="POST" action="<?= base_url('index.php?c=cpoe&a=dispense') ?>" style="display:inline;" onsubmit="return confirm('Konfirmasi bahwa obat telah disiapkan & diserahkan ke pasien?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-primary" title="Dispense Obat">
                        <i data-lucide="check-check"></i> Serahkan Obat
                      </button>
                    </form>
                  <?php endif; ?>

                  <!-- Tombol Batalkan jika status pending -->
                  <?php if ($ord['status'] === 'PENDING' && in_array($_SESSION['role'], ['admin', 'dokter'])): ?>
                    <form method="POST" action="<?= base_url('index.php?c=cpoe&a=cancel') ?>" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan order klinis ini?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                      <input type="hidden" name="cancel_reason" value="Dibatalkan oleh Dokter/Admin">
                      <button type="submit" class="btn btn-sm btn-outline" style="color: var(--danger); border-color: var(--danger-line);" title="Batalkan Order">
                        <i data-lucide="x"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php include __DIR__ . '/../components/pagination.php'; ?>
</div>

