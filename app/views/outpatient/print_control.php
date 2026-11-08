<?php
// app/views/outpatient/print_control.php - Lembar Rencana Kontrol Ulang & Edukasi Pasien Pulang
?>

<div style="margin-bottom: 20px;" class="no-print">
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <a href="<?= base_url('index.php?c=outpatient&a=index') ?>" class="btn btn-outline">
      <i data-lucide="arrow-left"></i>
      <span>Kembali ke Rawat Jalan</span>
    </a>
    <button onclick="window.print()" class="btn btn-primary">
      <i data-lucide="printer"></i>
      <span>Cetak Lembar Kontrol</span>
    </button>
  </div>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto; padding: 32px; background: #fff; border: 1px solid #cbd5e1; box-shadow: none;">
  <!-- Kop Surat Rumah Sakit Siloam -->
  <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--blue-900); padding-bottom: 16px; margin-bottom: 20px;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <div style="width: 44px; height: 44px; background: var(--blue-900); color: #fff; font-weight: 900; font-size: 24px; display: flex; align-items: center; justify-content: center; border-radius: 6px;">
        S
      </div>
      <div>
        <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--blue-900); letter-spacing: 0.5px;">
          RUMAH SAKIT SILOAM PEDULI
        </h2>
        <div style="font-size: 11px; color: var(--muted); margin-top: 2px;">
          Jl. Siloam No. 1, Jakarta • Telp: (021) 8064-6900 • IGD 24 Jam: 1-500-911
        </div>
      </div>
    </div>
    <div style="text-align: right; font-size: 11px; color: var(--muted);">
      <div>SURAT KONTROL RAWAT JALAN</div>
      <div style="font-weight: 700; color: #1e293b;">SOP/SIMRS/005/2026</div>
    </div>
  </div>

  <div style="text-align: center; margin-bottom: 24px;">
    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: var(--blue-900); text-transform: uppercase;">
      SURAT RENCANA KONTROL ULANG &amp; EDUKASI PASIEN
    </h3>
    <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
      Nomor Kunjungan: <b><?= htmlspecialchars($visit['booking_code']) ?></b> | Tanggal Layanan: <?= htmlspecialchars($visit['visit_date']) ?>
    </div>
  </div>

  <!-- Biodata Pasien -->
  <table style="width: 100%; font-size: 13px; margin-bottom: 20px; border-collapse: collapse;">
    <tr>
      <td style="width: 140px; padding: 6px 0; color: var(--muted);">Nama Pasien</td>
      <td style="width: 10px;">:</td>
      <td style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($visit['patient_name']) ?></td>
      <td style="width: 120px; color: var(--muted);">Nomor RM</td>
      <td style="width: 10px;">:</td>
      <td style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($visit['no_rm']) ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: var(--muted);">NIK Pasien</td>
      <td>:</td>
      <td><?= htmlspecialchars($visit['nik']) ?></td>
      <td style="color: var(--muted);">Jenis Kelamin</td>
      <td>:</td>
      <td><?= htmlspecialchars($visit['gender'] === 'L' ? 'Laki-laki' : 'Perempuan') ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: var(--muted);">Penjamin / Asuransi</td>
      <td>:</td>
      <td colspan="4"><b><?= htmlspecialchars($visit['payment_type'] ?? 'UMUM') ?></b></td>
    </tr>
  </table>

  <!-- Detail Jadwal Kontrol Ulang -->
  <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
    <h4 style="margin: 0 0 12px; font-size: 13px; font-weight: 700; color: var(--blue-900); text-transform: uppercase; border-bottom: 1px dashed #cbd5e1; padding-bottom: 6px;">
      Jadwal Kunjungan Kontrol Berikutnya
    </h4>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
      <div>
        <div style="color: var(--muted); font-size: 11px;">Tanggal Kontrol Ulang:</div>
        <div style="font-size: 15px; font-weight: 800; color: #0369a1; margin-top: 2px;">
          <?= htmlspecialchars($control['control_date'] ?? date('Y-m-d', strtotime('+7 days'))) ?>
        </div>
      </div>
      <div>
        <div style="color: var(--muted); font-size: 11px;">Poliklinik Tujuan:</div>
        <div style="font-weight: 700; color: #1e293b; margin-top: 2px;">
          <?= htmlspecialchars($visit['policlinic_name']) ?>
        </div>
      </div>
      <div style="grid-column: span 2;">
        <div style="color: var(--muted); font-size: 11px;">Dokter Penanggung Jawab:</div>
        <div style="font-weight: 700; color: #1e293b; margin-top: 2px;">
          <?= htmlspecialchars($visit['doctor_name']) ?>
        </div>
      </div>
      <div style="grid-column: span 2;">
        <div style="color: var(--muted); font-size: 11px;">Instruksi Kontrol &amp; Edukasi Medis:</div>
        <div style="font-weight: 600; color: #475569; margin-top: 2px;">
          <?= htmlspecialchars($control['notes'] ?? 'Evaluasi perkembangan keluhan klinis & kepatuhan minum obat.') ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Catatan Pasien -->
  <div style="font-size: 11px; color: var(--muted); line-height: 1.5; margin-bottom: 30px;">
    <b>Perhatian Pasien:</b>
    <ol style="margin: 4px 0 0; padding-left: 18px;">
      <li>Bawalah surat rencana kontrol ini beserta kartu berobat/kartu identitas saat hari pemeriksaan.</li>
      <li>Bagi peserta BPJS Kesehatan, pastikan masa berlaku surat rujukan faskes tingkat 1 masih aktif.</li>
      <li>Jika terdapat perburukan gejala darurat sebelum tanggal kontrol, segera kunjungi IGD Siloam terdekat.</li>
    </ol>
  </div>

  <!-- Tanda Tangan Dokter -->
  <div style="display: flex; justify-content: flex-end; margin-top: 30px;">
    <div style="text-align: center; width: 220px;">
      <div style="font-size: 12px; color: var(--muted);">Jakarta, <?= date('d F Y') ?></div>
      <div style="font-size: 12px; font-weight: 600; color: #1e293b; margin-top: 2px;">Dokter Pemeriksa,</div>
      <div style="height: 55px;"></div>
      <div style="font-weight: 700; font-size: 13px; color: #1e293b; text-decoration: underline;">
        <?= htmlspecialchars($visit['doctor_name']) ?>
      </div>
      <div style="font-size: 11px; color: var(--muted);">SIP: <?= htmlspecialchars($visit['sip_number'] ?? 'SIP/DKI/2026') ?></div>
    </div>
  </div>
</div>
