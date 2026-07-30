<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/sk_functions.php';

require_login();

if (isset($_GET['id'])) {
    $sk_id = (int)$_GET['id'];

    if (delete_sk($conn, $sk_id)) {
        $_SESSION['success_message'] = "Data SK berhasil dihapus.";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus data SK.";
    }
} else {
    $_SESSION['error_message'] = "ID SK tidak ditemukan.";
}

header('Location: ' . BASE_URL . 'sk');
exit();
