<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/surat_keluar_functions.php';

require_login();

if (isset($_GET['id'])) {
    $surat_id = (int)$_GET['id'];

    if (delete_surat_keluar($conn, $surat_id)) {
        $_SESSION['success_message'] = "Data surat keluar berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus data surat keluar.";
    }
} else {
    $_SESSION['error_message'] = "ID surat keluar tidak ditemukan.";
}

header('Location: ' . BASE_URL . 'surat_keluar');
exit();
