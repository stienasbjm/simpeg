<?php
// templates/portal/absensi.php
require_once __DIR__ . '/../../src/modules/absensi_functions.php';
require_once __DIR__ . '/../../src/modules/pegawai_functions.php';
require_once __DIR__ . '/../../src/modules/izin_functions.php';

$pid      = get_session_pegawai_id();
$pegawai  = $pid ? get_pegawai_by_id($conn, $pid) : null;
$absensi_hari = $pid ? get_absensi_hari_ini($conn, $pid) : null;

$sudah_masuk  = !empty($absensi_hari['jam_masuk']);
$sudah_pulang = !empty($absensi_hari['jam_keluar']);

$bulan  = (int)date('n');
$tahun  = (int)date('Y');
$detail = $pid ? get_detail_absensi_pegawai($conn, $pid, $bulan, $tahun) : [];
$riwayat_izin = $pid ? get_pengajuan_izin_by_pegawai($conn, $pid, 10) : [];

$nama_bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
               7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$badge_cls = ['hadir'=>'green','tidak_hadir'=>'red','izin'=>'blue','sakit'=>'amber','cuti'=>'purple'];
$status_lbl= ['hadir'=>'Hadir','tidak_hadir'=>'Tidak Hadir','izin'=>'Izin','sakit'=>'Sakit','cuti'=>'Cuti'];

$jenis_izin_lbl    = ['izin'=>'Izin','sakit'=>'Sakit','dinas'=>'Dinas Luar'];
$jenis_izin_badge  = ['izin'=>'blue','sakit'=>'amber','dinas'=>'purple'];
$status_izin_badge = ['menunggu'=>'indigo','disetujui'=>'green','ditolak'=>'red'];
$status_izin_lbl   = ['menunggu'=>'Menunggu','disetujui'=>'Disetujui','ditolak'=>'Ditolak'];
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

<?php if ($pid && $pegawai): ?>

<!-- ══════════════════════════════════════════════════════
     FORM PENGAJUAN IZIN / SAKIT / DINAS
     ══════════════════════════════════════════════════════ -->
<div class="e-card" style="margin-top:1.5rem;">
  <div class="e-card-header">
    <div class="e-card-title">
      <i class="bi bi-file-earmark-medical" style="color:var(--blue);"></i>
      Pengajuan Izin / Sakit / Dinas
    </div>
  </div>
  <div class="e-card-body" style="padding:1.5rem 2rem 2rem;">

    <form method="POST" action="<?php echo BASE_URL; ?>izin_save"
          enctype="multipart/form-data" id="formIzin">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="kirim_izin">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">

        <!-- Jenis Pengajuan -->
        <div>
          <label class="e-label" for="jenis">
            <i class="bi bi-tag" style="color:var(--indigo);margin-right:.3rem;"></i>
            Jenis Pengajuan <span style="color:var(--red);">*</span>
          </label>
          <select class="e-input" name="jenis" id="jenis" required>
            <option value="izin">🔵 Izin</option>
            <option value="sakit">🟡 Sakit</option>
            <option value="dinas">🟣 Dinas Luar</option>
          </select>
        </div>

        <!-- Tanggal -->
        <div>
          <label class="e-label" for="tanggal">
            <i class="bi bi-calendar-event" style="color:var(--indigo);margin-right:.3rem;"></i>
            Tanggal <span style="color:var(--red);">*</span>
          </label>
          <input class="e-input" type="date" name="tanggal" id="tanggal"
                 value="<?php echo date('Y-m-d'); ?>" required>
        </div>
      </div>

      <!-- Keterangan -->
      <div style="margin-bottom:1rem;">
        <label class="e-label" for="keterangan">
          <i class="bi bi-chat-left-text" style="color:var(--indigo);margin-right:.3rem;"></i>
          Keterangan / Alasan <span style="color:var(--red);">*</span>
        </label>
        <textarea class="e-input" name="keterangan" id="keterangan" rows="3"
                  placeholder="Jelaskan alasan izin, gejala sakit, atau keperluan dinas..." required
                  style="resize:vertical;min-height:80px;"></textarea>
      </div>

      <!-- Upload Bukti -->
      <div style="margin-bottom:1.5rem;">
        <label class="e-label" for="file_bukti">
          <i class="bi bi-paperclip" style="color:var(--indigo);margin-right:.3rem;"></i>
          Lampiran Bukti
          <span style="font-weight:400;color:var(--text-muted);font-size:.75rem;margin-left:.35rem;">
            (Opsional — Surat dokter, surat tugas, dll. JPG/PNG/PDF maks. 5 MB)
          </span>
        </label>

        <!-- Dropzone custom -->
        <div id="dropzone" onclick="document.getElementById('file_bukti').click()"
             style="border:2px dashed var(--border-medium);border-radius:var(--r-lg);
                    padding:1.5rem 1rem;text-align:center;cursor:pointer;
                    transition:all .25s ease;background:var(--bg-muted);">
          <i class="bi bi-cloud-arrow-up" id="dropIcon"
             style="font-size:2rem;color:var(--text-faint);display:block;margin-bottom:.5rem;"></i>
          <div id="dropLabel" style="font-size:.85rem;color:var(--text-muted);">
            Klik atau seret file ke sini
          </div>
          <div id="dropFilename" style="font-size:.8rem;font-weight:700;color:var(--indigo);margin-top:.35rem;display:none;"></div>
        </div>
        <input type="file" name="file_bukti" id="file_bukti"
               accept=".jpg,.jpeg,.png,.webp,.pdf"
               style="display:none;" onchange="handleFileSelect(this)">
      </div>

      <!-- Info box -->
      <div style="background:rgba(99,102,241,.07);border:1px solid rgba(99,102,241,.18);
                  border-radius:var(--r-md);padding:.85rem 1rem;
                  font-size:.8rem;color:var(--text-secondary);margin-bottom:1.5rem;
                  display:flex;align-items:flex-start;gap:.65rem;">
        <i class="bi bi-info-circle-fill" style="color:var(--indigo);flex-shrink:0;margin-top:.1rem;"></i>
        <span>Pengajuan akan <strong>menunggu persetujuan admin</strong>.
          Setelah disetujui, status absensi Anda pada tanggal tersebut akan diperbarui secara otomatis.
        </span>
      </div>

      <button type="submit" class="e-btn e-btn-primary" id="btnKirimIzin"
              style="padding:.85rem 2rem;font-size:.9rem;">
        <i class="bi bi-send-fill"></i>
        Kirim Pengajuan
      </button>
    </form>

  </div>
</div>

<!-- ══════════════════════════════════════════════════════
     RIWAYAT PENGAJUAN IZIN / SAKIT / DINAS
     ══════════════════════════════════════════════════════ -->
<div class="e-card" style="margin-top:1.5rem;">
  <div class="e-card-header">
    <div class="e-card-title">
      <i class="bi bi-clock-history" style="color:var(--purple);"></i>
      Riwayat Pengajuan
    </div>
    <span class="e-badge purple"><?php echo count($riwayat_izin); ?> pengajuan</span>
  </div>
  <div style="overflow-x:auto;">
    <table class="e-table">
      <thead><tr>
        <th>Tanggal</th>
        <th>Jenis</th>
        <th>Keterangan</th>
        <th>Bukti</th>
        <th>Status</th>
        <th>Catatan Admin</th>
      </tr></thead>
      <tbody>
        <?php if (!empty($riwayat_izin)): foreach ($riwayat_izin as $iz):
          $jbadge = $jenis_izin_badge[$iz['jenis']]  ?? 'indigo';
          $sbadge = $status_izin_badge[$iz['status']] ?? 'indigo';
        ?>
        <tr>
          <td style="font-weight:700;white-space:nowrap;">
            <?php echo date('d M Y', strtotime($iz['tanggal'])); ?>
          </td>
          <td>
            <span class="e-badge <?php echo $jbadge; ?>">
              <?php echo $jenis_izin_lbl[$iz['jenis']] ?? $iz['jenis']; ?>
            </span>
          </td>
          <td style="font-size:.82rem;max-width:200px;">
            <?php echo htmlspecialchars($iz['keterangan'] ?? '—'); ?>
          </td>
          <td style="text-align:center;">
            <?php if ($iz['file_bukti']): ?>
              <a href="<?php echo BASE_URL . 'public/uploads/izin/' . htmlspecialchars($iz['file_bukti']); ?>"
                 target="_blank" class="e-btn" style="padding:.3rem .7rem;font-size:.75rem;">
                <i class="bi bi-eye"></i> Lihat
              </a>
            <?php else: ?>
              <span style="color:var(--text-faint);font-size:.8rem;">—</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="e-badge <?php echo $sbadge; ?>">
              <?php echo $status_izin_lbl[$iz['status']] ?? $iz['status']; ?>
            </span>
          </td>
          <td style="font-size:.8rem;color:var(--text-muted);">
            <?php echo htmlspecialchars($iz['catatan_admin'] ?? '—'); ?>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6">
          <div class="e-empty">
            <i class="bi bi-file-earmark-x e-empty-icon"></i>
            <p>Belum ada pengajuan izin / sakit / dinas.</p>
          </div>
        </td></tr>
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

// File upload preview & drag-and-drop
function handleFileSelect(input) {
  const dropzone = document.getElementById('dropzone');
  const dropLabel = document.getElementById('dropLabel');
  const dropFilename = document.getElementById('dropFilename');
  const dropIcon = document.getElementById('dropIcon');

  if (input.files && input.files[0]) {
    const file = input.files[0];
    dropFilename.textContent = '📎 ' + file.name + ' (' + (file.size / (1024 * 1024)).toFixed(2) + ' MB)';
    dropFilename.style.display = 'block';
    dropLabel.textContent = 'File terpilih:';
    if (dropIcon) {
      dropIcon.className = 'bi bi-file-earmark-check-fill';
      dropIcon.style.color = 'var(--green)';
    }
    if (dropzone) {
      dropzone.style.borderColor = 'var(--green)';
      dropzone.style.background = 'rgba(16,185,129,.05)';
    }
  } else {
    dropFilename.style.display = 'none';
    dropLabel.textContent = 'Klik atau seret file ke sini';
    if (dropIcon) {
      dropIcon.className = 'bi bi-cloud-arrow-up';
      dropIcon.style.color = 'var(--text-faint)';
    }
    if (dropzone) {
      dropzone.style.borderColor = 'var(--border-medium)';
      dropzone.style.background = 'var(--bg-muted)';
    }
  }
}

// Drag & drop listeners
const dropzone = document.getElementById('dropzone');
if (dropzone) {
  ['dragenter', 'dragover'].forEach(eventName => {
    dropzone.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropzone.style.borderColor = 'var(--indigo)';
      dropzone.style.background = 'rgba(99,102,241,.08)';
    }, false);
  });

  ['dragleave', 'drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
      const fileInput = document.getElementById('file_bukti');
      if (!fileInput || !fileInput.files.length) {
        dropzone.style.borderColor = 'var(--border-medium)';
        dropzone.style.background = 'var(--bg-muted)';
      }
    }, false);
  });

  dropzone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    const fileInput = document.getElementById('file_bukti');
    if (fileInput && files.length) {
      fileInput.files = files;
      handleFileSelect(fileInput);
    }
  }, false);
}

// Submit loading state for Izin form
const formIzin = document.getElementById('formIzin');
const btnKirimIzin = document.getElementById('btnKirimIzin');
if (formIzin && btnKirimIzin) {
  formIzin.addEventListener('submit', function() {
    btnKirimIzin.disabled = true;
    btnKirimIzin.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Mengirim...';
  });
}
</script>
