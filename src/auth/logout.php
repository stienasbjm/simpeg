<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/auth.php'; // Untuk session_start()

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hapus semua variabel session
$_SESSION = array();

// Jika ingin menghancurkan session sepenuhnya, hapus juga cookie session.
// Catatan: Ini akan menghancurkan session, dan bukan hanya data session!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Akhiri session
session_destroy();

// Redirect ke halaman login
header('Location: ' . BASE_URL . 'login');
exit();
