<?php
// app/views/radiology/print_result.php - Lembar Hasil & Ekspertise Radiologi Resmi
$patAge = !empty($order['birth_date']) ? (date('Y') - date('Y', strtotime($order['birth_date']))) : '-';
$hasImage = !empty($order['image_path']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Ekspertise Radiologi') ?></title>
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
  <a href="<?= base_url(in_array(ClinicalWorkflow::role(), ['dokter','perawat','dokter spesialis','dpjp','kepala_ruang'], true) ? 'index.php?c=emr&a=history&patient_id=' . $order['patient_id'] : 'index.php?c=radiology&a=detail&id=' . $order['id']) ?>" class="btn btn-outline" style="font-size: 12.5px;">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Detail Order</span>
  </a>
  <div style="display: flex; gap: 8px;">
    <button onclick="window.print()" class="btn btn-primary" style="font-size: 13px;">
      <i data-lucide="printer"></i>
      <span>Cetak Lembar Ekspertise (PDF)</span>
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
        <div style="font-size: 11px; color: #475569;">Instalasi Radiologi &amp; Diagnostik Imaging Terakreditasi Paripurna KARS</div>
        <div style="font-size: 10px; color: #64748b;">Jl. Siloam No. 1, Jakarta &bull; Telp: (021) 500-911 &bull; rad@siloampeduli.co.id</div>
      </div>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 14px; font-weight: 700; color: #0f172a;">LEMBAR EKSPERTISE RADIOLOGI</div>
      <div class="font-mono" style="font-size: 11px; color: #475569; margin-top: 2px;">No: <?= htmlspecialchars($order['order_number']) ?></div>
      <div style="font-size: 10px; color: #64748b;">Tgl: <?= format_datetime_id($order['completed_at'] ?? $order['created_at']) ?></div>
    </div>
  </div>

  <!-- IDENTITAS PASIEN -->
  <table style="width: 100%; font-size: 11.5px; border-collapse: collapse; margin-bottom: 16px; line-height: 1.6;">
    <tr>
      <td style="width: 16%; color: #64748b;">No. Rekam Medis</td>
      <td style="width: 34%; font-weight: 700; color: #0f172a;" class="font-mono">: <?= htmlspecialchars($order['no_rm']) ?></td>
      <td style="width: 16%; color: #64748b;">Dokter Pengirim</td>
      <td style="width: 34%; font-weight: 600; color: #0f172a;">: <?= htmlspecialchars($order['doctor_name'] ?? 'Dokter Poliklinik') ?></td>
    </tr>
    <tr>
      <td style="color: #64748b;">Nama Pasien</td>
      <td style="font-weight: 700; color: #0f172a;">: <?= htmlspecialchars($order['patient_name']) ?></td>
      <td style="color: #64748b;">Modalitas Tindakan</td>
      <td style="font-weight: 600; color: #0f172a;">: <?= htmlspecialchars($order['modality_type']) ?></td>
    </tr>
    <tr>
      <td style="color: #64748b;">Tgl Lahir / Usia</td>
      <td style="color: #0f172a;">: <?= format_date_id($order['birth_date']) ?> (<?= $patAge ?> thn)</td>
      <td style="color: #64748b;">Indikasi Klinis</td>
      <td style="color: #0f172a;">: <?= htmlspecialchars($order['clinical_indication']) ?></td>
    </tr>
    <tr>
      <td style="color: #64748b;">Jenis Kelamin</td>
      <td style="color: #0f172a;">: <?= $order['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
      <td style="color: #64748b;">Radiografer</td>
      <td style="color: #0f172a;">: <?= htmlspecialchars($order['radiographer_name'] ?? '-') ?></td>
    </tr>
  </table>

  <!-- CITRA RADIOLOGI RESMI (WEBP) -->
  <?php if ($hasImage): ?>
    <div style="text-align: center; margin: 16px 0 24px; padding: 12px; background: #000; border-radius: 6px;">
      <img src="<?= base_url($order['image_path']) ?>" alt="Citra Radiologi" 
           style="max-height: 280px; max-width: 100%; object-fit: contain; display: block; margin: 0 auto;">
      <div style="color: #94a3b8; font-size: 9.5px; margin-top: 6px;">
        Citra pemeriksaan: <?= htmlspecialchars($order['image_filename']) ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- HASIL BACAAN EKSPERTISE -->
  <div style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 16px 20px; margin-bottom: 24px; font-size: 11.5px; line-height: 1.6;">
    <div style="margin-bottom: 14px;">
      <b style="color: #0f172a; text-transform: uppercase; font-size: 12px; display: block; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; margin-bottom: 6px;">
        Deskripsi Temuan Radiologi (Findings):
      </b>
      <div style="color: #1e293b; white-space: pre-line;">
        <?= htmlspecialchars($order['expertise_findings'] ?? 'Belum ada temuan radiologi.') ?>
      </div>
    </div>

    <div>
      <b style="color: #0f172a; text-transform: uppercase; font-size: 12px; display: block; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; margin-bottom: 6px;">
        Kesimpulan (Conclusion):
      </b>
      <div style="font-weight: 700; color: #0f172a; white-space: pre-line;">
        <?= htmlspecialchars($order['expertise_conclusion'] ?? 'Menunggu pembacaan dokter spesialis radiologi.') ?>
      </div>
    </div>
  </div>

  <!-- TANDA TANGAN DOKTER SPESIALIS RADIOLOGI -->
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 24px; font-size: 11.5px;">
    <div>
      <div style="padding: 4px 8px; border: 1px dashed #cbd5e1; border-radius: 4px; display: inline-block; font-size: 10px; color: #059669; font-weight: 600;">
        DIVALIDASI SECARA ELEKTRONIK &bull; REKAM MEDIS SAH
      </div>
      <div style="font-size: 10px; color: #64748b; margin-top: 6px;">
        Waktu Validasi: <?= format_datetime_id($order['completed_at'] ?? date('Y-m-d H:i:s')) ?>
      </div>
    </div>

    <div style="text-align: center; width: 240px;">
      <div style="color: #64748b; margin-bottom: 50px;">
        Jakarta, <?= date('d M Y') ?><br>
        <b>Dokter Spesialis Radiologi</b>
      </div>
      <div style="font-weight: 700; color: #0f172a; text-decoration: underline;">
        <?= htmlspecialchars($order['radiologist_name'] ?? 'dr. Hendra Wijaya, Sp.Rad') ?>
      </div>
      <div style="font-size: 10px; color: #64748b;">SIP: 446.2/SP-RAD/2026</div>
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
