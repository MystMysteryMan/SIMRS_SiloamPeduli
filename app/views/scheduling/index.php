<?php
// app/views/scheduling/index.php - Daftar Antrean & Jadwal Poliklinik (Lucide Icons)
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900);">
      Penjadwalan &amp; Antrean Poliklinik
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Monitor antrean pasien rawat jalan, kuota dokter per hari, penjadwalan ulang (reschedule), dan pembatalan janji temu.
    </p>
  </div>
  <a href="<?= base_url('index.php?c=appointment&a=create') ?>" class="btn btn-primary">
    <i data-lucide="plus-circle"></i>
    <span>Buat Janji Temu Baru</span>
  </a>
</div>

<!-- Filter Bar -->
<div class="card" style="padding: 16px 20px;">
  <form method="GET" action="<?= base_url('index.php') ?>" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
    <input type="hidden" name="c" value="appointment">
    <input type="hidden" name="a" value="index">

    <div style="width: 170px;">
      <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Tanggal Perjanjian</label>
      <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()">
    </div>

    <div style="width: 220px;">
      <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Poliklinik</label>
      <select name="policlinic_id" class="form-control" onchange="this.form.submit()">
        <option value="">-- Semua Poliklinik --</option>
        <?php foreach ($policlinics as $poli): ?>
          <option value="<?= $poli['id'] ?>" <?= ($selectedPoli == $poli['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($poli['name']) ?> (<?= htmlspecialchars($poli['queue_prefix']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="width: 180px;">
      <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Status Antrean</label>
      <select name="status" class="form-control" onchange="this.form.submit()">
        <option value="">-- Semua Status --</option>
        <option value="Menunggu" <?= ($selectedStatus === 'Menunggu') ? 'selected' : '' ?>>Menunggu</option>
        <option value="Sedang Dilayani" <?= ($selectedStatus === 'Sedang Dilayani') ? 'selected' : '' ?>>Sedang Dilayani</option>
        <option value="Selesai" <?= ($selectedStatus === 'Selesai') ? 'selected' : '' ?>>Selesai</option>
        <option value="Batal" <?= ($selectedStatus === 'Batal') ? 'selected' : '' ?>>Batal</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary" style="height: 38px;">
      <i data-lucide="filter"></i>
      <span>Filter</span>
    </button>
    <a href="<?= base_url('index.php?c=appointment&a=index') ?>" class="btn btn-outline" style="height: 38px;">
      <i data-lucide="calendar"></i>
      <span>Hari Ini</span>
    </a>
  </form>
</div>

<!-- Table Antrean -->
<div class="card">
  <div class="card-header">
    <h3>Daftar Antrean &amp; Janji Temu (<?= date('d M Y', strtotime($selectedDate)) ?>)</h3>
    <span class="badge badge-primary">Total: <?= count($appointments) ?> Pasien</span>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>No. Antrean</th>
          <th>No. RM</th>
          <th>Nama Pasien</th>
          <th>Penjamin</th>
          <th>Poliklinik</th>
          <th>Dokter Spesialis</th>
          <th>Keluhan Utama</th>
          <th>Status</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($appointments)): ?>
          <?php foreach ($appointments as $appt): ?>
            <tr>
              <td>
                <span class="badge badge-primary" style="font-size: 14px; font-weight: 800; padding: 5px 10px;">
                  <?= htmlspecialchars($appt['queue_number']) ?>
                </span>
              </td>
              <td><b><?= htmlspecialchars($appt['no_rm']) ?></b></td>
              <td><?= htmlspecialchars($appt['patient_name']) ?></td>
              <td>
                <span class="badge <?= $appt['insurance_type'] === 'BPJS' ? 'badge-success' : 'badge-muted' ?>">
                  <?= htmlspecialchars($appt['insurance_type']) ?>
                </span>
              </td>
              <td><?= htmlspecialchars($appt['policlinic_name']) ?></td>
              <td><?= htmlspecialchars($appt['doctor_name']) ?></td>
              <td style="font-size: 12px; color: var(--muted); max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?= htmlspecialchars($appt['chief_complaint'] ?? '-') ?>
              </td>
              <td>
                <?php
                $dispStatus = $appt['status_label'] ?? $appt['status'];
                $badgeClass = 'badge-muted';
                if (in_array($dispStatus, ['Menunggu', 'SCHEDULED'])) $badgeClass = 'badge-warning';
                if (in_array($dispStatus, ['Sedang Dilayani'])) $badgeClass = 'badge-primary';
                if (in_array($dispStatus, ['Selesai', 'COMPLETED'])) $badgeClass = 'badge-success';
                if (in_array($dispStatus, ['Batal', 'CANCELLED'])) $badgeClass = 'badge-danger';
                if (in_array($dispStatus, ['Dijadwal Ulang', 'RESCHEDULED'])) $badgeClass = 'badge-primary';
                ?>
                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($dispStatus) ?></span>
                <?php if (($dispStatus === 'Batal' || $dispStatus === 'CANCELLED' || $appt['status'] === 'CANCELLED') && !empty($appt['cancel_reason'])): ?>
                  <div style="font-size: 11px; color: #dc2626; margin-top: 4px; line-height: 1.3;" title="<?= htmlspecialchars($appt['cancel_reason']) ?>">
                    <i data-lucide="info" style="width: 11px; height: 11px; display: inline-block; vertical-align: middle;"></i>
                    <span><?= htmlspecialchars($appt['cancel_reason']) ?></span>
                  </div>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 6px;">
                  <a href="<?= base_url('index.php?c=appointment&a=printTicket&id=' . $appt['id']) ?>" target="_blank" class="btn btn-outline btn-sm" title="Cetak Tiket Antrean">
                    <i data-lucide="printer"></i>
                    <span>Tiket</span>
                  </a>

                  <?php if (in_array($appt['status'], ['SCHEDULED', 'Menunggu', 'RESCHEDULED', 'Dijadwal Ulang'])): ?>
                    <a href="<?= base_url('index.php?c=appointment&a=reschedule&id=' . $appt['id']) ?>" class="btn btn-outline btn-sm" title="Jadwal Ulang (Reschedule)">
                      <i data-lucide="calendar-sync"></i>
                      <span>Reschedule</span>
                    </a>

                    <button type="button" class="btn btn-danger btn-sm" onclick="openCancelModal(<?= $appt['id'] ?>, '<?= htmlspecialchars($appt['queue_number'], ENT_QUOTES) ?>')">
                      <i data-lucide="x-circle"></i>
                      <span>Batal</span>
                    </button>
                  <?php elseif (in_array($appt['status'], ['CANCELLED', 'Batal'])): ?>
                    <a href="<?= base_url('index.php?c=appointment&a=create&patient_id=' . $appt['patient_id']) ?>" class="btn btn-outline btn-sm" style="border-color: #3b82f6; color: #1d4ed8;" title="Daftarkan Ulang Antrean untuk Pasien Ini">
                      <i data-lucide="rotate-cw"></i>
                      <span>Daftar Ulang</span>
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="9" style="text-align: center; padding: 36px; color: var(--muted);">
              Tidak ada antrean atau janji temu yang terdaftar pada tanggal dan filter ini.
              <div style="margin-top: 10px;">
                <a href="<?= base_url('index.php?c=appointment&a=create') ?>" class="btn btn-primary btn-sm">
                  <i data-lucide="plus-circle"></i>
                  <span>Buat Janji Temu Baru</span>
                </a>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Pembatalan Janji Temu -->
<div id="cancelModal" class="modal-backdrop hidden" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100;">
  <div style="background: #fff; width: 100%; max-width: 420px; border-radius: 14px; padding: 24px; box-shadow: var(--shadow-lg);">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; color: var(--danger);">
      <i data-lucide="alert-triangle" class="lucide-lg"></i>
      <h3 style="margin: 0; font-size: 16px;">Batalkan Janji Temu / Antrean</h3>
    </div>
    <p id="cancelQueueText" style="font-size: 13px; color: var(--muted); margin-bottom: 16px;"></p>

    <form method="POST" action="<?= base_url('index.php?c=appointment&a=cancel') ?>">
      <input type="hidden" id="cancelApptId" name="id">

      <div class="form-group" style="margin-bottom: 12px;">
        <label class="form-label" for="cancel_preset">Kategori Alasan Standar RS</label>
        <select id="cancel_preset" class="form-control" onchange="applyCancelPreset(this.value)">
          <option value="">-- Pilih Kategori Alasan Pembatalan --</option>
          <option value="Pasien Berhalangan Hadir (Kendala tugas pekerjaan mendadak)">Pasien Berhalangan Hadir (Urusan mendadak / No-show)</option>
          <option value="Dokter Berhalangan / Operasi Cito Emergensi di Kamar Operasi">Dokter Berhalangan / Operasi Cito Emergensi</option>
          <option value="Kegawatan Akut - Pasien dialihkan langsung ke Triase IGD (Zona Merah)">Kegawatan Akut - Dialihkan Langsung ke IGD</option>
          <option value="Salah Input Poliklinik - Dibatalkan & didaftarkan ulang ke Poli Penyakit Dalam">Salah Pilih Poliklinik / Duplikasi Pendaftaran</option>
          <option value="Permintaan Pasien - Gejala sakit sudah mereda setelah istirahat">Permintaan Pasien (Keluhan Sakit Mereda)</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="cancel_reason">Keterangan / Alasan Pembatalan *</label>
        <textarea id="cancel_reason" name="reason" class="form-control" rows="3" placeholder="Pilih kategori di atas atau ketik alasan pembatalan..." required></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
        <button type="button" class="btn btn-outline" onclick="closeCancelModal()">Tutup</button>
        <button type="submit" class="btn btn-danger">
          <i data-lucide="x-circle"></i>
          <span>Konfirmasi Pembatalan</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function applyCancelPreset(val) {
  if (val) {
    document.getElementById('cancel_reason').value = val;
  }
}
function openCancelModal(apptId, queueNum) {
  document.getElementById('cancelApptId').value = apptId;
  document.getElementById('cancelQueueText').innerText = 'Anda akan membatalkan nomor antrean: ' + queueNum + '. Kuota dokter akan kembali dibebaskan.';
  document.getElementById('cancel_preset').selectedIndex = 0;
  document.getElementById('cancel_reason').value = '';
  document.getElementById('cancelModal').classList.remove('hidden');
  if (typeof lucide !== 'undefined') lucide.createIcons();
}
function closeCancelModal() {
  document.getElementById('cancelModal').classList.add('hidden');
}
</script>
