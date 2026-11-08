<?php
// app/views/pharmacy/dispense.php - Telaah Resep & Eksekusi Dispensing Obat (SOP/SIMRS/010/2026)

$isCompleted = ($prescription['status'] === 'COMPLETED');
$isSufficient = $stockCheck['is_sufficient'] ?? false;
$insufficientItems = $stockCheck['insufficient_items'] ?? [];
?>

<div class="content-header">
  <div class="d-flex items-center gap-3">
    <a href="<?= base_url('index.php?c=pharmacy') ?>" class="btn btn-secondary btn-sm" title="Kembali ke antrean">
      <i data-lucide="arrow-left"></i>
    </a>
    <div>
      <h1 class="page-title">Pengkajian Resep: <?= htmlspecialchars($prescription['order_number']) ?></h1>
      <p class="text-muted">Telaah administratif, farmasetis, validasi ketersediaan stok fisik real-time &amp; penyerahan obat.</p>
    </div>
  </div>
  <div>
    <?php if ($prescription['priority'] === 'CITO'): ?>
      <span class="badge badge-danger text-sm px-3 py-1 fw-bold">PRIORITAS CITO (EMERGENCY)</span>
    <?php else: ?>
      <span class="badge badge-secondary text-sm px-3 py-1">REGULER</span>
    <?php endif; ?>
  </div>
</div>

<!-- ALERT JIKA STOK KURANG -->
<?php if (!$isCompleted && !$isSufficient): ?>
  <div class="alert alert-danger mb-4 d-flex items-start gap-3 p-4">
    <div style="font-size: 24px; line-height: 1;"><i data-lucide="alert-octagon"></i></div>
    <div class="flex-1">
      <h4 class="fw-bold text-danger m-0 mb-1">Stok Obat Tidak Mencukupi!</h4>
      <p class="m-0 text-sm">
        Sebagian obat dalam resep ini memiliki sisa stok fisik yang lebih kecil dari kuantitas yang diresepkan. 
        Tombol <b>"Selesai &amp; Potong Stok"</b> ditangguhkan hingga stok ditambah dari gudang atau dokter melakukan penggantian terapi obat.
      </p>
      <div class="mt-2 text-xs fw-semibold">
        Daftar obat defisit: 
        <?php foreach ($insufficientItems as $ins): ?>
          <span class="badge badge-danger mr-1"><?= htmlspecialchars($ins['item_name']) ?> (Kurang: <?= $ins['deficit'] ?>)</span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- INFORMASI RESEP & PASIEN -->
<div class="grid grid-3 gap-4 mb-4">
  <!-- KARTU PASIEN -->
  <div class="card p-4">
    <div class="d-flex items-center gap-2 mb-3 text-primary fw-bold text-xs text-uppercase">
      <i data-lucide="user" style="width: 16px; height: 16px;"></i>
      <span>Identitas Pasien</span>
    </div>
    <div class="fw-bold text-lg text-dark"><?= htmlspecialchars($prescription['patient_name']) ?></div>
    <div class="text-sm text-muted mt-1 font-mono">No. RM: <?= htmlspecialchars($prescription['no_rm']) ?></div>
    
    <div class="mt-3 pt-3 border-top text-xs d-flex justify-between">
      <span class="text-muted">Jenis Kelamin:</span>
      <span class="fw-semibold"><?= htmlspecialchars($prescription['gender'] ?? '-') ?></span>
    </div>
    <div class="mt-1 text-xs d-flex justify-between">
      <span class="text-muted">Tanggal Lahir:</span>
      <span class="fw-semibold"><?= htmlspecialchars($prescription['birth_date'] ?? '-') ?></span>
    </div>
    <div class="mt-1 text-xs d-flex justify-between">
      <span class="text-muted">Penjamin:</span>
      <span class="badge badge-info"><?= htmlspecialchars($prescription['insurance_type'] ?? 'UMUM') ?></span>
    </div>

    <!-- PERINGATAN ALERGI PASIEN -->
    <div class="mt-3 pt-3 border-top">
      <div class="text-xs text-muted mb-1 fw-semibold">Riwayat Alergi:</div>
      <?php if (!empty($prescription['allergy_notes'])): ?>
        <div class="badge badge-danger text-xs p-2 d-flex items-center gap-1 w-100">
          <i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i>
          <span><?= htmlspecialchars($prescription['allergy_notes']) ?></span>
        </div>
      <?php else: ?>
        <span class="text-xs text-muted font-italic">Tidak ada riwayat alergi terdokumentasi.</span>
      <?php endif; ?>
    </div>
  </div>

  <!-- KARTU DOKTER & ASAL -->
  <div class="card p-4">
    <div class="d-flex items-center gap-2 mb-3 text-primary fw-bold text-xs text-uppercase">
      <i data-lucide="stethoscope" style="width: 16px; height: 16px;"></i>
      <span>Dokter Peresep &amp; Asal Pelayanan</span>
    </div>
    <div class="fw-bold text-lg text-dark"><?= htmlspecialchars($prescription['doctor_name']) ?></div>
    <div class="text-sm text-muted mt-1"><?= htmlspecialchars($prescription['specialization'] ?? 'Dokter Umum') ?></div>

    <div class="mt-3 pt-3 border-top text-xs d-flex justify-between">
      <span class="text-muted">Instalasi Asal:</span>
      <?php if ($prescription['visit_type'] === 'IGD'): ?>
        <span class="badge badge-danger">Gawat Darurat (IGD)</span>
      <?php elseif ($prescription['visit_type'] === 'RAWAT_INAP'): ?>
        <span class="badge badge-info">Rawat Inap</span>
      <?php else: ?>
        <span class="badge badge-primary">Rawat Jalan (Poli)</span>
      <?php endif; ?>
    </div>
    <div class="mt-1 text-xs d-flex justify-between">
      <span class="text-muted">Waktu Resep Dibuat:</span>
      <span class="fw-semibold"><?= date('d/m/Y H:i', strtotime($prescription['created_at'])) ?></span>
    </div>
    <div class="mt-1 text-xs d-flex justify-between">
      <span class="text-muted">Status Resep:</span>
      <?php if ($isCompleted): ?>
        <span class="badge badge-success">COMPLETED (Selesai)</span>
      <?php else: ?>
        <span class="badge badge-primary">PENDING (Antrean)</span>
      <?php endif; ?>
    </div>
  </div>

  <!-- KARTU KESIMPULAN TELAAH & AKSI -->
  <div class="card p-4 d-flex flex-column justify-between">
    <div>
      <div class="d-flex items-center gap-2 mb-3 text-primary fw-bold text-xs text-uppercase">
        <i data-lucide="shield-check" style="width: 16px; height: 16px;"></i>
        <span>Pemeriksaan Farmasi 5-Benar</span>
      </div>
      <div class="text-xs text-muted mb-3">
        Pastikan verifikasi Benar Pasien, Benar Obat, Benar Dosis, Benar Rute, dan Benar Waktu Pemberian sebelum penyerahan obat.
      </div>
      
      <div class="p-3 bg-slate-light rounded mb-3">
        <div class="text-xs text-muted">Status Ketersediaan Stok Fisik:</div>
        <?php if ($isSufficient): ?>
          <div class="text-sm fw-bold text-success mt-1 d-flex items-center gap-1">
            <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
            <span>Semua Obat Tersedia di Apotek</span>
          </div>
        <?php else: ?>
          <div class="text-sm fw-bold text-danger mt-1 d-flex items-center gap-1">
            <i data-lucide="x-circle" style="width: 16px; height: 16px;"></i>
            <span>Stok Obat Tidak Mencukupi!</span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!$isCompleted): ?>
      <form method="POST" action="<?= base_url('index.php?c=pharmacy&a=dispense') ?>" onsubmit="return confirm('Apakah Anda yakin telah menelaah dan menyiapkan obat ini? Stok obat akan dipotong secara atomik.');">
        <?= csrf_field() ?>
        <input type="hidden" name="order_id" value="<?= $prescription['id'] ?>">

        <div class="form-group mb-3">
          <label class="form-label text-xs fw-semibold">Catatan Apoteker / Konseling Obat:</label>
          <textarea name="notes" class="form-control text-xs" rows="2" placeholder="Catatan edukasi pasien, aturan minum khusus, atau substitusi..."></textarea>
        </div>

        <button type="submit" class="btn btn-success w-100 py-2 fw-bold" <?= !$isSufficient ? 'disabled' : '' ?>>
          <i data-lucide="check-check"></i>
          <span>Selesai &amp; Potong Stok</span>
        </button>
      </form>
    <?php else: ?>
      <div class="p-3 bg-green-light text-success rounded text-center">
        <i data-lucide="check-circle-2" style="width: 24px; height: 24px; margin-bottom: 4px;"></i>
        <div class="fw-bold text-sm">Resep Telah Diserahkan</div>
        <div class="text-xs">Stok obat telah dipotong dan rincian biaya diteruskan ke Billing kasir.</div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- TABEL RINCIAN OBAT DENGAN CEK STOK REAL-TIME & EDIT KUANTITAS -->
<div class="card overflow-hidden">
  <?php if (!$isCompleted): ?>
  <form method="POST" action="<?= base_url('index.php?c=pharmacy&a=updateQuantity') ?>" id="formUpdateQty">
    <?= csrf_field() ?>
    <input type="hidden" name="order_id" value="<?= $prescription['id'] ?>">
  <?php endif; ?>

  <div class="card-header p-3 border-bottom d-flex justify-between items-center bg-slate-light flex-wrap gap-2">
    <div class="fw-bold text-dark d-flex items-center gap-2">
      <i data-lucide="pill" style="width: 18px; height: 18px;"></i>
      <span>Daftar Obat yang Diresepkan (Rincian E-Resep CPOE)</span>
    </div>
    <div class="d-flex items-center gap-3">
      <span class="text-xs text-muted">
        Total Item: <b><?= count($prescription['items'] ?? []) ?> Macam Obat</b>
      </span>
      <?php if (!$isCompleted): ?>
        <button type="submit" class="btn btn-primary btn-sm" id="btnSaveQty" title="Simpan perubahan kuantitas ke database">
          <i data-lucide="save"></i>
          <span>Simpan Perubahan Jumlah</span>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th style="width: 40px;" class="text-center">No</th>
          <th>Nama Obat &amp; Bentuk Sediaan</th>
          <th>Dosis &amp; Rute</th>
          <th>Frekuensi &amp; Aturan Pakai</th>
          <th class="text-center" style="min-width: 130px;">Jumlah Diminta</th>
          <th class="text-center">Sisa Stok Apotek</th>
          <th class="text-center">Status Stok</th>
          <th class="text-end">Harga Satuan</th>
          <th class="text-end">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php 
          $grandTotal = 0;
          $num = 1;
        ?>
        <?php foreach ($prescription['items'] as $item): ?>
          <?php 
            $qty = (int)$item['quantity'];
            $stock = (int)($item['current_stock'] ?? 0);
            $subtotal = (float)($item['subtotal'] ?? 0);
            $grandTotal += $subtotal;
            $isItemSufficient = ($qty <= $stock);
          ?>
          <tr class="<?= !$isItemSufficient ? 'bg-red-light' : '' ?>">
            <td class="text-center text-muted"><?= $num++ ?></td>
            <td>
              <div class="fw-semibold text-dark"><?= htmlspecialchars($item['medicine_name'] ?? $item['item_name']) ?></div>
              <div class="text-xs text-muted">
                Kode: <span class="font-mono"><?= htmlspecialchars($item['medicine_code'] ?? '-') ?></span> &bull; 
                Sediaan: <?= htmlspecialchars($item['medicine_form'] ?? 'Tablet') ?> &bull; 
                Lokasi: <?= htmlspecialchars($item['medicine_location'] ?? 'Gudang Utama') ?>
              </div>
            </td>
            <td>
              <div class="fw-semibold text-xs"><?= htmlspecialchars($item['dosage'] ?? '-') ?></div>
              <div class="text-xs text-muted">Rute: <?= htmlspecialchars($item['route'] ?? 'Oral') ?></div>
            </td>
            <td>
              <div class="fw-semibold text-xs text-primary"><?= htmlspecialchars($item['frequency'] ?? '-') ?></div>
              <div class="text-xs text-muted font-italic"><?= htmlspecialchars($item['instructions'] ?? 'Sesuai petunjuk dokter') ?></div>
            </td>
            <td class="text-center">
              <?php if (!$isCompleted): ?>
                <div style="display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                  <input type="number" 
                         name="items[<?= $item['id'] ?>][quantity]" 
                         value="<?= $qty ?>" 
                         min="1" 
                         max="999" 
                         class="form-control text-center font-mono fw-bold item-qty-input" 
                         style="width: 74px; padding: 4px 6px; font-size: 14px;"
                         data-price="<?= (float)($item['unit_price'] ?? 0) ?>"
                         data-row-id="<?= $item['id'] ?>"
                         oninput="recalcPharmacyRow(this)">
                  <span class="text-xs text-muted fw-semibold"><?= htmlspecialchars($item['medicine_unit'] ?? 'Pcs') ?></span>
                </div>
              <?php else: ?>
                <span class="fw-bold font-mono text-base"><?= $qty ?></span> <?= htmlspecialchars($item['medicine_unit'] ?? 'Pcs') ?>
              <?php endif; ?>
            </td>
            <td class="text-center font-mono fw-semibold <?= $isItemSufficient ? 'text-success' : 'text-danger fw-bold' ?>">
              <?= $stock ?> <?= htmlspecialchars($item['medicine_unit'] ?? 'Pcs') ?>
            </td>
            <td class="text-center">
              <?php if ($isCompleted): ?>
                <span class="badge badge-success">DISPENSED</span>
              <?php elseif ($isItemSufficient): ?>
                <span class="badge badge-success">Stok Cukup</span>
              <?php else: ?>
                <span class="badge badge-danger">STOK KURANG (Defisit: <?= $qty - $stock ?>)</span>
              <?php endif; ?>
            </td>
            <td class="text-end font-mono text-xs">
              Rp <?= number_format((float)($item['unit_price'] ?? 0), 0, ',', '.') ?>
            </td>
            <td class="text-end font-mono fw-bold" id="subtotal-<?= $item['id'] ?>">
              Rp <?= number_format($subtotal, 0, ',', '.') ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="bg-slate-light fw-bold">
          <td colspan="8" class="text-end text-dark">Total Tagihan Farmasi:</td>
          <td class="text-end text-primary font-mono text-base" id="grandTotalPrescription">
            Rp <?= number_format($grandTotal, 0, ',', '.') ?>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php if (!$isCompleted): ?>
  </form>
  <?php endif; ?>
</div>

<script>
function recalcPharmacyRow(input) {
  const rowId = input.dataset.rowId;
  const price = parseFloat(input.dataset.price) || 0;
  let qty = parseInt(input.value) || 0;
  if (qty < 1) qty = 1;
  const subtotal = qty * price;
  const subtotalEl = document.getElementById('subtotal-' + rowId);
  if (subtotalEl) {
    subtotalEl.textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
  }

  let total = 0;
  document.querySelectorAll('.item-qty-input').forEach(inp => {
    const p = parseFloat(inp.dataset.price) || 0;
    const q = parseInt(inp.value) || 0;
    total += (q * p);
  });
  const grandEl = document.getElementById('grandTotalPrescription');
  if (grandEl) {
    grandEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
  }
}
</script>
