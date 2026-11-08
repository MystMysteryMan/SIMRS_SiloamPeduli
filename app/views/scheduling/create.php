<?php
// app/views/scheduling/create.php - Buat Janji Temu & Antrean Poliklinik (SOP/SIMRS/012/2026)
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=appointment&a=index') ?>" class="btn btn-outline btn-sm">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Daftar Antrean</span>
  </a>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
  <div class="card-header">
    <div>
      <h3>Pendaftaran Janji Temu &amp; Antrean Pasien</h3>
      <p>Sistem akan memvalidasi kuota dokter harian dan menerbitkan nomor antrean otomatis</p>
    </div>
    <span class="badge badge-success">
      <i data-lucide="check-circle-2"></i>
      <span>Validasi Kuota Real-time</span>
    </span>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
      <i data-lucide="alert-triangle"></i>
      <div><?= htmlspecialchars($error) ?></div>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?= base_url('index.php?c=appointment&a=create') ?>" id="apptForm">
    <!-- 1. Pasien -->
    <div class="form-group">
      <label class="form-label" for="patient_id">Pilih Pasien Terdaftar *</label>
      <select id="patient_id" name="patient_id" class="form-control" required>
        <option value="">-- Cari / Pilih Pasien Berdasarkan No. RM &amp; Nama --</option>
        <?php foreach ($patients as $p): ?>
          <option value="<?= $p['id'] ?>" <?= ($preselectedPatient && $preselectedPatient['id'] == $p['id']) ? 'selected' : '' ?>>
            [<?= htmlspecialchars($p['no_rm']) ?>] <?= htmlspecialchars($p['name']) ?> (NIK: <?= htmlspecialchars($p['nik']) ?> - <?= htmlspecialchars($p['insurance_type']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <div style="margin-top: 6px; font-size: 11.5px; color: var(--muted); display: flex; align-items: center; gap: 4px;">
        <i data-lucide="info" class="lucide-sm"></i>
        <?php if (in_array(strtolower($_SESSION['role'] ?? ''), ['admin', 'registrasi', 'petugas registrasi', 'kasir'])): ?>
          <span>Pasien belum memiliki No. RM?</span>
          <a href="<?= base_url('index.php?c=patient&a=create') ?>" style="font-weight: 700; color: var(--blue-700);">Daftarkan di Unit Pendaftaran &raquo;</a>
        <?php else: ?>
          <span>Pasien belum memiliki No. RM? Pendaftaran pasien baru dan penerbitan No. RM hanya dapat dilakukan di Unit Pendaftaran Pasien atau Kasir/Pembayaran.</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- 2. Poliklinik & Dokter -->
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label" for="policlinic_id">Tujuan Poliklinik *</label>
        <select id="policlinic_id" name="policlinic_id" class="form-control" required onchange="filterDoctorsByPoli(this.value)">
          <option value="">-- Pilih Poliklinik --</option>
          <?php foreach ($policlinics as $poli): ?>
            <option value="<?= $poli['id'] ?>" data-prefix="<?= htmlspecialchars($poli['queue_prefix']) ?>">
              <?= htmlspecialchars($poli['name']) ?> (Awalan Antrean: <?= htmlspecialchars($poli['queue_prefix']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="doctor_id">Dokter Spesialis *</label>
        <select id="doctor_id" name="doctor_id" class="form-control" required onchange="checkQuota()">
          <option value="">-- Pilih Dokter Terlebih Dahulu --</option>
          <?php foreach ($doctors as $doc): ?>
            <option value="<?= $doc['id'] ?>" data-poli="<?= $doc['policlinic_id'] ?>">
              <?= htmlspecialchars($doc['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- 3. Tanggal Perjanjian -->
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label" for="appointment_date">Tanggal Rencana Kunjungan *</label>
        <input type="date" id="appointment_date" name="appointment_date" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required onchange="checkQuota()">
      </div>

      <!-- Live Kuota Box -->
      <div class="form-group">
        <label class="form-label">Status Kuota Dokter Harian</label>
        <div id="quotaBox" style="padding: 10px 14px; border-radius: 8px; background: #f1f5f9; border: 1px solid var(--line); font-size: 13px; min-height: 40px; display: flex; align-items: center; gap: 8px;">
          <i data-lucide="info" class="lucide-sm" style="color: var(--muted);"></i>
          <span style="color: var(--muted);">Pilih dokter &amp; tanggal untuk memeriksa ketersediaan kuota.</span>
        </div>
      </div>
    </div>

    <!-- 4. Keluhan Awal -->
    <div class="form-group">
      <label class="form-label" for="chief_complaint">Keluhan Utama / Catatan Awal Kunjungan</label>
      <textarea id="chief_complaint" name="chief_complaint" class="form-control" rows="2" placeholder="Contoh: Demam naik turun 3 hari disertai batuk kering"></textarea>
    </div>

    <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
      <a href="<?= base_url('index.php?c=appointment&a=index') ?>" class="btn btn-outline">Batal</a>
      <button type="submit" id="submitBtn" class="btn btn-primary" style="min-width: 180px;">
        <i data-lucide="ticket"></i>
        <span>Cetak Tiket &amp; Antrean</span>
      </button>
    </div>
  </form>
</div>

<script>
const allDoctors = <?= json_encode($doctors) ?>;

function filterDoctorsByPoli(poliId) {
  const docSelect = document.getElementById('doctor_id');
  docSelect.innerHTML = '<option value="">-- Pilih Dokter --</option>';

  const filtered = allDoctors.filter(d => !poliId || d.policlinic_id == poliId);
  filtered.forEach(d => {
    const opt = document.createElement('option');
    opt.value = d.id;
    opt.textContent = d.name;
    docSelect.appendChild(opt);
  });

  checkQuota();
}

function checkQuota() {
  const docId = document.getElementById('doctor_id').value;
  const date = document.getElementById('appointment_date').value;
  const box = document.getElementById('quotaBox');
  const btn = document.getElementById('submitBtn');

  if (!docId || !date) {
    box.innerHTML = '<i data-lucide="info" class="lucide-sm" style="color: var(--muted);"></i><span style="color: var(--muted);">Pilih dokter & tanggal untuk memeriksa ketersediaan kuota.</span>';
    box.style.background = '#f1f5f9';
    btn.disabled = false;
    if (typeof lucide !== 'undefined') lucide.createIcons();
    return;
  }

  box.innerHTML = '<span style="color: var(--muted);">Memeriksa kuota...</span>';

  fetch(`<?= base_url('index.php?c=appointment&a=apiCheckQuota') ?>&doctor_id=${docId}&date=${date}`)
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        const d = res.data;
        if (!d.has_schedule) {
          box.innerHTML = `<i data-lucide="alert-triangle" style="color: var(--danger);"></i><b style="color: var(--danger);">Dokter tidak berpraktek pada hari ${d.day_name}.</b>`;
          box.style.background = 'var(--danger-bg)';
          btn.disabled = true;
        } else if (d.is_full) {
          box.innerHTML = `<i data-lucide="x-circle" style="color: var(--danger);"></i><b style="color: var(--danger);">KUOTA PENUH (${d.booked} / ${d.quota_per_day} terisi). Silakan pilih tanggal lain.</b>`;
          box.style.background = 'var(--danger-bg)';
          btn.disabled = true;
        } else {
          box.innerHTML = `<i data-lucide="check-circle" style="color: var(--success);"></i><b style="color: var(--success);">Kuota Tersedia: Sisa ${d.available_slots} antrean</b> (Terisi ${d.booked} dari kapasitas ${d.quota_per_day} pasien)`;
          box.style.background = 'var(--success-bg)';
          btn.disabled = false;
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
      }
    })
    .catch(() => {
      box.innerHTML = '<span style="color: var(--muted);">Gagal memeriksa kuota dokter secara otomatis.</span>';
    });
}
</script>
