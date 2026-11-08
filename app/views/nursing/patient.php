<?php
// app/views/nursing/patient.php - Lembar Asuhan Keperawatan Terpadu Pasien (Chart View)
?>

<!-- Header & Navigasi -->
<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <div style="display: flex; align-items: center; gap: 8px;">
      <a href="<?= base_url('index.php?c=nursing&a=index') ?>" class="btn btn-sm btn-outline" title="Kembali ke Nursing Station">
        <i data-lucide="arrow-left"></i>
      </a>
      <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900);">
        Chart Asuhan Keperawatan Terpadu
      </h1>
    </div>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Dokumentasi terintegrasi: Pengkajian Morse &amp; GCS, Monitoring TTV, Lembar MAR 6-Benar, Catatan SOAP, dan Eskalasi Klinis.
    </p>
  </div>
  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <a href="<?= base_url('index.php?c=nursing&a=assessment&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-outline">
      <i data-lucide="clipboard-check"></i>
      <span><?= $assessment ? 'Update Pengkajian' : 'Input Pengkajian' ?></span>
    </a>
    <a href="<?= base_url('index.php?c=nursing&a=vitalSigns&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-outline">
      <i data-lucide="activity"></i>
      <span>Input TTV</span>
    </a>
    <a href="<?= base_url('index.php?c=nursing&a=mar&patient_id=' . $patient['id'] . '&admission_id=' . $encounterId) ?>" class="btn btn-outline">
      <i data-lucide="pill"></i>
      <span>Lembar MAR</span>
    </a>
    <a href="<?= base_url('index.php?c=nursing&a=notes&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-outline">
      <i data-lucide="file-plus"></i>
      <span>Catat SOAP</span>
    </a>
    <a href="<?= base_url('index.php?c=nursing&a=escalate&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-outline" style="color: #dc2626; border-color: #fca5a5;">
      <i data-lucide="alert-triangle"></i>
      <span>Eskalasi DPJP</span>
    </a>
  </div>
</div>

<!-- Kartu Identitas Pasien & Ringkasan Status Klinis -->
<div class="card" style="margin-bottom: 24px; padding: 20px; background: linear-gradient(to right, #ffffff, #f8fafc); border-left: 4px solid var(--blue-700);">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
        <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--blue-900);">
          <?= htmlspecialchars($patient['name']) ?>
        </h2>
        <span class="badge" style="background: var(--blue-100); color: var(--blue-800); font-family: monospace; font-size: 13px;">
          RM: <?= htmlspecialchars($patient['no_rm']) ?>
        </span>
        <span class="badge" style="background: #e2e8f0; color: #334155;">
          <?= $encounterType === 'INPATIENT' ? 'Rawat Inap' : 'Rawat Jalan' ?> #<?= $encounterId ?>
        </span>
      </div>
      <div style="font-size: 13px; color: var(--muted); display: flex; gap: 16px; flex-wrap: wrap;">
        <span><strong>NIK:</strong> <?= htmlspecialchars($patient['nik'] ?? '-') ?></span>
        <span><strong>Gender:</strong> <?= $patient['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></span>
        <span><strong>Tgl Lahir:</strong> <?= date('d/m/Y', strtotime($patient['birth_date'])) ?> (<?= date_diff(date_create($patient['birth_date']), date_create('today'))->y ?> thn)</span>
        <span><strong>Gol. Darah:</strong> <?= htmlspecialchars($patient['blood_type'] ?? '-') ?></span>
        <span><strong>No Telp:</strong> <?= htmlspecialchars($patient['phone'] ?? '-') ?></span>
      </div>
    </div>

    <!-- Quick Clinical Status Indicators -->
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
      <div style="text-align: center; padding: 8px 14px; background: #ffffff; border: 1px solid var(--border); border-radius: 6px;">
        <span style="font-size: 11px; color: var(--muted); display: block;">Risiko Jatuh (Morse)</span>
        <?php
          $fallCat = $assessment['fall_risk_category'] ?? 'BELUM';
          $fallColor = $fallCat === 'TINGGI' ? '#dc2626' : ($fallCat === 'SEDANG' ? '#d97706' : '#16a34a');
          $fallBg = $fallCat === 'TINGGI' ? '#fee2e2' : ($fallCat === 'SEDANG' ? '#fef3c7' : '#dcfce7');
        ?>
        <strong style="color: <?= $fallColor ?>; font-size: 13px; background: <?= $fallBg ?>; padding: 2px 8px; border-radius: 4px; display: inline-block; margin-top: 2px;">
          <?= htmlspecialchars($fallCat) ?> (<?= (int)($assessment['fall_risk_score'] ?? 0) ?>)
        </strong>
      </div>

      <div style="text-align: center; padding: 8px 14px; background: #ffffff; border: 1px solid var(--border); border-radius: 6px;">
        <span style="font-size: 11px; color: var(--muted); display: block;">Skala Nyeri (NRS)</span>
        <?php
          $pain = (int)($assessment['pain_score'] ?? 0);
          $painColor = $pain >= 7 ? '#dc2626' : ($pain >= 4 ? '#d97706' : '#16a34a');
        ?>
        <strong style="color: <?= $painColor ?>; font-size: 14px; display: inline-block; margin-top: 2px;">
          <?= $pain ?> / 10
        </strong>
      </div>

      <div style="text-align: center; padding: 8px 14px; background: #ffffff; border: 1px solid var(--border); border-radius: 6px;">
        <span style="font-size: 11px; color: var(--muted); display: block;">Kesadaran</span>
        <strong style="color: var(--blue-900); font-size: 13px; display: inline-block; margin-top: 2px;">
          <?= htmlspecialchars($assessment['consciousness'] ?? 'Compos Mentis') ?>
        </strong>
      </div>

      <div style="text-align: center; padding: 8px 14px; background: #ffffff; border: 1px solid var(--border); border-radius: 6px;">
        <span style="font-size: 11px; color: var(--muted); display: block;">Status Fungsional</span>
        <strong style="color: var(--blue-900); font-size: 13px; display: inline-block; margin-top: 2px;">
          <?= htmlspecialchars($assessment['functional_status'] ?? 'MANDIRI') ?>
        </strong>
      </div>
    </div>
  </div>
</div>

<!-- Tabs Navigasi Rekam Asuhan -->
<div style="display: flex; border-bottom: 2px solid var(--border); margin-bottom: 20px; gap: 4px; overflow-x: auto;">
  <button type="button" class="tab-btn active" onclick="switchChartTab('tab-assessment', this)">
    <i data-lucide="clipboard-check" style="width: 16px; height: 16px;"></i>
    <span>Pengkajian Keperawatan</span>
  </button>
  <button type="button" class="tab-btn" onclick="switchChartTab('tab-vitals', this)">
    <i data-lucide="activity" style="width: 16px; height: 16px;"></i>
    <span>Monitoring TTV (<?= count($vitalSigns) ?>)</span>
  </button>
  <button type="button" class="tab-btn" onclick="switchChartTab('tab-mar', this)">
    <i data-lucide="pill" style="width: 16px; height: 16px;"></i>
    <span>Lembar MAR (<?= count($marRecords) ?>)</span>
  </button>
  <button type="button" class="tab-btn" onclick="switchChartTab('tab-soap', this)">
    <i data-lucide="file-text" style="width: 16px; height: 16px;"></i>
    <span>Catatan SOAP (<?= count($nursingNotes) ?>)</span>
  </button>
  <button type="button" class="tab-btn" onclick="switchChartTab('tab-escalations', this)">
    <i data-lucide="alert-triangle" style="width: 16px; height: 16px;"></i>
    <span>Eskalasi Klinis (<?= count($escalations) ?>)</span>
  </button>
</div>

<!-- Tab 1: Pengkajian Keperawatan -->
<div id="tab-assessment" class="tab-content">
  <div class="card" style="padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
      <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
        <i data-lucide="clipboard-check" style="color: var(--blue-700); width: 20px; height: 20px;"></i>
        <span>Hasil Pengkajian Awal Keperawatan</span>
      </h3>
      <a href="<?= base_url('index.php?c=nursing&a=assessment&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-sm btn-outline">
        <i data-lucide="edit-3"></i>
        <span><?= $assessment ? 'Ubah Pengkajian' : 'Input Baru' ?></span>
      </a>
    </div>

    <?php if (!$assessment): ?>
      <div style="text-align: center; padding: 32px; color: var(--muted);">
        <i data-lucide="info" style="width: 32px; height: 32px; margin-bottom: 8px; color: var(--blue-600); display: inline-block;"></i>
        <p style="margin: 0 0 12px; font-weight: 600;">Belum ada data pengkajian keperawatan untuk pasien ini.</p>
        <a href="<?= base_url('index.php?c=nursing&a=assessment&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-primary">
          <i data-lucide="plus"></i>
          <span>Buat Pengkajian Sekarang</span>
        </a>
      </div>
    <?php else: ?>
      <div class="grid-2" style="gap: 20px;">
        <div>
          <table class="table" style="font-size: 13px;">
            <tr>
              <th style="width: 40%; background: #f8fafc;">Keluhan Utama:</th>
              <td><?= nl2br(htmlspecialchars($assessment['chief_complaint'] ?? '-')) ?></td>
            </tr>
            <tr>
              <th style="background: #f8fafc;">Kondisi Umum:</th>
              <td><strong><?= htmlspecialchars($assessment['general_condition'] ?? 'Sedang') ?></strong></td>
            </tr>
            <tr>
              <th style="background: #f8fafc;">Tingkat Kesadaran:</th>
              <td><span class="badge" style="background: #e0f2fe; color: #0369a1;"><?= htmlspecialchars($assessment['consciousness'] ?? 'COMPOS_MENTIS') ?></span></td>
            </tr>
            <tr>
              <th style="background: #f8fafc;">Perawat Pengkaji:</th>
              <td><?= htmlspecialchars($assessment['nurse_name'] ?? 'Perawat Pelaksana') ?></td>
            </tr>
            <tr>
              <th style="background: #f8fafc;">Waktu Pengkajian:</th>
              <td><?= date('d/m/Y H:i', strtotime($assessment['assessment_date'])) ?></td>
            </tr>
          </table>
        </div>

        <div>
          <table class="table" style="font-size: 13px;">
            <tr>
              <th style="width: 45%; background: #f8fafc;">Risiko Jatuh (Morse):</th>
              <td>
                <span class="badge" style="background: <?= $fallBg ?>; color: <?= $fallColor ?>; font-weight: 700;">
                  <?= htmlspecialchars($assessment['fall_risk_category'] ?? 'RENDAH') ?> (Skor: <?= (int)$assessment['fall_risk_score'] ?>)
                </span>
                <small style="display: block; color: var(--muted); margin-top: 4px;">
                  <?= $assessment['fall_risk_score'] >= 45 ? 'Pasang gelang kuning risiko jatuh & perlakuan proteksi tinggi.' : 'Pengawasan standar perawat.' ?>
                </small>
              </td>
            </tr>
            <tr>
              <th style="background: #f8fafc;">Skala Nyeri (NRS 0-10):</th>
              <td>
                <strong style="color: <?= $painColor ?>; font-size: 15px;"><?= (int)$assessment['pain_score'] ?> / 10</strong>
                <span style="font-size: 12px; color: var(--muted);">
                  (<?= $pain == 0 ? 'Bebas Nyeri' : ($pain <= 3 ? 'Nyeri Ringan' : ($pain <= 6 ? 'Nyeri Sedang' : 'Nyeri Berat')) ?>)
                </span>
              </td>
            </tr>
            <tr>
              <th style="background: #f8fafc;">Status Fungsional:</th>
              <td>
                <span class="badge" style="background: #f1f5f9; color: #334155; font-weight: 600;">
                  <?= htmlspecialchars($assessment['functional_status'] ?? 'MANDIRI') ?>
                </span>
              </td>
            </tr>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Tab 2: Monitoring Tanda-Tanda Vital -->
<div id="tab-vitals" class="tab-content" style="display: none;">
  <div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border);">
      <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
        <i data-lucide="activity" style="width: 18px; height: 18px; color: var(--blue-600);"></i>
        <span>Riwayat Observasi Tanda-Tanda Vital (TTV)</span>
      </h3>
      <a href="<?= base_url('index.php?c=nursing&a=vitalSigns&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-sm btn-primary">
        <i data-lucide="plus"></i>
        <span>Catat TTV Baru</span>
      </a>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Waktu Observasi</th>
            <th>Tekanan Darah (TD)</th>
            <th>Nadi</th>
            <th>Pernapasan (RR)</th>
            <th>Suhu Tubuh</th>
            <th>SpO2</th>
            <th>Perawat</th>
            <th>Catatan Klinis</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($vitalSigns)): ?>
            <tr>
              <td colspan="8" style="text-align: center; padding: 24px; color: var(--muted);">
                Belum ada data observasi TTV tercatat.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($vitalSigns as $vs): ?>
              <tr>
                <td><strong><?= date('d/m/Y H:i', strtotime($vs['recorded_at'])) ?></strong></td>
                <td>
                  <strong style="color: <?= ($vs['systolic_bp'] > 140 || $vs['systolic_bp'] < 90) ? '#dc2626' : 'var(--blue-900)' ?>;">
                    <?= (int)$vs['systolic_bp'] ?> / <?= (int)$vs['diastolic_bp'] ?> mmHg
                  </strong>
                </td>
                <td><?= (int)$vs['pulse_rate'] ?> x/mnt</td>
                <td><?= (int)$vs['respiratory_rate'] ?> x/mnt</td>
                <td>
                  <span style="color: <?= $vs['temperature'] >= 38.0 ? '#dc2626' : 'inherit' ?>; font-weight: <?= $vs['temperature'] >= 38.0 ? '700' : 'normal' ?>;">
                    <?= number_format((float)$vs['temperature'], 1) ?> &deg;C
                  </span>
                </td>
                <td>
                  <strong style="color: <?= $vs['spo2'] < 95 ? '#dc2626' : '#16a34a' ?>;">
                    <?= (int)$vs['spo2'] ?>%
                  </strong>
                </td>
                <td><small><?= htmlspecialchars($vs['nurse_name'] ?? 'Perawat') ?></small></td>
                <td><small style="color: var(--muted);"><?= htmlspecialchars($vs['notes'] ?? '-') ?></small></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Tab 3: Lembar Pemberian Obat (MAR 6-Benar) -->
<div id="tab-mar" class="tab-content" style="display: none;">
  <div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border);">
      <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
        <i data-lucide="pill" style="width: 18px; height: 18px; color: var(--blue-600);"></i>
        <span>Medication Administration Record (Lembar MAR - Verifikasi 6-Benar)</span>
      </h3>
      <a href="<?= base_url('index.php?c=nursing&a=mar&patient_id=' . $patient['id'] . '&admission_id=' . $encounterId) ?>" class="btn btn-sm btn-primary">
        <i data-lucide="plus"></i>
        <span>Input Pemberian Obat</span>
      </a>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Waktu Terencana</th>
            <th>Nama Obat &amp; Dosis</th>
            <th>Rute</th>
            <th>Status Pemberian</th>
            <th>Respon Pasien</th>
            <th>Perawat Verifikator</th>
            <th>Waktu Diberikan</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($marRecords)): ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 24px; color: var(--muted);">
                Belum ada rekaman pemberian obat pada lembar MAR.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($marRecords as $mar): ?>
              <tr>
                <td><strong><?= date('d/m/Y H:i', strtotime($mar['planned_time'])) ?></strong></td>
                <td>
                  <strong style="color: var(--blue-900);"><?= htmlspecialchars($mar['medicine_name']) ?></strong><br>
                  <small style="color: var(--muted); font-weight: 600;">Dosis: <?= htmlspecialchars($mar['dosage']) ?></small>
                </td>
                <td><span class="badge" style="background: #f1f5f9; color: #334155;"><?= htmlspecialchars($mar['route']) ?></span></td>
                <td>
                  <?php if ($mar['status'] === 'GIVEN'): ?>
                    <span class="badge" style="background: #dcfce7; color: #15803d; font-weight: 700;">GIVEN (Diberikan)</span>
                  <?php elseif ($mar['status'] === 'DELAYED'): ?>
                    <span class="badge" style="background: #fef3c7; color: #b45309; font-weight: 700;">DELAYED (Ditunda)</span>
                  <?php else: ?>
                    <span class="badge" style="background: #fee2e2; color: #b91c1c; font-weight: 700;">REFUSED (Ditolak)</span>
                  <?php endif; ?>
                </td>
                <td><small><?= htmlspecialchars($mar['patient_response'] ?? 'Diterima baik') ?></small></td>
                <td><small><?= htmlspecialchars($mar['nurse_name'] ?? 'Perawat') ?></small></td>
                <td><small><?= $mar['given_time'] ? date('d/m/Y H:i', strtotime($mar['given_time'])) : '-' ?></small></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Tab 4: Catatan Perkembangan Keperawatan (SOAP) -->
<div id="tab-soap" class="tab-content" style="display: none;">
  <div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border);">
      <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
        <i data-lucide="file-text" style="width: 18px; height: 18px; color: var(--blue-600);"></i>
        <span>Catatan Perkembangan Pasien Terintegrasi (SOAP Keperawatan)</span>
      </h3>
      <a href="<?= base_url('index.php?c=nursing&a=notes&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-sm btn-primary">
        <i data-lucide="plus"></i>
        <span>Tambah Catatan SOAP</span>
      </a>
    </div>

    <div style="padding: 20px;">
      <?php if (empty($nursingNotes)): ?>
        <p style="text-align: center; color: var(--muted); margin: 24px 0;">Belum ada catatan SOAP keperawatan tercatat.</p>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
          <?php foreach ($nursingNotes as $n): ?>
            <div class="card" style="padding: 16px; border-left: 4px solid var(--blue-600); background: #fafafa;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span class="badge" style="background: var(--blue-100); color: var(--blue-800); font-weight: 700;">
                    Shift <?= htmlspecialchars($n['shift']) ?>
                  </span>
                  <strong style="font-size: 13px; color: var(--blue-900);"><?= date('d/m/Y H:i', strtotime($n['note_date'])) ?></strong>
                </div>
                <small style="color: var(--muted);">Perawat: <strong><?= htmlspecialchars($n['nurse_name'] ?? 'Perawat') ?></strong></small>
              </div>

              <div class="grid-2" style="gap: 12px; font-size: 13px;">
                <div>
                  <strong style="color: var(--blue-900); display: block; margin-bottom: 2px;">Subjective (S):</strong>
                  <p style="margin: 0 0 10px; background: white; padding: 8px; border-radius: 4px; border: 1px solid var(--border);">
                    <?= nl2br(htmlspecialchars($n['subjective'] ?? '-')) ?>
                  </p>

                  <strong style="color: var(--blue-900); display: block; margin-bottom: 2px;">Objective (O):</strong>
                  <p style="margin: 0; background: white; padding: 8px; border-radius: 4px; border: 1px solid var(--border);">
                    <?= nl2br(htmlspecialchars($n['objective'] ?? '-')) ?>
                  </p>
                </div>

                <div>
                  <strong style="color: var(--blue-900); display: block; margin-bottom: 2px;">Assessment / SDKI (A):</strong>
                  <p style="margin: 0 0 10px; background: white; padding: 8px; border-radius: 4px; border: 1px solid var(--border);">
                    <?= nl2br(htmlspecialchars($n['nursing_diagnosis'] ?? '-')) ?>
                  </p>

                  <strong style="color: var(--blue-900); display: block; margin-bottom: 2px;">Plan / SIKI &amp; Evaluasi (P):</strong>
                  <p style="margin: 0; background: white; padding: 8px; border-radius: 4px; border: 1px solid var(--border);">
                    <strong>Intervensi:</strong> <?= htmlspecialchars($n['interventions'] ?? '-') ?><br>
                    <strong>Evaluasi (SLKI):</strong> <?= htmlspecialchars($n['evaluation'] ?? '-') ?>
                  </p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Tab 5: Riwayat Eskalasi Klinis -->
<div id="tab-escalations" class="tab-content" style="display: none;">
  <div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border);">
      <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
        <i data-lucide="alert-triangle" style="width: 18px; height: 18px; color: #dc2626;"></i>
        <span>Riwayat Eskalasi Klinis Perawat ke Dokter DPJP</span>
      </h3>
      <a href="<?= base_url('index.php?c=nursing&a=escalate&patient_id=' . $patient['id'] . '&encounter_type=' . $encounterType . '&encounter_id=' . $encounterId) ?>" class="btn btn-sm btn-outline" style="color: #dc2626; border-color: #fca5a5;">
        <i data-lucide="plus"></i>
        <span>Lapor Eskalasi Baru</span>
      </a>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Waktu Lapor</th>
            <th>Alasan Eskalasi</th>
            <th>Ringkasan Parameter Klinis</th>
            <th>Dokter DPJP</th>
            <th>Status</th>
            <th>Instruksi Medis Dokter</th>
            <th>Waktu Respon</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($escalations)): ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 24px; color: var(--muted);">
                Tidak ada eskalasi klinis untuk pasien ini. Kondisi stabil terpantau.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($escalations as $e): ?>
              <tr>
                <td><strong><?= date('d/m/Y H:i', strtotime($e['created_at'])) ?></strong></td>
                <td style="color: #b91c1c; font-weight: 600;"><?= htmlspecialchars($e['escalation_reason']) ?></td>
                <td><small><?= htmlspecialchars($e['vital_signs_summary'] ?? '-') ?></small></td>
                <td><strong><?= htmlspecialchars($e['doctor_name'] ?? 'Dokter DPJP') ?></strong></td>
                <td>
                  <?php if ($e['status'] === 'RESPONDED'): ?>
                    <span class="badge" style="background: #dcfce7; color: #15803d; font-weight: 700;">RESPONDED</span>
                  <?php else: ?>
                    <span class="badge" style="background: #fee2e2; color: #b91c1c; font-weight: 700;">REPORTED</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($e['doctor_instruction']): ?>
                    <div style="background: #eff6ff; padding: 8px; border-radius: 4px; border-left: 3px solid #2563eb; font-size: 12px; color: #1e3a8a;">
                      <?= nl2br(htmlspecialchars($e['doctor_instruction'])) ?>
                    </div>
                  <?php else: ?>
                    <span style="color: var(--muted); font-size: 12px;">Menunggu instruksi dokter...</span>
                  <?php endif; ?>
                </td>
                <td><small><?= $e['responded_at'] ? date('d/m/Y H:i', strtotime($e['responded_at'])) : '-' ?></small></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
.tab-btn {
  background: none;
  border: none;
  padding: 10px 18px;
  font-size: 13px;
  font-weight: 600;
  color: var(--muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
}
.tab-btn:hover {
  color: var(--blue-900);
}
.tab-btn.active {
  color: var(--blue-900);
  border-bottom: 2px solid var(--blue-700);
  font-weight: 700;
}
</style>

<script>
function switchChartTab(tabId, el) {
  document.querySelectorAll('.tab-content').forEach(function(tab) {
    tab.style.display = 'none';
  });
  document.querySelectorAll('.tab-btn').forEach(function(btn) {
    btn.classList.remove('active');
  });
  document.getElementById(tabId).style.display = 'block';
  el.classList.add('active');
  if (window.lucide) lucide.createIcons();
}
</script>

