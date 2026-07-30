<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/sk_functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nomor_sk' => $_POST['nomor_sk'] ?? '',
        'tanggal_sk' => $_POST['tanggal_sk'] ?? '',
        'tentang' => $_POST['tentang'] ?? ''
    ];

    $file = $_FILES['file_sk'] ?? null;

    $id = $_GET['id'] ?? $_POST['id'] ?? null;

    if ($id) {
        // Update SK
        if (update_sk($conn, $id, $data, $file)) {
            $_SESSION['success_message'] = "Data SK berhasil diperbarui.";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui data SK.";
        }
    } else {
        // Add SK
        if (add_sk($conn, $data, $file)) {
            $_SESSION['success_message'] = "Data SK berhasil ditambahkan.";
        } else {
            $_SESSION['error_message'] = "Gagal menambahkan data SK.";
        }
    }
} else {
    $_SESSION['error_message'] = "Metode request tidak valid.";
}

header('Location: ' . BASE_URL . 'sk');
exit();
