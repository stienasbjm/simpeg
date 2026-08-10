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
$kd = hitung_kenaikan_pangkat_pegawai($d);
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
              <?php if (!empty($d['tmt_pangkat'])): ?>
                <div style="font-size:.73rem;color:var(--text-muted);margin-top:.15rem;">TMT Pangkat: <strong><?php echo date('d F Y', strtotime($d['tmt_pangkat'])); ?></strong></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="e-dl-row">
            <div class="e-dt">Jabatan Fungsional</div>
            <div class="e-dd">
              <?php echo !empty($d['jabatan_fungsional']) ? htmlspecialchars($d['jabatan_fungsional']) : '<span style="color:var(--text-faint);">—</span>'; ?>
              <?php if (!empty($d['tmt_jabatan'])): ?>
                <div style="font-size:.73rem;color:var(--text-muted);margin-top:.15rem;">TMT Jabatan: <strong><?php echo date('d F Y', strtotime($d['tmt_jabatan'])); ?></strong></div>
              <?php endif; ?>
            </div>
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

  <?php if (!empty($kd['status_text'])): ?>
  <!-- Card Peringatan Kenaikan Pangkat/Golongan (Dosen & Tendik) -->
  <div class="col-lg-12">
    <div class="e-card" style="border: 1px solid rgba(99,102,241,0.25);">
      <div class="e-card-header" style="background:linear-gradient(135deg, rgba(99,102,241,0.06), rgba(168,85,247,0.03));">
        <div class="e-card-title">
          <i class="bi <?php echo $kd['is_dosen'] ? 'bi-mortarboard-fill' : 'bi-award-fill'; ?>" style="color:var(--indigo);"></i>
          Peringatan Kenaikan Pangkat / Golongan (<?php echo htmlspecialchars($kd['kategori_pegawai']); ?>)
        </div>
        <span class="e-badge <?php echo $kd['badge_class']; ?>" style="font-size:0.8rem; padding:0.4rem 0.75rem;">
          <?php echo htmlspecialchars($kd['status_text']); ?>
        </span>
      </div>
      <div class="e-card-body">
        <div class="row g-3">
          <div class="col-md-7">
            <div style="padding:1rem; background:var(--bg-muted); border-radius:var(--r-md); border:1px solid var(--border-light);">
              <div style="font-size:0.88rem; font-weight:700; color:var(--text-primary); margin-bottom:0.5rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="bi bi-info-circle-fill" style="color:var(--indigo);"></i> Status &amp; Analisis Kenaikan Pangkat/Golongan
              </div>
              <p style="font-size:0.82rem; color:var(--text-secondary); margin-bottom:0.75rem;">
                <?php echo htmlspecialchars($kd['detail_msg']); ?>
              </p>
              <div class="e-dl" style="font-size:0.82rem;">
                <div class="e-dl-row">
                  <div class="e-dt">Kategori &amp; Jabatan</div>
                  <div class="e-dd"><strong><?php echo htmlspecialchars($kd['jabatan_norm']); ?></strong> (<?php echo htmlspecialchars($kd['kategori_pegawai']); ?>)</div>
                </div>
                <div class="e-dl-row">
                  <div class="e-dt">TMT Pangkat / Kerja</div>
                  <div class="e-dd"><?php echo $kd['tmt_fmt']; ?> (Masa Kerja Pangkat: <strong><?php echo $kd['masa_detail']; ?></strong>)</div>
                </div>
                <div class="e-dl-row">
                  <div class="e-dt">Golongan Saat Ini</div>
                  <div class="e-dd"><span class="e-badge purple"><?php echo htmlspecialchars($kd['golongan_saat_ini']); ?></span></div>
                </div>
                <div class="e-dl-row">
                  <div class="e-dt">Target / Penyetaraan</div>
                  <div class="e-dd"><span class="e-badge green" style="font-size:0.8rem; font-weight:700;">Golongan <?php echo htmlspecialchars($kd['target_golongan']); ?></span></div>
                </div>
                <?php if (!empty($d['sk_inpassing_2025']) && $kd['is_dosen']): ?>
                <div class="e-dl-row">
                  <div class="e-dt">SK Inpassing</div>
                  <div class="e-dd"><span class="e-badge blue"><i class="bi bi-check-circle-fill"></i> Memiliki SK Inpassing s.d. 2025</span></div>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="col-md-5">
            <div style="padding:1rem; background:rgba(99,102,241,0.04); border-radius:var(--r-md); border:1px solid rgba(99,102,241,0.15); height:100%;">
              <div style="font-size:0.82rem; font-weight:700; color:var(--indigo); margin-bottom:0.5rem;">
                <i class="bi bi-journal-bookmark-fill"></i> Aturan Kenaikan Pangkat (<?php echo htmlspecialchars($kd['kategori_pegawai']); ?>)
              </div>
              <?php if ($kd['is_dosen']): ?>
              <ul style="font-size:0.75rem; color:var(--text-muted); padding-left:1.1rem; margin-bottom:0.5rem; line-height:1.45;">
                <li><strong>Asisten Ahli:</strong> Penyetaraan Golongan IIIb sejak TMT.</li>
                <li><strong>Lektor:</strong> TMT (IIIb), TMT +1 s.d. TMT +3 (IIIc), > TMT +3 (IIId).</li>
                <li><strong>Lektor Kepala:</strong> TMT (IIId), TMT +1 dst (IVa / IVb/IVc jika SK Inpassing s.d. 2025).</li>
                <li><strong>Profesor:</strong> TMT (IVa), TMT +1..+3 (IVb), >3..5 thn (IVc), >5..7 thn (IVd), >7 thn (IVe + 200 AK).</li>
              </ul>
              <?php else: ?>
              <ul style="font-size:0.75rem; color:var(--text-muted); padding-left:1.1rem; margin-bottom:0.5rem; line-height:1.45;">
                <li><strong>Periode Kenaikan Pangkat Reguler:</strong> Diusulkan setiap <strong>4 tahun sekali</strong> (48 bulan) berdasarkan TMT Pangkat terakhir.</li>
                <li><strong>Persyaratan Administrasi:</strong> Penilaian Prestasi Kerja (SKP), SK Pangkat Terakhir, dan Ijazah Pendidikan.</li>
              </ul>
              <?php endif; ?>
              <?php if (!empty($kd['catatan'])): ?>
              <div style="font-size:0.75rem; background:var(--bg-card); padding:0.5rem 0.75rem; border-radius:var(--r-sm); border:1px dashed var(--border-medium); color:var(--text-primary);">
                <strong>Catatan Sistem:</strong> <?php echo htmlspecialchars($kd['catatan']); ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

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
