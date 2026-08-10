<?php
$user_info = get_user_info();
$uname     = htmlspecialchars($user_info['nama_lengkap'] ?? 'Pegawai');
$initial   = strtoupper(substr($uname, 0, 1));
$current   = $page ?? 'absensi';
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Pegawai — STIE Nasional Banjarmasin</title>
  <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>public/images/favicon.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
  <style>
    /* Portal-specific styles */
    :root { --portal-accent: #6366f1; }
    .portal-topbar {
      position: fixed; top:0; left:0; right:0;
      height: 60px;
      background: rgba(255,255,255,.9);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border-light);
      display: flex; align-items: center;
      padding: 0 1.5rem; gap: .875rem; z-index: 100;
      box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
    [data-bs-theme="dark"] .portal-topbar {
      background: rgba(10,15,30,.92);
    }
    .portal-body { padding-top: 60px; min-height: 100vh; background: var(--bg-page); }
    .portal-nav {
      display: flex; align-items: center; gap: .25rem; margin-left: auto;
    }
    .portal-nav-link {
      display: flex; align-items: center; gap: .4rem;
      padding: .45rem .875rem;
      border-radius: var(--r-full);
      color: var(--text-muted);
      text-decoration: none;
      font-size: .82rem; font-weight: 600;
      transition: all var(--dur) var(--ease);
    }
    .portal-nav-link:hover { background: var(--bg-hover); color: var(--text-primary); }
    .portal-nav-link.active {
      background: rgba(99,102,241,.1);
      color: var(--indigo);
    }
    .portal-content { max-width: 820px; margin: 0 auto; padding: 2rem 1.25rem 4rem; }
    @media (max-width: 576px) { .portal-content { padding: 1.25rem .875rem 3rem; } }
  </style>
</head>
<body>

<!-- Topbar Portal -->
<div class="portal-topbar">
  <img src="<?php echo BASE_URL; ?>public/images/logo.png" alt="Logo STIENAS" style="height:34px;width:auto;object-fit:contain;filter:drop-shadow(0 2px 4px rgba(0,0,0,.1));">
  <div>
    <div class="e-brand-name" style="font-size:.85rem;font-weight:900;">Portal Pegawai</div>
    <div class="e-brand-tag" style="font-size:.68rem;opacity:.8;">STIE Nasional Banjarmasin</div>
  </div>

  <nav class="portal-nav">
    <a href="<?php echo BASE_URL; ?>portal"
       class="portal-nav-link <?php echo $current==='absensi'?'active':''; ?>">
      <i class="bi bi-calendar2-check"></i> <span class="d-none d-sm-inline">Absensi</span>
    </a>
    <a href="<?php echo BASE_URL; ?>profil"
       class="portal-nav-link <?php echo $current==='profil'?'active':''; ?>">
      <i class="bi bi-person-circle"></i> <span class="d-none d-sm-inline">Profil</span>
    </a>
    <button class="e-theme-btn" id="themeBtn" type="button" title="Toggle dark mode" style="margin-left:.25rem;">
      <i class="bi bi-moon" id="themeIco"></i>
    </button>
    <a href="<?php echo BASE_URL; ?>logout"
       class="portal-nav-link" style="color:var(--red);" title="Logout">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </nav>
</div>

<div class="portal-body">
  <div class="portal-content">
    <?php include $content_page; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo BASE_URL; ?>public/js/main.js"></script>

<?php if (!empty($_SESSION['success_message'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  Swal.fire({
    title: 'Berhasil!',
    text: <?php echo json_encode($_SESSION['success_message']); ?>,
    icon: 'success',
    confirmButtonText: 'OK',
    confirmButtonColor: '#10b981',
    timer: 4000,
    timerProgressBar: true,
    customClass: { popup: 'e-swal-popup' }
  });
});
</script>
<?php unset($_SESSION['success_message']); endif; ?>

<?php if (!empty($_SESSION['error_message'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  Swal.fire({
    title: 'Perhatian / Gagal!',
    text: <?php echo json_encode($_SESSION['error_message']); ?>,
    icon: 'error',
    confirmButtonText: 'Tutup',
    confirmButtonColor: '#ef4444',
    customClass: { popup: 'e-swal-popup' }
  });
});
</script>
<?php unset($_SESSION['error_message']); endif; ?>

</body>
</html>
