<?php
// app/views/cpoe/pharmacy_queue.php - Antrean Farmasi & Dispensing E-Resep
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="pill" style="color: #059669; width: 22px; height: 22px;"></i>
      <span>Antrean Farmasi &amp; Dispensing Obat (CPOE)</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Verifikasi resep elektronik, telaah interaksi obat &amp; alergi, penyiapan sediaan obat, dan penyerahan ke pasien.
    </p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= base_url('index.php?c=cpoe&a=index') ?>" class="btn btn-outline">
      <i data-lucide="list"></i>
      <span>Semua Order CPOE</span>
    </a>
    <a href="<?= base_url('index.php?c=cpoe&a=pharmacyQueue') ?>" class="btn btn-outline">
      <i data-lucide="refresh-cw"></i>
      <span>Segarkan</span>
    </a>
  </div>
</div>

<!-- Antrean List -->
<?php if (empty($queue)): ?>
  <div class="card" style="text-align: center; padding: 48px; color: var(--muted);">
    <i data-lucide="check-circle" style="width: 48px; height: 48px; color: var(--success); margin-bottom: 12px;"></i>
    <h3 style="margin: 0 0 6px; color: var(--ink); font-size: 16px;">Semua Resep Sudah Dilayani!</h3>
    <p style="margin: 0; font-size: 13px;">Tidak ada resep obat CPOE yang sedang menunggu antrean dispensing saat ini.</p>
  </div>
<?php else: ?>
  <div style="display: flex; flex-direction: column; gap: 16px;">
    <?php foreach ($queue as $q): ?>
      <div class="card" style="padding: 18px; border-left: 4px solid <?= ($q['priority'] === 'CITO') ? '#dc2626' : '#10b981' ?>;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; border-bottom: 1px solid var(--line); padding-bottom: 12px;">
          <div>
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
              <span style="font-family: ui-monospace, monospace; font-size: 15px; font-weight: 700; color: var(--blue-900);">
                <?= htmlspecialchars($q['order_number']) ?>
              </span>
              <?php if ($q['priority'] === 'CITO'): ?>
                <span class="badge badge-cito"><i data-lucide="zap" class="lucide-sm"></i> CITO (DARURAT)</span>
              <?php else: ?>
                <span class="badge badge-reguler">REGULER</span>
              <?php endif; ?>
              <span class="badge badge-primary">Unit: <?= htmlspecialchars($q['visit_type']) ?></span>
              <span style="font-size: 11.5px; color: var(--muted);">
                <i data-lucide="clock" class="lucide-sm"></i> <?= date('d M Y, H:i', strtotime($q['created_at'])) ?>
              </span>
            </div>
            <div style="font-size: 14px; font-weight: 700; color: var(--ink); margin-top: 4px;">
              <?= htmlspecialchars($q['patient_name']) ?> 
              <span style="font-weight: 400; color: var(--muted); font-size: 12px;">(No. RM: <?= htmlspecialchars($q['no_rm']) ?> &bull; Penjamin: <?= htmlspecialchars($q['insurance_type']) ?>)</span>
            </div>
            <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">
              Dokter DPJP: <b><?= htmlspecialchars($q['doctor_name']) ?></b> (<?= htmlspecialchars($q['specialization'] ?? 'Spesialis') ?>)
            </div>
          </div>

          <div>
            <form method="POST" action="<?= base_url('index.php?c=cpoe&a=dispense') ?>" onsubmit="return confirm('Konfirmasi bahwa seluruh obat pada resep ini telah diverifikasi, disiapkan, dan diserahkan ke pasien?');">
              <?= csrf_field() ?>
              <input type="hidden" name="order_id" value="<?= $q['id'] ?>">
              <button type="submit" class="btn btn-primary" style="background: #059669; border-color: #047857; padding: 8px 18px; font-weight: 600;">
                <i data-lucide="check-check"></i>
                <span>Dispense &amp; Serahkan Obat</span>
              </button>
            </form>
          </div>
        </div>

        <!-- Rincian Item Resep -->
        <div style="background: var(--surface-alt); border-radius: var(--radius-sm); padding: 12px 14px;">
          <h4 style="margin: 0 0 8px; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); letter-spacing: 0.04em;">
            Rincian Formularium &amp; Aturan Pakai:
          </h4>
          <div style="overflow-x: auto;">
            <table class="table" style="margin: 0; background: #fff; border-radius: var(--radius-xs);">
              <thead>
                <tr>
                  <th>Nama Obat</th>
                  <th>Dosis</th>
                  <th>Frekuensi</th>
                  <th>Rute</th>
                  <th style="text-align: right;">Jumlah</th>
                  <th>Aturan Pakai / Catatan Apoteker</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($q['items'] as $it): ?>
                  <tr>
                    <td style="font-weight: 600; color: var(--blue-900);">
                      <?= htmlspecialchars($it['item_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($it['dosage'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($it['frequency'] ?? '-') ?></td>
                    <td><span class="badge badge-muted"><?= htmlspecialchars($it['route'] ?? 'Oral') ?></span></td>
                    <td class="num" style="font-weight: 700;"><?= (int)$it['quantity'] ?></td>
                    <td style="color: var(--ink);"><?= htmlspecialchars($it['instructions'] ?? '-') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <?php if (!empty($q['clinical_notes'])): ?>
          <div style="margin-top: 10px; font-size: 11.5px; color: var(--muted);">
            <b>Catatan DPJP:</b> <?= htmlspecialchars($q['clinical_notes']) ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

