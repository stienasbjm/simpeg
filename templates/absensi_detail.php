<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/absensi_functions.php';
require_once __DIR__ . '/../src/modules/pegawai_functions.php';
require_admin();
$pid    = (int)($_GET['id'] ?? 0);
$bulan  = (int)($_GET['bulan'] ?? date('n'));
$tahun  = (int)($_GET['tahun'] ?? date('Y'));
$pegawai= get_pegawai_by_id($conn, $pid);
if (!$pegawai) { $_SESSION['error_message'] = "Pegawai tidak ditemukan."; header('Location:'.BASE_URL.'absensi'); exit(); }
$detail = get_detail_absensi_pegawai($conn, $pid, $bulan, $tahun);
$nama_bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
               7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$status_opt = ['hadir'=>'Hadir','tidak_hadir'=>'Tidak Hadir','izin'=>'Izin','sakit'=>'Sakit','cuti'=>'Cuti'];
$badge_cls  = ['hadir'=>'green','tidak_hadir'=>'red','izin'=>'blue','sakit'=>'amber','cuti'=>'purple'];
?>
<div class="e-page-header">
  <div>
    <div class="e-breadcrumb">
      <a href="<?php echo BASE_URL; ?>absensi?bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>">Absensi</a>
      <span class="e-breadcrumb-sep">/</span>
      <span style="color:var(--teal);font-weight:600;">Detail</span>
    </div>
    <h1 class="e-page-title">
      <div class="title-icon" style="background:linear-gradient(135deg,rgba(20,184,166,.15),rgba(94,234,212,.08));color:var(--teal);">
        <i class="bi bi-person-check-fill"></i>
      </div>
      <?php echo htmlspecialchars($pegawai['nama']); ?>
    </h1>
    <p class="e-page-sub">Detail absensi — <?php echo $nama_bulan[$bulan] . ' ' . $tahun; ?></p>
  </div>
  <a href="<?php echo BASE_URL; ?>absensi?bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" class="e-btn e-btn-ghost"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php if (!empty($_SESSION['success_message'])): ?>
<div class="e-notice success" data-dismiss><i class="bi bi-check-circle-fill"></i><span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error_message'])): ?>
<div class="e-notice danger" data-dismiss><i class="bi bi-exclamation-triangle-fill"></i><span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span></div>
<?php endif; ?>

<div class="row g-3">
  <!-- Info Pegawai -->
  <div class="col-lg-4">
    <div class="e-card">
      <div class="e-card-header"><div class="e-card-title"><i class="bi bi-person-fill"></i> Info Pegawai</div></div>
      <div class="e-card-body">
        <div style="text-align:center;margin-bottom:1rem;">
          <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#14b8a6,#06b6d4);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:900;margin:0 auto .75rem;">
            <?php echo strtoupper(substr($pegawai['nama'], 0, 1)); ?>
          </div>
          <div style="font-weight:800;font-size:.95rem;"><?php echo htmlspecialchars($pegawai['nama']); ?></div>
          <div style="font-size:.78rem;color:var(--text-muted);"><?php echo htmlspecialchars($pegawai['nip']); ?></div>
        </div>
        <div class="e-dl">
          <div class="e-dl-row"><div class="e-dt">Pangkat</div><div class="e-dd" style="font-size:.8rem;"><?php echo htmlspecialchars($pegawai['kepangkatan']); ?></div></div>
          <div class="e-dl-row"><div class="e-dt">Status</div><div class="e-dd"><span class="e-badge indigo"><?php echo htmlspecialchars($pegawai['status_kepegawaian'] ?? 'PNS'); ?></span></div></div>
        </div>
      </div>
    </div>

    <!-- Tambah/Koreksi Absensi -->
    <div class="e-card" style="margin-top:1rem;">
      <div class="e-card-header"><div class="e-card-title"><i class="bi bi-plus-circle-fill" style="color:var(--teal);"></i> Tambah/Koreksi</div></div>
      <div class="e-card-body">
        <form method="POST" action="<?php echo BASE_URL; ?>absensi_save">
          <input type="hidden" name="action" value="admin_save">
          <input type="hidden" name="pegawai_id" value="<?php echo $pid; ?>">
          <div class="e-form-group">
            <label class="e-label">Tanggal</label>
            <input class="e-input" type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
          </div>
          <div class="e-form-group">
            <label class="e-label">Status</label>
            <select class="e-select" name="status">
              <?php foreach ($status_opt as $v => $l): ?>
              <option value="<?php echo $v; ?>"><?php echo $l; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-2">
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">Jam Masuk</label>
                <input class="e-input" type="time" name="jam_masuk">
              </div>
            </div>
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">Jam Pulang</label>
                <input class="e-input" type="time" name="jam_keluar">
              </div>
            </div>
          </div>
          <div class="e-form-group">
            <label class="e-label">Keterangan</label>
            <input class="e-input" type="text" name="keterangan" placeholder="Opsional">
          </div>
          <button type="submit" class="e-btn e-btn-primary" style="width:100%;justify-content:center;">
            <i class="bi bi-check-lg"></i> Simpan
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Detail Absensi -->
  <div class="col-lg-8">
    <div class="e-table-wrap">
      <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border-light);background:var(--bg-muted);">
        <div class="e-card-title"><i class="bi bi-list-check" style="color:var(--teal);"></i> Riwayat Absensi <span class="e-badge" style="background:rgba(20,184,166,.1);color:var(--teal);"><?php echo count($detail); ?> data</span></div>
      </div>
      <div style="overflow-x:auto;">
        <table class="e-table">
          <thead><tr>
            <th>Tanggal</th><th>Hari</th><th>Status</th><th>Jam Masuk</th><th>Jam Pulang</th><th>Keterangan</th><th style="text-align:right;">Aksi</th>
          </tr></thead>
          <tbody>
            <?php if (!empty($detail)): foreach ($detail as $d):
              $hari = ['Sun'=>'Minggu','Mon'=>'Senin','Tue'=>'Selasa','Wed'=>'Rabu','Thu'=>'Kamis','Fri'=>'Jumat','Sat'=>'Sabtu'];
              $day = date('D', strtotime($d['tanggal']));
              $cls = $badge_cls[$d['status']] ?? 'indigo';
            ?>
            <tr>
              <td style="font-weight:700;"><?php echo date('d M Y', strtotime($d['tanggal'])); ?></td>
              <td style="color:var(--text-muted);font-size:.8rem;"><?php echo $hari[$day] ?? $day; ?></td>
              <td><span class="e-badge <?php echo $cls; ?>"><?php echo $status_opt[$d['status']] ?? $d['status']; ?></span></td>
              <td style="font-size:.82rem;"><?php echo $d['jam_masuk'] ? substr($d['jam_masuk'],0,5) : '—'; ?></td>
              <td style="font-size:.82rem;"><?php echo $d['jam_keluar'] ? substr($d['jam_keluar'],0,5) : '—'; ?></td>
              <td style="font-size:.8rem;color:var(--text-muted);"><?php echo htmlspecialchars($d['keterangan'] ?? '—'); ?></td>
              <td style="text-align:right;">
                <a class="e-act-btn del" href="<?php echo BASE_URL; ?>absensi_save?action=admin_delete&id=<?php echo $d['id']; ?>&pid=<?php echo $pid; ?>" onclick="return confirm('Hapus data absensi ini?')"><i class="bi bi-trash3-fill"></i></a>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7"><div class="e-empty"><i class="bi bi-calendar-x e-empty-icon"></i><p>Belum ada data absensi bulan ini.</p></div></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
