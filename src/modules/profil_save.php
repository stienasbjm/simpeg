<?php
// src/modules/profil_save.php — Portal pegawai update profil & foto profil
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

    // Update data pegawai utama
    $stmt = $conn->prepare(
        "UPDATE pegawai SET nama=?, tempat_lahir=?, tanggal_lahir=?, ijazah=? WHERE id=?"
    );
    $stmt->bind_param("ssssi", $nama, $tempat_lahir, $tanggal_lahir, $ijazah, $pid);
    $ok = $stmt->execute();
    $stmt->close();

    // Update session nama
    if ($ok) $_SESSION['nama_lengkap'] = $nama;

    // Handle hapus foto profil
    if (isset($_POST['hapus_foto']) && $_POST['hapus_foto'] === '1') {
        $stmt = $conn->prepare("SELECT foto FROM pegawai WHERE id=?");
        $stmt->bind_param("i", $pid);
        $stmt->execute();
        $res = $stmt->get_result();
        $cur = $res->fetch_assoc();
        $stmt->close();

        if (!empty($cur['foto']) && file_exists(__DIR__ . '/../../public/uploads/foto/' . $cur['foto'])) {
            @unlink(__DIR__ . '/../../public/uploads/foto/' . $cur['foto']);
        }

        $stmt = $conn->prepare("UPDATE pegawai SET foto=NULL WHERE id=?");
        $stmt->bind_param("i", $pid);
        $stmt->execute();
        $stmt->close();
    }

    // Handle upload foto profil baru
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size      = 5 * 1024 * 1024; // 5 MB

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['foto']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_mimes)) {
            $_SESSION['error_message'] = "Format foto tidak didukung. Harap gunakan JPG, PNG, atau WebP.";
            header('Location: ' . BASE_URL . 'profil'); exit();
        }

        if ($_FILES['foto']['size'] > $max_size) {
            $_SESSION['error_message'] = "Ukuran file foto terlalu besar (Maksimal 5 MB).";
            header('Location: ' . BASE_URL . 'profil'); exit();
        }

        // Ambil nama foto lama jika ada
        $stmt = $conn->prepare("SELECT foto FROM pegawai WHERE id=?");
        $stmt->bind_param("i", $pid);
        $stmt->execute();
        $res = $stmt->get_result();
        $cur = $res->fetch_assoc();
        $stmt->close();

        if (!empty($cur['foto']) && file_exists(__DIR__ . '/../../public/uploads/foto/' . $cur['foto'])) {
            @unlink(__DIR__ . '/../../public/uploads/foto/' . $cur['foto']);
        }

        $ext           = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $new_foto_name = 'foto_' . $pid . '_' . time() . '.' . $ext;
        $dest          = __DIR__ . '/../../public/uploads/foto/' . $new_foto_name;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
            $stmt = $conn->prepare("UPDATE pegawai SET foto=? WHERE id=?");
            $stmt->bind_param("si", $new_foto_name, $pid);
            $stmt->execute();
            $stmt->close();
        }
    }

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
        ? "Profil dan foto berhasil diperbarui."
        : "Gagal memperbarui profil.";
}

header('Location: ' . BASE_URL . 'profil');
exit();
