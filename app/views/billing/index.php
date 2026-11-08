<?php
// app/views/billing/index.php - Dashboard Billing, Kasir & Asuransi (SOP/SIMRS/011/2026)
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="receipt" style="color: #059669; width: 22px; height: 22px;"></i>
      <span>Billing, Kasir &amp; Klaim Asuransi</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Perhitungan otomatis tarif pelayanan (CPOE, Lab, Rad, Obat), penerbitan faktur, manajemen klaim BPJS/Asuransi, dan pembayaran kasir.
    </p>
  </div>
  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <a href="<?= base_url('index.php?c=patient&a=create&source=billing') ?>" class="btn btn-outline" style="border-color: #059669; color: #047857;" title="Pendaftaran Pasien Baru di Kasir">
      <i data-lucide="user-plus"></i>
      <span>+ Pasien Baru (Kasir)</span>
    </a>
    <a href="<?= base_url('index.php?c=billing&a=claims') ?>" class="btn btn-outline" style="border-color: #3b82f6; color: #1d4ed8;">
      <i data-lucide="shield-check"></i>
      <span>Klaim Asuransi &amp; BPJS (<?= $stats['claims_pending'] ?>)</span>
    </a>
    <a href="<?= base_url('index.php?c=billing&a=index') ?>" class="btn btn-outline">
      <i data-lucide="refresh-cw"></i>
      <span>Segarkan</span>
    </a>
  </div>
</div>

<!-- KPI Cards Finansial -->
<div class="grid-4" style="margin-bottom: 20px;">
  <div class="stat-card" style="border-left: 3px solid #10b981;">
    <div class="top">
      <span>Pendapatan Kasir Hari Ini</span>
    </div>
    <div class="num" style="color: #047857; font-size: 20px;">
      Rp <?= number_format($stats['revenue_today'], 0, ',', '.') ?>
    </div>
    <div class="desc">
      <span class="badge badge-success"><?= $stats['paid_today'] ?> Transaksi</span>
      <span>pembayaran lunas</span>
    </div>
  </div>

  <div class="stat-card" style="border-left: 3px solid #f59e0b;">
    <div class="top">
      <span>Tagihan Belum Lunas</span>
    </div>
    <div class="num" style="color: #b45309;"><?= $stats['unpaid_count'] ?></div>
    <div class="desc">
      <span class="badge badge-warning"><?= $stats['unpaid_count'] ?> Invoice</span>
      <span>menunggu penyelesaian kasir</span>
    </div>
  </div>

  <div class="stat-card" style="border-left: 3px solid #3b82f6;">
    <div class="top">
      <span>Klaim BPJS / Asuransi Pending</span>
    </div>
    <div class="num" style="color: #1d4ed8;"><?= $stats['claims_pending'] ?></div>
    <div class="desc">
      <span class="badge badge-info"><?= $stats['claims_pending'] ?> Berkas</span>
      <span>verifikasi eligibilitas &amp; berkas</span>
    </div>
  </div>

  <div class="stat-card" style="border-left: 3px solid #6366f1;">
    <div class="top">
      <span>Pasien Selesai Kasir Hari Ini</span>
    </div>
    <div class="num" style="color: #4338ca;"><?= $stats['paid_today'] ?></div>
    <div class="desc">
      <span class="badge badge-primary"><?= $stats['paid_today'] ?> Pasien</span>
      <span>struk kuitansi tercetak</span>
    </div>
  </div>
</div>

<!-- Section 1: Antrean Pasien Siap Ditagih (Unbilled / Unpaid Queue) -->
<div class="card" style="padding: 0; overflow: hidden; margin-bottom: 24px;">
  <div style="padding: 14px 18px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h3 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--ink);">
        Antrean Pelayanan Pasien Siap Ditagihkan (Rawat Jalan &amp; IGD)
      </h3>
      <p style="margin: 2px 0 0; font-size: 11.5px; color: var(--muted);">
        Kunjungan yang telah selesai diperiksa dokter dan memerlukan agregasi biaya otomatis atau pembayaran kasir.
      </p>
    </div>
    <span class="badge badge-warning"><?= count($unbilled) ?> Menunggu</span>
  </div>

  <div style="overflow-x: auto;">
    <table class="table" style="margin: 0;">
      <thead>
        <tr>
          <th>No. Kunjungan</th>
          <th>Tanggal</th>
          <th>Pasien &amp; No. RM</th>
          <th>Unit Pelayanan</th>
          <th>Penjamin</th>
          <th>Dokter Pemeriksa</th>
          <th>Status Tagihan</th>
          <th style="text-align: right;">Aksi Kasir</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($unbilled)): ?>
          <tr>
            <td colspan="8" style="text-align: center; padding: 32px; color: var(--muted);">
              <i data-lucide="check-circle" style="width: 32px; height: 32px; margin-bottom: 6px; color: var(--success); opacity: 0.8;"></i>
              <p style="margin: 0;">Semua kunjungan pasien telah diselesaikan tagihannya!</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($unbilled as $row): ?>
            <tr>
              <td>
                <span style="font-family: ui-monospace, monospace; font-weight: 700; color: var(--blue-900);">
                  <?= htmlspecialchars($row['visit_number']) ?>
                </span>
                <div style="font-size: 10.5px; color: var(--muted); margin-top: 2px;">
                  Tipe: <?= htmlspecialchars($row['visit_type']) ?>
                </div>
              </td>
              <td style="font-size: 12px; white-space: nowrap;">
                <?= date('d M Y', strtotime($row['visit_date'])) ?>
              </td>
              <td>
                <div style="font-weight: 700; color: var(--ink);">
                  <?= htmlspecialchars($row['patient_name']) ?>
                </div>
                <div style="font-size: 11px; color: var(--muted); font-family: ui-monospace, monospace;">
                  <?= htmlspecialchars($row['no_rm']) ?>
                </div>
              </td>
              <td>
                <span class="badge badge-muted">
                  <?= htmlspecialchars($row['unit_name']) ?>
                </span>
              </td>
              <td>
                <?php if ($row['insurance_type'] === 'BPJS'): ?>
                  <span class="badge badge-success">BPJS KESEHATAN</span>
                <?php elseif ($row['insurance_type'] === 'ASURANSI'): ?>
                  <span class="badge badge-info">ASURANSI SWASTA</span>
                <?php else: ?>
                  <span class="badge badge-muted">UMUM (SELF-PAY)</span>
                <?php endif; ?>
              </td>
              <td style="font-size: 12.5px; color: var(--ink);">
                <?= htmlspecialchars($row['doctor_name']) ?>
              </td>
              <td>
                <?php if (empty($row['invoice_id'])): ?>
                  <span class="badge badge-warning">Belum Terbit Invoice</span>
                <?php else: ?>
                  <span class="badge badge-danger">Invoice Belum Bayar</span>
                  <div style="font-size: 11px; color: var(--ink); font-weight: 700; margin-top: 2px;">
                    Rp <?= number_format($row['patient_amount'], 0, ',', '.') ?>
                  </div>
                <?php endif; ?>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <?php if (empty($row['invoice_id'])): ?>
                  <a href="<?= base_url('index.php?c=billing&a=invoice&visit_type=' . $row['visit_type'] . '&reference_id=' . $row['reference_id']) ?>" class="btn btn-sm btn-primary">
                    <i data-lucide="calculator"></i>
                    <span>Hitung Tagihan</span>
                  </a>
                <?php else: ?>
                  <a href="<?= base_url('index.php?c=billing&a=payment&id=' . $row['invoice_id']) ?>" class="btn btn-sm btn-success" style="background: #059669; border-color: #047857;">
                    <i data-lucide="credit-card"></i>
                    <span>Proses Bayar</span>
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Section 2: Riwayat Faktur / Invoice Terbaru -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 14px 18px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
    <h3 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--ink);">
      Riwayat Invoice &amp; Penerimaan Pembayaran Kasir
    </h3>
    <span class="badge badge-muted"><?= count($recentInvoices) ?> Terakhir</span>
  </div>

  <div style="overflow-x: auto;">
    <table class="table" style="margin: 0;">
      <thead>
        <tr>
          <th>No. Invoice</th>
          <th>Waktu</th>
          <th>Pasien</th>
          <th style="text-align: right;">Total Biaya (Gross)</th>
          <th style="text-align: right;">Tanggungan Penjamin</th>
          <th style="text-align: right;">Tagihan Pasien</th>
          <th>Status</th>
          <th>Kasir</th>
          <th style="text-align: right;">Bukti</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentInvoices)): ?>
          <tr>
            <td colspan="9" style="text-align: center; padding: 28px; color: var(--muted);">
              Belum ada riwayat transaksi invoice.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($recentInvoices as $inv): ?>
            <tr>
              <td>
                <span style="font-family: ui-monospace, monospace; font-weight: 700; color: var(--blue-900);">
                  <?= htmlspecialchars($inv['invoice_number']) ?>
                </span>
                <div style="font-size: 10.5px; color: var(--muted);">
                  <?= htmlspecialchars($inv['visit_type']) ?> &bull; <?= htmlspecialchars($inv['patient_type']) ?>
                </div>
              </td>
              <td style="font-size: 11.5px; white-space: nowrap;">
                <?= date('d M Y, H:i', strtotime($inv['created_at'])) ?>
              </td>
              <td>
                <div style="font-weight: 600; color: var(--ink);">
                  <?= htmlspecialchars($inv['patient_name']) ?>
                </div>
                <div style="font-size: 10.5px; color: var(--muted); font-family: ui-monospace, monospace;">
                  <?= htmlspecialchars($inv['no_rm']) ?>
                </div>
              </td>
              <td class="num" style="font-weight: 600;">
                Rp <?= number_format($inv['total_gross'], 0, ',', '.') ?>
              </td>
              <td class="num" style="color: #047857;">
                Rp <?= number_format($inv['insurance_coverage'], 0, ',', '.') ?>
              </td>
              <td class="num" style="font-weight: 700; color: var(--blue-900);">
                Rp <?= number_format($inv['patient_amount'], 0, ',', '.') ?>
              </td>
              <td>
                <?php if ($inv['status'] === 'PAID'): ?>
                  <span class="badge badge-success"><i data-lucide="check" class="lucide-sm"></i> LUNAS</span>
                <?php elseif ($inv['status'] === 'UNPAID'): ?>
                  <span class="badge badge-warning"><i data-lucide="clock" class="lucide-sm"></i> BELUM BAYAR</span>
                <?php else: ?>
                  <span class="badge badge-danger">BATAL</span>
                <?php endif; ?>
              </td>
              <td style="font-size: 11.5px; color: var(--muted);">
                <?= htmlspecialchars($inv['cashier_name'] ?? '-') ?>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <?php if ($inv['status'] === 'PAID'): ?>
                  <a href="<?= base_url('index.php?c=billing&a=printReceipt&id=' . $inv['id']) ?>" target="_blank" class="btn btn-sm btn-outline" title="Cetak Kuitansi Resmi">
                    <i data-lucide="printer"></i> Kuitansi
                  </a>
                <?php else: ?>
                  <a href="<?= base_url('index.php?c=billing&a=payment&id=' . $inv['id']) ?>" class="btn btn-sm btn-success">
                    <i data-lucide="credit-card"></i> Bayar
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

