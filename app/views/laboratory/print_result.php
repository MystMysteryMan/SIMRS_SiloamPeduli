<?php
// app/views/laboratory/print_result.php - Lembar Hasil Pemeriksaan Laboratorium Resmi
$patAge = !empty($order['birth_date']) ? (date('Y') - date('Y', strtotime($order['birth_date']))) : '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Hasil Laboratorium') ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/logo.svg') ?>">
  <link rel="alternate icon" type="image/png" href="<?= base_url('assets/img/logo.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
  <script src="<?= base_url('assets/js/lucide.min.js') ?>"></script>
  <style>
    @media print {
      .no-print { display: none !important; }
      body { background: #fff !important; margin: 0; padding: 0; font-size: 11pt; color: #000; }
      .print-page { box-shadow: none !important; border: none !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
    }
    body {
      background: #f1f5f9;
      font-family: 'Instrument Sans', sans-serif;
      margin: 0;
      padding: 24px;
    }
    .print-page {
      background: #fff;
      max-width: 800px;
      margin: 0 auto;
      padding: 40px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.08);
      border-radius: 4px;
      border: 1px solid #e2e8f0;
    }
  </style>
</head>
<body>

<!-- Action Bar (Hidden on Print) -->
<div class="no-print" style="max-width: 800px; margin: 0 auto 16px; display: flex; justify-content: space-between; align-items: center;">
  <a href="<?= base_url(in_array(ClinicalWorkflow::role(), ['dokter','perawat','dokter_radiologi','dokter spesialis','dpjp','kepala_ruang'], true) ? 'index.php?c=emr&a=history&patient_id=' . $order['patient_id'] : 'index.php?c=laboratory&a=detail&id=' . $order['id']) ?>" class="btn btn-outline" style="font-size: 12.5px;">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Detail Order</span>
  </a>
  <div style="display: flex; gap: 8px;">
    <button onclick="window.print()" class="btn btn-primary" style="font-size: 13px;">
      <i data-lucide="printer"></i>
      <span>Cetak Lembar Hasil (PDF)</span>
    </button>
  </div>
</div>

<div class="print-page">
  <!-- KOP SURAT RUMAH SAKIT -->
  <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 16px; margin-bottom: 20px;">
    <div style="display: flex; align-items: center; gap: 14px;">
      <img src="<?= base_url('assets/img/logo.svg') ?>" alt="Logo Siloam" style="width: 48px; height: 48px; object-fit: contain;">
      <div>
        <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; letter-spacing: 0.5px;">SILOAM PEDULI</h2>
        <div style="font-size: 11px; color: #475569;">Instalasi Laboratorium Sentral Terakreditasi Paripurna KARS</div>
        <div style="font-size: 10px; color: #64748b;">Jl. Siloam No. 1, Jakarta &bull; Telp: (021) 500-911 &bull; lab@siloampeduli.co.id</div>
      </div>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 14px; font-weight: 700; color: #0f172a;">LEMBAR HASIL LABORATORIUM</div>
      <div class="font-mono" style="font-size: 11px; color: #475569; margin-top: 2px;">No: <?= htmlspecialchars($order['order_number']) ?></div>
      <div style="font-size: 10px; color: #64748b;">Tgl: <?= format_datetime_id($order['completed_at'] ?? $order['created_at']) ?></div>
    </div>
  </div>

  <!-- IDENTITAS PASIEN -->
  <table style="width: 100%; font-size: 11.5px; border-collapse: collapse; margin-bottom: 20px; line-height: 1.6;">
    <tr>
      <td style="width: 16%; color: #64748b;">No. Rekam Medis</td>
      <td style="width: 34%; font-weight: 700; color: #0f172a;" class="font-mono">: <?= htmlspecialchars($order['no_rm']) ?></td>
      <td style="width: 16%; color: #64748b;">Dokter Pengirim</td>
      <td style="width: 34%; font-weight: 600; color: #0f172a;">: <?= htmlspecialchars($order['doctor_name'] ?? 'Dokter Poliklinik') ?></td>
    </tr>
    <tr>
      <td style="color: #64748b;">Nama Pasien</td>
      <td style="font-weight: 700; color: #0f172a;">: <?= htmlspecialchars($order['patient_name']) ?></td>
      <td style="color: #64748b;">Tgl Order / Masuk</td>
      <td style="color: #0f172a;">: <?= format_datetime_id($order['created_at']) ?></td>
    </tr>
    <tr>
      <td style="color: #64748b;">Tgl Lahir / Usia</td>
      <td style="color: #0f172a;">: <?= format_date_id($order['birth_date']) ?> (<?= $patAge ?> thn)</td>
      <td style="color: #64748b;">Prioritas</td>
      <td style="color: #0f172a;">: <?= htmlspecialchars($order['clinical_priority']) ?></td>
    </tr>
    <tr>
      <td style="color: #64748b;">Jenis Kelamin</td>
      <td style="color: #0f172a;">: <?= $order['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
      <td style="color: #64748b;">Jaminan / Asuransi</td>
      <td style="color: #0f172a;">: <?= htmlspecialchars($order['insurance_type']) ?></td>
    </tr>
  </table>

  <!-- TABEL HASIL PENGUJIAN -->
  <table style="width: 100%; border-collapse: collapse; font-size: 11.5px; margin-bottom: 28px;">
    <thead>
      <tr style="border-top: 1.5px solid #0f172a; border-bottom: 1.5px solid #0f172a; background: #f8fafc; text-align: left;">
        <th style="padding: 8px 6px; width: 35%;">PEMERIKSAAN</th>
        <th style="padding: 8px 6px; width: 18%;">HASIL</th>
        <th style="padding: 8px 6px; width: 14%;">SATUAN</th>
        <th style="padding: 8px 6px; width: 18%;">NILAI RUJUKAN</th>
        <th style="padding: 8px 6px; width: 15%;">KETERANGAN</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <?php
          $flg = $it['result_flag'] ?? 'NORMAL';
          $isAbnormal = in_array($flg, ['HIGH', 'LOW', 'CRITICAL']);
          $valColor = '#0f172a';
          if ($flg === 'CRITICAL') $valColor = '#b91c1c';
          elseif ($flg === 'HIGH') $valColor = '#c2410c';
          elseif ($flg === 'LOW') $valColor = '#1d4ed8';
        ?>
        <tr style="border-bottom: 1px solid #e2e8f0;">
          <td style="padding: 8px 6px; font-weight: 600; color: #0f172a;">
            <?= htmlspecialchars($it['test_name']) ?>
          </td>
          <td style="padding: 8px 6px; font-weight: 700; color: <?= $valColor ?>;" class="font-mono">
            <?= htmlspecialchars($it['result_value'] ?? '-') ?>
            <?php if ($isAbnormal): ?>
              <span style="font-size: 10px; margin-left: 4px; font-weight: 800;">[<?= $flg ?>]</span>
            <?php endif; ?>
          </td>
          <td style="padding: 8px 6px; color: #475569;"><?= htmlspecialchars($it['result_unit'] ?? $it['default_unit'] ?? '-') ?></td>
          <td style="padding: 8px 6px; color: #475569;"><?= htmlspecialchars($it['result_normal_range'] ?? $it['default_normal_range'] ?? '-') ?></td>
          <td style="padding: 8px 6px; font-size: 10.5px; color: #64748b;"><?= htmlspecialchars($it['analyst_notes'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- TANDA TANGAN & PENGESAHAN -->
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 36px; padding-top: 16px; font-size: 11.5px;">
    <div>
      <div style="font-size: 10.5px; color: #64748b;">
        Catatan:<br>
        &bull; Hasil pemeriksaan ini hanya berlaku untuk spesimen yang diuji.<br>
        &bull; Nilai rujukan disesuaikan dengan metode reagen terkini.
      </div>
      <div style="margin-top: 10px; padding: 4px 8px; border: 1px dashed #cbd5e1; border-radius: 4px; display: inline-block; font-size: 10px; color: #059669; font-weight: 600;">
        VERIFIKASI SISTEM ELEKTRONIK &bull; TERVALIDASI
      </div>
    </div>

    <div style="text-align: center; width: 220px;">
      <div style="color: #64748b; margin-bottom: 50px;">
        Jakarta, <?= date('d M Y') ?><br>
        <b>Analis Laboratorium</b>
      </div>
      <div style="font-weight: 700; color: #0f172a; text-decoration: underline;">
        <?= htmlspecialchars($order['analyst_name'] ?? 'Budi Santoso, A.Md.AK') ?>
      </div>
      <div style="font-size: 10px; color: #64748b;">SIP: 446.1/LAB-SIMRS/2026</div>
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

