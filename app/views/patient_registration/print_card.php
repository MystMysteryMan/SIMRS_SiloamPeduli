<?php
// app/views/patient_registration/print_card.php - Cetak Kartu Pasien (Lucide Icons)
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kartu Pasien - <?= htmlspecialchars($patient['no_rm']) ?></title>
  <script src="<?= base_url('assets/js/lucide.min.js') ?>"></script>
  <style>
    @page {
      size: 85.6mm 53.98mm; /* Ukuran standar ID-1 / Kartu ATM / KTP */
      margin: 0;
    }
    body {
      margin: 0;
      padding: 24px;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f1f5f9;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .no-print {
      margin-bottom: 24px;
      display: flex;
      gap: 12px;
    }
    .btn {
      padding: 9px 18px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 0;
    }
    .btn-print { background: #0284c7; color: #fff; }
    .btn-back { background: #e2e8f0; color: #334155; }
    .btn-next { background: #10b981; color: #fff; }

    /* CARD DESIGN */
    .patient-card {
      width: 340px;
      height: 215px;
      background: linear-gradient(135deg, #0f2f57 0%, #15518a 60%, #0284c7 100%);
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.22);
      color: #fff;
      padding: 16px;
      box-sizing: border-box;
      position: relative;
      overflow: hidden;
    }
    .card-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid rgba(255,255,255,0.25);
      padding-bottom: 8px;
    }
    .hospital-title b {
      display: block;
      font-size: 13px;
      letter-spacing: 0.5px;
      font-weight: 800;
    }
    .hospital-title span {
      font-size: 9px;
      opacity: 0.85;
    }
    .card-badge {
      background: rgba(255,255,255,0.2);
      padding: 2px 8px;
      border-radius: 20px;
      font-size: 9px;
      font-weight: 700;
      text-transform: uppercase;
    }
    .card-body {
      margin-top: 12px;
    }
    .rm-number {
      font-size: 18px;
      font-weight: 800;
      letter-spacing: 1.5px;
      font-family: monospace;
      color: #38bdf8;
      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    .patient-name {
      font-size: 14px;
      font-weight: 700;
      margin-top: 4px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .meta-row {
      display: flex;
      gap: 16px;
      margin-top: 8px;
      font-size: 10px;
      opacity: 0.9;
    }
    .card-footer {
      position: absolute;
      bottom: 12px;
      left: 16px;
      right: 16px;
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      border-top: 1px dashed rgba(255,255,255,0.2);
      padding-top: 6px;
    }
    .barcode-sim {
      font-family: monospace;
      font-size: 16px;
      letter-spacing: 3px;
      opacity: 0.7;
    }
    .small-notes {
      font-size: 8px;
      opacity: 0.75;
      line-height: 1.1;
      text-align: right;
    }

    @media print {
      body {
        background: transparent;
        padding: 0;
      }
      .no-print {
        display: none !important;
      }
      .patient-card {
        box-shadow: none;
        border-radius: 0;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }
  </style>
</head>
<body>

<div class="no-print">
  <button class="btn btn-print" onclick="window.print()">
    <i data-lucide="printer"></i>
    <span>Cetak Kartu Sekarang</span>
  </button>
  <a href="<?= base_url('index.php?c=appointment&a=create&patient_id=' . $patient['id']) ?>" class="btn btn-next">
    <i data-lucide="calendar-plus"></i>
    <span>Lanjut Buat Janji Temu</span>
  </a>
  <a href="<?= base_url('index.php?c=patient&a=index') ?>" class="btn btn-back">
    <i data-lucide="arrow-left"></i>
    <span>Kembali</span>
  </a>
</div>

<div class="patient-card">
  <div class="card-top">
    <div class="hospital-title">
      <b>SILOAM PEDULI</b>
      <span>KARTU BEROBAT PASIEN</span>
    </div>
    <div class="card-badge"><?= htmlspecialchars($patient['insurance_type']) ?></div>
  </div>

  <div class="card-body">
    <div class="rm-number"><?= htmlspecialchars($patient['no_rm']) ?></div>
    <div class="patient-name"><?= htmlspecialchars(strtoupper($patient['name'])) ?></div>

    <div class="meta-row">
      <div>
        <span style="opacity: 0.7;">NIK:</span><br>
        <b><?= htmlspecialchars($patient['nik']) ?></b>
      </div>
      <div>
        <span style="opacity: 0.7;">Tgl Lahir:</span><br>
        <b><?= date('d/m/Y', strtotime($patient['birth_date'])) ?></b>
      </div>
      <div>
        <span style="opacity: 0.7;">JK / Gol:</span><br>
        <b><?= $patient['gender'] ?> / <?= $patient['blood_type'] ?: '-' ?></b>
      </div>
    </div>
  </div>

  <div class="card-footer">
    <div class="barcode-sim">|||| ||| ||||| ||||</div>
    <div class="small-notes">
      Harap bawa kartu ini setiap kali berobat.<br>
      Simpan dengan baik.
    </div>
  </div>
</div>

<script>
if (typeof lucide !== 'undefined') {
  lucide.createIcons();
}
</script>
</body>
</html>
