<?php require_once __DIR__ . '/../config/database.php'; ?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
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

<div class="e-login-split-wrapper">

  <!-- Left Side: Hero Banner -->
  <div class="e-login-hero-side">
    <div class="e-hero-content">
      <h1 class="e-hero-title">
        Empowering<br>
        Educational<br>
        Excellence
      </h1>
      <p class="e-hero-desc">
        Access the centralized administrative hub for STIE Nasional Banjarmasin. Secure, efficient, and streamlined personnel management.
      </p>
    </div>
  </div>

  <!-- Right Side: Form Area -->
  <div class="e-login-form-side">

    <!-- Top Theme Toggle Button -->
    <div class="e-login-theme-toggle">
      <button id="loginThemeBtn" type="button" class="e-theme-btn-login" title="Ganti Mode Tampilan">
        <i class="bi bi-moon-fill" id="loginThemeIco"></i>
      </button>
    </div>

    <!-- Centered Form Card -->
    <div class="e-login-card-container">
      <div class="e-login-card">

        <!-- Card Header -->
        <div class="e-card-header">
          <!-- Logo Lockup: icon kiri, nama+sub kanan -->
          <div class="e-card-brand">
            <div class="e-card-icon-badge">
              <i class="bi bi-person-vcard-fill"></i>
            </div>
            <div class="e-card-brand-text">
              <div class="e-card-app-name">SIMPEG</div>
              <p class="e-card-app-sub">Sistem Kepegawaian &amp; Absensi Digital</p>
            </div>
          </div>
          <div class="e-card-divider"></div>
          <h2 class="e-card-title">Welcome Back</h2>
          <p class="e-card-sub">Masuk ke akun administratif Anda.</p>
        </div>

        <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="e-login-error">
          <i class="bi bi-exclamation-circle-fill"></i>
          <span><?php echo htmlspecialchars($_SESSION['error_message']); ?></span>
        </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>login_process" method="POST" autocomplete="off" id="loginForm">
          <?php echo csrf_field(); ?>

          <div class="e-form-group">
            <div class="e-input-wrapper">
              <input class="e-input-field" type="text" name="username" id="username"
                     placeholder="Username or Email" required autocomplete="username">
            </div>
          </div>

          <div class="e-form-group">
            <div class="e-input-wrapper">
              <input class="e-input-field" type="password" name="password" id="password"
                     placeholder="Password" required autocomplete="current-password">
              <button type="button" id="togglePwd" class="e-pwd-toggle-btn" title="Lihat Password">
                <i class="bi bi-eye" id="eyeIcon"></i>
              </button>
            </div>
          </div>

          <div class="e-form-options">
            <label class="e-remember-check">
              <input type="checkbox" name="remember" id="remember">
              <span>Remember Me</span>
            </label>
            <a href="#" class="e-link-accent" onclick="Swal.fire({title:'Lupa Password?', text:'Silakan hubungi tim IT Support untuk mereset password akun Anda.', icon:'info', confirmButtonColor:'#0f172a'}); return false;">Forgot Password?</a>
          </div>

          <button class="e-login-btn" type="submit" id="loginBtn">
            <span>Sign In</span>
            <i class="bi bi-arrow-right"></i>
          </button>
        </form>

        <div class="e-card-support-footer">
          Having trouble? <a href="#" class="e-link-accent" onclick="Swal.fire({title:'Bantuan IT Support', text:'Email: info@stienas-ypb.ac.id | WhatsApp: +62 812-8700-0187', icon:'question', confirmButtonColor:'#0f172a'}); return false;">Contact IT Support</a>
        </div>

      </div>
    </div>

    <!-- Outside Page Footer -->
    <div class="e-login-footer-outside">
      <p class="e-footer-copy">&copy; <?php echo date('Y'); ?> STIE Nasional Banjarmasin. All Rights Reserved.</p>
      <div class="e-footer-links">
        <a href="#" onclick="return false;">Privacy Policy</a>
        <a href="#" onclick="return false;">Terms of Service</a>
        <a href="#" onclick="return false;">Help Desk</a>
      </div>
    </div>

  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  // Theme Toggle Logic
  const htmlEl          = document.documentElement;
  const loginThemeBtn   = document.getElementById('loginThemeBtn');
  const loginThemeIco   = document.getElementById('loginThemeIco');

  function applyLoginTheme(theme) {
    htmlEl.setAttribute('data-bs-theme', theme);
    localStorage.setItem('earsip-theme', theme);
    if (loginThemeIco) {
      if (theme === 'dark') {
        loginThemeIco.className = 'bi bi-sun-fill';
        loginThemeBtn.setAttribute('title', 'Ganti ke Light Mode');
      } else {
        loginThemeIco.className = 'bi bi-moon-fill';
        loginThemeBtn.setAttribute('title', 'Ganti ke Dark Mode');
      }
    }
  }

  // Initial Sync
  const currentTheme = htmlEl.getAttribute('data-bs-theme') || 'light';
  applyLoginTheme(currentTheme);

  if (loginThemeBtn) {
    loginThemeBtn.addEventListener('click', function() {
      const nextTheme = htmlEl.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      applyLoginTheme(nextTheme);
    });
  }

  // Password Visibility Toggle
  const toggleBtn = document.getElementById('togglePwd');
  const pwdInput  = document.getElementById('password');
  const eyeIcon   = document.getElementById('eyeIcon');
  if (toggleBtn && pwdInput && eyeIcon) {
    toggleBtn.addEventListener('click', function() {
      const isHidden = pwdInput.type === 'password';
      pwdInput.type = isHidden ? 'text' : 'password';
      eyeIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  }

  // Form Submit Loading State
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
          confirmButtonColor: '#0f172a'
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
    title: '<span style="font-weight:700;">Gagal Masuk</span>',
    html: <?php echo json_encode($_SESSION['error_message']); ?>,
    icon: 'error',
    confirmButtonText: 'Coba Lagi',
    confirmButtonColor: '#0f172a'
  });
});
</script>
<?php unset($_SESSION['error_message']); endif; ?>

</body>
</html>
