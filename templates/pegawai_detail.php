<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/pegawai_functions.php';

require_login();

$d = null;
if (isset($_GET['id'])) {
    $d = get_pegawai_by_id($conn, (int)$_GET['id']);
}

if (!$d) {
    $_SESSION['error_message'] = "Data pegawai tidak ditemukan.";
    header('Location: ' . BASE_URL . 'public/index.php?page=pegawai');
    exit();
}
?>

<div class="e-page-header">
  <div>
    <h1 class="e-page-title"><i class="bi bi-person-fill-gear" style="color:var(--purple);"></i> Detail Pegawai</h1>
    <p class="e-page-sub">Informasi lengkap data diri dan berkas digital pegawai.</p>
  </div>
  <div style="display:flex;gap:.5rem;">
    <a href="<?php echo BASE_URL; ?>public/index.php?page=pegawai_edit&id=<?php echo $d['id']; ?>" class="e-btn e-btn-primary">
      <i class="bi bi-pencil-fill"></i> Edit
    </a>
    <a href="<?php echo BASE_URL; ?>public/index.php?page=pegawai" class="e-btn e-btn-ghost">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
  </div>
</div>

<div class="row g-3">
  <!-- Identitas Utama -->
  <div class="col-lg-12">
    <div class="e-card mb-3">
      <div class="e-card-body">
        <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
          <div style="width:72px;height:72px;border-radius:var(--r-md);background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;flex-shrink:0;box-shadow:0 8px 16px rgba(168,85,247,.25);">
            <?php echo strtoupper(substr($d['nama'], 0, 1)); ?>
          </div>
          <div>
            <h3 style="margin:0 0 .25rem;font-size:1.25rem;font-weight:700;letter-spacing:-.02em;"><?php echo htmlspecialchars($d['nama']); ?></h3>
            <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
              <span class="e-badge purple" style="font-size:.7rem;"><i class="bi bi-person-badge"></i> <?php echo htmlspecialchars($d['nip']); ?></span>
              <span style="color:var(--text-secondary);font-size:.8rem;"><i class="bi bi-award" style="opacity:.6;"></i> <?php echo htmlspecialchars($d['kepangkatan']); ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php
$pi = hitung_status_pensiun($d['tanggal_lahir'] ?? null, $d['status_kepegawaian'] ?? '', $d['jabatan_fungsional'] ?? '');
$mk = hitung_masa_kerja($d['tanggal_masuk_kerja'] ?? null);
?>
  <div class="col-lg-6">
    <div class="e-card" style="height:100%;">
      <div class="e-card-header">
        <div class="e-card-title"><i class="bi bi-person-lines-fill"></i> Identitas Pegawai</div>
      </div>
      <div class="e-card-body">
        <div class="e-dl">
          <div class="e-dl-row">
            <div class="e-dt">Nama Lengkap</div>
            <div class="e-dd" style="font-weight:600;"><?php echo htmlspecialchars($d['nama']); ?></div>
          </div>
          <div class="e-dl-row">
            <div class="e-dt">NIP</div>
            <div class="e-dd"><span class="e-badge-mono"><?php echo htmlspecialchars($d['nip']); ?></span></div>
          </div>
          <div class="e-dl-row">
            <div class="e-dt">Tempat, Tgl Lahir</div>
            <div class="e-dd"><?php echo htmlspecialchars($d['tempat_lahir']); ?>, <?php echo date('d F Y', strtotime($d['tanggal_lahir'])); ?> (Usia: <?php echo $pi['usia_detail']; ?>)</div>
          </div>
          <div class="e-dl-row">
            <div class="e-dt">Status & Pangkat</div>
            <div class="e-dd">
              <span class="e-badge purple"><?php echo htmlspecialchars($d['status_kepegawaian'] ?? 'PNS'); ?></span> &middot; <?php echo htmlspecialchars($d['kepangkatan']); ?>
            </div>
          </div>
          <div class="e-dl-row">
            <div class="e-dt">Jabatan Fungsional</div>
            <div class="e-dd"><?php echo !empty($d['jabatan_fungsional']) ? htmlspecialchars($d['jabatan_fungsional']) : '<span style="color:var(--text-faint);">—</span>'; ?></div>
          </div>
          <div class="e-dl-row">
            <div class="e-dt">Ijazah Terakhir</div>
            <div class="e-dd"><?php echo htmlspecialchars($d['ijazah']); ?></div>
          </div>
          <div class="e-dl-row">
            <div class="e-dt">Masa Kerja</div>
            <div class="e-dd" style="color:var(--blue);font-weight:700;"><?php echo $mk; ?></div>
          </div>
          <div class="e-dl-row">
            <div class="e-dt">Ketentuan BUP</div>
            <div class="e-dd"><?php echo $pi['kategori']; ?></div>
          </div>
          <div class="e-dl-row">
            <div class="e-dt">Pemberitahuan Pensiun</div>
            <div class="e-dd">
              <span class="e-badge <?php echo $pi['badge_class']; ?>" style="font-size:.78rem;padding:.3rem .6rem;">
                <?php echo htmlspecialchars($pi['status_text']); ?>
              </span>
              <div style="font-size:.73rem;color:var(--text-muted);margin-top:.25rem;">
                Perkiraan Tgl Pensiun: <strong><?php echo $pi['tgl_pensiun']; ?></strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="e-card" style="height:100%;">
      <div class="e-card-header">
        <div class="e-card-title"><i class="bi bi-files"></i> Dokumen Digital</div>
      </div>
      <div class="e-card-body">
        <div style="display:flex;flex-direction:column;gap:1rem;">
          
          <?php
          $docs = [
            ['Ijazah Terakhir', $d['file_ijazah']],
            ['SK Kepangkatan', $d['file_kepangkatan']],
            ['SK Jabatan Fungsional', $d['file_jabatan_fungsional']]
          ];
          foreach ($docs as [$title, $file_path]):
          ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem;background:var(--bg-muted);border:1px solid var(--border-light);border-radius:var(--r-md);">
            <div style="display:flex;align-items:center;gap:.75rem;">
              <div style="width:36px;height:36px;border-radius:var(--r-sm);background:rgba(59,130,246,.1);display:flex;align-items:center;justify-content:center;color:var(--blue);font-size:1.1rem;flex-shrink:0;">
                <i class="bi bi-file-earmark-text-fill"></i>
              </div>
              <div>
                <div style="font-size:.82rem;font-weight:600;color:var(--text-primary);"><?php echo $title; ?></div>
                <div style="font-size:.72rem;color:var(--text-faint);margin-top:.15rem;">
                  <?php echo !empty($file_path) ? 'Berkas digital tersedia' : 'Belum diunggah'; ?>
                </div>
              </div>
            </div>
            <?php if (!empty($file_path)): ?>
            <a href="<?php echo BASE_URL . $file_path; ?>" target="_blank" class="e-btn e-btn-primary" style="padding:.3rem .6rem;font-size:.75rem;">
              Buka
            </a>
            <?php else: ?>
            <span class="e-badge" style="background:var(--border-light);color:var(--text-muted);">Kosong</span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>

        </div>
      </div>
    </div>
  </div>
</div>
