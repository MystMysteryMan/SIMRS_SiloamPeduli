<?php
// app/views/components/pagination.php - Reusable Siloam Pagination Control (Zero Emojis, Lucide Icons)

if (!isset($pagination) || $pagination['total'] <= 0) {
    return;
}

$page     = (int)($pagination['current_page'] ?? 1);
$lastPage = (int)($pagination['last_page'] ?? 1);
$from     = (int)($pagination['from'] ?? 0);
$to       = (int)($pagination['to'] ?? 0);
$total    = (int)($pagination['total'] ?? 0);

$buildUrl = function(int $targetPage) {
    $params = $_GET;
    $params['page'] = $targetPage;
    return 'index.php?' . http_build_query($params);
};
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding: 12px 16px; background: #fff; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; color: var(--muted); flex-wrap: wrap; gap: 12px;">
  <div>
    Menampilkan <b><?= $from ?></b> - <b><?= $to ?></b> dari <b><?= number_format($total) ?></b> data
  </div>
  
  <?php if ($lastPage > 1): ?>
    <div style="display: flex; align-items: center; gap: 6px;">
      <!-- Tombol Prev -->
      <?php if (!empty($pagination['has_prev'])): ?>
        <a href="<?= $buildUrl($page - 1) ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; display: flex; align-items: center; gap: 4px;">
          <i data-lucide="chevron-left" style="width: 14px; height: 14px;"></i>
          <span>Sebelumnya</span>
        </a>
      <?php else: ?>
        <span class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; display: flex; align-items: center; gap: 4px; opacity: 0.4; cursor: not-allowed;">
          <i data-lucide="chevron-left" style="width: 14px; height: 14px;"></i>
          <span>Sebelumnya</span>
        </span>
      <?php endif; ?>

      <!-- Indikator Halaman -->
      <span style="padding: 6px 14px; font-weight: 700; color: var(--blue-900); background: var(--blue-50); border: 1px solid var(--blue-200); border-radius: 6px; font-size: 12px;">
        Hal. <?= $page ?> dari <?= $lastPage ?>
      </span>

      <!-- Tombol Next -->
      <?php if (!empty($pagination['has_next'])): ?>
        <a href="<?= $buildUrl($page + 1) ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; display: flex; align-items: center; gap: 4px;">
          <span>Berikutnya</span>
          <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
        </a>
      <?php else: ?>
        <span class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; display: flex; align-items: center; gap: 4px; opacity: 0.4; cursor: not-allowed;">
          <span>Berikutnya</span>
          <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
        </span>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

