<?php
// src/modules/gaji_save.php — Handler simpan & hapus penggajian
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/keuangan_functions.php';
require_bendahara();

$action = $_REQUEST['action'] ?? 'save';
$bulan  = (int)($_REQUEST['bulan'] ?? date('n'));
$tahun  = (int)($_REQUEST['tahun'] ?? date('Y'));

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $ok = delete_gaji($conn, $id);
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? "Data gaji berhasil dihapus." : "Gagal menghapus data gaji.";
    }
} elseif ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'pegawai_id'        => (int)($_POST['pegawai_id'] ?? 0),
        'bulan'             => $bulan,
        'tahun'             => $tahun,
        'gaji_pokok'        => (float)($_POST['gaji_pokok'] ?? 0),
        'tunj_fungsional'   => (float)($_POST['tunj_fungsional'] ?? 0),
        'tunj_struktural'   => (float)($_POST['tunj_struktural'] ?? 0),
        'tunj_kesejahteraan'=> (float)($_POST['tunj_kesejahteraan'] ?? 0),
        'status_kawin'      => (int)($_POST['status_kawin'] ?? 0),
        'tunj_kawin'        => (float)($_POST['tunj_kawin'] ?? 0),
        'jumlah_anak'       => (int)($_POST['jumlah_anak'] ?? 0),
        'tunj_anak'         => (float)($_POST['tunj_anak'] ?? 0),
        'tunj_beras'        => (float)($_POST['tunj_beras'] ?? 200000),
        'tunjangan_makan'   => (float)($_POST['tunjangan_makan'] ?? 0),
        'tunjangan_lain'    => (float)($_POST['tunjangan_lain'] ?? 0),
        'pot_bpjs_tk'       => (float)($_POST['pot_bpjs_tk'] ?? 0),
        'pot_bpjs_kes'      => (float)($_POST['pot_bpjs_kes'] ?? 0),
        'pot_koperasi'      => (float)($_POST['pot_koperasi'] ?? 20000),
        'potongan'          => (float)($_POST['potongan'] ?? 0),
        'catatan'           => trim($_POST['catatan'] ?? '')
    ];

    if (!$data['pegawai_id']) {
        $_SESSION['error_message'] = "Pegawai tidak valid.";
    } else {
        $ok = save_gaji($conn, $data);
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? "Data penggajian berhasil disimpan." : "Gagal menyimpan data penggajian.";
    }
}

header('Location: ' . BASE_URL . 'gaji?bulan=' . $bulan . '&tahun=' . $tahun);
exit();
