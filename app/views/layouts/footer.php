<?php
// app/views/layouts/footer.php - Footer Layout Template
?>
    </div> <!-- /#stage -->
  </div> <!-- /#main -->
</div> <!-- /#app -->

<!-- DIALOG KONFIRMASI KELUAR AKUN -->
<div class="veil hidden" id="dlg-logout" role="dialog" aria-modal="true" aria-labelledby="dlgOutT" aria-describedby="dlgOutM">
  <div class="modal-confirm">
    <h3 id="dlgOutT">Konfirmasi Keluar Akun</h3>
    <p id="dlgOutM">
      Sesi <b><?= htmlspecialchars($_SESSION['full_name'] ?? 'Pengguna') ?></b> sebagai <b><?= htmlspecialchars($_SESSION['role'] ?? 'Staff') ?></b> akan diakhiri di seluruh sistem. Pastikan seluruh pekerjaan telah disimpan. Lanjutkan keluar?
    </p>
    <div class="row-actions">
      <button type="button" class="btn-sec" onclick="closeLogoutDialog()">Kembali</button>
      <a href="<?= base_url('index.php?c=auth&a=logout') ?>" class="btn-dan">
        <i data-lucide="log-out" style="width: 14px; height: 14px;"></i>
        <span>Keluar dari SIMRS</span>
      </a>
    </div>
  </div>
</div>

<script src="<?= base_url('assets/js/app.js') ?>"></script>
<script>
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }
</script>
</body>
</html>
