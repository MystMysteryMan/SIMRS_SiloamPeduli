<?php
// app/views/billing/print_receipt.php - Cetak Lembar Kuitansi Pembayaran Sah Siloam
$payment = $invoice['payment'] ?? [];
$receiptNum = $payment['receipt_number'] ?? ('KW-' . date('Ymd') . '-' . str_pad($invoice['id'], 4, '0', STR_PAD_LEFT));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($pageTitle ?? 'Kuitansi Pembayaran') ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/logo.svg') ?>">
  <link rel="alternate icon" type="image/png" href="<?= base_url('assets/img/logo.png') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
  <style>
    body { background: #f1f5f9; padding: 24px; }
    .receipt-container {
      background: #fff;
      max-width: 780px;
      margin: 0 auto;
      padding: 36px 44px;
      border: 1px solid var(--line);
      border-radius: var(--radius-md);
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
      position: relative;
    }
    @media print {
      body { background: #fff; padding: 0; }
      .receipt-container { border: none; box-shadow: none; padding: 0; max-width: 100%; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<div class="no-print" style="max-width: 780px; margin: 0 auto 16px; display: flex; justify-content: space-between; align-items: center;">
  <a href="<?= base_url('index.php?c=billing&a=index') ?>" class="btn btn-outline">
    <i data-lucide="arrow-left"></i> Kembali ke Kasir
  </a>
  <button onclick="window.print()" class="btn btn-primary" style="background: #059669; border-color: #047857;">
    <i data-lucide="printer"></i> Cetak Kuitansi Pembayaran
  </button>
</div>

<div class="receipt-container">
  <!-- Watermark Lunas -->
  <div class="watermark-paid">LUNAS</div>

  <!-- Header Rumah Sakit -->
  <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--blue-900); padding-bottom: 14px; margin-bottom: 20px;">
    <div style="display: flex; align-items: center; gap: 14px;">
      <img src="<?= base_url('assets/img/logo.svg') ?>" alt="Logo Siloam" style="width: 48px; height: 48px; object-fit: contain;">
      <div>
        <h1 style="margin: 0; font-size: 20px; font-weight: 800; color: var(--blue-950); letter-spacing: -0.02em;">
          RUMAH SAKIT SILOAM PEDULI
        </h1>
        <p style="margin: 3px 0 0; font-size: 11.5px; color: var(--muted); line-height: 1.4;">
          Jl. Siloam No. 1, Jakarta Barat &bull; Telp: (021) 555-0123 &bull; Kasir Sentral Rumah Sakit
        </p>
      </div>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 10px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Bukti Pembayaran Sah</div>
      <div style="font-family: ui-monospace, monospace; font-size: 15px; font-weight: 800; color: var(--blue-900);">
        <?= htmlspecialchars($receiptNum) ?>
      </div>
    </div>
  </div>

  <div style="text-align: center; margin-bottom: 22px;">
    <h2 style="margin: 0; font-size: 16px; font-weight: 800; text-transform: uppercase; color: var(--ink); letter-spacing: 0.04em;">
      KUITANSI PEMBAYARAN PELAYANAN KESEHATAN
    </h2>
    <div style="font-family: ui-monospace, monospace; font-size: 12px; color: var(--muted); margin-top: 3px;">
      Nomor Faktur: <?= htmlspecialchars($invoice['invoice_number']) ?> &bull; Tanggal: <?= date('d M Y, H:i', strtotime($payment['payment_time'] ?? $invoice['created_at'])) ?> WIB
    </div>
  </div>

  <!-- Identitas Pasien -->
  <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12.5px;">
    <tr>
      <td style="width: 20%; padding: 4px 0; color: var(--muted);">Telah Terima Dari</td>
      <td style="width: 40%; padding: 4px 0; font-weight: 700; color: var(--ink);">
        : <?= htmlspecialchars($invoice['patient_name']) ?>
      </td>
      <td style="width: 18%; padding: 4px 0; color: var(--muted);">No. Rekam Medis</td>
      <td style="width: 22%; padding: 4px 0; font-weight: 700; font-family: ui-monospace, monospace; color: var(--blue-900);">
        : <?= htmlspecialchars($invoice['no_rm']) ?>
      </td>
    </tr>
    <tr>
      <td style="padding: 4px 0; color: var(--muted);">Jenis Penjamin</td>
      <td style="padding: 4px 0; font-weight: 600;">: <?= htmlspecialchars($invoice['patient_type']) ?> <?= !empty($invoice['insurance_number']) ? '(' . htmlspecialchars($invoice['insurance_number']) . ')' : '' ?></td>
      <td style="padding: 4px 0; color: var(--muted);">Unit Layanan</td>
      <td style="padding: 4px 0; font-weight: 600;">: <?= htmlspecialchars($invoice['visit_type']) ?></td>
    </tr>
    <tr>
      <td style="padding: 4px 0; color: var(--muted);">Dokter Pemeriksa</td>
      <td style="padding: 4px 0;">: <?= htmlspecialchars($invoice['doctor_name'] ?? 'Dokter Jaga RS') ?></td>
      <td style="padding: 4px 0; color: var(--muted);">Metode Bayar</td>
      <td style="padding: 4px 0; font-weight: 700; color: #047857;">: <?= htmlspecialchars($payment['payment_method'] ?? 'TUNAI') ?></td>
    </tr>
  </table>

  <!-- Rincian Item Tagihan -->
  <table class="table" style="width: 100%; margin-bottom: 20px; border-top: 1px solid var(--line);">
    <thead>
      <tr style="border-bottom: 2px solid var(--line);">
        <th style="width: 8%;">No.</th>
        <th style="width: 52%;">Keterangan Pelayanan Medis / Obat</th>
        <th style="text-align: right; width: 10%;">Qty</th>
        <th style="text-align: right; width: 15%;">Tarif (Rp)</th>
        <th style="text-align: right; width: 15%;">Jumlah (Rp)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($invoice['items'] as $idx => $it): ?>
        <tr>
          <td style="color: var(--muted); font-size: 11px;"><?= $idx + 1 ?>.</td>
          <td style="font-weight: 600; color: var(--ink);">
            <?= htmlspecialchars($it['item_name']) ?>
            <span style="font-size: 10px; color: var(--muted); font-weight: 400;">(<?= htmlspecialchars($it['item_type']) ?>)</span>
          </td>
          <td class="num"><?= (int)$it['quantity'] ?></td>
          <td class="num"><?= number_format($it['unit_price'], 0, ',', '.') ?></td>
          <td class="num" style="font-weight: 700; color: var(--ink);"><?= number_format($it['subtotal'], 0, ',', '.') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Total Rekapitulasi Pembayaran -->
  <div style="display: flex; justify-content: flex-end; margin-bottom: 24px;">
    <div style="width: 380px; font-size: 13px;">
      <div style="display: flex; justify-content: space-between; padding: 4px 0; color: var(--muted);">
        <span>Total Biaya Pelayanan (Gross):</span>
        <span style="font-family: ui-monospace, monospace; font-weight: 700;">
          Rp <?= number_format($invoice['total_gross'], 0, ',', '.') ?>
        </span>
      </div>

      <div style="display: flex; justify-content: space-between; padding: 4px 0; color: #047857;">
        <span>Pertanggungan Penjamin (BPJS/Asuransi):</span>
        <span style="font-family: ui-monospace, monospace; font-weight: 700;">
          - Rp <?= number_format($invoice['insurance_coverage'], 0, ',', '.') ?>
        </span>
      </div>

      <div style="display: flex; justify-content: space-between; padding: 8px 0; border-top: 1px solid var(--line); border-bottom: 2px solid var(--blue-900); font-size: 15px; font-weight: 800; color: var(--blue-950);">
        <span>Total Tagihan Pasien (Co-Pay):</span>
        <span style="font-family: ui-monospace, monospace; color: #059669;">
          Rp <?= number_format($invoice['patient_amount'], 0, ',', '.') ?>
        </span>
      </div>

      <div style="display: flex; justify-content: space-between; padding: 6px 0 2px; color: var(--ink);">
        <span>Uang Diterima Kasir:</span>
        <span style="font-family: ui-monospace, monospace; font-weight: 700;">
          Rp <?= number_format($payment['amount_paid'] ?? $invoice['patient_amount'], 0, ',', '.') ?>
        </span>
      </div>

      <div style="display: flex; justify-content: space-between; padding: 2px 0; color: var(--muted);">
        <span>Uang Kembalian:</span>
        <span style="font-family: ui-monospace, monospace;">
          Rp <?= number_format($payment['change_amount'] ?? 0, 0, ',', '.') ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Footer Tanda Tangan Kasir -->
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 32px; padding-top: 16px; border-top: 1px dashed var(--line);">
    <div style="font-size: 11px; color: var(--muted); max-width: 340px; line-height: 1.45;">
      <i>Catatan: Lembar kuitansi ini adalah bukti pembayaran yang sah diterbitkan oleh sistem komputerisasi Rumah Sakit Siloam Peduli. Harap disimpan dengan baik.</i>
    </div>

    <div style="text-align: center; width: 200px;">
      <div style="font-size: 11.5px; color: var(--muted);">
        Jakarta, <?= date('d M Y') ?><br>
        Kasir yang Menerima,
      </div>
      <div style="height: 55px;"></div>
      <div style="font-weight: 700; border-bottom: 1px solid var(--ink); padding-bottom: 2px; font-size: 13px;">
        ( <?= htmlspecialchars($invoice['cashier_name'] ?? 'Budi Kasir, S.Ak') ?> )
      </div>
      <div style="font-size: 10.5px; color: var(--muted); margin-top: 2px;">Petugas Kasir Sentral</div>
    </div>
  </div>
</div>

<script src="<?= base_url('assets/js/lucide.min.js') ?>"></script>
<script>
  if (window.lucide) window.lucide.createIcons();
</script>
</body>
</html>

