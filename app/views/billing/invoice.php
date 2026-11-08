<?php
// app/views/billing/invoice.php - Rincian Agregasi Biaya & Penerbitan Faktur
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="calculator" style="color: #059669; width: 22px; height: 22px;"></i>
      <span>Agregasi Biaya Otomatis &amp; Penerbitan Invoice</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Penghitungan otomatis seluruh komponen tarif pelayanan medis terintegrasi: DPJP, CPOE, Lab, Radiologi, dan Farmasi.
    </p>
  </div>
  <div>
    <a href="<?= base_url('index.php?c=billing&a=index') ?>" class="btn btn-outline">
      <i data-lucide="arrow-left"></i>
      <span>Kembali ke Billing</span>
    </a>
  </div>
</div>

<form method="POST" action="<?= base_url('index.php?c=billing&a=generateInvoice') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="visit_type" value="<?= htmlspecialchars($calc['visit_type']) ?>">
  <input type="hidden" name="reference_id" value="<?= htmlspecialchars($calc['reference_id']) ?>">
  <input type="hidden" name="patient_id" value="<?= htmlspecialchars($calc['patient_id']) ?>">
  <input type="hidden" name="doctor_id" value="<?= htmlspecialchars($calc['doctor_id'] ?? '') ?>">
  <input type="hidden" name="patient_type" value="<?= htmlspecialchars($calc['patient_type']) ?>">
  <input type="hidden" name="insurance_number" value="<?= htmlspecialchars($calc['insurance_number'] ?? '') ?>">
  <input type="hidden" name="icd10_code" value="<?= htmlspecialchars($calc['icd10_code'] ?? 'Z00.0') ?>">
  <input type="hidden" name="total_gross" value="<?= $calc['total_gross'] ?>">
  <input type="hidden" name="insurance_coverage" value="<?= $calc['insurance_coverage'] ?>">
  <input type="hidden" name="patient_amount" value="<?= $calc['patient_amount'] ?>">

  <!-- Info Pasien & Penjamin Card -->
  <div class="card" style="margin-bottom: 20px; background: #fff; padding: 18px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px;">
      <div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
          <span class="badge badge-primary">UNIT: <?= htmlspecialchars($calc['visit_type']) ?></span>
          <?php if ($calc['patient_type'] === 'BPJS'): ?>
            <span class="badge badge-success">PENJAMIN: BPJS KESEHATAN</span>
          <?php elseif ($calc['patient_type'] === 'ASURANSI'): ?>
            <span class="badge badge-info">PENJAMIN: ASURANSI SWASTA</span>
          <?php else: ?>
            <span class="badge badge-muted">PENJAMIN: UMUM (MANDIRI)</span>
          <?php endif; ?>
        </div>
        <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--ink);">
          <?= htmlspecialchars($calc['patient_name']) ?>
        </h2>
        <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">
          No. RM: <b><?= htmlspecialchars($calc['no_rm']) ?></b> &bull; No. Polis: <b><?= htmlspecialchars($calc['insurance_number'] ?? '-') ?></b>
        </div>
      </div>

      <div style="text-align: right;">
        <div style="font-size: 11.5px; color: var(--muted);">Dokter Pemeriksa:</div>
        <div style="font-size: 13.5px; font-weight: 700; color: var(--blue-900);">
          <?= htmlspecialchars($calc['doctor_name']) ?>
        </div>
        <div style="font-size: 11px; color: var(--muted); margin-top: 2px;">
          Diagnosis ICD-10: <b class="badge badge-primary"><?= htmlspecialchars($calc['icd10_code']) ?></b>
        </div>
      </div>
    </div>
  </div>

  <!-- Rincian Komponen Biaya Pelayanan -->
  <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 20px;">
    <div style="padding: 14px 18px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
      <h3 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--ink);">
        Rincian Rinci Layanan &amp; Tarif (Itemized Costs)
      </h3>
      <span class="badge badge-muted"><?= count($calc['items']) ?> Komponen Biaya</span>
    </div>

    <div style="overflow-x: auto;">
      <table class="table" style="margin: 0;">
        <thead>
          <tr>
            <th style="width: 15%;">Kategori</th>
            <th style="width: 45%;">Nama Layanan / Tindakan / Obat</th>
            <th style="text-align: right; width: 10%;">Kuantitas</th>
            <th style="text-align: right; width: 15%;">Tarif Satuan</th>
            <th style="text-align: right; width: 15%;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($calc['items'] as $idx => $it): ?>
            <tr>
              <td>
                <input type="hidden" name="item_type[]" value="<?= htmlspecialchars($it['item_type']) ?>">
                <input type="hidden" name="item_name[]" value="<?= htmlspecialchars($it['item_name']) ?>">
                <input type="hidden" name="quantity[]" value="<?= (int)$it['quantity'] ?>">
                <input type="hidden" name="unit_price[]" value="<?= (float)$it['unit_price'] ?>">
                <input type="hidden" name="subtotal[]" value="<?= (float)$it['subtotal'] ?>">
                <input type="hidden" name="reference_table[]" value="<?= htmlspecialchars($it['reference_table'] ?? '') ?>">
                <input type="hidden" name="reference_id_item[]" value="<?= htmlspecialchars($it['reference_id'] ?? '') ?>">

                <?php if ($it['item_type'] === 'KONSULTASI'): ?>
                  <span class="badge badge-primary">Konsultasi</span>
                <?php elseif ($it['item_type'] === 'TINDAKAN'): ?>
                  <span class="badge badge-warning">Tindakan</span>
                <?php elseif ($it['item_type'] === 'LABORATORIUM'): ?>
                  <span class="badge badge-info">Laboratorium</span>
                <?php elseif ($it['item_type'] === 'RADIOLOGI'): ?>
                  <span class="badge badge-primary">Radiologi</span>
                <?php elseif ($it['item_type'] === 'OBAT'): ?>
                  <span class="badge badge-success">Farmasi / Obat</span>
                <?php else: ?>
                  <span class="badge badge-muted">Administrasi</span>
                <?php endif; ?>
              </td>
              <td style="font-weight: 600; color: var(--ink);">
                <?= htmlspecialchars($it['item_name']) ?>
              </td>
              <td class="num"><?= (int)$it['quantity'] ?></td>
              <td class="num">Rp <?= number_format($it['unit_price'], 0, ',', '.') ?></td>
              <td class="num" style="font-weight: 700; color: var(--blue-900);">
                Rp <?= number_format($it['subtotal'], 0, ',', '.') ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Kotak Kalkulasi Tagihan & Pertanggungan Penjamin -->
  <div class="card" style="margin-bottom: 24px; padding: 20px; background: var(--surface-alt); border: 2px solid var(--line);">
    <div style="display: flex; justify-content: flex-end;">
      <div style="width: 100%; max-width: 440px;">
        <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 13.5px; color: var(--ink-light);">
          <span>Subtotal Tagihan Kotor (Gross Total):</span>
          <span style="font-family: ui-monospace, monospace; font-weight: 700;">
            Rp <?= number_format($calc['total_gross'], 0, ',', '.') ?>
          </span>
        </div>

        <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 13.5px; color: #047857; border-bottom: 1px dashed var(--line);">
          <span>Pertanggungan Penjamin (<?= htmlspecialchars($calc['patient_type']) ?>):</span>
          <span style="font-family: ui-monospace, monospace; font-weight: 700;">
            - Rp <?= number_format($calc['insurance_coverage'], 0, ',', '.') ?>
          </span>
        </div>

        <div style="display: flex; justify-content: space-between; padding: 12px 0 0; font-size: 16px; font-weight: 800; color: var(--blue-950);">
          <span>Total Kewajiban Bayar Pasien (Co-Pay):</span>
          <span style="font-family: ui-monospace, monospace; font-size: 20px; color: <?= ($calc['patient_amount'] > 0) ? '#dc2626' : '#047857' ?>;">
            Rp <?= number_format($calc['patient_amount'], 0, ',', '.') ?>
          </span>
        </div>

        <?php if ($calc['patient_type'] === 'BPJS'): ?>
          <div style="margin-top: 8px; font-size: 11.5px; color: #047857; background: #ecfdf5; padding: 6px 10px; border-radius: var(--radius-xs);">
            <i data-lucide="check" class="lucide-sm"></i>
            Pasien terdaftar BPJS Kesehatan dengan manfaat aktif. 100% biaya medis ditanggung penjamin.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Tombol Aksi -->
  <div style="display: flex; gap: 12px; justify-content: flex-end;">
    <a href="<?= base_url('index.php?c=billing&a=index') ?>" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary" style="background: #059669; border-color: #047857; padding: 10px 24px; font-weight: 700;">
      <i data-lucide="receipt"></i>
      <span>Terbitkan Invoice &amp; Buka Kasir</span>
    </button>
  </div>
</form>

