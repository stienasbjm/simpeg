<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/surat_masuk_functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nomor_surat' => $_POST['nomor_surat'] ?? '',
        'tanggal_surat' => $_POST['tanggal_surat'] ?? '',
        'tanggal_diterima' => $_POST['tanggal_diterima'] ?? '',
        'pengirim' => $_POST['pengirim'] ?? '',
        'perihal' => $_POST['perihal'] ?? ''
    ];

    $file = $_FILES['file_surat'] ?? null;

    $id = $_GET['id'] ?? $_POST['id'] ?? null;

    if ($id) {
        // Update Surat Masuk
        if (update_surat_masuk($conn, $id, $data, $file)) {
            $_SESSION['success_message'] = "Data surat masuk berhasil diperbarui.";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui data surat masuk.";
        }
    } else {
        // Add Surat Masuk
        if (add_surat_masuk($conn, $data, $file)) {
            $_SESSION['success_message'] = "Data surat masuk berhasil ditambahkan.";
        } else {
            $_SESSION['error_message'] = "Gagal menambahkan data surat masuk.";
        }
    }
} else {
    $_SESSION['error_message'] = "Metode request tidak valid.";
}

header('Location: ' . BASE_URL . 'surat_masuk');
exit();
