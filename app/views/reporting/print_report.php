<?php
// app/views/reporting/print_report.php - Lembar Cetak Laporan Rekapitulasi Eksekutif Resmi Standar Siloam (SOP/SIMRS/015/2026)

$kpis = $reportData['kpis'] ?? [];
$inpatient = $reportData['inpatient'] ?? [];
$morbidity = $reportData['morbidity'] ?? [];
$medicines = $reportData['medicines'] ?? [];
$docNumber = 'LAP-EKSEKUTIF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Rekapitulasi Eksekutif - SIMRS Siloam Peduli</title>
  <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/logo.svg') ?>">
  <link rel="alternate icon" type="image/png" href="<?= base_url('assets/img/logo.png') ?>">
  <style>
    body {
      font-family: 'Helvetica Neue', Arial, sans-serif;
      margin: 0;
      padding: 24px;
      color: #1e293b;
      background: #ffffff;
      font-size: 12px;
      line-height: 1.5;
    }
    .print-actions {
      margin-bottom: 20px;
      padding: 12px;
      background: #f1f5f9;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    @media print {
      .print-actions {
        display: none !important;
      }
      body {
        padding: 0;
      }
    }
    .kop-surat {
      display: flex;
      align-items: center;
      border-bottom: 3px double #0f172a;
      padding-bottom: 12px;
      margin-bottom: 20px;
    }
    .kop-logo {
      width: 60px;
      height: 60px;
      background: #0284c7;
      color: #fff;
      font-size: 32px;
      font-weight: bold;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      margin-right: 18px;
    }
    .kop-text h2 {
      margin: 0;
      font-size: 18px;
      color: #0f172a;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .kop-text p {
      margin: 2px 0 0 0;
      font-size: 11px;
      color: #475569;
    }
    .doc-meta {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
      background: #f8fafc;
      padding: 10px 14px;
      border: 1px solid #e2e8f0;
      border-radius: 4px;
    }
    .section-title {
      font-size: 13px;
      font-weight: bold;
      color: #0f172a;
      border-bottom: 2px solid #0284c7;
      padding-bottom: 4px;
      margin-top: 20px;
      margin-bottom: 10px;
      text-transform: uppercase;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 14px;
      font-size: 11px;
    }
    th, td {
      border: 1px solid #cbd5e1;
      padding: 6px 10px;
      text-align: left;
    }
    th {
      background: #f1f5f9;
      font-weight: bold;
      color: #334155;
    }
    .text-center { text-align: center; }
    .text-end { text-align: right; }
    .font-mono { font-family: monospace; }
    .badge-status {
      display: inline-block;
      padding: 2px 6px;
      border-radius: 3px;
      font-weight: bold;
      font-size: 10px;
    }
    .badge-ideal { background: #dcfce7; color: #166534; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .signature-area {
      margin-top: 40px;
      display: flex;
      justify-content: space-between;
      page-break-inside: avoid;
    }
    .sig-box {
      width: 240px;
      text-align: center;
    }
    .sig-space {
      height: 70px;
    }
    .sig-name {
      font-weight: bold;
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="print-actions">
  <div>
    <b>Pratinjau Cetak Laporan Eksekutif Resmi SIMRS Siloam Peduli</b>
  </div>
  <div>
    <button onclick="window.print()" style="padding: 6px 16px; background: #0284c7; color: #fff; border: 0; border-radius: 4px; cursor: pointer; font-weight: bold;">
      Cetak / Simpan PDF
    </button>
  </div>
</div>

<!-- KOP SURAT RESMI RUMAH SAKIT -->
<div class="kop-surat">
  <img src="<?= base_url('assets/img/logo.svg') ?>" alt="Logo Siloam" class="kop-logo" style="object-fit: contain; background: none;">
  <div class="kop-text">
    <h2>SILOAM PEDULI</h2>
    <p>Jl. Siloam No. 88, Kawasan Medika Terpadu, Jakarta | Telp: (021) 8060-0900 | Email: info@siloampeduli.id</p>
    <p>Izin Operasional RS: No. 445/RS-088/DINKES/2026 | Standar Akreditasi KARS / Kemenkes RI</p>
  </div>
</div>

<div class="doc-meta">
  <div>
    <div><b>No. Dokumen:</b> <span class="font-mono"><?= $docNumber ?></span></div>
    <div><b>Jenis Laporan:</b> Rekapitulasi Eksekutif &amp; Indikator Mutu Rumah Sakit</div>
  </div>
  <div style="text-align: right;">
    <div><b>Periode Laporan:</b> <?= date('d F Y', strtotime($startDate)) ?> &mdash; <?= date('d F Y', strtotime($endDate)) ?></div>
    <div><b>Waktu Cetak:</b> <?= date('d/m/Y H:i') ?> WIB</div>
  </div>
</div>

<!-- 1. RINGKASAN EKSEKUTIF KUNJUNGAN & FINANSIAL -->
<div class="section-title">1. Ringkasan Kunjungan Pelayanan &amp; Kinerja Finansial</div>
<table>
  <thead>
    <tr>
      <th>Kategori Indikator</th>
      <th class="text-center">Jumlah / Frekuensi</th>
      <th>Keterangan / Rincian</th>
      <th class="text-end">Total Nominal (Rp)</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><b>Rawat Jalan (Poliklinik)</b></td>
      <td class="text-center font-mono"><?= number_format($kpis['outpatient_count'] ?? 0) ?> Pasien</td>
      <td>Pelayanan dokter spesialis dan umum</td>
      <td class="text-end font-mono">-</td>
    </tr>
    <tr>
      <td><b>Rawat Inap (Inpatient)</b></td>
      <td class="text-center font-mono"><?= number_format($kpis['inpatient_count'] ?? 0) ?> Admisi</td>
      <td>Pasien mondok di bangsal perawatan</td>
      <td class="text-end font-mono">-</td>
    </tr>
    <tr>
      <td><b>Instalasi Gawat Darurat (IGD)</b></td>
      <td class="text-center font-mono"><?= number_format($kpis['emergency_count'] ?? 0) ?> Kunjungan</td>
      <td>Pelayanan kedaruratan medik 24 jam</td>
      <td class="text-end font-mono">-</td>
    </tr>
    <tr style="background: #f8fafc; font-weight: bold;">
      <td>Total Kunjungan Pasien</td>
      <td class="text-center font-mono"><?= number_format($kpis['total_visits'] ?? 0) ?> Pasien</td>
      <td>Seluruh unit pelayanan</td>
      <td class="text-end font-mono">-</td>
    </tr>
    <tr>
      <td><b>Penerimaan Kasir (Lunas / Paid)</b></td>
      <td class="text-center font-mono">-</td>
      <td>Pembayaran tunai, EDC, transfer &amp; BPJS cover</td>
      <td class="text-end font-mono">Rp <?= number_format($kpis['total_paid'] ?? 0, 0, ',', '.') ?></td>
    </tr>
    <tr>
      <td><b>Piutang Pasien &amp; Klaim Asuransi</b></td>
      <td class="text-center font-mono">-</td>
      <td>Tagihan dalam proses verifikasi penjamin</td>
      <td class="text-end font-mono">Rp <?= number_format($kpis['total_receivable'] ?? 0, 0, ',', '.') ?></td>
    </tr>
    <tr style="background: #f8fafc; font-weight: bold;">
      <td>Total Pendapatan Terbit (Billed)</td>
      <td class="text-center font-mono">-</td>
      <td>Agregasi seluruh faktur invoice</td>
      <td class="text-end font-mono">Rp <?= number_format($kpis['total_billed'] ?? 0, 0, ',', '.') ?></td>
    </tr>
  </tbody>
</table>

<!-- 2. INDIKATOR EFISIENSI RAWAT INAP KEMENKES (BOR & ALOS) -->
<div class="section-title">2. Indikator Efisiensi Tempat Tidur Rawat Inap (Kemenkes / KARS)</div>
<table>
  <thead>
    <tr>
      <th>Parameter Indikator</th>
      <th class="text-center">Capaian Aktual</th>
      <th>Standar Nasional (Kemenkes)</th>
      <th class="text-center">Kategori Mutu</th>
      <th>Analisis / Interpretasi</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><b>BOR (Bed Occupancy Rate)</b></td>
      <td class="text-center font-mono" style="font-size: 13px; font-weight: bold;"><?= $inpatient['bor'] ?>%</td>
      <td>60% &mdash; 85%</td>
      <td class="text-center">
        <span class="badge-status <?= $inpatient['bor_status'] === 'IDEAL' ? 'badge-ideal' : 'badge-warning' ?>">
          <?= $inpatient['bor_status'] ?>
        </span>
      </td>
      <td>Persentase pemakaian tempat tidur aktif selama periode <?= $inpatient['days_in_period'] ?> hari</td>
    </tr>
    <tr>
      <td><b>ALOS (Average Length of Stay)</b></td>
      <td class="text-center font-mono" style="font-size: 13px; font-weight: bold;"><?= $inpatient['alos'] ?> Hari</td>
      <td>6 &mdash; 9 Hari</td>
      <td class="text-center">
        <span class="badge-status <?= $inpatient['alos_status'] === 'IDEAL' ? 'badge-ideal' : 'badge-warning' ?>">
          <?= $inpatient['alos_status'] ?>
        </span>
      </td>
      <td>Rata-rata lama perawatan pasien rawat inap hingga kepulangan</td>
    </tr>
    <tr>
      <td><b>Kapasitas Bed Rumah Sakit</b></td>
      <td class="text-center font-mono"><?= $inpatient['total_beds'] ?> Tempat Tidur</td>
      <td>-</td>
      <td class="text-center">-</td>
      <td>Bed Terisi: <b><?= $inpatient['occupied_beds'] ?></b> | Bed Kosong: <b><?= $inpatient['available_beds'] ?></b> (Utilisasi: <?= $inpatient['current_occupancy_ratio'] ?>%)</td>
    </tr>
  </tbody>
</table>

<!-- 3. STATISTIK 10 BESAR MORBIDITAS PENYAKIT (TOP 10 ICD-10) -->
<div class="section-title">3. Statistik 10 Besar Morbiditas Penyakit (Top 10 ICD-10)</div>
<table>
  <thead>
    <tr>
      <th style="width: 40px;" class="text-center">Rank</th>
      <th style="width: 90px;">Kode ICD-10</th>
      <th>Deskripsi Diagnosis Medis</th>
      <th class="text-center" style="width: 100px;">Jumlah Kasus</th>
      <th class="text-end" style="width: 90px;">Persentase</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($morbidity)): ?>
      <tr>
        <td colspan="5" class="text-center" style="color: #64748b; padding: 14px;">Tidak ada diagnosis yang tercatat pada periode ini.</td>
      </tr>
    <?php else: ?>
      <?php foreach ($morbidity as $m): ?>
        <tr>
          <td class="text-center font-mono"><b>#<?= $m['rank'] ?></b></td>
          <td class="font-mono"><b><?= htmlspecialchars($m['icd_code']) ?></b></td>
          <td><?= htmlspecialchars($m['diagnosis_name']) ?></td>
          <td class="text-center font-mono"><?= number_format($m['total_cases']) ?></td>
          <td class="text-end font-mono"><?= $m['percentage'] ?>%</td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<!-- LEMBAR PENGESAHAN DOKUMEN RESMI -->
<div class="signature-area">
  <div class="sig-box">
    <div>Mengetahui,</div>
    <div><b>Kepala Instalasi Rekam Medis &amp; TI</b></div>
    <div class="sig-space"></div>
    <div class="sig-name">Ahmad Zulkarnain, S.Kom, MMRS</div>
    <div style="font-size: 10px; color: #64748b;">NIP: 19850412 201001 1 003</div>
  </div>

  <div class="sig-box">
    <div>Jakarta, <?= date('d F Y') ?></div>
    <div><b>Direktur Utama Rumah Sakit</b></div>
    <div class="sig-space"></div>
    <div class="sig-name">dr. Hendra Pratama, MARS</div>
    <div style="font-size: 10px; color: #64748b;">SIP: 446/SIP-DIR/2026</div>
  </div>
</div>

</body>
</html>

