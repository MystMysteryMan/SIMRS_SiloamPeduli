<?php
// app/views/inventory/goods_in.php - Penerimaan Barang Masuk & Expiry Date (SOP/SIMRS/014/2026)

$selectedItemId = (int)($_GET['item_id'] ?? 0);
?>

<div class="content-header">
  <div class="d-flex items-center gap-3">
    <a href="<?= base_url('index.php?c=inventory') ?>" class="btn btn-secondary btn-sm" title="Kembali ke stok gudang">
      <i data-lucide="arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title">Penerimaan Barang Masuk (Goods Receipt)</h1>
      <p class="text-muted">Pencatatan barang logistik/obat tiba dari supplier, update saldo stok &amp; tanggal kedaluwarsa.</p>
    </div>
  </div>
</div>

<div class="layout-split">
  <!-- FORM PENERIMAAN BARANG MASUK -->
  <div class="card p-4">
    <div class="d-flex items-center gap-2 mb-3 text-primary fw-bold text-xs text-uppercase">
      <i data-lucide="package-plus" style="width: 16px; height: 16px;"></i>
      <span>Form Input Penerimaan</span>
    </div>

    <form method="POST" action="<?= base_url('index.php?c=inventory&a=goodsIn') ?>">
      <?= csrf_field() ?>
      <div class="form-group mb-3">
        <label class="form-label fw-bold">Pilih Barang / Obat: <span class="text-danger">*</span></label>
        <select name="item_id" class="form-control" required>
          <option value="">-- Pilih Barang di Gudang --</option>
          <?php foreach ($medicines as $m): ?>
            <option value="<?= $m['id'] ?>" <?= $m['id'] == $selectedItemId ? 'selected' : '' ?>>
              <?= htmlspecialchars($m['code']) ?> - <?= htmlspecialchars($m['name']) ?> (Stok: <?= $m['stock'] ?> <?= $m['unit'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group mb-3">
        <label class="form-label fw-bold">No. Surat Jalan / Dokumen Supplier: <span class="text-danger">*</span></label>
        <input type="text" name="document_number" class="form-control font-mono" placeholder="Misal: SJ-2026-00891" required>
      </div>

      <div class="grid grid-2 gap-3 mb-3">
        <div class="form-group">
          <label class="form-label fw-bold">Jumlah Masuk: <span class="text-danger">*</span></label>
          <input type="number" name="quantity" class="form-control font-mono" min="1" value="20" required>
        </div>
        <div class="form-group">
          <label class="form-label">Lokasi Rak / Gudang:</label>
          <input type="text" name="to_location" class="form-control" value="Gudang Utama">
        </div>
      </div>

      <div class="form-group mb-3">
        <label class="form-label fw-bold">Tanggal Kedaluwarsa (Expiry Date):</label>
        <input type="date" name="expiry_date" class="form-control font-mono" value="<?= date('Y-m-d', strtotime('+18 months')) ?>">
        <div class="text-xs text-muted mt-1">Sistem akan otomatis memperbarui tanggal kedaluwarsa barang.</div>
      </div>

      <div class="form-group mb-4">
        <label class="form-label">Keterangan / Catatan:</label>
        <input type="text" name="notes" class="form-control text-sm" placeholder="Misal: Batch A-12 dari PT Kimia Farma">
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2">
        <i data-lucide="save"></i>
        <span>Simpan Penerimaan Masuk</span>
      </button>
    </form>
  </div>

  <!-- TABEL RIWAYAT PENERIMAAN BARANG TERBARU -->
  <div class="card overflow-hidden">
    <div class="card-header p-3 border-bottom d-flex justify-between items-center bg-slate-light">
      <div class="fw-bold text-dark d-flex items-center gap-2">
        <i data-lucide="history" style="width: 16px; height: 16px;"></i>
        <span>Riwayat Penerimaan Barang Masuk Terbaru (10 Transaksi Terakhir)</span>
      </div>
      <a href="<?= base_url('index.php?c=inventory&a=stockMutation&type=MASUK') ?>" class="text-xs text-primary fw-semibold">
        Lihat Semua Mutasi Masuk &rarr;
      </a>
    </div>

    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>No. Transaksi</th>
            <th>Nama Barang</th>
            <th class="text-center">Jumlah Masuk</th>
            <th>No. Surat Jalan</th>
            <th>Lokasi Simpan</th>
            <th>Tgl Expired</th>
            <th>Waktu Terima</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentTransactions)): ?>
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                <i data-lucide="package" style="width: 40px; height: 40px; margin-bottom: 6px;"></i>
                <div>Belum ada transaksi penerimaan barang masuk.</div>
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
                <td class="text-center font-mono fw-bold text-success">
                  +<?= (int)$tx['quantity'] ?> <?= htmlspecialchars($tx['item_unit']) ?>
                </td>
                <td class="font-mono text-xs">
                  <?= htmlspecialchars($tx['document_number'] ?: '-') ?>
                </td>
                <td class="text-xs">
                  <span class="badge badge-secondary"><?= htmlspecialchars($tx['to_location'] ?: 'Gudang Utama') ?></span>
                </td>
                <td class="font-mono text-xs text-muted">
                  <?= $tx['expiry_date'] ? date('d/m/Y', strtotime($tx['expiry_date'])) : '-' ?>
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
