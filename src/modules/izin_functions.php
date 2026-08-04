<?php
// src/modules/izin_functions.php

require_once __DIR__ . '/../../config/database.php';

/**
 * Kirim pengajuan izin/sakit/dinas oleh pegawai.
 * Menangani upload file bukti.
 */
function kirim_pengajuan_izin($conn, $pegawai_id, $tanggal, $jenis, $keterangan, $file = null) {
    // Validasi jenis
    $jenis_valid = ['izin', 'sakit', 'dinas'];
    if (!in_array($jenis, $jenis_valid)) {
        return ['status' => 'error', 'msg' => 'Jenis pengajuan tidak valid.'];
    }
    if (empty($tanggal)) {
        return ['status' => 'error', 'msg' => 'Tanggal wajib diisi.'];
    }

    // Cek duplikat pengajuan di tanggal yang sama
    $stmt = $conn->prepare("SELECT id FROM pengajuan_izin WHERE pegawai_id = ? AND tanggal = ?");
    $stmt->bind_param("is", $pegawai_id, $tanggal);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        return ['status' => 'error', 'msg' => 'Anda sudah mengajukan izin/sakit/dinas untuk tanggal tersebut.'];
    }
    $stmt->close();

    // Handle upload file bukti
    $file_bukti = null;
    if (!empty($file) && $file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        $max_size      = 5 * 1024 * 1024; // 5 MB

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_types)) {
            return ['status' => 'error', 'msg' => 'Format file tidak didukung. Gunakan JPG, PNG, WebP, atau PDF.'];
        }
        if ($file['size'] > $max_size) {
            return ['status' => 'error', 'msg' => 'Ukuran file maksimal 5 MB.'];
        }

        $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $file_bukti = 'izin_' . $pegawai_id . '_' . date('Ymd') . '_' . uniqid() . '.' . $ext;
        $dest      = __DIR__ . '/../../public/uploads/izin/' . $file_bukti;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['status' => 'error', 'msg' => 'Gagal mengupload file bukti.'];
        }
    }

    // Simpan ke database
    $stmt = $conn->prepare(
        "INSERT INTO pengajuan_izin (pegawai_id, tanggal, jenis, keterangan, file_bukti)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issss", $pegawai_id, $tanggal, $jenis, $keterangan, $file_bukti);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        // Hapus file jika DB gagal
        if ($file_bukti && file_exists(__DIR__ . '/../../public/uploads/izin/' . $file_bukti)) {
            unlink(__DIR__ . '/../../public/uploads/izin/' . $file_bukti);
        }
        return ['status' => 'error', 'msg' => 'Gagal menyimpan pengajuan. Silakan coba lagi.'];
    }

    $label = ['izin' => 'Izin', 'sakit' => 'Sakit', 'dinas' => 'Dinas Luar'];
    return ['status' => 'ok', 'msg' => 'Pengajuan ' . ($label[$jenis] ?? $jenis) . ' berhasil dikirim dan menunggu persetujuan admin.'];
}

/**
 * Ambil semua pengajuan milik pegawai tertentu (untuk portal)
 */
function get_pengajuan_izin_by_pegawai($conn, $pegawai_id, $limit = 10) {
    $stmt = $conn->prepare(
        "SELECT * FROM pengajuan_izin WHERE pegawai_id = ?
         ORDER BY created_at DESC LIMIT ?"
    );
    $stmt->bind_param("ii", $pegawai_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $list   = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    $stmt->close();
    return $list;
}

/**
 * Ambil semua pengajuan (untuk admin)
 */
function get_all_pengajuan_izin($conn, $status = null) {
    $where  = $status ? "WHERE pi.status = ?" : "";
    $sql    = "SELECT pi.*, p.nama, p.nip, p.jabatan_fungsional
               FROM pengajuan_izin pi
               JOIN pegawai p ON pi.pegawai_id = p.id
               $where
               ORDER BY pi.created_at DESC";
    $stmt   = $conn->prepare($sql);
    if ($status) {
        $stmt->bind_param("s", $status);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $list   = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    $stmt->close();
    return $list;
}

/**
 * Admin: setujui atau tolak pengajuan.
 * Jika disetujui, otomatis membuat/memperbarui rekaman di tabel absensi.
 */
function update_status_pengajuan($conn, $id, $status, $catatan_admin = '') {
    $valid = ['disetujui', 'ditolak'];
    if (!in_array($status, $valid)) return false;

    // Ambil data pengajuan
    $stmt = $conn->prepare("SELECT * FROM pengajuan_izin WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $iz  = $res->fetch_assoc();
    $stmt->close();

    if (!$iz) return false;

    // Update status pengajuan
    $stmt = $conn->prepare(
        "UPDATE pengajuan_izin SET status = ?, catatan_admin = ? WHERE id = ?"
    );
    $stmt->bind_param("ssi", $status, $catatan_admin, $id);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok && $status === 'disetujui') {
        require_once __DIR__ . '/absensi_functions.php';
        // Map jenis pengajuan ke status absensi
        $map = [
            'izin'  => 'izin',
            'sakit' => 'sakit',
            'dinas' => 'izin' // Dinas luar masuk ke kategori izin pada rekap absensi
        ];
        $abs_status = $map[$iz['jenis']] ?? 'izin';
        $ket_prefix = strtoupper($iz['jenis']) . ': ';
        $ket_full   = $ket_prefix . ($iz['keterangan'] ?? '');

        admin_simpan_absensi(
            $conn,
            (int)$iz['pegawai_id'],
            $iz['tanggal'],
            null, // jam_masuk
            null, // jam_keluar
            $abs_status,
            $ket_full
        );
    }

    return $ok;
}
