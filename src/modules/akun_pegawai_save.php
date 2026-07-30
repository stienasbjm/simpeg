<?php
// src/modules/akun_pegawai_save.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'akun_pegawai'); 
    exit();
}

$action      = $_POST['action'] ?? 'create';
$pegawai_id  = (int)($_POST['pegawai_id'] ?? 0);
$username    = trim($_POST['username'] ?? '');
$password    = $_POST['password'] ?? '';
$user_id     = (int)($_POST['user_id'] ?? 0);

if ($action === 'create') {
    if (empty($username) || empty($password) || !$pegawai_id) {
        $_SESSION['error_message'] = "Semua bidang formulir wajib diisi.";
        header('Location: ' . BASE_URL . 'akun_pegawai_add'); 
        exit();
    }
    // Cek username unik
    $chk = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $chk->bind_param("s", $username);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $_SESSION['error_message'] = "Username sudah digunakan.";
        $chk->close();
        header('Location: ' . BASE_URL . 'akun_pegawai_add'); 
        exit();
    }
    $chk->close();

    // Ambil nama terbaru dari tabel pegawai
    $pq = $conn->prepare("SELECT nama FROM pegawai WHERE id = ?");
    $pq->bind_param("i", $pegawai_id);
    $pq->execute();
    $prow = $pq->get_result()->fetch_assoc();
    $pq->close();
    $nama = $prow['nama'] ?? 'Pegawai';

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        "INSERT INTO users (username, password, nama_lengkap, role, pegawai_id) VALUES (?, ?, ?, 'pegawai', ?)"
    );
    $stmt->bind_param("sssi", $username, $hash, $nama, $pegawai_id);
    $ok = $stmt->execute();
    $stmt->close();
    $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? "Akun pegawai berhasil dibuat." : "Gagal membuat akun.";

} elseif ($action === 'update') {
    if (empty($username) || !$user_id || !$pegawai_id) {
        $_SESSION['error_message'] = "Data tidak lengkap.";
        header('Location: ' . BASE_URL . 'akun_pegawai'); 
        exit();
    }

    // Cek username unik (selain user id ini)
    $chk = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $chk->bind_param("si", $username, $user_id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $_SESSION['error_message'] = "Username sudah digunakan oleh akun lain.";
        $chk->close();
        header('Location: ' . BASE_URL . 'akun_pegawai'); 
        exit();
    }
    $chk->close();

    // Ambil nama dari data pegawai yang di-link
    $pq = $conn->prepare("SELECT nama FROM pegawai WHERE id = ?");
    $pq->bind_param("i", $pegawai_id);
    $pq->execute();
    $prow = $pq->get_result()->fetch_assoc();
    $pq->close();
    $nama = $prow['nama'] ?? 'Pegawai';

    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, nama_lengkap = ?, pegawai_id = ? WHERE id = ? AND role = 'pegawai'");
        $stmt->bind_param("sssii", $username, $hash, $nama, $pegawai_id, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, nama_lengkap = ?, pegawai_id = ? WHERE id = ? AND role = 'pegawai'");
        $stmt->bind_param("ssii", $username, $nama, $pegawai_id, $user_id);
    }

    $ok = $stmt->execute();
    $stmt->close();
    $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? "Akun pegawai berhasil diperbarui." : "Gagal memperbarui akun pegawai.";

} elseif ($action === 'reset_password') {
    if (empty($password) || !$user_id) {
        $_SESSION['error_message'] = "Password tidak boleh kosong.";
        header('Location: ' . BASE_URL . 'akun_pegawai'); 
        exit();
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'pegawai'");
    $stmt->bind_param("si", $hash, $user_id);
    $ok = $stmt->execute();
    $stmt->close();
    $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? "Password berhasil direset." : "Gagal reset password.";
}

header('Location: ' . BASE_URL . 'akun_pegawai'); 
exit();
