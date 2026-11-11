<?php
// app/views/radiology/process.php - Tindakan Radiologi, Unggah Citra WebP, & Ekspertise
$isCito = ($order['clinical_priority'] === 'CITO');
$st = $order['status'];
$patAge = !empty($order['birth_date']) ? (date('Y') - date('Y', strtotime($order['birth_date']))) : '-';
$hasImage = RadiologyModel::hasImage($order);
?>

<div style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <div style="display: flex; align-items: center; gap: 8px;">
      <a href="<?= base_url('index.php?c=radiology&a=index') ?>" class="btn btn-outline" style="height: 32px; padding: 0 10px; font-size: 12px;">
        <i data-lucide="arrow-left"></i>
        <span>Daftar Order</span>
      </a>
      <h1 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--blue-900);">
        Pemeriksaan Diagnostik Radiologi
      </h1>
    </div>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Nomor Order: <b class="font-mono" style="color: var(--blue-800);"><?= htmlspecialchars($order['order_number']) ?></b> &bull; Tindakan: <b><?= htmlspecialchars($order['modality_type']) ?></b>
    </p>
  </div>
  <div style="display: flex; gap: 8px;">
    <?php if ($st === 'VALID'): ?>
      <a href="<?= base_url('index.php?c=radiology&a=printResult&id=' . $order['id']) ?>" target="_blank" class="btn btn-primary">
        <i data-lucide="printer"></i>
        <span>Cetak Lembar Ekspertise</span>
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Header Pasien & Detail Order -->
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
        <span><b>Dokter Perujuk:</b> <?= htmlspecialchars($order['doctor_name'] ?? 'Dokter Poliklinik') ?></span>
      </div>
      <div style="margin-top: 8px; font-size: 12.5px; color: var(--slate-700); background: #f8fafc; padding: 8px 12px; border-radius: 4px; border: 1px solid var(--border);">
        <b>Indikasi Klinis / Kecurigaan:</b> <?= htmlspecialchars($order['clinical_indication']) ?>
      </div>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 600;">Status Tindakan</div>
      <?php
        $badgeClass = 'badge-secondary';
        if ($st === 'PENDING') $badgeClass = 'badge-warning';
        elseif ($st === 'PROCESS') $badgeClass = 'badge-info';
        elseif ($st === 'VALID') $badgeClass = 'badge-success';
      ?>
      <span class="badge <?= $badgeClass ?>" style="font-size: 13px; margin-top: 4px;">
        <?= htmlspecialchars($st) ?>
      </span>
      <?php if (!empty($order['radiographer_name'])): ?>
        <div style="font-size: 11.5px; color: var(--muted); margin-top: 4px;">
          Radiografer: <?= htmlspecialchars($order['radiographer_name']) ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($order['radiologist_name'])): ?>
        <div style="font-size: 11.5px; color: var(--muted);">
          Sp.Rad: <?= htmlspecialchars($order['radiologist_name']) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($st === 'PENDING'): ?>
  <!-- LANGKAH 1: RADIOGRAFER MEMULAI TINDAKAN -->
  <div class="card" style="padding: 24px; text-align: center; max-width: 680px; margin: 0 auto 30px;">
    <div style="width: 56px; height: 56px; border-radius: 50%; background: #eff6ff; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: var(--blue-600);">
      <i data-lucide="camera" style="width: 28px; height: 28px;"></i>
    </div>
    <h2 style="margin: 0 0 8px; font-size: 17px; font-weight: 700; color: var(--blue-900);">
      Persiapan Pasien di Ruang Radiologi
    </h2>
    <p style="margin: 0 0 20px; color: var(--muted); font-size: 13px;">
      Konfirmasi identitas pasien, posisikan sesuai proyeksi <b><?= htmlspecialchars($order['modality_type']) ?></b>, dan lakukan eksposur radiografi.
    </p>

    <form method="POST" action="<?= base_url('index.php?c=radiology&a=process') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
      <button type="submit" class="btn btn-primary" style="height: 44px; padding: 0 28px; font-size: 14px;">
        <i data-lucide="play"></i>
        <span>Mulai Tindakan Pemotretan Radiologi</span>
      </button>
    </form>
  </div>

<?php else: ?>

  <!-- LANGKAH 2: WORKSPACE KOLABORASI RADIOGRAFER (CITRA WEBP) & SPESIALIS RADIOLOGI (EKSPERTISE) -->
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: flex-start;">

    <!-- SISI KIRI: CITRA RADIOLOGI (RADIOGRAFER) -->
    <div class="card" style="padding: 20px;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <i data-lucide="image" style="color: var(--blue-600); width: 18px; height: 18px;"></i>
          <b style="color: var(--blue-900); font-size: 14px;">Citra Radiologi</b>
        </div>
        <?php if ($hasImage): ?>
          <span class="badge badge-success">
            <i data-lucide="check"></i> Citra Siap
          </span>
        <?php else: ?>
          <span class="badge badge-warning">Menunggu Unggah</span>
        <?php endif; ?>
      </div>

      <!-- Pratinjau Citra -->
      <?php if ($hasImage): ?>
        <div style="margin-bottom: 16px; text-align: center; background: #000; border-radius: 8px; overflow: hidden; padding: 8px;">
          <a href="<?= htmlspecialchars(base_url($order['image_path'])) ?>" target="_blank" title="Klik untuk membuka citra ukuran penuh">
            <img src="<?= htmlspecialchars(base_url($order['image_path'])) ?>" alt="Hasil Rontgen"
                 style="max-width: 100%; max-height: 380px; object-fit: contain; display: block; margin: 0 auto;">
          </a>
        </div>
        <div style="font-size: 12px; color: var(--muted); margin-bottom: 14px; display: flex; justify-content: space-between;">
          <span>Berkas: <b><?= htmlspecialchars($order['image_filename']) ?></b></span>
          <span>Citra tersimpan</span>
        </div>
        <?php if (!empty($order['technical_notes'])): ?>
          <div style="font-size: 12px; color: var(--slate-700); background: #f8fafc; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); margin-bottom: 16px;">
            <b>Catatan Teknis Radiografer:</b> <?= htmlspecialchars($order['technical_notes']) ?>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div style="padding: 36px 16px; text-align: center; background: #f8fafc; border: 1px dashed var(--border); border-radius: 8px; color: var(--muted); margin-bottom: 16px;">
          <i data-lucide="upload-cloud" style="width: 40px; height: 40px; margin-bottom: 8px; display: inline-block;"></i>
          <div style="font-size: 13.5px; font-weight: 600;">Belum Ada Citra Radiologi Diunggah</div>
          <div style="font-size: 11.5px;">Radiografer perlu mengunggah foto rontgen hasil eksposur pasien.</div>
        </div>
      <?php endif; ?>

      <!-- Form Unggah Citra (Radiografer / Admin) -->
      <?php if ($canUpload && $st === 'PROCESS'): ?>
        <div style="border-top: 1px solid var(--border); padding-top: 14px;">
          <b style="font-size: 12.5px; color: var(--blue-950); display: block; margin-bottom: 10px;">
            <?= $hasImage ? 'Unggah Ulang / Ganti Citra:' : 'Unggah Citra Hasil Pemotretan:' ?>
          </b>
          <form method="POST" action="<?= base_url('index.php?c=radiology&a=upload') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

            <div class="form-group">
              <label class="form-label" style="font-size: 12px;">Pilih Berkas Citra (JPG, PNG, atau WebP)</label>
              <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" required>
              <div style="font-size: 11px; color: var(--muted); margin-top: 4px;">
                Pilih citra JPG, PNG, atau WebP dengan ukuran maksimal <b>1 MB</b>.
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" style="font-size: 12px;">Catatan Teknis Pemotretan (kV, mAs, Posisi)</label>
              <input type="text" name="technical_notes" class="form-control" placeholder="Contoh: Proyeksi PA Erect, 65 kV, 12 mAs, FFD 150cm" 
                     value="<?= htmlspecialchars($order['technical_notes'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-outline" style="width: 100%; height: 38px;">
              <i data-lucide="upload"></i>
              <span>Unggah &amp; Simpan Citra</span>
            </button>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <!-- SISI KANAN: EKSPERTISE DOKTER SPESIALIS RADIOLOGI -->
    <div class="card" style="padding: 20px;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <i data-lucide="file-text" style="color: var(--blue-600); width: 18px; height: 18px;"></i>
          <b style="color: var(--blue-900); font-size: 14px;">Ekspertise Dokter Spesialis Radiologi</b>
        </div>
        <?php if ($st === 'VALID'): ?>
          <span class="badge badge-success">
            <i data-lucide="check-check"></i> Ekspertise Terbit
          </span>
        <?php endif; ?>
      </div>

      <?php if (!$hasImage): ?>
        <div style="padding: 30px 16px; text-align: center; color: var(--muted); font-size: 12.5px;">
          <i data-lucide="clock" style="width: 32px; height: 32px; margin-bottom: 8px; display: inline-block;"></i>
          <div>Menunggu radiografer mengunggah citra radiologi sebelum ekspertise dapat dibuat.</div>
        </div>
      <?php else: ?>

        <form method="POST" action="<?= base_url('index.php?c=radiology&a=saveExpertise') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

          <div class="form-group">
            <label class="form-label" style="font-size: 12px;">
              <b>Deskripsi Temuan Radiologi (Findings) <span style="color: var(--red-600);">*</span>:</b>
            </label>
            <textarea name="expertise_findings" class="form-control" rows="6" required 
                      placeholder="Contoh: Cor: bentuk dan ukuran dalam batas normal. Pulmo: tampak infiltrat halus di lapangan tengah paru dextra. Sinus kostofrenikus dan hemidiafragma tajam..."><?= htmlspecialchars($order['expertise_findings'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label" style="font-size: 12px;">
              <b>Kesimpulan Diagnosis Radiologi (Conclusion) <span style="color: var(--red-600);">*</span>:</b>
            </label>
            <textarea name="expertise_conclusion" class="form-control" rows="3" required 
                      placeholder="Contoh: Pneumonia lobaris dextra. Tidak tampak kardiomegali maupun efusi pleura."><?= htmlspecialchars($order['expertise_conclusion'] ?? '') ?></textarea>
          </div>

          <?php if ($canExpertise): ?>
            <div style="margin-top: 20px;">
              <button type="submit" class="btn btn-primary" style="width: 100%; height: 42px; font-size: 13.5px; font-weight: 600;">
                <i data-lucide="check-circle-2"></i>
                <span>Validasi &amp; Terbitkan Ekspertise Radiologi</span>
              </button>
            </div>
          <?php endif; ?>

          <?php if ($st === 'VALID'): ?>
            <div style="margin-top: 12px; text-align: center;">
              <a href="<?= base_url('index.php?c=radiology&a=printResult&id=' . $order['id']) ?>" target="_blank" class="btn btn-outline" style="width: 100%; height: 38px;">
                <i data-lucide="printer"></i>
                <span>Buka Lembar Cetak Ekspertise Resmi</span>
              </a>
            </div>
          <?php endif; ?>
        </form>

      <?php endif; ?>
    </div>

  </div>

<?php endif; ?>
