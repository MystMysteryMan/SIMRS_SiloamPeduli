<?php
// app/views/pharmacy/medicines.php - Manajemen Stok Obat Farmasi (SOP/SIMRS/010/2026)
?>

<div class="content-header">
  <div>
    <h1 class="page-title">Manajemen Stok Obat Farmasi</h1>
    <p class="text-muted">Katalog formularium obat, monitoring sisa stok, peringatan masa kedaluwarsa &amp; penambahan stok.</p>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-secondary" onclick="openAddStockModal()">
      <i data-lucide="package-plus"></i>
      <span>Tambah Stok Obat Lama</span>
    </button>
    <button type="button" class="btn btn-primary" onclick="openCreateMedicineModal()">
      <i data-lucide="plus-circle"></i>
      <span>Daftar Obat Baru</span>
    </button>
  </div>
</div>

<!-- KARTU RINGKASAN STOK OBAT FARMASI (4 KPI SUMMARY CARDS) -->
<div class="summary-grid-4">
  <!-- 1. TOTAL OBAT -->
  <div class="summary-kpi-card">
    <div class="kpi-label">
      <span class="summary-dot blue"></span>
      <span>Total Obat</span>
    </div>
    <div class="kpi-num"><?= number_format($stats['total_medicines'] ?? 0) ?></div>
    <div class="kpi-sub">Item formularium terdaftar</div>
  </div>

  <!-- 2. STOK AMAN -->
  <div class="summary-kpi-card">
    <div class="kpi-label">
      <span class="summary-dot green"></span>
      <span>Stok Aman</span>
    </div>
    <div class="kpi-num" style="color: var(--green-600);"><?= number_format($stats['safe_stock_count'] ?? 0) ?></div>
    <div class="kpi-sub">Kuantitas di atas batas buffer</div>
  </div>

  <!-- 3. PERLU PERHATIAN -->
  <div class="summary-kpi-card">
    <div class="kpi-label">
      <span class="summary-dot orange"></span>
      <span>Perlu Perhatian</span>
    </div>
    <div class="kpi-num" style="color: #d97706;"><?= number_format($stats['low_stock_count'] ?? 0) ?></div>
    <div class="kpi-sub">Mendekati / &le; stok minimum</div>
  </div>

  <!-- 4. STOK HABIS -->
  <div class="summary-kpi-card">
    <div class="kpi-label">
      <span class="summary-dot red"></span>
      <span>Stok Habis</span>
    </div>
    <div class="kpi-num" style="color: var(--danger);"><?= number_format($stats['empty_stock_count'] ?? 0) ?></div>
    <div class="kpi-sub">Kuantitas fisik 0 di apotek</div>
  </div>
</div>

<!-- FILTER & PENCARIAN -->
<div class="card p-3 mb-4">
  <form method="GET" action="<?= base_url('index.php') ?>" class="d-flex gap-3 items-center flex-wrap">
    <input type="hidden" name="c" value="pharmacy">
    <input type="hidden" name="a" value="medicines">

    <div class="flex-1" style="min-width: 250px;">
      <div class="input-icon-wrapper">
        <i data-lucide="search" class="input-icon"></i>
        <input type="text" name="search" class="form-control" placeholder="Cari kode obat, nama obat, atau bentuk sediaan..." value="<?= htmlspecialchars($search) ?>">
      </div>
    </div>

    <div style="min-width: 170px;">
      <select name="stock_status" class="form-control" onchange="this.form.submit()">
        <option value="">Semua Kondisi Stok</option>
        <option value="available" <?= $stockStatus === 'available' ? 'selected' : '' ?>>Stok Aman (&gt; Min)</option>
        <option value="low" <?= $stockStatus === 'low' ? 'selected' : '' ?>>Stok Menipis (&le; Min)</option>
        <option value="empty" <?= $stockStatus === 'empty' ? 'selected' : '' ?>>Stok Kosong (0)</option>
      </select>
    </div>

    <div style="min-width: 170px;">
      <select name="expiry_status" class="form-control" onchange="this.form.submit()">
        <option value="">Semua Masa Kedaluwarsa</option>
        <option value="warning" <?= $expiryStatus === 'warning' ? 'selected' : '' ?>>Mendekati Kedaluwarsa (&le; 90 Hari)</option>
        <option value="expired" <?= $expiryStatus === 'expired' ? 'selected' : '' ?>>Sudah Kedaluwarsa</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">
      <i data-lucide="filter"></i>
      <span>Filter</span>
    </button>

    <?php if (!empty($search) || !empty($stockStatus) || !empty($expiryStatus)): ?>
      <a href="<?= base_url('index.php?c=pharmacy&a=medicines') ?>" class="btn btn-secondary" title="Reset filter">
        <i data-lucide="rotate-ccw"></i>
      </a>
    <?php endif; ?>
  </form>
</div>

<!-- TABEL DAFTAR OBAT -->
<div class="card overflow-hidden">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead>
        <tr>
          <th style="width: 110px;">Kode Obat</th>
          <th>Nama Obat</th>
          <th>Bentuk &amp; Satuan</th>
          <th>Lokasi Penyimpanan</th>
          <th class="text-center">Sisa Stok</th>
          <th class="text-center">Min. Stok</th>
          <th>Tanggal Kedaluwarsa</th>
          <th class="text-end">Harga Jual</th>
          <th class="text-end" style="width: 120px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($medicines)): ?>
          <tr>
            <td colspan="9" class="text-center py-5 text-muted">
              <i data-lucide="inbox" style="width: 48px; height: 48px; margin-bottom: 8px;"></i>
              <div>Tidak ada data obat yang ditemukan.</div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($medicines as $m): ?>
            <?php 
              $isLow = ($m['stock'] <= $m['min_stock'] && $m['stock'] > 0);
              $isEmpty = ($m['stock'] == 0);
              $isExpired = ($m['expiry_category'] === 'EXPIRED');
              $isExpWarning = ($m['expiry_category'] === 'WARNING');
            ?>
            <tr>
              <td>
                <span class="font-mono fw-semibold text-primary"><?= htmlspecialchars($m['code']) ?></span>
              </td>
              <td>
                <div class="fw-semibold text-dark"><?= htmlspecialchars($m['name']) ?></div>
              </td>
              <td>
                <div class="text-xs text-dark"><?= htmlspecialchars($m['form']) ?></div>
                <div class="text-xs text-muted"><?= htmlspecialchars($m['unit']) ?></div>
              </td>
              <td>
                <span class="text-xs badge badge-secondary"><?= htmlspecialchars($m['location'] ?? 'Gudang Utama') ?></span>
              </td>
              <td class="text-center">
                <?php if ($isEmpty): ?>
                  <span class="badge badge-danger">KOSONG (0)</span>
                <?php elseif ($isLow): ?>
                  <span class="badge badge-warning"><?= (int)$m['stock'] ?></span>
                <?php else: ?>
                  <span class="badge badge-success"><?= (int)$m['stock'] ?></span>
                <?php endif; ?>
              </td>
              <td class="text-center font-mono text-xs text-muted">
                <?= (int)$m['min_stock'] ?>
              </td>
              <td>
                <?php if ($m['expiry_date']): ?>
                  <div class="text-xs font-mono"><?= date('d/m/Y', strtotime($m['expiry_date'])) ?></div>
                  <?php if ($isExpired): ?>
                    <span class="badge badge-danger text-xs">KADALUWARSA</span>
                  <?php elseif ($isExpWarning): ?>
                    <span class="badge badge-warning text-xs">&le; 90 Hari</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-xs text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-end font-mono">
                Rp <?= number_format((float)$m['sell_price'], 0, ',', '.') ?>
              </td>
              <td class="text-end">
                <button type="button" class="btn btn-sm btn-secondary" 
                        onclick="openAddStockModal(<?= (int)$m['id'] ?>, '<?= htmlspecialchars(addslashes($m['name'])) ?>', <?= (int)$m['stock'] ?>)"
                        title="Tambah stok obat">
                  <i data-lucide="plus"></i>
                  <span>Stok</span>
                </button>
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
        Halaman <?= $page ?> dari <?= $totalPages ?> (Total <?= number_format($totalItems) ?> obat)
      </div>
      <div class="d-flex gap-1">
        <?php if ($page > 1): ?>
          <a href="<?= base_url('index.php?c=pharmacy&a=medicines&search=' . urlencode($search) . '&stock_status=' . urlencode($stockStatus) . '&expiry_status=' . urlencode($expiryStatus) . '&page=' . ($page - 1)) ?>" class="btn btn-sm btn-secondary">
            <i data-lucide="chevron-left"></i>
          </a>
        <?php endif; ?>

        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
          <a href="<?= base_url('index.php?c=pharmacy&a=medicines&search=' . urlencode($search) . '&stock_status=' . urlencode($stockStatus) . '&expiry_status=' . urlencode($expiryStatus) . '&page=' . $p) ?>" 
             class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="<?= base_url('index.php?c=pharmacy&a=medicines&search=' . urlencode($search) . '&stock_status=' . urlencode($stockStatus) . '&expiry_status=' . urlencode($expiryStatus) . '&page=' . ($page + 1)) ?>" class="btn btn-sm btn-secondary">
            <i data-lucide="chevron-right"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- MODAL TAMBAH STOK OBAT LAMA -->
<div id="modalAddStock" class="modal-backdrop" style="display: none;">
  <div class="modal-box">
    <div class="modal-header">
      <h3 class="modal-title d-flex items-center gap-2">
        <i data-lucide="package-plus" class="text-primary"></i>
        <span>Tambah Stok Obat Lama</span>
      </h3>
      <button type="button" class="btn-close" onclick="closeModal('modalAddStock')">&times;</button>
    </div>
    <form method="POST" action="<?= base_url('index.php?c=pharmacy&a=medicines') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="add_stock">
      <input type="hidden" id="addStockMedId" name="medicine_id" value="">

      <div class="modal-body">
        <div id="addStockSelectGroup" class="form-group mb-3" style="display: none;">
          <label class="form-label fw-bold">Pilih Obat dari Katalog: <span class="text-danger">*</span></label>
          <select id="selectMedicineAddStock" class="form-control" onchange="onSelectMedStock(this)">
            <option value="">-- Pilih Obat di Farmasi --</option>
            <?php foreach ($medicines as $m): ?>
              <option value="<?= $m['id'] ?>" data-name="<?= htmlspecialchars($m['name']) ?>" data-stock="<?= (int)$m['stock'] ?>">
                <?= htmlspecialchars($m['code']) ?> - <?= htmlspecialchars($m['name']) ?> (Sisa: <?= (int)$m['stock'] ?> <?= htmlspecialchars($m['unit']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="addStockTextGroup" class="form-group mb-3">
          <label class="form-label">Nama Obat:</label>
          <input type="text" id="addStockMedName" class="form-control" readonly style="background: #f1f5f9;">
        </div>

        <div class="form-group mb-3">
          <label class="form-label">Sisa Stok Fisik Saat Ini:</label>
          <input type="text" id="addStockCurrentStock" class="form-control font-mono" readonly style="background: #f1f5f9;">
        </div>

        <div class="form-group mb-3">
          <label class="form-label fw-bold">Jumlah Penambahan Stok: <span class="text-danger">*</span></label>
          <input type="number" name="quantity" class="form-control font-mono" min="1" value="10" required>
        </div>

        <div class="form-group mb-3">
          <label class="form-label">Catatan Sumber / Keterangan:</label>
          <input type="text" name="notes" class="form-control" placeholder="Misal: Penerimaan tambahan dari Gudang Pusat">
        </div>
      </div>

      <div class="modal-footer d-flex justify-end gap-2">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalAddStock')">Batal</button>
        <button type="submit" class="btn btn-primary">
          <i data-lucide="save"></i>
          <span>Simpan Penambahan</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL TAMBAH OBAT BARU -->
<div id="modalCreateMedicine" class="modal-backdrop" style="display: none;">
  <div class="modal-box" style="max-width: 600px;">
    <div class="modal-header">
      <h3 class="modal-title d-flex items-center gap-2">
        <i data-lucide="plus-circle" class="text-primary"></i>
        <span>Pendaftaran Master Obat Baru</span>
      </h3>
      <button type="button" class="btn-close" onclick="closeModal('modalCreateMedicine')">&times;</button>
    </div>
    <form method="POST" action="<?= base_url('index.php?c=pharmacy&a=medicines') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="create_medicine">

      <div class="modal-body">
        <div class="grid grid-2 gap-3 mb-3">
          <div class="form-group">
            <label class="form-label">Kode Obat: <span class="text-danger">*</span></label>
            <input type="text" name="code" class="form-control font-mono" placeholder="Misal: MED-099" required>
          </div>
          <div class="form-group">
            <label class="form-label">Nama Obat: <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="Nama generik / paten" required>
          </div>
        </div>

        <div class="grid grid-2 gap-3 mb-3">
          <div class="form-group">
            <label class="form-label">Bentuk Sediaan:</label>
            <select name="form" class="form-control">
              <option value="Tablet">Tablet</option>
              <option value="Kapsul">Kapsul</option>
              <option value="Sirup">Sirup</option>
              <option value="Injeksi">Injeksi / Vial</option>
              <option value="Salep">Salep / Krim</option>
              <option value="Tetes">Tetes</option>
              <option value="Cairan Infus">Cairan Infus</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Satuan:</label>
            <input type="text" name="unit" class="form-control" value="Strip" placeholder="Strip / Botol / Ampul">
          </div>
        </div>

        <div class="grid grid-3 gap-3 mb-3">
          <div class="form-group">
            <label class="form-label">Harga Jual (Rp): <span class="text-danger">*</span></label>
            <input type="number" name="sell_price" class="form-control font-mono" min="0" step="500" value="10000" required>
          </div>
          <div class="form-group">
            <label class="form-label">Stok Awal:</label>
            <input type="number" name="stock" class="form-control font-mono" min="0" value="50">
          </div>
          <div class="form-group">
            <label class="form-label">Min. Stok (Buffer):</label>
            <input type="number" name="min_stock" class="form-control font-mono" min="1" value="10">
          </div>
        </div>

        <div class="grid grid-2 gap-3 mb-3">
          <div class="form-group">
            <label class="form-label">Lokasi Penyimpanan:</label>
            <input type="text" name="location" class="form-control" value="Gudang Utama / Rak Farmasi">
          </div>
          <div class="form-group">
            <label class="form-label">Tanggal Kedaluwarsa:</label>
            <input type="date" name="expiry_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
          </div>
        </div>
      </div>

      <div class="modal-footer d-flex justify-end gap-2">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalCreateMedicine')">Batal</button>
        <button type="submit" class="btn btn-primary">
          <i data-lucide="save"></i>
          <span>Daftarkan Obat Baru</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddStockModal(id, name, currentStock) {
  if (id) {
    document.getElementById('addStockMedId').value = id;
    document.getElementById('addStockMedName').value = name;
    document.getElementById('addStockCurrentStock').value = currentStock;
    document.getElementById('addStockSelectGroup').style.display = 'none';
    document.getElementById('addStockTextGroup').style.display = 'block';
  } else {
    document.getElementById('addStockMedId').value = '';
    document.getElementById('addStockMedName').value = '';
    document.getElementById('addStockCurrentStock').value = '-';
    const sel = document.getElementById('selectMedicineAddStock');
    if (sel) sel.value = '';
    document.getElementById('addStockSelectGroup').style.display = 'block';
    document.getElementById('addStockTextGroup').style.display = 'none';
  }
  document.getElementById('modalAddStock').style.display = 'flex';
  if (typeof lucide !== 'undefined') lucide.createIcons();
}

function onSelectMedStock(sel) {
  const opt = sel.options[sel.selectedIndex];
  if (opt && opt.value) {
    document.getElementById('addStockMedId').value = opt.value;
    document.getElementById('addStockMedName').value = opt.getAttribute('data-name');
    document.getElementById('addStockCurrentStock').value = opt.getAttribute('data-stock');
  }
}

function openCreateMedicineModal() {
  document.getElementById('modalCreateMedicine').style.display = 'flex';
  if (typeof lucide !== 'undefined') lucide.createIcons();
}
</script>
