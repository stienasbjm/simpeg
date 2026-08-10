<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/pegawai_functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $_SESSION['error_message'] = "Token CSRF tidak valid.";
        header('Location: ' . BASE_URL . 'pegawai');
        exit();
    }
    $tmk = !empty($_POST['tanggal_masuk_kerja']) ? $_POST['tanggal_masuk_kerja'] : null;
    $tmt_pangkat = !empty($_POST['tmt_pangkat']) ? $_POST['tmt_pangkat'] : null;
    $tmt_jabatan = !empty($_POST['tmt_jabatan']) ? $_POST['tmt_jabatan'] : null;
    $sk_inpassing_2025 = isset($_POST['sk_inpassing_2025']) ? 1 : 0;

    $data = [
        'nama'                => trim($_POST['nama'] ?? ''),
        'tempat_lahir'        => trim($_POST['tempat_lahir'] ?? ''),
        'tanggal_lahir'       => $_POST['tanggal_lahir'] ?? '',
        'nip'                 => trim($_POST['nip'] ?? ''),
        'kepangkatan'         => $_POST['kepangkatan'] ?? '',
        'tmt_pangkat'         => $tmt_pangkat,
        'jabatan_fungsional'  => trim($_POST['jabatan_fungsional'] ?? '') ?: null,
        'tmt_jabatan'         => $tmt_jabatan,
        'sk_inpassing_2025'   => $sk_inpassing_2025,
        'ijazah'              => trim($_POST['ijazah'] ?? ''),
        'status_kepegawaian'  => $_POST['status_kepegawaian'] ?? 'Dosen PNS',
        'tanggal_masuk_kerja' => $tmk,
    ];
    $files = [
        'file_ijazah'            => $_FILES['file_ijazah'] ?? null,
        'file_kepangkatan'       => $_FILES['file_kepangkatan'] ?? null,
        'file_jabatan_fungsional'=> $_FILES['file_jabatan_fungsional'] ?? null,
    ];
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    if ($id) {
        if (update_pegawai($conn, $id, $data, $files)) {
            $_SESSION['success_message'] = "Data pegawai berhasil diperbarui.";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui data pegawai.";
        }
    } else {
        if (add_pegawai($conn, $data, $files)) {
            $_SESSION['success_message'] = "Data pegawai berhasil ditambahkan.";
        } else {
            $_SESSION['error_message'] = "Gagal menambahkan data pegawai.";
        }
    }
} else {
    $_SESSION['error_message'] = "Metode request tidak valid.";
}
header('Location: ' . BASE_URL . 'pegawai');
exit();
