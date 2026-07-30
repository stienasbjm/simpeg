<?php
// src/modules/kas_export.php — Generate laporan Excel Arus Kas
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth/auth.php';
require_once __DIR__ . '/keuangan_functions.php';
require_bendahara();

$jenis_kas = $_GET['jenis_kas'] ?? '';
$bulan     = (int)($_GET['bulan'] ?? date('n'));
$tahun     = (int)($_GET['tahun'] ?? date('Y'));

$nama_bulan = [
    1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
    7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
];

$transaksi = get_all_kas($conn, $jenis_kas, $bulan, $tahun);
$saldo     = get_kas_summary($conn);
$label_bln = $nama_bulan[$bulan] . ' ' . $tahun;
$jenis_lbl = $jenis_kas === 'kas_kecil' ? 'Kas Kecil' : ($jenis_kas === 'kas_besar' ? 'Kas Besar' : 'Semua Kas');

// HTTP headers for Excel download
$filename = "Laporan_Arus_Kas_{$jenis_lbl}_{$label_bln}.xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");
?>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; font-size: 10pt; }
  table { border-collapse: collapse; width: 100%; }
  th, td { border: 1px solid #999; padding: 5px 8px; font-size: 9pt; }
  .header-row th { background: #1e3a8a; color: white; font-weight: bold; text-align: center; }
  .title-cell { font-size: 14pt; font-weight: bold; text-align: left; border: none; }
  .sub-title { font-size: 10pt; text-align: left; border: none; color: #333; }
  tr:nth-child(even) { background-color: #f8fafc; }
  .money-in  { text-align: right; color: #166534; font-weight: bold; }
  .money-out { text-align: right; color: #991b1b; font-weight: bold; }
  .total-row { background: #1e3a8a; color: white; font-weight: bold; }
</style>
</head>
<body>
<table>
  <tr>
    <td colspan="8" class="title-cell">
      LAPORAN ARUS KAS PEGAWAI & INSTANSI — <?php echo strtoupper($label_bln); ?>
    </td>
  </tr>
  <tr>
    <td colspan="8" class="sub-title">
      Filter Kas: <strong><?php echo $jenis_lbl; ?></strong> &nbsp;|&nbsp;
      Dicetak pada: <?php echo date('d/m/Y H:i'); ?> &nbsp;|&nbsp;
      Saldo Kas Kecil: Rp <?php echo number_format($saldo['kas_kecil_total'], 0, ',', '.'); ?> &nbsp;|&nbsp;
      Saldo Kas Besar: Rp <?php echo number_format($saldo['kas_besar_total'], 0, ',', '.'); ?>
    </td>
  </tr>
  <tr><td colspan="8" style="border:none;height:10px;"></td></tr>

  <thead>
    <tr class="header-row">
      <th style="width:30px;">#</th>
      <th>Tanggal</th>
      <th>Jenis Kas</th>
      <th>Kategori / Akun</th>
      <th>Keterangan</th>
      <th>Pemasukan (Rp)</th>
      <th>Pengeluaran (Rp)</th>
      <th>Pencatat</th>
    </tr>
  </thead>
  <tbody>
  <?php 
    $no = 1;
    $total_masuk = 0;
    $total_keluar = 0;

    foreach ($transaksi as $t):
      $masuk = $t['tipe'] === 'pemasukan' ? (float)$t['jumlah'] : 0;
      $keluar = $t['tipe'] === 'pengeluaran' ? (float)$t['jumlah'] : 0;
      $total_masuk += $masuk;
      $total_keluar += $keluar;
  ?>
  <tr>
    <td style="text-align:center;"><?php echo $no++; ?></td>
    <td style="text-align:center;"><?php echo date('d/m/Y', strtotime($t['tanggal'])); ?></td>
    <td style="text-align:center;"><?php echo $t['jenis_kas']==='kas_kecil'?'Kas Kecil':'Kas Besar'; ?></td>
    <td style="font-weight:bold;"><?php echo htmlspecialchars($t['kategori']); ?></td>
    <td><?php echo htmlspecialchars($t['keterangan'] ?: '—'); ?></td>
    <td class="money-in"><?php echo $masuk > 0 ? 'Rp ' . number_format($masuk, 0, ',', '.') : '—'; ?></td>
    <td class="money-out"><?php echo $keluar > 0 ? 'Rp ' . number_format($keluar, 0, ',', '.') : '—'; ?></td>
    <td style="text-align:center;"><?php echo htmlspecialchars($t['user_name'] ?: 'System'); ?></td>
  </tr>
  <?php endforeach; ?>

  <tr class="total-row">
    <td colspan="5" style="text-align:right;">TOTAL PERIODE INI</td>
    <td style="text-align:right;background:#15803d;color:#fff;">Rp <?php echo number_format($total_masuk, 0, ',', '.'); ?></td>
    <td style="text-align:right;background:#b91c1c;color:#fff;">Rp <?php echo number_format($total_keluar, 0, ',', '.'); ?></td>
    <td>-</td>
  </tr>
  <tr style="background:#e0e7ff;font-weight:bold;">
    <td colspan="5" style="text-align:right;color:#3730a3;">ARUS KAS NETTO (PEMASUKAN - PENGELUARAN)</td>
    <td colspan="2" style="text-align:right;color:#3730a3;font-size:11pt;">
      Rp <?php echo number_format($total_masuk - $total_keluar, 0, ',', '.'); ?>
    </td>
    <td>-</td>
  </tr>
  </tbody>
</table>
</body>
</html>
