<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/akun_admin_functions.php';

require_developer();

$admin_list = get_all_admin_users($conn);
$total = count($admin_list);
$current_id = $_SESSION['user_id'] ?? 0;
?>

<div class="e-page-header">
  <div>
    <div class="e-breadcrumb">
      <i class="bi bi-house-fill" style="font-size:.7rem;"></i>
      <a href="<?php echo BASE_URL; ?>dashboard">Beranda</a>
      <span class="e-breadcrumb-sep">/</span>
      <span style="color:var(--indigo);font-weight:600;">Akun Administrator</span>
    </div>
    <h1 class="e-page-title">
      <div class="title-icon" style="background:linear-gradient(135deg,rgba(99,102,241,.15),rgba(167,139,250,.08));color:var(--indigo);">
        <i class="bi bi-shield-lock-fill"></i>
      </div>
      Kelola Akun Admin & Developer
    </h1>
    <p class="e-page-sub">Kelola akun administrator dan akun pengembang (developer) sistem. Total <strong><?php echo $total; ?></strong> akun.</p>
  </div>
  <button type="button" class="e-btn e-btn-primary" onclick="showAddModal()">
    <i class="bi bi-person-plus-fill"></i> Tambah Akun Admin
  </button>
</div>

<?php if (!empty($_SESSION['success_message'])): ?>
<div class="e-notice success" data-dismiss><i class="bi bi-check-circle-fill"></i> <span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error_message'])): ?>
<div class="e-notice danger" data-dismiss><i class="bi bi-exclamation-triangle-fill"></i> <span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span></div>
<?php endif; ?>

<div class="e-table-wrap">
  <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;background:var(--bg-muted);">
    <div class="e-card-title">
      <i class="bi bi-table" style="color:var(--indigo);"></i>
      Daftar Pengguna Administrator & Developer
      <span class="e-badge indigo"><?php echo $total; ?></span>
    </div>
    <div class="e-search">
      <i class="bi bi-search e-search-icon"></i>
      <input type="text" id="tblSearch" placeholder="Cari admin/dev..." oninput="filterTable(this.value)">
    </div>
  </div>
  <div style="overflow-x:auto;">
    <table class="e-table" id="mainTable">
      <thead>
        <tr>
          <th style="width:44px;">#</th>
          <th>Nama Lengkap</th>
          <th>Username</th>
          <th>Hak Akses / Role</th>
          <th>Tgl Dibuat</th>
          <th style="text-align:right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($admin_list)): $no=1; foreach ($admin_list as $r):
          $is_dev = ($r['role'] === 'developer');
          $is_ben = ($r['role'] === 'bendahara');
          $is_self = ($r['id'] == $current_id);
          
          if ($is_dev) {
            $avatar_bg = 'linear-gradient(135deg,#8b5cf6,#c084fc)';
            $badge_cls = 'purple';
            $badge_ico = 'bi-code-slash';
            $badge_lbl = 'Developer';
          } elseif ($is_ben) {
            $avatar_bg = 'linear-gradient(135deg,#059669,#34d399)';
            $badge_cls = 'green';
            $badge_ico = 'bi-wallet2';
            $badge_lbl = 'Bendahara';
          } else {
            $avatar_bg = 'linear-gradient(135deg,#3b82f6,#60a5fa)';
            $badge_cls = 'blue';
            $badge_ico = 'bi-shield-check';
            $badge_lbl = 'Administrator';
          }
        ?>
        <tr>
          <td style="color:var(--text-faint);font-size:.75rem;font-weight:800;"><?php echo $no++; ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:.625rem;">
              <div style="width:32px;height:32px;border-radius:50%;background:<?php echo $avatar_bg; ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;flex-shrink:0;">
                <?php echo strtoupper(substr($r['nama_lengkap'], 0, 1)); ?>
              </div>
              <div>
                <span style="font-weight:700;color:var(--text-primary);"><?php echo htmlspecialchars($r['nama_lengkap']); ?></span>
                <?php if ($is_self): ?>
                <span class="e-badge green" style="font-size:.65rem;margin-left:.3rem;">Saya</span>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td><span class="e-badge-mono"><?php echo htmlspecialchars($r['username']); ?></span></td>
          <td>
            <span class="e-badge <?php echo $badge_cls; ?>">
              <i class="bi <?php echo $badge_ico; ?>" style="font-size:.68rem;"></i>
              <?php echo $badge_lbl; ?>
            </span>
          </td>
          <td style="font-size:.8rem;color:var(--text-muted);">
            <?php echo !empty($r['created_at']) ? date('d M Y H:i', strtotime($r['created_at'])) : '—'; ?>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:.35rem;justify-content:flex-end;">
              <button class="e-act-btn edit" title="Edit Akun" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($r)); ?>)">
                <i class="bi bi-pencil-fill"></i>
              </button>
              <?php if (!$is_self): ?>
              <a class="e-act-btn del" href="<?php echo BASE_URL; ?>akun_admin_delete?id=<?php echo $r['id']; ?>" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus akun <?php echo htmlspecialchars($r['username']); ?>?')">
                <i class="bi bi-trash3-fill"></i>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6"><div class="e-empty"><i class="bi bi-shield-x e-empty-icon"></i><p>Belum ada akun admin/dev.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah Akun -->
<div id="addModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:var(--bg-card);border:1px solid var(--border-light);border-radius:var(--r-xl);padding:1.75rem;width:100%;max-width:440px;box-shadow:var(--shadow-xl);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h3 style="font-size:1.05rem;font-weight:800;margin:0;color:var(--text-primary);">
        <i class="bi bi-person-plus-fill" style="color:var(--indigo);"></i> Tambah Akun Admin / Developer
      </h3>
      <button type="button" class="e-btn e-btn-ghost" style="padding:.25rem .5rem;" onclick="closeAddModal()">&times;</button>
    </div>
    <form method="POST" action="<?php echo BASE_URL; ?>akun_admin_save">
      <input type="hidden" name="action" value="create">
      <div class="e-form-group">
        <label class="e-label">Nama Lengkap <span class="req">*</span></label>
        <input class="e-input" type="text" name="nama_lengkap" placeholder="Contoh: Developer Utama" required>
      </div>
      <div class="e-form-group">
        <label class="e-label">Username <span class="req">*</span></label>
        <input class="e-input" type="text" name="username" placeholder="Contoh: dev_admin" required autocomplete="off">
      </div>
      <div class="e-form-group">
        <label class="e-label">Password <span class="req">*</span></label>
        <input class="e-input" type="password" name="password" placeholder="Min. 6 karakter" required minlength="6" autocomplete="new-password">
      </div>
      <div class="e-form-group">
        <label class="e-label">Hak Akses / Role <span class="req">*</span></label>
        <select class="e-select" name="role" required>
          <option value="admin">Administrator (Admin Kepegawaian)</option>
          <option value="bendahara">Bendahara (Bendahara Keuangan)</option>
          <option value="developer">Developer (Pengembang Sistem)</option>
        </select>
      </div>
      <div style="display:flex;gap:.5rem;margin-top:1.5rem;">
        <button type="submit" class="e-btn e-btn-primary" style="flex:1;"><i class="bi bi-check-lg"></i> Simpan Akun</button>
        <button type="button" class="e-btn e-btn-ghost" onclick="closeAddModal()">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Akun -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:var(--bg-card);border:1px solid var(--border-light);border-radius:var(--r-xl);padding:1.75rem;width:100%;max-width:440px;box-shadow:var(--shadow-xl);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
      <h3 style="font-size:1.05rem;font-weight:800;margin:0;color:var(--text-primary);">
        <i class="bi bi-pencil-square" style="color:var(--indigo);"></i> Edit Akun Admin / Developer
      </h3>
      <button type="button" class="e-btn e-btn-ghost" style="padding:.25rem .5rem;" onclick="closeEditModal()">&times;</button>
    </div>
    <form method="POST" action="<?php echo BASE_URL; ?>akun_admin_save">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="editId">
      <div class="e-form-group">
        <label class="e-label">Nama Lengkap <span class="req">*</span></label>
        <input class="e-input" type="text" name="nama_lengkap" id="editNama" required>
      </div>
      <div class="e-form-group">
        <label class="e-label">Username <span class="req">*</span></label>
        <input class="e-input" type="text" name="username" id="editUsername" required autocomplete="off">
      </div>
      <div class="e-form-group">
        <label class="e-label">Hak Akses / Role <span class="req">*</span></label>
        <select class="e-select" name="role" id="editRole" required>
          <option value="admin">Administrator (Admin Kepegawaian)</option>
          <option value="bendahara">Bendahara (Bendahara Keuangan)</option>
          <option value="developer">Developer (Pengembang Sistem)</option>
        </select>
      </div>
      <div class="e-form-group">
        <label class="e-label">Password Baru <span style="font-weight:400;color:var(--text-muted);">(Opsional, isi jika ingin diubah)</span></label>
        <input class="e-input" type="password" name="password" placeholder="Kosongkan jika tidak diubah" minlength="6" autocomplete="new-password">
      </div>
      <div style="display:flex;gap:.5rem;margin-top:1.5rem;">
        <button type="submit" class="e-btn e-btn-primary" style="flex:1;"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
        <button type="button" class="e-btn e-btn-ghost" onclick="closeEditModal()">Batal</button>
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
function showAddModal() {
  document.getElementById('addModal').style.display = 'flex';
}
function closeAddModal() {
  document.getElementById('addModal').style.display = 'none';
}
function showEditModal(user) {
  document.getElementById('editId').value = user.id;
  document.getElementById('editNama').value = user.nama_lengkap;
  document.getElementById('editUsername').value = user.username;
  document.getElementById('editRole').value = user.role;
  document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
}

document.getElementById('addModal').addEventListener('click', function(e) { if (e.target === this) closeAddModal(); });
document.getElementById('editModal').addEventListener('click', function(e) { if (e.target === this) closeEditModal(); });
</script>
