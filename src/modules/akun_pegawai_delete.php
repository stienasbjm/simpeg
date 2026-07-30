<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'pegawai'");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? "Akun pegawai dihapus." : "Gagal menghapus akun.";
}
header('Location: ' . BASE_URL . 'akun_pegawai'); exit();
