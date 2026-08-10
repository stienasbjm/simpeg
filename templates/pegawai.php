<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/pegawai_functions.php';
require_login();
$list  = get_all_pegawai($conn);
$total = count($list);
$status_badge_cls = [
  'Dosen PNS'           => 'blue',
  'Dosen TY'            => 'purple',
  'Tenaga Kependidikan' => 'green',
  'Kontrak/Tidak tetap' => 'amber'
];
?>

<div class="e-page-header">
  <div>
    <div class="e-breadcrumb">
      <i class="bi bi-house-fill" style="font-size:.7rem;"></i>
      <a href="<?php echo BASE_URL; ?>dashboard">Beranda</a>
      <span class="e-breadcrumb-sep">/</span>
      <span style="color:var(--purple);font-weight:600;">Data Pegawai</span>
    </div>
    <h1 class="e-page-title">
      <div class="title-icon" style="background:linear-gradient(135deg,rgba(139,92,246,.15),rgba(196,181,253,.08));color:var(--purple);">
        <i class="bi bi-people-fill"></i>
      </div>
      Data Pegawai
    </h1>
    <p class="e-page-sub">Kelola data kepegawaian instansi. Total <strong><?php echo $total; ?></strong> pegawai terdaftar.</p>
  </div>
  <a href="<?php echo BASE_URL; ?>pegawai_add" class="e-btn e-btn-primary">
    <i class="bi bi-person-plus-fill"></i> Tambah Pegawai
  </a>
</div>

<?php if (!empty($_SESSION['success_message'])): ?>
<div class="e-notice success" data-dismiss><i class="bi bi-check-circle-fill"></i> <span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error_message'])): ?>
<div class="e-notice danger" data-dismiss><i class="bi bi-exclamation-triangle-fill"></i> <span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span></div>
<?php endif; ?>

<div class="e-table-wrap">
  <?php
  // Hitung jumlah pegawai pensiun & pegawai (dosen & tendik) yang waktunya naik pangkat/golongan
  $near_pensiun_list = [];
  $pegawai_due_list  = [];
  if (!empty($list)) {
      foreach ($list as $r) {
          $pi = hitung_status_pensiun($r['tanggal_lahir'] ?? null, $r['status_kepegawaian'] ?? '', $r['jabatan_fungsional'] ?? '');
          if ($pi['is_mendekati']) {
              $near_pensiun_list[] = $r['nama'] . ' (' . $pi['status_text'] . ')';
          }
          $kd = hitung_kenaikan_pangkat_pegawai($r);
          if ($kd['is_due'] || $kd['is_upcoming']) {
              $pegawai_due_list[] = $r['nama'] . ' [' . $kd['kategori_pegawai'] . '] (' . $kd['status_text'] . ')';
          }
      }
  }
  ?>

  <?php if (!empty($pegawai_due_list)): ?>
  <div style="padding:0.75rem 1.25rem; background:rgba(99,102,241,0.08); border-bottom:1px solid rgba(99,102,241,0.2); display:flex; align-items:center; gap:0.6rem; color:var(--indigo); font-size:0.82rem; font-weight:600;">
    <i class="bi bi-award-fill" style="font-size:1.1rem; color:var(--indigo);"></i>
    <div>
      <strong>Peringatan Kenaikan Pangkat/Golongan Pegawai:</strong> Terdapat <strong><?php echo count($pegawai_due_list); ?></strong> pegawai (Dosen / Tendik) yang sudah waktunya atau mendekati jadwal kenaikan pangkat/penyetaraan golongan.
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($near_pensiun_list)): ?>
  <div style="padding:0.75rem 1.25rem; background:rgba(239,68,68,0.08); border-bottom:1px solid rgba(239,68,68,0.2); display:flex; align-items:center; gap:0.6rem; color:var(--red); font-size:0.82rem; font-weight:600;">
    <i class="bi bi-bell-fill" style="font-size:1rem;"></i>
    <div>
      <strong>Pemberitahuan Pensiun:</strong> Terdapat <strong><?php echo count($near_pensiun_list); ?></strong> pegawai yang mendekati atau telah mencapai usia pensiun (BUP).
    </div>
  </div>
  <?php endif; ?>

  <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;background:var(--bg-muted);">
    <div class="e-card-title">
      <i class="bi bi-table" style="color:var(--purple);"></i>
      Daftar Pegawai
      <span class="e-badge purple"><?php echo $total; ?></span>
    </div>
    <div class="e-search">
      <i class="bi bi-search e-search-icon"></i>
      <input type="text" id="tblSearch" placeholder="Cari pegawai..." oninput="filterTable(this.value)">
    </div>
  </div>
  <div style="overflow-x:auto;">
    <table class="e-table" id="mainTable">
      <thead>
        <tr>
          <th style="width:44px;">#</th>
          <th>Nama Pegawai</th>
          <th>NIP</th>
          <th>Status & Jabatan</th>
          <th>Peringatan Naik Pangkat / Golongan</th>
          <th>Pemberitahuan Pensiun</th>
          <th style="text-align:right;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($list)): $no=1; foreach ($list as $r):
          $sc = $status_badge_cls[$r['status_kepegawaian'] ?? 'PNS'] ?? 'purple';
          $mk = hitung_masa_kerja($r['tanggal_masuk_kerja'] ?? null);
          $pi = hitung_status_pensiun($r['tanggal_lahir'] ?? null, $r['status_kepegawaian'] ?? '', $r['jabatan_fungsional'] ?? '');
          $kd = hitung_kenaikan_pangkat_pegawai($r);
        ?>
        <tr>
          <td style="color:var(--text-faint);font-size:.75rem;font-weight:800;"><?php echo $no++; ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:.625rem;">
              <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a78bfa);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;flex-shrink:0;box-shadow:0 2px 6px rgba(99,102,241,.3);">
                <?php echo strtoupper(substr($r['nama'], 0, 1)); ?>
              </div>
              <div>
                <span style="font-weight:700;color:var(--text-primary);display:block;"><?php echo htmlspecialchars($r['nama']); ?></span>
                <span style="font-size:.72rem;color:var(--text-muted);"><?php echo htmlspecialchars($r['kepangkatan']); ?></span>
              </div>
            </div>
          </td>
          <td><span class="e-badge-mono" style="font-size:.72rem;"><?php echo htmlspecialchars($r['nip']); ?></span></td>
          <td>
            <span class="e-badge <?php echo $sc; ?>"><?php echo htmlspecialchars($r['status_kepegawaian'] ?? 'PNS'); ?></span>
            <?php if (!empty($r['jabatan_fungsional'])): ?>
              <div style="font-size:.75rem;color:var(--text-muted);margin-top:0.2rem;"><?php echo htmlspecialchars($r['jabatan_fungsional']); ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($kd['status_text'])): ?>
              <span class="e-badge <?php echo $kd['badge_class']; ?>" style="font-size:0.75rem; padding:0.35rem 0.65rem;">
                <?php echo htmlspecialchars($kd['status_text']); ?>
              </span>
              <div style="font-size:0.7rem; color:var(--text-faint); margin-top:0.2rem;">
                <?php echo $kd['kategori_pegawai']; ?> &middot; TMT: <strong><?php echo $kd['tmt_fmt']; ?></strong> &middot; Target: <strong>Gol. <?php echo $kd['target_golongan']; ?></strong>
              </div>
            <?php else: ?>
              <span style="font-size:0.75rem; color:var(--text-faint);">—</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="e-badge <?php echo $pi['badge_class']; ?>" style="font-size:0.75rem; padding:0.35rem 0.65rem;">
              <?php echo htmlspecialchars($pi['status_text']); ?>
            </span>
            <div style="font-size:0.7rem; color:var(--text-faint); margin-top:0.2rem;">
              Usia: <strong><?php echo $pi['usia_detail']; ?></strong> &middot; BUP: <strong><?php echo $pi['bup']; ?> thn</strong>
            </div>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:.35rem;justify-content:flex-end;">
              <a class="e-act-btn view" href="<?php echo BASE_URL; ?>pegawai_detail?id=<?php echo $r['id']; ?>" title="Detail"><i class="bi bi-eye-fill"></i></a>
              <a class="e-act-btn edit" href="<?php echo BASE_URL; ?>pegawai_edit?id=<?php echo $r['id']; ?>" title="Edit"><i class="bi bi-pencil-fill"></i></a>
              <a class="e-act-btn del"  href="<?php echo BASE_URL; ?>pegawai_delete?id=<?php echo $r['id']; ?>" title="Hapus" onclick="return confirm('Hapus data pegawai ini?');"><i class="bi bi-trash3-fill"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="7">
          <div class="e-empty"><i class="bi bi-people e-empty-icon"></i><p>Belum ada data pegawai.</p></div>
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
