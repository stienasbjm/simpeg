<?php
// src/modules/absensi_export.php — Generate laporan Excel per bulan dengan perhitungan Uang Makan
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/absensi_functions.php';
require_admin();

$bulan      = (int)($_GET['bulan'] ?? date('n'));
$tahun      = (int)($_GET['tahun'] ?? date('Y'));
$uang_makan = (int)($_GET['uang_makan'] ?? 20000);
if ($uang_makan < 0) $uang_makan = 20000;

$nama_bulan = [
    1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
    7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
];

$rekap    = get_rekap_bulanan($conn, $bulan, $tahun);
$jumlah_hr = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
$label_bln = $nama_bulan[$bulan] . ' ' . $tahun;

// Generate per-day absensi for each pegawai (matrix)
$detail_map = [];
foreach ($rekap as $r) {
    $detail = get_detail_absensi_pegawai($conn, $r['id'], $bulan, $tahun);
    foreach ($detail as $d) {
        $tgl = (int)date('j', strtotime($d['tanggal']));
        $detail_map[$r['id']][$tgl] = $d;
    }
}

// HTTP headers for Excel download
$filename = "Laporan_Absensi_UangMakan_{$label_bln}.xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

// Symbol map
$sym = [
    'hadir'        => '✓',
    'tidak_hadir'  => '✗',
    'izin'         => 'I',
    'sakit'        => 'S',
    'cuti'         => 'C',
];
?>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; font-size: 10pt; }
  table { border-collapse: collapse; width: 100%; }
  th, td { border: 1px solid #999; padding: 4px 6px; text-align: center; font-size: 9pt; }
  .header-row th { background: #1e3a8a; color: white; font-weight: bold; }
  .sub-header th { background: #93c5fd; color: #1e3a8a; font-weight: bold; }
  .nama-col { text-align: left !important; }
  .title-cell { font-size: 14pt; font-weight: bold; text-align: left; border: none; }
  .sub-title { font-size: 10pt; text-align: left; border: none; color: #333; }
  tr:nth-child(even) { background-color: #f0f4ff; }
  .hadir   { color: #166534; font-weight: bold; }
  .absen   { color: #991b1b; font-weight: bold; }
  .izin    { color: #1e40af; }
  .sakit   { color: #92400e; }
  .cuti    { color: #5b21b6; }
  .total   { background: #fef3c7; font-weight: bold; }
  .money   { text-align: right !important; font-weight: bold; background: #ecfdf5; color: #065f46; }
  .grand-total { background: #1e3a8a; color: white; font-weight: bold; font-size: 10pt; }
</style>
</head>
<body>
<table>
  <tr>
    <td colspan="<?php echo 9 + $jumlah_hr; ?>" class="title-cell">
      LAPORAN REKAP ABSENSI & UANG MAKAN PEGAWAI — <?php echo strtoupper($label_bln); ?>
    </td>
  </tr>
  <tr>
    <td colspan="<?php echo 9 + $jumlah_hr; ?>" class="sub-title">
      Dicetak pada: <?php echo date('d/m/Y H:i'); ?> &nbsp;|&nbsp;
      Total Pegawai: <?php echo count($rekap); ?> &nbsp;|&nbsp;
      <strong>Tarif Uang Makan per Hari Hadir: Rp <?php echo number_format($uang_makan, 0, ',', '.'); ?></strong>
    </td>
  </tr>
  <tr><td colspan="<?php echo 9 + $jumlah_hr; ?>" style="border:none;height:10px;"></td></tr>

  <thead>
    <tr class="header-row">
      <th rowspan="2">#</th>
      <th rowspan="2" class="nama-col">Nama Pegawai</th>
      <th rowspan="2">NIP</th>
      <th rowspan="2">Pangkat/Gol</th>
      <th rowspan="2">Jabatan</th>
      <th rowspan="2">Status</th>
      <!-- Kolom tanggal -->
      <?php for ($d = 1; $d <= $jumlah_hr; $d++): ?>
      <th style="min-width:22px;"><?php echo $d; ?></th>
      <?php endfor; ?>
      <th colspan="5">Rekap Kehadiran (Hari)</th>
      <th rowspan="2">Tarif / Hari (Rp)</th>
      <th rowspan="2">Total Uang Makan (Rp)</th>
    </tr>
    <tr class="sub-header">
      <?php for ($d = 1; $d <= $jumlah_hr; $d++): ?>
      <?php $day = date('D', mktime(0,0,0,$bulan,$d,$tahun)); ?>
      <th><?php echo substr($day, 0, 1); ?></th>
      <?php endfor; ?>
      <th>Hadir</th><th>Absen</th><th>Izin</th><th>Sakit</th><th>Cuti</th>
    </tr>
  </thead>
  <tbody>
  <?php 
    $no = 1;
    $grand_hadir = 0; $grand_alpa = 0; $grand_izin = 0; $grand_sakit = 0; $grand_cuti = 0;
    $grand_uang_makan = 0;

    foreach ($rekap as $r): 
      $total_uang = $r['total_hadir'] * $uang_makan;
      $grand_hadir += $r['total_hadir'];
      $grand_alpa  += $r['total_tidak_hadir'];
      $grand_izin  += $r['total_izin'];
      $grand_sakit += $r['total_sakit'];
      $grand_cuti  += $r['total_cuti'];
      $grand_uang_makan += $total_uang;
  ?>
  <tr>
    <td><?php echo $no++; ?></td>
    <td class="nama-col"><?php echo htmlspecialchars($r['nama']); ?></td>
    <td><?php echo htmlspecialchars($r['nip']); ?></td>
    <td><?php echo htmlspecialchars($r['kepangkatan']); ?></td>
    <td><?php echo htmlspecialchars($r['jabatan_fungsional'] ?: '—'); ?></td>
    <td><?php echo htmlspecialchars($r['status_kepegawaian']); ?></td>
    <?php for ($d = 1; $d <= $jumlah_hr; $d++): ?>
    <?php
      $ab = $detail_map[$r['id']][$d] ?? null;
      $day_of_week = date('N', mktime(0,0,0,$bulan,$d,$tahun)); // 6=Sat 7=Sun
      $is_weekend = ($day_of_week >= 6);
      if ($is_weekend) { echo '<td style="background:#e5e7eb;color:#9ca3af;">—</td>'; }
      elseif (!$ab)    { echo '<td>·</td>'; }
      else {
          $st = $ab['status'];
          $cls = match($st) {
              'hadir'       => 'hadir',
              'tidak_hadir' => 'absen',
              'izin'        => 'izin',
              'sakit'       => 'sakit',
              'cuti'        => 'cuti',
              default       => ''
          };
          echo '<td class="' . $cls . '">' . ($sym[$st] ?? '?') . '</td>';
      }
    ?>
    <?php endfor; ?>
    <td class="total hadir"><?php echo $r['total_hadir']; ?></td>
    <td class="total absen"><?php echo $r['total_tidak_hadir']; ?></td>
    <td class="total izin"><?php echo $r['total_izin']; ?></td>
    <td class="total sakit"><?php echo $r['total_sakit']; ?></td>
    <td class="total cuti"><?php echo $r['total_cuti']; ?></td>
    <td style="text-align:right;">Rp <?php echo number_format($uang_makan, 0, ',', '.'); ?></td>
    <td class="money">Rp <?php echo number_format($total_uang, 0, ',', '.'); ?></td>
  </tr>
  <?php endforeach; ?>
  
  <!-- Row Grand Total -->
  <tr class="grand-total">
    <td colspan="<?php echo 6 + $jumlah_hr; ?>" style="text-align:right;font-weight:bold;">TOTAL KESELURUHAN</td>
    <td><?php echo $grand_hadir; ?></td>
    <td><?php echo $grand_alpa; ?></td>
    <td><?php echo $grand_izin; ?></td>
    <td><?php echo $grand_sakit; ?></td>
    <td><?php echo $grand_cuti; ?></td>
    <td>-</td>
    <td style="text-align:right;background:#059669;color:#fff;">Rp <?php echo number_format($grand_uang_makan, 0, ',', '.'); ?></td>
  </tr>
  </tbody>
</table>

<br>
<table style="width:45%;margin-top:10px;">
  <tr><th colspan="2" style="background:#1e3a8a;color:white;">Keterangan & Catatan</th></tr>
  <tr><td class="hadir">✓</td><td style="text-align:left;">Hadir (Mendapat Uang Makan)</td></tr>
  <tr><td class="absen">✗</td><td style="text-align:left;">Tidak Hadir / Alpa</td></tr>
  <tr><td class="izin">I</td><td style="text-align:left;">Izin</td></tr>
  <tr><td class="sakit">S</td><td style="text-align:left;">Sakit</td></tr>
  <tr><td class="cuti">C</td><td style="text-align:left;">Cuti</td></tr>
  <tr><td>·</td><td style="text-align:left;">Tidak Ada Data</td></tr>
  <tr><td style="background:#e5e7eb;color:#9ca3af;">—</td><td style="text-align:left;">Hari Libur/Akhir Pekan</td></tr>
  <tr>
    <td style="font-weight:bold;background:#ecfdf5;color:#065f46;">Formula</td>
    <td style="text-align:left;font-weight:bold;">Total Uang Makan = Total Hadir × Rp <?php echo number_format($uang_makan, 0, ',', '.'); ?></td>
  </tr>
</table>
</body>
</html>

