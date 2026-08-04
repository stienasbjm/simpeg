<?php
// src/modules/izin_save.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/izin_functions.php';

require_login();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Pegawai: Kirim pengajuan izin/sakit/dinas ──────────────────────────────
if ($action === 'kirim_izin') {
    $pid = get_session_pegawai_id();
    if (!$pid) {
        $_SESSION['error_message'] = "Akun tidak terhubung ke data pegawai.";
        header('Location: ' . BASE_URL . 'portal'); exit();
    }

    $tanggal    = trim($_POST['tanggal']    ?? '');
    $jenis      = trim($_POST['jenis']      ?? 'izin');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $file       = $_FILES['file_bukti'] ?? null;

    $res = kirim_pengajuan_izin($conn, $pid, $tanggal, $jenis, $keterangan, $file);
    $_SESSION[$res['status'] === 'ok' ? 'success_message' : 'error_message'] = $res['msg'];
    header('Location: ' . BASE_URL . 'portal'); exit();
}

// ── Admin: Setujui pengajuan ───────────────────────────────────────────────
if ($action === 'approve_izin') {
    require_admin();
    $id             = (int)($_POST['id']             ?? 0);
    $catatan_admin  = trim($_POST['catatan_admin']   ?? '');
    if ($id) {
        $ok = update_status_pengajuan($conn, $id, 'disetujui', $catatan_admin);
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Pengajuan disetujui.' : 'Gagal menyetujui.';
    }
    header('Location: ' . BASE_URL . 'absensi'); exit();
}

// ── Admin: Tolak pengajuan ─────────────────────────────────────────────────
if ($action === 'tolak_izin') {
    require_admin();
    $id             = (int)($_POST['id']             ?? 0);
    $catatan_admin  = trim($_POST['catatan_admin']   ?? '');
    if ($id) {
        $ok = update_status_pengajuan($conn, $id, 'ditolak', $catatan_admin);
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Pengajuan ditolak.' : 'Gagal menolak.';
    }
    header('Location: ' . BASE_URL . 'absensi'); exit();
}

header('Location: ' . BASE_URL . 'portal'); exit();
