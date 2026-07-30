<?php
// templates/parts/modal_ttd.php — Shared Modal Pengaturan Penandatangan
require_once __DIR__ . '/../../src/modules/keuangan_functions.php';
$ttd_data = get_pengaturan_ttd($conn);
?>
<!-- ═══════════ MODAL PENGATURAN PEJABAT PENANDATANGAN ═══════════ -->
<div class="modal fade" id="modalTTD" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border-medium);border-radius:var(--r-lg);">
      <div class="modal-header" style="border-bottom:1px solid var(--border-light);">
        <h5 class="modal-title" style="font-weight:800;font-size:1.1rem;"><i class="bi bi-pen-fill" style="color:var(--indigo);"></i> Pengaturan Pejabat Penandatangan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?php echo BASE_URL; ?>pengaturan_ttd_save" method="POST">
        <div class="modal-body" style="padding:1.25rem;">
          <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:1rem;">
            Nama & NIP yang diisi di sini akan otomatis tampil pada cetakan <strong>Slip Gaji</strong> dan <strong>Laporan Arus Kas</strong>.
          </div>

          <!-- Section Bendahara -->
          <div style="font-weight:800;font-size:.82rem;color:var(--green);margin-bottom:.5rem;text-transform:uppercase;">1. Bendahara Keuangan</div>
          <div class="row g-2 mb-3">
            <div class="col-12">
              <div class="e-form-group">
                <label class="e-label">Nama Lengkap & Gelar Bendahara</label>
                <input type="text" class="e-input" name="ttd_bendahara_nama" value="<?php echo htmlspecialchars($ttd_data['ttd_bendahara_nama'] ?? ''); ?>" placeholder="Contoh: Hj. Siti Rahmah, S.E." required>
              </div>
            </div>
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">NIP Bendahara</label>
                <input type="text" class="e-input" name="ttd_bendahara_nip" value="<?php echo htmlspecialchars($ttd_data['ttd_bendahara_nip'] ?? ''); ?>" placeholder="Contoh: 198801012015032001" required>
              </div>
            </div>
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">Jabatan Bendahara</label>
                <input type="text" class="e-input" name="ttd_bendahara_jabatan" value="<?php echo htmlspecialchars($ttd_data['ttd_bendahara_jabatan'] ?? 'Bendahara Keuangan'); ?>" placeholder="Bendahara Keuangan">
              </div>
            </div>
          </div>

          <!-- Section Pimpinan -->
          <div style="font-weight:800;font-size:.82rem;color:var(--blue);margin-bottom:.5rem;text-transform:uppercase;">2. Pimpinan Instansi / Ketua</div>
          <div class="row g-2 mb-3">
            <div class="col-12">
              <div class="e-form-group">
                <label class="e-label">Nama Lengkap & Gelar Pimpinan</label>
                <input type="text" class="e-input" name="ttd_pimpinan_nama" value="<?php echo htmlspecialchars($ttd_data['ttd_pimpinan_nama'] ?? ''); ?>" placeholder="Contoh: Dr. H. A. Gazali, M.M." required>
              </div>
            </div>
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">NIP Pimpinan</label>
                <input type="text" class="e-input" name="ttd_pimpinan_nip" value="<?php echo htmlspecialchars($ttd_data['ttd_pimpinan_nip'] ?? ''); ?>" placeholder="Contoh: 197502022003121001" required>
              </div>
            </div>
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">Jabatan Pimpinan</label>
                <input type="text" class="e-input" name="ttd_pimpinan_jabatan" value="<?php echo htmlspecialchars($ttd_data['ttd_pimpinan_jabatan'] ?? 'Pimpinan / Ketua'); ?>" placeholder="Ketua / Direktur">
              </div>
            </div>
          </div>

          <!-- Kota Terbit -->
          <div class="e-form-group">
            <label class="e-label">Kota Terbit Laporan</label>
            <input type="text" class="e-input" name="kota_terbit" value="<?php echo htmlspecialchars($ttd_data['kota_terbit'] ?? 'Banjarmasin'); ?>" placeholder="Contoh: Banjarmasin">
          </div>
        </div>

        <div class="modal-footer" style="border-top:1px solid var(--border-light);">
          <button type="button" class="e-btn e-btn-ghost" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="e-btn e-btn-primary"><i class="bi bi-check-lg"></i> Simpan Penandatangan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openModalTTD() {
  const modal = new bootstrap.Modal(document.getElementById('modalTTD'));
  modal.show();
}
</script>
