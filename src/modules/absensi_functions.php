<?php
// src/modules/absensi_functions.php
require_once __DIR__ . '/../../config/database.php';

function get_all_absensi($conn, $bulan = null, $tahun = null, $pegawai_id = null) {
    $where = [];
    $params = []; $types = '';
    if ($bulan && $tahun) {
        $where[] = "MONTH(a.tanggal) = ? AND YEAR(a.tanggal) = ?";
        $params[] = $bulan; $params[] = $tahun; $types .= 'ii';
    }
    if ($pegawai_id) {
        $where[] = "a.pegawai_id = ?";
        $params[] = $pegawai_id; $types .= 'i';
    }
    $sql = "SELECT a.*, p.nama, p.nip, p.jabatan_fungsional, p.kepangkatan
            FROM absensi a
            JOIN pegawai p ON a.pegawai_id = p.id"
         . (!empty($where) ? " WHERE " . implode(" AND ", $where) : "")
         . " ORDER BY a.tanggal DESC, p.nama ASC";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $list = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    $stmt->close();
    return $list;
}

function get_absensi_hari_ini($conn, $pegawai_id) {
    $today = date('Y-m-d');
    $stmt  = $conn->prepare("SELECT * FROM absensi WHERE pegawai_id = ? AND tanggal = ?");
    $stmt->bind_param("is", $pegawai_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->num_rows > 0 ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

function absen_masuk($conn, $pegawai_id) {
    $today    = date('Y-m-d');
    $jam      = date('H:i:s');
    $existing = get_absensi_hari_ini($conn, $pegawai_id);
    if ($existing) return ['status' => 'sudah', 'msg' => 'Anda sudah melakukan absen masuk hari ini.'];
    $stmt = $conn->prepare(
        "INSERT INTO absensi (pegawai_id, tanggal, jam_masuk, status) VALUES (?, ?, ?, 'hadir')"
    );
    $stmt->bind_param("iss", $pegawai_id, $today, $jam);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok ? ['status' => 'ok', 'msg' => 'Absen masuk berhasil dicatat pukul ' . date('H:i')] : ['status' => 'error', 'msg' => 'Gagal mencatat absensi.'];
}

function absen_pulang($conn, $pegawai_id) {
    $today    = date('Y-m-d');
    $jam      = date('H:i:s');
    $existing = get_absensi_hari_ini($conn, $pegawai_id);
    if (!$existing) return ['status' => 'error', 'msg' => 'Anda belum melakukan absen masuk hari ini.'];
    if (!empty($existing['jam_keluar'])) return ['status' => 'sudah', 'msg' => 'Anda sudah melakukan absen pulang hari ini.'];
    $stmt = $conn->prepare("UPDATE absensi SET jam_keluar = ? WHERE pegawai_id = ? AND tanggal = ?");
    $stmt->bind_param("sis", $jam, $pegawai_id, $today);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok ? ['status' => 'ok', 'msg' => 'Absen pulang berhasil dicatat pukul ' . date('H:i')] : ['status' => 'error', 'msg' => 'Gagal mencatat absen pulang.'];
}

function admin_simpan_absensi($conn, $pegawai_id, $tanggal, $jam_masuk, $jam_keluar, $status, $keterangan) {
    $existing = null;
    $stmt = $conn->prepare("SELECT id FROM absensi WHERE pegawai_id = ? AND tanggal = ?");
    $stmt->bind_param("is", $pegawai_id, $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) $existing = $result->fetch_assoc();
    $stmt->close();

    $jam_masuk  = !empty($jam_masuk)  ? $jam_masuk  : null;
    $jam_keluar = !empty($jam_keluar) ? $jam_keluar : null;
    $keterangan = !empty($keterangan) ? $keterangan : null;

    if ($existing) {
        $stmt = $conn->prepare("UPDATE absensi SET jam_masuk=?, jam_keluar=?, status=?, keterangan=? WHERE pegawai_id=? AND tanggal=?");
        $stmt->bind_param("ssssis", $jam_masuk, $jam_keluar, $status, $keterangan, $pegawai_id, $tanggal);
    } else {
        $stmt = $conn->prepare("INSERT INTO absensi (pegawai_id, tanggal, jam_masuk, jam_keluar, status, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $pegawai_id, $tanggal, $jam_masuk, $jam_keluar, $status, $keterangan);
    }
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function delete_absensi($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM absensi WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function get_rekap_bulanan($conn, $bulan, $tahun) {
    // Semua pegawai + data absensi bulan tsb
    $sql = "SELECT p.id, p.nama, p.nip, p.jabatan_fungsional, p.kepangkatan, p.status_kepegawaian,
            COUNT(CASE WHEN a.status='hadir' THEN 1 END) as total_hadir,
            COUNT(CASE WHEN a.status='tidak_hadir' THEN 1 END) as total_tidak_hadir,
            COUNT(CASE WHEN a.status='izin' THEN 1 END) as total_izin,
            COUNT(CASE WHEN a.status='sakit' THEN 1 END) as total_sakit,
            COUNT(CASE WHEN a.status='cuti' THEN 1 END) as total_cuti,
            COUNT(a.id) as total_tercatat
            FROM pegawai p
            LEFT JOIN absensi a ON p.id = a.pegawai_id
              AND MONTH(a.tanggal) = ? AND YEAR(a.tanggal) = ?
            GROUP BY p.id
            ORDER BY p.nama ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $bulan, $tahun);
    $stmt->execute();
    $result = $stmt->get_result();
    $list   = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    $stmt->close();
    return $list;
}

function get_detail_absensi_pegawai($conn, $pegawai_id, $bulan, $tahun) {
    $stmt = $conn->prepare(
        "SELECT * FROM absensi WHERE pegawai_id = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ? ORDER BY tanggal ASC"
    );
    $stmt->bind_param("iii", $pegawai_id, $bulan, $tahun);
    $stmt->execute();
    $result = $stmt->get_result();
    $list   = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    $stmt->close();
    return $list;
}

// Akun Pegawai functions
function get_all_akun_pegawai($conn) {
    $result = $conn->query(
        "SELECT u.*, p.nama, p.nip, p.jabatan_fungsional FROM users u
         LEFT JOIN pegawai p ON u.pegawai_id = p.id
         WHERE u.role = 'pegawai' ORDER BY p.nama ASC"
    );
    $list = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    return $list;
}

function get_pegawai_tanpa_akun($conn) {
    $result = $conn->query(
        "SELECT p.* FROM pegawai p WHERE p.id NOT IN
         (SELECT pegawai_id FROM users WHERE pegawai_id IS NOT NULL AND role='pegawai')
         ORDER BY p.nama ASC"
    );
    $list = [];
    while ($row = $result->fetch_assoc()) $list[] = $row;
    return $list;
}
