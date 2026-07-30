<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/absensi_functions.php';
require_once __DIR__ . '/../src/modules/pegawai_functions.php';

require_admin();

$akun_list = get_all_akun_pegawai($conn);
$total     = count($akun_list);
$all_peg   = get_all_pegawai($conn);
?>

<div class="e-page-header">
  <div>
    <div class="e-breadcrumb">
      <i class="bi bi-house-fill" style="font-size:.7rem;"></i>
      <a href="<?php echo BASE_URL; ?>dashboard">Beranda</a>
      <span class="e-breadcrumb-sep">/</span>
      <span style="color:var(--indigo);font-weight:600;">Akun Pegawai</span>
    </div>
    <h1 class="e-page-title">
      <div class="title-icon" style="background:linear-gradient(135deg,rgba(99,102,241,.15),rgba(167,139,250,.08));color:var(--indigo);">
        <i class="bi bi-person-lock"></i>
      </div>
      Manajemen Akun Pegawai
    </h1>
    <p class="e-page-sub">Kelola akun login pegawai (sinkron otomatis dengan data kepegawaian). Total <strong><?php echo $total; ?></strong> akun aktif.</p>
  </div>
  <a href="<?php echo BASE_URL; ?>akun_pegawai_add" class="e-btn e-btn-primary">
    <i class="bi bi-person-plus-fill"></i> Buat Akun Pegawai
  </a>
</div>

<?php if (!empty($_SESSION['success_message'])): ?>
<div class="e-notice success" data-dismiss><i class="bi bi-check-circle-fill"></i> <span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error_message'])): ?>
<div class="e-notice danger" data-dismiss><i class="bi bi-exclamation-triangle-fill"></i> <span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span></div>
<?php endif; ?>

<!-- Notice info sync & login -->
<div class="e-notice info" style="margin-bottom:1.25rem;">
  <i class="bi bi-info-circle-fill"></i>
  <span><strong>Sinkronisasi Otomatis:</strong> Nama akun tersinkronisasi otomatis dengan Data Kepegawaian. Pegawai dapat login menggunakan <strong>Username</strong> atau <strong>NIP</strong>.</span>
</div>

<div class="e-table-wrap">
  <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;background:var(--bg-muted);">
    <div class="e-card-title">
      <i class="bi bi-table" style="color:var(--indigo);"></i>
      Daftar Akun Pegawai
      <span class="e-badge indigo"><?php echo $total; ?></span>
    </div>
    <div class="e-search">
      <i class="bi bi-search e-search-icon"></i>
      <input type="text" id="tblSearch" placeholder="Cari akun / NIP..." oninput="filterTable(this.value)">
    </div>
  </div>
  <div style="overflow-x:auto;">
    <table class="e-table" id="mainTable">
      <thead>
        <tr>
          <th style="width:44px;">#</th>
          <th>Nama Pegawai (Tersinkron)</th>
          <th>Username / NIP Login</th>
          <th>Status Pegawai</th>
          <th>Jabatan</th>
          <th style="text-align:right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($akun_list)): $no=1; foreach ($akun_list as $r): ?>
        <tr>
          <td style="color:var(--text-faint);font-size:.75rem;font-weight:800;"><?php echo $no++; ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:.625rem;">
              <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a78bfa);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;flex-shrink:0;">
                <?php echo strtoupper(substr($r['nama'] ?? $r['nama_lengkap'] ?? 'P', 0, 1)); ?>
              </div>
              <div>
                <span style="font-weight:700;color:var(--text-primary);"><?php echo htmlspecialchars($r['nama'] ?? $r['nama_lengkap']); ?></span>
                <div style="font-size:.72rem;color:var(--text-muted);"><i class="bi bi-arrow-repeat" style="color:var(--green);"></i> Sync ke pegawai #<?php echo $r['pegawai_id']; ?></div>
              </div>
            </div>
          </td>
          <td>
            <div style="display:flex;flex-direction:column;gap:.15rem;">
              <span class="e-badge-mono" style="font-weight:700;"><?php echo htmlspecialchars($r['username']); ?></span>
              <span style="font-size:.72rem;color:var(--text-muted);">NIP: <?php echo htmlspecialchars($r['nip'] ?? '—'); ?></span>
            </div>
          </td>
          <td>
            <span class="e-badge blue"><?php echo htmlspecialchars($r['kepangkatan'] ?? 'Pegawai'); ?></span>
          </td>
          <td style="font-size:.82rem;color:var(--text-secondary);"><?php echo htmlspecialchars($r['jabatan_fungsional'] ?? '—'); ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:.35rem;justify-content:flex-end;">
              <!-- Tombol Edit Akun -->
              <button class="e-act-btn edit" title="Edit Akun" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($r)); ?>)">
                <i class="bi bi-pencil-fill"></i>
              </button>
              <!-- Tombol Reset Password -->
              <button class="e-act-btn view" title="Reset Password" onclick="showResetModal(<?php echo $r['id']; ?>, '<?php echo htmlspecialchars($r['username']); ?>')">
                <i class="bi bi-key-fill"></i>
              </button>
              <!-- Tombol Hapus Akun -->
              <a class="e-act-btn del" href="<?php echo BASE_URL; ?>akun_pegawai_delete?id=<?php echo $r['id']; ?>" title="Hapus Akun" onclick="return confirm('Apakah Anda yakin ingin menghapus akun pegawai <?php echo htmlspecialchars($r['username']); ?>?')">
                <i class="bi bi-trash3-fill"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6"><div class="e-empty"><i class="bi bi-person-x e-empty-icon"></i><p>Belum ada akun pegawai.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Edit Akun Pegawai -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:var(--bg-card);border:1px solid var(--border-light);border-radius:var(--r-xl);padding:1.75rem;width:100%;max-width:440px;box-shadow:var(--shadow-xl);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h3 style="font-size:1.05rem;font-weight:800;margin:0;color:var(--text-primary);">
        <i class="bi bi-pencil-square" style="color:var(--indigo);"></i> Edit Akun Pegawai
      </h3>
      <button type="button" class="e-btn e-btn-ghost" style="padding:.25rem .5rem;" onclick="closeEditModal()">&times;</button>
    </div>
    <form method="POST" action="<?php echo BASE_URL; ?>akun_pegawai_save">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="user_id" id="editUserId">
      <div class="e-form-group">
        <label class="e-label">Terhubung ke Data Pegawai <span class="req">*</span></label>
        <select class="e-select" name="pegawai_id" id="editPegawaiId" required>
          <?php foreach ($all_peg as $p): ?>
          <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nama']); ?> — <?php echo htmlspecialchars($p['nip']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="e-form-group">
        <label class="e-label">Username Login <span class="req">*</span></label>
        <input class="e-input" type="text" name="username" id="editUsername" required autocomplete="off">
      </div>
      <div class="e-form-group">
        <label class="e-label">Password Baru <span style="font-weight:400;color:var(--text-muted);">(Opsional, isi jika diubah)</span></label>
        <input class="e-input" type="password" name="password" placeholder="Kosongkan jika tidak diubah" minlength="6">
      </div>
      <div style="display:flex;gap:.5rem;margin-top:1.5rem;">
        <button type="submit" class="e-btn e-btn-primary" style="flex:1;"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
        <button type="button" class="e-btn e-btn-ghost" onclick="closeEditModal()">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Reset Password -->
<div id="resetModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:var(--bg-card);border:1px solid var(--border-light);border-radius:var(--r-xl);padding:1.75rem;width:100%;max-width:380px;box-shadow:var(--shadow-xl);">
    <h3 style="font-size:1rem;font-weight:800;margin:0 0 .25rem;color:var(--text-primary);">Reset Password Pegawai</h3>
    <p style="font-size:.8rem;color:var(--text-muted);margin:0 0 1.25rem;">Username: <strong id="resetUsername"></strong></p>
    <form method="POST" action="<?php echo BASE_URL; ?>akun_pegawai_save">
      <input type="hidden" name="action" value="reset_password">
      <input type="hidden" name="user_id" id="resetUserId">
      <div class="e-form-group">
        <label class="e-label">Password Baru <span class="req">*</span></label>
        <input class="e-input" type="password" name="password" placeholder="Min. 6 karakter" required minlength="6">
      </div>
      <div style="display:flex;gap:.5rem;margin-top:1.25rem;">
        <button type="submit" class="e-btn e-btn-primary" style="flex:1;"><i class="bi bi-check-lg"></i> Simpan</button>
        <button type="button" class="e-btn e-btn-ghost" onclick="closeResetModal()">Batal</button>
      </div>
    </form>
  </div>
</div>

<script>
function filterTable(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#mainTable tbody tr').forEach(function(r) {
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
function showEditModal(user) {
  document.getElementById('editUserId').value = user.id;
  document.getElementById('editUsername').value = user.username;
  document.getElementById('editPegawaiId').value = user.pegawai_id;
  document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
}
function showResetModal(id, uname) {
  document.getElementById('resetUserId').value = id;
  document.getElementById('resetUsername').textContent = uname;
  document.getElementById('resetModal').style.display = 'flex';
}
function closeResetModal() {
  document.getElementById('resetModal').style.display = 'none';
}

document.getElementById('editModal').addEventListener('click', function(e) { if (e.target === this) closeEditModal(); });
document.getElementById('resetModal').addEventListener('click', function(e) { if (e.target === this) closeResetModal(); });
</script>
