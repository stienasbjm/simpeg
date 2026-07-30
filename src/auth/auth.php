if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_start();
}

// ─── CSRF Token Helpers ──────────────────────────────────────────────────────
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = get_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function is_logged_in() {
    if (isset($_SESSION['user_id'])) return true;
    if (defined('DISABLE_AUTH') && DISABLE_AUTH) return true;
    return false;
}

function is_admin() {
    if (isset($_SESSION['user_id'])) {
        return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'developer');
    }
    if (defined('DISABLE_AUTH') && DISABLE_AUTH) return true;
    return false;
}

function is_bendahara() {
    if (isset($_SESSION['user_id'])) {
        return isset($_SESSION['role']) && ($_SESSION['role'] === 'bendahara' || $_SESSION['role'] === 'developer');
    }
    if (defined('DISABLE_AUTH') && DISABLE_AUTH) return true;
    return false;
}

function is_developer() {
    if (isset($_SESSION['user_id'])) {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'developer';
    }
    if (defined('DISABLE_AUTH') && DISABLE_AUTH) return true;
    return false;
}

function is_pegawai() {
    if (isset($_SESSION['user_id'])) {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'pegawai';
    }
    if (defined('DISABLE_AUTH') && DISABLE_AUTH) return false;
    return false;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . 'login');
        exit();
    }
}

function require_admin() {
    if (!is_logged_in() || (!is_admin() && !is_bendahara())) {
        header('Location: ' . BASE_URL . 'login');
        exit();
    }
}

function require_bendahara() {
    if (!is_logged_in() || !is_bendahara()) {
        $_SESSION['error_message'] = "Akses ditolak. Halaman ini hanya untuk Akun Bendahara atau Administrator.";
        header('Location: ' . BASE_URL . 'dashboard');
        exit();
    }
}

function require_developer() {
    if (!is_logged_in() || !is_developer()) {
        $_SESSION['error_message'] = "Akses ditolak. Halaman ini hanya untuk Akun Developer.";
        header('Location: ' . BASE_URL . 'dashboard');
        exit();
    }
}

function require_pegawai_role() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . 'login');
        exit();
    }
}

function get_user_info() {
    if (isset($_SESSION['user_id'])) {
        return [
            'id'          => $_SESSION['user_id'],
            'username'    => $_SESSION['username'],
            'nama_lengkap'=> $_SESSION['nama_lengkap'],
            'role'        => $_SESSION['role'] ?? 'admin',
            'pegawai_id'  => $_SESSION['pegawai_id'] ?? null,
        ];
    }
    if (defined('DISABLE_AUTH') && DISABLE_AUTH) {
        return ['id' => 0, 'username' => 'dev', 'nama_lengkap' => 'Developer', 'role' => 'developer', 'pegawai_id' => null];
    }
    return null;
}

function get_session_pegawai_id() {
    return $_SESSION['pegawai_id'] ?? null;
}