<?php
// app/views/billing/claims.php - Pengajuan & Verifikasi Klaim Asuransi/BPJS
?>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--blue-900); display: flex; align-items: center; gap: 8px;">
      <i data-lucide="shield-check" style="color: #3b82f6; width: 22px; height: 22px;"></i>
      <span>Pengajuan &amp; Verifikasi Klaim Asuransi / BPJS</span>
    </h1>
    <p style="margin: 4px 0 0; color: var(--muted); font-size: 13px;">
      Pelacakan pengajuan klaim JKN/BPJS dan asuransi komersial, rekonsiliasi nominal disetujui, dan penanganan status rejected.
    </p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="<?= base_url('index.php?c=billing&a=index') ?>" class="btn btn-outline">
      <i data-lucide="arrow-left"></i>
      <span>Kembali ke Billing Kasir</span>
    </a>
    <a href="<?= base_url('index.php?c=billing&a=claims') ?>" class="btn btn-outline">
      <i data-lucide="refresh-cw"></i>
      <span>Segarkan</span>
    </a>
  </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 18px; padding: 14px 18px;">
  <form method="GET" action="<?= base_url('index.php') ?>" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
    <input type="hidden" name="c" value="billing">
    <input type="hidden" name="a" value="claims">

    <div style="flex: 1; min-width: 220px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Cari No. Klaim / Pasien / No. Invoice</label>
      <input type="text" name="search" class="form-control" placeholder="Ketik kata kunci..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
    </div>

    <div style="width: 170px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Jenis Penjamin</label>
      <select name="insurance_type" class="form-control">
        <option value="">Semua Penjamin</option>
        <option value="BPJS" <?= ($filters['insurance_type'] ?? '') === 'BPJS' ? 'selected' : '' ?>>BPJS Kesehatan</option>
        <option value="ASURANSI_SWASTA" <?= ($filters['insurance_type'] ?? '') === 'ASURANSI_SWASTA' ? 'selected' : '' ?>>Asuransi Swasta</option>
      </select>
    </div>

    <div style="width: 170px;">
      <label class="form-label" style="font-size: 11px; text-transform: uppercase;">Status Klaim</label>
      <select name="status" class="form-control">
        <option value="">Semua Status</option>
        <option value="SUBMITTED" <?= ($filters['status'] ?? '') === 'SUBMITTED' ? 'selected' : '' ?>>Diajukan (Submitted)</option>
        <option value="APPROVED" <?= ($filters['status'] ?? '') === 'APPROVED' ? 'selected' : '' ?>>Disetujui (Approved)</option>
        <option value="REJECTED" <?= ($filters['status'] ?? '') === 'REJECTED' ? 'selected' : '' ?>>Ditolak (Rejected)</option>
        <option value="PENDING" <?= ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' ?>>Tertunda (Pending)</option>
      </select>
    </div>

    <div>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="filter"></i>
        <span>Filter</span>
      </button>
      <a href="<?= base_url('index.php?c=billing&a=claims') ?>" class="btn btn-outline" title="Reset Filter">
        <i data-lucide="rotate-ccw"></i>
      </a>
    </div>
  </form>
</div>

<!-- Claims Table -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 14px 18px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
    <h3 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--ink);">
      Berkas Klaim Penjamin Asuransi &amp; BPJS
    </h3>
    <span class="badge badge-muted"><?= count($claims) ?> Berkas</span>
  </div>

  <div style="overflow-x: auto;">
    <table class="table" style="margin: 0;">
      <thead>
        <tr>
          <th>No. Klaim</th>
          <th>Tgl Diajukan</th>
          <th>Pasien &amp; No. Polis</th>
          <th>Penjamin</th>
          <th>Diagnosa ICD-10</th>
          <th style="text-align: right;">Nominal Diajukan</th>
          <th style="text-align: right;">Nominal Disetujui</th>
          <th>Status Klaim</th>
          <th style="text-align: right;">Aksi Verifikasi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($claims)): ?>
          <tr>
            <td colspan="9" style="text-align: center; padding: 32px; color: var(--muted);">
              Belum ada berkas klaim asuransi yang tercatat.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($claims as $c): ?>
            <tr>
              <td>
                <span style="font-family: ui-monospace, monospace; font-weight: 700; color: var(--blue-900);">
                  <?= htmlspecialchars($c['claim_number']) ?>
                </span>
                <div style="font-size: 10.5px; color: var(--muted); margin-top: 2px;">
                  Ref: <?= htmlspecialchars($c['invoice_number']) ?>
                </div>
              </td>
              <td style="font-size: 12px; white-space: nowrap;">
                <?= date('d M Y', strtotime($c['submission_date'])) ?>
              </td>
              <td>
                <div style="font-weight: 700; color: var(--ink);">
                  <?= htmlspecialchars($c['patient_name']) ?>
                </div>
                <div style="font-size: 11px; color: var(--muted); font-family: ui-monospace, monospace;">
                  RM: <?= htmlspecialchars($c['no_rm']) ?> &bull; Polis: <?= htmlspecialchars($c['policy_number']) ?>
                </div>
              </td>
              <td>
                <?php if ($c['insurance_type'] === 'BPJS'): ?>
                  <span class="badge badge-success">BPJS KESEHATAN</span>
                <?php else: ?>
                  <span class="badge badge-info">ASURANSI SWASTA</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge badge-primary">
                  <?= htmlspecialchars($c['diagnosis_code']) ?>
                </span>
                <div style="font-size: 11px; color: var(--muted); margin-top: 2px;">
                  <?= htmlspecialchars($c['diagnosis_name'] ?? 'Pemeriksaan Klinis') ?>
                </div>
              </td>
              <td class="num" style="font-weight: 600;">
                Rp <?= number_format($c['claimed_amount'], 0, ',', '.') ?>
              </td>
              <td class="num" style="font-weight: 700; color: #047857;">
                Rp <?= number_format($c['approved_amount'], 0, ',', '.') ?>
              </td>
              <td>
                <?php if ($c['status'] === 'APPROVED'): ?>
                  <span class="badge badge-success"><i data-lucide="check" class="lucide-sm"></i> APPROVED</span>
                <?php elseif ($c['status'] === 'SUBMITTED'): ?>
                  <span class="badge badge-info"><i data-lucide="send" class="lucide-sm"></i> SUBMITTED</span>
                <?php elseif ($c['status'] === 'PENDING'): ?>
                  <span class="badge badge-warning"><i data-lucide="clock" class="lucide-sm"></i> PENDING</span>
                <?php else: ?>
                  <span class="badge badge-danger"><i data-lucide="x" class="lucide-sm"></i> REJECTED</span>
                <?php endif; ?>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <?php if ($c['status'] === 'SUBMITTED' || $c['status'] === 'PENDING'): ?>
                  <div style="display: inline-flex; gap: 4px;">
                    <!-- Setujui Klaim -->
                    <form method="POST" action="<?= base_url('index.php?c=billing&a=updateClaim') ?>" style="display:inline;" onsubmit="return confirm('Setujui klaim asuransi ini sebesar Rp <?= number_format($c['claimed_amount'], 0, ',', '.') ?>?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="claim_id" value="<?= $c['id'] ?>">
                      <input type="hidden" name="status" value="APPROVED">
                      <input type="hidden" name="approved_amount" value="<?= $c['claimed_amount'] ?>">
                      <button type="submit" class="btn btn-sm btn-success" title="Setujui Penuh (Approve)">
                        <i data-lucide="check"></i> Approve
                      </button>
                    </form>

                    <!-- Tolak Klaim -->
                    <form method="POST" action="<?= base_url('index.php?c=billing&a=updateClaim') ?>" style="display:inline;" onsubmit="return confirm('PERINGATAN: Menolak klaim akan mengembalikan biaya ke tagihan mandiri pasien (Self-Pay). Lanjutkan penolakan?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="claim_id" value="<?= $c['id'] ?>">
                      <input type="hidden" name="status" value="REJECTED">
                      <input type="hidden" name="approved_amount" value="0">
                      <input type="hidden" name="notes" value="Dokumen penjamin tidak memenuhi syarat">
                      <button type="submit" class="btn btn-sm btn-outline" style="color: var(--danger); border-color: var(--danger-line);" title="Tolak Klaim (Reject)">
                        <i data-lucide="x"></i> Reject
                      </button>
                    </form>
                  </div>
                <?php else: ?>
                  <span style="font-size: 11px; color: var(--muted);">Telah Tuntas</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php include __DIR__ . '/../components/pagination.php'; ?>
</div>

