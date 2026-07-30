<?php
// templates/kas.php — Manajemen Arus Kas Kecil & Kas Besar
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/auth/auth.php';
require_once __DIR__ . '/../src/modules/keuangan_functions.php';
require_bendahara();

$jenis_kas_filter = $_GET['jenis_kas'] ?? '';
$bulan            = (int)($_GET['bulan'] ?? date('n'));
$tahun            = (int)($_GET['tahun'] ?? date('Y'));

$transaksi = get_all_kas($conn, $jenis_kas_filter, $bulan, $tahun);
$saldo     = get_kas_summary($conn);

$nama_bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
               7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$tahun_opts = range(2020, 2035);
?>

<div class="e-page-header">
  <div>
    <div class="e-breadcrumb">
      <i class="bi bi-house-fill" style="font-size:.7rem;"></i>
      <a href="<?php echo BASE_URL; ?>dashboard">Beranda</a>
      <span class="e-breadcrumb-sep">/</span>
      <span style="color:var(--blue);font-weight:600;">Keuangan</span>
      <span class="e-breadcrumb-sep">/</span>
      <span style="color:var(--text-muted);">Arus Kas</span>
    </div>
    <h1 class="e-page-title">
      <div class="title-icon" style="background:linear-gradient(135deg,rgba(37,99,235,.15),rgba(59,130,246,.08));color:var(--blue);">
        <i class="bi bi-wallet2"></i>
      </div>
      Manajemen Arus Kas
    </h1>
    <p class="e-page-sub">Pencatatan kas kecil & kas besar instansi periode <strong><?php echo $nama_bulan[$bulan] . ' ' . $tahun; ?></strong>.</p>
  </div>

  <button type="button" class="e-btn e-btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKas">
    <i class="bi bi-plus-lg"></i> Tambah Transaksi Kas
  </button>
</div>

<?php if (!empty($_SESSION['success_message'])): ?>
<div class="e-notice success" data-dismiss><i class="bi bi-check-circle-fill"></i><span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error_message'])): ?>
<div class="e-notice danger" data-dismiss><i class="bi bi-exclamation-triangle-fill"></i><span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span></div>
<?php endif; ?>

<!-- Stat Widgets Saldo -->
<div class="row g-3" style="margin-bottom:1.25rem;">
  <div class="col-md-4">
    <div class="e-card" style="background:linear-gradient(135deg,rgba(59,130,246,.08),rgba(37,99,235,.02));border:1px solid rgba(59,130,246,.2);">
      <div class="e-card-body" style="padding:1.1rem 1.25rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <span style="font-size:.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Saldo Kas Kecil</span>
          <span class="e-badge blue">Kas Operasional</span>
        </div>
        <div style="font-size:1.5rem;font-weight:900;color:var(--blue);margin-top:.3rem;">
          Rp <?php echo number_format($saldo['kas_kecil_total'], 0, ',', '.'); ?>
        </div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:.25rem;">
          Masuk: Rp <?php echo number_format($saldo['kas_kecil_masuk'], 0, ',', '.'); ?> &middot; Keluar: Rp <?php echo number_format($saldo['kas_kecil_keluar'], 0, ',', '.'); ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="e-card" style="background:linear-gradient(135deg,rgba(16,185,129,.08),rgba(5,150,105,.02));border:1px solid rgba(16,185,129,.2);">
      <div class="e-card-body" style="padding:1.1rem 1.25rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <span style="font-size:.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Saldo Kas Besar</span>
          <span class="e-badge green">Kas Utama</span>
        </div>
        <div style="font-size:1.5rem;font-weight:900;color:var(--green);margin-top:.3rem;">
          Rp <?php echo number_format($saldo['kas_besar_total'], 0, ',', '.'); ?>
        </div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:.25rem;">
          Masuk: Rp <?php echo number_format($saldo['kas_besar_masuk'], 0, ',', '.'); ?> &middot; Keluar: Rp <?php echo number_format($saldo['kas_besar_keluar'], 0, ',', '.'); ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="e-card" style="background:linear-gradient(135deg,rgba(139,92,246,.08),rgba(124,58,237,.02));border:1px solid rgba(139,92,246,.2);">
      <div class="e-card-body" style="padding:1.1rem 1.25rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <span style="font-size:.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Total Seluruh Kas</span>
          <span class="e-badge purple">Akumulasi</span>
        </div>
        <div style="font-size:1.5rem;font-weight:900;color:var(--purple);margin-top:.3rem;">
          Rp <?php echo number_format($saldo['total_kas'], 0, ',', '.'); ?>
        </div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:.25rem;">
          Kas Kecil + Kas Besar
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Filter Bar -->
<div class="e-card" style="margin-bottom:1.25rem;">
  <div class="e-card-body" style="padding:1rem 1.25rem;">
    <form method="GET" action="" style="display:flex;align-items:flex-end;gap:.875rem;flex-wrap:wrap;">
      <input type="hidden" name="page" value="kas">
      <div>
        <label class="e-label">Jenis Kas</label>
        <select class="e-select" name="jenis_kas" style="width:150px;">
          <option value="">Semua Kas</option>
          <option value="kas_kecil" <?php echo $jenis_kas_filter==='kas_kecil'?'selected':''; ?>>Kas Kecil</option>
          <option value="kas_besar" <?php echo $jenis_kas_filter==='kas_besar'?'selected':''; ?>>Kas Besar</option>
        </select>
      </div>
      <div>
        <label class="e-label">Bulan</label>
        <select class="e-select" name="bulan" style="width:140px;">
          <?php foreach ($nama_bulan as $m => $n): ?>
          <option value="<?php echo $m; ?>" <?php echo $m === $bulan ? 'selected' : ''; ?>><?php echo $n; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="e-label">Tahun</label>
        <select class="e-select" name="tahun" style="width:100px;">
          <?php foreach ($tahun_opts as $y): ?>
          <option value="<?php echo $y; ?>" <?php echo $y === $tahun ? 'selected' : ''; ?>><?php echo $y; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="e-btn e-btn-primary"><i class="bi bi-funnel-fill"></i> Filter Kas</button>
      <a href="<?php echo BASE_URL; ?>kas_export?jenis_kas=<?php echo $jenis_kas_filter; ?>&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" class="e-btn e-btn-ghost" style="background:#15803d;color:#fff;border:none;" title="Download Excel Laporan Kas">
        <i class="bi bi-file-earmark-excel-fill"></i> Export Excel
      </a>
      <a href="<?php echo BASE_URL; ?>kas_print?jenis_kas=<?php echo $jenis_kas_filter; ?>&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" target="_blank" class="e-btn e-btn-ghost" style="background:#2563eb;color:#fff;border:none;" title="Cetak Laporan / PDF Kas">
        <i class="bi bi-printer-fill"></i> Cetak Laporan PDF
      </a>
      <button type="button" class="e-btn e-btn-ghost" style="background:rgba(99,102,241,.1);color:var(--indigo);border:none;" onclick="openModalTTD()" title="Pengaturan Nama & NIP Pejabat Penandatangan">
        <i class="bi bi-pen-fill"></i> Atur Penandatangan
      </button>
    </form>
  </div>
</div>

<!-- Tabel Transaksi Kas -->
<div class="e-table-wrap">
  <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border-light);background:var(--bg-muted);display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
    <div class="e-card-title"><i class="bi bi-receipt" style="color:var(--blue);"></i> Riwayat Transaksi Kas <span class="e-badge blue"><?php echo count($transaksi); ?> Transaksi</span></div>
    <div class="e-search" style="margin-left:auto;"><i class="bi bi-search e-search-icon"></i><input type="text" id="kasSearch" placeholder="Cari transaksi..." oninput="filterKasTable(this.value)"></div>
  </div>
  <div style="overflow-x:auto;">
    <table class="e-table" id="kasTable">
      <thead><tr>
        <th style="width:40px;">#</th>
        <th>Tanggal</th>
        <th>Jenis Kas</th>
        <th>Tipe</th>
        <th>Kategori</th>
        <th>Keterangan</th>
        <th style="text-align:right;">Nominal (Rp)</th>
        <th>Pencatat</th>
        <th style="text-align:center;">Aksi</th>
      </tr></thead>
      <tbody>
        <?php if (!empty($transaksi)): $no=1; foreach ($transaksi as $t): ?>
        <tr>
          <td style="color:var(--text-faint);font-size:.75rem;font-weight:800;"><?php echo $no++; ?></td>
          <td style="font-weight:600;"><?php echo date('d M Y', strtotime($t['tanggal'])); ?></td>
          <td>
            <span class="e-badge <?php echo $t['jenis_kas']==='kas_kecil'?'blue':'green'; ?>">
              <?php echo $t['jenis_kas']==='kas_kecil'?'Kas Kecil':'Kas Besar'; ?>
            </span>
          </td>
          <td>
            <span class="e-badge <?php echo $t['tipe']==='pemasukan'?'green':'red'; ?>">
              <i class="bi <?php echo $t['tipe']==='pemasukan'?'bi-arrow-down-left':'bi-arrow-up-right'; ?>"></i>
              <?php echo ucfirst($t['tipe']); ?>
            </span>
          </td>
          <td style="font-weight:700;">
            <?php if (!empty($t['is_auto_gaji'])): ?>
              <span class="e-badge purple" style="font-size:.65rem;margin-right:.25rem;"><i class="bi bi-cash-stack"></i> Sync Gaji</span>
            <?php endif; ?>
            <?php echo htmlspecialchars($t['kategori']); ?>
          </td>
          <td style="font-size:.82rem;color:var(--text-muted);"><?php echo htmlspecialchars($t['keterangan'] ?: '—'); ?></td>
          <td style="text-align:right;font-weight:800;color:<?php echo $t['tipe']==='pemasukan'?'var(--green)':'var(--red)'; ?>;">
            <?php echo ($t['tipe']==='pemasukan'?'+':'-') . ' Rp ' . number_format($t['jumlah'], 0, ',', '.'); ?>
          </td>
          <td style="font-size:.78rem;color:var(--text-muted);"><?php echo htmlspecialchars($t['user_name'] ?: 'System'); ?></td>
          <td style="text-align:center;">
            <div style="display:flex;align-items:center;justify-content:center;gap:.35rem;">
              <?php if (!empty($t['is_auto_gaji'])): ?>
                <a href="<?php echo BASE_URL; ?>gaji?bulan=<?php echo $t['bulan'] ?? $bulan; ?>&tahun=<?php echo $t['tahun'] ?? $tahun; ?>" class="e-btn e-btn-ghost" style="padding:.2rem .5rem;font-size:.72rem;color:var(--purple);" title="Kelola di Modul Penggajian">
                  <i class="bi bi-arrow-right-circle"></i> Detail Gaji
                </a>
              <?php else: ?>
                <button type="button" class="e-act-btn edit" 
                        title="Edit Transaksi Kas"
                        onclick="openEditKasModal(<?php echo htmlspecialchars(json_encode($t)); ?>)">
                  <i class="bi bi-pencil-fill"></i>
                </button>
                <a href="<?php echo BASE_URL; ?>kas_delete?action=delete&id=<?php echo $t['id']; ?>&jenis_kas=<?php echo $jenis_kas_filter; ?>&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>" 
                   class="e-act-btn del" 
                   title="Hapus Transaksi"
                   onclick="return confirm('Hapus pencatatan kas ini?')">
                   <i class="bi bi-trash3-fill"></i>
                </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="9"><div class="e-empty"><i class="bi bi-wallet-2 e-empty-icon"></i><p>Belum ada transaksi kas untuk periode ini.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah Transaksi Kas -->
<div class="modal fade" id="modalTambahKas" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border-medium);border-radius:var(--r-lg);">
      <div class="modal-header" style="border-bottom:1px solid var(--border-light);">
        <h5 class="modal-title" style="font-weight:800;font-size:1.1rem;"><i class="bi bi-plus-circle-fill" style="color:var(--blue);"></i> Tambah Transaksi Kas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?php echo BASE_URL; ?>kas_save" method="POST">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="jenis_kas_filter" value="<?php echo $jenis_kas_filter; ?>">
        <input type="hidden" name="bulan" value="<?php echo $bulan; ?>">
        <input type="hidden" name="tahun" value="<?php echo $tahun; ?>">
        
        <div class="modal-body" style="padding:1.25rem;">
          <div class="row g-3">
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">Tanggal Transaksi <span class="req">*</span></label>
                <input type="date" class="e-input" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
              </div>
            </div>
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">Jenis Kas <span class="req">*</span></label>
                <select class="e-select" name="jenis_kas" required>
                  <option value="kas_kecil">Kas Kecil (Operasional)</option>
                  <option value="kas_besar">Kas Besar (Utama)</option>
                </select>
              </div>
            </div>
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">Tipe Transaksi <span class="req">*</span></label>
                <select class="e-select" name="tipe" required>
                  <option value="pemasukan">Pemasukan (+)</option>
                  <option value="pengeluaran">Pengeluaran (-)</option>
                </select>
              </div>
            </div>
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">Nominal Transaksi (Rp) <span class="req">*</span></label>
                <input type="number" class="e-input" name="jumlah" placeholder="Contoh: 150000" min="100" required>
              </div>
            </div>
            <div class="col-12">
              <div class="e-form-group">
                <label class="e-label">Kategori / Akun <span class="req">*</span></label>
                <input type="text" class="e-input" name="kategori" placeholder="Contoh: Pembelian ATK, Penerimaan Dana, Biaya Listrik" required>
              </div>
            </div>
            <div class="col-12">
              <div class="e-form-group">
                <label class="e-label">Keterangan Tambahan</label>
                <textarea class="e-textarea" name="keterangan" rows="2" placeholder="Detail transaksi..."></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--border-light);">
          <button type="button" class="e-btn e-btn-ghost" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="e-btn e-btn-primary"><i class="bi bi-check-lg"></i> Simpan Transaksi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Transaksi Kas -->
<div class="modal fade" id="modalEditKas" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border-medium);border-radius:var(--r-lg);">
      <div class="modal-header" style="border-bottom:1px solid var(--border-light);">
        <h5 class="modal-title" style="font-weight:800;font-size:1.1rem;"><i class="bi bi-pencil-square" style="color:var(--amber);"></i> Edit Transaksi Kas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?php echo BASE_URL; ?>kas_save" method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_kas_id">
        <input type="hidden" name="jenis_kas_filter" value="<?php echo $jenis_kas_filter; ?>">
        <input type="hidden" name="bulan" value="<?php echo $bulan; ?>">
        <input type="hidden" name="tahun" value="<?php echo $tahun; ?>">
        
        <div class="modal-body" style="padding:1.25rem;">
          <div class="row g-3">
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">Tanggal Transaksi <span class="req">*</span></label>
                <input type="date" class="e-input" name="tanggal" id="edit_kas_tanggal" required>
              </div>
            </div>
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">Jenis Kas <span class="req">*</span></label>
                <select class="e-select" name="jenis_kas" id="edit_kas_jenis_kas" required>
                  <option value="kas_kecil">Kas Kecil (Operasional)</option>
                  <option value="kas_besar">Kas Besar (Utama)</option>
                </select>
              </div>
            </div>
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">Tipe Transaksi <span class="req">*</span></label>
                <select class="e-select" name="tipe" id="edit_kas_tipe" required>
                  <option value="pemasukan">Pemasukan (+)</option>
                  <option value="pengeluaran">Pengeluaran (-)</option>
                </select>
              </div>
            </div>
            <div class="col-6">
              <div class="e-form-group">
                <label class="e-label">Nominal Transaksi (Rp) <span class="req">*</span></label>
                <input type="number" class="e-input" name="jumlah" id="edit_kas_jumlah" min="100" required>
              </div>
            </div>
            <div class="col-12">
              <div class="e-form-group">
                <label class="e-label">Kategori / Akun <span class="req">*</span></label>
                <input type="text" class="e-input" name="kategori" id="edit_kas_kategori" required>
              </div>
            </div>
            <div class="col-12">
              <div class="e-form-group">
                <label class="e-label">Keterangan Tambahan</label>
                <textarea class="e-textarea" name="keterangan" id="edit_kas_keterangan" rows="2"></textarea>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer" style="border-top:1px solid var(--border-light);">
          <button type="button" class="e-btn e-btn-ghost" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="e-btn e-btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function filterKasTable(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#kasTable tbody tr').forEach(function(r){
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

function openEditKasModal(data) {
  document.getElementById('edit_kas_id').value = data.id;
  document.getElementById('edit_kas_tanggal').value = data.tanggal;
  document.getElementById('edit_kas_jenis_kas').value = data.jenis_kas;
  document.getElementById('edit_kas_tipe').value = data.tipe;
  document.getElementById('edit_kas_jumlah').value = data.jumlah;
  document.getElementById('edit_kas_kategori').value = data.kategori;
  document.getElementById('edit_kas_keterangan').value = data.keterangan || '';

  const modal = new bootstrap.Modal(document.getElementById('modalEditKas'));
  modal.show();
}
</script>

<?php require_once __DIR__ . '/parts/modal_ttd.php'; ?>
