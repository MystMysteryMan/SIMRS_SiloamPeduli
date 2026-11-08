<?php
// app/views/inventory/goods_out.php - Pengeluaran Barang ke Unit & Validasi Stok (SOP/SIMRS/014/2026)

$selectedItemId = (int)($_GET['item_id'] ?? 0);
?>

<div class="content-header">
  <div class="d-flex items-center gap-3">
    <a href="<?= base_url('index.php?c=inventory') ?>" class="btn btn-secondary btn-sm" title="Kembali ke stok gudang">
      <i data-lucide="arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title">Pengeluaran Barang ke Unit (Goods Issue)</h1>
      <p class="text-muted">Penyaluran logistik &amp; obat ke Poliklinik, Bangsal Rawat Inap, dan IGD dengan validasi saldo stok.</p>
    </div>
  </div>
</div>

<div class="layout-split">
  <!-- FORM PENGELUARAN BARANG -->
  <div class="card p-4">
    <div class="d-flex items-center gap-2 mb-3 text-primary fw-bold text-xs text-uppercase">
      <i data-lucide="package-minus" style="width: 16px; height: 16px;"></i>
      <span>Form Input Pengeluaran</span>
    </div>

    <form method="POST" action="<?= base_url('index.php?c=inventory&a=goodsOut') ?>">
      <?= csrf_field() ?>
      <div class="form-group mb-3">
        <label class="form-label fw-bold">Pilih Barang di Gudang: <span class="text-danger">*</span></label>
        <select name="item_id" class="form-control" required>
          <option value="">-- Pilih Barang yang Dikeluarkan --</option>
          <?php foreach ($medicines as $m): ?>
            <option value="<?= $m['id'] ?>" <?= $m['id'] == $selectedItemId ? 'selected' : '' ?>>
              <?= htmlspecialchars($m['code']) ?> - <?= htmlspecialchars($m['name']) ?> (Stok: <?= $m['stock'] ?> <?= $m['unit'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group mb-3">
        <label class="form-label fw-bold">Unit / Instalasi Penerima: <span class="text-danger">*</span></label>
        <select name="unit_destination" class="form-control" required>
          <option value="Poliklinik Umum">Poliklinik Umum</option>
          <option value="Poliklinik Gigi">Poliklinik Gigi</option>
          <option value="Poliklinik Anak">Poliklinik Anak</option>
          <option value="Poliklinik Penyakit Dalam">Poliklinik Penyakit Dalam</option>
          <option value="Instalasi Gawat Darurat (IGD)">Instalasi Gawat Darurat (IGD)</option>
          <option value="Bangsal Mawar (Rawat Inap)">Bangsal Mawar (Rawat Inap)</option>
          <option value="Bangsal Melati (Rawat Inap)">Bangsal Melati (Rawat Inap)</option>
          <option value="Laboratorium">Laboratorium</option>
          <option value="Radiologi">Radiologi</option>
          <option value="Kamar Operasi (OK)">Kamar Operasi (OK)</option>
        </select>
      </div>

      <div class="grid grid-2 gap-3 mb-3">
        <div class="form-group">
          <label class="form-label fw-bold">Jumlah Keluar: <span class="text-danger">*</span></label>
          <input type="number" name="quantity" class="form-control font-mono" min="1" value="5" required>
        </div>
        <div class="form-group">
          <label class="form-label">No. Dokumen / SPB:</label>
          <input type="text" name="document_number" class="form-control font-mono" placeholder="REQ-001">
        </div>
      </div>

      <div class="form-group mb-4">
        <label class="form-label">Keterangan / Keperluan:</label>
        <input type="text" name="notes" class="form-control text-sm" placeholder="Misal: Permintaan stok mingguan bangsal">
      </div>

      <button type="submit" class="btn btn-danger w-100 py-2">
        <i data-lucide="package-minus"></i>
        <span>Keluarkan &amp; Potong Stok Gudang</span>
      </button>
    </form>
  </div>

  <!-- TABEL RIWAYAT PENGELUARAN BARANG TERBARU -->
  <div class="card overflow-hidden">
    <div class="card-header p-3 border-bottom d-flex justify-between items-center bg-slate-light">
      <div class="fw-bold text-dark d-flex items-center gap-2">
        <i data-lucide="history" style="width: 16px; height: 16px;"></i>
        <span>Riwayat Pengeluaran Barang ke Unit (10 Transaksi Terakhir)</span>
      </div>
      <a href="<?= base_url('index.php?c=inventory&a=stockMutation&type=KELUAR') ?>" class="text-xs text-primary fw-semibold">
        Lihat Semua Mutasi Keluar &rarr;
      </a>
    </div>

    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>No. Transaksi</th>
            <th>Nama Barang</th>
            <th class="text-center">Jumlah Keluar</th>
            <th>Unit Penerima</th>
            <th>No. Dokumen</th>
            <th>Waktu Pengeluaran</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentTransactions)): ?>
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i data-lucide="package" style="width: 40px; height: 40px; margin-bottom: 6px;"></i>
                <div>Belum ada transaksi pengeluaran barang.</div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentTransactions as $tx): ?>
              <tr>
                <td>
                  <span class="font-mono text-xs fw-semibold text-primary"><?= htmlspecialchars($tx['transaction_number']) ?></span>
                </td>
                <td>
                  <div class="fw-semibold text-dark text-xs"><?= htmlspecialchars($tx['item_name']) ?></div>
                  <div class="text-xs text-muted"><?= htmlspecialchars($tx['item_code']) ?></div>
                </td>
                <td class="text-center font-mono fw-bold text-danger">
                  -<?= (int)$tx['quantity'] ?> <?= htmlspecialchars($tx['item_unit']) ?>
                </td>
                <td>
                  <span class="badge badge-info text-xs"><?= htmlspecialchars($tx['to_location'] ?: 'Unit Pelayanan') ?></span>
                </td>
                <td class="font-mono text-xs text-muted">
                  <?= htmlspecialchars($tx['document_number'] ?: '-') ?>
                </td>
                <td class="text-xs text-muted font-mono">
                  <?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
