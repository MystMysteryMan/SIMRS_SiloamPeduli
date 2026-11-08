<?php
// app/views/igd/disposition.php - Keputusan Disposisi Akhir Pasien IGD
$a = $visit['assessment'] ?? [];
$t = $visit['triage'] ?? [];
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="log-out" style="color: #10b981; width: 22px; height: 22px;"></i>
      <span>Penetapan Disposisi Akhir Pasien IGD</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Evaluasi hasil stabilisasi dan penentuan tindak lanjut perawatan: Rawat Inap, ICU, Rujuk Luar, Pulang, atau Meninggal.
    </p>
  </div>
  <div>
    <a href="<?= base_url('index.php?c=igd&a=index') ?>" class="btn btn-outline">
      <i data-lucide="arrow-left"></i>
      <span>Kembali ke IGD</span>
    </a>
  </div>
</div>

<!-- Patient Header Card -->
<div class="card" style="margin-bottom: 20px; background: #fff; padding: 18px 20px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
    <div>
      <div style="display: flex; align-items: center; gap: 8px;">
        <span class="badge badge-triage-<?= strtolower($visit['triage_category'] ?? 'kuning') ?>">
          TRIAGE: <?= $visit['triage_category'] ?? 'KUNING' ?>
        </span>
        <span style="font-family: ui-monospace, monospace; font-size: 13px; font-weight: 700; color: var(--blue-900);">
          <?= htmlspecialchars($visit['visit_number']) ?>
        </span>
      </div>
      <h2 style="margin: 6px 0 2px; font-size: 18px; font-weight: 700; color: var(--ink);">
        <?= htmlspecialchars($visit['patient_name'] ?? $visit['anonymous_name']) ?>
      </h2>
      <div style="font-size: 12px; color: var(--muted); display: flex; gap: 10px;">
        <span>No. RM: <b><?= htmlspecialchars($visit['no_rm'] ?? 'MR/MRS. X') ?></b></span>
        <span>&bull;</span>
        <span>Penjamin: <b><?= htmlspecialchars($visit['insurance_type'] ?? 'UMUM') ?></b></span>
      </div>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 11px; color: var(--muted); text-transform: uppercase; font-weight: 700;">Diagnosis Kerja DPJP</div>
      <div style="font-size: 13.5px; font-weight: 700; color: var(--blue-900); margin-top: 2px;">
        [<?= htmlspecialchars($a['icd10_code'] ?? 'Observasi') ?>] <?= htmlspecialchars($a['diagnosis_name'] ?? 'Dalam Evaluasi') ?>
      </div>
    </div>
  </div>
</div>

<form method="POST" action="<?= base_url('index.php?c=igd&a=storeDisposition') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="emergency_visit_id" value="<?= $visit['id'] ?>">

  <div class="card" style="margin-bottom: 20px;">
    <h3 style="margin: 0 0 16px; font-size: 14px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="compass" class="lucide-md" style="color: #10b981;"></i>
      <span>Pilih Keputusan Disposisi Medis <span style="color: var(--danger);">*</span></span>
    </h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-bottom: 20px;">
      <!-- Rawat Inap -->
      <label class="card" style="padding: 16px; cursor: pointer; border-left: 4px solid var(--blue-600); background: #fff;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
          <input type="radio" name="disposition_type" value="RAWAT_INAP" <?= (($visit['disposition_type'] ?? 'RAWAT_INAP') === 'RAWAT_INAP') ? 'checked' : '' ?> required>
          <span style="font-weight: 700; color: var(--blue-900); font-size: 13.5px;">RAWAT INAP</span>
        </div>
        <div style="font-size: 11.5px; color: var(--muted);">
          Pasien dipindahkan ke bangsal rawat inap untuk tatalaksana lanjutan.
        </div>
      </label>

      <!-- ICU -->
      <label class="card" style="padding: 16px; cursor: pointer; border-left: 4px solid #dc2626; background: #fff;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
          <input type="radio" name="disposition_type" value="ICU" <?= (($visit['disposition_type'] ?? '') === 'ICU') ? 'checked' : '' ?> required>
          <span style="font-weight: 700; color: #dc2626; font-size: 13.5px;">ICU / INTENSIVE</span>
        </div>
        <div style="font-size: 11.5px; color: var(--muted);">
          Pasien memerlukan monitoring ketat hemodinamik dan ventilator.
        </div>
      </label>

      <!-- Rujuk -->
      <label class="card" style="padding: 16px; cursor: pointer; border-left: 4px solid #d97706; background: #fff;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
          <input type="radio" name="disposition_type" value="RUJUK" <?= (($visit['disposition_type'] ?? '') === 'RUJUK') ? 'checked' : '' ?> required>
          <span style="font-weight: 700; color: #b45309; font-size: 13.5px;">RUJUK KE RS LAIN</span>
        </div>
        <div style="font-size: 11.5px; color: var(--muted);">
          Alih rawat ke fasilitas kesehatan rujukan yang memiliki fasilitas khusus.
        </div>
      </label>

      <!-- Pulang -->
      <label class="card" style="padding: 16px; cursor: pointer; border-left: 4px solid #16a34a; background: #fff;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
          <input type="radio" name="disposition_type" value="PULANG" <?= (($visit['disposition_type'] ?? '') === 'PULANG') ? 'checked' : '' ?> required>
          <span style="font-weight: 700; color: #15803d; font-size: 13.5px;">PULANG / RAWAT JALAN</span>
        </div>
        <div style="font-size: 11.5px; color: var(--muted);">
          Kondisi stabil, gejala membaik, kontrol poliklinik terjadwal.
        </div>
      </label>

      <!-- Meninggal -->
      <label class="card" style="padding: 16px; cursor: pointer; border-left: 4px solid #0f172a; background: #fff;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
          <input type="radio" name="disposition_type" value="MENINGGAL" <?= (($visit['disposition_type'] ?? '') === 'MENINGGAL') ? 'checked' : '' ?> required>
          <span style="font-weight: 700; color: #0f172a; font-size: 13.5px;">MENINGGAL</span>
        </div>
        <div style="font-size: 11.5px; color: var(--muted);">
          Pasien dinyatakan meninggal dunia secara klinis di ruang resusitasi IGD.
        </div>
      </label>
    </div>

    <!-- Catatan Tambahan / Resume Disposisi -->
    <div>
      <label class="form-label" style="font-weight: 700;">Catatan Instruksi &amp; Alasan Disposisi</label>
      <textarea name="disposition_notes" class="form-control" rows="3" placeholder="Tuliskan alasan pemilihan disposisi, ruangan/faskes tujuan, instruksi obat pulang, atau jam henti jantung jika meninggal..."><?= htmlspecialchars($visit['disposition_notes'] ?? 'Kondisi stabil setelah resusitasi cairan dan pemberian medikasi awal.') ?></textarea>
    </div>
  </div>

  <!-- Submit Buttons -->
  <div style="display: flex; gap: 12px; justify-content: flex-end;">
    <a href="<?= base_url('index.php?c=igd&a=index') ?>" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary" style="background: #10b981; border-color: #047857; padding: 10px 24px; font-weight: 700;">
      <i data-lucide="check"></i>
      <span>Tetapkan Disposisi &amp; Cetak Surat</span>
    </button>
  </div>
</form>

