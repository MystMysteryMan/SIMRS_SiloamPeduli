<?php
// app/views/reporting/indicators.php - Master Indikator Mutu & Pelayanan RS (SOP/SIMRS/015/2026)
?>

<div class="content-header">
  <div>
    <h1 class="page-title">Master Indikator Mutu &amp; Kinerja RS</h1>
    <p class="text-muted">Standarisasi indikator klinis, efisiensi pelayanan Kemenkes, keselamatan pasien &amp; target mutu manajemen.</p>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-primary" onclick="openModal('modalAddIndicator')">
      <i data-lucide="plus-circle"></i>
      <span>+ Tambah Indikator</span>
    </button>
    <a href="<?= base_url('index.php?c=report&a=index') ?>" class="btn btn-secondary">
      <i data-lucide="arrow-left"></i>
      <span>Dashboard</span>
    </a>
  </div>
</div>

<!-- TABEL DAFTAR INDIKATOR MUTU -->
<div class="card overflow-hidden">
  <div class="card-header p-3 border-bottom d-flex justify-between items-center bg-slate-light">
    <div class="fw-bold text-dark d-flex items-center gap-2">
      <i data-lucide="target" style="width: 18px; height: 18px; color: var(--blue-600);"></i>
      <span>Daftar Indikator Pelayanan &amp; Mutu Rumah Sakit</span>
    </div>
    <span class="text-xs text-muted">Total: <b><?= count($indicators) ?> Indikator Terdaftar</b></span>
  </div>

  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th style="width: 45px;" class="text-center">No</th>
          <th>Nama Indikator Mutu</th>
          <th>Jenis Data</th>
          <th>Parameter Pelayanan</th>
          <th>Nilai / Standar Capaian</th>
          <th class="text-center">Status</th>
          <th class="text-center" style="width: 100px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($indicators)): ?>
          <tr>
            <td colspan="7" class="text-center text-muted p-4">Belum ada indikator mutu yang terdaftar.</td>
          </tr>
        <?php else: ?>
          <?php $no = 1; ?>
          <?php foreach ($indicators as $ind): ?>
            <tr>
              <td class="text-center text-muted"><?= $no++ ?></td>
              <td class="fw-semibold text-dark"><?= htmlspecialchars($ind['name']) ?></td>
              <td><span class="badge badge-info"><?= htmlspecialchars($ind['data_type']) ?></span></td>
              <td><?= htmlspecialchars($ind['parameter']) ?></td>
              <td class="font-mono fw-bold text-primary"><?= htmlspecialchars($ind['current_value']) ?></td>
              <td class="text-center">
                <?php if (strtoupper($ind['status']) === 'AKTIF'): ?>
                  <span class="badge-indicator-aktif">Aktif</span>
                <?php else: ?>
                  <span class="badge-indicator-nonaktif">Non-Aktif</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <form method="POST" action="<?= base_url('index.php?c=report&a=deleteIndicator') ?>" onsubmit="return confirm('Apakah Anda yakin ingin menghapus indikator ini?');" style="display: inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= $ind['id'] ?>">
                  <button type="submit" class="btn btn-outline btn-sm text-danger" style="padding: 3px 8px; font-size: 11px;" title="Hapus Indikator">
                    <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL DIALOG [+ TAMBAH INDIKATOR] -->
<div class="modal-backdrop hidden" id="modalAddIndicator" role="dialog" aria-modal="true" style="position: fixed; inset: 0; background: rgba(15, 47, 87, 0.45); backdrop-filter: blur(2px); display: none; place-items: center; z-index: 1000; padding: 16px;">
  <div class="modal-confirm" style="width: min(500px, 100%);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--line); padding-bottom: 10px;">
      <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
        <i data-lucide="plus-circle" style="color: var(--blue-600); width: 18px; height: 18px;"></i>
        <span>Tambah Indikator Mutu &amp; Kinerja RS</span>
      </h3>
      <button type="button" onclick="closeModal('modalAddIndicator')" style="border: 0; background: none; font-size: 18px; color: var(--muted); cursor: pointer;">&times;</button>
    </div>

    <form method="POST" action="<?= base_url('index.php?c=report&a=saveIndicator') ?>">
      <?= csrf_field() ?>

      <div class="form-group mb-3">
        <label class="form-label text-xs fw-bold">Nama Indikator <span style="color: var(--danger);">*</span></label>
        <input type="text" name="name" class="form-control" placeholder="Contoh: Kepatuhan Jam Visite Dokter Spesialis" required>
      </div>

      <div class="form-group mb-3">
        <label class="form-label text-xs fw-bold">Jenis Data <span style="color: var(--danger);">*</span></label>
        <select name="data_type" class="form-control" required>
          <option value="Efisiensi Tempat Tidur">Efisiensi Tempat Tidur (BOR/ALOS)</option>
          <option value="Mutu Pelayanan">Mutu &amp; Kepatuhan Pelayanan</option>
          <option value="Waktu Pelayanan">Waktu Tanggap / Response Time</option>
          <option value="Keselamatan Pasien">Keselamatan Pasien (Patient Safety)</option>
          <option value="Kinerja Finansial">Kinerja Finansial &amp; Klaim</option>
        </select>
      </div>

      <div class="form-group mb-3">
        <label class="form-label text-xs fw-bold">Parameter Pelayanan <span style="color: var(--danger);">*</span></label>
        <select name="parameter" class="form-control" required>
          <option value="Semua Pelayanan">Semua Pelayanan</option>
          <option value="Rawat Jalan">Rawat Jalan (Poliklinik)</option>
          <option value="Rawat Inap">Rawat Inap</option>
          <option value="IGD">Gawat Darurat (IGD)</option>
          <option value="Farmasi">Farmasi &amp; Apotek</option>
          <option value="Laboratorium">Laboratorium</option>
          <option value="Radiologi">Radiologi</option>
        </select>
      </div>

      <div class="form-group mb-3">
        <label class="form-label text-xs fw-bold">Target Capaian / Nilai Standar</label>
        <input type="text" name="current_value" class="form-control" placeholder="Contoh: >= 90% atau < 30 Menit" value="100%">
      </div>

      <div class="form-group mb-4">
        <label class="form-label text-xs fw-bold">Status Indikator</label>
        <select name="status" class="form-control">
          <option value="AKTIF">Aktif</option>
          <option value="NON-AKTIF">Non-Aktif</option>
        </select>
      </div>

      <div class="row-actions">
        <button type="button" class="btn-sec" onclick="closeModal('modalAddIndicator')">Batal</button>
        <button type="submit" class="btn btn-primary" style="padding: 9px 18px; font-weight: 700;">Simpan Indikator</button>
      </div>
    </form>
  </div>
</div>

