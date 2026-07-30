<?php
// src/modules/keuangan_functions.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/absensi_functions.php';

/**
 * Ambil daftar transaksi kas berdasarkan filter jenis_kas, bulan, dan tahun
 * Termasuk otomatis sync pengeluaran penggajian pegawai (Kas Besar)
 */
function get_all_kas($conn, $jenis_kas = null, $bulan = null, $tahun = null) {
    $where = [];
    $params = [];
    $types = "";

    if (!empty($jenis_kas)) {
        $where[] = "jenis_kas = ?";
        $params[] = $jenis_kas;
        $types .= "s";
    }
    if (!empty($bulan)) {
        $where[] = "MONTH(tanggal) = ?";
        $params[] = $bulan;
        $types .= "i";
    }
    if (!empty($tahun)) {
        $where[] = "YEAR(tanggal) = ?";
        $params[] = $tahun;
        $types .= "i";
    }

    $sql = "SELECT k.*, u.nama_lengkap AS user_name FROM kas_transaksi k LEFT JOIN users u ON k.created_by = u.id";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY tanggal DESC, id DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $list = [];
    while ($row = $res->fetch_assoc()) {
        $list[] = $row;
    }
    $stmt->close();

    // Sync transaksi pengeluaran gaji pegawai (1 baris total per bulan, jenis kas_besar)
    if (empty($jenis_kas) || $jenis_kas === 'kas_besar') {
        $g_where = [];
        $g_params = [];
        $g_types = "";
        if (!empty($bulan)) {
            $g_where[] = "g.bulan = ?";
            $g_params[] = $bulan;
            $g_types .= "i";
        }
        if (!empty($tahun)) {
            $g_where[] = "g.tahun = ?";
            $g_params[] = $tahun;
            $g_types .= "i";
        }

        $g_sql = "SELECT g.bulan, g.tahun, SUM(g.total_gaji) AS total_pengeluaran_gaji, COUNT(g.id) AS jumlah_pegawai 
                  FROM gaji g 
                  WHERE g.total_gaji > 0";
        if (!empty($g_where)) {
            $g_sql .= " AND " . implode(" AND ", $g_where);
        }
        $g_sql .= " GROUP BY g.bulan, g.tahun";

        $g_stmt = $conn->prepare($g_sql);
        if (!empty($g_params)) {
            $g_stmt->bind_param($g_types, ...$g_params);
        }
        $g_stmt->execute();
        $g_res = $g_stmt->get_result();

        $nama_bulan_map = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        while ($g_row = $g_res->fetch_assoc()) {
            $bln_num = (int)$g_row['bulan'];
            $thn_num = (int)$g_row['tahun'];
            $bln_str = $nama_bulan_map[$bln_num] ?? $bln_num;
            $tgl_gaji = sprintf("%04d-%02d-28", $thn_num, $bln_num);
            $jml_peg  = (int)$g_row['jumlah_pegawai'];

            $list[] = [
                'id'           => 'gaji_total_' . $thn_num . '_' . $bln_num,
                'tanggal'      => $tgl_gaji,
                'jenis_kas'    => 'kas_besar',
                'tipe'         => 'pengeluaran',
                'kategori'     => 'Total Gaji & Tunjangan Pegawai',
                'keterangan'   => 'Pengeluaran Gaji Seluruh Pegawai Periode ' . $bln_str . ' ' . $thn_num . ' (' . $jml_peg . ' Pegawai)',
                'jumlah'       => (float)$g_row['total_pengeluaran_gaji'],
                'user_name'    => 'Sistem Penggajian',
                'is_auto_gaji' => true,
                'bulan'        => $bln_num,
                'tahun'        => $thn_num
            ];
        }
        $g_stmt->close();

        // Urutkan kembali berdasarkan tanggal DESC
        usort($list, function($a, $b) {
            return strcmp($b['tanggal'], $a['tanggal']);
        });
    }

    return $list;
}

/**
 * Hitung Saldo Akumulasi Kas Kecil, Kas Besar, dan Total Kas
 */
function get_kas_summary($conn) {
    $sql = "SELECT jenis_kas, tipe, SUM(jumlah) as total FROM kas_transaksi GROUP BY jenis_kas, tipe";
    $res = $conn->query($sql);
    
    $saldo = [
        'kas_kecil_masuk'  => 0,
        'kas_kecil_keluar' => 0,
        'kas_kecil_total'  => 0,
        'kas_besar_masuk'  => 0,
        'kas_besar_keluar' => 0,
        'kas_besar_total'  => 0,
        'total_kas'        => 0
    ];

    while ($row = $res->fetch_assoc()) {
        $jk = $row['jenis_kas'];
        $tp = $row['tipe'];
        $val = (float)$row['total'];

        if ($jk === 'kas_kecil') {
            if ($tp === 'pemasukan') $saldo['kas_kecil_masuk'] += $val;
            else $saldo['kas_kecil_keluar'] += $val;
        } elseif ($jk === 'kas_besar') {
            if ($tp === 'pemasukan') $saldo['kas_besar_masuk'] += $val;
            else $saldo['kas_besar_keluar'] += $val;
        }
    }

    // Sync Pengeluaran Penggajian Pegawai ke Kas Besar
    $sql_gaji = "SELECT SUM(total_gaji) as total_gaji_seluruh FROM gaji WHERE total_gaji > 0";
    $res_gaji = $conn->query($sql_gaji);
    if ($res_gaji && $r_gaji = $res_gaji->fetch_assoc()) {
        $saldo['kas_besar_keluar'] += (float)$r_gaji['total_gaji_seluruh'];
    }

    $saldo['kas_kecil_total'] = $saldo['kas_kecil_masuk'] - $saldo['kas_kecil_keluar'];
    $saldo['kas_besar_total'] = $saldo['kas_besar_masuk'] - $saldo['kas_besar_keluar'];
    $saldo['total_kas']       = $saldo['kas_kecil_total'] + $saldo['kas_besar_total'];

    return $saldo;
}

/**
 * Tambah transaksi kas baru
 */
function add_kas_transaksi($conn, $data, $user_id) {
    $stmt = $conn->prepare("INSERT INTO kas_transaksi (tanggal, jenis_kas, tipe, kategori, keterangan, jumlah, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "sssssdi",
        $data['tanggal'], $data['jenis_kas'], $data['tipe'],
        $data['kategori'], $data['keterangan'], $data['jumlah'], $user_id
    );
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Update transaksi kas
 */
function update_kas_transaksi($conn, $data) {
    $stmt = $conn->prepare("UPDATE kas_transaksi SET tanggal=?, jenis_kas=?, tipe=?, kategori=?, keterangan=?, jumlah=? WHERE id=?");
    $stmt->bind_param(
        "sssssdi",
        $data['tanggal'], $data['jenis_kas'], $data['tipe'],
        $data['kategori'], $data['keterangan'], $data['jumlah'], $data['id']
    );
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Hapus transaksi kas
 */
function delete_kas_transaksi($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM kas_transaksi WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Ambil daftar penggajian per periode
 */
function get_all_gaji($conn, $bulan, $tahun) {
    $sql = "SELECT g.id AS gaji_id, g.*,
                   p.id AS pegawai_id, p.nama, p.nip, p.kepangkatan, p.status_kepegawaian, p.jabatan_fungsional 
            FROM pegawai p 
            LEFT JOIN gaji g ON p.id = g.pegawai_id AND g.bulan = ? AND g.tahun = ?
            ORDER BY p.nama ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $bulan, $tahun);
    $stmt->execute();
    $res = $stmt->get_result();
    $list = [];
    while ($row = $res->fetch_assoc()) {
        // Hitung total hadir bulan ini dari absensi
        $rekap = get_detail_absensi_pegawai($conn, $row['pegawai_id'], $bulan, $tahun);
        $total_hadir = 0;
        foreach ($rekap as $d) {
            if (($d['status'] ?? '') === 'hadir') $total_hadir++;
        }
        $row['total_hadir'] = $total_hadir;
        $list[] = $row;
    }
    $stmt->close();
    return $list;
}

/**
 * Ambil single record penggajian berdasarkan ID
 */
function get_gaji_by_id($conn, $id) {
    $stmt = $conn->prepare("
        SELECT g.*, p.nama, p.nip, p.kepangkatan, p.status_kepegawaian, p.jabatan_fungsional, p.ijazah 
        FROM gaji g 
        JOIN pegawai p ON g.pegawai_id = p.id 
        WHERE g.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->num_rows > 0 ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

/**
 * Simpan atau perbarui data penggajian
 */
function save_gaji($conn, $data) {
    $gaji_pokok         = (float)($data['gaji_pokok'] ?? 0);
    $tunj_fungsional    = (float)($data['tunj_fungsional'] ?? 0);
    $tunj_struktural    = (float)($data['tunj_struktural'] ?? 0);
    $tunj_kesejahteraan = (float)($data['tunj_kesejahteraan'] ?? 0);
    $status_kawin       = (int)($data['status_kawin'] ?? 0);
    $tunj_kawin         = isset($data['tunj_kawin']) ? (float)$data['tunj_kawin'] : ($status_kawin ? 0.10 * $gaji_pokok : 0);
    $jumlah_anak        = (int)($data['jumlah_anak'] ?? 0);
    $tunj_anak          = isset($data['tunj_anak']) ? (float)$data['tunj_anak'] : (($jumlah_anak * 0.02) * $gaji_pokok);
    $tunj_beras         = (float)($data['tunj_beras'] ?? 200000);
    $tunjangan_makan    = (float)($data['tunjangan_makan'] ?? 0);
    $tunjangan_lain     = (float)($data['tunjangan_lain'] ?? 0);

    $pot_bpjs_tk        = isset($data['pot_bpjs_tk']) ? (float)$data['pot_bpjs_tk'] : (0.02 * $gaji_pokok);
    $pot_bpjs_kes       = isset($data['pot_bpjs_kes']) ? (float)$data['pot_bpjs_kes'] : (0.05 * $gaji_pokok);
    $pot_koperasi       = (float)($data['pot_koperasi'] ?? 20000);
    $potongan           = (float)($data['potongan'] ?? 0);

    $total_penerimaan   = $gaji_pokok + $tunj_fungsional + $tunj_struktural + $tunj_kesejahteraan + $tunj_kawin + $tunj_anak + $tunj_beras + $tunjangan_makan + $tunjangan_lain;
    $total_potongan     = $pot_bpjs_tk + $pot_bpjs_kes + $pot_koperasi + $potongan;
    $total_gaji         = $total_penerimaan - $total_potongan;

    $chk = $conn->prepare("SELECT id FROM gaji WHERE pegawai_id = ? AND bulan = ? AND tahun = ?");
    $chk->bind_param("iii", $data['pegawai_id'], $data['bulan'], $data['tahun']);
    $chk->execute();
    $exist = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($exist) {
        $stmt = $conn->prepare("UPDATE gaji SET gaji_pokok=?, tunj_fungsional=?, tunj_struktural=?, tunj_kesejahteraan=?, status_kawin=?, tunj_kawin=?, jumlah_anak=?, tunj_anak=?, tunj_beras=?, tunjangan_makan=?, tunjangan_lain=?, pot_bpjs_tk=?, pot_bpjs_kes=?, pot_koperasi=?, potongan=?, total_gaji=?, catatan=? WHERE id=?");
        $stmt->bind_param("ddddididddddddddsi", 
            $gaji_pokok, $tunj_fungsional, $tunj_struktural, $tunj_kesejahteraan, $status_kawin, $tunj_kawin, $jumlah_anak, $tunj_anak, $tunj_beras, $tunjangan_makan, $tunjangan_lain, 
            $pot_bpjs_tk, $pot_bpjs_kes, $pot_koperasi, $potongan, $total_gaji, $data['catatan'], $exist['id']
        );
    } else {
        $stmt = $conn->prepare("INSERT INTO gaji (pegawai_id, bulan, tahun, gaji_pokok, tunj_fungsional, tunj_struktural, tunj_kesejahteraan, status_kawin, tunj_kawin, jumlah_anak, tunj_anak, tunj_beras, tunjangan_makan, tunjangan_lain, pot_bpjs_tk, pot_bpjs_kes, pot_koperasi, potongan, total_gaji, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiddddididdddddddds", 
            $data['pegawai_id'], $data['bulan'], $data['tahun'],
            $gaji_pokok, $tunj_fungsional, $tunj_struktural, $tunj_kesejahteraan, $status_kawin, $tunj_kawin, $jumlah_anak, $tunj_anak, $tunj_beras, $tunjangan_makan, $tunjangan_lain, 
            $pot_bpjs_tk, $pot_bpjs_kes, $pot_koperasi, $potongan, $total_gaji, $data['catatan']
        );
    }
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Hapus record gaji
 */
function delete_gaji($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM gaji WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Ambil semua pengaturan penandatangan
 */
function get_pengaturan_ttd($conn) {
    $res = $conn->query("SELECT key_name, key_value FROM pengaturan_ttd");
    $data = [
        'ttd_bendahara_nama'    => 'Hj. Siti Rahmah, S.E.',
        'ttd_bendahara_nip'     => '198801012015032001',
        'ttd_bendahara_jabatan' => 'Bendahara Keuangan',
        'ttd_pimpinan_nama'     => 'Dr. H. A. Gazali, M.M.',
        'ttd_pimpinan_nip'      => '197502022003121001',
        'ttd_pimpinan_jabatan'  => 'Pimpinan / Ketua Instansi',
        'kota_terbit'           => 'Banjarmasin'
    ];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $data[$row['key_name']] = $row['key_value'];
        }
    }
    return $data;
}

/**
 * Simpan pengaturan penandatangan
 */
function save_pengaturan_ttd($conn, $input) {
    $keys = [
        'ttd_bendahara_nama',
        'ttd_bendahara_nip',
        'ttd_bendahara_jabatan',
        'ttd_pimpinan_nama',
        'ttd_pimpinan_nip',
        'ttd_pimpinan_jabatan',
        'kota_terbit'
    ];
    foreach ($keys as $k) {
        if (isset($input[$k])) {
            $val = trim($input[$k]);
            $stmt = $conn->prepare("INSERT INTO pengaturan_ttd (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)");
            $stmt->bind_param("ss", $k, $val);
            $stmt->execute();
            $stmt->close();
        }
    }
    return true;
}
