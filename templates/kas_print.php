<?php
// templates/kas_print.php — Cetak / Print PDF Laporan Arus Kas Resmi
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/keuangan_functions.php';
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
$jenis_lbl = $jenis_kas === 'kas_kecil' ? 'Kas Kecil (Operasional)' : ($jenis_kas === 'kas_besar' ? 'Kas Besar (Utama)' : 'Semua Kas (Kecil & Besar)');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan Arus Kas — <?php echo $label_bln; ?></title>
  <style>
    @page { size: A4 landscape; margin: 1.2cm; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 10pt; color: #1e293b; background: #f8fafc; margin: 0; padding: 20px; }
    .report-card { max-width: 1050px; margin: 0 auto; background: #fff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,.05); }
    .kop-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 1rem; margin-bottom: 1.25rem; }
    .kop-title { font-size: 1.35rem; font-weight: 900; color: #0f172a; letter-spacing: -.02em; }
    .kop-sub { font-size: .85rem; color: #64748b; margin-top: .2rem; }
    .report-badge { background: #e0e7ff; color: #3730a3; padding: .35rem .85rem; border-radius: 20px; font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
    .summary-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem; }
    .sum-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: .85rem 1rem; border-radius: 8px; }
    .sum-label { font-size: .75rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
    .sum-val { font-size: 1.25rem; font-weight: 900; color: #0f172a; margin-top: .15rem; }
    .kas-table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; }
    .kas-table th { background: #0f172a; color: #fff; text-align: left; padding: 8px 10px; font-size: .82rem; text-transform: uppercase; letter-spacing: .05em; }
    .kas-table td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; font-size: .88rem; }
    .money-in { text-align: right; color: #166534; font-weight: 700; }
    .money-out { text-align: right; color: #991b1b; font-weight: 700; }
    .grand-total { background: #0f172a; color: #fff; font-weight: 700; }
    .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2.5rem; text-align: center; }
    .sig-space { height: 60px; }
    .sig-name { font-weight: 800; text-decoration: underline; color: #0f172a; }
    .sig-title { font-size: .82rem; color: #64748b; }
    .no-print { margin-bottom: 1.25rem; text-align: right; }
    .btn-print { background: #2563eb; color: #fff; border: none; padding: .6rem 1.25rem; border-radius: 6px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: .5rem; font-size: .9rem; }
    @media print {
      body { background: #fff; padding: 0; }
      .report-card { border: none; box-shadow: none; max-width: 100%; padding: 0; }
      .no-print { display: none; }
    }
  </style>
</head>
<body>

<div class="no-print">
  <button class="btn-print" onclick="window.print()"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4h10z"></path></svg> Cetak / Print PDF</button>
</div>

<div class="report-card">
  <!-- Kop Header -->
  <div class="kop-header">
    <div style="display:flex;align-items:center;gap:1.25rem;">
      <img src="<?php echo BASE_URL; ?>public/images/logo.png" alt="Logo STIENAS" style="height:56px;width:auto;object-fit:contain;">
      <div>
        <div class="kop-title">STIE NASIONAL BANJARMASIN</div>
        <div class="kop-sub">Laporan Resmi Transaksi Arus Kas Instansi & Keuangan</div>
      </div>
    </div>
    <div class="report-badge"><?php echo $jenis_lbl; ?></div>
  </div>

  <div style="margin-bottom:1rem;font-size:.9rem;color:#475569;">
    Periode Transaksi: <strong><?php echo $label_bln; ?></strong> &nbsp;&middot;&nbsp; 
    Dicetak pada: <strong><?php echo date('d F Y H:i'); ?></strong>
  </div>

  <!-- Summary Box -->
  <div class="summary-grid">
    <div class="sum-box" style="border-left:4px solid #2563eb;">
      <div class="sum-label">Saldo Akumulasi Kas Kecil</div>
      <div class="sum-val" style="color:#2563eb;">Rp <?php echo number_format($saldo['kas_kecil_total'], 0, ',', '.'); ?></div>
    </div>
    <div class="sum-box" style="border-left:4px solid #059669;">
      <div class="sum-label">Saldo Akumulasi Kas Besar</div>
      <div class="sum-val" style="color:#059669;">Rp <?php echo number_format($saldo['kas_besar_total'], 0, ',', '.'); ?></div>
    </div>
    <div class="sum-box" style="border-left:4px solid #7c3aed;">
      <div class="sum-label">Total Seluruh Kas Instansi</div>
      <div class="sum-val" style="color:#7c3aed;">Rp <?php echo number_format($saldo['total_kas'], 0, ',', '.'); ?></div>
    </div>
  </div>

  <!-- Table Transaksi -->
  <table class="kas-table">
    <thead>
      <tr>
        <th style="width:30px;text-align:center;">#</th>
        <th>Tanggal</th>
        <th>Jenis Kas</th>
        <th>Kategori / Akun</th>
        <th>Keterangan</th>
        <th style="text-align:right;">Pemasukan (Rp)</th>
        <th style="text-align:right;">Pengeluaran (Rp)</th>
        <th style="text-align:center;">Pencatat</th>
      </tr>
    </thead>
    <tbody>
      <?php 
        $no = 1;
        $total_masuk = 0;
        $total_keluar = 0;

        if (!empty($transaksi)):
        foreach ($transaksi as $t):
          $masuk = $t['tipe'] === 'pemasukan' ? (float)$t['jumlah'] : 0;
          $keluar = $t['tipe'] === 'pengeluaran' ? (float)$t['jumlah'] : 0;
          $total_masuk += $masuk;
          $total_keluar += $keluar;
      ?>
      <tr>
        <td style="text-align:center;"><?php echo $no++; ?></td>
        <td><?php echo date('d/m/Y', strtotime($t['tanggal'])); ?></td>
        <td><?php echo $t['jenis_kas']==='kas_kecil'?'Kas Kecil':'Kas Besar'; ?></td>
        <td style="font-weight:700;"><?php echo htmlspecialchars($t['kategori']); ?></td>
        <td><?php echo htmlspecialchars($t['keterangan'] ?: '—'); ?></td>
        <td class="money-in"><?php echo $masuk > 0 ? 'Rp ' . number_format($masuk, 0, ',', '.') : '—'; ?></td>
        <td class="money-out"><?php echo $keluar > 0 ? 'Rp ' . number_format($keluar, 0, ',', '.') : '—'; ?></td>
        <td style="text-align:center;font-size:.8rem;"><?php echo htmlspecialchars($t['user_name'] ?: 'System'); ?></td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="8" style="text-align:center;padding:1.5rem;color:#94a3b8;">Belum ada transaksi kas pada periode ini.</td></tr>
      <?php endif; ?>

      <tr class="grand-total">
        <td colspan="5" style="text-align:right;font-weight:bold;">TOTAL PERIODE INI</td>
        <td style="text-align:right;background:#166534;color:#fff;">Rp <?php echo number_format($total_masuk, 0, ',', '.'); ?></td>
        <td style="text-align:right;background:#991b1b;color:#fff;">Rp <?php echo number_format($total_keluar, 0, ',', '.'); ?></td>
        <td>-</td>
      </tr>
    </tbody>
  </table>

<?php
$ttd  = get_pengaturan_ttd($conn);
$kota = $ttd['kota_terbit'] ?: 'Banjarmasin';
?>
  <!-- Tanda Tangan -->
  <div class="signatures">
    <div>
      <div class="sig-title">Mengetahui,<br><?php echo htmlspecialchars($ttd['ttd_pimpinan_jabatan']); ?></div>
      <div class="sig-space"></div>
      <div class="sig-name"><?php echo htmlspecialchars($ttd['ttd_pimpinan_nama']); ?></div>
      <div class="sig-title">NIP: <?php echo htmlspecialchars($ttd['ttd_pimpinan_nip']); ?></div>
    </div>
    <div>
      <div class="sig-title"><?php echo htmlspecialchars($kota); ?>, <?php echo date('d F Y'); ?><br><?php echo htmlspecialchars($ttd['ttd_bendahara_jabatan']); ?>,</div>
      <div class="sig-space"></div>
      <div class="sig-name"><?php echo htmlspecialchars($ttd['ttd_bendahara_nama']); ?></div>
      <div class="sig-title">NIP: <?php echo htmlspecialchars($ttd['ttd_bendahara_nip']); ?></div>
    </div>
  </div>
</div>

</body>
</html>
