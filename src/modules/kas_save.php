<?php
// src/modules/kas_save.php — Handler simpan & hapus kas transaksi
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/keuangan_functions.php';
require_bendahara();

$action = $_REQUEST['action'] ?? 'add';

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $ok = delete_kas_transaksi($conn, $id);
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? "Transaksi kas berhasil dihapus." : "Gagal menghapus transaksi.";
    }
} elseif ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'tanggal'   => $_POST['tanggal'] ?? date('Y-m-d'),
        'jenis_kas' => $_POST['jenis_kas'] ?? 'kas_kecil',
        'tipe'      => $_POST['tipe'] ?? 'pemasukan',
        'kategori'  => trim($_POST['kategori'] ?? ''),
        'keterangan'=> trim($_POST['keterangan'] ?? ''),
        'jumlah'    => (float)($_POST['jumlah'] ?? 0)
    ];

    if (empty($data['kategori']) || $data['jumlah'] <= 0) {
        $_SESSION['error_message'] = "Kategori dan nominal transaksi harus diisi dengan benar.";
    } else {
        $user_id = $_SESSION['user_id'] ?? null;
        $ok = add_kas_transaksi($conn, $data, $user_id);
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? "Transaksi kas berhasil dicatat." : "Gagal mencatat transaksi kas.";
    }
} elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id'        => (int)($_POST['id'] ?? 0),
        'tanggal'   => $_POST['tanggal'] ?? date('Y-m-d'),
        'jenis_kas' => $_POST['jenis_kas'] ?? 'kas_kecil',
        'tipe'      => $_POST['tipe'] ?? 'pemasukan',
        'kategori'  => trim($_POST['kategori'] ?? ''),
        'keterangan'=> trim($_POST['keterangan'] ?? ''),
        'jumlah'    => (float)($_POST['jumlah'] ?? 0)
    ];

    if (!$data['id'] || empty($data['kategori']) || $data['jumlah'] <= 0) {
        $_SESSION['error_message'] = "Data transaksi kas yang akan diedit tidak valid.";
    } else {
        $ok = update_kas_transaksi($conn, $data);
        $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? "Transaksi kas berhasil diperbarui." : "Gagal memperbarui transaksi kas.";
    }
}

$jenis_kas = $_REQUEST['jenis_kas'] ?? '';
$bulan     = $_REQUEST['bulan'] ?? '';
$tahun     = $_REQUEST['tahun'] ?? '';

$redirect = BASE_URL . 'kas';
$params   = [];
if ($jenis_kas) $params[] = 'jenis_kas=' . urlencode($jenis_kas);
if ($bulan)     $params[] = 'bulan=' . urlencode($bulan);
if ($tahun)     $params[] = 'tahun=' . urlencode($tahun);

if (!empty($params)) {
    $redirect .= '?' . implode('&', $params);
}

header('Location: ' . $redirect);
exit();
