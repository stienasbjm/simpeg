<?php
// src/modules/sk_functions.php

require_once __DIR__ . '/../../config/database.php';

/**
 * Get all SK from the database.
 * @param mysqli $conn Database connection object.
 * @return array List of SK.
 */
function get_all_sk($conn) {
    $sql = "SELECT * FROM surat_keputusan ORDER BY tanggal_sk DESC";
    $result = $conn->query($sql);
    $sk_list = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sk_list[] = $row;
        }
    }
    return $sk_list;
}

/**
 * Get a single SK by ID.
 * @param mysqli $conn Database connection object.
 * @param int $id SK ID.
 * @return array|null SK data or null if not found.
 */
function get_sk_by_id($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM surat_keputusan WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

/**
 * Add a new SK to the database.
 * @param mysqli $conn Database connection object.
 * @param array $data SK data.
 * @param array $file Uploaded file data.
 * @return bool True on success, false on failure.
 */
function add_sk($conn, $data, $file) {
    $file_sk = upload_file_sk($file, 'sk');

    $stmt = $conn->prepare("INSERT INTO surat_keputusan (nomor_sk, tanggal_sk, tentang, file_sk) VALUES (?, ?, ?, ?)");
    $stmt->bind_param(
        "ssss",
        $data['nomor_sk'], $data['tanggal_sk'], $data['tentang'], $file_sk
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Update an existing SK.
 * @param mysqli $conn Database connection object.
 * @param int $id SK ID.
 * @param array $data SK data.
 * @param array $file Uploaded file data.
 * @return bool True on success, false on failure.
 */
function update_sk($conn, $id, $data, $file) {
    $existing_sk = get_sk_by_id($conn, $id);

    $file_sk = $existing_sk['file_sk'];
    if (isset($file) && $file['error'] == UPLOAD_ERR_OK) {
        $file_sk = upload_file_sk($file, 'sk');
        if ($existing_sk['file_sk'] && file_exists(__DIR__ . '/../../public/' . $existing_sk['file_sk'])) {
            unlink(__DIR__ . '/../../public/' . $existing_sk['file_sk']);
        }
    }

    $stmt = $conn->prepare("UPDATE surat_keputusan SET nomor_sk=?, tanggal_sk=?, tentang=?, file_sk=? WHERE id=?");
    $stmt->bind_param(
        "ssssi",
        $data['nomor_sk'], $data['tanggal_sk'], $data['tentang'], $file_sk, $id
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Delete an SK from the database.
 * @param mysqli $conn Database connection object.
 * @param int $id SK ID.
 * @return bool True on success, false on failure.
 */
function delete_sk($conn, $id) {
    $existing_sk = get_sk_by_id($conn, $id);
    if (!$existing_sk) return false;

    if ($existing_sk['file_sk'] && file_exists(__DIR__ . '/../../public/' . $existing_sk['file_sk'])) {
        unlink(__DIR__ . '/../../public/' . $existing_sk['file_sk']);
    }

    $stmt = $conn->prepare("DELETE FROM surat_keputusan WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Handle file uploads for SK.
 * @param array $file_data Data from $_FILES.
 * @param string $folder Subfolder within 'public/uploads/'.
 * @return string|null Path to the uploaded file or null on failure.
 */
function upload_file_sk($file_data, $folder) {
    if (!isset($file_data) || $file_data['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $target_dir = __DIR__ . '/../../public/uploads/' . $folder . '/';
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = pathinfo($file_data['name'], PATHINFO_EXTENSION);
    $new_file_name = uniqid($folder . '_') . '.' . $file_extension;
    $target_file = $target_dir . $new_file_name;

    $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        $_SESSION['error_message'] = "Jenis file tidak diizinkan. Hanya PDF, JPG, JPEG, PNG.";
        return null;
    }

    if ($file_data['size'] > 10 * 1024 * 1024) { // 10MB
        $_SESSION['error_message'] = "Ukuran file terlalu besar (maks 10MB).";
        return null;
    }

    if (move_uploaded_file($file_data['tmp_name'], $target_file)) {
        return 'uploads/' . $folder . '/' . $new_file_name;
    } else {
        $_SESSION['error_message'] = "Gagal mengunggah file " . $file_data['name'];
        return null;
    }
}
