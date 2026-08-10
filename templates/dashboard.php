<?php
// templates/dashboard.php — Dashboard Utama System (Dengan Tampilan Khusus Bendahara)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/pegawai_functions.php';
require_once __DIR__ . '/../src/modules/surat_masuk_functions.php';
require_once __DIR__ . '/../src/modules/surat_keluar_functions.php';
require_once __DIR__ . '/../src/modules/sk_functions.php';
require_once __DIR__ . '/../src/modules/keuangan_functions.php';

$user_info    = get_user_info();
$nama_lengkap = $user_info ? $user_info['nama_lengkap'] : 'Pengguna';
$user_role    = $user_info['role'] ?? 'admin';
$is_bendahara_role = is_bendahara();
$is_admin_role = is_admin();

$hour = (int)date('G');
$greeting = $hour < 12 ? 'Selamat pagi' : ($hour < 17 ? 'Selamat siang' : 'Selamat malam');

// Ambil data TTD
$ttd = get_pengaturan_ttd($conn);

if ($is_bendahara_role):
    // ══════════════════════════════════════════════════════════════════════════
    // DASHBOARD KHUSUS BENDAHARA KEUANGAN
    // ══════════════════════════════════════════════════════════════════════════
    $bulan_ini = (int)date('n');
    $tahun_ini = (int)date('Y');
    $kas_summary = get_kas_summary($conn);
    $daftar_gaji = get_all_gaji($conn, $bulan_ini, $tahun_ini);
    $kas_terbaru = get_all_kas($conn, '', $bulan_ini, $tahun_ini);
    $kas_terbaru = array_slice($kas_terbaru, 0, 5);

    $total_anggaran_gaji = 0;
    $pegawai_tergaji = 0;
    foreach ($daftar_gaji as $g) {
        $total_anggaran_gaji += (float)($g['total_gaji'] ?? 0);
        if (!empty($g['gaji_id'])) $pegawai_tergaji++;
    }
    $nama_bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                   7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
?>

<!-- Page Header Bendahara -->
<div class="e-page-header">
  <div>
    <div class="e-breadcrumb">
      <i class="bi bi-house-fill" style="font-size:.7rem;"></i>
      <span>Beranda</span>
      <span class="e-breadcrumb-sep">/</span>
      <span style="color:var(--green);font-weight:600;">Dashboard Keuangan Bendahara</span>
    </div>
    <h1 class="e-page-title">
      <div class="title-icon" style="background:linear-gradient(135deg,rgba(16,185,129,.15),rgba(5,150,105,.08));color:var(--green);">
        <i class="bi bi-wallet2"></i>
      </div>
      Dashboard Keuangan Bendahara
    </h1>
    <p class="e-page-sub"><?php echo $greeting; ?>, <strong><?php echo htmlspecialchars($nama_lengkap); ?></strong>. Kontrol penuh Arus Kas, Penggajian, dan Slip Gaji.</p>
  </div>
  <div style="display:flex;align-items:center;gap:.75rem;">
    <button type="button" class="e-btn e-btn-ghost" style="background:var(--bg-card);border:1px solid var(--border-medium);" onclick="openModalTTD()">
      <i class="bi bi-pen-fill" style="color:var(--indigo);"></i> Pengaturan NIP Penandatangan
    </button>
    <div class="e-date-badge">
      <i class="bi bi-calendar3" style="color:var(--green);"></i>
      <?php echo date('d F Y'); ?>
    </div>
  </div>
</div>

<?php if (!empty($_SESSION['success_message'])): ?>
<div class="e-notice success" data-dismiss><i class="bi bi-check-circle-fill"></i><span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span></div>
<?php endif; ?>

<!-- Financial Stat Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="e-stat s-blue">
      <div class="e-stat-icon blue"><i class="bi bi-wallet2"></i></div>
      <div class="e-stat-num" style="font-size:1.35rem;">Rp <?php echo number_format($kas_summary['kas_kecil_total'], 0, ',', '.'); ?></div>
      <div class="e-stat-lbl">Saldo Kas Kecil</div>
      <a href="<?php echo BASE_URL; ?>kas?jenis_kas=kas_kecil" class="e-stat-link" style="color:var(--blue);">Lihat Kas Kecil <i class="bi bi-arrow-right"></i></a>
    </div>
  </div>

  <div class="col-6 col-xl-3">
    <div class="e-stat s-green">
      <div class="e-stat-icon green"><i class="bi bi-bank"></i></div>
      <div class="e-stat-num" style="font-size:1.35rem;">Rp <?php echo number_format($kas_summary['kas_besar_total'], 0, ',', '.'); ?></div>
      <div class="e-stat-lbl">Saldo Kas Besar</div>
      <a href="<?php echo BASE_URL; ?>kas?jenis_kas=kas_besar" class="e-stat-link" style="color:var(--green);">Lihat Kas Besar <i class="bi bi-arrow-right"></i></a>
    </div>
  </div>

  <div class="col-6 col-xl-3">
    <div class="e-stat s-purple">
      <div class="e-stat-icon purple"><i class="bi bi-cash-coin"></i></div>
      <div class="e-stat-num" style="font-size:1.35rem;">Rp <?php echo number_format($kas_summary['total_kas'], 0, ',', '.'); ?></div>
      <div class="e-stat-lbl">Total Seluruh Kas</div>
      <a href="<?php echo BASE_URL; ?>kas" class="e-stat-link" style="color:var(--purple);">Arus Kas Instansi <i class="bi bi-arrow-right"></i></a>
    </div>
  </div>

  <div class="col-6 col-xl-3">
    <div class="e-stat s-amber">
      <div class="e-stat-icon amber"><i class="bi bi-cash-stack"></i></div>
      <div class="e-stat-num" style="font-size:1.35rem;">Rp <?php echo number_format($total_anggaran_gaji, 0, ',', '.'); ?></div>
      <div class="e-stat-lbl">Gaji (<?php echo $nama_bulan[$bulan_ini]; ?>)</div>
      <a href="<?php echo BASE_URL; ?>gaji" class="e-stat-link" style="color:var(--amber);">Kelola Penggajian <i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
</div>

<!-- Aksi Cepat Bendahara & Riwayat -->
<div class="row g-3 mb-4">
  <div class="col-lg-5">
    <div class="e-card" style="height:100%;">
      <div class="e-card-header">
        <div class="e-card-title"><i class="bi bi-lightning-charge-fill" style="color:var(--green);"></i> Aksi Cepat Keuangan</div>
      </div>
      <div class="e-card-body">
        <div class="d-grid gap-2">
          <a href="<?php echo BASE_URL; ?>kas" class="e-btn e-btn-primary" style="justify-content:flex-start;padding:.75rem 1rem;">
            <i class="bi bi-plus-circle-fill" style="font-size:1.1rem;"></i> Kelola & Input Transaksi Kas
          </a>
          <a href="<?php echo BASE_URL; ?>gaji" class="e-btn e-btn-ghost" style="justify-content:flex-start;padding:.75rem 1rem;background:var(--bg-muted);">
            <i class="bi bi-cash-stack" style="font-size:1.1rem;color:var(--green);"></i> Input & Edit Gaji Pegawai
          </a>
          <a href="<?php echo BASE_URL; ?>slip_gaji_print?all=1&bulan=<?php echo $bulan_ini; ?>&tahun=<?php echo $tahun_ini; ?>" target="_blank" class="e-btn e-btn-ghost" style="justify-content:flex-start;padding:.75rem 1rem;background:rgba(16,185,129,.1);color:var(--green);">
            <i class="bi bi-printer-fill" style="font-size:1.1rem;"></i> Cetak Semua Slip Gaji (Bulan Ini)
          </a>
          <a href="<?php echo BASE_URL; ?>kas_export?bulan=<?php echo $bulan_ini; ?>&tahun=<?php echo $tahun_ini; ?>" class="e-btn e-btn-ghost" style="justify-content:flex-start;padding:.75rem 1rem;background:rgba(37,99,235,.1);color:var(--blue);">
            <i class="bi bi-file-earmark-excel-fill" style="font-size:1.1rem;"></i> Export Laporan Kas Excel
          </a>
          <button type="button" class="e-btn e-btn-ghost" style="justify-content:flex-start;padding:.75rem 1rem;background:rgba(124,58,237,.1);color:var(--purple);" onclick="openModalTTD()">
            <i class="bi bi-pen-fill" style="font-size:1.1rem;"></i> Ubah Pejabat Penandatangan (Nama/NIP)
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="e-card" style="height:100%;">
      <div class="e-card-header">
        <div class="e-card-title"><i class="bi bi-receipt" style="color:var(--blue);"></i> Transaksi Kas Terbaru</div>
        <a href="<?php echo BASE_URL; ?>kas" class="e-card-link">Lihat semua <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="e-card-body" style="padding:0;">
        <div style="overflow-x:auto;">
          <table class="e-table" style="font-size:.85rem;">
            <thead><tr>
              <th>Tanggal</th>
              <th>Kas</th>
              <th>Kategori</th>
              <th style="text-align:right;">Nominal (Rp)</th>
            </tr></thead>
            <tbody>
              <?php if (!empty($kas_terbaru)): foreach ($kas_terbaru as $kt): ?>
              <tr>
                <td><?php echo date('d/m/Y', strtotime($kt['tanggal'])); ?></td>
                <td><span class="e-badge-mono"><?php echo $kt['jenis_kas']==='kas_kecil'?'Kecil':'Besar'; ?></span></td>
                <td style="font-weight:600;"><?php echo htmlspecialchars($kt['kategori']); ?></td>
                <td style="text-align:right;font-weight:700;color:<?php echo $kt['tipe']==='pemasukan'?'var(--green)':'var(--red)'; ?>;">
                  <?php echo $kt['tipe']==='pemasukan'?'+':'-'; ?> Rp <?php echo number_format($kt['jumlah'], 0, ',', '.'); ?>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="4"><div class="e-empty"><p>Belum ada transaksi kas bulan ini.</p></div></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php 
endif; 

if ($is_admin_role): 
    // ══════════════════════════════════════════════════════════════════════════
    // DASHBOARD ADMINISTRATOR & DEVELOPER
    // ══════════════════════════════════════════════════════════════════════════
    $total_sm   = count(get_all_surat_masuk($conn));
    $total_sk2  = count(get_all_surat_keluar($conn));
    $total_sk   = count(get_all_sk($conn));
    $total_peg  = count(get_all_pegawai($conn));
?>

<!-- Page Header Admin -->
<div class="e-page-header">
  <div>
    <div class="e-breadcrumb">
      <i class="bi bi-house-fill" style="font-size:.7rem;"></i>
      <span>Beranda</span>
      <span class="e-breadcrumb-sep">/</span>
      <span style="color:var(--indigo);font-weight:600;">Dashboard</span>
    </div>
    <h1 class="e-page-title">
      <div class="title-icon" style="background:linear-gradient(135deg,rgba(99,102,241,.15),rgba(167,139,250,.08));color:var(--indigo);">
        <i class="bi bi-grid-1x2-fill"></i>
      </div>
      Dashboard
    </h1>
    <p class="e-page-sub"><?php echo $greeting; ?>, <strong><?php echo htmlspecialchars($nama_lengkap); ?></strong>. Berikut ringkasan sistem hari ini.</p>
  </div>
  <div style="display:flex;align-items:center;gap:.75rem;">
    <button type="button" class="e-btn e-btn-ghost" style="background:var(--bg-card);border:1px solid var(--border-medium);" onclick="openModalTTD()">
      <i class="bi bi-pen-fill" style="color:var(--indigo);"></i> Pengaturan NIP Penandatangan
    </button>
    <div class="e-date-badge">
      <i class="bi bi-calendar3" style="color:var(--indigo);"></i>
      <?php echo date('d F Y'); ?>
    </div>
  </div>
</div>

<?php if (!empty($_SESSION['success_message'])): ?>
<div class="e-notice success" data-dismiss><i class="bi bi-check-circle-fill"></i><span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span></div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
  <?php
  $stats = [
    ['s-blue',  'blue',  'bi-inbox-fill',            $total_sm,  'Surat Masuk',     'surat_masuk',  'var(--blue)'],
    ['s-green', 'green', 'bi-send-fill',              $total_sk2, 'Surat Keluar',    'surat_keluar', 'var(--green)'],
    ['s-amber', 'amber', 'bi-file-earmark-text-fill', $total_sk,  'Surat Keputusan', 'sk',           'var(--amber)'],
    ['s-purple','purple','bi-people-fill',             $total_peg, 'Data Pegawai',    'pegawai',      'var(--purple)'],
  ];
  foreach ($stats as [$sclass, $iclass, $icon, $num, $label, $page, $color]):
  ?>
  <div class="col-6 col-xl-3">
    <div class="e-stat <?php echo $sclass; ?>">
      <div class="e-stat-icon <?php echo $iclass; ?>"><i class="bi <?php echo $icon; ?>"></i></div>
      <div class="e-stat-num"><?php echo number_format($num); ?></div>
      <div class="e-stat-lbl"><?php echo $label; ?></div>
      <a href="<?php echo BASE_URL . $page; ?>"
         class="e-stat-link" style="color:<?php echo $color; ?>">
        Lihat semua <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php
// Ambil daftar dosen yang sudah waktunya/mendekati kenaikan pangkat
$all_pegawai_dash = get_all_pegawai($conn);
$dosen_alerts = [];
foreach ($all_pegawai_dash as $peg) {
    $kd = hitung_kenaikan_pangkat_dosen($peg);
    if ($kd['is_dosen'] && ($kd['is_due'] || $kd['is_upcoming'])) {
        $dosen_alerts[] = [
            'pegawai' => $peg,
            'kd'      => $kd
        ];
    }
}
?>

<?php if (!empty($dosen_alerts)): ?>
<!-- Widget Peringatan Kenaikan Pangkat/Golongan Dosen -->
<div class="e-card mb-4" style="border:1px solid rgba(99,102,241,0.3);">
  <div class="e-card-header" style="background:linear-gradient(135deg, rgba(99,102,241,0.08), rgba(168,85,247,0.04));">
    <div class="e-card-title">
      <i class="bi bi-mortarboard-fill" style="color:var(--indigo); font-size:1.15rem;"></i>
      Peringatan Kenaikan Pangkat / Golongan Dosen (Penyetaraan Kemendikbud)
    </div>
    <span class="e-badge danger"><?php echo count($dosen_alerts); ?> Dosen Perlu Perhatian</span>
  </div>
  <div class="e-card-body" style="padding:0;">
    <div style="overflow-x:auto;">
      <table class="e-table" style="font-size:.85rem;">
        <thead>
          <tr>
            <th>Nama Dosen & NIP</th>
            <th>Jabatan Akademik</th>
            <th>TMT Jabatan / Masa</th>
            <th>Gol. Saat Ini</th>
            <th>Penyetaraan Tunjangan (Target)</th>
            <th>Status Kenaikan Pangkat</th>
            <th style="text-align:right;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($dosen_alerts as $item): $p = $item['pegawai']; $k = $item['kd']; ?>
          <tr>
            <td>
              <strong style="color:var(--text-primary);display:block;"><?php echo htmlspecialchars($p['nama']); ?></strong>
              <span class="e-badge-mono" style="font-size:.7rem;"><?php echo htmlspecialchars($p['nip']); ?></span>
            </td>
            <td><span class="e-badge purple"><?php echo htmlspecialchars($k['jabatan_norm']); ?></span></td>
            <td>
              <div><?php echo $k['tmt_fmt']; ?></div>
              <div style="font-size:.72rem; color:var(--indigo); font-weight:600;">Masa: <?php echo $k['masa_detail']; ?></div>
            </td>
            <td><span class="e-badge-mono"><?php echo htmlspecialchars($k['golongan_saat_ini']); ?></span></td>
            <td><span class="e-badge green" style="font-weight:700;">Gol. <?php echo htmlspecialchars($k['target_golongan']); ?></span></td>
            <td>
              <span class="e-badge <?php echo $k['badge_class']; ?>" style="font-size:.75rem; padding:.35rem .65rem;">
                <?php echo htmlspecialchars($k['status_text']); ?>
              </span>
            </td>
            <td style="text-align:right;">
              <a href="<?php echo BASE_URL; ?>pegawai_detail?id=<?php echo $p['id']; ?>" class="e-btn e-btn-ghost" style="padding:.25rem .55rem; font-size:.75rem;">
                <i class="bi bi-eye-fill"></i> Detail Dosen
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Quick Actions + System Status -->
<div class="row g-3">
  <div class="col-lg-7">
    <div class="e-card">
      <div class="e-card-header">
        <div class="e-card-title">
          <i class="bi bi-lightning-charge-fill" style="color:var(--amber);"></i>
          Aksi Cepat
        </div>
        <span class="e-badge indigo">4 Menu</span>
      </div>
      <div class="e-card-body">
        <div class="row g-2">
          <?php
          $tiles = [
            ['bi-inbox-fill',            'Surat Masuk Baru',  'surat_masuk_add',  'var(--blue)',   'rgba(79,142,247,.12)'],
            ['bi-send-fill',             'Surat Keluar Baru', 'surat_keluar_add', 'var(--green)',  'rgba(16,185,129,.12)'],
            ['bi-file-earmark-plus-fill','Tambah SK',          'sk_add',           'var(--amber)',  'rgba(245,158,11,.12)'],
            ['bi-person-plus-fill',      'Tambah Pegawai',    'pegawai_add',      'var(--purple)', 'rgba(139,92,246,.12)'],
          ];
          foreach ($tiles as [$ico, $lbl, $pg, $col, $bg]):
          ?>
          <div class="col-6 col-sm-3">
            <a class="e-tile" href="<?php echo BASE_URL . $pg; ?>">
              <div class="tile-icon" style="background:<?php echo $bg; ?>;color:<?php echo $col; ?>;">
                <i class="bi <?php echo $ico; ?>"></i>
              </div>
              <span><?php echo $lbl; ?></span>
            </a>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="e-card" style="height:100%;">
      <div class="e-card-header">
        <div class="e-card-title">
          <i class="bi bi-info-circle-fill" style="color:var(--blue);"></i>
          Informasi Sistem
        </div>
        <span class="e-badge green">Aktif</span>
      </div>
      <div class="e-card-body" style="font-size:.875rem;">
        <div class="d-flex justify-content-between py-2 border-bottom">
          <span style="color:var(--text-muted);">Sistem</span>
          <strong style="color:var(--text-primary);">SIMPEG v1.5</strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom">
          <span style="color:var(--text-muted);">Database</span>
          <strong style="color:var(--green);"><i class="bi bi-check-circle-fill"></i> Terhubung</strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom">
          <span style="color:var(--text-muted);">Role Anda</span>
          <strong style="color:var(--indigo);text-transform:capitalize;"><?php echo htmlspecialchars($user_role); ?></strong>
        </div>
        <div class="d-flex justify-content-between py-2">
          <span style="color:var(--text-muted);">PHP Version</span>
          <strong><?php echo PHP_VERSION; ?></strong>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ═══════════ MODAL PENGATURAN PEJABAT PENANDATANGAN ═══════════ -->
<div class="modal fade" id="modalTTD" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border-medium);border-radius:var(--r-lg);">
      <div class="modal-header" style="border-bottom:1px solid var(--border-light);">
        <h5 class="modal-title" style="font-weight:800;font-size:1.1rem;"><i class="bi bi-pen-fill" style="color:var(--indigo);"></i> Pengaturan Pejabat Penandatangan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?php echo BASE_URL; ?>pengaturan_ttd_save" method="POST">
        <div class="modal-body" style="padding:1.25rem;">
          <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:1rem;">
            Nama & NIP yang diisi di sini akan otomatis tampil pada cetakan <strong>Slip Gaji</strong> dan <strong>Laporan Arus Kas</strong>.
          </div>

          <!-- Section Bendahara -->
          <div style="font-weight:800;font-size:.82rem;color:var(--green);margin-bottom:.5rem;text-transform:uppercase;">1. Bendahara Keuangan</div>
          <div class="row g-2 mb-3">
            <div class="col-12">
              <div class="e-form-group">
                <label class="e-label">Nama Lengkap & Gelar Bendahara</label>
                <input type="text" class="e-input" name="ttd_bendahara_nama" value="<?php echo htmlspecialchars($ttd['ttd_bendahara_nama'] ?? ''); ?>" placeholder="Contoh: Hj. Siti Rahmah, S.E." required>
              </div>
            </div>
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">NIP Bendahara</label>
                <input type="text" class="e-input" name="ttd_bendahara_nip" value="<?php echo htmlspecialchars($ttd['ttd_bendahara_nip'] ?? ''); ?>" placeholder="Contoh: 198801012015032001" required>
              </div>
            </div>
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">Jabatan Bendahara</label>
                <input type="text" class="e-input" name="ttd_bendahara_jabatan" value="<?php echo htmlspecialchars($ttd['ttd_bendahara_jabatan'] ?? 'Bendahara Keuangan'); ?>" placeholder="Bendahara Keuangan">
              </div>
            </div>
          </div>

          <!-- Section Pimpinan -->
          <div style="font-weight:800;font-size:.82rem;color:var(--blue);margin-bottom:.5rem;text-transform:uppercase;">2. Pimpinan Instansi / Ketua</div>
          <div class="row g-2 mb-3">
            <div class="col-12">
              <div class="e-form-group">
                <label class="e-label">Nama Lengkap & Gelar Pimpinan</label>
                <input type="text" class="e-input" name="ttd_pimpinan_nama" value="<?php echo htmlspecialchars($ttd['ttd_pimpinan_nama'] ?? ''); ?>" placeholder="Contoh: Dr. H. A. Gazali, M.M." required>
              </div>
            </div>
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">NIP Pimpinan</label>
                <input type="text" class="e-input" name="ttd_pimpinan_nip" value="<?php echo htmlspecialchars($ttd['ttd_pimpinan_nip'] ?? ''); ?>" placeholder="Contoh: 197502022003121001" required>
              </div>
            </div>
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">Jabatan Pimpinan</label>
                <input type="text" class="e-input" name="ttd_pimpinan_jabatan" value="<?php echo htmlspecialchars($ttd['ttd_pimpinan_jabatan'] ?? 'Pimpinan / Ketua'); ?>" placeholder="Ketua / Direktur">
              </div>
            </div>
          </div>

          <!-- Kota Terbit -->
          <div class="e-form-group">
            <label class="e-label">Kota Terbit Laporan</label>
            <input type="text" class="e-input" name="kota_terbit" value="<?php echo htmlspecialchars($ttd['kota_terbit'] ?? 'Banjarmasin'); ?>" placeholder="Contoh: Banjarmasin">
          </div>
        </div>

        <div class="modal-footer" style="border-top:1px solid var(--border-light);">
          <button type="button" class="e-btn e-btn-ghost" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="e-btn e-btn-primary"><i class="bi bi-check-lg"></i> Simpan Penandatangan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openModalTTD() {
  const modal = new bootstrap.Modal(document.getElementById('modalTTD'));
  modal.show();
}
</script>
