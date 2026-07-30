<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/pegawai_functions.php';

require_login();

if (isset($_GET['id'])) {
    $pegawai_id = (int)$_GET['id'];

    if (delete_pegawai($conn, $pegawai_id)) {
        $_SESSION['success_message'] = "Data pegawai berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus data pegawai.";
    }
} else {
    $_SESSION['error_message'] = "ID pegawai tidak ditemukan.";
}

header('Location: ' . BASE_URL . 'pegawai');
exit();
