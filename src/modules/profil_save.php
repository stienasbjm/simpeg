<?php
// src/modules/profil_save.php — Portal pegawai update profil sendiri
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_login();

$pid = get_session_pegawai_id();
if (!$pid) {
    $_SESSION['error_message'] = "Akun tidak terhubung ke data pegawai.";
    header('Location: ' . BASE_URL . 'profil'); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $_SESSION['error_message'] = "Token CSRF tidak valid. Silakan coba lagi.";
        header('Location: ' . BASE_URL . 'profil'); exit();
    }
    $nama          = trim($_POST['nama'] ?? '');
    $tempat_lahir  = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $ijazah        = trim($_POST['ijazah'] ?? '');
    $pass_baru     = $_POST['password_baru'] ?? '';
    $pass_konfirm  = $_POST['password_konfirmasi'] ?? '';

    if (empty($nama) || empty($tempat_lahir) || empty($tanggal_lahir) || empty($ijazah)) {
        $_SESSION['error_message'] = "Field yang wajib tidak boleh kosong.";
        header('Location: ' . BASE_URL . 'profil'); exit();
    }

    // Update data pegawai
    $stmt = $conn->prepare(
        "UPDATE pegawai SET nama=?, tempat_lahir=?, tanggal_lahir=?, ijazah=? WHERE id=?"
    );
    $stmt->bind_param("ssssi", $nama, $tempat_lahir, $tanggal_lahir, $ijazah, $pid);
    $ok = $stmt->execute();
    $stmt->close();

    // Update session nama
    if ($ok) $_SESSION['nama_lengkap'] = $nama;

    // Update password jika diisi
    if (!empty($pass_baru)) {
        if ($pass_baru !== $pass_konfirm) {
            $_SESSION['error_message'] = "Konfirmasi password tidak cocok.";
            header('Location: ' . BASE_URL . 'profil'); exit();
        }
        if (strlen($pass_baru) < 6) {
            $_SESSION['error_message'] = "Password minimal 6 karakter.";
            header('Location: ' . BASE_URL . 'profil'); exit();
        }
        $uid  = $_SESSION['user_id'];
        $hash = password_hash($pass_baru, PASSWORD_DEFAULT);
        $stmt2 = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt2->bind_param("si", $hash, $uid);
        $stmt2->execute();
        $stmt2->close();
    }

    $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok
        ? "Profil berhasil diperbarui."
        : "Gagal memperbarui profil.";
}

header('Location: ' . BASE_URL . 'profil');
exit();
