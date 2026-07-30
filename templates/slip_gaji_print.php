<?php
// templates/slip_gaji_print.php — Halaman Cetak Slip Gaji Resmi (Tunggal & Menyeluruh Seluruh Pegawai)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/keuangan_functions.php';
require_login();

$id    = (int)($_GET['id'] ?? 0);
$all   = isset($_GET['all']) || $id === 0;
$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));

$nama_bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
               7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$periode_label = $nama_bulan[$bulan] . ' ' . $tahun;

$list_gaji = [];
if ($id > 0) {
    $single = get_gaji_by_id($conn, $id);
    if ($single) {
        // Cek permission jika pegawai
        if (is_pegawai() && $single['pegawai_id'] != get_session_pegawai_id()) {
            echo "<h3>Akses ditolak. Anda hanya dapat mencetak slip gaji milik sendiri.</h3>";
            exit();
        }
        $list_gaji[] = $single;
    }
} else {
    // Hanya Admin & Bendahara yang boleh cetak massal seluruh pegawai
    require_bendahara();
    $raw_list = get_all_gaji($conn, $bulan, $tahun);
    foreach ($raw_list as $row) {
        if (!empty($row['gaji_id'])) {
            $g_detail = get_gaji_by_id($conn, $row['gaji_id']);
            if ($g_detail) $list_gaji[] = $g_detail;
        }
    }
}

if (empty($list_gaji)) {
    echo "<div style='font-family:sans-serif;padding:2rem;text-align:center;'>
            <h2>Belum ada data slip gaji yang tersimpan untuk periode " . $periode_label . ".</h2>
            <p>Silakan simpan penggajian pegawai terlebih dahulu melalui menu Keuangan &gt; Penggajian.</p>
          </div>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Slip Gaji — <?php echo count($list_gaji) === 1 ? htmlspecialchars($list_gaji[0]['nama']) : 'Seluruh Pegawai (' . $periode_label . ')'; ?></title>
  <style>
    @page { size: A4 portrait; margin: 1.2cm; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 10.5pt; color: #1e293b; background: #f8fafc; margin: 0; padding: 20px; }
    .slip-card { max-width: 780px; margin: 0 auto 2.5rem; background: #fff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 2.25rem; box-shadow: 0 4px 20px rgba(0,0,0,.05); page-break-after: always; }
    .kop-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 1rem; margin-bottom: 1.25rem; }
    .kop-title { font-size: 1.35rem; font-weight: 900; color: #0f172a; letter-spacing: -.02em; }
    .kop-sub { font-size: .85rem; color: #64748b; margin-top: .2rem; }
    .slip-badge { background: #e0e7ff; color: #3730a3; padding: .35rem .85rem; border-radius: 20px; font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
    .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem 1.5rem; background: #f1f5f9; padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.25rem; }
    .meta-item { font-size: .88rem; }
    .meta-label { color: #64748b; font-size: .75rem; text-transform: uppercase; letter-spacing: .03em; }
    .meta-val { font-weight: 700; color: #0f172a; margin-top: .1rem; }
    .salary-table { width: 100%; border-collapse: collapse; margin-bottom: 1.25rem; }
    .salary-table th { background: #0f172a; color: #fff; text-align: left; padding: 8px 12px; font-size: .82rem; text-transform: uppercase; letter-spacing: .05em; }
    .salary-table td { padding: 7px 12px; border-bottom: 1px solid #e2e8f0; font-size: .88rem; }
    .sec-title { font-weight: 800; background: #f8fafc; color: #334155; }
    .amount-col { text-align: right; font-weight: 700; }
    .total-box { background: #ecfdf5; border: 2px dashed #059669; border-radius: 8px; padding: 1.1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
    .total-title { font-size: .95rem; font-weight: 800; color: #065f46; }
    .total-amount { font-size: 1.6rem; font-weight: 900; color: #047857; }
    .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2.5rem; text-align: center; }
    .sig-space { height: 60px; }
    .sig-name { font-weight: 800; text-decoration: underline; color: #0f172a; }
    .sig-title { font-size: .82rem; color: #64748b; }
    .no-print { margin-bottom: 1.25rem; text-align: right; }
    .btn-print { background: #2563eb; color: #fff; border: none; padding: .6rem 1.25rem; border-radius: 6px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: .5rem; font-size: .9rem; }
    @media print {
      body { background: #fff; padding: 0; }
      .slip-card { border: none; box-shadow: none; max-width: 100%; padding: 0; margin-bottom: 0; }
      .no-print { display: none; }
    }
  </style>
</head>
<body>

<div class="no-print">
  <button class="btn-print" onclick="window.print()"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4h10z"></path></svg> Cetak Semua / Print PDF (<?php echo count($list_gaji); ?> Slip Gaji)</button>
</div>

<?php foreach ($list_gaji as $gaji): 
  $ttd  = get_pengaturan_ttd($conn);
  $kota = $ttd['kota_terbit'] ?: 'Banjarmasin';

  $gaji_pokok         = (float)($gaji['gaji_pokok'] ?? 0);
  $tunj_fungsional    = (float)($gaji['tunj_fungsional'] ?? 0);
  $tunj_struktural    = (float)($gaji['tunj_struktural'] ?? 0);
  $tunj_kesejahteraan = (float)($gaji['tunj_kesejahteraan'] ?? 0);
  $tunj_kawin         = (float)($gaji['tunj_kawin'] ?? 0);
  $tunj_anak          = (float)($gaji['tunj_anak'] ?? 0);
  $tunj_beras         = (float)($gaji['tunj_beras'] ?? 200000);
  $tunjangan_makan    = (float)($gaji['tunjangan_makan'] ?? 0);
  $tunjangan_lain     = (float)($gaji['tunjangan_lain'] ?? 0);

  $subtotal_penerimaan = $gaji_pokok + $tunj_fungsional + $tunj_struktural + $tunj_kesejahteraan + $tunj_kawin + $tunj_anak + $tunj_beras + $tunjangan_makan + $tunjangan_lain;

  $pot_bpjs_tk  = (float)($gaji['pot_bpjs_tk'] ?? 0);
  $pot_bpjs_kes = (float)($gaji['pot_bpjs_kes'] ?? 0);
  $pot_koperasi = (float)($gaji['pot_koperasi'] ?? 20000);
  $pot_lain     = (float)($gaji['potongan'] ?? 0);

  $subtotal_potongan = $pot_bpjs_tk + $pot_bpjs_kes + $pot_koperasi + $pot_lain;

  $take_home_pay = $subtotal_penerimaan - $subtotal_potongan;
?>
<div class="slip-card">
  <!-- Kop Header -->
  <div class="kop-header">
    <div>
      <div class="kop-title">SIMPEG System</div>
      <div class="kop-sub">Sistem Manajemen Kepegawaian & Penggajian Resmi</div>
    </div>
    <div class="slip-badge">SLIP GAJI PEGAWAI</div>
  </div>

  <!-- Metadata Pegawai -->
  <div class="meta-grid">
    <div class="meta-item">
      <div class="meta-label">Nama Pegawai</div>
      <div class="meta-val"><?php echo htmlspecialchars($gaji['nama']); ?></div>
    </div>
    <div class="meta-item">
      <div class="meta-label">NIP / ID</div>
      <div class="meta-val"><?php echo htmlspecialchars($gaji['nip'] ?: '—'); ?></div>
    </div>
    <div class="meta-item">
      <div class="meta-label">Jabatan / Pangkat</div>
      <div class="meta-val"><?php echo htmlspecialchars($gaji['kepangkatan']); ?> <?php echo $gaji['jabatan_fungsional'] ? '('.$gaji['jabatan_fungsional'].')' : ''; ?></div>
    </div>
    <div class="meta-item">
      <div class="meta-label">Periode Penggajian</div>
      <div class="meta-val"><?php echo $periode_label; ?></div>
    </div>
  </div>

  <!-- Table Rincian -->
  <table class="salary-table">
    <thead>
      <tr>
        <th>Rincian Penghasilan & Potongan</th>
        <th style="text-align:right;">Nominal (Rp)</th>
      </tr>
    </thead>
    <tbody>
      <tr class="sec-title">
        <td colspan="2" style="color:#15803d;font-weight:900;">A. PENERIMAAN / TUNJANGAN</td>
      </tr>
      <tr>
        <td style="padding-left:1.5rem;">1. Gaji Pokok</td>
        <td class="amount-col">Rp <?php echo number_format($gaji_pokok, 0, ',', '.'); ?></td>
      </tr>
      <?php if ($tunj_fungsional > 0): ?>
      <tr>
        <td style="padding-left:1.5rem;">2. Tunjangan Fungsional</td>
        <td class="amount-col">Rp <?php echo number_format($tunj_fungsional, 0, ',', '.'); ?></td>
      </tr>
      <?php endif; ?>
      <?php if ($tunj_struktural > 0): ?>
      <tr>
        <td style="padding-left:1.5rem;">3. Tunjangan Struktural</td>
        <td class="amount-col">Rp <?php echo number_format($tunj_struktural, 0, ',', '.'); ?></td>
      </tr>
      <?php endif; ?>
      <?php if ($tunj_kesejahteraan > 0): ?>
      <tr>
        <td style="padding-left:1.5rem;">4. Tunjangan Kesejahteraan</td>
        <td class="amount-col">Rp <?php echo number_format($tunj_kesejahteraan, 0, ',', '.'); ?></td>
      </tr>
      <?php endif; ?>
      <?php if ($tunj_kawin > 0): ?>
      <tr>
        <td style="padding-left:1.5rem;">5. Tunjangan Kawin (10%)</td>
        <td class="amount-col">Rp <?php echo number_format($tunj_kawin, 0, ',', '.'); ?></td>
      </tr>
      <?php endif; ?>
      <?php if ($tunj_anak > 0): ?>
      <tr>
        <td style="padding-left:1.5rem;">6. Tunjangan Anak (2%/anak &middot; <?php echo (int)($gaji['jumlah_anak']??0); ?> anak)</td>
        <td class="amount-col">Rp <?php echo number_format($tunj_anak, 0, ',', '.'); ?></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td style="padding-left:1.5rem;">7. Tunjangan Beras Pekerja</td>
        <td class="amount-col">Rp <?php echo number_format($tunj_beras, 0, ',', '.'); ?></td>
      </tr>
      <tr>
        <td style="padding-left:1.5rem;">8. Uang Makan (Kehadiran)</td>
        <td class="amount-col">Rp <?php echo number_format($tunjangan_makan, 0, ',', '.'); ?></td>
      </tr>
      <?php if ($tunjangan_lain > 0): ?>
      <tr>
        <td style="padding-left:1.5rem;">9. Tunjangan Lainnya</td>
        <td class="amount-col">Rp <?php echo number_format($tunjangan_lain, 0, ',', '.'); ?></td>
      </tr>
      <?php endif; ?>
      <tr style="font-weight:800;background:#f0fdf4;">
        <td style="padding-left:1.5rem;color:#166534;">SUBTOTAL PENERIMAAN (A)</td>
        <td class="amount-col" style="color:#166534;">Rp <?php echo number_format($subtotal_penerimaan, 0, ',', '.'); ?></td>
      </tr>

      <tr class="sec-title">
        <td colspan="2" style="color:#b91c1c;font-weight:900;">B. POTONGAN GAJI</td>
      </tr>
      <?php if ($pot_bpjs_tk > 0): ?>
      <tr>
        <td style="padding-left:1.5rem;">1. Asuransi Ketenagakerjaan / BPJS TK (2%)</td>
        <td class="amount-col" style="color:#dc2626;">- Rp <?php echo number_format($pot_bpjs_tk, 0, ',', '.'); ?></td>
      </tr>
      <?php endif; ?>
      <?php if ($pot_bpjs_kes > 0): ?>
      <tr>
        <td style="padding-left:1.5rem;">2. Asuransi Kesehatan / BPJS Kes (5%)</td>
        <td class="amount-col" style="color:#dc2626;">- Rp <?php echo number_format($pot_bpjs_kes, 0, ',', '.'); ?></td>
      </tr>
      <?php endif; ?>
      <?php if ($pot_koperasi > 0): ?>
      <tr>
        <td style="padding-left:1.5rem;">3. Simpanan Wajib Koperasi</td>
        <td class="amount-col" style="color:#dc2626;">- Rp <?php echo number_format($pot_koperasi, 0, ',', '.'); ?></td>
      </tr>
      <?php endif; ?>
      <?php if ($pot_lain > 0): ?>
      <tr>
        <td style="padding-left:1.5rem;">4. Potongan Lainnya</td>
        <td class="amount-col" style="color:#dc2626;">- Rp <?php echo number_format($pot_lain, 0, ',', '.'); ?></td>
      </tr>
      <?php endif; ?>
      <tr style="font-weight:800;background:#fef2f2;">
        <td style="padding-left:1.5rem;color:#991b1b;">SUBTOTAL POTONGAN (B)</td>
        <td class="amount-col" style="color:#991b1b;">- Rp <?php echo number_format($subtotal_potongan, 0, ',', '.'); ?></td>
      </tr>
    </tbody>
  </table>

  <!-- Total Box -->
  <div class="total-box">
    <div>
      <div class="total-title">TAKE HOME PAY (TOTAL GAJI DITERIMA)</div>
      <div style="font-size:.78rem;color:#047857;margin-top:.15rem;">Subtotal Penerimaan (A) - Subtotal Potongan (B)</div>
    </div>
    <div class="total-amount">
      Rp <?php echo number_format($take_home_pay, 0, ',', '.'); ?>
    </div>
  </div>

  <?php if (!empty($gaji['catatan'])): ?>
  <div style="font-size:.82rem;color:#64748b;margin-bottom:1.25rem;padding:.75rem;background:#f8fafc;border-left:3px solid #64748b;border-radius:4px;">
    <strong>Catatan:</strong> <?php echo htmlspecialchars($gaji['catatan']); ?>
  </div>
  <?php endif; ?>

  <!-- Tanda Tangan -->
  <div class="signatures">
    <div>
      <div class="sig-title">Penerima (Pegawai),</div>
      <div class="sig-space"></div>
      <div class="sig-name"><?php echo htmlspecialchars($gaji['nama']); ?></div>
      <div class="sig-title">NIP: <?php echo htmlspecialchars($gaji['nip'] ?: '—'); ?></div>
    </div>
    <div>
      <div class="sig-title"><?php echo htmlspecialchars($kota); ?>, <?php echo date('d F Y'); ?><br><?php echo htmlspecialchars($ttd['ttd_bendahara_jabatan']); ?>,</div>
      <div class="sig-space"></div>
      <div class="sig-name"><?php echo htmlspecialchars($ttd['ttd_bendahara_nama']); ?></div>
      <div class="sig-title">NIP: <?php echo htmlspecialchars($ttd['ttd_bendahara_nip']); ?></div>
    </div>
  </div>
</div>
<?php endforeach; ?>

</body>
</html>
