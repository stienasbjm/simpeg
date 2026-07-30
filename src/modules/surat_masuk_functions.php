<?php
// src/modules/surat_masuk_functions.php

require_once __DIR__ . '/../../config/database.php';

/**
 * Get all incoming mails from the database.
 * @param mysqli $conn Database connection object.
 * @return array List of incoming mails.
 */
function get_all_surat_masuk($conn) {
    $sql = "SELECT * FROM surat_masuk ORDER BY tanggal_diterima DESC";
    $result = $conn->query($sql);
    $surat_masuk_list = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $surat_masuk_list[] = $row;
        }
    }
    return $surat_masuk_list;
}

/**
 * Get a single incoming mail by ID.
 * @param mysqli $conn Database connection object.
 * @param int $id Incoming mail ID.
 * @return array|null Incoming mail data or null if not found.
 */
function get_surat_masuk_by_id($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM surat_masuk WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

/**
 * Add a new incoming mail to the database.
 * @param mysqli $conn Database connection object.
 * @param array $data Incoming mail data.
 * @param array $file Uploaded file data.
 * @return bool True on success, false on failure.
 */
function add_surat_masuk($conn, $data, $file) {
    $file_surat = upload_file_surat($file, 'surat_masuk');

    $stmt = $conn->prepare("INSERT INTO surat_masuk (nomor_surat, tanggal_surat, tanggal_diterima, pengirim, perihal, file_surat) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "ssssss",
        $data['nomor_surat'], $data['tanggal_surat'], $data['tanggal_diterima'],
        $data['pengirim'], $data['perihal'], $file_surat
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Update an existing incoming mail.
 * @param mysqli $conn Database connection object.
 * @param int $id Incoming mail ID.
 * @param array $data Incoming mail data.
 * @param array $file Uploaded file data.
 * @return bool True on success, false on failure.
 */
function update_surat_masuk($conn, $id, $data, $file) {
    $existing_surat = get_surat_masuk_by_id($conn, $id);

    $file_surat = $existing_surat['file_surat'];
    if (isset($file) && $file['error'] == UPLOAD_ERR_OK) {
        $file_surat = upload_file_surat($file, 'surat_masuk');
        if ($existing_surat['file_surat'] && file_exists(__DIR__ . '/../../public/' . $existing_surat['file_surat'])) {
            unlink(__DIR__ . '/../../public/' . $existing_surat['file_surat']);
        }
    }

    $stmt = $conn->prepare("UPDATE surat_masuk SET nomor_surat=?, tanggal_surat=?, tanggal_diterima=?, pengirim=?, perihal=?, file_surat=? WHERE id=?");
    $stmt->bind_param(
        "ssssssi",
        $data['nomor_surat'], $data['tanggal_surat'], $data['tanggal_diterima'],
        $data['pengirim'], $data['perihal'], $file_surat, $id
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Delete an incoming mail from the database.
 * @param mysqli $conn Database connection object.
 * @param int $id Incoming mail ID.
 * @return bool True on success, false on failure.
 */
function delete_surat_masuk($conn, $id) {
    $existing_surat = get_surat_masuk_by_id($conn, $id);
    if (!$existing_surat) return false;

    if ($existing_surat['file_surat'] && file_exists(__DIR__ . '/../../public/' . $existing_surat['file_surat'])) {
        unlink(__DIR__ . '/../../public/' . $existing_surat['file_surat']);
    }

    $stmt = $conn->prepare("DELETE FROM surat_masuk WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Handle file uploads for surat.
 * @param array $file_data Data from $_FILES.
 * @param string $folder Subfolder within 'public/uploads/'.
 * @return string|null Path to the uploaded file or null on failure.
 */
function upload_file_surat($file_data, $folder) {
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
