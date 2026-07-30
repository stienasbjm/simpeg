<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ─── CSRF Token Verification ─────────────────────────────────────────────
    if (!verify_csrf_token()) {
        $_SESSION['error_message'] = "Token keamanan (CSRF) tidak valid. Silakan coba lagi.";
        header('Location: ' . BASE_URL . 'login');
        exit();
    }
    // ─── Brute-Force Protection Rate Limiting ────────────────────────────────
    $now = time();
    if (isset($_SESSION['login_lockout_time']) && $_SESSION['login_lockout_time'] > $now) {
        $remaining = ceil(($_SESSION['login_lockout_time'] - $now) / 60);
        $_SESSION['error_message'] = "Terlalu banyak percobaan gagal. Silakan coba lagi dalam $remaining menit.";
        header('Location: ' . BASE_URL . 'login');
        exit();
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['error_message'] = "Username/NIP dan password harus diisi.";
        header('Location: ' . BASE_URL . 'login');
        exit();
    }

    // Query pencarian berdasarkan Username ATAU NIP Pegawai
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.password, 
               COALESCE(p.nama, u.nama_lengkap) AS nama_lengkap, 
               u.role, u.pegawai_id 
        FROM users u 
        LEFT JOIN pegawai p ON u.pegawai_id = p.id 
        WHERE u.username = ? OR (p.nip = ? AND p.nip IS NOT NULL AND p.nip != '')
        LIMIT 1
    ");
    if (!$stmt) {
        $_SESSION['error_message'] = "Kesalahan server saat memproses login.";
        header('Location: ' . BASE_URL . 'login');
        exit();
    }

    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows >= 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Regenerasi Session ID (Mencegah Session Fixation)
            session_regenerate_id(true);

            // Reset hitungan percobaan login yang gagal
            unset($_SESSION['login_attempts'], $_SESSION['login_lockout_time']);

            // Set session data
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role']         = $user['role'] ?? 'admin';
            $_SESSION['pegawai_id']   = $user['pegawai_id'];

            // Update nama_lengkap di tabel users agar terus sync
            if (!empty($user['pegawai_id'])) {
                $sync = $conn->prepare("UPDATE users SET nama_lengkap = ? WHERE id = ?");
                $sync->bind_param("si", $user['nama_lengkap'], $user['id']);
                $sync->execute();
                $sync->close();
            }

            $stmt->close();

            // Safe log inside tmp/
            $log_dir = __DIR__ . '/../../tmp/';
            if (!is_dir($log_dir)) { mkdir($log_dir, 0755, true); }
            error_log(date('Y-m-d H:i:s') . " - LOGIN SUCCESS: user={" . $user['username'] . "}, role={" . $_SESSION['role'] . "}" . PHP_EOL, 3, $log_dir . 'login.log');

            // Role-based redirect
            if ($_SESSION['role'] === 'pegawai') {
                header('Location: ' . BASE_URL . 'portal');
            } else {
                header('Location: ' . BASE_URL . 'dashboard');
            }
            exit();
        }
    }

    // Login Gagal: Tambah hitungan gagal
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] >= 5) {
        $_SESSION['login_lockout_time'] = time() + (3 * 60); // Cooldown 3 menit
        $_SESSION['error_message'] = "Terlalu banyak percobaan gagal (5x). Akun dikunci sementara selama 3 menit.";
    } else {
        $sisa = 5 - $_SESSION['login_attempts'];
        $_SESSION['error_message'] = "Username/NIP atau password salah. (Sisa percobaan: $sisa)";
    }

    if (isset($stmt) && $stmt) { $stmt->close(); }
}

header('Location: ' . BASE_URL . 'login');
exit();
