<?php
// app/views/laboratory/process.php - Proses Pengujian & Input Hasil Laboratorium
$isCito = ($order['clinical_priority'] === 'CITO');
$st = $order['status'];
$patAge = !empty($order['birth_date']) ? (date('Y') - date('Y', strtotime($order['birth_date']))) : '-';
?>

<div style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <div style="display: flex; align-items: center; gap: 8px;">
      <a href="<?= base_url('index.php?c=laboratory&a=index') ?>" class="btn btn-outline" style="height: 32px; padding: 0 10px; font-size: 12px;">
        <i data-lucide="arrow-left"></i>
        <span>Daftar Order</span>
      </a>
      <h1 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--blue-900);">
        Pemeriksaan Laboratorium Sentral
      </h1>
    </div>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Nomor Order: <b class="font-mono" style="color: var(--blue-800);"><?= htmlspecialchars($order['order_number']) ?></b> &bull; Waktu Order: <?= format_datetime_id($order['created_at']) ?>
    </p>
  </div>
  <div style="display: flex; gap: 8px;">
    <?php if ($st === 'VALID'): ?>
      <a href="<?= base_url('index.php?c=laboratory&a=printResult&id=' . $order['id']) ?>" target="_blank" class="btn btn-primary">
        <i data-lucide="printer"></i>
        <span>Cetak Lembar Hasil Resmi</span>
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Header Pasien & Status Order -->
<div class="card" style="padding: 16px 20px; margin-bottom: 20px; border-left: 4px solid <?= $isCito ? 'var(--red-600)' : 'var(--blue-600)' ?>;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 18px; font-weight: 700; color: var(--blue-950);">
          <?= htmlspecialchars($order['patient_name']) ?>
        </span>
        <span class="badge badge-primary font-mono">
          No. RM: <?= htmlspecialchars($order['no_rm']) ?>
        </span>
        <span class="badge <?= $isCito ? 'badge-danger' : 'badge-secondary' ?>">
          Prioritas: <?= htmlspecialchars($order['clinical_priority']) ?>
        </span>
        <span class="badge <?= ($order['insurance_type'] === 'BPJS') ? 'badge-success' : 'badge-secondary' ?>">
          <?= htmlspecialchars($order['insurance_type']) ?>
        </span>
      </div>
      <div style="font-size: 13px; color: var(--slate-600); margin-top: 6px; display: flex; gap: 16px; flex-wrap: wrap;">
        <span><b>JK/Usia:</b> <?= $order['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?> / <?= $patAge ?> tahun</span>
        <span><b>Gol. Darah:</b> <?= htmlspecialchars($order['blood_type'] ?? '-') ?></span>
        <span><b>Dokter Pengirim:</b> <?= htmlspecialchars($order['doctor_name'] ?? 'Dokter Poliklinik') ?></span>
        <?php if (!empty($order['diagnosis_name'])): ?>
          <span><b>Diagnosa Klinis:</b> [<?= htmlspecialchars($order['icd10_code'] ?? '') ?>] <?= htmlspecialchars($order['diagnosis_name']) ?></span>
        <?php endif; ?>
      </div>
      <?php if (!empty($order['clinical_notes'])): ?>
        <div style="margin-top: 8px; font-size: 12.5px; color: var(--slate-700); background: #f8fafc; padding: 6px 10px; border-radius: 4px; border: 1px solid var(--border);">
          <b>Catatan Klinis Dokter:</b> <?= htmlspecialchars($order['clinical_notes']) ?>
        </div>
      <?php endif; ?>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 600;">Status Order Lab</div>
      <?php
        $badgeClass = 'badge-secondary';
        if ($st === 'PENDING') $badgeClass = 'badge-warning';
        elseif ($st === 'PROCESS') $badgeClass = 'badge-info';
        elseif ($st === 'VALID') $badgeClass = 'badge-success';
      ?>
      <span class="badge <?= $badgeClass ?>" style="font-size: 13px; margin-top: 4px;">
        <?= htmlspecialchars($st) ?>
      </span>
      <?php if (!empty($order['analyst_name'])): ?>
        <div style="font-size: 11.5px; color: var(--muted); margin-top: 4px;">
          Analis: <?= htmlspecialchars($order['analyst_name']) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($st === 'PENDING'): ?>
  <!-- WORKFLOW LANGKAH 1: PENERIMAAN SAMPEL & MULAI PENGUJIAN -->
  <div class="card" style="padding: 24px; text-align: center; max-width: 680px; margin: 0 auto 30px;">
    <div style="width: 56px; height: 56px; border-radius: 50%; background: #eff6ff; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: var(--blue-600);">
      <i data-lucide="test-tube-2" style="width: 28px; height: 28px;"></i>
    </div>
    <h2 style="margin: 0 0 8px; font-size: 17px; font-weight: 700; color: var(--blue-900);">
      Penerimaan Spesimen / Sampel Pasien
    </h2>
    <p style="margin: 0 0 20px; color: var(--muted); font-size: 13px;">
      Konfirmasi bahwa tabung darah atau spesimen klinis pasien telah diambil sesuai SOP flebotomi dan siap dilakukan pemeriksaan analitik di laboratorium.
    </p>

    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 6px; padding: 14px; text-align: left; margin-bottom: 20px;">
      <b style="font-size: 12.5px; color: var(--blue-950); display: block; margin-bottom: 8px;">Daftar Item Pemeriksaan yang Diminta:</b>
      <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: var(--slate-700);">
        <?php foreach ($items as $it): ?>
          <li><b><?= htmlspecialchars($it['test_name']) ?></b> (Tarif: Rp <?= number_format($it['tariff'] ?? 0, 0, ',', '.') ?>)</li>
        <?php endforeach; ?>
      </ul>
    </div>

    <form method="POST" action="<?= base_url('index.php?c=laboratory&a=process') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <button type="submit" class="btn btn-primary" style="height: 44px; padding: 0 28px; font-size: 14px;">
        <i data-lucide="check"></i>
        <span>Terima Sampel &amp; Mulai Pengujian</span>
      </button>
    </form>
  </div>

<?php else: ?>

  <!-- WORKFLOW LANGKAH 2: FORM PENGISIAN & VALIDASI HASIL LABORATORIUM -->
  <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 24px;">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
      <div>
        <b style="color: var(--blue-900); font-size: 14.5px;">Input Nilai Parameter Hasil Pengujian</b>
        <span style="font-size: 12px; color: var(--muted); margin-left: 8px;">(Lakukan validasi rujukan dan flag evaluasi nilai kritis)</span>
      </div>
      <?php if ($st === 'VALID'): ?>
        <span class="badge badge-success">
          <i data-lucide="check-check"></i> Hasil Terverifikasi
        </span>
      <?php endif; ?>
    </div>

    <form method="POST" action="<?= base_url('index.php?c=laboratory&a=saveResult') ?>" style="padding: 20px;">
      <?= csrf_field() ?>
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

      <div class="table-responsive" style="margin-bottom: 20px;">
        <table class="table">
          <thead>
            <tr>
              <th style="width: 240px;">Pemeriksaan</th>
              <th style="width: 180px;">Hasil Pengujian <span style="color: var(--red-600);">*</span></th>
              <th style="width: 140px;">Satuan</th>
              <th style="width: 180px;">Nilai Rujukan</th>
              <th style="width: 150px;">Flag Evaluasi</th>
              <th>Catatan Analis</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td>
                  <div style="font-weight: 600; color: var(--blue-900);">
                    <?= htmlspecialchars($item['test_name']) ?>
                  </div>
                  <?php if (!empty($item['test_code'])): ?>
                    <span class="badge badge-secondary font-mono" style="font-size: 10.5px;"><?= htmlspecialchars($item['test_code']) ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <input type="text" name="results[<?= $item['id'] ?>][result_value]" class="form-control font-mono" 
                         style="font-weight: 700; color: var(--blue-950);" required 
                         placeholder="Nilai angka/teks" 
                         value="<?= htmlspecialchars($item['result_value'] ?? '') ?>">
                </td>
                <td>
                  <input type="text" name="results[<?= $item['id'] ?>][unit]" class="form-control" 
                         placeholder="g/dL, mg/dL..." 
                         value="<?= htmlspecialchars($item['result_unit'] ?? $item['default_unit'] ?? '') ?>">
                </td>
                <td>
                  <input type="text" name="results[<?= $item['id'] ?>][normal_range]" class="form-control" 
                         placeholder="Contoh: 12.0 - 16.0" 
                         value="<?= htmlspecialchars($item['result_normal_range'] ?? $item['default_normal_range'] ?? '') ?>">
                </td>
                <td>
                  <?php $currFlag = $item['result_flag'] ?? 'NORMAL'; ?>
                  <select name="results[<?= $item['id'] ?>][result_flag]" class="form-control" style="font-weight: 600;">
                    <option value="NORMAL" <?= ($currFlag === 'NORMAL') ? 'selected' : '' ?>>NORMAL</option>
                    <option value="HIGH" <?= ($currFlag === 'HIGH') ? 'selected' : '' ?> style="color: var(--amber-700);">HIGH (Tinggi)</option>
                    <option value="LOW" <?= ($currFlag === 'LOW') ? 'selected' : '' ?> style="color: var(--blue-700);">LOW (Rendah)</option>
                    <option value="CRITICAL" <?= ($currFlag === 'CRITICAL') ? 'selected' : '' ?> style="color: var(--red-700); font-weight: 700;">CRITICAL (Kritis)</option>
                  </select>
                </td>
                <td>
                  <input type="text" name="results[<?= $item['id'] ?>][analyst_notes]" class="form-control" 
                         placeholder="Catatan morfologi..." 
                         value="<?= htmlspecialchars($item['analyst_notes'] ?? '') ?>">
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 16px;">
        <div style="font-size: 12px; color: var(--muted);">
          <i data-lucide="shield-check" style="display: inline-block; vertical-align: middle; width: 14px; height: 14px;"></i>
          Validasi ini akan mencatat identitas analis pemeriksa ke dalam sistem audit trail SIMRS.
        </div>
        <div style="display: flex; gap: 10px;">
          <a href="<?= base_url('index.php?c=laboratory&a=index') ?>" class="btn btn-outline">
            <span>Batal</span>
          </a>
          <button type="submit" class="btn btn-primary" style="height: 42px; padding: 0 20px;">
            <i data-lucide="check-circle-2"></i>
            <span>Simpan &amp; Validasi Hasil Laboratorium</span>
          </button>
        </div>
      </div>
    </form>
  </div>

<?php endif; ?>

