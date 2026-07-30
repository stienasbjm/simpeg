<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/surat_keluar_functions.php';
require_login();
$list = get_all_surat_keluar($conn);
$total = count($list);
?>

<div class="e-page-header">
  <div>
    <div class="e-breadcrumb">
      <i class="bi bi-house-fill" style="font-size:.7rem;"></i>
      <a href="<?php echo BASE_URL; ?>dashboard">Beranda</a>
      <span class="e-breadcrumb-sep">/</span>
      <span style="color:var(--green);font-weight:600;">Surat Keluar</span>
    </div>
    <h1 class="e-page-title">
      <div class="title-icon" style="background:linear-gradient(135deg,rgba(16,185,129,.15),rgba(110,231,183,.08));color:var(--green);">
        <i class="bi bi-send-fill"></i>
      </div>
      Surat Keluar
    </h1>
    <p class="e-page-sub">Arsip seluruh surat keluar resmi instansi. Total <strong><?php echo $total; ?></strong> dokumen.</p>
  </div>
  <a href="<?php echo BASE_URL; ?>surat_keluar_add" class="e-btn e-btn-primary">
    <i class="bi bi-plus-lg"></i> Tambah Surat Keluar
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
      <i class="bi bi-table" style="color:var(--green);"></i>
      Daftar Surat Keluar
      <span class="e-badge green" style="margin-left:.25rem;"><?php echo $total; ?></span>
    </div>
    <div class="e-search">
      <i class="bi bi-search e-search-icon"></i>
      <input type="text" id="tblSearch" placeholder="Cari surat..." oninput="filterTable(this.value)">
    </div>
  </div>
  <div style="overflow-x:auto;">
    <table class="e-table" id="mainTable">
      <thead>
        <tr>
          <th style="width:44px;">#</th>
          <th>Nomor Surat</th>
          <th>Tujuan</th>
          <th>Perihal</th>
          <th>Tgl Surat</th>
          <th style="text-align:right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($list)): $no=1; foreach ($list as $r): ?>
        <tr>
          <td style="color:var(--text-faint);font-size:.75rem;font-weight:700;"><?php echo $no++; ?></td>
          <td><span class="e-badge-mono"><?php echo htmlspecialchars($r['nomor_surat']); ?></span></td>
          <td style="font-weight:500;color:var(--text-primary);"><?php echo htmlspecialchars($r['tujuan']); ?></td>
          <td style="max-width:240px;">
            <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($r['perihal']); ?>">
              <?php echo htmlspecialchars($r['perihal']); ?>
            </div>
          </td>
          <td style="white-space:nowrap;color:var(--text-muted);font-size:.8rem;">
            <i class="bi bi-calendar3" style="font-size:.7rem;opacity:.7;"></i>
            <?php echo date('d M Y', strtotime($r['tanggal_surat'])); ?>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:.3rem;justify-content:flex-end;">
              <a class="e-act-btn view" href="<?php echo BASE_URL; ?>surat_keluar_detail?id=<?php echo $r['id']; ?>" title="Detail"><i class="bi bi-eye-fill"></i></a>
              <a class="e-act-btn edit" href="<?php echo BASE_URL; ?>surat_keluar_edit?id=<?php echo $r['id']; ?>" title="Edit"><i class="bi bi-pencil-fill"></i></a>
              <a class="e-act-btn del"  href="<?php echo BASE_URL; ?>surat_keluar_delete?id=<?php echo $r['id']; ?>" title="Hapus" onclick="return confirm('Hapus surat keluar ini?');"><i class="bi bi-trash3-fill"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6">
          <div class="e-empty"><i class="bi bi-send e-empty-icon"></i><p>Belum ada data surat keluar.</p></div>
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
