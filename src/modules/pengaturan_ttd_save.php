<?php
// src/modules/pengaturan_ttd_save.php — Handler simpan penandatangan
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/keuangan_functions.php';
require_bendahara();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    save_pengaturan_ttd($conn, $_POST);
    $_SESSION['success_message'] = "Pengaturan Nama & NIP Pejabat Penandatangan berhasil diperbarui.";
}

$redirect = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'dashboard');
header('Location: ' . $redirect);
exit();
