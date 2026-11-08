<?php
// app/views/master_data/doctors.php - Master Dokter & Jadwal Praktek (Lucide Icons)
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=master&a=index') ?>" class="btn btn-outline btn-sm">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Pusat Master Data</span>
  </a>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger" style="margin-bottom: 16px;">
    <i data-lucide="alert-triangle"></i>
    <div><?= htmlspecialchars($error) ?></div>
  </div>
<?php endif; ?>

<div class="grid-2">
  <!-- Form 1: Tambah Dokter -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3>1. Tambah Dokter Spesialis</h3>
        <p>Pendaftaran tenaga medis spesialis</p>
      </div>
      <i data-lucide="stethoscope" class="lucide-md" style="color: var(--blue-600);"></i>
    </div>

    <form method="POST" action="<?= base_url('index.php?c=master&a=doctors') ?>">
      <input type="hidden" name="action_type" value="create_doctor">

      <div class="form-group">
        <label class="form-label" for="name">Nama Lengkap Dokter *</label>
        <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: dr. Budi Santoso, Sp.PD" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="policlinic_id">Unit Poliklinik *</label>
        <select id="policlinic_id" name="policlinic_id" class="form-control" required>
          <option value="">-- Pilih Poliklinik --</option>
          <?php foreach ($policlinics as $poli): ?>
            <option value="<?= $poli['id'] ?>"><?= htmlspecialchars($poli['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label class="form-label" for="sip_number">Nomor SIP</label>
          <input type="text" id="sip_number" name="sip_number" class="form-control" placeholder="SIP.446/...">
        </div>
        <div class="form-group">
          <label class="form-label" for="phone">No. Telepon / WA</label>
          <input type="tel" id="phone" name="phone" class="form-control" placeholder="08xxxxxxxxxx">
        </div>
      </div>

      <div style="margin-top: 16px;">
        <button type="submit" class="btn btn-primary" style="width: 100%;">
          <i data-lucide="save"></i>
          <span>Simpan Data Dokter</span>
        </button>
      </div>
    </form>
  </div>

  <!-- Form 2: Tambah Jadwal Praktek & Kuota -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3>2. Jadwal Praktek &amp; Batas Kuota</h3>
        <p>Tentukan kuota harian penerimaan pasien</p>
      </div>
      <i data-lucide="calendar" class="lucide-md" style="color: var(--green-600);"></i>
    </div>

    <form method="POST" action="<?= base_url('index.php?c=master&a=doctors') ?>">
      <input type="hidden" name="action_type" value="add_schedule">

      <div class="form-group">
        <label class="form-label" for="doctor_id">Pilih Dokter *</label>
        <select id="doctor_id" name="doctor_id" class="form-control" required>
          <option value="">-- Pilih Dokter --</option>
          <?php foreach ($doctors as $doc): ?>
            <option value="<?= $doc['id'] ?>"><?= htmlspecialchars($doc['name']) ?> (<?= htmlspecialchars($doc['policlinic_name']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label class="form-label" for="day_of_week">Hari Praktek *</label>
          <select id="day_of_week" name="day_of_week" class="form-control" required>
            <option value="Senin">Senin</option>
            <option value="Selasa">Selasa</option>
            <option value="Rabu">Rabu</option>
            <option value="Kamis">Kamis</option>
            <option value="Jumat">Jumat</option>
            <option value="Sabtu">Sabtu</option>
            <option value="Minggu">Minggu</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="quota_per_day">Kuota Pasien / Hari *</label>
          <input type="number" id="quota_per_day" name="quota_per_day" class="form-control" value="20" min="1" max="100" required>
        </div>
      </div>

      <div class="grid-2">
        <div class="form-group">
          <label class="form-label" for="start_time">Jam Mulai *</label>
          <input type="time" id="start_time" name="start_time" class="form-control" value="08:00" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="end_time">Jam Selesai *</label>
          <input type="time" id="end_time" name="end_time" class="form-control" value="12:00" required>
        </div>
      </div>

      <div style="margin-top: 16px;">
        <button type="submit" class="btn btn-success" style="width: 100%;">
          <i data-lucide="plus-circle"></i>
          <span>Daftarkan Jadwal &amp; Kuota</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Daftar Dokter & Jadwal Praktek -->
<div class="card">
  <div class="card-header">
    <div>
      <h3>Daftar Dokter &amp; Jadwal Praktek</h3>
      <p>Data jadwal pelayanan dan kuota harian dokter spesialis</p>
    </div>
    <span class="badge badge-primary"><?= count($doctors) ?> Dokter Terdaftar</span>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Nama Dokter</th>
          <th>Poliklinik</th>
          <th>No. SIP</th>
          <th>Jadwal Praktek &amp; Kuota</th>
          <th>Status</th>
          <th style="text-align: right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($doctors as $doc): ?>
          <?php
            $schedules = $doc['schedules'] ?? [];
          ?>
          <tr>
            <td><b><?= htmlspecialchars($doc['name']) ?></b></td>
            <td><?= htmlspecialchars($doc['policlinic_name']) ?></td>
            <td><?= htmlspecialchars($doc['sip_number'] ?? '-') ?></td>
            <td>
              <?php if (!empty($schedules)): ?>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                  <?php foreach ($schedules as $s): ?>
                    <div style="font-size: 11.5px; background: #f8fafc; border: 1px solid var(--line); border-radius: 6px; padding: 3px 8px; display: inline-flex; justify-content: space-between; align-items: center; gap: 10px;">
                      <span><b><?= $s['day_of_week'] ?></b> (<?= substr($s['start_time'], 0, 5) ?>-<?= substr($s['end_time'], 0, 5) ?>)</span>
                      <span class="badge badge-primary" style="font-size: 10.5px;">Kuota: <?= $s['quota_per_day'] ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <span style="color: var(--muted); font-size: 11px;">Belum ada jadwal praktek</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= $doc['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                <?= $doc['is_active'] ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </td>
            <td style="text-align: right;">
              <a href="<?= base_url('index.php?c=master&a=toggleDoctor&id=' . $doc['id']) ?>" 
                 class="btn <?= $doc['is_active'] ? 'btn-danger' : 'btn-success' ?> btn-sm">
                <i data-lucide="<?= $doc['is_active'] ? 'x-circle' : 'check-circle' ?>"></i>
                <span><?= $doc['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></span>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
