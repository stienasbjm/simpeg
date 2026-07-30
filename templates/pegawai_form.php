<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/pegawai_functions.php';
require_login();
$is_edit = false; $data = [];
$form_url = BASE_URL . 'pegawai_save';
if (isset($_GET['id'])) {
    $id = (int)$_GET['id']; $data = get_pegawai_by_id($conn, $id);
    if ($data) { $is_edit = true; $form_url .= '?id=' . $id; }
    else { $_SESSION['error_message'] = "Data tidak ditemukan."; header('Location: ' . BASE_URL . 'pegawai'); exit(); }
}
$pangkat_list    = get_pangkat_list();
$status_list     = ['Dosen PNS', 'Dosen TY', 'Tenaga Kependidikan', 'Kontrak/Tidak tetap'];
$current_pangkat = $data['kepangkatan'] ?? '';
$current_status  = $data['status_kepegawaian'] ?? 'Dosen PNS';
?>

<div class="e-page-header">
  <div>
    <div class="e-breadcrumb">
      <i class="bi bi-house-fill" style="font-size:.7rem;"></i>
      <a href="<?php echo BASE_URL; ?>pegawai">Data Pegawai</a>
      <span class="e-breadcrumb-sep">/</span>
      <span style="color:var(--purple);font-weight:600;"><?php echo $is_edit ? 'Edit' : 'Tambah'; ?></span>
    </div>
    <h1 class="e-page-title">
      <div class="title-icon" style="background:linear-gradient(135deg,rgba(139,92,246,.15),rgba(196,181,253,.08));color:var(--purple);">
        <i class="bi bi-person-fill-gear"></i>
      </div>
      <?php echo $is_edit ? 'Edit Data Pegawai' : 'Tambah Data Pegawai'; ?>
    </h1>
    <p class="e-page-sub"><?php echo $is_edit ? 'Perbarui profil dan dokumen digital pegawai.' : 'Isi lengkap formulir data pegawai baru.'; ?></p>
  </div>
  <a href="<?php echo BASE_URL; ?>pegawai" class="e-btn e-btn-ghost">
    <i class="bi bi-arrow-left"></i> Kembali
  </a>
</div>

<?php if (!empty($_SESSION['error_message'])): ?>
<div class="e-notice danger"><i class="bi bi-exclamation-triangle-fill"></i><span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span></div>
<?php endif; ?>

<div class="e-card">
  <div class="e-card-header">
    <div class="e-card-title"><i class="bi bi-person-lines-fill" style="color:var(--purple);"></i> Data Kepegawaian</div>
  </div>
  <div class="e-card-body">
    <form action="<?php echo $form_url; ?>" method="POST" enctype="multipart/form-data">
      <?php echo csrf_field(); ?>
      <?php if ($is_edit): ?>
        <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
      <?php endif; ?>

      <div class="e-form-section"><i class="bi bi-person-badge"></i> Identitas Pegawai</div>
      <div class="row g-3">
        <div class="col-md-8">
          <div class="e-form-group">
            <label class="e-label" for="nama">Nama Lengkap <span class="req">*</span></label>
            <input class="e-input" type="text" id="nama" name="nama"
              value="<?php echo htmlspecialchars($data['nama'] ?? ''); ?>"
              placeholder="Nama lengkap beserta gelar" required>
          </div>
        </div>
        <div class="col-md-4">
          <div class="e-form-group">
            <label class="e-label" for="nip">NIP <span class="req">*</span></label>
            <input class="e-input" type="text" id="nip" name="nip"
              value="<?php echo htmlspecialchars($data['nip'] ?? ''); ?>"
              placeholder="198501012010121001" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="e-form-group">
            <label class="e-label" for="tempat_lahir">Tempat Lahir <span class="req">*</span></label>
            <input class="e-input" type="text" id="tempat_lahir" name="tempat_lahir"
              value="<?php echo htmlspecialchars($data['tempat_lahir'] ?? ''); ?>"
              placeholder="Kota kelahiran" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="e-form-group">
            <label class="e-label" for="tanggal_lahir">Tanggal Lahir <span class="req">*</span></label>
            <input class="e-input" type="date" id="tanggal_lahir" name="tanggal_lahir"
              value="<?php echo htmlspecialchars($data['tanggal_lahir'] ?? ''); ?>" required>
          </div>
        </div>

        <!-- Pangkat/Golongan Dropdown -->
        <div class="col-md-6">
          <div class="e-form-group">
            <label class="e-label" for="kepangkatan">Pangkat / Golongan <span class="req">*</span></label>
            <select class="e-select" id="kepangkatan" name="kepangkatan" required>
              <option value="">— Pilih Pangkat/Golongan —</option>
              <?php foreach ($pangkat_list as $golongan => $pangkats): ?>
              <optgroup label="── <?php echo $golongan; ?> ──">
                <?php foreach ($pangkats as $p): ?>
                <option value="<?php echo $p; ?>" <?php echo ($current_pangkat === $p) ? 'selected' : ''; ?>>
                  <?php echo $p; ?>
                </option>
                <?php endforeach; ?>
              </optgroup>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Status Kepegawaian -->
        <div class="col-md-3">
          <div class="e-form-group">
            <label class="e-label" for="status_kepegawaian">Status Kepegawaian <span class="req">*</span></label>
            <select class="e-select" id="status_kepegawaian" name="status_kepegawaian" required>
              <?php foreach ($status_list as $s): ?>
              <option value="<?php echo $s; ?>" <?php echo ($current_status === $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Tanggal Masuk Kerja -->
        <div class="col-md-3">
          <div class="e-form-group">
            <label class="e-label" for="tanggal_masuk_kerja">Tgl. Mulai Bekerja</label>
            <input class="e-input" type="date" id="tanggal_masuk_kerja" name="tanggal_masuk_kerja"
              value="<?php echo htmlspecialchars($data['tanggal_masuk_kerja'] ?? ''); ?>">
          </div>
        </div>

        <div class="col-md-6">
          <div class="e-form-group">
            <label class="e-label" for="jabatan_fungsional">Jabatan Fungsional</label>
            <input class="e-input" type="text" id="jabatan_fungsional" name="jabatan_fungsional"
              value="<?php echo htmlspecialchars($data['jabatan_fungsional'] ?? ''); ?>"
              placeholder="Lektor / Asisten Ahli (opsional)">
          </div>
        </div>
        <div class="col-md-6">
          <div class="e-form-group">
            <label class="e-label" for="ijazah">Ijazah Terakhir <span class="req">*</span></label>
            <input class="e-input" type="text" id="ijazah" name="ijazah"
              value="<?php echo htmlspecialchars($data['ijazah'] ?? ''); ?>"
              placeholder="S2 Teknik Informatika" required>
          </div>
        </div>
      </div>

      <div class="e-form-section" style="margin-top:1.25rem;"><i class="bi bi-files"></i> Dokumen Digital</div>
      <div class="row g-3">
        <?php
        $docs = [
          ['file_ijazah',            'Ijazah Terakhir',       'file_ijazah'],
          ['file_kepangkatan',       'SK Kepangkatan',        'file_kepangkatan'],
          ['file_jabatan_fungsional','SK Jabatan Fungsional', 'file_jabatan_fungsional'],
        ];
        foreach ($docs as [$fid, $flabel, $fkey]):
        ?>
        <div class="col-md-4">
          <div class="e-form-group">
            <label class="e-label" for="<?php echo $fid; ?>"><?php echo $flabel; ?></label>
            <input class="e-file-input" type="file" id="<?php echo $fid; ?>" name="<?php echo $fid; ?>">
            <?php if (!empty($data[$fkey])): ?>
            <div style="display:flex;align-items:center;gap:.4rem;margin-top:.5rem;font-size:.75rem;color:var(--text-muted);">
              <i class="bi bi-file-earmark-check" style="color:var(--green);"></i>
              <a href="<?php echo BASE_URL . $data[$fkey]; ?>" target="_blank" style="color:var(--blue);font-weight:600;">Lihat File</a>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="e-divider"></div>
      <div style="display:flex;gap:.625rem;">
        <button type="submit" class="e-btn e-btn-primary">
          <i class="bi bi-check-lg"></i> <?php echo $is_edit ? 'Simpan Perubahan' : 'Simpan Data'; ?>
        </button>
        <a href="<?php echo BASE_URL; ?>pegawai" class="e-btn e-btn-ghost">Batal</a>
      </div>
    </form>
  </div>
</div>
