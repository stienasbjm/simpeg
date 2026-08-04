<?php
// templates/portal/profil.php
require_once __DIR__ . '/../../src/modules/pegawai_functions.php';
$user_info = get_user_info();
$pid       = get_session_pegawai_id();
$pegawai   = $pid ? get_pegawai_by_id($conn, $pid) : null;
$masa_kerja= $pegawai ? hitung_masa_kerja($pegawai['tanggal_masuk_kerja'] ?? null) : '—';
$badge_status = ['Dosen PNS'=>'blue','Dosen TY'=>'purple','Tenaga Kependidikan'=>'green','Kontrak/Tidak tetap'=>'amber'];
?>

<?php if (!empty($_SESSION['success_message'])): ?>
<div class="e-notice success" data-dismiss><i class="bi bi-check-circle-fill"></i><span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error_message'])): ?>
<div class="e-notice danger" data-dismiss><i class="bi bi-exclamation-triangle-fill"></i><span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span></div>
<?php endif; ?>

<div style="margin-bottom:1.5rem;">
  <h1 class="e-page-title" style="margin:0 0 .25rem;">
    <div class="title-icon" style="background:linear-gradient(135deg,rgba(99,102,241,.15),rgba(167,139,250,.08));color:var(--indigo);">
      <i class="bi bi-person-circle"></i>
    </div>
    Profil Saya
  </h1>
  <p class="e-page-sub">Data profil kepegawaian dan akun login Anda.</p>
</div>

<?php if (!$pid || !$pegawai): ?>
<div class="e-notice danger"><i class="bi bi-exclamation-triangle-fill"></i>
  <span>Akun belum terhubung ke data pegawai. Hubungi administrator.</span>
</div>
<?php else: ?>

<?php
$has_foto = !empty($pegawai['foto']) && file_exists(__DIR__ . '/../../src/../public/uploads/foto/' . $pegawai['foto']);
$foto_url = $has_foto ? BASE_URL . 'public/uploads/foto/' . htmlspecialchars($pegawai['foto']) : null;
?>

<div class="row g-3">
  <!-- Info Card -->
  <div class="col-lg-4">
    <div class="e-card">
      <div class="e-card-body" style="text-align:center;padding:2rem 1.5rem;">

        <!-- Avatar Wrapper with Camera Change Button -->
        <div style="position:relative;width:96px;height:96px;margin:0 auto 1rem;">
          <?php if ($has_foto): ?>
            <img id="avatarPreview" src="<?php echo $foto_url; ?>" alt="Foto Profil"
                 style="width:96px;height:96px;border-radius:50%;object-fit:cover;box-shadow:0 8px 24px rgba(99,102,241,.3);border:3px solid var(--indigo);">
          <?php else: ?>
            <div id="avatarInitials" style="width:96px;height:96px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a78bfa);color:#fff;display:flex;align-items:center;justify-content:center;font-size:2.25rem;font-weight:900;box-shadow:0 8px 24px rgba(99,102,241,.3);">
              <?php echo strtoupper(substr($pegawai['nama'], 0, 1)); ?>
            </div>
            <img id="avatarPreview" src="" alt="Preview"
                 style="width:96px;height:96px;border-radius:50%;object-fit:cover;box-shadow:0 8px 24px rgba(99,102,241,.3);border:3px solid var(--indigo);display:none;">
          <?php endif; ?>

          <!-- Camera Badge -->
          <button type="button" onclick="document.getElementById('inputFotoHidden').click()"
                  title="Ganti Foto Profil"
                  style="position:absolute;bottom:0;right:0;width:32px;height:32px;border-radius:50%;background:var(--indigo);color:#fff;border:2px solid var(--bg-card);display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.2);transition:all .2s ease;">
            <i class="bi bi-camera-fill" style="font-size:.85rem;"></i>
          </button>
        </div>

        <div style="font-weight:900;font-size:1.05rem;color:var(--text-primary);"><?php echo htmlspecialchars($pegawai['nama']); ?></div>
        <div style="font-size:.78rem;color:var(--text-muted);margin:.25rem 0 .75rem;"><?php echo htmlspecialchars($pegawai['nip']); ?></div>
        <?php $sc = $badge_status[$pegawai['status_kepegawaian'] ?? 'PNS'] ?? 'indigo'; ?>
        <span class="e-badge <?php echo $sc; ?>"><?php echo htmlspecialchars($pegawai['status_kepegawaian'] ?? 'PNS'); ?></span>

        <div style="margin-top:1rem;">
          <button type="button" onclick="document.getElementById('inputFotoHidden').click()" class="e-btn" style="padding:.35rem .85rem;font-size:.78rem;width:100%;justify-content:center;">
            <i class="bi bi-upload"></i> Unggah Foto Profil
          </button>
        </div>
      </div>
    </div>

<?php
$pensiun_info = $pegawai ? hitung_status_pensiun($pegawai['tanggal_lahir'] ?? null, $pegawai['status_kepegawaian'] ?? '', $pegawai['jabatan_fungsional'] ?? '') : null;
?>
    <!-- Stats -->
    <div class="e-card" style="margin-top:1rem;">
      <div class="e-card-body" style="padding:1.25rem;">
        <div style="display:flex;flex-direction:column;gap:.75rem;">
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:.8rem;color:var(--text-muted);"><i class="bi bi-award-fill" style="color:var(--amber);"></i> Pangkat</span>
            <span style="font-size:.82rem;font-weight:700;"><?php echo htmlspecialchars($pegawai['kepangkatan']); ?></span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:.8rem;color:var(--text-muted);"><i class="bi bi-briefcase-fill" style="color:var(--blue);"></i> Masa Kerja</span>
            <span style="font-size:.82rem;font-weight:700;color:var(--blue);"><?php echo $masa_kerja; ?></span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:.8rem;color:var(--text-muted);"><i class="bi bi-mortarboard-fill" style="color:var(--purple);"></i> Ijazah</span>
            <span style="font-size:.82rem;font-weight:700;"><?php echo htmlspecialchars($pegawai['ijazah']); ?></span>
          </div>
          <?php if (!empty($pegawai['jabatan_fungsional'])): ?>
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:.8rem;color:var(--text-muted);"><i class="bi bi-person-workspace" style="color:var(--teal);"></i> Jabatan</span>
            <span style="font-size:.82rem;font-weight:700;"><?php echo htmlspecialchars($pegawai['jabatan_fungsional']); ?></span>
          </div>
          <?php endif; ?>
          
          <?php if ($pensiun_info): ?>
          <div style="border-top:1px solid var(--border-light);padding-top:.75rem;margin-top:.25rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.35rem;">
              <span style="font-size:.8rem;color:var(--text-muted);"><i class="bi bi-clock-history" style="color:var(--red);"></i> Ketentuan BUP</span>
              <span style="font-size:.78rem;font-weight:700;color:var(--text-primary);"><?php echo $pensiun_info['kategori']; ?></span>
            </div>
            <div style="background:var(--bg-muted);padding:.6rem .75rem;border-radius:var(--r-md);border:1px solid var(--border-light);">
              <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:.72rem;color:var(--text-muted);">Pemberitahuan Pensiun:</span>
                <span class="e-badge <?php echo $pensiun_info['badge_class']; ?>" style="font-size:.7rem;padding:.2rem .5rem;">
                  <?php echo htmlspecialchars($pensiun_info['status_text']); ?>
                </span>
              </div>
              <div style="font-size:.7rem;color:var(--text-faint);margin-top:.3rem;">
                Tgl Pensiun: <strong><?php echo $pensiun_info['tgl_pensiun']; ?></strong> (Usia: <?php echo $pensiun_info['usia_detail']; ?>)
              </div>
            </div>
          </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>

  <!-- Edit Form -->
  <div class="col-lg-8">
    <div class="e-card">
      <div class="e-card-header">
        <div class="e-card-title"><i class="bi bi-pencil-square" style="color:var(--indigo);"></i> Edit Profil</div>
      </div>
      <div class="e-card-body">
        <form method="POST" action="<?php echo BASE_URL; ?>profil_save" enctype="multipart/form-data" id="formProfil">
          <?php echo csrf_field(); ?>

          <!-- Hidden File Input for Foto -->
          <input type="file" name="foto" id="inputFotoHidden" accept=".jpg,.jpeg,.png,.webp" style="display:none;" onchange="previewFoto(this)">

          <!-- Section Foto Profil -->
          <div class="e-form-section"><i class="bi bi-image-fill"></i> Foto Profil</div>
          <div style="background:var(--bg-muted);padding:1rem 1.25rem;border-radius:var(--r-md);margin-bottom:1.25rem;border:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <div>
              <div style="font-weight:700;font-size:.85rem;color:var(--text-primary);">File Foto Profil</div>
              <div style="font-size:.75rem;color:var(--text-muted);">Format: JPG, PNG, atau WebP. Ukuran maks. 5 MB.</div>
              <div id="fotoFileName" style="font-size:.78rem;font-weight:700;color:var(--indigo);margin-top:.2rem;display:none;"></div>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;">
              <button type="button" class="e-btn" onclick="document.getElementById('inputFotoHidden').click()" style="font-size:.8rem;padding:.4rem .85rem;">
                <i class="bi bi-folder2-open"></i> Pilih Foto
              </button>
              <?php if ($has_foto): ?>
                <label style="display:inline-flex;align-items:center;gap:.35rem;font-size:.78rem;color:var(--red);cursor:pointer;user-select:none;margin:0;">
                  <input type="checkbox" name="hapus_foto" value="1">
                  <i class="bi bi-trash"></i> Hapus Foto
                </label>
              <?php endif; ?>
            </div>
          </div>

          <div class="e-form-section"><i class="bi bi-person-badge"></i> Data Diri</div>
          <div class="row g-3">
            <div class="col-md-8">
              <div class="e-form-group">
                <label class="e-label">Nama Lengkap <span class="req">*</span></label>
                <input class="e-input" type="text" name="nama" value="<?php echo htmlspecialchars($pegawai['nama']); ?>" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="e-form-group">
                <label class="e-label">NIP</label>
                <input class="e-input" type="text" value="<?php echo htmlspecialchars($pegawai['nip']); ?>" disabled style="opacity:.6;cursor:not-allowed;">
              </div>
            </div>
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">Tempat Lahir <span class="req">*</span></label>
                <input class="e-input" type="text" name="tempat_lahir" value="<?php echo htmlspecialchars($pegawai['tempat_lahir']); ?>" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">Tanggal Lahir <span class="req">*</span></label>
                <input class="e-input" type="date" name="tanggal_lahir" value="<?php echo htmlspecialchars($pegawai['tanggal_lahir']); ?>" required>
              </div>
            </div>
            <div class="col-12">
              <div class="e-form-group">
                <label class="e-label">Ijazah Terakhir <span class="req">*</span></label>
                <input class="e-input" type="text" name="ijazah" value="<?php echo htmlspecialchars($pegawai['ijazah']); ?>" required>
              </div>
            </div>
          </div>

          <div class="e-form-section" style="margin-top:1.25rem;"><i class="bi bi-lock"></i> Ganti Password</div>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">Password Baru</label>
                <input class="e-input" type="password" name="password_baru" placeholder="Kosongkan jika tidak diubah" minlength="6">
              </div>
            </div>
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">Konfirmasi Password</label>
                <input class="e-input" type="password" name="password_konfirmasi" placeholder="Ulangi password baru">
              </div>
            </div>
          </div>

          <div class="e-divider"></div>
          <button type="submit" class="e-btn e-btn-primary" id="btnSimpanProfil"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
        </form>
      </div>
    </div>

    <!-- Dokumen -->
    <?php if (!empty($pegawai['file_ijazah']) || !empty($pegawai['file_kepangkatan']) || !empty($pegawai['file_jabatan_fungsional'])): ?>
    <div class="e-card" style="margin-top:1rem;">
      <div class="e-card-header"><div class="e-card-title"><i class="bi bi-file-earmark-text-fill" style="color:var(--teal);"></i> Dokumen Saya</div></div>
      <div class="e-card-body">
        <div style="display:flex;flex-direction:column;gap:.625rem;">
          <?php
          $docs = [
            ['file_ijazah','Ijazah'],
            ['file_kepangkatan','SK Kepangkatan'],
            ['file_jabatan_fungsional','SK Jabatan Fungsional'],
          ];
          foreach ($docs as [$key, $label]):
            if (empty($pegawai[$key])) continue;
          ?>
          <div style="display:flex;align-items:center;gap:.625rem;padding:.625rem .875rem;border-radius:var(--r-md);background:var(--bg-muted);">
            <i class="bi bi-file-earmark-pdf-fill" style="color:var(--red);font-size:1.1rem;"></i>
            <span style="flex:1;font-size:.82rem;font-weight:600;"><?php echo $label; ?></span>
            <a href="<?php echo BASE_URL . $pegawai[$key]; ?>" target="_blank" class="e-btn e-btn-ghost" style="padding:.3rem .625rem;font-size:.75rem;">
              <i class="bi bi-eye-fill"></i> Lihat
            </a>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<script>
function previewFoto(input) {
  const file = input.files[0];
  if (!file) return;

  const fileNameDiv = document.getElementById('fotoFileName');
  if (fileNameDiv) {
    fileNameDiv.textContent = '📎 ' + file.name + ' (' + (file.size / (1024 * 1024)).toFixed(2) + ' MB)';
    fileNameDiv.style.display = 'block';
  }

  const reader = new FileReader();
  reader.onload = function(e) {
    const imgPreview = document.getElementById('avatarPreview');
    const initialsDiv = document.getElementById('avatarInitials');

    if (imgPreview) {
      imgPreview.src = e.target.result;
      imgPreview.style.display = 'block';
    }
    if (initialsDiv) {
      initialsDiv.style.display = 'none';
    }
  };
  reader.readAsDataURL(file);
}

const formProfil = document.getElementById('formProfil');
const btnSimpanProfil = document.getElementById('btnSimpanProfil');
if (formProfil && btnSimpanProfil) {
  formProfil.addEventListener('submit', function() {
    btnSimpanProfil.disabled = true;
    btnSimpanProfil.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...';
  });
}
</script>
