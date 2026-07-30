<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/surat_keluar_functions.php';
require_login();
$is_edit = false; $data = [];
$form_url = BASE_URL . 'surat_keluar_save';
if (isset($_GET['id'])) {
    $id = (int)$_GET['id']; $data = get_surat_keluar_by_id($conn, $id);
    if ($data) { $is_edit = true; $form_url .= '?id=' . $id; }
    else { $_SESSION['error_message'] = "Data tidak ditemukan."; header('Location: ' . BASE_URL . 'surat_keluar'); exit(); }
}
?>

<div class="e-page-header">
  <div>
    <h1 class="e-page-title">
      <i class="bi bi-send-fill" style="color:var(--green);"></i>
      <?php echo $is_edit ? 'Edit Surat Keluar' : 'Tambah Surat Keluar'; ?>
    </h1>
    <p class="e-page-sub"><?php echo $is_edit ? 'Perbarui data surat keluar.' : 'Formulir pencatatan surat keluar baru.'; ?></p>
  </div>
  <a href="<?php echo BASE_URL; ?>surat_keluar" class="e-btn e-btn-ghost">
    <i class="bi bi-arrow-left"></i> Kembali
  </a>
</div>

<?php if (!empty($_SESSION['error_message'])): ?>
<div class="e-notice danger"><i class="bi bi-exclamation-triangle-fill"></i><span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span></div>
<?php endif; ?>

<div class="e-card">
  <div class="e-card-header">
    <div class="e-card-title"><i class="bi bi-file-text" style="color:var(--green);"></i> Informasi Surat Keluar</div>
  </div>
  <div class="e-card-body">
    <form action="<?php echo $form_url; ?>" method="POST" enctype="multipart/form-data">
      <?php if ($is_edit): ?>
        <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
      <?php endif; ?>

      <div class="e-form-section"><i class="bi bi-info-circle"></i> Data Surat Keluar</div>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="e-form-group">
            <label class="e-label" for="nomor_surat">Nomor Surat <span class="req">*</span></label>
            <input class="e-input" type="text" id="nomor_surat" name="nomor_surat"
              value="<?php echo htmlspecialchars($data['nomor_surat'] ?? ''); ?>"
              placeholder="Contoh: 080/INSTANSI/VII/2024" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="e-form-group">
            <label class="e-label" for="tujuan">Tujuan Surat <span class="req">*</span></label>
            <input class="e-input" type="text" id="tujuan" name="tujuan"
              value="<?php echo htmlspecialchars($data['tujuan'] ?? ''); ?>"
              placeholder="Kepada Yth. / instansi tujuan" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="e-form-group">
            <label class="e-label" for="tanggal_surat">Tanggal Surat <span class="req">*</span></label>
            <input class="e-input" type="date" id="tanggal_surat" name="tanggal_surat"
              value="<?php echo htmlspecialchars($data['tanggal_surat'] ?? ''); ?>" required>
          </div>
        </div>
        <div class="col-12">
          <div class="e-form-group">
            <label class="e-label" for="perihal">Perihal <span class="req">*</span></label>
            <textarea class="e-textarea" id="perihal" name="perihal" rows="3"
              placeholder="Perihal surat keluar..." required><?php echo htmlspecialchars($data['perihal'] ?? ''); ?></textarea>
          </div>
        </div>
      </div>

      <div class="e-form-section" style="margin-top:1.25rem;"><i class="bi bi-paperclip"></i> Lampiran</div>

      <div class="col-md-7">
        <div class="e-form-group">
          <label class="e-label" for="file_surat">Berkas Surat (PDF / JPG / PNG)</label>
          <input class="e-file-input" type="file" id="file_surat" name="file_surat">
          <?php if (!empty($data['file_surat'])): ?>
          <div style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem;font-size:.78rem;color:var(--text-muted);">
            <i class="bi bi-file-earmark-check" style="color:var(--green);"></i>
            <a href="<?php echo BASE_URL . $data['file_surat']; ?>" target="_blank" style="color:var(--blue);font-weight:600;">Lihat File</a>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="e-divider"></div>
      <div style="display:flex;gap:.625rem;">
        <button type="submit" class="e-btn e-btn-primary">
          <i class="bi bi-check-lg"></i> <?php echo $is_edit ? 'Simpan Perubahan' : 'Simpan Data'; ?>
        </button>
        <a href="<?php echo BASE_URL; ?>surat_keluar" class="e-btn e-btn-ghost">Batal</a>
      </div>
    </form>
  </div>
</div>
