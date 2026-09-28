// server/src/routes/keuangan.js — Kas & Gaji
const express = require('express');
const router = express.Router();
const { supabase } = require('../config/supabase');
const { verifyToken, requireAdmin, requireBendahara } = require('../middleware/auth');

router.use(verifyToken);

// ═══════════════════════════════════════════
// KAS ROUTES
// ═══════════════════════════════════════════

/** GET /api/keuangan/kas?jenis_kas=&bulan=&tahun= */
router.get('/kas', requireBendahara, async (req, res) => {
  try {
    const { jenis_kas, bulan, tahun } = req.query;
    let query = supabase
      .from('kas_transaksi')
      .select('*, users(nama_lengkap)', { count: 'exact' })
      .order('tanggal', { ascending: false });

    if (jenis_kas) query = query.eq('jenis_kas', jenis_kas);
    if (bulan && tahun) {
      const startDate = `${tahun}-${String(bulan).padStart(2, '0')}-01`;
      const endDate = new Date(tahun, parseInt(bulan), 0).toISOString().split('T')[0];
      query = query.gte('tanggal', startDate).lte('tanggal', endDate);
    } else if (tahun) {
      query = query.gte('tanggal', `${tahun}-01-01`).lte('tanggal', `${tahun}-12-31`);
    }

    const { data, error, count } = await query;
    if (error) throw error;

    // Hitung saldo per jenis kas
    const { data: summary } = await supabase.rpc('get_kas_summary');
    return res.json({ data, total: count, summary: summary?.[0] || {} });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Gagal mengambil data kas.' });
  }
});

/** GET /api/keuangan/kas/summary */
router.get('/kas/summary', requireBendahara, async (req, res) => {
  try {
    // Hitung dari sisi server
    const { data: kasKecilIn } = await supabase.from('kas_transaksi').select('jumlah').eq('jenis_kas', 'kas_kecil').eq('tipe', 'pemasukan');
    const { data: kasKecilOut } = await supabase.from('kas_transaksi').select('jumlah').eq('jenis_kas', 'kas_kecil').eq('tipe', 'pengeluaran');
    const { data: kasBesarIn } = await supabase.from('kas_transaksi').select('jumlah').eq('jenis_kas', 'kas_besar').eq('tipe', 'pemasukan');
    const { data: kasBesarOut } = await supabase.from('kas_transaksi').select('jumlah').eq('jenis_kas', 'kas_besar').eq('tipe', 'pengeluaran');

    const sum = (arr) => (arr || []).reduce((acc, r) => acc + parseFloat(r.jumlah || 0), 0);

    const kasKecilTotal = sum(kasKecilIn) - sum(kasKecilOut);
    const kasBesarTotal = sum(kasBesarIn) - sum(kasBesarOut);

    return res.json({
      kas_kecil_total: kasKecilTotal,
      kas_besar_total: kasBesarTotal,
      total_kas: kasKecilTotal + kasBesarTotal,
    });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal mengambil summary kas.' });
  }
});

/** POST /api/keuangan/kas */
router.post('/kas', requireBendahara, async (req, res) => {
  try {
    const { jenis_kas, tipe, kategori, jumlah, keterangan, tanggal } = req.body;

    if (!jenis_kas || !tipe || !kategori || !jumlah || !tanggal) {
      return res.status(400).json({ message: 'Semua field wajib diisi.' });
    }
    if (!['kas_kecil', 'kas_besar'].includes(jenis_kas)) {
      return res.status(400).json({ message: 'Jenis kas tidak valid.' });
    }
    if (!['pemasukan', 'pengeluaran'].includes(tipe)) {
      return res.status(400).json({ message: 'Tipe transaksi tidak valid.' });
    }

    const { data, error } = await supabase.from('kas_transaksi').insert([{
      jenis_kas,
      tipe,
      kategori: kategori.trim(),
      jumlah: parseFloat(jumlah),
      keterangan: keterangan?.trim() || null,
      tanggal,
      created_by: req.user.id,
      created_at: new Date().toISOString(),
    }]).select().single();

    if (error) throw error;
    return res.status(201).json({ message: 'Transaksi kas berhasil ditambahkan.', data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal menambah transaksi kas.' });
  }
});

/** PUT /api/keuangan/kas/:id */
router.put('/kas/:id', requireBendahara, async (req, res) => {
  try {
    const { jenis_kas, tipe, kategori, jumlah, keterangan, tanggal } = req.body;
    const { data, error } = await supabase.from('kas_transaksi').update({
      jenis_kas,
      tipe,
      kategori: kategori?.trim(),
      jumlah: parseFloat(jumlah),
      keterangan: keterangan?.trim() || null,
      tanggal,
      updated_at: new Date().toISOString(),
    }).eq('id', req.params.id).select().single();
    if (error) throw error;
    return res.json({ message: 'Transaksi kas berhasil diperbarui.', data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal memperbarui transaksi kas.' });
  }
});

/** DELETE /api/keuangan/kas/:id */
router.delete('/kas/:id', requireBendahara, async (req, res) => {
  try {
    const { error } = await supabase.from('kas_transaksi').delete().eq('id', req.params.id);
    if (error) throw error;
    return res.json({ message: 'Transaksi kas berhasil dihapus.' });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal menghapus transaksi kas.' });
  }
});

// ═══════════════════════════════════════════
// GAJI ROUTES
// ═══════════════════════════════════════════

/** GET /api/keuangan/gaji?bulan=&tahun= */
router.get('/gaji', requireBendahara, async (req, res) => {
  try {
    const { bulan, tahun } = req.query;
    const b = parseInt(bulan) || new Date().getMonth() + 1;
    const t = parseInt(tahun) || new Date().getFullYear();

    // Ambil semua pegawai aktif + data gaji mereka (left join)
    const { data: pegawaiList, error: pegErr } = await supabase
      .from('pegawai')
      .select('id, nip, nama, jabatan, pangkat_golongan, unit_kerja, status_kepegawaian')
      .order('nama');

    if (pegErr) throw pegErr;

    const { data: gajiList, error: gajiErr } = await supabase
      .from('gaji')
      .select('*')
      .eq('bulan', b)
      .eq('tahun', t);

    if (gajiErr) throw gajiErr;

    const gajiMap = {};
    (gajiList || []).forEach((g) => { gajiMap[g.pegawai_id] = g; });

    const result = (pegawaiList || []).map((peg) => ({
      ...peg,
      gaji: gajiMap[peg.id] || null,
    }));

    return res.json({ data: result, bulan: b, tahun: t });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Gagal mengambil data gaji.' });
  }
});

/** POST /api/keuangan/gaji — Simpan gaji satu pegawai */
router.post('/gaji', requireBendahara, async (req, res) => {
  try {
    const {
      pegawai_id, bulan, tahun,
      gaji_pokok, tunjangan_jabatan, tunjangan_keluarga,
      tunjangan_makan, tunjangan_transport, tunjangan_lain,
      potongan_bpjs, potongan_pajak, potongan_lain, keterangan
    } = req.body;

    if (!pegawai_id || !bulan || !tahun) {
      return res.status(400).json({ message: 'Pegawai ID, bulan, dan tahun wajib diisi.' });
    }

    const gp = parseFloat(gaji_pokok) || 0;
    const tj = parseFloat(tunjangan_jabatan) || 0;
    const tk = parseFloat(tunjangan_keluarga) || 0;
    const tm = parseFloat(tunjangan_makan) || 0;
    const tt = parseFloat(tunjangan_transport) || 0;
    const tl = parseFloat(tunjangan_lain) || 0;
    const pb = parseFloat(potongan_bpjs) || 0;
    const pp = parseFloat(potongan_pajak) || 0;
    const pl = parseFloat(potongan_lain) || 0;

    const total_tunjangan = tj + tk + tm + tt + tl;
    const total_potongan = pb + pp + pl;
    const total_gaji = gp + total_tunjangan - total_potongan;

    const gajiData = {
      pegawai_id,
      bulan: parseInt(bulan),
      tahun: parseInt(tahun),
      gaji_pokok: gp,
      tunjangan_jabatan: tj,
      tunjangan_keluarga: tk,
      tunjangan_makan: tm,
      tunjangan_transport: tt,
      tunjangan_lain: tl,
      potongan_bpjs: pb,
      potongan_pajak: pp,
      potongan_lain: pl,
      total_tunjangan,
      total_potongan,
      total_gaji,
      keterangan: keterangan?.trim() || null,
      created_by: req.user.id,
      updated_at: new Date().toISOString(),
    };

    const { data, error } = await supabase
      .from('gaji')
      .upsert(gajiData, { onConflict: 'pegawai_id,bulan,tahun' })
      .select()
      .single();

    if (error) throw error;
    return res.json({ message: 'Data gaji berhasil disimpan.', data });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Gagal menyimpan data gaji.' });
  }
});

/** GET /api/keuangan/gaji/slip/:pegawaiId?bulan=&tahun= */
router.get('/gaji/slip/:pegawaiId', requireBendahara, async (req, res) => {
  try {
    const { bulan, tahun } = req.query;
    const { data: gaji, error: gajiErr } = await supabase
      .from('gaji')
      .select('*')
      .eq('pegawai_id', req.params.pegawaiId)
      .eq('bulan', parseInt(bulan))
      .eq('tahun', parseInt(tahun))
      .single();

    if (gajiErr || !gaji) return res.status(404).json({ message: 'Data slip gaji tidak ditemukan.' });

    const { data: pegawai, error: pegErr } = await supabase
      .from('pegawai')
      .select('id, nama, nip, jabatan, pangkat_golongan, unit_kerja')
      .eq('id', req.params.pegawaiId)
      .single();

    if (pegErr) throw pegErr;

    // Ambil data penandatangan
    const { data: ttd } = await supabase.from('pengaturan').select('*').eq('key', 'ttd').single();

    return res.json({ gaji, pegawai, ttd: ttd?.value || {} });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal mengambil data slip gaji.' });
  }
});

// ═══════════════════════════════════════════
// PENGATURAN TTD
// ═══════════════════════════════════════════

/** GET /api/keuangan/ttd */
router.get('/ttd', requireBendahara, async (req, res) => {
  try {
    const { data } = await supabase.from('pengaturan').select('value').eq('key', 'ttd').single();
    return res.json({ data: data?.value || {} });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal mengambil data TTD.' });
  }
});

/** PUT /api/keuangan/ttd */
router.put('/ttd', requireBendahara, async (req, res) => {
  try {
    const { data, error } = await supabase
      .from('pengaturan')
      .upsert({ key: 'ttd', value: req.body }, { onConflict: 'key' })
      .select()
      .single();
    if (error) throw error;
    return res.json({ message: 'Pengaturan TTD berhasil disimpan.', data: data.value });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal menyimpan pengaturan TTD.' });
  }
});

module.exports = router;
