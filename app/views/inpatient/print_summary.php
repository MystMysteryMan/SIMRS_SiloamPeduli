<?php
// app/views/inpatient/print_summary.php - Lembar Cetak Resmi Resume Medis / Discharge Summary Pasien Rawat Inap
$daysStay = max(1, ceil((strtotime($admission['discharge_date'] ?? date('Y-m-d H:i:s')) - strtotime($admission['admission_date'])) / 86400));
?>

<div style="margin-bottom: 20px;" class="no-print">
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <a href="<?= base_url('index.php?c=inpatient&a=index') ?>" class="btn btn-outline">
      <i data-lucide="arrow-left"></i>
      <span>Kembali ke Rawat Inap</span>
    </a>
    <button onclick="window.print()" class="btn btn-primary">
      <i data-lucide="printer"></i>
      <span>Cetak Resume Medis</span>
    </button>
  </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto; padding: 36px; background: #fff; border: 1px solid #cbd5e1; box-shadow: none;">
  <!-- Kop Surat Rumah Sakit Siloam -->
  <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--blue-900); padding-bottom: 16px; margin-bottom: 20px;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <div style="width: 46px; height: 46px; background: var(--blue-900); color: #fff; font-weight: 900; font-size: 26px; display: flex; align-items: center; justify-content: center; border-radius: 6px;">
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
      <div>RESUME MEDIS RAWAT INAP</div>
      <div style="font-weight: 700; color: #1e293b;">SOP/SIMRS/006/2026</div>
    </div>
  </div>

  <div style="text-align: center; margin-bottom: 24px;">
    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: var(--blue-900); text-transform: uppercase;">
      RINGKASAN KELUAR / RESUME MEDIS (DISCHARGE SUMMARY)
    </h3>
    <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
      Nomor Registrasi: <b><?= htmlspecialchars($admission['admission_number']) ?></b>
    </div>
  </div>

  <!-- Biodata Pasien & Episode Rawat -->
  <table style="width: 100%; font-size: 12px; margin-bottom: 20px; border-collapse: collapse; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
    <tr>
      <td style="width: 130px; padding: 4px 0; color: var(--muted);">Nama Pasien</td>
      <td style="width: 10px;">:</td>
      <td style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($admission['patient_name']) ?></td>
      <td style="width: 130px; color: var(--muted);">Nomor Rekam Medis</td>
      <td style="width: 10px;">:</td>
      <td style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($admission['no_rm']) ?></td>
    </tr>
    <tr>
      <td style="padding: 4px 0; color: var(--muted);">NIK / No. KTP</td>
      <td>:</td>
      <td><?= htmlspecialchars($admission['nik']) ?></td>
      <td style="color: var(--muted);">Jenis Kelamin / Lahir</td>
      <td>:</td>
      <td><?= htmlspecialchars($admission['gender'] === 'L' ? 'Laki-laki' : 'Perempuan') ?> / <?= htmlspecialchars($admission['birth_date']) ?></td>
    </tr>
    <tr>
      <td style="padding: 4px 0; color: var(--muted);">Kamar &amp; Ruangan</td>
      <td>:</td>
      <td><?= htmlspecialchars($admission['room_name']) ?> (Bed <?= htmlspecialchars($admission['bed_number']) ?>)</td>
      <td style="color: var(--muted);">Kelas Perawatan</td>
      <td>:</td>
      <td><?= htmlspecialchars(str_replace('_', ' ', $admission['class_type'])) ?></td>
    </tr>
    <tr>
      <td style="padding: 4px 0; color: var(--muted);">Tanggal Masuk</td>
      <td>:</td>
      <td><?= date('d/m/Y H:i', strtotime($admission['admission_date'])) ?></td>
      <td style="color: var(--muted);">Tanggal Keluar</td>
      <td>:</td>
      <td><?= !empty($admission['discharge_date']) ? date('d/m/Y H:i', strtotime($admission['discharge_date'])) : '-' ?> (LOS: <?= $daysStay ?> Hari)</td>
    </tr>
    <tr>
      <td style="padding: 4px 0; color: var(--muted);">Dokter DPJP</td>
      <td>:</td>
      <td><b><?= htmlspecialchars($admission['doctor_name']) ?></b></td>
      <td style="color: var(--muted);">Jenis Penjamin</td>
      <td>:</td>
      <td><b><?= htmlspecialchars($admission['insurance_type']) ?></b></td>
    </tr>
  </table>

  <!-- Bagian Klinis & Diagnosis -->
  <div style="font-size: 13px; margin-bottom: 24px;">
    <div style="margin-bottom: 14px;">
      <div style="font-weight: 700; color: var(--blue-900); font-size: 13px; text-transform: uppercase; margin-bottom: 4px;">
        1. Diagnosis Masuk &amp; Diagnosis Akhir
      </div>
      <div style="padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
        <div style="margin-bottom: 4px;">Diagnosis Masuk: <b><?= htmlspecialchars($summary['admission_diagnosis_icd10'] ?? '-') ?></b></div>
        <div>Diagnosis Akhir: <b style="color: #b91c1c; font-size: 14px;"><?= htmlspecialchars($summary['discharge_diagnosis_icd10'] ?? '-') ?></b> - <i><?= htmlspecialchars($summary['diagnosis_description'] ?? '') ?></i></div>
      </div>
    </div>

    <?php if (!empty($summary['procedures_icd9cm'])): ?>
      <div style="margin-bottom: 14px;">
        <div style="font-weight: 700; color: var(--blue-900); font-size: 13px; text-transform: uppercase; margin-bottom: 4px;">
          2. Tindakan / Prosedur Medis (ICD-9-CM)
        </div>
        <div style="padding: 8px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
          <?= htmlspecialchars($summary['procedures_icd9cm']) ?>
        </div>
      </div>
    <?php endif; ?>

    <div style="margin-bottom: 14px;">
      <div style="font-weight: 700; color: var(--blue-900); font-size: 13px; text-transform: uppercase; margin-bottom: 4px;">
        3. Ringkasan Perjalanan Penyakit (Hospital Course)
      </div>
      <div style="padding: 8px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; line-height: 1.5;">
        <?= nl2br(htmlspecialchars($summary['hospital_course'] ?? 'Perawatan selesai dengan hasil evaluasi membaik.')) ?>
      </div>
    </div>

    <div style="margin-bottom: 14px;">
      <div style="font-weight: 700; color: var(--blue-900); font-size: 13px; text-transform: uppercase; margin-bottom: 4px;">
        4. Keadaan Saat Keluar &amp; Rencana Kontrol
      </div>
      <div style="padding: 8px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
        <div>Kondisi Keluar: <b style="color: #15803d;"><?= htmlspecialchars($summary['condition_at_discharge'] ?? 'MEMBAIK') ?></b></div>
        <div>Tanggal Kontrol: <b><?= htmlspecialchars($summary['control_date'] ?? '-') ?></b></div>
      </div>
    </div>

    <?php if (!empty($summary['take_home_medications'])): ?>
      <div style="margin-bottom: 14px;">
        <div style="font-weight: 700; color: var(--blue-900); font-size: 13px; text-transform: uppercase; margin-bottom: 4px;">
          5. Terapi &amp; Obat Pulang (Take-Home Medications)
        </div>
        <div style="padding: 8px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
          <?= nl2br(htmlspecialchars($summary['take_home_medications'])) ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($summary['discharge_instructions'])): ?>
      <div style="margin-bottom: 14px;">
        <div style="font-weight: 700; color: var(--blue-900); font-size: 13px; text-transform: uppercase; margin-bottom: 4px;">
          6. Instruksi Perawatan Rumah &amp; Tanda Bahaya
        </div>
        <div style="padding: 8px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
          <?= nl2br(htmlspecialchars($summary['discharge_instructions'])) ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Pengesahan Dokter DPJP -->
  <div style="display: flex; justify-content: flex-end; margin-top: 36px;">
    <div style="text-align: center; width: 240px;">
      <div style="font-size: 12px; color: var(--muted);">Jakarta, <?= date('d F Y') ?></div>
      <div style="font-size: 12px; font-weight: 600; color: #1e293b; margin-top: 2px;">Dokter DPJP Yang Merawat,</div>
      <div style="height: 60px;"></div>
      <div style="font-weight: 700; font-size: 13px; color: #1e293b; text-decoration: underline;">
        <?= htmlspecialchars($admission['doctor_name']) ?>
      </div>
      <div style="font-size: 11px; color: var(--muted);">SIP: <?= htmlspecialchars($admission['sip_number'] ?? 'SIP/DKI/2026') ?></div>
    </div>
  </div>
</div>

