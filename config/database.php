<?php

// Konfigurasi Database
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'e_arsip');

// Atur Timezone Indonesia (WITA +08:00 / Asia/Makassar)
date_default_timezone_set('Asia/Makassar');

// Buat Koneksi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek Koneksi
if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}
$conn->query("SET time_zone = '+08:00'");

// Atur base URL — selalu mengarah ke root aplikasi (clean URL)
function get_base_url() {
    $port = $_SERVER['SERVER_PORT'] ?? 80;
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $port == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Tentukan root aplikasi (folder e_arsip)
    $script_dir = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';

    // Cari posisi /e_arsip/ lalu ambil sampai situ saja
    if (preg_match('#^(/[^/]+/e_arsip)#', $script_dir, $m)) {
        $base_path = $m[1];
    } elseif (preg_match('#^(/e_arsip)#', $script_dir, $m)) {
        $base_path = $m[1];
    } else {
        // Fallback: naik dari /public ke parent
        $base_path = rtrim(str_replace('/public', '', dirname($script_dir)), '/');
    }

    return $protocol . $host . $base_path . '/';
}

define('BASE_URL', get_base_url());

// Helper: buat URL halaman dengan clean path (tanpa ?page=)
function url(string $page): string {
    return BASE_URL . ltrim($page, '/');
}

// Keamanan Auth — Selalu aktifkan otentikasi wajib login
define('DISABLE_AUTH', false);