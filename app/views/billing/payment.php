<?php
// app/views/billing/payment.php - Form Kasir Pembayaran & Penyelesaian Tagihan
$patientAmount = (float)$invoice['patient_amount'];
$isBpjsFree = ($invoice['patient_type'] === 'BPJS' && $patientAmount <= 0);
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="credit-card" style="color: #059669; width: 22px; height: 22px;"></i>
      <span>Penerimaan Pembayaran Kasir (Checkout)</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Pencatatan transaksi pembayaran tunai/non-tunai, perhitungan uang kembalian, dan penutupan tagihan.
    </p>
  </div>
  <div>
    <a href="<?= base_url('index.php?c=billing&a=index') ?>" class="btn btn-outline">
      <i data-lucide="arrow-left"></i>
      <span>Kembali ke Billing</span>
    </a>
  </div>
</div>

<div style="display: grid; grid-template-columns: minmax(320px, 1fr) minmax(360px, 1.2fr); gap: 20px; align-items: start;">
  <!-- Sisi Kiri: Ringkasan Invoice & Item Tagihan -->
  <div class="card" style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--line); padding-bottom: 10px;">
      <div>
        <span style="font-family: ui-monospace, monospace; font-size: 14px; font-weight: 800; color: var(--blue-900);">
          <?= htmlspecialchars($invoice['invoice_number']) ?>
        </span>
        <div style="font-size: 11px; color: var(--muted);">
          Terbit: <?= date('d M Y, H:i', strtotime($invoice['created_at'])) ?>
        </div>
      </div>
      <div>
        <?php if ($invoice['status'] === 'PAID'): ?>
          <span class="badge badge-success" style="font-size: 12px; padding: 4px 10px;"><i data-lucide="check" class="lucide-sm"></i> LUNAS</span>
        <?php else: ?>
          <span class="badge badge-warning" style="font-size: 12px; padding: 4px 10px;"><i data-lucide="clock" class="lucide-sm"></i> MENUNGGU PEMBAYARAN</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Pasien Details -->
    <div style="margin-bottom: 16px; font-size: 12.5px; line-height: 1.5;">
      <div style="color: var(--muted);">Pasien:</div>
      <div style="font-size: 15px; font-weight: 700; color: var(--ink);">
        <?= htmlspecialchars($invoice['patient_name']) ?>
      </div>
      <div style="color: var(--muted);">
        No. RM: <b><?= htmlspecialchars($invoice['no_rm']) ?></b> &bull; Penjamin: <b><?= htmlspecialchars($invoice['patient_type']) ?></b>
      </div>
    </div>

    <!-- Rincian Item Tagihan -->
    <div style="max-height: 260px; overflow-y: auto; border: 1px solid var(--line); border-radius: var(--radius-sm); margin-bottom: 16px;">
      <table class="table" style="margin: 0; font-size: 12px;">
        <thead style="position: sticky; top: 0; background: var(--surface-alt);">
          <tr>
            <th>Item Pelayanan</th>
            <th style="text-align: right;">Qty</th>
            <th style="text-align: right;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($invoice['items'] as $it): ?>
            <tr>
              <td><?= htmlspecialchars($it['item_name']) ?></td>
              <td class="num"><?= (int)$it['quantity'] ?></td>
              <td class="num">Rp <?= number_format($it['subtotal'], 0, ',', '.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Total Breakdown -->
    <div style="background: var(--surface-alt); padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px;">
      <div style="display: flex; justify-content: space-between; margin-bottom: 4px; color: var(--muted);">
        <span>Total Biaya (Gross):</span>
        <span style="font-family: ui-monospace, monospace;">Rp <?= number_format($invoice['total_gross'], 0, ',', '.') ?></span>
      </div>
      <div style="display: flex; justify-content: space-between; margin-bottom: 6px; color: #047857;">
        <span>Tanggungan Penjamin:</span>
        <span style="font-family: ui-monospace, monospace;">- Rp <?= number_format($invoice['insurance_coverage'], 0, ',', '.') ?></span>
      </div>
      <div style="display: flex; justify-content: space-between; padding-top: 6px; border-top: 1px dashed var(--line); font-size: 15px; font-weight: 800; color: var(--blue-950);">
        <span>Kewajiban Pasien:</span>
        <span style="font-family: ui-monospace, monospace; color: <?= ($patientAmount > 0) ? '#dc2626' : '#047857' ?>;">
          Rp <?= number_format($patientAmount, 0, ',', '.') ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Sisi Kanan: Form Pembayaran Kasir -->
  <div class="card" style="padding: 24px; border-top: 4px solid #059669;">
    <h3 style="margin: 0 0 16px; font-size: 16px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="wallet" class="lucide-md" style="color: #059669;"></i>
      <span>Form Kasir &amp; Eksekusi Pelunasan</span>
    </h3>

    <?php if ($invoice['status'] === 'PAID'): ?>
      <div style="text-align: center; padding: 32px 16px;">
        <i data-lucide="check-circle" style="width: 56px; height: 56px; color: var(--success); margin-bottom: 12px;"></i>
        <h3 style="margin: 0 0 6px; color: var(--ink);">Tagihan Ini Sudah Lunas!</h3>
        <p style="margin: 0 0 16px; font-size: 13px; color: var(--muted);">
          Pembayaran telah diterima dengan nomor kuitansi: <b><?= htmlspecialchars($invoice['payment']['receipt_number'] ?? '-') ?></b>
        </p>
        <a href="<?= base_url('index.php?c=billing&a=printReceipt&id=' . $invoice['id']) ?>" target="_blank" class="btn btn-primary" style="padding: 10px 24px;">
          <i data-lucide="printer"></i>
          <span>Cetak Kuitansi Pembayaran Sah</span>
        </a>
      </div>
    <?php else: ?>
      <form method="POST" action="<?= base_url('index.php?c=billing&a=processPayment') ?>" id="paymentForm">
        <?= csrf_field() ?>
        <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">

        <?php if ($isBpjsFree): ?>
          <!-- Kasus Pasien BPJS 100% Ditanggung -->
          <div style="padding: 16px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: var(--radius-sm); margin-bottom: 20px;">
            <div style="font-size: 14px; font-weight: 700; color: #065f46; display: flex; align-items: center; gap: 8px;">
              <i data-lucide="shield-check" style="color: #059669;"></i>
              <span>TANGGUNGAN PENUH BPJS KESEHATAN</span>
            </div>
            <p style="margin: 6px 0 0; font-size: 12.5px; color: #047857; line-height: 1.45;">
              Seluruh biaya pelayanan (Rp <?= number_format($invoice['total_gross'], 0, ',', '.') ?>) ditanggung penuh oleh program JKN/KIS. Pasien tidak dikenakan biaya tambahan (Rp 0).
            </p>
          </div>

          <input type="hidden" name="payment_method" value="ASURANSI_FULL">
          <input type="hidden" name="amount_paid" value="0">

          <button type="submit" class="btn btn-primary" style="width: 100%; background: #059669; border-color: #047857; padding: 12px; font-weight: 700; font-size: 14px;">
            <i data-lucide="check"></i>
            <span>Konfirmasi Pelunasan BPJS &amp; Cetak Bukti</span>
          </button>
        <?php else: ?>
          <!-- Kasus Pasien Umum / Co-Pay Asuransi Swasta -->
          <div style="margin-bottom: 16px;">
            <label class="form-label" style="font-weight: 700;">Metode Pembayaran</label>
            <select name="payment_method" class="form-control" required>
              <option value="TUNAI">Tunai (Cash)</option>
              <option value="DEBIT">Kartu Debit (EDC)</option>
              <option value="KREDIT">Kartu Kredit (EDC)</option>
              <option value="QRIS">QRIS Dinamis</option>
              <option value="TRANSFER">Transfer Bank / Virtual Account</option>
            </select>
          </div>

          <div style="margin-bottom: 16px;">
            <label class="form-label" style="font-weight: 700;">Nominal Uang Diterima dari Pasien <span style="color: var(--danger);">*</span></label>
            <div style="position: relative;">
              <span style="position: absolute; left: 12px; top: 10px; font-weight: 700; color: var(--muted); font-family: ui-monospace, monospace;">Rp</span>
              <input type="number" name="amount_paid" id="amountPaidInput" class="form-control num" style="padding-left: 42px; font-size: 18px; font-weight: 800; color: var(--blue-900);" value="<?= $patientAmount ?>" min="<?= $patientAmount ?>" required oninput="calcChange()">
            </div>
            <div style="display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap;">
              <button type="button" class="btn btn-sm btn-outline" onclick="setQuickAmount(<?= $patientAmount ?>)">Pas (Rp <?= number_format($patientAmount, 0, ',', '.') ?>)</button>
              <button type="button" class="btn btn-sm btn-outline" onclick="setQuickAmount(100000)">100 Ribu</button>
              <button type="button" class="btn btn-sm btn-outline" onclick="setQuickAmount(200000)">200 Ribu</button>
              <button type="button" class="btn btn-sm btn-outline" onclick="setQuickAmount(500000)">500 Ribu</button>
            </div>
          </div>

          <!-- Kalkulasi Uang Kembalian -->
          <div style="background: var(--surface-alt); padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; border: 1px solid var(--line);">
            <div style="font-size: 11.5px; font-weight: 700; color: var(--muted); text-transform: uppercase;">
              Uang Kembalian Pasien:
            </div>
            <div id="changeDisplay" style="font-size: 24px; font-weight: 900; color: #059669; font-family: ui-monospace, monospace; margin-top: 2px;">
              Rp 0
            </div>
            <div id="underpaidWarning" style="display: none; font-size: 11.5px; color: #dc2626; font-weight: 600; margin-top: 4px;">
              <i data-lucide="alert-triangle" class="lucide-sm"></i> Uang pembayaran kurang dari total tagihan!
            </div>
          </div>

          <div style="margin-bottom: 20px;">
            <label class="form-label" style="font-size: 11.5px;">Catatan Kasir</label>
            <input type="text" name="notes" class="form-control" placeholder="Contoh: No. Ref Kartu EDC 98124912 / Tunai pas">
          </div>

          <button type="submit" id="submitPayBtn" class="btn btn-primary" style="width: 100%; background: #059669; border-color: #047857; padding: 12px; font-weight: 700; font-size: 14px;">
            <i data-lucide="check"></i>
            <span>Selesaikan Pembayaran &amp; Cetak Kuitansi</span>
          </button>
        <?php endif; ?>
      </form>
    <?php endif; ?>
  </div>
</div>

<script>
const targetAmount = <?= (float)$patientAmount ?>;

function setQuickAmount(amt) {
  document.getElementById('amountPaidInput').value = amt;
  calcChange();
}

function calcChange() {
  const input = document.getElementById('amountPaidInput');
  const display = document.getElementById('changeDisplay');
  const warn = document.getElementById('underpaidWarning');
  const btn = document.getElementById('submitPayBtn');

  if (!input) return;

  const paid = parseFloat(input.value) || 0;
  const change = paid - targetAmount;

  if (change < 0) {
    display.innerText = 'Rp 0';
    display.style.color = '#dc2626';
    warn.style.display = 'block';
    btn.disabled = true;
    btn.style.opacity = '0.5';
  } else {
    display.innerText = 'Rp ' + change.toLocaleString('id-ID');
    display.style.color = '#059669';
    warn.style.display = 'none';
    btn.disabled = false;
    btn.style.opacity = '1';
  }
}
</script>

