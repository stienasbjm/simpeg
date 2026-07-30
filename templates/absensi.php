<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/absensi_functions.php';
require_once __DIR__ . '/../src/modules/pegawai_functions.php';
require_admin();
$bulan      = (int)($_GET['bulan'] ?? date('n'));
$tahun      = (int)($_GET['tahun'] ?? date('Y'));
$uang_makan = (int)($_GET['uang_makan'] ?? 20000);
if ($uang_makan < 0) $uang_makan = 20000;

$pid_filter = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
$rekap      = get_rekap_bulanan($conn, $bulan, $tahun);
$all_peg    = get_all_pegawai($conn);
$nama_bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
               7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$tahun_opts = range(2020, 2035);

// Hitung total akumulasi hadir & total uang makan seluruh pegawai
$total_hadir_semua = 0;
foreach ($rekap as $r) {
    $total_hadir_semua += $r['total_hadir'];
}
$total_uang_makan_semua = $total_hadir_semua * $uang_makan;
?>
<div class="e-page-header">
  <div>
    <div class="e-breadcrumb">
      <i class="bi bi-house-fill" style="font-size:.7rem;"></i>
      <a href="<?php echo BASE_URL; ?>dashboard">Beranda</a>
      <span class="e-breadcrumb-sep">/</span>
      <span style="color:var(--teal);font-weight:600;">Absensi</span>
    </div>
    <h1 class="e-page-title">
      <div class="title-icon" style="background:linear-gradient(135deg,rgba(20,184,166,.15),rgba(94,234,212,.08));color:var(--teal);">
        <i class="bi bi-calendar2-check-fill"></i>
      </div>
      Rekap Absensi & Uang Makan
    </h1>
    <p class="e-page-sub">Rekap kehadiran dan perhitungan uang makan pegawai bulan <strong><?php echo $nama_bulan[$bulan] . ' ' . $tahun; ?></strong>.</p>
  </div>
  <!-- Export Excel -->
  <a href="<?php echo BASE_URL; ?>absensi_export?bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>&uang_makan=<?php echo $uang_makan; ?>"
     class="e-btn e-btn-primary" style="background:linear-gradient(135deg,#10b981,#059669);border-color:#047857;box-shadow:0 4px 12px rgba(16,185,129,.3);">
    <i class="bi bi-file-earmark-excel-fill"></i> Export Excel (Laporan Uang Makan)
  </a>
</div>

<?php if (!empty($_SESSION['success_message'])): ?>
<div class="e-notice success" data-dismiss><i class="bi bi-check-circle-fill"></i><span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span></div>
<?php endif; ?>

<!-- Filter & Tarif Uang Makan -->
<div class="e-card" style="margin-bottom:1.25rem;">
  <div class="e-card-body" style="padding:1rem 1.25rem;">
    <form method="GET" action="" style="display:flex;align-items:flex-end;gap:.875rem;flex-wrap:wrap;">
      <input type="hidden" name="page" value="absensi">
      <div>
        <label class="e-label">Bulan</label>
        <select class="e-select" name="bulan" style="width:140px;">
          <?php foreach ($nama_bulan as $m => $n): ?>
          <option value="<?php echo $m; ?>" <?php echo $m === $bulan ? 'selected' : ''; ?>><?php echo $n; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="e-label">Tahun</label>
        <select class="e-select" name="tahun" style="width:100px;">
          <?php foreach ($tahun_opts as $y): ?>
          <option value="<?php echo $y; ?>" <?php echo $y === $tahun ? 'selected' : ''; ?>><?php echo $y; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="e-label" title="Tarif uang makan per hari hadir">Uang Makan / Hari (Rp) <i class="bi bi-info-circle" style="font-size:.75rem;color:var(--text-muted);"></i></label>
        <div style="position:relative;display:flex;align-items:center;">
          <span style="position:absolute;left:.75rem;font-size:.82rem;font-weight:700;color:var(--text-muted);">Rp</span>
          <input type="number" class="e-input" name="uang_makan" value="<?php echo $uang_makan; ?>" min="0" step="500" style="width:170px;padding-left:2.2rem;font-weight:700;" placeholder="20000">
        </div>
      </div>
      <button type="submit" class="e-btn e-btn-primary"><i class="bi bi-calculator-fill"></i> Hitung & Filter</button>
    </form>
  </div>
</div>

<!-- Cards Stats Ringkasan Uang Makan -->
<div class="row g-3" style="margin-bottom:1.25rem;">
  <div class="col-md-4">
    <div class="e-card" style="background:linear-gradient(135deg,rgba(16,185,129,.08),rgba(5,150,105,.02));border:1px solid rgba(16,185,129,.2);">
      <div class="e-card-body" style="padding:1rem 1.25rem;">
        <div style="font-size:.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Tarif Uang Makan / Hari</div>
        <div style="font-size:1.5rem;font-weight:900;color:var(--green);margin-top:.2rem;">Rp <?php echo number_format($uang_makan, 0, ',', '.'); ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="e-card" style="background:linear-gradient(135deg,rgba(79,142,247,.08),rgba(37,99,235,.02));border:1px solid rgba(79,142,247,.2);">
      <div class="e-card-body" style="padding:1rem 1.25rem;">
        <div style="font-size:.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Total Kehadiran (Hari)</div>
        <div style="font-size:1.5rem;font-weight:900;color:var(--blue);margin-top:.2rem;"><?php echo number_format($total_hadir_semua); ?> Hari</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="e-card" style="background:linear-gradient(135deg,rgba(139,92,246,.08),rgba(124,58,237,.02));border:1px solid rgba(139,92,246,.2);">
      <div class="e-card-body" style="padding:1rem 1.25rem;">
        <div style="font-size:.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Total Anggaran Uang Makan</div>
        <div style="font-size:1.5rem;font-weight:900;color:var(--purple);margin-top:.2rem;">Rp <?php echo number_format($total_uang_makan_semua, 0, ',', '.'); ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Rekap Table -->
<div class="e-table-wrap">
  <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border-light);background:var(--bg-muted);display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
    <div class="e-card-title"><i class="bi bi-table" style="color:var(--teal);"></i> Rekap Bulanan <span class="e-badge" style="background:rgba(20,184,166,.1);color:var(--teal);"><?php echo count($rekap); ?> Pegawai</span></div>
    <div class="e-search" style="margin-left:auto;"><i class="bi bi-search e-search-icon"></i><input type="text" id="tblSearch" placeholder="Cari pegawai..." oninput="filterTable(this.value)"></div>
  </div>
  <div style="overflow-x:auto;">
    <table class="e-table" id="mainTable">
      <thead><tr>
        <th style="width:40px;">#</th>
        <th>Nama Pegawai</th>
        <th>NIP</th>
        <th>Pangkat</th>
        <th style="text-align:center;color:var(--green);">Hadir</th>
        <th style="text-align:center;color:var(--red);">Alpa</th>
        <th style="text-align:center;color:var(--blue);">Izin</th>
        <th style="text-align:center;color:var(--amber);">Sakit</th>
        <th style="text-align:center;color:var(--purple);">Cuti</th>
        <th style="text-align:right;color:var(--green);">Est. Uang Makan</th>
        <th style="text-align:right;">Detail</th>
      </tr></thead>
      <tbody>
        <?php if (!empty($rekap)): $no=1; foreach ($rekap as $r): $est_uang = $r['total_hadir'] * $uang_makan; ?>
        <tr>
          <td style="color:var(--text-faint);font-size:.75rem;font-weight:800;"><?php echo $no++; ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:.625rem;">
              <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#14b8a6,#06b6d4);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:800;flex-shrink:0;">
                <?php echo strtoupper(substr($r['nama'], 0, 1)); ?>
              </div>
              <span style="font-weight:700;"><?php echo htmlspecialchars($r['nama']); ?></span>
            </div>
          </td>
          <td><span class="e-badge-mono" style="font-size:.72rem;"><?php echo htmlspecialchars($r['nip']); ?></span></td>
          <td style="font-size:.8rem;color:var(--text-muted);"><?php echo htmlspecialchars($r['kepangkatan']); ?></td>
          <td style="text-align:center;font-weight:800;color:var(--green);"><?php echo $r['total_hadir']; ?></td>
          <td style="text-align:center;font-weight:800;color:var(--red);"><?php echo $r['total_tidak_hadir']; ?></td>
          <td style="text-align:center;font-weight:700;color:var(--blue);"><?php echo $r['total_izin']; ?></td>
          <td style="text-align:center;font-weight:700;color:var(--amber);"><?php echo $r['total_sakit']; ?></td>
          <td style="text-align:center;font-weight:700;color:var(--purple);"><?php echo $r['total_cuti']; ?></td>
          <td style="text-align:right;font-weight:800;color:var(--green);">Rp <?php echo number_format($est_uang, 0, ',', '.'); ?></td>
          <td style="text-align:right;">
            <a class="e-act-btn view" href="<?php echo BASE_URL; ?>absensi_detail?id=<?php echo $r['id']; ?>&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" title="Detail"><i class="bi bi-eye-fill"></i></a>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="11"><div class="e-empty"><i class="bi bi-calendar-x e-empty-icon"></i><p>Belum ada data absensi untuk periode ini.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
function filterTable(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#mainTable tbody tr').forEach(function(r){r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';});
}
</script>
