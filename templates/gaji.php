<?php
// templates/gaji.php — Manajemen Penggajian & Slip Gaji Pegawai
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/keuangan_functions.php';
require_bendahara();

$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$uang_makan_per_hari = 20000;

$daftar_gaji = get_all_gaji($conn, $bulan, $tahun);
$nama_bulan  = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$tahun_opts  = range(2020, 2035);

// Hitung total anggaran gaji periode ini
$total_anggaran_gaji = 0;
foreach ($daftar_gaji as $g) {
    $total_anggaran_gaji += (float)($g['total_gaji'] ?? 0);
}
?>

<div class="e-page-header">
  <div>
    <div class="e-breadcrumb">
      <i class="bi bi-house-fill" style="font-size:.7rem;"></i>
      <a href="<?php echo BASE_URL; ?>dashboard">Beranda</a>
      <span class="e-breadcrumb-sep">/</span>
      <span style="color:var(--blue);font-weight:600;">Keuangan</span>
      <span class="e-breadcrumb-sep">/</span>
      <span style="color:var(--text-muted);">Penggajian & Slip Gaji</span>
    </div>
    <h1 class="e-page-title">
      <div class="title-icon" style="background:linear-gradient(135deg,rgba(16,185,129,.15),rgba(5,150,105,.08));color:var(--green);">
        <i class="bi bi-cash-stack"></i>
      </div>
      Manajemen Gaji & Slip Gaji
    </h1>
    <p class="e-page-sub">Penggajian rincian lengkap dan pencetakan slip gaji pegawai bulan <strong><?php echo $nama_bulan[$bulan] . ' ' . $tahun; ?></strong>.</p>
  </div>
</div>

<?php if (!empty($_SESSION['success_message'])): ?>
<div class="e-notice success" data-dismiss><i class="bi bi-check-circle-fill"></i><span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error_message'])): ?>
<div class="e-notice danger" data-dismiss><i class="bi bi-exclamation-triangle-fill"></i><span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span></div>
<?php endif; ?>

<!-- Filter & Ringkasan -->
<div class="row g-3" style="margin-bottom:1.25rem;">
  <div class="col-lg-8">
    <div class="e-card">
      <div class="e-card-body" style="padding:1rem 1.25rem;">
        <form method="GET" action="" style="display:flex;align-items:flex-end;gap:.875rem;flex-wrap:wrap;">
          <input type="hidden" name="page" value="gaji">
          <div>
            <label class="e-label">Bulan Penggajian</label>
            <select class="e-select" name="bulan" style="width:160px;">
              <?php foreach ($nama_bulan as $m => $n): ?>
              <option value="<?php echo $m; ?>" <?php echo $m === $bulan ? 'selected' : ''; ?>><?php echo $n; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="e-label">Tahun</label>
            <select class="e-select" name="tahun" style="width:110px;">
              <?php foreach ($tahun_opts as $y): ?>
              <option value="<?php echo $y; ?>" <?php echo $y === $tahun ? 'selected' : ''; ?>><?php echo $y; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="e-btn e-btn-primary"><i class="bi bi-funnel-fill"></i> Tampilkan Gaji</button>
          <a href="<?php echo BASE_URL; ?>slip_gaji_print?all=1&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" target="_blank" class="e-btn e-btn-ghost" style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;" title="Cetak Seluruh Slip Gaji Pegawai Bulan Ini">
            <i class="bi bi-printer-fill"></i> Cetak Semua Slip Gaji (Bulan Ini)
          </a>
          <button type="button" class="e-btn e-btn-ghost" style="background:rgba(99,102,241,.1);color:var(--indigo);border:none;" onclick="openModalTTD()" title="Pengaturan Nama & NIP Pejabat Penandatangan">
            <i class="bi bi-pen-fill"></i> Atur Penandatangan
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="e-card" style="background:linear-gradient(135deg,rgba(16,185,129,.08),rgba(5,150,105,.02));border:1px solid rgba(16,185,129,.2);">
      <div class="e-card-body" style="padding:1rem 1.25rem;">
        <div style="font-size:.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Total Anggaran Gaji Bulan Ini</div>
        <div style="font-size:1.5rem;font-weight:900;color:var(--green);margin-top:.2rem;">
          Rp <?php echo number_format($total_anggaran_gaji, 0, ',', '.'); ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Tabel Penggajian -->
<div class="e-table-wrap">
  <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border-light);background:var(--bg-muted);display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
    <div class="e-card-title"><i class="bi bi-person-vcard" style="color:var(--green);"></i> Penggajian Pegawai <span class="e-badge green"><?php echo count($daftar_gaji); ?> Pegawai</span></div>
    <div class="e-search" style="margin-left:auto;"><i class="bi bi-search e-search-icon"></i><input type="text" id="gajiSearch" placeholder="Cari pegawai..." oninput="filterGajiTable(this.value)"></div>
  </div>
  <div style="overflow-x:auto;">
    <table class="e-table" id="gajiTable">
      <thead><tr>
        <th style="width:40px;">#</th>
        <th>Nama Pegawai</th>
        <th>Status / Pangkat</th>
        <th style="text-align:center;">Hadir</th>
        <th style="text-align:right;">Gaji Pokok</th>
        <th style="text-align:right;">Tot. Tunjangan</th>
        <th style="text-align:right;color:var(--red);">Tot. Potongan</th>
        <th style="text-align:right;color:var(--green);">Take Home Pay</th>
        <th style="text-align:center;">Aksi / Slip Gaji</th>
      </tr></thead>
      <tbody>
        <?php if (!empty($daftar_gaji)): $no=1; foreach ($daftar_gaji as $g): 
          $auto_tunj_makan = $g['total_hadir'] * $uang_makan_per_hari;
          $gaji_pokok      = (float)($g['gaji_pokok'] ?? 0);
          $tunj_fung       = (float)($g['tunj_fungsional'] ?? 0);
          $tunj_struk      = (float)($g['tunj_struktural'] ?? 0);
          $tunj_sejahtera  = (float)($g['tunj_kesejahteraan'] ?? 0);
          $status_kawin    = (int)($g['status_kawin'] ?? 0);
          $tunj_kawin      = isset($g['tunj_kawin']) ? (float)$g['tunj_kawin'] : ($status_kawin ? 0.10 * $gaji_pokok : 0);
          $jumlah_anak     = (int)($g['jumlah_anak'] ?? 0);
          $tunj_anak       = isset($g['tunj_anak']) ? (float)$g['tunj_anak'] : (($jumlah_anak * 0.02) * $gaji_pokok);
          $tunj_beras      = isset($g['tunj_beras']) ? (float)$g['tunj_beras'] : 200000;
          $tunj_makan      = isset($g['tunjangan_makan']) ? (float)$g['tunjangan_makan'] : $auto_tunj_makan;
          $tunj_lain       = (float)($g['tunjangan_lain'] ?? 0);

          $tot_tunjangan   = $tunj_fung + $tunj_struk + $tunj_sejahtera + $tunj_kawin + $tunj_anak + $tunj_beras + $tunj_makan + $tunj_lain;

          $pot_bpjs_tk     = isset($g['pot_bpjs_tk']) ? (float)$g['pot_bpjs_tk'] : (0.02 * $gaji_pokok);
          $pot_bpjs_kes    = isset($g['pot_bpjs_kes']) ? (float)$g['pot_bpjs_kes'] : (0.05 * $gaji_pokok);
          $pot_koperasi    = isset($g['pot_koperasi']) ? (float)$g['pot_koperasi'] : 20000;
          $pot_lain        = (float)($g['potongan'] ?? 0);

          $tot_potongan    = $pot_bpjs_tk + $pot_bpjs_kes + $pot_koperasi + $pot_lain;

          $total_gaji      = isset($g['total_gaji']) ? (float)$g['total_gaji'] : ($gaji_pokok + $tot_tunjangan - $tot_potongan);
          $has_record      = !empty($g['gaji_id']);
        ?>
        <tr>
          <td style="color:var(--text-faint);font-size:.75rem;font-weight:800;"><?php echo $no++; ?></td>
          <td>
            <div style="font-weight:700;"><?php echo htmlspecialchars($g['nama']); ?></div>
            <div style="font-size:.75rem;color:var(--text-muted);"><?php echo htmlspecialchars($g['nip']); ?></div>
          </td>
          <td>
            <span class="e-badge-mono" style="font-size:.72rem;"><?php echo htmlspecialchars($g['status_kepegawaian']); ?></span>
            <div style="font-size:.75rem;color:var(--text-muted);"><?php echo htmlspecialchars($g['kepangkatan']); ?></div>
          </td>
          <td style="text-align:center;font-weight:800;color:var(--blue);"><?php echo $g['total_hadir']; ?> hr</td>
          <td style="text-align:right;font-weight:600;">Rp <?php echo number_format($gaji_pokok, 0, ',', '.'); ?></td>
          <td style="text-align:right;font-weight:600;color:var(--blue);">Rp <?php echo number_format($tot_tunjangan, 0, ',', '.'); ?></td>
          <td style="text-align:right;font-weight:600;color:var(--red);">Rp <?php echo number_format($tot_potongan, 0, ',', '.'); ?></td>
          <td style="text-align:right;font-weight:900;color:var(--green);font-size:.95rem;">
            Rp <?php echo number_format($total_gaji, 0, ',', '.'); ?>
          </td>
          <td style="text-align:center;">
            <div style="display:flex;align-items:center;justify-content:center;gap:.35rem;">
              <!-- Edit/Input Modal Trigger -->
              <button type="button" class="e-act-btn edit" 
                      title="Edit Detail Penggajian"
                      onclick="openEditGajiModal(<?php echo htmlspecialchars(json_encode([
                        'pegawai_id'    => $g['pegawai_id'],
                        'nama'          => $g['nama'],
                        'total_hadir'   => $g['total_hadir'],
                        'gaji_pokok'    => $gaji_pokok,
                        'tunj_fung'     => $tunj_fung,
                        'tunj_struk'    => $tunj_struk,
                        'tunj_sejahtera'=> $tunj_sejahtera,
                        'status_kawin'  => $status_kawin,
                        'tunj_kawin'    => $tunj_kawin,
                        'jumlah_anak'   => $jumlah_anak,
                        'tunj_anak'     => $tunj_anak,
                        'tunj_beras'    => $tunj_beras,
                        'tunj_makan'    => $tunj_makan,
                        'tunj_lain'     => $tunj_lain,
                        'pot_bpjs_tk'   => $pot_bpjs_tk,
                        'pot_bpjs_kes'  => $pot_bpjs_kes,
                        'pot_koperasi'  => $pot_koperasi,
                        'potongan'      => $pot_lain,
                        'catatan'       => $g['catatan'] ?? ''
                      ])); ?>)">
                <i class="bi bi-pencil-fill"></i>
              </button>

              <?php if ($has_record): ?>
              <!-- Cetak Slip Gaji -->
              <a href="<?php echo BASE_URL; ?>slip_gaji_print?id=<?php echo $g['gaji_id']; ?>" 
                 target="_blank" 
                 class="e-btn e-btn-primary" 
                 style="padding:.25rem .55rem;font-size:.72rem;background:linear-gradient(135deg,#10b981,#059669);" 
                 title="Cetak Slip Gaji">
                <i class="bi bi-printer-fill"></i> Slip Gaji
              </a>

              <a href="<?php echo BASE_URL; ?>gaji_delete?action=delete&id=<?php echo $g['gaji_id']; ?>&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" 
                 class="e-act-btn del" 
                 title="Hapus Data Gaji"
                 onclick="return confirm('Hapus penggajian pegawai ini?')">
                <i class="bi bi-trash3-fill"></i>
              </a>
              <?php else: ?>
              <span class="e-badge" style="opacity:.6;">Belum Disimpan</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="9"><div class="e-empty"><i class="bi bi-cash-coin e-empty-icon"></i><p>Belum ada data pegawai.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Edit / Input Gaji Detail -->
<div class="modal fade" id="modalGaji" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border-medium);border-radius:var(--r-lg);">
      <div class="modal-header" style="border-bottom:1px solid var(--border-light);">
        <h5 class="modal-title" style="font-weight:800;font-size:1.1rem;"><i class="bi bi-cash-stack" style="color:var(--green);"></i> Pengaturan Detail Gaji & Tunjangan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?php echo BASE_URL; ?>gaji_save" method="POST">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="bulan" value="<?php echo $bulan; ?>">
        <input type="hidden" name="tahun" value="<?php echo $tahun; ?>">
        <input type="hidden" name="pegawai_id" id="modal_pegawai_id">

        <div class="modal-body" style="padding:1.25rem;">
          <div style="margin-bottom:1rem;padding:.625rem .875rem;background:var(--bg-muted);border-radius:var(--r-md);display:flex;align-items:center;justify-content:space-between;">
            <div>
              <div style="font-size:.75rem;color:var(--text-muted);">Pegawai:</div>
              <div id="modal_nama_pegawai" style="font-weight:800;font-size:1.05rem;color:var(--text-primary);"></div>
            </div>
            <div style="text-align:right;">
              <div style="font-size:.75rem;color:var(--text-muted);">Periode: <strong><?php echo $nama_bulan[$bulan] . ' ' . $tahun; ?></strong></div>
              <div style="font-size:.78rem;color:var(--blue);font-weight:700;">Kehadiran: <span id="modal_total_hadir">0</span> hari</div>
            </div>
          </div>

          <!-- Section 1: Penerimaan -->
          <div style="font-weight:800;font-size:.85rem;color:var(--green);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.05em;">A. Penerimaan / Tunjangan</div>
          <div class="row g-2" style="background:rgba(16,185,129,.03);padding:.75rem;border-radius:var(--r-md);border:1px solid rgba(16,185,129,.15);margin-bottom:1rem;">
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">Gaji Pokok (Rp) <span class="req">*</span></label>
                <input type="number" class="e-input" name="gaji_pokok" id="modal_gaji_pokok" min="0" step="1000" oninput="calcGajiFormulas()" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">Tunjangan Fungsional (Rp)</label>
                <input type="number" class="e-input" name="tunj_fungsional" id="modal_tunj_fung" min="0" step="1000" oninput="calcGajiFormulas()">
              </div>
            </div>
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">Tunjangan Struktural (Rp)</label>
                <input type="number" class="e-input" name="tunj_struktural" id="modal_tunj_struk" min="0" step="1000" oninput="calcGajiFormulas()">
              </div>
            </div>
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">Tunjangan Kesejahteraan (Rp)</label>
                <input type="number" class="e-input" name="tunj_kesejahteraan" id="modal_tunj_sejahtera" min="0" step="1000" oninput="calcGajiFormulas()">
              </div>
            </div>
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">Status Perkawinan</label>
                <select class="e-select" name="status_kawin" id="modal_status_kawin" onchange="calcGajiFormulas()">
                  <option value="0">Belum Kawin / Tanpa Tunjangan Kawin</option>
                  <option value="1">Kawin / Punya Suami-Istri (10%)</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">Tunjangan Kawin (Rp)</label>
                <input type="number" class="e-input" name="tunj_kawin" id="modal_tunj_kawin" min="0" step="1000" oninput="calcGajiFormulas()">
              </div>
            </div>
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">Jumlah Anak Tanggungan</label>
                <input type="number" class="e-input" name="jumlah_anak" id="modal_jumlah_anak" min="0" max="10" oninput="calcGajiFormulas()" placeholder="2% per anak">
              </div>
            </div>
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">Tunjangan Anak (Rp)</label>
                <input type="number" class="e-input" name="tunj_anak" id="modal_tunj_anak" min="0" step="1000" oninput="calcGajiFormulas()">
              </div>
            </div>
            <div class="col-md-4">
              <div class="e-form-group">
                <label class="e-label">Tunjangan Beras (Rp)</label>
                <input type="number" class="e-input" name="tunj_beras" id="modal_tunj_beras" min="0" step="1000" oninput="calcGajiFormulas()">
              </div>
            </div>
            <div class="col-md-4">
              <div class="e-form-group">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                  <label class="e-label" style="margin:0;">Uang Makan (Rp)</label>
                  <button type="button" class="e-btn e-btn-ghost" style="padding:0 .4rem;font-size:.7rem;color:var(--green);font-weight:700;" onclick="syncUangMakanAbsensi()" title="Hitung ulang otomatis dari absensi">
                    <i class="bi bi-arrow-repeat"></i> Sync Absen
                  </button>
                </div>
                <input type="number" class="e-input" name="tunjangan_makan" id="modal_tunj_makan" min="0" step="1000" oninput="calcGajiTotals()" style="margin-top:.2rem;">
                <div style="font-size:.7rem;color:var(--text-muted);margin-top:.25rem;background:rgba(99,102,241,.06);padding:.25rem .4rem;border-radius:4px;">
                  <i class="bi bi-calendar2-check-fill" style="color:var(--indigo);"></i> Absensi: <strong id="modal_sync_hadir_text">0 hari</strong> &times; Rp 20.000 = <strong id="modal_sync_calc_text">Rp 0</strong>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="e-form-group">
                <label class="e-label">Tunjangan Lain (Rp)</label>
                <input type="number" class="e-input" name="tunjangan_lain" id="modal_tunj_lain" min="0" step="1000" oninput="calcGajiFormulas()">
              </div>
            </div>
          </div>

          <!-- Section 2: Potongan -->
          <div style="font-weight:800;font-size:.85rem;color:var(--red);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.05em;">B. Potongan Gaji</div>
          <div class="row g-2" style="background:rgba(239,68,68,.03);padding:.75rem;border-radius:var(--r-md);border:1px solid rgba(239,68,68,.15);margin-bottom:1rem;">
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">BPJS Ketenagakerjaan (2% Gaji)</label>
                <input type="number" class="e-input" name="pot_bpjs_tk" id="modal_pot_bpjs_tk" min="0" step="1000" oninput="calcGajiTotals()">
              </div>
            </div>
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">BPJS Kesehatan (5% Gaji)</label>
                <input type="number" class="e-input" name="pot_bpjs_kes" id="modal_pot_bpjs_kes" min="0" step="1000" oninput="calcGajiTotals()">
              </div>
            </div>
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">Potongan Koperasi (Rp)</label>
                <input type="number" class="e-input" name="pot_koperasi" id="modal_pot_koperasi" min="0" step="1000" oninput="calcGajiTotals()">
              </div>
            </div>
            <div class="col-md-6">
              <div class="e-form-group">
                <label class="e-label">Potongan Lainnya (Rp)</label>
                <input type="number" class="e-input" name="potongan" id="modal_potongan" min="0" step="1000" oninput="calcGajiTotals()">
              </div>
            </div>
          </div>

          <!-- Section 3: Take Home Pay Preview -->
          <div style="padding:1rem;background:rgba(16,185,129,.1);border:1px solid var(--green);border-radius:var(--r-md);display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <div>
              <div style="font-size:.78rem;font-weight:800;color:var(--green);text-transform:uppercase;">Estimasi Take Home Pay (Total Gaji Diterima)</div>
              <div style="font-size:.75rem;color:var(--text-muted);">Penerimaan - Potongan</div>
            </div>
            <div id="modal_take_home_pay" style="font-size:1.5rem;font-weight:900;color:var(--green);">
              Rp 0
            </div>
          </div>

          <div class="e-form-group">
            <label class="e-label">Catatan / Keterangan Slip Gaji</label>
            <textarea class="e-textarea" name="catatan" id="modal_catatan" rows="2" placeholder="Catatan khusus untuk pegawai..."></textarea>
          </div>
        </div>

        <div class="modal-footer" style="border-top:1px solid var(--border-light);">
          <button type="button" class="e-btn e-btn-ghost" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="e-btn e-btn-primary"><i class="bi bi-check-lg"></i> Simpan Detail Penggajian</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let currentTotalHadir = 0;

function filterGajiTable(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#gajiTable tbody tr').forEach(function(r){
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

function openEditGajiModal(data) {
  currentTotalHadir = data.total_hadir || 0;
  const calcMakan = currentTotalHadir * 20000;

  document.getElementById('modal_pegawai_id').value = data.pegawai_id;
  document.getElementById('modal_nama_pegawai').textContent = data.nama;
  document.getElementById('modal_total_hadir').textContent = currentTotalHadir;
  document.getElementById('modal_sync_hadir_text').textContent = currentTotalHadir + ' hari';
  document.getElementById('modal_sync_calc_text').textContent = 'Rp ' + calcMakan.toLocaleString('id-ID');

  document.getElementById('modal_gaji_pokok').value    = data.gaji_pokok || 0;
  document.getElementById('modal_tunj_fung').value     = data.tunj_fung || 0;
  document.getElementById('modal_tunj_struk').value    = data.tunj_struk || 0;
  document.getElementById('modal_tunj_sejahtera').value= data.tunj_sejahtera || 0;
  document.getElementById('modal_status_kawin').value  = data.status_kawin || 0;
  document.getElementById('modal_tunj_kawin').value    = data.tunj_kawin || 0;
  document.getElementById('modal_jumlah_anak').value   = data.jumlah_anak || 0;
  document.getElementById('modal_tunj_anak').value     = data.tunj_anak || 0;
  document.getElementById('modal_tunj_beras').value    = data.tunj_beras || 200000;

  if (data.tunj_makan !== undefined && data.tunj_makan !== null && data.tunj_makan > 0) {
    document.getElementById('modal_tunj_makan').value  = data.tunj_makan;
  } else {
    document.getElementById('modal_tunj_makan').value  = calcMakan;
  }

  document.getElementById('modal_tunj_lain').value     = data.tunj_lain || 0;

  document.getElementById('modal_pot_bpjs_tk').value  = data.pot_bpjs_tk || (0.02 * (data.gaji_pokok || 0));
  document.getElementById('modal_pot_bpjs_kes').value = data.pot_bpjs_kes || (0.05 * (data.gaji_pokok || 0));
  document.getElementById('modal_pot_koperasi').value = data.pot_koperasi || 20000;
  document.getElementById('modal_potongan').value     = data.potongan || 0;
  document.getElementById('modal_catatan').value      = data.catatan || '';

  calcGajiTotals();

  const modal = new bootstrap.Modal(document.getElementById('modalGaji'));
  modal.show();
}

function syncUangMakanAbsensi() {
  const calcMakan = currentTotalHadir * 20000;
  document.getElementById('modal_tunj_makan').value = calcMakan;
  calcGajiTotals();
}

function calcGajiFormulas() {
  const gp = parseFloat(document.getElementById('modal_gaji_pokok').value) || 0;
  const statusKawin = parseInt(document.getElementById('modal_status_kawin').value) || 0;
  const jmlAnak = parseInt(document.getElementById('modal_jumlah_anak').value) || 0;

  // Auto formulas if not custom edited
  if (statusKawin === 1) {
    document.getElementById('modal_tunj_kawin').value = Math.round(0.10 * gp);
  } else {
    document.getElementById('modal_tunj_kawin').value = 0;
  }

  document.getElementById('modal_tunj_anak').value = Math.round((jmlAnak * 0.02) * gp);
  document.getElementById('modal_pot_bpjs_tk').value = Math.round(0.02 * gp);
  document.getElementById('modal_pot_bpjs_kes').value = Math.round(0.05 * gp);

  calcGajiTotals();
}

function calcGajiTotals() {
  const gp = parseFloat(document.getElementById('modal_gaji_pokok').value) || 0;
  const tf = parseFloat(document.getElementById('modal_tunj_fung').value) || 0;
  const ts = parseFloat(document.getElementById('modal_tunj_struk').value) || 0;
  const tk = parseFloat(document.getElementById('modal_tunj_sejahtera').value) || 0;
  const tkw = parseFloat(document.getElementById('modal_tunj_kawin').value) || 0;
  const tanak = parseFloat(document.getElementById('modal_tunj_anak').value) || 0;
  const tberas = parseFloat(document.getElementById('modal_tunj_beras').value) || 0;
  const tm = parseFloat(document.getElementById('modal_tunj_makan').value) || 0;
  const tl = parseFloat(document.getElementById('modal_tunj_lain').value) || 0;

  $totPenerimaan = gp + tf + ts + tk + tkw + tanak + tberas + tm + tl;

  const potTk = parseFloat(document.getElementById('modal_pot_bpjs_tk').value) || 0;
  const potKes = parseFloat(document.getElementById('modal_pot_bpjs_kes').value) || 0;
  const potKop = parseFloat(document.getElementById('modal_pot_koperasi').value) || 0;
  const potLain = parseFloat(document.getElementById('modal_potongan').value) || 0;

  $totPotongan = potTk + potKes + potKop + potLain;

  const thp = $totPenerimaan - $totPotongan;
  document.getElementById('modal_take_home_pay').textContent = 'Rp ' + thp.toLocaleString('id-ID');
}
</script>

<?php require_once __DIR__ . '/parts/modal_ttd.php'; ?>
