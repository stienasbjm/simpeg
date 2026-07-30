<?php require_once __DIR__ . '/../config/database.php'; ?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — SIMPEG System</title>
  <meta name="description" content="Masuk ke Sistem Manajemen Kepegawaian & Absensi Digital">
  <script>
    (function() {
      const savedTheme = localStorage.getItem('earsip-theme') ||
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      document.documentElement.setAttribute('data-bs-theme', savedTheme);
    })();
  </script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>
<body>

<div class="e-login-wrap">

  <!-- Floating Theme Toggle Button -->
  <div class="e-login-theme-toggle">
    <button id="loginThemeBtn" type="button" class="e-theme-btn-login">
      <i class="bi bi-moon-stars-fill" id="loginThemeIco"></i>
      <span id="loginThemeText">Dark Mode</span>
    </button>
  </div>

  <!-- Animated Grid Background -->
  <div class="e-login-grid"></div>

  <!-- Animated Moving Gradient Orbs (Animasi Bergerak) -->
  <div class="e-animated-orb orb-1"></div>
  <div class="e-animated-orb orb-2"></div>
  <div class="e-animated-orb orb-3"></div>

  <div class="e-login-box">

    <!-- Header -->
    <div class="e-login-header">
      <div class="e-login-logo">
        <i class="bi bi-person-vcard-fill"></i>
      </div>
      <h1 class="e-login-title">SIMPEG</h1>
      <p class="e-login-sub">Sistem Kepegawaian & Absensi Digital</p>
    </div>

    <?php if (!empty($_SESSION['error_message'])): ?>
    <div class="e-login-error">
      <i class="bi bi-exclamation-circle-fill"></i>
      <span><?php echo htmlspecialchars($_SESSION['error_message']); ?></span>
    </div>
    <?php endif; ?>

    <form action="<?php echo BASE_URL; ?>login_process" method="POST" autocomplete="off" id="loginForm">
      <?php echo csrf_field(); ?>
      <div>
        <label class="e-login-label" for="username">Username / NIP</label>
        <div class="e-login-field">
          <i class="bi bi-person e-login-icon"></i>
          <input class="e-login-input" type="text" name="username" id="username"
                 placeholder="Masukkan username atau NIP" required autocomplete="username">
        </div>
      </div>
      <div>
        <label class="e-login-label" for="password">Password</label>
        <div class="e-login-field">
          <i class="bi bi-lock e-login-icon"></i>
          <input class="e-login-input" type="password" name="password" id="password"
                 placeholder="Masukkan password" required autocomplete="current-password">
          <button type="button" id="togglePwd"
                  style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:.875rem;padding:0;transition:color .2s ease;">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <button class="e-login-btn" type="submit" id="loginBtn">
        <span>Masuk ke Sistem</span>
        <i class="bi bi-arrow-right-circle-fill btn-arrow-icon"></i>
      </button>
    </form>

    <p class="e-login-foot">&copy; <?php echo date('Y'); ?> SIMPEG System &middot; Hak Cipta Dilindungi</p>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  // Sync Theme State & Toggle
  const htmlEl          = document.documentElement;
  const loginThemeBtn   = document.getElementById('loginThemeBtn');
  const loginThemeIco   = document.getElementById('loginThemeIco');
  const loginThemeText  = document.getElementById('loginThemeText');

  function applyLoginTheme(theme) {
    htmlEl.setAttribute('data-bs-theme', theme);
    localStorage.setItem('earsip-theme', theme);
    if (loginThemeIco && loginThemeText) {
      if (theme === 'dark') {
        loginThemeIco.className = 'bi bi-moon-stars-fill';
        loginThemeText.textContent = 'Dark Mode';
      } else {
        loginThemeIco.className = 'bi bi-sun-fill';
        loginThemeText.textContent = 'Light Mode';
      }
    }
  }

  // Initial Sync
  const currentTheme = htmlEl.getAttribute('data-bs-theme') || 'dark';
  applyLoginTheme(currentTheme);

  if (loginThemeBtn) {
    loginThemeBtn.addEventListener('click', function() {
      const nextTheme = htmlEl.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      applyLoginTheme(nextTheme);
    });
  }

  // Password toggle
  const toggleBtn = document.getElementById('togglePwd');
  const pwdInput  = document.getElementById('password');
  const eyeIcon   = document.getElementById('eyeIcon');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function() {
      const isHidden = pwdInput.type === 'password';
      pwdInput.type = isHidden ? 'text' : 'password';
      eyeIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  }

  // Form submit & loading
  const loginForm = document.getElementById('loginForm');
  const loginBtn  = document.getElementById('loginBtn');
  if (loginForm && loginBtn) {
    loginForm.addEventListener('submit', function(e) {
      const u = document.getElementById('username').value.trim();
      const p = document.getElementById('password').value.trim();
      if (!u || !p) {
        e.preventDefault();
        Swal.fire({
          title: 'Form Belum Lengkap',
          text: 'Harap isi Username/NIP dan Password terlebih dahulu.',
          icon: 'warning',
          confirmButtonText: 'OK',
          confirmButtonColor: '#6366f1',
          customClass: { popup: 'e-swal-popup' }
        });
        return;
      }
      loginBtn.innerHTML = '<span>Memproses...</span> <span class="spinner-border spinner-border-sm ms-1" style="width:.9rem;height:.9rem;border-width:.15em;"></span>';
      loginBtn.disabled = true;
    });
  }
</script>

<?php if (!empty($_SESSION['error_message'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  Swal.fire({
    title: '<span style="font-weight:800;">Gagal Masuk</span>',
    html: <?php echo json_encode($_SESSION['error_message']); ?>,
    icon: 'error',
    confirmButtonText: 'Coba Lagi',
    confirmButtonColor: '#6366f1',
    customClass: { popup: 'e-swal-popup' }
  });
});
</script>
<?php unset($_SESSION['error_message']); endif; ?>

</body>
</html>
