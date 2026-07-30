<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/surat_keluar_functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nomor_surat' => $_POST['nomor_surat'] ?? '',
        'tanggal_surat' => $_POST['tanggal_surat'] ?? '',
        'tujuan' => $_POST['tujuan'] ?? '',
        'perihal' => $_POST['perihal'] ?? ''
    ];

    $file = $_FILES['file_surat'] ?? null;

    $id = $_GET['id'] ?? $_POST['id'] ?? null;

    if ($id) {
        // Update Surat Keluar
        if (update_surat_keluar($conn, $id, $data, $file)) {
            $_SESSION['success_message'] = "Data surat keluar berhasil diperbarui.";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui data surat keluar.";
        }
    } else {
        // Add Surat Keluar
        if (add_surat_keluar($conn, $data, $file)) {
            $_SESSION['success_message'] = "Data surat keluar berhasil ditambahkan.";
        } else {
            $_SESSION['error_message'] = "Gagal menambahkan data surat keluar.";
        }
    }
} else {
    $_SESSION['error_message'] = "Metode request tidak valid.";
}

header('Location: ' . BASE_URL . 'surat_keluar');
exit();
