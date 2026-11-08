<?php
// app/views/inventory/index.php - Pengelolaan Persediaan & Gudang Logistik (SOP/SIMRS/014/2026)
?>

<div class="content-header">
  <div>
    <h1 class="page-title">Gudang &amp; Logistik Farmasi/Medis</h1>
    <p class="text-muted">Pemantauan persediaan barang, deteksi dini masa kedaluwarsa, dan perputaran logistik rumah sakit.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= base_url('index.php?c=inventory&a=purchaseRequest') ?>" class="btn btn-secondary">
      <i data-lucide="shopping-cart"></i>
      <span>Pengajuan PR</span>
    </a>
    <a href="<?= base_url('index.php?c=inventory&a=goodsIn') ?>" class="btn btn-primary">
      <i data-lucide="package-plus"></i>
      <span>Penerimaan Masuk</span>
    </a>
    <a href="<?= base_url('index.php?c=inventory&a=goodsOut') ?>" class="btn btn-secondary">
      <i data-lucide="package-minus"></i>
      <span>Pengeluaran Unit</span>
    </a>
  </div>
</div>

<!-- KARTU STATISTIK GUDANG -->
<div class="grid grid-4 mb-4">
  <div class="card p-3 stat-card">
    <div class="d-flex justify-between items-center">
      <div>
        <div class="text-xs text-muted text-uppercase fw-semibold">Total Item Barang</div>
        <div class="text-2xl fw-bold mt-1 text-primary"><?= number_format($stats['total_items'] ?? 0) ?></div>
      </div>
      <div class="stat-icon bg-blue-light text-primary">
        <i data-lucide="boxes"></i>
      </div>
    </div>
  </div>

  <div class="card p-3 stat-card">
    <div class="d-flex justify-between items-center">
      <div>
        <div class="text-xs text-muted text-uppercase fw-semibold">Stok Kritis / Menipis</div>
        <div class="text-2xl fw-bold mt-1 <?= ($stats['low_stock_items'] ?? 0) > 0 ? 'text-danger' : 'text-muted' ?>">
          <?= number_format($stats['low_stock_items'] ?? 0) ?>
        </div>
      </div>
      <div class="stat-icon bg-red-light text-danger">
        <i data-lucide="alert-triangle"></i>
      </div>
    </div>
  </div>

  <div class="card p-3 stat-card">
    <div class="d-flex justify-between items-center">
      <div>
        <div class="text-xs text-muted text-uppercase fw-semibold">Mendekati Expired (&le; 90 Hari)</div>
        <div class="text-2xl fw-bold mt-1 <?= ($stats['warning_expiry_items'] ?? 0) > 0 ? 'text-amber' : 'text-muted' ?>">
          <?= number_format($stats['warning_expiry_items'] ?? 0) ?>
        </div>
      </div>
      <div class="stat-icon bg-amber-light text-amber">
        <i data-lucide="clock"></i>
      </div>
    </div>
  </div>

  <div class="card p-3 stat-card">
    <div class="d-flex justify-between items-center">
      <div>
        <div class="text-xs text-muted text-uppercase fw-semibold">PR Menunggu Persetujuan</div>
        <div class="text-2xl fw-bold mt-1 text-info"><?= number_format($stats['pending_pr_count'] ?? 0) ?></div>
      </div>
      <div class="stat-icon bg-blue-light text-info">
        <i data-lucide="file-clock"></i>
      </div>
    </div>
  </div>
</div>

<!-- FILTER & PENCARIAN GUDANG -->
<div class="card p-3 mb-4">
  <form method="GET" action="<?= base_url('index.php') ?>" class="d-flex gap-3 items-center flex-wrap">
    <input type="hidden" name="c" value="inventory">
    <input type="hidden" name="a" value="index">

    <div class="flex-1" style="min-width: 250px;">
      <div class="input-icon-wrapper">
        <i data-lucide="search" class="input-icon"></i>
        <input type="text" name="search" class="form-control" placeholder="Cari kode barang, nama barang, atau lokasi rak..." value="<?= htmlspecialchars($search) ?>">
      </div>
    </div>

    <div style="min-width: 170px;">
      <select name="stock_status" class="form-control" onchange="this.form.submit()">
        <option value="">Semua Level Stok</option>
        <option value="safe" <?= $stockStatus === 'safe' ? 'selected' : '' ?>>Stok Aman (&gt; Buffer)</option>
        <option value="low" <?= $stockStatus === 'low' ? 'selected' : '' ?>>Stok Menipis (&le; Buffer)</option>
        <option value="empty" <?= $stockStatus === 'empty' ? 'selected' : '' ?>>Stok Kosong (0)</option>
      </select>
    </div>

    <div style="min-width: 180px;">
      <select name="expiry_status" class="form-control" onchange="this.form.submit()">
        <option value="">Semua Masa Kadaluwarsa</option>
        <option value="warning" <?= $expiryStatus === 'warning' ? 'selected' : '' ?>>Kritis (&le; 90 Hari)</option>
        <option value="expired" <?= $expiryStatus === 'expired' ? 'selected' : '' ?>>Sudah Expired</option>
        <option value="safe" <?= $expiryStatus === 'safe' ? 'selected' : '' ?>>Aman (&gt; 90 Hari)</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">
      <i data-lucide="filter"></i>
      <span>Filter</span>
    </button>

    <?php if (!empty($search) || !empty($stockStatus) || !empty($expiryStatus) || !empty($location)): ?>
      <a href="<?= base_url('index.php?c=inventory') ?>" class="btn btn-secondary" title="Reset filter">
        <i data-lucide="rotate-ccw"></i>
      </a>
    <?php endif; ?>
  </form>
</div>

<!-- TABEL STOK GUDANG -->
<div class="card overflow-hidden">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead>
        <tr>
          <th style="width: 110px;">Kode Barang</th>
          <th>Nama Barang &amp; Bentuk</th>
          <th>Lokasi Rak / Gudang</th>
          <th class="text-center">Sisa Stok</th>
          <th class="text-center">Buffer Min.</th>
          <th>Masa Kedaluwarsa</th>
          <th class="text-center">Peringatan Expired</th>
          <th class="text-end" style="width: 160px;">Aksi Cepat</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <i data-lucide="inbox" style="width: 48px; height: 48px; margin-bottom: 8px;"></i>
              <div>Tidak ada persediaan barang yang cocok dengan filter.</div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($items as $item): ?>
            <?php 
              $isLow = ($item['stock_level'] === 'LOW');
              $isEmpty = ($item['stock_level'] === 'EMPTY');
              $isExp = ($item['expiry_status'] === 'EXPIRED');
              $isWarn = ($item['expiry_status'] === 'WARNING');
            ?>
            <tr class="<?= $isExp ? 'bg-red-light' : ($isWarn ? 'bg-amber-light' : '') ?>">
              <td>
                <span class="font-mono fw-semibold text-primary"><?= htmlspecialchars($item['code']) ?></span>
              </td>
              <td>
                <div class="fw-semibold text-dark"><?= htmlspecialchars($item['name']) ?></div>
                <div class="text-xs text-muted"><?= htmlspecialchars($item['form'] ?? 'Item Medis') ?> &bull; Satuan: <?= htmlspecialchars($item['unit']) ?></div>
              </td>
              <td>
                <span class="badge badge-secondary text-xs">
                  <i data-lucide="map-pin" style="width: 12px; height: 12px;"></i>
                  <?= htmlspecialchars($item['location'] ?? 'Gudang Utama') ?>
                </span>
              </td>
              <td class="text-center">
                <?php if ($isEmpty): ?>
                  <span class="badge badge-danger">KOSONG</span>
                <?php elseif ($isLow): ?>
                  <span class="badge badge-warning fw-bold"><?= (int)$item['stock'] ?></span>
                <?php else: ?>
                  <span class="badge badge-success fw-bold"><?= (int)$item['stock'] ?></span>
                <?php endif; ?>
              </td>
              <td class="text-center font-mono text-xs text-muted">
                <?= (int)$item['min_stock'] ?> <?= htmlspecialchars($item['unit']) ?>
              </td>
              <td>
                <?php if ($item['expiry_date']): ?>
                  <div class="font-mono text-xs"><?= date('d/m/Y', strtotime($item['expiry_date'])) ?></div>
                  <div class="text-xs text-muted">
                    <?php if ((int)$item['days_until_expiry'] < 0): ?>
                      Lewat <?= abs((int)$item['days_until_expiry']) ?> hari
                    <?php else: ?>
                      Sisa <?= (int)$item['days_until_expiry'] ?> hari
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span class="text-xs text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if ($isExp): ?>
                  <span class="badge badge-danger">KEDALUWARSA</span>
                <?php elseif ($isWarn): ?>
                  <span class="badge badge-warning">&le; 90 HARI</span>
                <?php else: ?>
                  <span class="badge badge-success">AMAN</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="d-flex justify-end gap-1">
                  <a href="<?= base_url('index.php?c=inventory&a=purchaseRequest&item_id=' . $item['id']) ?>" 
                     class="btn btn-xs btn-secondary" title="Buat Pengajuan Pembelian PR">
                    <i data-lucide="shopping-cart" style="width: 13px; height: 13px;"></i>
                    <span>PR</span>
                  </a>
                  <a href="<?= base_url('index.php?c=inventory&a=goodsIn&item_id=' . $item['id']) ?>" 
                     class="btn btn-xs btn-primary" title="Terima Barang Masuk">
                    <i data-lucide="package-plus" style="width: 13px; height: 13px;"></i>
                    <span>Masuk</span>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- PAGINATION -->
  <?php if ($totalPages > 1): ?>
    <div class="card-footer d-flex justify-between items-center p-3 border-top">
      <div class="text-xs text-muted">
        Halaman <?= $page ?> dari <?= $totalPages ?> (Total <?= number_format($totalItems) ?> persediaan barang)
      </div>
      <div class="d-flex gap-1">
        <?php if ($page > 1): ?>
          <a href="<?= base_url('index.php?c=inventory&a=index&search=' . urlencode($search) . '&stock_status=' . urlencode($stockStatus) . '&expiry_status=' . urlencode($expiryStatus) . '&page=' . ($page - 1)) ?>" class="btn btn-sm btn-secondary">
            <i data-lucide="chevron-left"></i>
          </a>
        <?php endif; ?>

        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
          <a href="<?= base_url('index.php?c=inventory&a=index&search=' . urlencode($search) . '&stock_status=' . urlencode($stockStatus) . '&expiry_status=' . urlencode($expiryStatus) . '&page=' . $p) ?>" 
             class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="<?= base_url('index.php?c=inventory&a=index&search=' . urlencode($search) . '&stock_status=' . urlencode($stockStatus) . '&expiry_status=' . urlencode($expiryStatus) . '&page=' . ($page + 1)) ?>" class="btn btn-sm btn-secondary">
            <i data-lucide="chevron-right"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

