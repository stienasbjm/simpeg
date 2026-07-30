<?php
// src/modules/akun_admin_save.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/akun_admin_functions.php';

require_developer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'akun_admin');
    exit();
}

$action = $_POST['action'] ?? 'create';

if ($action === 'create') {
    $username     = trim($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $role         = $_POST['role'] ?? 'admin';

    if (empty($username) || empty($password) || empty($nama_lengkap)) {
        $_SESSION['error_message'] = "Semua bidang formulir wajib diisi.";
    } else {
        $res = add_admin_user($conn, $username, $password, $nama_lengkap, $role);
        $_SESSION[$res['status'] ? 'success_message' : 'error_message'] = $res['msg'];
    }

} elseif ($action === 'update') {
    $id           = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $username     = trim($_POST['username'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $role         = $_POST['role'] ?? 'admin';
    $password     = $_POST['password'] ?? '';

    if ($id === null || empty($username) || empty($nama_lengkap)) {
        $_SESSION['error_message'] = "Data tidak valid.";
    } else {
        $res = update_admin_user($conn, $id, $username, $nama_lengkap, $role, $password);
        if ($res['status']) {
            $_SESSION['success_message'] = $res['msg'];
            // Jika mengedit akun sendiri yang sedang aktif, update session
            if ($id == $_SESSION['user_id']) {
                $_SESSION['username']     = $username;
                $_SESSION['nama_lengkap'] = $nama_lengkap;
                $_SESSION['role']         = $role;
            }
        } else {
            $_SESSION['error_message'] = $res['msg'];
        }
    }
}

header('Location: ' . BASE_URL . 'akun_admin');
exit();
