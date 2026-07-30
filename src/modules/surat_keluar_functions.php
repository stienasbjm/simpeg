<?php
// src/modules/surat_keluar_functions.php

require_once __DIR__ . '/../../config/database.php';

/**
 * Get all outgoing mails from the database.
 * @param mysqli $conn Database connection object.
 * @return array List of outgoing mails.
 */
function get_all_surat_keluar($conn) {
    $sql = "SELECT * FROM surat_keluar ORDER BY tanggal_surat DESC";
    $result = $conn->query($sql);
    $surat_keluar_list = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $surat_keluar_list[] = $row;
        }
    }
    return $surat_keluar_list;
}

/**
 * Get a single outgoing mail by ID.
 * @param mysqli $conn Database connection object.
 * @param int $id Outgoing mail ID.
 * @return array|null Outgoing mail data or null if not found.
 */
function get_surat_keluar_by_id($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM surat_keluar WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

/**
 * Add a new outgoing mail to the database.
 * @param mysqli $conn Database connection object.
 * @param array $data Outgoing mail data.
 * @param array $file Uploaded file data.
 * @return bool True on success, false on failure.
 */
function add_surat_keluar($conn, $data, $file) {
    $file_surat = upload_file_surat_keluar($file, 'surat_keluar');

    $stmt = $conn->prepare("INSERT INTO surat_keluar (nomor_surat, tanggal_surat, tujuan, perihal, file_surat) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "sssss",
        $data['nomor_surat'], $data['tanggal_surat'], $data['tujuan'],
        $data['perihal'], $file_surat
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Update an existing outgoing mail.
 * @param mysqli $conn Database connection object.
 * @param int $id Outgoing mail ID.
 * @param array $data Outgoing mail data.
 * @param array $file Uploaded file data.
 * @return bool True on success, false on failure.
 */
function update_surat_keluar($conn, $id, $data, $file) {
    $existing_surat = get_surat_keluar_by_id($conn, $id);

    $file_surat = $existing_surat['file_surat'];
    if (isset($file) && $file['error'] == UPLOAD_ERR_OK) {
        $file_surat = upload_file_surat_keluar($file, 'surat_keluar');
        if ($existing_surat['file_surat'] && file_exists(__DIR__ . '/../../public/' . $existing_surat['file_surat'])) {
            unlink(__DIR__ . '/../../public/' . $existing_surat['file_surat']);
        }
    }

    $stmt = $conn->prepare("UPDATE surat_keluar SET nomor_surat=?, tanggal_surat=?, tujuan=?, perihal=?, file_surat=? WHERE id=?");
    $stmt->bind_param(
        "sssssi",
        $data['nomor_surat'], $data['tanggal_surat'], $data['tujuan'],
        $data['perihal'], $file_surat, $id
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Delete an outgoing mail from the database.
 * @param mysqli $conn Database connection object.
 * @param int $id Outgoing mail ID.
 * @return bool True on success, false on failure.
 */
function delete_surat_keluar($conn, $id) {
    $existing_surat = get_surat_keluar_by_id($conn, $id);
    if (!$existing_surat) return false;

    if ($existing_surat['file_surat'] && file_exists(__DIR__ . '/../../public/' . $existing_surat['file_surat'])) {
        unlink(__DIR__ . '/../../public/' . $existing_surat['file_surat']);
    }

    $stmt = $conn->prepare("DELETE FROM surat_keluar WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Handle file uploads for surat keluar.
 * @param array $file_data Data from $_FILES.
 * @param string $folder Subfolder within 'public/uploads/'.
 * @return string|null Path to the uploaded file or null on failure.
 */
function upload_file_surat_keluar($file_data, $folder) {
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
