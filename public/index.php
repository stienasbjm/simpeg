<?php
// ─── Security Headers ────────────────────────────────────────────────────────
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
}

// ─── Config & Auth ────────────────────────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';

// ─── Clean URL Router ─────────────────────────────────────────────────────────
// Mendukung URL bersih: /e_arsip/dashboard, /e_arsip/surat_masuk, dst.
// Juga tetap mendukung ?page= untuk kompatibilitas mundur.

function resolve_page(): string {
    // 1. Coba dari ?page= (backward compat)
    if (!empty($_GET['page'])) {
        return $_GET['page'];
    }

    // 2. Parse dari URI path
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = strtok($request_uri, '?');
    
    // Hapus base directory /simpeg, /e_arsip, /public, atau /index.php jika ada
    $path = preg_replace('#^/(?:simpeg|e_arsip)?(?:/public)?(?:/index\.php)?#i', '', $path);
    $path = trim($path, '/');

    // Robustness: jika path mengandung & (misal: pegawai_save&id=2), pisahkan & dan masukkan ke $_GET
    if (strpos($path, '&') !== false) {
        $parts = explode('&', $path, 2);
        $path = $parts[0];
        if (!empty($parts[1])) {
            parse_str($parts[1], $extra_params);
            foreach ($extra_params as $k => $v) {
                if (!isset($_GET[$k])) $_GET[$k] = $v;
                if (!isset($_REQUEST[$k])) $_REQUEST[$k] = $v;
            }
        }
    }

    return $path ?: 'dashboard';
}

$page = resolve_page();

// Halaman login — layout khusus (tidak pakai layout utama)
if ($page === 'login') {
    include __DIR__ . '/../templates/layout_blank.php';
    exit();
}
if ($page === 'login_process') {
    include __DIR__ . '/../src/auth/login_process.php';
    exit();
}
if ($page === 'logout') {
    include __DIR__ . '/../src/auth/logout.php';
    exit();
}
if ($page === 'slip_gaji_print') {
    include __DIR__ . '/../templates/slip_gaji_print.php';
    exit();
}

// ─── Action Routes (POST handlers) ───────────────────────────────────────────
$action_routes = [
    'pegawai_save'      => '/../src/modules/pegawai_save.php',
    'pegawai_delete'    => '/../src/modules/pegawai_delete.php',
    'surat_masuk_save'  => '/../src/modules/surat_masuk_save.php',
    'surat_masuk_delete'=> '/../src/modules/surat_masuk_delete.php',
    'surat_keluar_save' => '/../src/modules/surat_keluar_save.php',
    'surat_keluar_delete'=> '/../src/modules/surat_keluar_delete.php',
    'sk_save'           => '/../src/modules/sk_save.php',
    'sk_delete'         => '/../src/modules/sk_delete.php',
    'akun_pegawai_save' => '/../src/modules/akun_pegawai_save.php',
    'akun_pegawai_delete'=> '/../src/modules/akun_pegawai_delete.php',
    'absensi_save'      => '/../src/modules/absensi_save.php',
    'absensi_export'    => '/../src/modules/absensi_export.php',
    'akun_admin_save'   => '/../src/modules/akun_admin_save.php',
    'akun_admin_delete' => '/../src/modules/akun_admin_delete.php',
    'kas_save'          => '/../src/modules/kas_save.php',
    'kas_delete'        => '/../src/modules/kas_save.php',
    'kas_export'        => '/../src/modules/kas_export.php',
    'gaji_save'         => '/../src/modules/gaji_save.php',
    'gaji_delete'       => '/../src/modules/gaji_save.php',
    'pengaturan_ttd_save' => '/../src/modules/pengaturan_ttd_save.php',
];

if (isset($action_routes[$page])) {
    include __DIR__ . $action_routes[$page];
    exit();
}

// ─── Standalone Print Pages (Bypass Layout) ──────────────────────────────────
$no_layout_pages = ['slip_gaji_print', 'kas_print'];
if (in_array($page, $no_layout_pages)) {
    $path = __DIR__ . '/../templates/' . $page . '.php';
    if (file_exists($path)) {
        include $path;
        exit();
    }
}

// ─── Portal Pegawai Handler ─────────────────────────────────────────────────
$portal_pages = ['portal', 'profil', 'absensi_action', 'profil_save'];
if (in_array($page, $portal_pages)) {
    include __DIR__ . '/portal.php';
    exit();
}

// Cek login
if (!is_logged_in() && $page !== 'login') {
    header('Location: ' . BASE_URL . 'login');
    exit();
}

// Pegawai → redirect ke portal
if (is_pegawai()) {
    header('Location: ' . BASE_URL . 'portal');
    exit();
}

// Set error reporting (nonaktifkan di produksi!)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ─── Page Whitelist ───────────────────────────────────────────────────────────
$allowed_pages = [
    'dashboard',
    'surat_masuk',
    'surat_keluar',
    'sk',
    'pegawai',
    'pegawai_add',
    'pegawai_edit',
    'pegawai_detail',
    'surat_masuk_add',
    'surat_masuk_edit',
    'surat_masuk_detail',
    'surat_keluar_add',
    'surat_keluar_edit',
    'surat_keluar_detail',
    'sk_add',
    'sk_edit',
    'sk_detail',
    'akun_pegawai',
    'akun_pegawai_add',
    'absensi',
    'absensi_detail',
    'akun_admin',
    'kas',
    'gaji',
    'kas_print',
    'slip_gaji_print',
];

// ─── Template Map ─────────────────────────────────────────────────────────────
$template_map = [
    'pegawai_add'        => 'pegawai_form.php',
    'pegawai_edit'       => 'pegawai_form.php',
    'pegawai_detail'     => 'pegawai_detail.php',
    'surat_masuk_add'    => 'surat_masuk_form.php',
    'surat_masuk_edit'   => 'surat_masuk_form.php',
    'surat_masuk_detail' => 'surat_masuk_detail.php',
    'surat_keluar_add'   => 'surat_keluar_form.php',
    'surat_keluar_edit'  => 'surat_keluar_form.php',
    'surat_keluar_detail'=> 'surat_keluar_detail.php',
    'sk_add'             => 'sk_form.php',
    'sk_edit'            => 'sk_form.php',
    'sk_detail'          => 'sk_detail.php',
    'akun_pegawai_add'   => 'akun_pegawai_form.php',
];

// ─── Load Template ────────────────────────────────────────────────────────────
if (in_array($page, $allowed_pages)) {
    $template_file = $template_map[$page] ?? ($page . '.php');
    $template_path = __DIR__ . '/../templates/' . $template_file;

    if (file_exists($template_path)) {
        $content_page = $template_path;
    } else {
        http_response_code(404);
        $content_page = __DIR__ . '/../templates/404.php';
    }
} else {
    http_response_code(404);
    $content_page = __DIR__ . '/../templates/404.php';
}

include __DIR__ . '/../templates/layout.php';

