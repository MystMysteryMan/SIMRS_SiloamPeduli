<?php
// app/views/nursing/index.php - Dokumentasi Asuhan Keperawatan Terpadu (SOP/SIMRS/013/2026)
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="heart-pulse" style="color: var(--blue-700); width: 22px; height: 22px;"></i>
      <span>Nursing Station &amp; Asuhan Keperawatan Terpadu</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Dokumentasi asuhan keperawatan terstandar SDKI/SIKI/SLKI, lembar MAR (6-Benar), monitoring vital signs, dan eskalasi klinis perawat ke dokter.
    </p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= base_url('index.php?c=inpatient&a=index') ?>" class="btn btn-outline">
      <i data-lucide="bed-double"></i>
      <span>Rawat Inap Bangsal</span>
    </a>
  </div>
</div>

<!-- Escalation Alert Banner if any pending escalations -->
<?php if (!empty($escalations)): ?>
  <div class="card" style="margin-bottom: 20px; border-left: 4px solid #dc2626; background: #fff5f5; padding: 16px;">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
      <div style="display: flex; gap: 12px; align-items: flex-start;">
        <i data-lucide="alert-triangle" style="color: #dc2626; width: 24px; height: 24px; flex-shrink: 0; margin-top: 2px;"></i>
        <div>
          <h3 style="margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #991b1b;">
            Perhatian: Terdapat <?= count($escalations) ?> Eskalasi Klinis Menunggu Respon Dokter DPJP
          </h3>
          <p style="margin: 0; font-size: 13px; color: #7f1d1d;">
            Perawat melaporkan perburukan kondisi klinis pasien rawat inap yang membutuhkan instruksi medis segera.
          </p>
        </div>
      </div>
    </div>

    <div style="margin-top: 14px; overflow-x: auto;">
      <table class="table" style="background: #ffffff; border-radius: 6px; font-size: 13px;">
        <thead>
          <tr style="background: #fee2e2; color: #991b1b;">
            <th>Waktu Lapor</th>
            <th>Pasien</th>
            <th>Dokter Tujuan</th>
            <th>Alasan Eskalasi</th>
            <th>Ringkasan Tanda Vital</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($escalations as $esc): ?>
            <tr>
              <td><?= date('d/m/Y H:i', strtotime($esc['created_at'])) ?></td>
              <td>
                <strong><?= htmlspecialchars($esc['patient_name'] ?? 'Pasien #' . $esc['patient_id']) ?></strong><br>
                <span class="badge" style="background: #e2e8f0; color: #475569; font-size: 11px;">RM: <?= htmlspecialchars($esc['no_rm'] ?? '-') ?></span>
              </td>
              <td><?= htmlspecialchars($esc['doctor_name'] ?? 'Dokter DPJP') ?></td>
              <td style="color: #b91c1c; font-weight: 600;"><?= htmlspecialchars($esc['escalation_reason']) ?></td>
              <td><small><?= htmlspecialchars($esc['vital_signs_summary'] ?? '-') ?></small></td>
              <td>
                <?php if (in_array($_SESSION['role'], ['admin', 'dokter'])): ?>
                  <button type="button" class="btn btn-sm btn-primary" onclick="openRespondModal(<?= (int)$esc['id'] ?>, '<?= htmlspecialchars(addslashes($esc['patient_name'] ?? 'Pasien')) ?>')">
                    <i data-lucide="message-square"></i>
                    <span>Beri Instruksi</span>
                  </button>
                <?php else: ?>
                  <span class="badge" style="background: #fee2e2; color: #991b1b;">Menunggu Dokter</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- Quick Stats -->
<div class="grid-3" style="margin-bottom: 20px;">
  <div class="stat-card">
    <div class="top">
      <span>Pasien Aktif di Bangsal</span>
      <i data-lucide="users" style="color: var(--blue-600); width: 18px; height: 18px;"></i>
    </div>
    <div class="num" style="color: var(--blue-900);"><?= count($activeInpatients) ?></div>
    <div class="desc"><span>Dalam asuhan keperawatan aktif</span></div>
  </div>

  <div class="stat-card">
    <div class="top">
      <span>Eskalasi Klinis Terbuka</span>
      <i data-lucide="alert-circle" style="color: #dc2626; width: 18px; height: 18px;"></i>
    </div>
    <div class="num" style="color: #dc2626;"><?= count($escalations) ?></div>
    <div class="desc"><span class="badge" style="background: #fee2e2; color: #991b1b;">Butuh Instruksi</span></div>
  </div>

  <div class="stat-card">
    <div class="top">
      <span>Standar KARS 2026</span>
      <i data-lucide="shield-check" style="color: #16a34a; width: 18px; height: 18px;"></i>
    </div>
    <div class="num" style="color: #16a34a;">100%</div>
    <div class="desc"><span>SDKI/SIKI &amp; MAR 6-Benar</span></div>
  </div>
</div>

<!-- Daftar Pasien Aktif di Bangsal -->
<div class="card">
  <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border);">
    <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="bed" style="width: 18px; height: 18px; color: var(--blue-600);"></i>
      <span>Daftar Pasien Aktif di Bangsal Rawat Inap</span>
    </h3>
    <span class="badge" style="background: var(--blue-100); color: var(--blue-800);">
      <?= count($activeInpatients) ?> Pasien
    </span>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>No. Registrasi &amp; Tanggal</th>
          <th>Identitas Pasien</th>
          <th>Kamar &amp; Bed</th>
          <th>Dokter DPJP</th>
          <th>Diagnosa Masuk</th>
          <th>Menu Asuhan Keperawatan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($activeInpatients)): ?>
          <tr>
            <td colspan="6" style="text-align: center; padding: 32px; color: var(--muted);">
              <i data-lucide="check-circle" style="width: 32px; height: 32px; margin-bottom: 8px; color: #16a34a; display: inline-block;"></i>
              <p style="margin: 0; font-weight: 600;">Tidak ada pasien rawat inap yang sedang aktif di bangsal.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($activeInpatients as $adm): ?>
            <tr>
              <td>
                <strong style="color: var(--blue-900); font-family: monospace; font-size: 13px;"><?= htmlspecialchars($adm['admission_number']) ?></strong><br>
                <small style="color: var(--muted);"><?= date('d/m/Y H:i', strtotime($adm['admission_date'])) ?></small>
              </td>
              <td>
                <div style="font-weight: 700; color: var(--blue-900);"><?= htmlspecialchars($adm['patient_name']) ?></div>
                <div style="font-size: 12px; color: var(--muted);">
                  RM: <span style="font-family: monospace; font-weight: 600;"><?= htmlspecialchars($adm['no_rm']) ?></span> • 
                  <?= htmlspecialchars($adm['gender'] ?? 'L') ?> • 
                  <?= htmlspecialchars($adm['age'] ?? '-') ?> thn
                </div>
              </td>
              <td>
                <strong><?= htmlspecialchars($adm['room_name'] ?? 'Kamar') ?></strong><br>
                <span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 11px;">
                  Bed <?= htmlspecialchars($adm['bed_number'] ?? '-') ?> (<?= htmlspecialchars($adm['class_name'] ?? 'Kelas') ?>)
                </span>
              </td>
              <td>
                <div style="font-weight: 600;"><?= htmlspecialchars($adm['dpjp_name'] ?? 'Dokter DPJP') ?></div>
                <small style="color: var(--muted);"><?= htmlspecialchars($adm['specialization'] ?? 'Spesialis') ?></small>
              </td>
              <td>
                <span style="font-size: 13px;"><?= htmlspecialchars($adm['admission_diagnosis'] ?? '-') ?></span>
              </td>
              <td>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                  <!-- Chart View -->
                  <a href="<?= base_url('index.php?c=nursing&a=patient&patient_id=' . $adm['patient_id'] . '&encounter_type=INPATIENT&encounter_id=' . $adm['id']) ?>" 
                     class="btn btn-sm btn-primary" title="Buka Chart Pasien">
                    <i data-lucide="clipboard-list"></i>
                    <span>Chart Asuhan</span>
                  </a>

                  <!-- Vital Signs -->
                  <a href="<?= base_url('index.php?c=nursing&a=vitalSigns&patient_id=' . $adm['patient_id'] . '&encounter_type=INPATIENT&encounter_id=' . $adm['id']) ?>" 
                     class="btn btn-sm btn-outline" title="Tanda Vital">
                    <i data-lucide="activity"></i>
                    <span>TTV</span>
                  </a>

                  <!-- MAR -->
                  <a href="<?= base_url('index.php?c=nursing&a=mar&patient_id=' . $adm['patient_id'] . '&admission_id=' . $adm['id']) ?>" 
                     class="btn btn-sm btn-outline" title="Lembar MAR">
                    <i data-lucide="pill"></i>
                    <span>MAR</span>
                  </a>

                  <!-- SOAP -->
                  <a href="<?= base_url('index.php?c=nursing&a=notes&patient_id=' . $adm['patient_id'] . '&encounter_type=INPATIENT&encounter_id=' . $adm['id']) ?>" 
                     class="btn btn-sm btn-outline" title="Catatan Perkembangan SOAP">
                    <i data-lucide="file-text"></i>
                    <span>SOAP</span>
                  </a>

                  <!-- Eskalasi -->
                  <a href="<?= base_url('index.php?c=nursing&a=escalate&patient_id=' . $adm['patient_id'] . '&encounter_type=INPATIENT&encounter_id=' . $adm['id']) ?>" 
                     class="btn btn-sm btn-outline" style="color: #dc2626; border-color: #fca5a5;" title="Eskalasi Klinis">
                    <i data-lucide="alert-triangle"></i>
                    <span>Eskalasi</span>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Respon Eskalasi Dokter -->
<div id="respondEscalationModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
  <div style="background: white; border-radius: 8px; width: 100%; max-width: 520px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
        <i data-lucide="message-square" style="color: var(--blue-700); width: 20px; height: 20px;"></i>
        <span>Instruksi Medis Dokter DPJP</span>
      </h3>
      <button type="button" onclick="closeRespondModal()" style="border: none; background: none; cursor: pointer; color: var(--muted); font-size: 18px;">&times;</button>
    </div>

    <form method="POST" action="<?= base_url('index.php?c=nursing&a=respondEscalation') ?>">
      <input type="hidden" name="escalation_id" id="modalEscalationId" value="">

      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Pasien Terlapor</label>
        <input type="text" id="modalPatientName" class="form-control" readonly style="background: #f8fafc; font-weight: 600;">
      </div>

      <div style="margin-bottom: 18px;">
        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--blue-900); margin-bottom: 4px;">
          Instruksi Medis / Rencana Tindakan DPJP <span style="color: red;">*</span>
        </label>
        <textarea name="doctor_instruction" rows="4" class="form-control" placeholder="Contoh: Berikan drip Paracetamol 1 gr IV, observasi TD tiap 15 menit, laporkan bila sistolik < 90 mmHg..." required></textarea>
        <small style="color: var(--muted); display: block; margin-top: 4px;">Instruksi terdokumentasi permanen untuk verifikasi perawat pelaksana.</small>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 8px;">
        <button type="button" class="btn btn-outline" onclick="closeRespondModal()">Batal</button>
        <button type="submit" class="btn btn-primary">
          <i data-lucide="send"></i>
          <span>Kirim Instruksi Medis</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openRespondModal(id, patientName) {
  document.getElementById('modalEscalationId').value = id;
  document.getElementById('modalPatientName').value = patientName;
  var modal = document.getElementById('respondEscalationModal');
  modal.style.display = 'flex';
  if (window.lucide) lucide.createIcons();
}

function closeRespondModal() {
  document.getElementById('respondEscalationModal').style.display = 'none';
}
</script>

