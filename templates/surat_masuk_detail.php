<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/surat_masuk_functions.php';
require_login();

$d = null;
if (isset($_GET['id'])) $d = get_surat_masuk_by_id($conn, (int)$_GET['id']);
if (!$d) { $_SESSION['error_message'] = "Data tidak ditemukan."; header('Location: ' . BASE_URL . 'surat_masuk'); exit(); }
?>

<div class="e-page-header">
  <div>
    <h1 class="e-page-title"><i class="bi bi-inbox-fill" style="color:var(--blue);"></i> Detail Surat Masuk</h1>
    <p class="e-page-sub">Informasi lengkap dokumen surat masuk.</p>
  </div>
  <div style="display:flex;gap:.5rem;">
    <a href="<?php echo BASE_URL; ?>surat_masuk_edit?id=<?php echo $d['id']; ?>" class="e-btn e-btn-primary">
      <i class="bi bi-pencil-fill"></i> Edit
    </a>
    <a href="<?php echo BASE_URL; ?>surat_masuk" class="e-btn e-btn-ghost">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="e-card">
      <div class="e-card-header">
        <div class="e-card-title"><i class="bi bi-file-text"></i> Rincian Surat</div>
        <span class="e-badge-mono"><?php echo htmlspecialchars($d['nomor_surat']); ?></span>
      </div>
      <div class="e-card-body">
        <div class="e-dl">
          <div class="e-dl-row">
            <div class="e-dt">Nomor Surat</div>
            <div class="e-dd"><span class="e-badge-mono"><?php echo htmlspecialchars($d['nomor_surat']); ?></span></div>
          </div>
          <div class="e-dl-row">
            <div class="e-dt">Pengirim</div>
            <div class="e-dd" style="font-weight:600;"><?php echo htmlspecialchars($d['pengirim']); ?></div>
          </div>
          <div class="e-dl-row">
            <div class="e-dt">Perihal</div>
            <div class="e-dd"><?php echo htmlspecialchars($d['perihal']); ?></div>
          </div>
          <div class="e-dl-row">
            <div class="e-dt">Tanggal Surat</div>
            <div class="e-dd"><i class="bi bi-calendar3" style="opacity:.6;font-size:.8rem;"></i> <?php echo date('d F Y', strtotime($d['tanggal_surat'])); ?></div>
          </div>
          <div class="e-dl-row">
            <div class="e-dt">Tanggal Diterima</div>
            <div class="e-dd"><i class="bi bi-calendar-check" style="opacity:.6;font-size:.8rem;"></i> <?php echo date('d F Y', strtotime($d['tanggal_diterima'])); ?></div>
          </div>
          <?php if (!empty($d['created_at'])): ?>
          <div class="e-dl-row">
            <div class="e-dt">Dicatat Pada</div>
            <div class="e-dd" style="color:var(--text-muted);font-size:.8rem;"><?php echo date('d F Y, H:i', strtotime($d['created_at'])); ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="e-card">
      <div class="e-card-header">
        <div class="e-card-title"><i class="bi bi-paperclip"></i> File Lampiran</div>
      </div>
      <div class="e-card-body">
        <?php if (!empty($d['file_surat'])): ?>
        <div class="e-file-card" style="flex-direction:column;text-align:center;gap:.875rem;padding:1.5rem 1rem;">
          <i class="bi bi-file-earmark-pdf-fill" style="font-size:2.25rem;color:var(--red);"></i>
          <div>
            <div class="e-file-name">Berkas Surat Tersedia</div>
            <div class="e-file-hint" style="margin-top:.2rem;">Klik untuk membuka atau unduh</div>
          </div>
          <a href="<?php echo BASE_URL . $d['file_surat']; ?>" target="_blank" class="e-btn e-btn-primary" style="width:100%;justify-content:center;">
            <i class="bi bi-box-arrow-up-right"></i> Buka File
          </a>
        </div>
        <?php else: ?>
        <div class="e-empty" style="padding:2rem 1rem;">
          <i class="bi bi-file-earmark-x e-empty-icon"></i>
          <p>Belum ada file yang diunggah.</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
