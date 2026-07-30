<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/absensi_functions.php';
require_admin();
$tanpa_akun = get_pegawai_tanpa_akun($conn);
?>
<div class="e-page-header">
  <div>
    <div class="e-breadcrumb">
      <i class="bi bi-house-fill" style="font-size:.7rem;"></i>
      <a href="<?php echo BASE_URL; ?>akun_pegawai">Akun Pegawai</a>
      <span class="e-breadcrumb-sep">/</span>
      <span style="color:var(--indigo);font-weight:600;">Buat Akun Baru</span>
    </div>
    <h1 class="e-page-title">
      <div class="title-icon" style="background:linear-gradient(135deg,rgba(99,102,241,.15),rgba(167,139,250,.08));color:var(--indigo);">
        <i class="bi bi-person-plus-fill"></i>
      </div>
      Buat Akun Pegawai
    </h1>
    <p class="e-page-sub">Buat akun login untuk pegawai agar dapat mengakses portal dan melakukan absensi.</p>
  </div>
  <a href="<?php echo BASE_URL; ?>akun_pegawai" class="e-btn e-btn-ghost"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php if (!empty($_SESSION['error_message'])): ?>
<div class="e-notice danger" data-dismiss><i class="bi bi-exclamation-triangle-fill"></i><span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span></div>
<?php endif; ?>

<div class="e-card" style="max-width:560px;">
  <div class="e-card-header">
    <div class="e-card-title"><i class="bi bi-person-badge" style="color:var(--indigo);"></i> Informasi Akun</div>
  </div>
  <div class="e-card-body">
    <form action="<?php echo BASE_URL; ?>akun_pegawai_save" method="POST">
      <input type="hidden" name="action" value="create">
      <div class="e-form-group">
        <label class="e-label" for="pegawai_id">Pilih Pegawai <span class="req">*</span></label>
        <?php if (empty($tanpa_akun)): ?>
        <div class="e-notice success" style="margin:0;"><i class="bi bi-check-circle-fill"></i><span>Semua pegawai sudah memiliki akun.</span></div>
        <?php else: ?>
        <select class="e-select" id="pegawai_id" name="pegawai_id" required>
          <option value="">— Pilih Pegawai —</option>
          <?php foreach ($tanpa_akun as $p): ?>
          <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nama']); ?> — <?php echo htmlspecialchars($p['nip']); ?></option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
      </div>
      <div class="e-form-group">
        <label class="e-label" for="username">Username <span class="req">*</span></label>
        <input class="e-input" type="text" id="username" name="username" placeholder="Contoh: budi.santoso" required autocomplete="off">
        <div style="font-size:.72rem;color:var(--text-faint);margin-top:.3rem;">Gunakan huruf kecil, tanpa spasi.</div>
      </div>
      <div class="e-form-group">
        <label class="e-label" for="password">Password <span class="req">*</span></label>
        <input class="e-input" type="password" id="password" name="password" placeholder="Min. 6 karakter" required minlength="6" autocomplete="new-password">
      </div>
      <?php if (!empty($tanpa_akun)): ?>
      <div class="e-divider"></div>
      <div style="display:flex;gap:.5rem;">
        <button type="submit" class="e-btn e-btn-primary"><i class="bi bi-check-lg"></i> Buat Akun</button>
        <a href="<?php echo BASE_URL; ?>akun_pegawai" class="e-btn e-btn-ghost">Batal</a>
      </div>
      <?php endif; ?>
    </form>
  </div>
</div>
