<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/sk_functions.php';
require_login();
$list  = get_all_sk($conn);
$total = count($list);
?>

<div class="e-page-header">
  <div>
    <div class="e-breadcrumb">
      <i class="bi bi-house-fill" style="font-size:.7rem;"></i>
      <a href="<?php echo BASE_URL; ?>dashboard">Beranda</a>
      <span class="e-breadcrumb-sep">/</span>
      <span style="color:var(--amber);font-weight:600;">Surat Keputusan</span>
    </div>
    <h1 class="e-page-title">
      <div class="title-icon" style="background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(252,211,77,.08));color:var(--amber);">
        <i class="bi bi-file-earmark-text-fill"></i>
      </div>
      Surat Keputusan
    </h1>
    <p class="e-page-sub">Arsip dokumen Surat Keputusan instansi. Total <strong><?php echo $total; ?></strong> dokumen.</p>
  </div>
  <a href="<?php echo BASE_URL; ?>sk_add" class="e-btn e-btn-primary">
    <i class="bi bi-plus-lg"></i> Tambah SK
  </a>
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
      <i class="bi bi-table" style="color:var(--amber);"></i>
      Daftar Surat Keputusan
      <span class="e-badge amber"><?php echo $total; ?></span>
    </div>
    <div class="e-search">
      <i class="bi bi-search e-search-icon"></i>
      <input type="text" id="tblSearch" placeholder="Cari SK..." oninput="filterTable(this.value)">
    </div>
  </div>
  <div style="overflow-x:auto;">
    <table class="e-table" id="mainTable">
      <thead>
        <tr>
          <th style="width:44px;">#</th>
          <th>Nomor SK</th>
          <th>Tentang</th>
          <th>Tgl Penetapan</th>
          <th style="text-align:right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($list)): $no=1; foreach ($list as $r): ?>
        <tr>
          <td style="color:var(--text-faint);font-size:.75rem;font-weight:800;"><?php echo $no++; ?></td>
          <td><span class="e-badge-mono"><?php echo htmlspecialchars($r['nomor_sk']); ?></span></td>
          <td style="max-width:320px;">
            <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600;color:var(--text-primary);" title="<?php echo htmlspecialchars($r['tentang']); ?>">
              <?php echo htmlspecialchars($r['tentang']); ?>
            </div>
          </td>
          <td style="white-space:nowrap;color:var(--text-muted);font-size:.8rem;">
            <i class="bi bi-calendar3" style="font-size:.7rem;opacity:.6;"></i>
            <?php echo date('d M Y', strtotime($r['tanggal_sk'])); ?>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:.35rem;justify-content:flex-end;">
              <a class="e-act-btn view" href="<?php echo BASE_URL; ?>sk_detail?id=<?php echo $r['id']; ?>" title="Detail"><i class="bi bi-eye-fill"></i></a>
              <a class="e-act-btn edit" href="<?php echo BASE_URL; ?>sk_edit?id=<?php echo $r['id']; ?>" title="Edit"><i class="bi bi-pencil-fill"></i></a>
              <a class="e-act-btn del"  href="<?php echo BASE_URL; ?>sk_delete?id=<?php echo $r['id']; ?>" title="Hapus" onclick="return confirm('Hapus SK ini?');"><i class="bi bi-trash3-fill"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="5">
          <div class="e-empty"><i class="bi bi-file-earmark e-empty-icon"></i><p>Belum ada data Surat Keputusan.</p></div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function filterTable(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#mainTable tbody tr').forEach(function(row) {
    var text = row.textContent.toLowerCase();
    row.style.display = text.includes(q) ? '' : 'none';
  });
}
</script>
