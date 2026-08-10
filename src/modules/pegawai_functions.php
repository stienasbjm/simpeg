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


// ── Hitung Penyetaraan Tunjangan Profesi & Peringatan Kenaikan Pangkat Dosen ─
/**
 * Ketentuan Penyetaraan Tunjangan Profesi / Kenaikan Golongan Bagi Dosen Non-ASN:
 * - Asisten Ahli : TMT s.d. seterusnya -> IIIb
 * - Lektor       : TMT -> IIIb | TMT+1 s.d. TMT+3 -> IIIc | di atas TMT+3 -> IIId
 * - Lektor Kepala: TMT -> IIId | TMT+1 dst -> IVa (atau IVb/IVc bagi SK Inpassing s.d. 2025)
 * - Profesor     : TMT -> IVa | TMT+1 s.d. TMT+3 -> IVb | >TMT+3 s.d. TMT+5 -> IVc | >TMT+5 s.d. TMT+7 -> IVd | >TMT+7 -> IVe (+200 AK)
 */
function hitung_kenaikan_pangkat_dosen($pegawai) {
    if (empty($pegawai)) {
        return [
            'is_dosen'      => false,
            'is_due'        => false,
            'is_upcoming'   => false,
            'status_text'   => 'Bukan Dosen',
            'badge_class'   => 'secondary',
            'detail_msg'    => '',
            'catatan'       => ''
        ];
    }

    $st = strtolower($pegawai['status_kepegawaian'] ?? '');
    $jf = strtolower($pegawai['jabatan_fungsional'] ?? '');

    $is_dosen = (strpos($st, 'dosen') !== false ||
                 strpos($jf, 'dosen') !== false ||
                 strpos($jf, 'asisten ahli') !== false ||
                 strpos($jf, 'lektor') !== false ||
                 strpos($jf, 'profesor') !== false ||
                 strpos($jf, 'guru besar') !== false);

    if (!$is_dosen) {
        return [
            'is_dosen'      => false,
            'is_due'        => false,
            'is_upcoming'   => false,
            'status_text'   => 'Bukan Dosen',
            'badge_class'   => 'secondary',
            'detail_msg'    => '',
            'catatan'       => ''
        ];
    }

    // Normalisasi Jabatan Akademik
    $jabatan_norm = 'Lektor';
    if (strpos($jf, 'profesor') !== false || strpos($jf, 'guru besar') !== false || strpos($jf, 'prof') !== false) {
        $jabatan_norm = 'Profesor';
    } elseif (strpos($jf, 'lektor kepala') !== false) {
        $jabatan_norm = 'Lektor Kepala';
    } elseif (strpos($jf, 'lektor') !== false) {
        $jabatan_norm = 'Lektor';
    } elseif (strpos($jf, 'asisten ahli') !== false) {
        $jabatan_norm = 'Asisten Ahli';
    }

    // Prioritas TMT: tmt_jabatan -> tmt_pangkat -> tanggal_masuk_kerja
    $tmt_raw = !empty($pegawai['tmt_jabatan']) && $pegawai['tmt_jabatan'] !== '0000-00-00'
        ? $pegawai['tmt_jabatan']
        : (!empty($pegawai['tmt_pangkat']) && $pegawai['tmt_pangkat'] !== '0000-00-00'
            ? $pegawai['tmt_pangkat']
            : ($pegawai['tanggal_masuk_kerja'] ?? null));

    if (empty($tmt_raw) || $tmt_raw === '0000-00-00') {
        return [
            'is_dosen'           => true,
            'jabatan_norm'       => $jabatan_norm,
            'tmt_fmt'            => 'Belum Diisi',
            'masa_tahun'         => 0,
            'masa_bulan'         => 0,
            'masa_detail'        => 'TMT belum diisi',
            'target_golongan'    => '—',
            'golongan_saat_ini'  => $pegawai['kepangkatan'] ?? '—',
            'is_due'             => false,
            'is_upcoming'        => false,
            'status_text'        => '⚠️ TMT Belum Diisi',
            'badge_class'        => 'amber',
            'detail_msg'         => 'Data TMT Jabatan / Tanggal Masuk Bekerja belum diisi di sistem.',
            'catatan'            => 'Harap isi TMT Jabatan Akademik atau Tanggal Masuk Bekerja untuk menghitung penyetaraan golongan.'
        ];
    }

    try {
        $tmt_date = new DateTime($tmt_raw);
        $today    = new DateTime();
    } catch (Exception $e) {
        return [
            'is_dosen'      => true,
            'is_due'        => false,
            'is_upcoming'   => false,
            'status_text'   => 'TMT Invalid',
            'badge_class'   => 'secondary',
            'detail_msg'    => '',
            'catatan'       => ''
        ];
    }

    $diff = $tmt_date->diff($today);
    $years = $diff->y;
    $months = $diff->m;
    $total_months = ($years * 12) + $months;

    $masa_parts = [];
    if ($years > 0) $masa_parts[] = "$years thn";
    if ($months > 0) $masa_parts[] = "$months bln";
    if (empty($masa_parts)) $masa_parts[] = "0 bln";
    $masa_detail = implode(' ', $masa_parts);

    $months_list = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $tmt_fmt = $tmt_date->format('d') . ' ' . ($months_list[(int)$tmt_date->format('n')] ?? '') . ' ' . $tmt_date->format('Y');

    $sk_2025 = !empty($pegawai['sk_inpassing_2025']);

    $target_gol = 'IIIb';
    $next_target = null;
    $catatan = '';
    $next_threshold_months = 999;

    if ($jabatan_norm === 'Asisten Ahli') {
        $target_gol = 'IIIb';
        $catatan = 'Asisten Ahli berhak penyetaraan tunjangan profesi Golongan IIIb sejak TMT seterusnya. Rekomendasi kenaikan ke Lektor jika memenuhi kualifikasi.';
        if ($total_months >= 24) {
            $next_target = 'Lektor (IIIc)';
        }
    } elseif ($jabatan_norm === 'Lektor') {
        if ($years < 1) {
            $target_gol = 'IIIb';
            $next_target = 'IIIc';
            $next_threshold_months = 12 - $total_months;
            $catatan = 'Masa jabatan TMT baru (< 1 thn). Berhak penyetaraan IIIb.';
        } elseif ($years >= 1 && $years <= 3) {
            $target_gol = 'IIIc';
            $next_target = 'IIId';
            $next_threshold_months = 36 - $total_months;
            $catatan = 'Masa jabatan TMT +1 s.d. TMT +3 thn. Berhak penyetaraan IIIc.';
        } else {
            $target_gol = 'IIId';
            $catatan = 'Masa jabatan di atas TMT +3 thn. Berhak penyetaraan IIId.';
        }
    } elseif ($jabatan_norm === 'Lektor Kepala') {
        if ($years < 1) {
            $target_gol = 'IIId';
            $next_target = $sk_2025 ? 'IVb / IVc' : 'IVa';
            $next_threshold_months = 12 - $total_months;
            $catatan = 'Masa jabatan TMT baru (< 1 thn). Berhak penyetaraan IIId.';
        } else {
            $target_gol = $sk_2025 ? 'IVb' : 'IVa';
            $catatan = $sk_2025
                ? 'Masa jabatan TMT +1 dst dengan SK Inpassing s.d. 2025. Berhak penyetaraan IVb / IVc.'
                : 'Masa jabatan TMT +1 dst. Berhak penyetaraan IVa.';
        }
    } elseif ($jabatan_norm === 'Profesor') {
        if ($years < 1) {
            $target_gol = 'IVa';
            $next_target = 'IVb';
            $next_threshold_months = 12 - $total_months;
            $catatan = 'Masa jabatan Profesor TMT baru (< 1 thn). Berhak penyetaraan IVa.';
        } elseif ($years >= 1 && $years <= 3) {
            $target_gol = 'IVb';
            $next_target = 'IVc';
            $next_threshold_months = 36 - $total_months;
            $catatan = 'Masa jabatan Profesor TMT +1 s.d. TMT +3 thn. Berhak penyetaraan IVb.';
        } elseif ($years > 3 && $years <= 5) {
            $target_gol = 'IVc';
            $next_target = 'IVd';
            $next_threshold_months = 60 - $total_months;
            $catatan = 'Masa jabatan Profesor di atas TMT +3 s.d. TMT +5 thn. Berhak penyetaraan IVc.';
        } elseif ($years > 5 && $years <= 7) {
            $target_gol = 'IVd';
            $next_target = 'IVe';
            $next_threshold_months = 84 - $total_months;
            $catatan = 'Masa jabatan Profesor di atas TMT +5 s.d. TMT +7 thn. Berhak penyetaraan IVd.';
        } else {
            $target_gol = 'IVe';
            $catatan = 'Masa jabatan Profesor di atas TMT +7 thn s.d. Pensiun. Berhak penyetaraan IVe (Syarat tambahan: 200 Angka Kredit).';
        }
    }

    $gol_rank = [
        'Ia'=>1, 'Ib'=>2, 'Ic'=>3, 'Id'=>4,
        'IIa'=>5, 'IIb'=>6, 'IIc'=>7, 'IId'=>8,
        'IIIa'=>9, 'IIIb'=>10, 'IIIc'=>11, 'IIId'=>12,
        'IVa'=>13, 'IVb'=>14, 'IVc'=>15, 'IVd'=>16, 'IVe'=>17
    ];

    $curr_gol = 'IIIa';
    if (preg_match('/(I{1,3}|IV|V)[a-e]/i', $pegawai['kepangkatan'] ?? '', $matches)) {
        $found = strtolower($matches[0]);
        $map = ['iiia'=>'IIIa','iiib'=>'IIIb','iiic'=>'IIIc','iiid'=>'IIId','iva'=>'IVa','ivb'=>'IVb','ivc'=>'IVc','ivd'=>'IVd','ive'=>'IVe','iia'=>'IIa','iib'=>'IIb','iic'=>'IIc','iid'=>'IId','ia'=>'Ia','ib'=>'Ib','ic'=>'Ic','id'=>'Id'];
        $curr_gol = $map[$found] ?? strtoupper($found);
    } else {
        foreach ($gol_rank as $g_code => $val) {
            if (stripos($pegawai['kepangkatan'] ?? '', $g_code) !== false) {
                $curr_gol = $g_code;
                break;
            }
        }
    }

    $curr_level   = $gol_rank[$curr_gol] ?? 10;
    $target_level = $gol_rank[$target_gol] ?? 10;

    $is_due = false;
    $is_upcoming = false;

    if ($curr_level < $target_level) {
        $is_due = true;
        $status_text = "⚠️ Waktunya Naik Golongan (Target: $target_gol)";
        $badge_class = "danger";
        $detail_msg = "Golongan saat ini ($curr_gol) masih di bawah penyetaraan masa jabatan ($target_gol). Dosen yang bersangkutan sudah waktunya melakukan kenaikan pangkat/golongan.";
    } elseif ($curr_level == $target_level && $next_target && $next_threshold_months <= 3 && $next_threshold_months > 0) {
        $is_upcoming = true;
        $status_text = "⚡ Mendekati Penyetaraan ($next_target)";
        $badge_class = "warning";
        $detail_msg = "Mendekati jadwal penyetaraan / kenaikan ke $next_target dalam $next_threshold_months bulan lagi.";
    } else {
        $status_text = "✅ Golongan Sesuai Masa Jabatan ($curr_gol)";
        $badge_class = "green";
        $detail_msg = "Pangkat/Golongan saat ini ($curr_gol) sudah memenuhi standar penyetaraan Kemendikbud ($target_gol).";
    }

    return [
        'is_dosen'           => true,
        'jabatan_norm'       => $jabatan_norm,
        'tmt_fmt'            => $tmt_fmt,
        'masa_tahun'         => $years,
        'masa_bulan'         => $months,
        'masa_detail'        => $masa_detail,
        'target_golongan'    => $target_gol,
        'next_target'        => $next_target,
        'golongan_saat_ini'  => $curr_gol,
        'is_due'             => $is_due,
        'is_upcoming'        => $is_upcoming,
        'status_text'        => $status_text,
        'badge_class'        => $badge_class,
        'detail_msg'         => $detail_msg,
        'catatan'            => $catatan,
        'kategori_pegawai'   => 'Dosen',
    ];
}


// ── Hitung Peringatan Kenaikan Pangkat Reguler Bagi Tenaga Kependidikan (Tendik)
/**
 * Ketentuan Reguler Tendik: Kenaikan Pangkat/Golongan diproses setiap 4 tahun (48 bulan) dari TMT Pangkat.
 */
function hitung_kenaikan_pangkat_tendik($pegawai) {
    if (empty($pegawai)) {
        return [
            'is_dosen'          => false,
            'is_tendik'         => false,
            'is_due'            => false,
            'is_upcoming'       => false,
            'status_text'       => 'Tidak Aktif',
            'badge_class'       => 'secondary',
            'detail_msg'        => '',
            'catatan'           => '',
            'kategori_pegawai'  => 'Tenaga Kependidikan'
        ];
    }

    $tmt_raw = !empty($pegawai['tmt_pangkat']) && $pegawai['tmt_pangkat'] !== '0000-00-00'
        ? $pegawai['tmt_pangkat']
        : (!empty($pegawai['tanggal_masuk_kerja']) && $pegawai['tanggal_masuk_kerja'] !== '0000-00-00'
            ? $pegawai['tanggal_masuk_kerja']
            : null);

    if (empty($tmt_raw) || $tmt_raw === '0000-00-00') {
        return [
            'is_dosen'           => false,
            'is_tendik'          => true,
            'jabatan_norm'       => 'Tenaga Kependidikan',
            'tmt_fmt'            => 'Belum Diisi',
            'masa_tahun'         => 0,
            'masa_bulan'         => 0,
            'masa_detail'        => 'TMT belum diisi',
            'target_golongan'    => '—',
            'golongan_saat_ini'  => $pegawai['kepangkatan'] ?? '—',
            'is_due'             => false,
            'is_upcoming'        => false,
            'status_text'        => '⚠️ TMT Pangkat Belum Diisi',
            'badge_class'        => 'amber',
            'detail_msg'         => 'Data TMT Pangkat atau Tanggal Masuk Bekerja belum diisi di sistem.',
            'catatan'            => 'Harap isi TMT Pangkat/Golongan untuk menghitung periode 4 tahun kenaikan pangkat reguler.',
            'kategori_pegawai'   => 'Tenaga Kependidikan'
        ];
    }

    try {
        $tmt_date = new DateTime($tmt_raw);
        $today    = new DateTime();
    } catch (Exception $e) {
        return [
            'is_dosen'          => false,
            'is_tendik'         => true,
            'is_due'            => false,
            'is_upcoming'       => false,
            'status_text'       => 'TMT Invalid',
            'badge_class'       => 'secondary',
            'detail_msg'        => '',
            'catatan'           => '',
            'kategori_pegawai'  => 'Tenaga Kependidikan'
        ];
    }

    $diff = $tmt_date->diff($today);
    $years = $diff->y;
    $months = $diff->m;
    $total_months = ($years * 12) + $months;

    $masa_parts = [];
    if ($years > 0) $masa_parts[] = "$years thn";
    if ($months > 0) $masa_parts[] = "$months bln";
    if (empty($masa_parts)) $masa_parts[] = "0 bln";
    $masa_detail = implode(' ', $masa_parts);

    $months_list = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $tmt_fmt = $tmt_date->format('d') . ' ' . ($months_list[(int)$tmt_date->format('n')] ?? '') . ' ' . $tmt_date->format('Y');

    $next_map = [
        'Ia'=>'Ib', 'Ib'=>'Ic', 'Ic'=>'Id', 'Id'=>'IIa',
        'IIa'=>'IIb', 'IIb'=>'IIc', 'IIc'=>'IId', 'IId'=>'IIIa',
        'IIIa'=>'IIIb', 'IIIb'=>'IIIc', 'IIIc'=>'IIId', 'IIId'=>'IVa',
        'IVa'=>'IVb', 'IVb'=>'IVc', 'IVc'=>'IVd', 'IVd'=>'IVe', 'IVe'=>'IVe'
    ];

    $curr_gol = 'IIa';
    if (preg_match('/(I{1,3}|IV|V)[a-e]/i', $pegawai['kepangkatan'] ?? '', $matches)) {
        $found = strtolower($matches[0]);
        $map = ['iiia'=>'IIIa','iiib'=>'IIIb','iiic'=>'IIIc','iiid'=>'IIId','iva'=>'IVa','ivb'=>'IVb','ivc'=>'IVc','ivd'=>'IVd','ive'=>'IVe','iia'=>'IIa','iib'=>'IIb','iic'=>'IIc','iid'=>'IId','ia'=>'Ia','ib'=>'Ib','ic'=>'Ic','id'=>'Id'];
        $curr_gol = $map[$found] ?? strtoupper($found);
    }

    $target_gol = $next_map[$curr_gol] ?? $curr_gol;

    $is_due = false;
    $is_upcoming = false;

    if ($total_months >= 48) { // >= 4 tahun
        $is_due = true;
        $status_text = "⚠️ Waktunya Naik Pangkat (Target: $target_gol)";
        $badge_class = "danger";
        $detail_msg = "Masa pangkat saat ini sudah mencapai $masa_detail (>= 4 tahun TMT). Tenaga kependidikan yang bersangkutan sudah waktunya diusulkan kenaikan pangkat/golongan reguler ke $target_gol.";
        $catatan = "Ketentuan Reguler Tendik: Kenaikan pangkat diusulkan setiap 4 tahun sekali berdasarkan penilaian kinerja (SKP) dan kelengkapan berkas.";
    } elseif ($total_months >= 42) { // 3.5 - 4 tahun (sisa <= 6 bulan)
        $sisa_bln = 48 - $total_months;
        $is_upcoming = true;
        $status_text = "⚡ Mendekati Naik Pangkat (Sisa $sisa_bln bln)";
        $badge_class = "warning";
        $detail_msg = "Mendekati masa 4 tahun kenaikan pangkat/golongan reguler ($target_gol) dalam $sisa_bln bulan lagi.";
        $catatan = "Persiapkan kelengkapan berkas administrasi dan Penilaian Prestasi Kerja (SKP) menjelang periode usulan kenaikan pangkat.";
    } else {
        $sisa_bln = 48 - $total_months;
        $status_text = "✅ Pangkat/Golongan Aktif ($curr_gol)";
        $badge_class = "green";
        $detail_msg = "Pangkat/Golongan saat ini ($curr_gol) masih aktif (Masa Pangkat: $masa_detail). Kenaikan pangkat reguler berikutnya diperkirakan $sisa_bln bulan lagi.";
        $catatan = "Kenaikan Pangkat Reguler berikutnya ($target_gol) akan diusulkan setelah genap 4 tahun TMT Pangkat.";
    }

    return [
        'is_dosen'           => false,
        'is_tendik'          => true,
        'jabatan_norm'       => !empty($pegawai['jabatan_fungsional']) ? $pegawai['jabatan_fungsional'] : 'Tenaga Kependidikan',
        'tmt_fmt'            => $tmt_fmt,
        'masa_tahun'         => $years,
        'masa_bulan'         => $months,
        'masa_detail'        => $masa_detail,
        'target_golongan'    => $target_gol,
        'next_target'        => $target_gol,
        'golongan_saat_ini'  => $curr_gol,
        'is_due'             => $is_due,
        'is_upcoming'        => $is_upcoming,
        'status_text'        => $status_text,
        'badge_class'        => $badge_class,
        'detail_msg'         => $detail_msg,
        'catatan'            => $catatan,
        'kategori_pegawai'   => 'Tenaga Kependidikan'
    ];
}

/**
 * Wrapper Utama: Hitung Kenaikan Pangkat Pegawai (Dosen & Tenaga Kependidikan)
 */
function hitung_kenaikan_pangkat_pegawai($pegawai) {
    $kd = hitung_kenaikan_pangkat_dosen($pegawai);
    if ($kd['is_dosen']) {
        return $kd;
    }
    return hitung_kenaikan_pangkat_tendik($pegawai);
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
        "INSERT INTO pegawai (nama, tempat_lahir, tanggal_lahir, nip, kepangkatan, tmt_pangkat, jabatan_fungsional, tmt_jabatan, sk_inpassing_2025,
         ijazah, status_kepegawaian, tanggal_masuk_kerja,
         file_ijazah, file_kepangkatan, file_jabatan_fungsional)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "ssssssssissssss",
        $data['nama'], $data['tempat_lahir'], $data['tanggal_lahir'], $data['nip'],
        $data['kepangkatan'], $data['tmt_pangkat'], $data['jabatan_fungsional'], $data['tmt_jabatan'], $data['sk_inpassing_2025'], $data['ijazah'],
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
        "UPDATE pegawai SET nama=?, tempat_lahir=?, tanggal_lahir=?, nip=?, kepangkatan=?, tmt_pangkat=?,
         jabatan_fungsional=?, tmt_jabatan=?, sk_inpassing_2025=?, ijazah=?, status_kepegawaian=?, tanggal_masuk_kerja=?,
         file_ijazah=?, file_kepangkatan=?, file_jabatan_fungsional=? WHERE id=?"
    );
    $stmt->bind_param(
        "ssssssssissssssi",
        $data['nama'], $data['tempat_lahir'], $data['tanggal_lahir'], $data['nip'],
        $data['kepangkatan'], $data['tmt_pangkat'], $data['jabatan_fungsional'], $data['tmt_jabatan'], $data['sk_inpassing_2025'], $data['ijazah'],
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
