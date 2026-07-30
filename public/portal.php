<?php
// public/portal.php — Router Portal Pegawai
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';

// Harus login
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . 'login');
    exit();
}
// Admin yang akses portal → redirect ke dashboard
if (is_admin()) {
    header('Location: ' . BASE_URL . 'dashboard');
    exit();
}

$page_name = resolve_page();
$page = $_GET['page'] ?? ($page_name === 'profil' ? 'profil' : 'absensi');

// Handler actions
if ($page_name === 'absensi_action' || $page === 'absensi_action') {
    include __DIR__ . '/../src/modules/absensi_save.php';
    exit();
}
if ($page_name === 'profil_save' || $page === 'profil_save') {
    include __DIR__ . '/../src/modules/profil_save.php';
    exit();
}

$allowed = ['absensi', 'profil'];
if (!in_array($page, $allowed)) $page = 'absensi';

$content_page = __DIR__ . '/../templates/portal/' . $page . '.php';
if (!file_exists($content_page)) {
    $content_page = __DIR__ . '/../templates/portal/absensi.php';
}

include __DIR__ . '/../templates/portal/layout.php';

