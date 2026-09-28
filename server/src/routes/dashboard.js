// server/src/routes/dashboard.js
const express = require('express');
const router = express.Router();
const { supabase } = require('../config/supabase');
const { verifyToken } = require('../middleware/auth');

router.use(verifyToken);

/** GET /api/dashboard — Ambil data ringkasan untuk dashboard */
router.get('/', async (req, res) => {
  try {
    const role = req.user.role;
    const now = new Date();
    const bulan = now.getMonth() + 1;
    const tahun = now.getFullYear();

    const result = {};

    if (role === 'admin' || role === 'developer') {
      // Statistik untuk admin
      const [
        { count: totalPegawai },
        { count: totalSuratMasuk },
        { count: totalSuratKeluar },
        { count: totalSK },
      ] = await Promise.all([
        supabase.from('pegawai').select('*', { count: 'exact', head: true }),
        supabase.from('surat_masuk').select('*', { count: 'exact', head: true }),
        supabase.from('surat_keluar').select('*', { count: 'exact', head: true }),
        supabase.from('surat_keputusan').select('*', { count: 'exact', head: true }),
      ]);

      result.stats = {
        total_pegawai: totalPegawai || 0,
        total_surat_masuk: totalSuratMasuk || 0,
        total_surat_keluar: totalSuratKeluar || 0,
        total_sk: totalSK || 0,
      };

      // Peringatan kenaikan pangkat (pegawai yang TMT pangkat > 2 tahun)
      const { data: pegawaiList } = await supabase
        .from('pegawai')
        .select('id, nama, nip, pangkat_golongan, tmt_pangkat, jabatan_fungsional, status_kepegawaian')
        .not('tmt_pangkat', 'is', null);

      const alerts = [];
      (pegawaiList || []).forEach((peg) => {
        if (!peg.tmt_pangkat) return;
        const tmt = new Date(peg.tmt_pangkat);
        const diffMonths = (now.getFullYear() - tmt.getFullYear()) * 12 + (now.getMonth() - tmt.getMonth());
        const isDosen = peg.status_kepegawaian?.toLowerCase().includes('dosen') ||
                        peg.jabatan_fungsional?.toLowerCase().includes('lektor') ||
                        peg.jabatan_fungsional?.toLowerCase().includes('asisten ahli');
        const threshold = isDosen ? 36 : 24; // Dosen 3 tahun, tendik 2 tahun

        if (diffMonths >= threshold) {
          alerts.push({
            ...peg,
            masa_bulan: diffMonths,
            is_due: diffMonths >= threshold,
            is_upcoming: diffMonths >= threshold - 3 && diffMonths < threshold,
          });
        }
      });

      result.pangkat_alerts = alerts.slice(0, 10); // Maksimal 10
    }

    if (role === 'bendahara' || role === 'developer') {
      // Summary kas untuk bendahara
      const [
        { data: kasKecilIn },
        { data: kasKecilOut },
        { data: kasBesarIn },
        { data: kasBesarOut },
        { data: gajiList },
        { data: kasTerbaru },
      ] = await Promise.all([
        supabase.from('kas_transaksi').select('jumlah').eq('jenis_kas', 'kas_kecil').eq('tipe', 'pemasukan'),
        supabase.from('kas_transaksi').select('jumlah').eq('jenis_kas', 'kas_kecil').eq('tipe', 'pengeluaran'),
        supabase.from('kas_transaksi').select('jumlah').eq('jenis_kas', 'kas_besar').eq('tipe', 'pemasukan'),
        supabase.from('kas_transaksi').select('jumlah').eq('jenis_kas', 'kas_besar').eq('tipe', 'pengeluaran'),
        supabase.from('gaji').select('total_gaji').eq('bulan', bulan).eq('tahun', tahun),
        supabase.from('kas_transaksi').select('tanggal, jenis_kas, kategori, tipe, jumlah').order('tanggal', { ascending: false }).limit(5),
      ]);

      const sum = (arr) => (arr || []).reduce((acc, r) => acc + parseFloat(r.jumlah || 0), 0);
      const sumGaji = (arr) => (arr || []).reduce((acc, r) => acc + parseFloat(r.total_gaji || 0), 0);

      const kasKecilTotal = sum(kasKecilIn) - sum(kasKecilOut);
      const kasBesarTotal = sum(kasBesarIn) - sum(kasBesarOut);

      result.keuangan = {
        kas_kecil_total: kasKecilTotal,
        kas_besar_total: kasBesarTotal,
        total_kas: kasKecilTotal + kasBesarTotal,
        total_anggaran_gaji: sumGaji(gajiList),
        pegawai_tergaji: (gajiList || []).length,
        bulan,
        tahun,
      };

      result.kas_terbaru = kasTerbaru || [];
    }

    return res.json(result);
  } catch (err) {
    console.error('Dashboard error:', err);
    return res.status(500).json({ message: 'Gagal mengambil data dashboard.' });
  }
});

module.exports = router;
