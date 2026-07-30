<?php
// templates/portal/absensi.php
require_once __DIR__ . '/../../src/modules/absensi_functions.php';
require_once __DIR__ . '/../../src/modules/pegawai_functions.php';

$pid      = get_session_pegawai_id();
$pegawai  = $pid ? get_pegawai_by_id($conn, $pid) : null;
$absensi_hari = $pid ? get_absensi_hari_ini($conn, $pid) : null;

$sudah_masuk  = !empty($absensi_hari['jam_masuk']);
$sudah_pulang = !empty($absensi_hari['jam_keluar']);

$bulan  = (int)date('n');
$tahun  = (int)date('Y');
$detail = $pid ? get_detail_absensi_pegawai($conn, $pid, $bulan, $tahun) : [];
$nama_bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
               7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$badge_cls = ['hadir'=>'green','tidak_hadir'=>'red','izin'=>'blue','sakit'=>'amber','cuti'=>'purple'];
$status_lbl= ['hadir'=>'Hadir','tidak_hadir'=>'Tidak Hadir','izin'=>'Izin','sakit'=>'Sakit','cuti'=>'Cuti'];
?>

<?php if (!empty($_SESSION['success_message'])): ?>
<div class="e-notice success" data-dismiss><i class="bi bi-check-circle-fill"></i><span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error_message'])): ?>
<div class="e-notice danger" data-dismiss><i class="bi bi-exclamation-triangle-fill"></i><span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span></div>
<?php endif; ?>

<!-- Header tanggal & jam -->
<div style="text-align:center;margin-bottom:2rem;">
  <div style="font-size:.78rem;font-weight:700;color:var(--text-faint);text-transform:uppercase;letter-spacing:.1em;">
    <?php echo date('l, d F Y'); ?>
  </div>
  <div id="live-clock" style="font-size:3rem;font-weight:900;letter-spacing:-.05em;color:var(--text-primary);line-height:1.1;margin:.5rem 0;">
    <?php echo date('H:i:s'); ?>
  </div>
  <div style="font-size:.82rem;color:var(--text-muted);">Waktu Sistem</div>
</div>

<?php if (!$pid || !$pegawai): ?>
<div class="e-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>
  <span>Akun Anda belum terhubung ke data pegawai. Hubungi administrator.</span>
</div>
<?php else: ?>

<!-- Tombol Absensi -->
<div class="e-card" style="margin-bottom:1.5rem;">
  <div class="e-card-body" style="padding:2rem;">
    <div style="text-align:center;margin-bottom:1.5rem;">
      <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a78bfa);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:900;margin:0 auto .875rem;box-shadow:0 6px 20px rgba(99,102,241,.35);">
        <?php echo strtoupper(substr($pegawai['nama'], 0, 1)); ?>
      </div>
      <div style="font-weight:800;font-size:1.05rem;color:var(--text-primary);"><?php echo htmlspecialchars($pegawai['nama']); ?></div>
      <div style="font-size:.8rem;color:var(--text-muted);margin-top:.2rem;"><?php echo htmlspecialchars($pegawai['kepangkatan']); ?></div>
    </div>

    <?php if ($sudah_masuk && $sudah_pulang): ?>
    <!-- Selesai hari ini -->
    <div style="text-align:center;padding:1.5rem;background:rgba(16,185,129,.06);border:1px solid rgba(16,185,129,.2);border-radius:var(--r-lg);">
      <i class="bi bi-check-circle-fill" style="font-size:2rem;color:var(--green);display:block;margin-bottom:.5rem;"></i>
      <div style="font-weight:800;color:var(--green);margin-bottom:.25rem;">Absensi Hari Ini Lengkap</div>
      <div style="font-size:.82rem;color:var(--text-muted);">
        Masuk: <strong><?php echo substr($absensi_hari['jam_masuk'],0,5); ?></strong>
        &nbsp;·&nbsp;
        Pulang: <strong><?php echo substr($absensi_hari['jam_keluar'],0,5); ?></strong>
      </div>
    </div>

    <?php else: ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <!-- Absen Masuk -->
      <form method="POST" action="<?php echo BASE_URL; ?>absensi_action">
        <input type="hidden" name="action" value="masuk">
        <button type="submit" class="e-btn e-btn-primary"
          style="width:100%;justify-content:center;padding:1.1rem;font-size:.9rem;border-radius:var(--r-lg);<?php echo $sudah_masuk ? 'opacity:.5;pointer-events:none;' : ''; ?>"
          <?php echo $sudah_masuk ? 'disabled' : ''; ?>>
          <i class="bi bi-box-arrow-in-right" style="font-size:1.1rem;"></i>
          <div>
            <div>Absen Masuk</div>
            <?php if ($sudah_masuk): ?>
            <div style="font-size:.7rem;opacity:.8;">✓ <?php echo substr($absensi_hari['jam_masuk'],0,5); ?></div>
            <?php endif; ?>
          </div>
        </button>
      </form>
      <!-- Absen Pulang -->
      <form method="POST" action="<?php echo BASE_URL; ?>absensi_action">
        <input type="hidden" name="action" value="pulang">
        <button type="submit" class="e-btn"
          style="width:100%;justify-content:center;padding:1.1rem;font-size:.9rem;border-radius:var(--r-lg);background:linear-gradient(135deg,#10b981,#059669);color:#fff;border-color:#047857;box-shadow:0 4px 12px rgba(16,185,129,.3);<?php echo (!$sudah_masuk||$sudah_pulang) ? 'opacity:.5;pointer-events:none;' : ''; ?>"
          <?php echo (!$sudah_masuk || $sudah_pulang) ? 'disabled' : ''; ?>>
          <i class="bi bi-box-arrow-right" style="font-size:1.1rem;"></i>
          <div>
            <div>Absen Pulang</div>
            <?php if ($sudah_pulang): ?>
            <div style="font-size:.7rem;opacity:.8;">✓ <?php echo substr($absensi_hari['jam_keluar'],0,5); ?></div>
            <?php endif; ?>
          </div>
        </button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Riwayat Bulan Ini -->
<div class="e-card">
  <div class="e-card-header">
    <div class="e-card-title"><i class="bi bi-calendar3" style="color:var(--indigo);"></i> Riwayat — <?php echo $nama_bulan[$bulan] . ' ' . $tahun; ?></div>
    <span class="e-badge indigo"><?php echo count($detail); ?> hari</span>
  </div>
  <div style="overflow-x:auto;">
    <table class="e-table">
      <thead><tr>
        <th>Tanggal</th><th>Status</th><th>Jam Masuk</th><th>Jam Pulang</th><th>Keterangan</th>
      </tr></thead>
      <tbody>
        <?php if (!empty($detail)): foreach (array_reverse($detail) as $d):
          $cls = $badge_cls[$d['status']] ?? 'indigo';
        ?>
        <tr>
          <td style="font-weight:700;"><?php echo date('d M', strtotime($d['tanggal'])); ?></td>
          <td><span class="e-badge <?php echo $cls; ?>"><?php echo $status_lbl[$d['status']] ?? $d['status']; ?></span></td>
          <td><?php echo $d['jam_masuk'] ? substr($d['jam_masuk'],0,5) : '—'; ?></td>
          <td><?php echo $d['jam_keluar'] ? substr($d['jam_keluar'],0,5) : '—'; ?></td>
          <td style="font-size:.8rem;color:var(--text-muted);"><?php echo htmlspecialchars($d['keterangan'] ?? '—'); ?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="5"><div class="e-empty"><i class="bi bi-calendar-x e-empty-icon"></i><p>Belum ada catatan absensi bulan ini.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
// Live clock
function updateClock() {
  const now = new Date();
  const h = String(now.getHours()).padStart(2,'0');
  const m = String(now.getMinutes()).padStart(2,'0');
  const s = String(now.getSeconds()).padStart(2,'0');
  const el = document.getElementById('live-clock');
  if (el) el.textContent = h + ':' + m + ':' + s;
}
setInterval(updateClock, 1000);
updateClock();
</script>
