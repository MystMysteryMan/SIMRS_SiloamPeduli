<?php
// app/views/inpatient/transfer.php - Form Transfer Pasien / Mutasi Tempat Tidur (SOP/SIMRS/006/2026)
?>

<div style="margin-bottom: 20px;">
  <a href="<?= base_url('index.php?c=inpatient&a=index') ?>" class="btn btn-outline" style="margin-bottom: 12px;">
    <i data-lucide="arrow-left"></i>
    <span>Kembali ke Rawat Inap</span>
  </a>

  <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
    <i data-lucide="arrow-left-right" style="color: var(--blue-700); width: 22px; height: 22px;"></i>
    <span>Mutasi Pasien Antar-Ruangan / Transfer Bed</span>
  </h1>
  <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
    SOP/SIMRS/006/2026: Perpindahan kamar/kelas perawatan. Bed asal otomatis dibebaskan dan bed tujuan dialokasikan ke pasien.
  </p>
</div>

<!-- Profil Pasien & Lokasi Saat Ini -->
<div class="card" style="margin-bottom: 20px; padding: 20px; background: var(--blue-50); border: 1px solid var(--blue-200); max-width: 800px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
    <div>
      <span class="badge" style="background: var(--blue-700); color: #fff; margin-bottom: 6px;">
        <?= htmlspecialchars($admission['admission_number']) ?>
      </span>
      <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--blue-900);">
        <?= htmlspecialchars($admission['patient_name']) ?>
      </h2>
      <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
        No. RM: <b><?= htmlspecialchars($admission['no_rm']) ?></b> | NIK: <?= htmlspecialchars($admission['nik']) ?> | DPJP: <?= htmlspecialchars($admission['doctor_name']) ?>
      </div>
    </div>
    <div style="padding: 10px 16px; background: #fff; border: 1px solid var(--blue-200); border-radius: 6px; text-align: right;">
      <div style="font-size: 11px; color: var(--muted); text-transform: uppercase;">Lokasi Bed Saat Ini</div>
      <div style="font-size: 14px; font-weight: 800; color: var(--blue-900);">
        <?= htmlspecialchars($admission['room_name']) ?> (Bed <?= htmlspecialchars($admission['bed_number']) ?>)
      </div>
      <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars(str_replace('_', ' ', $admission['class_type'])) ?></div>
    </div>
  </div>
</div>

<div class="card" style="max-width: 800px; padding: 24px;">
  <form method="POST" action="<?= base_url('index.php?c=inpatient&a=transfer&id=' . $admission['id']) ?>">
    <div style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 24px;">
      
      <!-- Pilih Bed Tujuan -->
      <div>
        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--blue-900); margin-bottom: 6px;">
          Pilih Tempat Tidur Tujuan Transfer (Harus Berstatus TERSEDIA) <span style="color: #dc2626;">*</span>
        </label>
        <?php if (empty($availableBeds)): ?>
          <div style="padding: 12px; background: #fee2e2; color: #b91c1c; border-radius: 6px; font-size: 13px;">
            Tidak ada tempat tidur kosong untuk transfer saat ini.
          </div>
        <?php else: ?>
          <select name="to_bed_id" class="form-control" required style="font-weight: 600;">
            <option value="">-- Pilih Bed Tujuan --</option>
            <?php foreach ($availableBeds as $bed): ?>
              <?php if ($bed['id'] != $admission['bed_id']): ?>
                <option value="<?= $bed['id'] ?>">
                  <?= htmlspecialchars($bed['room_name']) ?> (<?= htmlspecialchars($bed['room_code']) ?>) • Bed <?= htmlspecialchars($bed['bed_number']) ?> • <?= htmlspecialchars(str_replace('_', ' ', $bed['class_type'])) ?> • Rp <?= number_format($bed['daily_rate'], 0, ',', '.') ?>/hari
                </option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>

      <!-- Alasan Transfer -->
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Alasan Perpindahan Kamar / Bed <span style="color: #dc2626;">*</span>
        </label>
        <select name="transfer_reason" class="form-control" required>
          <option value="Permintaan Pasien / Keluarga (Upgrade/Downgrade Kelas)">Permintaan Pasien / Keluarga (Upgrade/Downgrade Kelas)</option>
          <option value="Indikasi Klinis / Perburukan Kondisi (Pindah ICU/Isolasi)">Indikasi Klinis / Perburukan Kondisi (Pindah ICU/Isolasi)</option>
          <option value="Perbaikan Kondisi Pasien (Stepdown dari ICU ke Bangsal)">Perbaikan Kondisi Pasien (Stepdown dari ICU ke Bangsal)</option>
          <option value="Kebutuhan Isolasi / Manajemen Infeksi Ruangan">Kebutuhan Isolasi / Manajemen Infeksi Ruangan</option>
          <option value="Pemeliharaan Fasilitas Ruangan / Perbaikan Bed">Pemeliharaan Fasilitas Ruangan / Perbaikan Bed</option>
        </select>
      </div>

      <!-- Catatan Khusus -->
      <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">
          Catatan Tambahan Transfer
        </label>
        <textarea name="notes" class="form-control" rows="3" placeholder="Instruksi perpindahan infus, oksigen, atau catatan perawat..."></textarea>
      </div>

    </div>

    <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border); padding-top: 16px;">
      <a href="<?= base_url('index.php?c=inpatient&a=index') ?>" class="btn btn-outline">Batal</a>
      <button type="submit" class="btn btn-primary" <?= empty($availableBeds) ? 'disabled' : '' ?>>
        <i data-lucide="check"></i>
        <span>Konfirmasi Perpindahan Kamar &amp; Alokasi Bed</span>
      </button>
    </div>
  </form>
</div>

