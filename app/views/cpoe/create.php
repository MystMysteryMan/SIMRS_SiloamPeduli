<?php
// app/views/cpoe/create.php - Formulir Input Order CPOE (E-Resep & Tindakan Medis)
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="plus-circle" style="color: var(--blue-600); width: 22px; height: 22px;"></i>
      <span>Penerbitan Order Pelayanan Medis (CPOE)</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Input resep elektronik farmasi dan instruksi tindakan keperawatan terstandar SOP rumah sakit.
    </p>
  </div>
  <div>
    <a href="<?= base_url('index.php?c=cpoe&a=index') ?>" class="btn btn-outline">
      <i data-lucide="arrow-left"></i>
      <span>Kembali ke Daftar Order</span>
    </a>
  </div>
</div>

<form method="POST" action="<?= base_url('index.php?c=cpoe&a=store') ?>" id="cpoeForm">
  <?= csrf_field() ?>
  <input type="hidden" name="visit_type" value="<?= htmlspecialchars($visitType ?? 'RAWAT_JALAN') ?>">
  <input type="hidden" name="reference_id" value="<?= htmlspecialchars($referenceId ?? '') ?>">

  <!-- Pasien & Dokter Info Card -->
  <div class="card" style="margin-bottom: 20px;">
    <h3 style="margin: 0 0 16px; font-size: 14px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="user" class="lucide-md" style="color: var(--blue-600);"></i>
      <span>Informasi Pasien &amp; DPJP Pembuat Order</span>
    </h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
      <!-- Pilih Pasien -->
      <div>
        <label class="form-label" style="font-weight: 600;">Pasien Penerima Order <span style="color: var(--danger);">*</span></label>
        <?php if ($selectedPatient): ?>
          <input type="hidden" name="patient_id" value="<?= $selectedPatient['id'] ?>">
          <div style="padding: 10px 14px; background: var(--surface-alt); border: 1px solid var(--line); border-radius: var(--radius-sm);">
            <div style="font-weight: 700; color: var(--blue-900); font-size: 14px;">
              <?= htmlspecialchars($selectedPatient['full_name']) ?>
            </div>
            <div style="font-size: 11.5px; color: var(--muted); margin-top: 3px; display: flex; gap: 8px;">
              <span>No. RM: <b><?= htmlspecialchars($selectedPatient['no_rm']) ?></b></span>
              <span>&bull;</span>
              <span>Penjamin: <b class="badge badge-primary"><?= htmlspecialchars($selectedPatient['insurance_type']) ?></b></span>
            </div>
          </div>
        <?php else: ?>
          <select name="patient_id" class="form-control" required onchange="window.location.href='<?= base_url('index.php?c=cpoe&a=create&patient_id=') ?>' + this.value;">
            <option value="">-- Pilih Pasien Terdaftar --</option>
            <?php foreach ($patients as $p): ?>
              <option value="<?= $p['id'] ?>" <?= (isset($_GET['patient_id']) && (int)$_GET['patient_id'] === (int)$p['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['no_rm']) ?> - <?= htmlspecialchars($p['full_name']) ?> (<?= htmlspecialchars($p['insurance_type']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>

      <!-- Pilih Dokter DPJP -->
      <div>
        <label class="form-label" style="font-weight: 600;">Dokter Penanggung Jawab (DPJP) <span style="color: var(--danger);">*</span></label>
        <select name="doctor_id" class="form-control" required>
          <?php foreach ($doctors as $d): ?>
            <option value="<?= $d['id'] ?>">
              <?= htmlspecialchars($d['full_name']) ?> (<?= htmlspecialchars($d['specialization']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Prioritas Order -->
      <div>
        <label class="form-label" style="font-weight: 600;">Prioritas Klinis</label>
        <div style="display: flex; gap: 12px; margin-top: 6px;">
          <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
            <input type="radio" name="priority" value="REGULER" checked>
            <span class="badge badge-reguler">REGULER</span>
          </label>
          <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
            <input type="radio" name="priority" value="CITO">
            <span class="badge badge-cito">CITO (DARURAT)</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Peringatan Alergi Pasien -->
    <?php if (!empty($allergies)): ?>
      <div style="margin-top: 16px; padding: 10px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 10px;">
        <i data-lucide="alert-triangle" style="color: #dc2626; width: 18px; height: 18px; flex-shrink: 0;"></i>
        <div style="font-size: 12px; color: #991b1b;">
          <b>PERINGATAN RIWAYAT ALERGI PASIEN:</b> Pasien memiliki alergi terhadap 
          <?php foreach ($allergies as $idx => $al): ?>
            <span style="font-weight: 700; text-decoration: underline;"><?= htmlspecialchars($al['allergen_name']) ?></span> (Tingkat: <?= htmlspecialchars($al['severity']) ?>)<?= ($idx < count($allergies)-1) ? ', ' : '' ?>
          <?php endforeach; ?>. Harap teliti sebelum meresepkan golongan obat terkait!
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Jenis Order & Rincian Item Card -->
  <div class="card" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 16px; border-bottom: 1px solid var(--line); padding-bottom: 12px;">
      <h3 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
        <i data-lucide="layers" class="lucide-md" style="color: var(--blue-600);"></i>
        <span>Jenis &amp; Rincian Order Pelayanan</span>
      </h3>

      <!-- Tab Switcher -->
      <div style="display: flex; gap: 8px;">
        <label class="btn btn-sm btn-outline" id="tabResepBtn" style="cursor: pointer; background: var(--blue-50); color: var(--blue-700); border-color: var(--blue-700);">
          <input type="radio" name="order_type" value="RESEP_OBAT" checked style="display:none;" onchange="switchOrderTab('RESEP_OBAT')">
          <i data-lucide="pill" class="lucide-sm"></i>
          <span>E-Resep Farmasi</span>
        </label>
        <label class="btn btn-sm btn-outline" id="tabTindakanBtn" style="cursor: pointer;" onchange="switchOrderTab('TINDAKAN_MEDIS')">
          <input type="radio" name="order_type" value="TINDAKAN_MEDIS" style="display:none;">
          <i data-lucide="activity" class="lucide-sm"></i>
          <span>Tindakan Medis / Keperawatan</span>
        </label>
      </div>
    </div>

    <!-- Section E-Resep Farmasi -->
    <div id="sectionResep">
      <p style="margin: 0 0 12px; font-size: 12.5px; color: var(--muted);">
        Tambahkan obat-obatan yang diresepkan. Order otomatis diteruskan ke antrean dispensing farmasi.
      </p>

      <div style="overflow-x: auto;">
        <table class="table" id="tableResep" style="margin-bottom: 12px;">
          <thead>
            <tr>
              <th style="width: 30%;">Nama Obat (Formularium)</th>
              <th style="width: 15%;">Dosis</th>
              <th style="width: 15%;">Frekuensi</th>
              <th style="width: 12%;">Rute</th>
              <th style="width: 10%;">Jumlah (Qty)</th>
              <th style="width: 18%;">Aturan Pakai / Catatan</th>
              <th style="width: 5%;"></th>
            </tr>
          </thead>
          <tbody id="resepRows">
            <tr>
              <td>
                <select name="medicine_id[]" class="form-control" required>
                  <option value="">-- Pilih Obat --</option>
                  <?php foreach ($medicines as $m): ?>
                    <option value="<?= $m['id'] ?>">
                      <?= htmlspecialchars($m['name']) ?> (Rp <?= number_format($m['unit_price'], 0, ',', '.') ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input type="text" name="med_dosage[]" class="form-control" placeholder="Contoh: 500 mg"></td>
              <td><input type="text" name="med_frequency[]" class="form-control" placeholder="Contoh: 3x1 sehari"></td>
              <td>
                <select name="med_route[]" class="form-control">
                  <option value="Oral">Oral (Minum)</option>
                  <option value="IV">Intravena (IV)</option>
                  <option value="IM">Intramuskular (IM)</option>
                  <option value="SC">Subkutan (SC)</option>
                  <option value="Topikal">Topikal (Oles)</option>
                  <option value="Inhalasi">Inhalasi (Uap)</option>
                </select>
              </td>
              <td><input type="number" name="med_quantity[]" class="form-control num" value="10" min="1" required></td>
              <td><input type="text" name="med_instructions[]" class="form-control" placeholder="Sesudah makan / jika demam"></td>
              <td style="text-align: center;">
                <button type="button" class="btn btn-sm btn-outline" style="color: var(--danger); padding: 4px;" onclick="removeRow(this)">
                  <i data-lucide="trash-2" class="lucide-sm"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <button type="button" class="btn btn-sm btn-outline" onclick="addResepRow()">
        <i data-lucide="plus"></i>
        <span>Tambah Obat Lain</span>
      </button>
    </div>

    <!-- Section Tindakan Medis / Nursing Orders -->
    <div id="sectionTindakan" style="display: none;">
      <p style="margin: 0 0 12px; font-size: 12.5px; color: var(--muted);">
        Tambahkan instruksi tindakan medis atau asuhan keperawatan. Order otomatis diteruskan ke antrean perawat.
      </p>

      <div style="overflow-x: auto;">
        <table class="table" id="tableTindakan" style="margin-bottom: 12px;">
          <thead>
            <tr>
              <th style="width: 45%;">Jenis Tindakan Medis / Tarif</th>
              <th style="width: 15%;">Jumlah (Kali)</th>
              <th style="width: 35%;">Instruksi / Catatan Khusus Perawat</th>
              <th style="width: 5%;"></th>
            </tr>
          </thead>
          <tbody id="tindakanRows">
            <tr>
              <td>
                <select name="tariff_id[]" class="form-control">
                  <option value="">-- Pilih Tindakan Medis --</option>
                  <?php foreach ($tariffs as $t): ?>
                    <option value="<?= $t['id'] ?>">
                      <?= htmlspecialchars($t['name']) ?> (Rp <?= number_format($t['amount'], 0, ',', '.') ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input type="number" name="proc_quantity[]" class="form-control num" value="1" min="1"></td>
              <td><input type="text" name="proc_instructions[]" class="form-control" placeholder="Misal: Pasang infus RL 20 tpm tangan kiri"></td>
              <td style="text-align: center;">
                <button type="button" class="btn btn-sm btn-outline" style="color: var(--danger); padding: 4px;" onclick="removeRow(this)">
                  <i data-lucide="trash-2" class="lucide-sm"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <button type="button" class="btn btn-sm btn-outline" onclick="addTindakanRow()">
        <i data-lucide="plus"></i>
        <span>Tambah Tindakan Lain</span>
      </button>
    </div>

    <!-- Catatan Tambahan Dokter -->
    <div style="margin-top: 20px;">
      <label class="form-label" style="font-weight: 600;">Catatan Klinis / Indikasi Khusus</label>
      <textarea name="clinical_notes" class="form-control" rows="2" placeholder="Tuliskan catatan tambahan untuk Apoteker atau Perawat pelaksana..."></textarea>
    </div>
  </div>

  <!-- Submit Buttons -->
  <div style="display: flex; gap: 12px; justify-content: flex-end;">
    <a href="<?= base_url('index.php?c=cpoe&a=index') ?>" class="btn btn-outline">Batal</a>
    <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
      <i data-lucide="check"></i>
      <span>Terbitkan Order CPOE Sekarang</span>
    </button>
  </div>
</form>

<script>
function switchOrderTab(type) {
  const tabResep = document.getElementById('tabResepBtn');
  const tabTindakan = document.getElementById('tabTindakanBtn');
  const secResep = document.getElementById('sectionResep');
  const secTindakan = document.getElementById('sectionTindakan');

  if (type === 'RESEP_OBAT') {
    tabResep.style.background = 'var(--blue-50)';
    tabResep.style.color = 'var(--blue-700)';
    tabResep.style.borderColor = 'var(--blue-700)';
    tabTindakan.style.background = '';
    tabTindakan.style.color = '';
    tabTindakan.style.borderColor = '';
    secResep.style.display = 'block';
    secTindakan.style.display = 'none';
  } else {
    tabTindakan.style.background = 'var(--blue-50)';
    tabTindakan.style.color = 'var(--blue-700)';
    tabTindakan.style.borderColor = 'var(--blue-700)';
    tabResep.style.background = '';
    tabResep.style.color = '';
    tabResep.style.borderColor = '';
    secTindakan.style.display = 'block';
    secResep.style.display = 'none';
  }
}

function addResepRow() {
  const tbody = document.getElementById('resepRows');
  const firstRow = tbody.querySelector('tr');
  const newRow = firstRow.cloneNode(true);
  newRow.querySelectorAll('input').forEach(i => {
    if (i.name.includes('quantity')) i.value = '10';
    else i.value = '';
  });
  tbody.appendChild(newRow);
  if (window.lucide) window.lucide.createIcons();
}

function addTindakanRow() {
  const tbody = document.getElementById('tindakanRows');
  const firstRow = tbody.querySelector('tr');
  const newRow = firstRow.cloneNode(true);
  newRow.querySelectorAll('input').forEach(i => {
    if (i.name.includes('quantity')) i.value = '1';
    else i.value = '';
  });
  tbody.appendChild(newRow);
  if (window.lucide) window.lucide.createIcons();
}

function removeRow(btn) {
  const tbody = btn.closest('tbody');
  if (tbody.querySelectorAll('tr').length > 1) {
    btn.closest('tr').remove();
  } else {
    alert('Minimal harus ada 1 baris item!');
  }
}
</script>

