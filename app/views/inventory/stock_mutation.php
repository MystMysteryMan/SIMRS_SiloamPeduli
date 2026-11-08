<?php
// app/views/inventory/stock_mutation.php - Mutasi Persediaan Antar-Lokasi & Buku Mutasi (SOP/SIMRS/014/2026)
?>

<div class="content-header">
  <div class="d-flex items-center gap-3">
    <a href="<?= base_url('index.php?c=inventory') ?>" class="btn btn-secondary btn-sm" title="Kembali ke stok gudang">
      <i data-lucide="arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title">Mutasi Stok Persediaan &amp; Buku Mutasi</h1>
      <p class="text-muted">Perpindahan barang antar-depo penyimpanan dan audit trail riwayat arus barang masuk, keluar, dan mutasi.</p>
    </div>
  </div>
  <div>
    <button type="button" class="btn btn-primary" onclick="openMutateModal()">
      <i data-lucide="arrow-left-right"></i>
      <span>Form Mutasi Antar-Depo</span>
    </button>
  </div>
</div>

<!-- FILTER BUKU MUTASI -->
<div class="card p-3 mb-4">
  <form method="GET" action="<?= base_url('index.php') ?>" class="d-flex gap-3 items-center flex-wrap">
    <input type="hidden" name="c" value="inventory">
    <input type="hidden" name="a" value="stockMutation">

    <div class="flex-1" style="min-width: 250px;">
      <div class="input-icon-wrapper">
        <i data-lucide="search" class="input-icon"></i>
        <input type="text" name="search" class="form-control" placeholder="Cari No. Transaksi, nama barang, atau dokumen..." value="<?= htmlspecialchars($search) ?>">
      </div>
    </div>

    <div style="min-width: 170px;">
      <select name="type" class="form-control" onchange="this.form.submit()">
        <option value="">Semua Jenis Mutasi</option>
        <option value="MASUK" <?= $currentType === 'MASUK' ? 'selected' : '' ?>>Barang Masuk (MASUK)</option>
        <option value="KELUAR" <?= $currentType === 'KELUAR' ? 'selected' : '' ?>>Barang Keluar (KELUAR)</option>
        <option value="MUTASI" <?= $currentType === 'MUTASI' ? 'selected' : '' ?>>Transfer Antar-Depo (MUTASI)</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">
      <i data-lucide="filter"></i>
      <span>Filter</span>
    </button>

    <?php if (!empty($search) || !empty($currentType)): ?>
      <a href="<?= base_url('index.php?c=inventory&a=stockMutation') ?>" class="btn btn-secondary" title="Reset filter">
        <i data-lucide="rotate-ccw"></i>
      </a>
    <?php endif; ?>
  </form>
</div>

<!-- TABEL BUKU MUTASI RIWAYAT TRANSAKSI -->
<div class="card overflow-hidden">
  <div class="card-header p-3 border-bottom d-flex justify-between items-center bg-slate-light">
    <div class="fw-bold text-dark d-flex items-center gap-2">
      <i data-lucide="book-open" style="width: 16px; height: 16px;"></i>
      <span>Buku Mutasi Riwayat Persediaan (Transparan &amp; Audit Ready)</span>
    </div>
    <div class="text-xs text-muted">
      Total Tercatat: <b><?= number_format($totalItems) ?> Transaksi</b>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th style="width: 150px;">No. Transaksi</th>
          <th>Nama Barang</th>
          <th class="text-center">Jenis Transaksi</th>
          <th class="text-center">Jumlah</th>
          <th>Arus Perpindahan</th>
          <th>No. Dokumen / Surat</th>
          <th>Petugas Pelaksana</th>
          <th>Waktu Pencatatan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <i data-lucide="file-text" style="width: 48px; height: 48px; margin-bottom: 8px;"></i>
              <div>Belum ada transaksi di buku mutasi.</div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($transactions as $tx): ?>
            <tr>
              <td>
                <span class="font-mono text-xs fw-semibold text-primary"><?= htmlspecialchars($tx['transaction_number']) ?></span>
              </td>
              <td>
                <div class="fw-semibold text-dark text-xs"><?= htmlspecialchars($tx['item_name']) ?></div>
                <div class="text-xs text-muted"><?= htmlspecialchars($tx['item_code']) ?></div>
              </td>
              <td class="text-center">
                <?php if ($tx['transaction_type'] === 'MASUK'): ?>
                  <span class="badge badge-success">MASUK</span>
                <?php elseif ($tx['transaction_type'] === 'KELUAR'): ?>
                  <span class="badge badge-danger">KELUAR</span>
                <?php else: ?>
                  <span class="badge badge-info">MUTASI</span>
                <?php endif; ?>
              </td>
              <td class="text-center font-mono fw-bold">
                <?php if ($tx['transaction_type'] === 'MASUK'): ?>
                  <span class="text-success">+<?= (int)$tx['quantity'] ?> <?= htmlspecialchars($tx['item_unit']) ?></span>
                <?php elseif ($tx['transaction_type'] === 'KELUAR'): ?>
                  <span class="text-danger">-<?= (int)$tx['quantity'] ?> <?= htmlspecialchars($tx['item_unit']) ?></span>
                <?php else: ?>
                  <span class="text-primary"><?= (int)$tx['quantity'] ?> <?= htmlspecialchars($tx['item_unit']) ?></span>
                <?php endif; ?>
              </td>
              <td class="text-xs">
                <?php if ($tx['transaction_type'] === 'MASUK'): ?>
                  <span class="text-muted">Penerimaan &rarr; </span><b><?= htmlspecialchars($tx['to_location'] ?: 'Gudang') ?></b>
                <?php elseif ($tx['transaction_type'] === 'KELUAR'): ?>
                  <b><?= htmlspecialchars($tx['from_location'] ?: 'Gudang') ?></b> &rarr; <span class="text-primary"><?= htmlspecialchars($tx['to_location'] ?: 'Unit') ?></span>
                <?php else: ?>
                  <b><?= htmlspecialchars($tx['from_location']) ?></b> &rarr; <b><?= htmlspecialchars($tx['to_location']) ?></b>
                <?php endif; ?>
              </td>
              <td class="font-mono text-xs text-muted">
                <?= htmlspecialchars($tx['document_number'] ?: '-') ?>
              </td>
              <td class="text-xs">
                <?= htmlspecialchars($tx['officer_name'] ?? 'Petugas Gudang') ?>
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

  <!-- PAGINATION -->
  <?php if ($totalPages > 1): ?>
    <div class="card-footer d-flex justify-between items-center p-3 border-top">
      <div class="text-xs text-muted">
        Halaman <?= $page ?> dari <?= $totalPages ?> (Total <?= number_format($totalItems) ?> mutasi)
      </div>
      <div class="d-flex gap-1">
        <?php if ($page > 1): ?>
          <a href="<?= base_url('index.php?c=inventory&a=stockMutation&search=' . urlencode($search) . '&type=' . urlencode($currentType) . '&page=' . ($page - 1)) ?>" class="btn btn-sm btn-secondary">
            <i data-lucide="chevron-left"></i>
          </a>
        <?php endif; ?>

        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
          <a href="<?= base_url('index.php?c=inventory&a=stockMutation&search=' . urlencode($search) . '&type=' . urlencode($currentType) . '&page=' . $p) ?>" 
             class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="<?= base_url('index.php?c=inventory&a=stockMutation&search=' . urlencode($search) . '&type=' . urlencode($currentType) . '&page=' . ($page + 1)) ?>" class="btn btn-sm btn-secondary">
            <i data-lucide="chevron-right"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- MODAL FORM MUTASI ANTAR-LOKASI -->
<div id="modalMutate" class="modal-backdrop" style="display: none;">
  <div class="modal-box" style="max-width: 520px;">
    <div class="modal-header">
      <h3 class="modal-title d-flex items-center gap-2">
        <i data-lucide="arrow-left-right" class="text-primary"></i>
        <span>Form Mutasi Stok Antar-Depo</span>
      </h3>
      <button type="button" class="btn-close" onclick="closeModal('modalMutate')">&times;</button>
    </div>
    <form method="POST" action="<?= base_url('index.php?c=inventory&a=stockMutation') ?>">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="form-group mb-3">
          <label class="form-label fw-bold">Pilih Barang yang Dimutasi: <span class="text-danger">*</span></label>
          <select name="item_id" class="form-control" required>
            <option value="">-- Pilih Barang --</option>
            <?php foreach ($medicines as $m): ?>
              <option value="<?= $m['id'] ?>">
                <?= htmlspecialchars($m['code']) ?> - <?= htmlspecialchars($m['name']) ?> (Stok: <?= $m['stock'] ?> <?= $m['unit'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="grid grid-2 gap-3 mb-3">
          <div class="form-group">
            <label class="form-label fw-bold">Lokasi Asal: <span class="text-danger">*</span></label>
            <select name="from_location" class="form-control" required>
              <option value="Gudang Utama">Gudang Utama</option>
              <option value="Apotek Rawat Jalan">Apotek Rawat Jalan</option>
              <option value="Depo Farmasi Rawat Inap">Depo Farmasi Rawat Inap</option>
              <option value="Depo Farmasi IGD">Depo Farmasi IGD</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label fw-bold">Lokasi Tujuan: <span class="text-danger">*</span></label>
            <select name="to_location" class="form-control" required>
              <option value="Apotek Rawat Jalan">Apotek Rawat Jalan</option>
              <option value="Depo Farmasi Rawat Inap">Depo Farmasi Rawat Inap</option>
              <option value="Depo Farmasi IGD">Depo Farmasi IGD</option>
              <option value="Gudang Utama">Gudang Utama</option>
            </select>
          </div>
        </div>

        <div class="form-group mb-3">
          <label class="form-label fw-bold">Jumlah Mutasi: <span class="text-danger">*</span></label>
          <input type="number" name="quantity" class="form-control font-mono" min="1" value="10" required>
        </div>

        <div class="form-group mb-3">
          <label class="form-label">Catatan Mutasi:</label>
          <input type="text" name="notes" class="form-control" placeholder="Misal: Penyesuaian persediaan depo IGD">
        </div>
      </div>

      <div class="modal-footer d-flex justify-end gap-2">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalMutate')">Batal</button>
        <button type="submit" class="btn btn-primary">
          <i data-lucide="check"></i>
          <span>Simpan Mutasi</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openMutateModal() {
  document.getElementById('modalMutate').style.display = 'flex';
  if (typeof lucide !== 'undefined') lucide.createIcons();
}
</script>
