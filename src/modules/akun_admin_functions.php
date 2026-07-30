<?php
// src/modules/akun_admin_functions.php
require_once __DIR__ . '/../../config/database.php';

function get_all_admin_users($conn) {
    $result = $conn->query(
        "SELECT id, username, nama_lengkap, role, created_at 
         FROM users 
         WHERE role IN ('admin', 'developer', 'bendahara') 
         ORDER BY id ASC"
    );
    $list = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $list[] = $row;
        }
    }
    return $list;
}

function get_admin_user_by_id($conn, $id) {
    $stmt = $conn->prepare("SELECT id, username, nama_lengkap, role FROM users WHERE id = ? AND role IN ('admin', 'developer', 'bendahara')");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->num_rows > 0 ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

function add_admin_user($conn, $username, $password, $nama_lengkap, $role = 'admin') {
    // Cek username unik
    $chk = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $chk->bind_param("s", $username);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $chk->close();
        return ['status' => false, 'msg' => 'Username sudah digunakan.'];
    }
    $chk->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $hash, $nama_lengkap, $role);
    $ok = $stmt->execute();
    $stmt->close();
    return ['status' => $ok, 'msg' => $ok ? 'Akun administrator berhasil dibuat.' : 'Gagal membuat akun.'];
}

function update_admin_user($conn, $id, $username, $nama_lengkap, $role, $password = null) {
    // Cek username unik (selain user ini)
    $chk = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $chk->bind_param("si", $username, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $chk->close();
        return ['status' => false, 'msg' => 'Username sudah digunakan oleh akun lain.'];
    }
    $chk->close();

    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, nama_lengkap=?, role=?, password=? WHERE id=?");
        $stmt->bind_param("ssssi", $username, $nama_lengkap, $role, $hash, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, nama_lengkap=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $username, $nama_lengkap, $role, $id);
    }

    $ok = $stmt->execute();
    $stmt->close();
    return ['status' => $ok, 'msg' => $ok ? 'Data akun berhasil diperbarui.' : 'Gagal memperbarui akun.'];
}

function delete_admin_user($conn, $id, $current_user_id) {
    if ($id == $current_user_id) {
        return ['status' => false, 'msg' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang digunakan.'];
    }
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role IN ('admin', 'developer', 'bendahara')");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    return ['status' => $ok, 'msg' => $ok ? 'Akun administrator berhasil dihapus.' : 'Gagal menghapus akun.'];
}
