<?php
// src/modules/akun_admin_delete.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/akun_admin_functions.php';

require_developer();

$id = (int)($_GET['id'] ?? 0);
$current_id = $_SESSION['user_id'] ?? 0;

if ($id) {
    $res = delete_admin_user($conn, $id, $current_id);
    $_SESSION[$res['status'] ? 'success_message' : 'error_message'] = $res['msg'];
}

header('Location: ' . BASE_URL . 'akun_admin');
exit();
