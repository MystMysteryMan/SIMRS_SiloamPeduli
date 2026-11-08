<?php
// app/views/scheduling/reschedule.php - Reschedule / Penjadwalan Ulang (Lucide Icons)
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=appointment&a=index') ?>" class="btn btn-outline btn-sm">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Daftar Antrean</span>
  </a>
</div>

<div class="card" style="max-width: 680px; margin: 0 auto;">
  <div class="card-header">
    <div>
      <h3>Penjadwalan Ulang (Reschedule) Janji Temu</h3>
      <p>Mengubah tanggal atau dokter akan menerbitkan nomor antrean baru dan membebaskan kuota lama</p>
    </div>
    <span class="badge badge-warning"><?= htmlspecialchars($appointment['queue_number']) ?></span>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
      <i data-lucide="alert-triangle"></i>
      <div><?= htmlspecialchars($error) ?></div>
    </div>
  <?php endif; ?>

  <!-- Rincian Saat Ini -->
  <div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 10px; padding: 16px; margin-bottom: 20px;">
    <h4 style="margin: 0 0 10px; font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px;">Informasi Antrean Saat Ini:</h4>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;">
      <div><b>Pasien:</b> [<?= htmlspecialchars($appointment['no_rm']) ?>] <?= htmlspecialchars($appointment['patient_name']) ?></div>
      <div><b>Poliklinik:</b> <?= htmlspecialchars($appointment['policlinic_name']) ?></div>
      <div><b>Dokter Asal:</b> <?= htmlspecialchars($appointment['doctor_name']) ?></div>
      <div><b>Tanggal Asal:</b> <?= date('d M Y', strtotime($appointment['appointment_date'])) ?></div>
    </div>
  </div>

  <form method="POST" action="<?= base_url('index.php?c=appointment&a=reschedule&id=' . $appointment['id']) ?>">
    <div class="form-group">
      <label class="form-label" for="new_date">Tanggal Kunjungan Baru *</label>
      <input type="date" id="new_date" name="new_date" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="new_doctor_id">Dokter Spesialis Tujuan *</label>
      <select id="new_doctor_id" name="new_doctor_id" class="form-control" required>
        <?php foreach ($doctorsInPoli as $doc): ?>
          <option value="<?= $doc['id'] ?>" <?= ($doc['id'] == $appointment['doctor_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($doc['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="reason">Alasan Penjadwalan Ulang</label>
      <input type="text" id="reason" name="reason" class="form-control" placeholder="Contoh: Permintaan pasien berhalangan di tanggal sebelumnya">
    </div>

    <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
      <a href="<?= base_url('index.php?c=appointment&a=index') ?>" class="btn btn-outline">Batal</a>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="calendar-sync"></i>
        <span>Simpan Jadwal Baru</span>
      </button>
    </div>
  </form>
</div>
