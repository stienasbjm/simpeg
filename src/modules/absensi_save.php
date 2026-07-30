<?php
// src/modules/absensi_save.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/absensi_functions.php';
require_login();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Absensi oleh pegawai sendiri
if ($action === 'masuk') {
    $pid = get_session_pegawai_id();
    if (!$pid) { $_SESSION['error_message'] = "Akun tidak terhubung ke data pegawai."; }
    else {
        $res = absen_masuk($conn, $pid);
        $_SESSION[$res['status'] === 'ok' ? 'success_message' : 'error_message'] = $res['msg'];
    }
    header('Location: ' . BASE_URL . 'portal'); exit();
}

if ($action === 'pulang') {
    $pid = get_session_pegawai_id();
    if (!$pid) { $_SESSION['error_message'] = "Akun tidak terhubung ke data pegawai."; }
    else {
        $res = absen_pulang($conn, $pid);
        $_SESSION[$res['status'] === 'ok' ? 'success_message' : 'error_message'] = $res['msg'];
    }
    header('Location: ' . BASE_URL . 'portal'); exit();
}

// Simpan/koreksi absensi oleh admin
if ($action === 'admin_save') {
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ok = admin_simpan_absensi(
            $conn,
            (int)($_POST['pegawai_id'] ?? 0),
            $_POST['tanggal'] ?? date('Y-m-d'),
            $_POST['jam_masuk'] ?? '',
            $_POST['jam_keluar'] ?? '',
            $_POST['status'] ?? 'hadir',
            $_POST['keterangan'] ?? ''
        );
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Data absensi berhasil disimpan.' : 'Gagal menyimpan data absensi.';
    }
    $pid = $_POST['pegawai_id'] ?? '';
    header('Location: ' . BASE_URL . 'absensi_detail?id=' . $pid); exit();
}

// Hapus absensi oleh admin
if ($action === 'admin_delete') {
    require_admin();
    $id = (int)($_GET['id'] ?? 0);
    $pid = (int)($_GET['pid'] ?? 0);
    if ($id) {
        $ok = delete_absensi($conn, $id);
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Absensi dihapus.' : 'Gagal menghapus.';
    }
    header('Location: ' . BASE_URL . 'absensi_detail?id=' . $pid); exit();
}

header('Location: ' . BASE_URL . 'absensi'); exit();
