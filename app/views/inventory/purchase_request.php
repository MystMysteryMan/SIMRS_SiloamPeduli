<?php
// app/views/inventory/purchase_request.php - Pengajuan Pembelian / Purchase Request (SOP/SIMRS/014/2026)

$selectedItemId = (int)($_GET['item_id'] ?? 0);
?>

<div class="content-header">
  <div class="d-flex items-center gap-3">
    <a href="<?= base_url('index.php?c=inventory') ?>" class="btn btn-secondary btn-sm" title="Kembali ke stok gudang">
      <i data-lucide="arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title">Purchase Request (Pengajuan Pembelian)</h1>
      <p class="text-muted">Pengadaan persediaan barang medis &amp; obat ketika stok menipis dengan nomor unik PR-YYYYMMDD-XXXX.</p>
    </div>
  </div>
  <div>
    <button type="button" class="btn btn-primary" onclick="openCreatePRModal()">
      <i data-lucide="plus-circle"></i>
      <span>Buat Pengajuan PR Baru</span>
    </button>
  </div>
</div>

<!-- FILTER & SEARCH -->
<div class="card p-3 mb-4">
  <form method="GET" action="<?= base_url('index.php') ?>" class="d-flex gap-3 items-center flex-wrap">
    <input type="hidden" name="c" value="inventory">
    <input type="hidden" name="a" value="purchaseRequest">

    <div class="flex-1" style="min-width: 250px;">
      <div class="input-icon-wrapper">
        <i data-lucide="search" class="input-icon"></i>
        <input type="text" name="search" class="form-control" placeholder="Cari No. PR atau nama barang..." value="<?= htmlspecialchars($search) ?>">
      </div>
    </div>

    <div style="min-width: 170px;">
      <select name="status" class="form-control" onchange="this.form.submit()">
        <option value="">Semua Status PR</option>
        <option value="PENDING" <?= $status === 'PENDING' ? 'selected' : '' ?>>Diajukan (Pending)</option>
        <option value="APPROVED" <?= $status === 'APPROVED' ? 'selected' : '' ?>>Disetujui (Approved)</option>
        <option value="ORDERED" <?= $status === 'ORDERED' ? 'selected' : '' ?>>Dipesan ke Supplier</option>
        <option value="RECEIVED" <?= $status === 'RECEIVED' ? 'selected' : '' ?>>Telah Diterima</option>
        <option value="REJECTED" <?= $status === 'REJECTED' ? 'selected' : '' ?>>Ditolak (Rejected)</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">
      <i data-lucide="filter"></i>
      <span>Filter</span>
    </button>

    <?php if (!empty($search) || !empty($status)): ?>
      <a href="<?= base_url('index.php?c=inventory&a=purchaseRequest') ?>" class="btn btn-secondary" title="Reset filter">
        <i data-lucide="rotate-ccw"></i>
      </a>
    <?php endif; ?>
  </form>
</div>

<!-- TABEL DAFTAR PURCHASE REQUEST -->
<div class="card overflow-hidden">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead>
        <tr>
          <th style="width: 160px;">No. Dokumen PR</th>
          <th>Nama Barang &amp; Spesifikasi</th>
          <th class="text-center">Jumlah Kebutuhan</th>
          <th>Alasan / Catatan Pengadaan</th>
          <th>Diajukan Oleh</th>
          <th>Tanggal Pengajuan</th>
          <th class="text-center">Status</th>
          <th class="text-end" style="width: 140px;">Ubah Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($requests)): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <i data-lucide="clipboard-list" style="width: 48px; height: 48px; margin-bottom: 8px;"></i>
              <div>Belum ada dokumen Purchase Request yang terdaftar.</div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($requests as $pr): ?>
            <tr>
              <td>
                <span class="font-mono fw-bold text-primary"><?= htmlspecialchars($pr['pr_number']) ?></span>
              </td>
              <td>
                <div class="fw-semibold text-dark"><?= htmlspecialchars($pr['item_name']) ?></div>
              </td>
              <td class="text-center font-mono fw-bold text-base">
                <?= (int)$pr['quantity'] ?> <?= htmlspecialchars($pr['unit']) ?>
              </td>
              <td>
                <div class="text-xs text-muted"><?= htmlspecialchars($pr['notes'] ?: '-') ?></div>
              </td>
              <td>
                <div class="fw-semibold text-xs text-dark"><?= htmlspecialchars($pr['requester_name'] ?? 'Petugas Gudang') ?></div>
                <div class="text-xs text-muted"><?= htmlspecialchars($pr['requester_role'] ?? 'Gudang') ?></div>
              </td>
              <td class="text-xs text-muted font-mono">
                <?= date('d/m/Y H:i', strtotime($pr['created_at'])) ?>
              </td>
              <td class="text-center">
                <?php if ($pr['status'] === 'PENDING'): ?>
                  <span class="badge badge-warning">Diajukan</span>
                <?php elseif ($pr['status'] === 'APPROVED'): ?>
                  <span class="badge badge-primary">Disetujui</span>
                <?php elseif ($pr['status'] === 'ORDERED'): ?>
                  <span class="badge badge-info">Dipesan</span>
                <?php elseif ($pr['status'] === 'RECEIVED'): ?>
                  <span class="badge badge-success">Diterima</span>
                <?php else: ?>
                  <span class="badge badge-danger">Ditolak</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <form method="POST" action="<?= base_url('index.php?c=inventory&a=purchaseRequest') ?>" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="form_action" value="update_status">
                  <input type="hidden" name="pr_id" value="<?= $pr['id'] ?>">
                  <select name="status" class="form-control form-control-sm text-xs d-inline-block" style="width: auto;" onchange="this.form.submit()">
                    <option value="PENDING" <?= $pr['status'] === 'PENDING' ? 'selected' : '' ?>>Diajukan</option>
                    <option value="APPROVED" <?= $pr['status'] === 'APPROVED' ? 'selected' : '' ?>>Setujui</option>
                    <option value="ORDERED" <?= $pr['status'] === 'ORDERED' ? 'selected' : '' ?>>Pesan</option>
                    <option value="RECEIVED" <?= $pr['status'] === 'RECEIVED' ? 'selected' : '' ?>>Terima</option>
                    <option value="REJECTED" <?= $pr['status'] === 'REJECTED' ? 'selected' : '' ?>>Tolak</option>
                  </select>
                </form>
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
        Halaman <?= $page ?> dari <?= $totalPages ?> (Total <?= number_format($totalItems) ?> dokumen PR)
      </div>
      <div class="d-flex gap-1">
        <?php if ($page > 1): ?>
          <a href="<?= base_url('index.php?c=inventory&a=purchaseRequest&search=' . urlencode($search) . '&status=' . urlencode($status) . '&page=' . ($page - 1)) ?>" class="btn btn-sm btn-secondary">
            <i data-lucide="chevron-left"></i>
          </a>
        <?php endif; ?>

        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
          <a href="<?= base_url('index.php?c=inventory&a=purchaseRequest&search=' . urlencode($search) . '&status=' . urlencode($status) . '&page=' . $p) ?>" 
             class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="<?= base_url('index.php?c=inventory&a=purchaseRequest&search=' . urlencode($search) . '&status=' . urlencode($status) . '&page=' . ($page + 1)) ?>" class="btn btn-sm btn-secondary">
            <i data-lucide="chevron-right"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- MODAL FORM BUAT PURCHASE REQUEST BARU -->
<div id="modalCreatePR" class="modal-backdrop" style="<?= $selectedItemId > 0 ? 'display:flex;' : 'display:none;' ?>">
  <div class="modal-box" style="max-width: 550px;">
    <div class="modal-header">
      <h3 class="modal-title d-flex items-center gap-2">
        <i data-lucide="shopping-cart" class="text-primary"></i>
        <span>Pengajuan Pembelian Barang (Purchase Request)</span>
      </h3>
      <button type="button" class="btn-close" onclick="closeModal('modalCreatePR')">&times;</button>
    </div>
    <form method="POST" action="<?= base_url('index.php?c=inventory&a=purchaseRequest') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="create">

      <div class="modal-body">
        <div class="form-group mb-3">
          <label class="form-label">Pilih dari Katalog Barang / Obat: <span class="text-muted">(Opsional)</span></label>
          <select id="selectCatalogItem" name="item_id" class="form-control" onchange="onSelectItem(this)">
            <option value="">-- Ketik Nama Barang Bebas / Bukan di Katalog --</option>
            <?php foreach ($medicines as $m): ?>
              <option value="<?= $m['id'] ?>" 
                      data-name="<?= htmlspecialchars($m['name']) ?>" 
                      data-unit="<?= htmlspecialchars($m['unit']) ?>"
                      <?= $m['id'] == $selectedItemId ? 'selected' : '' ?>>
                <?= htmlspecialchars($m['code']) ?> - <?= htmlspecialchars($m['name']) ?> (Sisa stok: <?= $m['stock'] ?> <?= $m['unit'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group mb-3">
          <label class="form-label fw-bold">Nama Barang: <span class="text-danger">*</span></label>
          <input type="text" id="prItemName" name="item_name" class="form-control" placeholder="Nama obat / BMHP / logistik" required>
        </div>

        <div class="grid grid-2 gap-3 mb-3">
          <div class="form-group">
            <label class="form-label fw-bold">Jumlah Kebutuhan: <span class="text-danger">*</span></label>
            <input type="number" name="quantity" class="form-control font-mono" min="1" value="50" required>
          </div>
          <div class="form-group">
            <label class="form-label">Satuan:</label>
            <input type="text" id="prItemUnit" name="unit" class="form-control" value="Pcs" placeholder="Strip / Box / Botol">
          </div>
        </div>

        <div class="form-group mb-3">
          <label class="form-label">Alasan / Catatan Pengadaan:</label>
          <textarea name="notes" class="form-control text-sm" rows="3" placeholder="Misal: Stok di bawah batas minimum (buffer stock) untuk persiapan akhir bulan."></textarea>
        </div>
      </div>

      <div class="modal-footer d-flex justify-end gap-2">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalCreatePR')">Batal</button>
        <button type="submit" class="btn btn-primary">
          <i data-lucide="send"></i>
          <span>Kirim Pengajuan PR</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openCreatePRModal() {
  document.getElementById('modalCreatePR').style.display = 'flex';
  if (typeof lucide !== 'undefined') lucide.createIcons();
}
function onSelectItem(sel) {
  const opt = sel.options[sel.selectedIndex];
  if (opt.value) {
    document.getElementById('prItemName').value = opt.getAttribute('data-name') || '';
    document.getElementById('prItemUnit').value = opt.getAttribute('data-unit') || 'Pcs';
  }
}
window.addEventListener('DOMContentLoaded', () => {
  const sel = document.getElementById('selectCatalogItem');
  if (sel && sel.value) {
    onSelectItem(sel);
  }
});
</script>
