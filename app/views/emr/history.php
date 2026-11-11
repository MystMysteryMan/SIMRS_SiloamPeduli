<?php
// app/views/emr/history.php - Riwayat Longitudinal Rekam Medis Pasien (SOP Langkah 9)
$patAge = !empty($patient['birth_date']) ? (date('Y') - date('Y', strtotime($patient['birth_date']))) : '-';
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <div style="display: flex; align-items: center; gap: 8px;">
      <a href="<?= base_url('index.php?c=emr&a=index') ?>" class="btn btn-outline" style="height: 32px; padding: 0 10px; font-size: 12px;">
        <i data-lucide="arrow-left"></i>
        <span>Antrean</span>
      </a>
      <h1 style="margin: 0; font-size: 19px; font-weight: 700; color: var(--blue-900);">
        Rekam Medis Lengkap Pasien (Longitudinal EMR)
      </h1>
    </div>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Riwayat kunjungan terpadu, catatan SOAP, hasil uji laboratorium, dan citra diagnostik radiologi.
    </p>
  </div>
  <div>
    <button onclick="window.print()" class="btn btn-outline">
      <i data-lucide="printer"></i>
      <span>Cetak Ringkasan</span>
    </button>
  </div>
</div>

<!-- Header Pasien -->
<div class="card" style="padding: 18px 22px; margin-bottom: 20px; border-left: 4px solid var(--blue-600); background: linear-gradient(to right, #f8fafc, #ffffff);">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 20px; font-weight: 700; color: var(--blue-950);">
          <?= htmlspecialchars($patient['full_name']) ?>
        </span>
        <span class="badge badge-primary font-mono" style="font-size: 13px;">
          No. RM: <?= htmlspecialchars($patient['no_rm']) ?>
        </span>
        <span class="badge <?= ($patient['insurance_type'] === 'BPJS') ? 'badge-success' : 'badge-secondary' ?>">
          <?= htmlspecialchars($patient['insurance_type']) ?>
        </span>
      </div>
      <div style="font-size: 13px; color: var(--slate-600); margin-top: 6px; display: flex; gap: 16px; flex-wrap: wrap;">
        <span><b>NIK:</b> <?= htmlspecialchars($patient['nik']) ?></span>
        <span><b>JK/Usia:</b> <?= $patient['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?> / <?= $patAge ?> tahun</span>
        <span><b>Tgl Lahir:</b> <?= format_date_id($patient['birth_date']) ?></span>
        <span><b>Gol. Darah:</b> <?= htmlspecialchars($patient['blood_type'] ?? '-') ?></span>
        <span><b>Telepon:</b> <?= htmlspecialchars($patient['phone_number'] ?? '-') ?></span>
      </div>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 12px; color: var(--muted);">Total Kunjungan</div>
      <div style="font-size: 22px; font-weight: 700; color: var(--blue-800);"><?= count($records) ?> Kali</div>
    </div>
  </div>

  <!-- Alergi Banner -->
  <?php if (!empty($patient['allergy_notes']) || !empty($allergies)): ?>
    <div style="margin-top: 14px; padding: 10px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; display: flex; align-items: center; gap: 8px; color: #991b1b; font-size: 13px;">
      <i data-lucide="alert-triangle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
      <div>
        <b>PERINGATAN ALERGI:</b> <?= htmlspecialchars($patient['allergy_notes'] ?? '') ?>
        <?php foreach ($allergies as $alg): ?>
          <span class="badge badge-danger" style="margin-left: 4px;">
            <?= htmlspecialchars($alg['allergen_name']) ?> (<?= htmlspecialchars($alg['severity']) ?>) - <?= htmlspecialchars($alg['reaction']) ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Tabs Navigasi Riwayat -->
<div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
  <button type="button" class="btn btn-primary" id="tabBtnEmr" onclick="showTab('emr')">
    <i data-lucide="stethoscope"></i>
    <span>Kunjungan &amp; EMR (<?= count($records) ?>)</span>
  </button>
  <button type="button" class="btn btn-outline" id="tabBtnLab" onclick="showTab('lab')">
    <i data-lucide="flask-conical"></i>
    <span>Laboratorium (<?= count($labHistory) ?>)</span>
  </button>
  <button type="button" class="btn btn-outline" id="tabBtnRad" onclick="showTab('rad')">
    <i data-lucide="film"></i>
    <span>Radiologi &amp; Citra (<?= count($radHistory) ?>)</span>
  </button>
</div>

<!-- TAB 1: TIMELINE EMR -->
<div id="tabContentEmr">
  <?php if (empty($records)): ?>
    <div class="card" style="padding: 40px; text-align: center; color: var(--muted);">
      <i data-lucide="clipboard" style="width: 48px; height: 48px; stroke-width: 1.5; margin-bottom: 12px; display: inline-block;"></i>
      <div style="font-size: 14px; font-weight: 600;">Belum ada catatan rekam medis tersimpan.</div>
    </div>
  <?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 16px;">
      <?php foreach ($records as $rec): ?>
        <div class="card" style="padding: 18px 22px; border-left: 4px solid var(--blue-600);">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
            <div>
              <span style="font-size: 15px; font-weight: 700; color: var(--blue-950);">
                <?= format_datetime_id($rec['examined_at'] ?? $rec['created_at']) ?>
              </span>
              <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">
                Poliklinik: <b><?= htmlspecialchars($rec['policlinic_name'] ?? 'Rawat Jalan') ?></b> &bull; Dokter: <b><?= htmlspecialchars($rec['doctor_name'] ?? '-') ?></b>
              </div>
            </div>
            <div style="text-align: right;">
              <span class="badge badge-primary font-mono" style="font-size: 13px;">
                <?= htmlspecialchars($rec['status']) ?> · ICD-10: <?= htmlspecialchars($rec['icd10_code'] ?? '-') ?>
              </span>
              <div style="font-size: 13px; font-weight: 600; color: var(--blue-800); margin-top: 2px;">
                <?= htmlspecialchars($rec['diagnosis_name'] ?? '-') ?>
              </div>
            </div>
          </div>

          <!-- Tanda Vital -->
          <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; background: #f8fafc; padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; font-size: 12px;">
            <div><span style="color: var(--muted); display: block;">Tensi Darah:</span><b><?= htmlspecialchars($rec['vital_bp'] ?: '-') ?></b></div>
            <div><span style="color: var(--muted); display: block;">Suhu:</span><b><?= $rec['vital_temp'] ? $rec['vital_temp'] . ' °C' : '-' ?></b></div>
            <div><span style="color: var(--muted); display: block;">Nadi:</span><b><?= $rec['vital_pulse'] ? $rec['vital_pulse'] . ' x/m' : '-' ?></b></div>
            <div><span style="color: var(--muted); display: block;">RR:</span><b><?= $rec['vital_rr'] ? $rec['vital_rr'] . ' x/m' : '-' ?></b></div>
            <div><span style="color: var(--muted); display: block;">BB / TB:</span><b><?= ($rec['weight_kg'] ?: '-') ?> kg / <?= ($rec['height_cm'] ?: '-') ?> cm</b></div>
            <div><span style="color: var(--muted); display: block;">BMI:</span><b><?= $rec['bmi'] ? $rec['bmi'] . ' kg/m²' : '-' ?></b></div>
          </div>

          <!-- SOAP Details -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; font-size: 12.5px;">
            <div>
              <div style="margin-bottom: 8px;">
                <b style="color: var(--blue-900);">S - Subjektif:</b>
                <div style="color: var(--slate-700); margin-top: 2px;"><?= nl2br(htmlspecialchars($rec['subjective'] ?? '-')) ?></div>
              </div>
              <div>
                <b style="color: var(--blue-900);">O - Objektif:</b>
                <div style="color: var(--slate-700); margin-top: 2px;"><?= nl2br(htmlspecialchars($rec['objective'] ?? '-')) ?></div>
              </div>
            </div>
            <div>
              <div style="margin-bottom: 8px;">
                <b style="color: var(--blue-900);">A - Asesmen:</b>
                <div style="color: var(--slate-700); margin-top: 2px;"><?= nl2br(htmlspecialchars($rec['assessment'] ?? '-')) ?></div>
              </div>
              <div>
                <b style="color: var(--blue-900);">P - Plan:</b>
                <div style="color: var(--slate-700); margin-top: 2px;"><?= nl2br(htmlspecialchars($rec['plan'] ?? '-')) ?></div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="card" style="padding:20px;margin:20px 0"><h2 style="font-size:17px">Lampiran dan catatan keperawatan</h2>
<?php foreach ($records as $rec): if (empty($rec['nurse_notes'])) continue; ?><p><b>Kunjungan <?= format_date_id($rec['appointment_date']) ?>:</b> <?= nl2br(htmlspecialchars($rec['nurse_notes'])) ?></p><?php endforeach; ?>
<?php foreach ($attachments as $attachment): ?><p><a target="_blank" rel="noopener" href="<?= base_url('index.php?c=emr&a=attachment&id='.$attachment['id']) ?>"><?= htmlspecialchars($attachment['filename']) ?></a> · Kunjungan #<?= (int)$attachment['appointment_id'] ?> · <?= htmlspecialchars($attachment['uploader']) ?></p><?php endforeach; ?>
<?php if (!$attachments): ?><p>Belum ada lampiran.</p><?php endif; ?></div>
<!-- TAB 2: RIWAYAT LABORATORIUM -->
<div id="tabContentLab" style="display: none;">
  <?php if (empty($labHistory)): ?>
    <div class="card" style="padding: 40px; text-align: center; color: var(--muted);">
      <i data-lucide="flask-conical" style="width: 48px; height: 48px; stroke-width: 1.5; margin-bottom: 12px; display: inline-block;"></i>
      <div style="font-size: 14px; font-weight: 600;">Belum ada riwayat pemeriksaan laboratorium untuk pasien ini.</div>
    </div>
  <?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 16px;">
      <?php foreach ($labHistory as $lh): ?>
        <div class="card" style="padding: 18px 22px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
            <div>
              <span class="font-mono" style="font-size: 14px; font-weight: 700; color: var(--blue-950);">
                <?= htmlspecialchars($lh['order_number']) ?>
              </span>
              <span style="font-size: 12px; color: var(--muted); margin-left: 8px;">
                (<?= format_datetime_id($lh['created_at']) ?>)
              </span>
              <div style="font-size: 12px; color: var(--slate-600); margin-top: 2px;">
                Dokter Perujuk: <?= htmlspecialchars($lh['doctor_name'] ?? '-') ?> &bull; Analis: <?= htmlspecialchars($lh['analyst_name'] ?? '-') ?>
              </div>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
              <span class="badge <?= ($lh['status'] === 'VALID') ? 'badge-success' : 'badge-warning' ?>">
                <?= htmlspecialchars($lh['status']) ?>
              </span>
              <?php if ($lh['status'] === 'VALID'): ?>
                <a href="<?= base_url('index.php?c=laboratory&a=printResult&id=' . $lh['id']) ?>" target="_blank" class="btn btn-outline" style="height: 30px; font-size: 11.5px; padding: 0 8px;">
                  <i data-lucide="printer"></i>
                  <span>Cetak Hasil</span>
                </a>
              <?php endif; ?>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table" style="font-size: 12.5px;">
              <thead>
                <tr>
                  <th>Nama Pemeriksaan</th>
                  <th>Hasil Uji</th>
                  <th>Nilai Rujukan</th>
                  <th>Satuan</th>
                  <th>Flag Evaluasi</th>
                  <th>Catatan Analis</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($lh['items'] as $item): ?>
                  <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($item['test_name']) ?></td>
                    <td class="font-mono" style="font-weight: 700; color: var(--blue-900);">
                      <?= htmlspecialchars($item['result_value'] ?? 'Dalam Proses') ?>
                    </td>
                    <td><?= htmlspecialchars($item['result_normal_range'] ?? $item['default_normal_range'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($item['result_unit'] ?? $item['default_unit'] ?? '-') ?></td>
                    <td>
                      <?php
                        $flg = $item['result_flag'] ?? 'NORMAL';
                        $flgClass = 'badge-success';
                        if ($flg === 'HIGH') $flgClass = 'badge-warning';
                        elseif ($flg === 'LOW') $flgClass = 'badge-info';
                        elseif ($flg === 'CRITICAL') $flgClass = 'badge-danger';
                      ?>
                      <span class="badge <?= $flgClass ?>"><?= $flg ?></span>
                    </td>
                    <td style="color: var(--muted);"><?= htmlspecialchars($item['analyst_notes'] ?? '-') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- TAB 3: RIWAYAT RADIOLOGI & CITRA MEDIS -->
<div id="tabContentRad" style="display: none;">
  <?php if (empty($radHistory)): ?>
    <div class="card" style="padding: 40px; text-align: center; color: var(--muted);">
      <i data-lucide="film" style="width: 48px; height: 48px; stroke-width: 1.5; margin-bottom: 12px; display: inline-block;"></i>
      <div style="font-size: 14px; font-weight: 600;">Belum ada riwayat diagnostik radiologi untuk pasien ini.</div>
    </div>
  <?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 16px;">
      <?php foreach ($radHistory as $rh): ?>
        <div class="card" style="padding: 18px 22px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
            <div>
              <span class="font-mono" style="font-size: 14px; font-weight: 700; color: var(--blue-950);">
                <?= htmlspecialchars($rh['order_number']) ?>
              </span>
              <span class="badge badge-primary" style="margin-left: 6px;">
                <?= htmlspecialchars($rh['modality_type']) ?>
              </span>
              <span style="font-size: 12px; color: var(--muted); margin-left: 8px;">
                (<?= format_datetime_id($rh['created_at']) ?>)
              </span>
              <div style="font-size: 12px; color: var(--slate-600); margin-top: 2px;">
                Radiografer: <?= htmlspecialchars($rh['radiographer_name'] ?? '-') ?> &bull; Spesialis Radiologi: <?= htmlspecialchars($rh['radiologist_name'] ?? '-') ?>
              </div>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
              <span class="badge <?= ($rh['status'] === 'VALID') ? 'badge-success' : 'badge-warning' ?>">
                <?= htmlspecialchars($rh['status']) ?>
              </span>
              <?php if ($rh['status'] === 'VALID'): ?>
                <a href="<?= base_url('index.php?c=radiology&a=printResult&id=' . $rh['id']) ?>" target="_blank" class="btn btn-outline" style="height: 30px; font-size: 11.5px; padding: 0 8px;">
                  <i data-lucide="printer"></i>
                  <span>Cetak Ekspertise</span>
                </a>
              <?php endif; ?>
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 240px 1fr; gap: 18px; align-items: flex-start;">
            <!-- Pratinjau Citra WebP -->
            <div>
              <?php if (!empty($rh['image_path']) && $rh['status'] === 'VALID'): ?>
                <a href="<?= base_url($rh['image_path']) ?>" target="_blank" title="Klik untuk memperbesar citra radiologi">
                  <img src="<?= base_url($rh['image_path']) ?>" alt="Citra Radiologi" 
                       style="width: 100%; height: 180px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border); background: #000;">
                </a>
                <div style="font-size: 11px; color: var(--muted); text-align: center; margin-top: 4px;">
                  Citra pemeriksaan tersimpan
                </div>
              <?php else: ?>
                <div style="width: 100%; height: 160px; background: #f1f5f9; border: 1px dashed var(--border); border-radius: 6px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--muted); font-size: 12px;">
                  <i data-lucide="image-off" style="width: 28px; height: 28px; margin-bottom: 4px;"></i>
                  <span>Citra Belum Tersedia</span>
                </div>
              <?php endif; ?>
            </div>

            <!-- Temuan & Ekspertise -->
            <div style="font-size: 12.5px;">
              <div style="margin-bottom: 8px;">
                <b style="color: var(--slate-700);">Indikasi Klinis:</b>
                <div style="color: var(--slate-600);"><?= htmlspecialchars($rh['clinical_indication'] ?? '-') ?></div>
              </div>
              <div style="margin-bottom: 8px;">
                <b style="color: var(--blue-900);">Temuan Ekspertise Radiologi:</b>
                <div style="color: var(--slate-800); margin-top: 2px; background: #f8fafc; padding: 8px 10px; border-radius: 4px;">
                  <?= nl2br(htmlspecialchars($rh['expertise_findings'] ?? 'Belum ada bacaan ekspertise')) ?>
                </div>
              </div>
              <div>
                <b style="color: var(--blue-900);">Kesimpulan Diagnosa:</b>
                <div style="color: var(--blue-950); font-weight: 600; margin-top: 2px;">
                  <?= nl2br(htmlspecialchars($rh['expertise_conclusion'] ?? 'Menunggu validasi dokter spesialis radiologi')) ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
function showTab(tabName) {
  const tabs = ['emr', 'lab', 'rad'];
  tabs.forEach(t => {
    document.getElementById('tabContent' + t.charAt(0).toUpperCase() + t.slice(1)).style.display = (t === tabName) ? 'block' : 'none';
    const btn = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
    if (t === tabName) {
      btn.className = 'btn btn-primary';
    } else {
      btn.className = 'btn btn-outline';
    }
  });
}
</script>
