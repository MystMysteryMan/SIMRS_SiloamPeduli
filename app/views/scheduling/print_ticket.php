<?php
// app/views/scheduling/print_ticket.php - Cetak Tiket Antrean Poliklinik (Thermal Receipt Standar)
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tiket Antrean - <?= htmlspecialchars($appointment['queue_number']) ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/logo.svg') ?>">
  <link rel="alternate icon" type="image/png" href="<?= base_url('assets/img/logo.png') ?>">
  <script src="<?= base_url('assets/js/lucide.min.js') ?>"></script>
  <style>
    @page {
      size: 80mm auto;
      margin: 4mm;
    }
    body {
      font-family: 'Courier New', Courier, monospace;
      font-size: 12px;
      color: #000;
      background: #f1f5f9;
      margin: 0;
      padding: 24px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .no-print {
      margin-bottom: 20px;
      display: flex;
      gap: 12px;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    }
    .btn {
      padding: 8px 16px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      border: 0;
    }
    .btn-print { background: #0284c7; color: #fff; }
    .btn-back { background: #e2e8f0; color: #334155; }

    /* THERMAL TICKET */
    .ticket {
      width: 290px;
      background: #fff;
      padding: 22px 18px;
      border: 1px dashed #94a3b8;
      border-radius: 4px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      text-align: center;
    }
    .ticket-header h2 {
      margin: 0;
      font-size: 16px;
      font-weight: 900;
      letter-spacing: 0.5px;
    }
    .ticket-header p {
      margin: 2px 0 10px;
      font-size: 10px;
      line-height: 1.3;
    }
    .divider {
      border-top: 1px dashed #000;
      margin: 10px 0;
    }
    .queue-title {
      font-size: 12px;
      font-weight: bold;
      text-transform: uppercase;
      margin-top: 8px;
    }
    .queue-number {
      font-size: 42px;
      font-weight: 900;
      letter-spacing: 2px;
      margin: 6px 0;
    }
    .ticket-info {
      text-align: left;
      font-size: 11px;
      line-height: 1.5;
    }
    .ticket-info table {
      width: 100%;
    }
    .ticket-info td {
      vertical-align: top;
      padding: 2px 0;
    }
    .ticket-footer {
      font-size: 10px;
      margin-top: 12px;
      line-height: 1.3;
    }

    @media print {
      body {
        background: transparent;
        padding: 0;
      }
      .no-print {
        display: none !important;
      }
      .ticket {
        box-shadow: none;
        border: none;
        width: 100%;
        padding: 0;
      }
    }
  </style>
</head>
<body>

<div class="no-print">
  <button class="btn btn-print" onclick="window.print()">
    <i data-lucide="printer"></i>
    <span>Cetak Tiket Thermal</span>
  </button>
  <a href="<?= base_url('index.php?c=appointment&a=index') ?>" class="btn btn-back">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Antrean</span>
  </a>
</div>

<div class="ticket">
  <div class="ticket-header">
    <img src="<?= base_url('assets/img/logo.svg') ?>" alt="Logo Siloam" style="width: 38px; height: 38px; object-fit: contain; margin-bottom: 4px;">
    <h2>SILOAM PEDULI</h2>
    <p>Jl. Garnisun No. 2, Semanggi, Jakarta Selatan<br>Telp: (021) 2996-2888</p>
  </div>

  <div class="divider"></div>

  <div class="queue-title">NOMOR ANTREAN POLIKLINIK</div>
  <div class="queue-number"><?= htmlspecialchars($appointment['queue_number']) ?></div>
  <div style="font-weight: bold; font-size: 13px;"><?= htmlspecialchars($appointment['policlinic_name']) ?></div>

  <div class="divider"></div>

  <div class="ticket-info">
    <table>
      <tr>
        <td style="width: 80px;">No. RM</td>
        <td>: <b><?= htmlspecialchars($appointment['no_rm']) ?></b></td>
      </tr>
      <tr>
        <td>Pasien</td>
        <td>: <?= htmlspecialchars($appointment['patient_name']) ?></td>
      </tr>
      <tr>
        <td>Penjamin</td>
        <td>: <?= htmlspecialchars($appointment['insurance_type']) ?></td>
      </tr>
      <tr>
        <td>Dokter</td>
        <td>: <?= htmlspecialchars($appointment['doctor_name']) ?></td>
      </tr>
      <tr>
        <td>Tanggal</td>
        <td>: <?= date('d/m/Y', strtotime($appointment['appointment_date'])) ?></td>
      </tr>
      <tr>
        <td>Dicetak</td>
        <td>: <?= date('d/m/Y H:i') ?> WIB</td>
      </tr>
    </table>
  </div>

  <div class="divider"></div>

  <div class="ticket-footer">
    Terima kasih atas kunjungan Anda.<br>
    Harap menunggu di ruang tunggu poliklinik sesuai nomor antrean yang tertera.<br>
    <i>Semoga lekas sembuh.</i>
  </div>
</div>

<script>
if (typeof lucide !== 'undefined') {
  lucide.createIcons();
}
</script>
</body>
</html>
