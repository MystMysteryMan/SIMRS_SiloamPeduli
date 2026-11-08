<?php
// app/views/nursing/notes.php - Catatan Perkembangan Keperawatan (SOAP SDKI/SIKI/SLKI) (SOP/SIMRS/013/2026)
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <div style="display: flex; align-items: center; gap: 8px;">
      <a href="<?= base_url('index.php?c=nursing&a=patient&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-sm btn-outline">
        <i data-lucide="arrow-left"></i>
      </a>
      <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900);">
        Catatan Perkembangan Keperawatan (SOAP Terpadu)
      </h1>
    </div>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Pasien: <strong><?= htmlspecialchars($patient['name']) ?></strong> (RM: <?= htmlspecialchars($patient['no_rm']) ?>) • Standar SDKI / SIKI / SLKI
    </p>
  </div>
</div>

<div class="grid-2" style="gap: 24px; align-items: flex-start;">
  <!-- Form Catat SOAP Baru -->
  <div class="card" style="padding: 24px;">
    <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 700; color: var(--blue-900); border-bottom: 1px solid var(--border); padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
      <i data-lucide="edit-3" style="width: 18px; height: 18px; color: var(--blue-700);"></i>
      <span>Catat Perkembangan Shift Baru</span>
    </h3>

    <form method="POST" action="<?= base_url('index.php?c=nursing&a=notes') ?>">
      <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
      <input type="hidden" name="encounter_type" value="<?= htmlspecialchars($encounterType) ?>">
      <input type="hidden" name="encounter_id" value="<?= (int)$encounterId ?>">

      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Dinas / Shift <span style="color: red;">*</span></label>
        <select name="shift" class="form-control" required>
          <option value="PAGI">Shift Pagi (07:00 - 14:00)</option>
          <option value="SIANG">Shift Siang (14:00 - 21:00)</option>
          <option value="MALAM">Shift Malam (21:00 - 07:00)</option>
        </select>
      </div>

      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Subjective (S) - Keluhan &amp; Pernyataan Pasien <span style="color: red;">*</span></label>
        <textarea name="subjective" rows="2" class="form-control" required placeholder="Pasien mengatakan nyeri luka operasi berkurang, mual sudah tidak ada..."></textarea>
      </div>

      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Objective (O) - Hasil Observasi Fisik &amp; TTV <span style="color: red;">*</span></label>
        <textarea name="objective" rows="2" class="form-control" required placeholder="TD: 120/80 mmHg, Nadi: 82x/m, RR: 18x/m, Suhu: 36.6 C, luka operasi bersih rembesan (-)..."></textarea>
      </div>

      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Assessment (A) - Diagnosis Keperawatan (SDKI) <span style="color: red;">*</span></label>
        <input type="text" name="nursing_diagnosis" class="form-control" required placeholder="Contoh: D.0077 Nyeri Akut b.d Agen Pencedera Fisik / D.0056 Risiko Jatuh">
      </div>

      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Interventions / Plan (P) - Rencana Tindakan (SIKI) <span style="color: red;">*</span></label>
        <textarea name="interventions" rows="2" class="form-control" required placeholder="Manajemen nyeri: observasi TTV tiap shift, pertahankan tirah baring, delegasi pemberian analgetik..."></textarea>
      </div>

      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">Evaluation (SLKI) - Evaluasi Luaran <span style="color: red;">*</span></label>
        <textarea name="evaluation" rows="2" class="form-control" required placeholder="Tingkat nyeri menurun, mobilitas fisik meningkat parsial, masalah teratasi sebagian..."></textarea>
      </div>

      <div style="margin-bottom: 20px;">
        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #1e3a8a; background: #eff6ff; padding: 10px; border-radius: 6px; border: 1px solid #bfdbfe;">
          <input type="checkbox" name="is_finalized" value="1" checked>
          <span>Finalisasi Catatan Perkembangan (Kunci verifikasi perawat)</span>
        </label>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 8px;">
        <a href="<?= base_url('index.php?c=nursing&a=patient&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-outline">
          Batal
        </a>
        <button type="submit" class="btn btn-primary">
          <i data-lucide="save"></i>
          <span>Simpan Catatan SOAP</span>
        </button>
      </div>
    </form>
  </div>

  <!-- Riwayat Catatan Perkembangan -->
  <div class="card" style="padding: 24px;">
    <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 700; color: var(--blue-900); border-bottom: 1px solid var(--border); padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
      <i data-lucide="book-open" style="width: 18px; height: 18px; color: var(--blue-700);"></i>
      <span>Riwayat Catatan SOAP Pasien</span>
    </h3>

    <?php if (empty($notesList)): ?>
      <p style="text-align: center; color: var(--muted); padding: 24px 0;">Belum ada catatan SOAP tersimpan untuk episode perawatan ini.</p>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 14px;">
        <?php foreach ($notesList as $note): ?>
          <div style="border: 1px solid var(--border); border-radius: 6px; padding: 12px; background: #fafafa;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <span class="badge" style="background: var(--blue-100); color: var(--blue-800); font-weight: 700;">
                Shift <?= htmlspecialchars($note['shift']) ?>
              </span>
              <small style="color: var(--muted);"><?= date('d/m/Y H:i', strtotime($note['note_date'])) ?> • <?= htmlspecialchars($note['nurse_name'] ?? 'Perawat') ?></small>
            </div>
            <div style="font-size: 12px; line-height: 1.5;">
              <div><strong>S:</strong> <?= htmlspecialchars($note['subjective']) ?></div>
              <div><strong>O:</strong> <?= htmlspecialchars($note['objective']) ?></div>
              <div><strong>A:</strong> <?= htmlspecialchars($note['nursing_diagnosis']) ?></div>
              <div><strong>P:</strong> <?= htmlspecialchars($note['interventions']) ?></div>
              <div><strong>E:</strong> <?= htmlspecialchars($note['evaluation']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

