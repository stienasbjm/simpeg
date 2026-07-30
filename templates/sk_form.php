<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/sk_functions.php';
require_login();
$is_edit = false; $data = [];
$form_url = BASE_URL . 'sk_save';
if (isset($_GET['id'])) {
    $id = (int)$_GET['id']; $data = get_sk_by_id($conn, $id);
    if ($data) { $is_edit = true; $form_url .= '?id=' . $id; }
    else { $_SESSION['error_message'] = "Data SK tidak ditemukan."; header('Location: ' . BASE_URL . 'sk'); exit(); }
}
?>

<div class="e-page-header">
  <div>
    <h1 class="e-page-title">
      <i class="bi bi-file-earmark-text-fill" style="color:var(--amber);"></i>
      <?php echo $is_edit ? 'Edit Surat Keputusan' : 'Tambah Surat Keputusan'; ?>
    </h1>
    <p class="e-page-sub"><?php echo $is_edit ? 'Perbarui dokumen SK.' : 'Upload dan arsipkan SK baru.'; ?></p>
  </div>
  <a href="<?php echo BASE_URL; ?>sk" class="e-btn e-btn-ghost">
    <i class="bi bi-arrow-left"></i> Kembali
  </a>
</div>

<?php if (!empty($_SESSION['error_message'])): ?>
<div class="e-notice danger"><i class="bi bi-exclamation-triangle-fill"></i><span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span></div>
<?php endif; ?>

<div class="e-card">
  <div class="e-card-header">
    <div class="e-card-title"><i class="bi bi-file-earmark-ruled" style="color:var(--amber);"></i> Detail Surat Keputusan</div>
  </div>
  <div class="e-card-body">
    <form action="<?php echo $form_url; ?>" method="POST" enctype="multipart/form-data">
      <?php if ($is_edit): ?>
        <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
      <?php endif; ?>

      <div class="e-form-section"><i class="bi bi-info-circle"></i> Data SK</div>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="e-form-group">
            <label class="e-label" for="nomor_sk">Nomor SK <span class="req">*</span></label>
            <input class="e-input" type="text" id="nomor_sk" name="nomor_sk"
              value="<?php echo htmlspecialchars($data['nomor_sk'] ?? ''); ?>"
              placeholder="Contoh: 120/SK-REKTOR/2024" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="e-form-group">
            <label class="e-label" for="tanggal_sk">Tanggal Penetapan <span class="req">*</span></label>
            <input class="e-input" type="date" id="tanggal_sk" name="tanggal_sk"
              value="<?php echo htmlspecialchars($data['tanggal_sk'] ?? ''); ?>" required>
          </div>
        </div>
        <div class="col-12">
          <div class="e-form-group">
            <label class="e-label" for="tentang">Tentang / Judul SK <span class="req">*</span></label>
            <textarea class="e-textarea" id="tentang" name="tentang" rows="3"
              placeholder="Subjek atau ketetapan dalam SK..." required><?php echo htmlspecialchars($data['tentang'] ?? ''); ?></textarea>
          </div>
        </div>
      </div>

      <div class="e-form-section" style="margin-top:1.25rem;"><i class="bi bi-paperclip"></i> File SK</div>

      <div class="col-md-7">
        <div class="e-form-group">
          <label class="e-label" for="file_sk">Berkas SK (PDF / JPG / PNG)</label>
          <input class="e-file-input" type="file" id="file_sk" name="file_sk">
          <?php if (!empty($data['file_sk'])): ?>
          <div style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem;font-size:.78rem;color:var(--text-muted);">
            <i class="bi bi-file-earmark-check" style="color:var(--green);"></i>
            <a href="<?php echo BASE_URL . $data['file_sk']; ?>" target="_blank" style="color:var(--blue);font-weight:600;">Lihat File SK</a>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="e-divider"></div>
      <div style="display:flex;gap:.625rem;">
        <button type="submit" class="e-btn e-btn-primary">
          <i class="bi bi-check-lg"></i> <?php echo $is_edit ? 'Simpan Perubahan' : 'Simpan Data'; ?>
        </button>
        <a href="<?php echo BASE_URL; ?>sk" class="e-btn e-btn-ghost">Batal</a>
      </div>
    </form>
  </div>
</div>
