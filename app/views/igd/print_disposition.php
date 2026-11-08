<?php
// app/views/igd/print_disposition.php - Lembar Surat Disposisi & Resume Medis IGD (Print Version)
$t = $visit['triage'] ?? [];
$a = $visit['assessment'] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($pageTitle ?? 'Surat Disposisi IGD') ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/logo.svg') ?>">
  <link rel="alternate icon" type="image/png" href="<?= base_url('assets/img/logo.png') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
  <style>
    body { background: #f1f5f9; padding: 24px; }
    .print-sheet {
      background: #fff;
      max-width: 800px;
      margin: 0 auto;
      padding: 36px 44px;
      border: 1px solid var(--line);
      border-radius: var(--radius-md);
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }
    @media print {
      body { background: #fff; padding: 0; }
      .print-sheet { border: none; box-shadow: none; padding: 0; max-width: 100%; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<div class="no-print" style="max-width: 800px; margin: 0 auto 16px; display: flex; justify-content: space-between; align-items: center;">
  <a href="<?= base_url('index.php?c=igd&a=index') ?>" class="btn btn-outline">
    <i data-lucide="arrow-left"></i> Kembali ke IGD
  </a>
  <button onclick="window.print()" class="btn btn-primary">
    <i data-lucide="printer"></i> Cetak Dokumen Disposisi
  </button>
</div>

<div class="print-sheet">
  <!-- Kop Surat Rumah Sakit -->
  <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--blue-900); padding-bottom: 14px; margin-bottom: 20px;">
    <div style="display: flex; align-items: center; gap: 14px;">
      <img src="<?= base_url('assets/img/logo.svg') ?>" alt="Logo Siloam" style="width: 48px; height: 48px; object-fit: contain;">
      <div>
        <h1 style="margin: 0; font-size: 20px; font-weight: 800; color: var(--blue-950); letter-spacing: -0.02em;">
          RUMAH SAKIT SILOAM PEDULI
        </h1>
        <p style="margin: 3px 0 0; font-size: 11.5px; color: var(--muted); line-height: 1.4;">
          Jl. Siloam No. 1, Jakarta Barat &bull; Telp: (021) 555-0123 &bull; Email: info@siloampeduli.co.id
        </p>
      </div>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 10px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Unit Pelayanan</div>
      <div style="font-size: 14px; font-weight: 800; color: #dc2626;">INSTALASI GAWAT DARURAT</div>
    </div>
  </div>

  <div style="text-align: center; margin-bottom: 20px;">
    <h2 style="margin: 0; font-size: 16px; font-weight: 800; text-transform: uppercase; color: var(--ink); letter-spacing: 0.02em;">
      SURAT DISPOSISI &amp; RESUME MEDIS GAWAT DARURAT
    </h2>
    <div style="font-family: ui-monospace, monospace; font-size: 12px; font-weight: 600; color: var(--muted); margin-top: 3px;">
      Nomor Kunjungan: <?= htmlspecialchars($visit['visit_number']) ?>
    </div>
  </div>

  <!-- Biodata Pasien & Triase -->
  <table style="width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 12.5px;">
    <tr>
      <td style="width: 18%; padding: 4px 0; color: var(--muted);">Nama Pasien</td>
      <td style="width: 32%; padding: 4px 0; font-weight: 700; color: var(--ink);">
        : <?= htmlspecialchars($visit['patient_name'] ?? $visit['anonymous_name']) ?>
      </td>
      <td style="width: 18%; padding: 4px 0; color: var(--muted);">No. Rekam Medis</td>
      <td style="width: 32%; padding: 4px 0; font-weight: 700; font-family: ui-monospace, monospace; color: var(--blue-900);">
        : <?= htmlspecialchars($visit['no_rm'] ?? 'MR/MRS. X') ?>
      </td>
    </tr>
    <tr>
      <td style="padding: 4px 0; color: var(--muted);">Tgl Lahir / Usia</td>
      <td style="padding: 4px 0;">: <?= !empty($visit['birth_date']) ? date('d-m-Y', strtotime($visit['birth_date'])) : '-' ?></td>
      <td style="padding: 4px 0; color: var(--muted);">Jenis Penjamin</td>
      <td style="padding: 4px 0; font-weight: 600;">: <?= htmlspecialchars($visit['insurance_type'] ?? 'UMUM') ?></td>
    </tr>
    <tr>
      <td style="padding: 4px 0; color: var(--muted);">Waktu Kedatangan</td>
      <td style="padding: 4px 0;">: <?= date('d M Y, H:i', strtotime($visit['arrival_time'])) ?> WIB</td>
      <td style="padding: 4px 0; color: var(--muted);">Kategori Triase</td>
      <td style="padding: 4px 0; font-weight: 700;">
        : <span class="badge badge-triage-<?= strtolower($visit['triage_category'] ?? 'kuning') ?>">
            <?= $visit['triage_category'] ?? 'KUNING' ?> (ESI <?= $t['esi_level'] ?? '-' ?>)
          </span>
      </td>
    </tr>
  </table>

  <!-- Ringkasan Klinis & ABCDE -->
  <div style="border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 16px;">
    <h3 style="margin: 0 0 8px; font-size: 13px; font-weight: 700; color: var(--blue-900); text-transform: uppercase;">
      I. Ringkasan Pemeriksaan Primer (Primary Survey ABCDE)
    </h3>
    <table style="width: 100%; font-size: 12px; line-height: 1.5;">
      <tr>
        <td style="width: 25%; font-weight: 600;">Keluhan Utama</td>
        <td>: <?= htmlspecialchars($t['chief_complaint'] ?? '-') ?></td>
      </tr>
      <tr>
        <td style="font-weight: 600;">Tanda Vital Tiba</td>
        <td>: TD: <?= $t['systolic_bp'] ?? '-' ?>/<?= $t['diastolic_bp'] ?? '-' ?> mmHg &bull; Nadi: <?= $t['pulse_rate'] ?? '-' ?> x/m &bull; RR: <?= $t['respiratory_rate'] ?? '-' ?> x/m &bull; Suhu: <?= $t['temperature'] ?? '-' ?> &deg;C &bull; SpO2: <?= $t['spo2'] ?? '-' ?>%</td>
      </tr>
      <tr>
        <td style="font-weight: 600;">GCS (E, V, M)</td>
        <td>: Total <?= $t['gcs_total'] ?? '15' ?> (E:<?= $t['gcs_eye'] ?? '4' ?> V:<?= $t['gcs_verbal'] ?? '5' ?> M:<?= $t['gcs_motor'] ?? '6' ?>)</td>
      </tr>
      <tr>
        <td style="font-weight: 600;">Airway &amp; Breathing</td>
        <td>: <?= htmlspecialchars($a['primary_airway'] ?? 'Paten') ?> &bull; <?= htmlspecialchars($a['primary_breathing'] ?? 'Spontan') ?></td>
      </tr>
      <tr>
        <td style="font-weight: 600;">Circulation &amp; Disability</td>
        <td>: <?= htmlspecialchars($a['primary_circulation'] ?? 'Kuat') ?> &bull; <?= htmlspecialchars($a['primary_disability'] ?? 'Pupil Isokor') ?></td>
      </tr>
    </table>
  </div>

  <!-- Diagnosis Kerja -->
  <div style="border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 16px;">
    <h3 style="margin: 0 0 8px; font-size: 13px; font-weight: 700; color: var(--blue-900); text-transform: uppercase;">
      II. Diagnosis Kerja Emergensi (ICD-10)
    </h3>
    <div style="font-size: 14px; font-weight: 800; color: var(--ink);">
      [<?= htmlspecialchars($a['icd10_code'] ?? 'R68.89') ?>] <?= htmlspecialchars($a['diagnosis_name'] ?? 'Pemeriksaan Kegawatdaruratan') ?>
    </div>
    <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
      <b>Terapi &amp; Tindakan yang Diberikan:</b> <?= htmlspecialchars($a['therapy_plan'] ?? 'Observasi hemodinamik') ?>
    </div>
  </div>

  <!-- Keputusan Disposisi Akhir -->
  <div style="border: 2px solid var(--blue-900); border-radius: var(--radius-sm); padding: 16px; margin-bottom: 24px; background: var(--surface-alt);">
    <h3 style="margin: 0 0 6px; font-size: 13px; font-weight: 800; color: var(--blue-950); text-transform: uppercase;">
      III. Keputusan Disposisi Akhir
    </h3>
    <div style="font-size: 16px; font-weight: 900; color: #dc2626;">
      KEPUTUSAN: <?= htmlspecialchars($visit['disposition_type'] ?? 'RAWAT_INAP') ?>
    </div>
    <div style="font-size: 12px; color: var(--ink); margin-top: 6px;">
      <b>Instruksi &amp; Alasan Disposisi:</b><br>
      <?= nl2br(htmlspecialchars($visit['disposition_notes'] ?? 'Pasien telah mendapatkan stabilisasi awal di IGD.')) ?>
    </div>
  </div>

  <!-- Tanda Tangan -->
  <div style="display: flex; justify-content: space-between; margin-top: 36px;">
    <div style="text-align: center; width: 220px;">
      <div style="font-size: 11.5px; color: var(--muted);">Petugas Triase / Perawat Jaga</div>
      <div style="height: 60px;"></div>
      <div style="font-weight: 700; border-bottom: 1px solid var(--ink); padding-bottom: 2px;">
        ( Siti Triase, A.Md.Kep )
      </div>
      <div style="font-size: 10.5px; color: var(--muted); margin-top: 2px;">NIP: 2026.04.101</div>
    </div>

    <div style="text-align: center; width: 220px;">
      <div style="font-size: 11.5px; color: var(--muted);">Dokter Jaga IGD (DPJP)</div>
      <div style="height: 60px;"></div>
      <div style="font-weight: 700; border-bottom: 1px solid var(--ink); padding-bottom: 2px;">
        ( <?= htmlspecialchars($visit['doctor_name'] ?? 'dr. Ahmad Fauzi, Sp.PD') ?> )
      </div>
      <div style="font-size: 10.5px; color: var(--muted); margin-top: 2px;">SIP: 446/SIP/DKI/2026</div>
    </div>
  </div>
</div>

<script src="<?= base_url('assets/js/lucide.min.js') ?>"></script>
<script>
  if (window.lucide) window.lucide.createIcons();
</script>
</body>
</html>

