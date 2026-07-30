<?php
// src/modules/pegawai_functions.php
require_once __DIR__ . '/../../config/database.php';

// ── Daftar Pangkat/Golongan PNS Indonesia ─────────────────────────
function get_pangkat_list() {
    return [
        'Golongan I' => [
            'Juru Muda (Ia)',    'Juru Muda Tk.I (Ib)',
            'Juru (Ic)',         'Juru Tk.I (Id)',
        ],
        'Golongan II' => [
            'Pengatur Muda (IIa)',    'Pengatur Muda Tk.I (IIb)',
            'Pengatur (IIc)',         'Pengatur Tk.I (IId)',
        ],
        'Golongan III' => [
            'Penata Muda (IIIa)',    'Penata Muda Tk.I (IIIb)',
            'Penata (IIIc)',         'Penata Tk.I (IIId)',
        ],
        'Golongan IV' => [
            'Pembina (IVa)',          'Pembina Tk.I (IVb)',
            'Pembina Utama Muda (IVc)',
            'Pembina Utama Madya (IVd)',
            'Pembina Utama (IVe)',
        ],
        'Non-PNS' => [
            'Tenaga Kontrak',
            'Tenaga Honorer',
            'PPPK',
        ],
    ];
}

// ── Hitung masa kerja ─────────────────────────────────────────────
function hitung_masa_kerja($tanggal_masuk) {
    if (empty($tanggal_masuk)) return '—';
    $mulai = new DateTime($tanggal_masuk);
    $now   = new DateTime();
    $diff  = $mulai->diff($now);
    $parts = [];
    if ($diff->y > 0) $parts[] = $diff->y . ' tahun';
    if ($diff->m > 0) $parts[] = $diff->m . ' bulan';
    if (empty($parts))  $parts[] = $diff->d . ' hari';
    return implode(' ', $parts);
}

// ── Hitung Status & Pemberitahuan Pensiun Pegawai ─────────────────
/**
 * Ketentuan Batas Usia Pensiun (BUP):
 * - 70 tahun: Guru Besar (Profesor)
 * - 65 tahun: Dosen pada umumnya (Asisten Ahli, Lektor, Lektor Kepala)
 * - 58 tahun: Tenaga Kependidikan (Tendik / Staf)
 */
function hitung_status_pensiun($tanggal_lahir, $status_kepegawaian = '', $jabatan_fungsional = '') {
    if (empty($tanggal_lahir) || $tanggal_lahir === '0000-00-00') {
        return [
            'usia'             => 0,
            'usia_detail'      => '—',
            'bup'              => 58,
            'kategori'         => 'Tenaga Kependidikan (58 thn)',
            'tgl_pensiun'      => '—',
            'sisa_tahun'       => 0,
            'sisa_bulan'       => 0,
            'total_sisa_bulan' => 999,
            'is_pensiun'       => false,
            'is_mendekati'     => false,
            'status_text'      => 'Belum diisi',
            'badge_class'      => 'secondary',
        ];
    }

    try {
        $birth = new DateTime($tanggal_lahir);
        $today = new DateTime();
    } catch (Exception $e) {
        return [
            'usia' => 0, 'usia_detail' => '—', 'bup' => 58, 'kategori' => 'Invalid',
            'tgl_pensiun' => '—', 'sisa_tahun' => 0, 'sisa_bulan' => 0, 'total_sisa_bulan' => 999,
            'is_pensiun' => false, 'is_mendekati' => false, 'status_text' => 'Tgl Lahir Invalid', 'badge_class' => 'secondary'
        ];
    }

    $diffAge = $birth->diff($today);
    $usia_tahun = $diffAge->y;
    $usia_bulan = $diffAge->m;
    $usia_detail = $usia_tahun . ' thn' . ($usia_bulan > 0 ? ' ' . $usia_bulan . ' bln' : '');

    // Penentuan BUP
    $st = strtolower($status_kepegawaian ?? '');
    $jf = strtolower($jabatan_fungsional ?? '');

    $bup = 58;
    $kategori = 'Tenaga Kependidikan (58 thn)';

    if (strpos($jf, 'guru besar') !== false || strpos($jf, 'profesor') !== false || strpos($jf, 'prof.') !== false || strpos($jf, 'prof') !== false) {
        $bup = 70;
        $kategori = 'Guru Besar (70 thn)';
    } elseif (strpos($st, 'dosen') !== false || strpos($jf, 'lektor') !== false || strpos($jf, 'asisten ahli') !== false || strpos($jf, 'dosen') !== false) {
        $bup = 65;
        $kategori = 'Dosen (65 thn)';
    } elseif (strpos($st, 'kependidikan') !== false || strpos($st, 'tendik') !== false || strpos($st, 'staf') !== false || strpos($st, 'admin') !== false) {
        $bup = 58;
        $kategori = 'Tenaga Kependidikan (58 thn)';
    }

    $pensionDate = (clone $birth)->modify("+$bup years");
    $is_pensiun  = ($today >= $pensionDate);
    $diffPension = $today->diff($pensionDate);

    $months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $tgl_pensiun_fmt = $pensionDate->format('d') . ' ' . ($months[(int)$pensionDate->format('n')] ?? '') . ' ' . $pensionDate->format('Y');

    if ($is_pensiun) {
        $overY = $diffPension->y;
        $overM = $diffPension->m;
        $sisa_tahun = -$overY;
        $sisa_bulan = -$overM;
        $total_sisa_bulan = -($overY * 12 + $overM);

        $over_str = [];
        if ($overY > 0) $over_str[] = "$overY thn";
        if ($overM > 0) $over_str[] = "$overM bln";
        $over_text = !empty($over_str) ? implode(' ', $over_str) : '0 bln';

        $status_text = "Sudah Pensiun ($over_text lalu)";
        $badge_class = "danger";
        $is_mendekati = true;
    } else {
        $sisa_y = $diffPension->y;
        $sisa_m = $diffPension->m;
        $total_sisa_bulan = ($sisa_y * 12) + $sisa_m;
        $sisa_tahun = $sisa_y;
        $sisa_bulan = $sisa_m;

        $sisa_str = [];
        if ($sisa_y > 0) $sisa_str[] = "$sisa_y thn";
        if ($sisa_m > 0) $sisa_str[] = "$sisa_m bln";
        if (empty($sisa_str)) $sisa_str[] = "< 1 bln";
        $sisa_text = implode(' ', $sisa_str);

        if ($total_sisa_bulan <= 12) {
            $status_text = "⚠️ Mendekati Pensiun (Sisa $sisa_text)";
            $badge_class = "danger";
            $is_mendekati = true;
        } elseif ($total_sisa_bulan <= 24) {
            $status_text = "⚡ Mendekati Pensiun (Sisa $sisa_text)";
            $badge_class = "warning";
            $is_mendekati = true;
        } elseif ($total_sisa_bulan <= 36) {
            $status_text = "Persiapan Pensiun (Sisa $sisa_text)";
            $badge_class = "amber";
            $is_mendekati = false;
        } else {
            $status_text = "Aktif (BUP $bup thn — Sisa $sisa_text)";
            $badge_class = "green";
            $is_mendekati = false;
        }
    }

    return [
        'usia'             => $usia_tahun,
        'usia_detail'      => $usia_detail,
        'bup'              => $bup,
        'kategori'         => $kategori,
        'tgl_pensiun'      => $tgl_pensiun_fmt,
        'sisa_tahun'       => $sisa_tahun,
        'sisa_bulan'       => $sisa_bulan,
        'total_sisa_bulan' => $total_sisa_bulan,
        'is_pensiun'       => $is_pensiun,
        'is_mendekati'     => $is_mendekati,
        'status_text'      => $status_text,
        'badge_class'      => $badge_class,
    ];
}


// ── CRUD Pegawai ──────────────────────────────────────────────────
function get_all_pegawai($conn) {
    $sql = "SELECT * FROM pegawai ORDER BY nama ASC";
    $result = $conn->query($sql);
    $list = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) $list[] = $row;
    }
    return $list;
}

function get_pegawai_by_id($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM pegawai WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

function add_pegawai($conn, $data, $files) {
    $file_ijazah             = upload_file($files['file_ijazah'],            'ijazah');
    $file_kepangkatan        = upload_file($files['file_kepangkatan'],       'kepangkatan');
    $file_jabatan_fungsional = upload_file($files['file_jabatan_fungsional'],'jabatan_fungsional');

    $stmt = $conn->prepare(
        "INSERT INTO pegawai (nama, tempat_lahir, tanggal_lahir, nip, kepangkatan, jabatan_fungsional,
         ijazah, status_kepegawaian, tanggal_masuk_kerja,
         file_ijazah, file_kepangkatan, file_jabatan_fungsional)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "ssssssssssss",
        $data['nama'], $data['tempat_lahir'], $data['tanggal_lahir'], $data['nip'],
        $data['kepangkatan'], $data['jabatan_fungsional'], $data['ijazah'],
        $data['status_kepegawaian'], $data['tanggal_masuk_kerja'],
        $file_ijazah, $file_kepangkatan, $file_jabatan_fungsional
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function update_pegawai($conn, $id, $data, $files) {
    $existing = get_pegawai_by_id($conn, $id);

    $file_ijazah = $existing['file_ijazah'];
    if (isset($files['file_ijazah']) && $files['file_ijazah']['error'] == UPLOAD_ERR_OK) {
        $file_ijazah = upload_file($files['file_ijazah'], 'ijazah');
        if ($existing['file_ijazah'] && file_exists(__DIR__ . '/../../public/' . $existing['file_ijazah']))
            unlink(__DIR__ . '/../../public/' . $existing['file_ijazah']);
    }

    $file_kepangkatan = $existing['file_kepangkatan'];
    if (isset($files['file_kepangkatan']) && $files['file_kepangkatan']['error'] == UPLOAD_ERR_OK) {
        $file_kepangkatan = upload_file($files['file_kepangkatan'], 'kepangkatan');
        if ($existing['file_kepangkatan'] && file_exists(__DIR__ . '/../../public/' . $existing['file_kepangkatan']))
            unlink(__DIR__ . '/../../public/' . $existing['file_kepangkatan']);
    }

    $file_jabatan_fungsional = $existing['file_jabatan_fungsional'];
    if (isset($files['file_jabatan_fungsional']) && $files['file_jabatan_fungsional']['error'] == UPLOAD_ERR_OK) {
        $file_jabatan_fungsional = upload_file($files['file_jabatan_fungsional'], 'jabatan_fungsional');
        if ($existing['file_jabatan_fungsional'] && file_exists(__DIR__ . '/../../public/' . $existing['file_jabatan_fungsional']))
            unlink(__DIR__ . '/../../public/' . $existing['file_jabatan_fungsional']);
    }

    $stmt = $conn->prepare(
        "UPDATE pegawai SET nama=?, tempat_lahir=?, tanggal_lahir=?, nip=?, kepangkatan=?,
         jabatan_fungsional=?, ijazah=?, status_kepegawaian=?, tanggal_masuk_kerja=?,
         file_ijazah=?, file_kepangkatan=?, file_jabatan_fungsional=? WHERE id=?"
    );
    $stmt->bind_param(
        "ssssssssssssi",
        $data['nama'], $data['tempat_lahir'], $data['tanggal_lahir'], $data['nip'],
        $data['kepangkatan'], $data['jabatan_fungsional'], $data['ijazah'],
        $data['status_kepegawaian'], $data['tanggal_masuk_kerja'],
        $file_ijazah, $file_kepangkatan, $file_jabatan_fungsional, $id
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function delete_pegawai($conn, $id) {
    $existing = get_pegawai_by_id($conn, $id);
    if (!$existing) return false;
    foreach (['file_ijazah','file_kepangkatan','file_jabatan_fungsional'] as $f) {
        if ($existing[$f] && file_exists(__DIR__ . '/../../public/' . $existing[$f]))
            unlink(__DIR__ . '/../../public/' . $existing[$f]);
    }
    $stmt = $conn->prepare("DELETE FROM pegawai WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function upload_file($file_data, $folder) {
    if (!isset($file_data) || $file_data['error'] !== UPLOAD_ERR_OK) return null;
    $target_dir = __DIR__ . '/../../public/uploads/' . $folder . '/';
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

    $original_name = basename($file_data['name']);
    $ext           = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $allowed_ext   = ['pdf', 'jpg', 'jpeg', 'png'];

    // 1. Cek Ekstensi Terlarang & Double Extension (misal: shell.php.png)
    if (!in_array($ext, $allowed_ext) || preg_match('/\.(php|phtml|php3|php4|php5|php7|phps|cgi|pl|exe|htaccess)\b/i', $original_name)) {
        $_SESSION['error_message'] = "Jenis file tidak diizinkan. Hanya PDF, JPG, JPEG, PNG.";
        return null;
    }

    // 2. Cek Ukuran Maksimal (5MB)
    if ($file_data['size'] > 5 * 1024 * 1024) {
        $_SESSION['error_message'] = "Ukuran file terlalu besar (maksimal 5MB).";
        return null;
    }

    // 3. Cek MIME Type Asli dengan FileInfo
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file_data['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = [
            'pdf'  => ['application/pdf'],
            'jpg'  => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png'  => ['image/png', 'image/x-png'],
        ];

        $valid_mime = false;
        foreach ($allowed_mimes[$ext] ?? [] as $m) {
            if (strtolower($mime) === strtolower($m)) {
                $valid_mime = true;
                break;
            }
        }

        if (!$valid_mime) {
            $_SESSION['error_message'] = "Konten file tidak sesuai dengan ekstensinya (MIME mismatch).";
            return null;
        }
    }

    // 4. Buat Nama Acak Aman (Hashed Name)
    $new_name = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
    $target   = $target_dir . $new_name;

    if (move_uploaded_file($file_data['tmp_name'], $target)) {
        chmod($target, 0644);
        return 'uploads/' . $folder . '/' . $new_name;
    }

    $_SESSION['error_message'] = "Gagal mengunggah file.";
    return null;
}
