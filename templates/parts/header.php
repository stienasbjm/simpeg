<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
$user_info   = get_user_info();
$current_path = trim(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'), '/');
// Ambil segment terakhir path sebagai page name
$current = basename($current_path) ?: 'dashboard';
// Fallback ke ?page= jika ada
if (!empty($_GET['page'])) $current = $_GET['page'];
$initial     = !empty($user_info['nama_lengkap']) ? strtoupper(substr($user_info['nama_lengkap'], 0, 1)) : 'P';
$uname       = htmlspecialchars($user_info['nama_lengkap'] ?? 'Admin');
$urole       = htmlspecialchars($user_info['username'] ?? 'admin');
$user_type   = $user_info['role'] ?? 'admin';
$is_dev_user = ($user_type === 'developer');
$is_bendahara_user = ($user_type === 'bendahara');

// Matchers
$arsip_matches = ['surat_masuk','surat_masuk_add','surat_masuk_edit','surat_masuk_detail',
                  'surat_keluar','surat_keluar_add','surat_keluar_edit','surat_keluar_detail',
                  'sk','sk_add','sk_edit','sk_detail'];
$is_arsip_active = in_array($current, $arsip_matches);

$keuangan_matches = ['kas','gaji','slip_gaji_print'];
$is_keuangan_active = in_array($current, $keuangan_matches);
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIMPEG System</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
  <style>
    /* Submenu Accordion Styles */
    .e-nav-dropdown { position: relative; list-style: none; margin: 0; padding: 0; }
    .e-nav-toggle { display: flex; align-items: center; justify-content: space-between; cursor: pointer; }
    .e-nav-toggle .arrow { transition: transform 0.2s ease; font-size: 0.75rem; }
    .e-nav-dropdown.is-open .e-nav-toggle .arrow { transform: rotate(90deg); }
    .e-submenu { display: none; padding-left: 1.75rem; margin-top: 0.25rem; list-style: none; }
    .e-nav-dropdown.is-open .e-submenu { display: block; }
    .e-submenu-link {
      display: flex; align-items: center; gap: 0.5rem;
      padding: 0.4rem 0.75rem; border-radius: var(--r-md);
      color: var(--sidebar-text); text-decoration: none; font-size: 0.82rem; font-weight: 500;
      transition: all var(--dur) var(--ease);
    }
    .e-submenu-link:hover { color: var(--sidebar-text-hover); background: var(--sidebar-hover-bg); }
    .e-submenu-link.is-active { color: var(--sidebar-active); font-weight: 700; background: var(--sidebar-active-bg); }
  </style>
</head>
<body>

<!-- ═══════════ TOPBAR ═══════════ -->
<header class="e-topbar">
  <button class="e-menu-btn" id="menuBtn" type="button">
    <i class="bi bi-list"></i>
  </button>

  <a class="e-brand" href="<?php echo BASE_URL; ?>dashboard">
    <div class="e-brand-icon"><i class="bi bi-person-vcard-fill"></i></div>
    <div>
      <div class="e-brand-name">SIMPEG</div>
      <div class="e-brand-tag">Sistem Kepegawaian & Keuangan</div>
    </div>
  </a>

  <!-- Search bar -->
  <div class="e-topbar-search d-none d-md-flex">
    <i class="bi bi-search" style="font-size:.78rem;"></i>
    <span>Cari data pegawai, kas & arsip&hellip;</span>
    <span style="margin-left:auto;font-size:.68rem;opacity:.6;background:var(--bg-card);border:1px solid var(--border-medium);border-radius:4px;padding:.1rem .35rem;">⌘K</span>
  </div>

  <div class="e-topbar-right">
    <button class="e-theme-btn" id="themeBtn" type="button" title="Toggle dark mode">
      <i class="bi bi-moon" id="themeIco"></i>
    </button>

    <div class="e-user-chip">
      <div class="e-avatar" style="<?php echo $is_dev_user ? 'background:linear-gradient(135deg,#8b5cf6,#c084fc);' : ($is_bendahara_user ? 'background:linear-gradient(135deg,#10b981,#059669);' : ''); ?>">
        <?php echo $initial; ?>
      </div>
      <div>
        <div class="e-user-name"><?php echo $uname; ?></div>
        <div class="e-user-role">
          <?php echo $is_dev_user ? '⚙️ @' . $urole : ($is_bendahara_user ? '💰 Bendahara' : '@' . $urole); ?>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- ═══════════ OVERLAY ═══════════ -->
<div class="e-overlay" id="overlay"></div>

<!-- ═══════════ SIDEBAR ═══════════ -->
<nav class="e-sidebar" id="sidebar">
  <div class="e-sidebar-body">

    <!-- User card -->
    <div class="e-sidebar-user">
      <div class="e-avatar-lg" style="<?php echo $is_dev_user ? 'background:linear-gradient(135deg,#8b5cf6,#c084fc);' : ($is_bendahara_user ? 'background:linear-gradient(135deg,#10b981,#059669);' : ''); ?>">
        <?php echo $initial; ?>
      </div>
      <div style="min-width:0">
        <div class="e-sidebar-user-name"><?php echo $uname; ?></div>
        <div class="e-sidebar-user-role">
          <?php if ($is_dev_user): ?>
            <i class="bi bi-code-slash" style="color:#a78bfa;font-size:.7rem;"></i>
            <span style="color:#c084fc;font-weight:700;">Developer</span>
          <?php elseif ($is_bendahara_user): ?>
            <i class="bi bi-wallet-fill" style="color:#10b981;font-size:.7rem;"></i>
            <span style="color:#10b981;font-weight:700;">Bendahara Keuangan</span>
          <?php else: ?>
            <i class="bi bi-patch-check-fill" style="color:#818cf8;font-size:.6rem;"></i>
            Administrator
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Menu Utama -->
    <div class="e-nav-label">Menu Utama</div>
    <ul class="e-nav">
      
      <!-- Dashboard -->
      <li>
        <a class="e-nav-link <?php echo $current==='dashboard' ? 'is-active' : ''; ?>" href="<?php echo BASE_URL; ?>dashboard">
          <div class="nav-icon"><i class="bi bi-grid-1x2-fill"></i></div>
          <span>Dashboard</span>
        </a>
      </li>

      <?php if ($is_dev_user || $user_type === 'admin'): ?>
      <!-- Data Pegawai -->
      <li>
        <a class="e-nav-link <?php echo in_array($current, ['pegawai','pegawai_add','pegawai_edit','pegawai_detail']) ? 'is-active' : ''; ?>" href="<?php echo BASE_URL; ?>pegawai">
          <div class="nav-icon"><i class="bi bi-people-fill"></i></div>
          <span>Data Pegawai</span>
        </a>
      </li>
      <?php endif; ?>

      <!-- Absensi Pegawai -->
      <li>
        <a class="e-nav-link <?php echo in_array($current, ['absensi','absensi_detail']) ? 'is-active' : ''; ?>" href="<?php echo BASE_URL; ?>absensi">
          <div class="nav-icon"><i class="bi bi-calendar2-check-fill"></i></div>
          <span>Absensi Pegawai</span>
        </a>
      </li>

      <!-- ARSIP (Submenu Collapsible) -->
      <?php if ($is_dev_user || $user_type === 'admin'): ?>
      <li class="e-nav-dropdown <?php echo $is_arsip_active ? 'is-open' : ''; ?>">
        <a class="e-nav-link e-nav-toggle <?php echo $is_arsip_active ? 'is-active' : ''; ?>" onclick="toggleNavDropdown(this)">
          <div style="display:flex;align-items:center;gap:.625rem;">
            <div class="nav-icon"><i class="bi bi-archive-fill"></i></div>
            <span>Arsip</span>
          </div>
          <i class="bi bi-chevron-right arrow"></i>
        </a>
        <ul class="e-submenu">
          <li>
            <a class="e-submenu-link <?php echo in_array($current, ['surat_masuk','surat_masuk_add','surat_masuk_edit','surat_masuk_detail']) ? 'is-active' : ''; ?>" href="<?php echo BASE_URL; ?>surat_masuk">
              <i class="bi bi-inbox-fill"></i> Surat Masuk
            </a>
          </li>
          <li>
            <a class="e-submenu-link <?php echo in_array($current, ['surat_keluar','surat_keluar_add','surat_keluar_edit','surat_keluar_detail']) ? 'is-active' : ''; ?>" href="<?php echo BASE_URL; ?>surat_keluar">
              <i class="bi bi-send-fill"></i> Surat Keluar
            </a>
          </li>
          <li>
            <a class="e-submenu-link <?php echo in_array($current, ['sk','sk_add','sk_edit','sk_detail']) ? 'is-active' : ''; ?>" href="<?php echo BASE_URL; ?>sk">
              <i class="bi bi-file-earmark-text-fill"></i> Surat Keputusan (SK)
            </a>
          </li>
        </ul>
      </li>
      <?php endif; ?>

      <!-- KEUANGAN (Submenu Collapsible) -->
      <?php if ($is_dev_user || $is_bendahara_user): ?>
      <li class="e-nav-dropdown <?php echo $is_keuangan_active ? 'is-open' : ''; ?>">
        <a class="e-nav-link e-nav-toggle <?php echo $is_keuangan_active ? 'is-active' : ''; ?>" onclick="toggleNavDropdown(this)">
          <div style="display:flex;align-items:center;gap:.625rem;">
            <div class="nav-icon"><i class="bi bi-cash-coin"></i></div>
            <span>Keuangan</span>
          </div>
          <i class="bi bi-chevron-right arrow"></i>
        </a>
        <ul class="e-submenu">
          <li>
            <a class="e-submenu-link <?php echo $current==='kas' ? 'is-active' : ''; ?>" href="<?php echo BASE_URL; ?>kas">
              <i class="bi bi-wallet2"></i> Arus Kas (Kecil & Besar)
            </a>
          </li>
          <li>
            <a class="e-submenu-link <?php echo in_array($current, ['gaji','slip_gaji_print']) ? 'is-active' : ''; ?>" href="<?php echo BASE_URL; ?>gaji">
              <i class="bi bi-cash-stack"></i> Penggajian & Slip Gaji
            </a>
          </li>
        </ul>
      </li>
      <?php endif; ?>

      <?php if ($is_dev_user || $user_type === 'admin'): ?>
      <!-- Akun Pegawai -->
      <li>
        <a class="e-nav-link <?php echo in_array($current, ['akun_pegawai','akun_pegawai_add']) ? 'is-active' : ''; ?>" href="<?php echo BASE_URL; ?>akun_pegawai">
          <div class="nav-icon"><i class="bi bi-person-lock"></i></div>
          <span>Akun Pegawai</span>
        </a>
      </li>
      <?php endif; ?>

      <!-- Hanya Developer yang melihat menu Akun Admin & Dev -->
      <?php if ($is_dev_user): ?>
      <li>
        <a class="e-nav-link <?php echo $current==='akun_admin' ? 'is-active' : ''; ?>" href="<?php echo BASE_URL; ?>akun_admin">
          <div class="nav-icon"><i class="bi bi-shield-lock-fill"></i></div>
          <span>Akun Admin & Dev</span>
        </a>
      </li>
      <?php endif; ?>

    </ul>

    <!-- Sistem -->
    <div class="e-nav-label" style="margin-top:1.25rem;">Sistem</div>
    <ul class="e-nav">
      <li>
        <a class="e-nav-link is-logout" href="<?php echo BASE_URL; ?>logout">
          <div class="nav-icon"><i class="bi bi-box-arrow-right"></i></div>
          <span>Logout</span>
        </a>
      </li>
    </ul>

  </div>
</nav>

<script>
function toggleNavDropdown(el) {
  const parent = el.closest('.e-nav-dropdown');
  if (parent) {
    parent.classList.toggle('is-open');
  }
}
</script>

<!-- ═══════════ MAIN CONTENT ═══════════ -->
<main class="e-main">