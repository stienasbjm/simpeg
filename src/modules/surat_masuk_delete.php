<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/surat_masuk_functions.php';

require_login();

if (isset($_GET['id'])) {
    $surat_id = (int)$_GET['id'];

    if (delete_surat_masuk($conn, $surat_id)) {
        $_SESSION['success_message'] = "Data surat masuk berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus data surat masuk.";
    }
} else {
    $_SESSION['error_message'] = "ID surat masuk tidak ditemukan.";
}

header('Location: ' . BASE_URL . 'surat_masuk');
exit();
