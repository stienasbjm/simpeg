// server/src/routes/absensi.js
const express = require('express');
const router = express.Router();
const { supabase } = require('../config/supabase');
const { verifyToken, requireAdmin } = require('../middleware/auth');

router.use(verifyToken);

/** GET /api/absensi?bulan=&tahun=&pegawai_id= */
router.get('/', async (req, res) => {
  try {
    const { bulan, tahun, pegawai_id } = req.query;
    let query = supabase
      .from('absensi')
      .select('*, pegawai(id, nama, nip, unit_kerja)', { count: 'exact' })
      .order('tanggal', { ascending: false });

    if (bulan && tahun) {
      const startDate = `${tahun}-${String(bulan).padStart(2, '0')}-01`;
      const endDate = new Date(tahun, bulan, 0).toISOString().split('T')[0];
      query = query.gte('tanggal', startDate).lte('tanggal', endDate);
    }
    if (pegawai_id) query = query.eq('pegawai_id', pegawai_id);

    const { data, error, count } = await query;
    if (error) throw error;
    return res.json({ data, total: count });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Gagal mengambil data absensi.' });
  }
});

/** GET /api/absensi/rekap?bulan=&tahun= — Rekap absensi semua pegawai */
router.get('/rekap', requireAdmin, async (req, res) => {
  try {
    const { bulan, tahun } = req.query;
    if (!bulan || !tahun) return res.status(400).json({ message: 'Bulan dan tahun wajib diisi.' });

    const startDate = `${tahun}-${String(bulan).padStart(2, '0')}-01`;
    const endDate = new Date(tahun, parseInt(bulan), 0).toISOString().split('T')[0];

    const { data, error } = await supabase
      .from('absensi')
      .select('pegawai_id, status, pegawai(nama, nip, unit_kerja)')
      .gte('tanggal', startDate)
      .lte('tanggal', endDate);

    if (error) throw error;

    // Hitung rekap per pegawai
    const rekapMap = {};
    data.forEach((row) => {
      const pid = row.pegawai_id;
      if (!rekapMap[pid]) {
        rekapMap[pid] = {
          pegawai_id: pid,
          nama: row.pegawai?.nama,
          nip: row.pegawai?.nip,
          unit_kerja: row.pegawai?.unit_kerja,
          hadir: 0,
          izin: 0,
          sakit: 0,
          alpha: 0,
          cuti: 0,
          total: 0,
        };
      }
      rekapMap[pid].total++;
      const status = row.status?.toLowerCase();
      if (status === 'hadir') rekapMap[pid].hadir++;
      else if (status === 'izin') rekapMap[pid].izin++;
      else if (status === 'sakit') rekapMap[pid].sakit++;
      else if (status === 'alpha') rekapMap[pid].alpha++;
      else if (status === 'cuti') rekapMap[pid].cuti++;
    });

    return res.json({ data: Object.values(rekapMap) });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal mengambil rekap absensi.' });
  }
});

/** POST /api/absensi — Input/Update absensi (bulk upsert) */
router.post('/', requireAdmin, async (req, res) => {
  try {
    const { absensi_list } = req.body; // Array: [{pegawai_id, tanggal, status, keterangan}]
    if (!Array.isArray(absensi_list) || absensi_list.length === 0) {
      return res.status(400).json({ message: 'Data absensi tidak valid.' });
    }

    const toUpsert = absensi_list.map((item) => ({
      pegawai_id: item.pegawai_id,
      tanggal: item.tanggal,
      status: item.status,
      keterangan: item.keterangan || null,
      created_by: req.user.id,
      updated_at: new Date().toISOString(),
    }));

    const { data, error } = await supabase
      .from('absensi')
      .upsert(toUpsert, { onConflict: 'pegawai_id,tanggal' })
      .select();

    if (error) throw error;
    return res.json({ message: `${data.length} data absensi berhasil disimpan.`, data });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Gagal menyimpan data absensi.' });
  }
});

/** POST /api/absensi/izin — Pengajuan izin oleh pegawai */
router.post('/izin', async (req, res) => {
  try {
    const { tanggal_mulai, tanggal_selesai, jenis_izin, alasan } = req.body;

    if (!tanggal_mulai || !jenis_izin) {
      return res.status(400).json({ message: 'Tanggal dan jenis izin wajib diisi.' });
    }

    const { data, error } = await supabase.from('izin').insert([{
      pegawai_id: req.user.pegawai_id,
      tanggal_mulai,
      tanggal_selesai: tanggal_selesai || tanggal_mulai,
      jenis_izin,
      alasan: alasan?.trim() || null,
      status: 'pending',
      created_at: new Date().toISOString(),
    }]).select().single();

    if (error) throw error;
    return res.status(201).json({ message: 'Pengajuan izin berhasil dikirim.', data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal mengajukan izin.' });
  }
});

/** GET /api/absensi/izin — List pengajuan izin */
router.get('/izin', requireAdmin, async (req, res) => {
  try {
    const { status } = req.query;
    let query = supabase.from('izin').select('*, pegawai(nama, nip)').order('created_at', { ascending: false });
    if (status) query = query.eq('status', status);
    const { data, error } = await query;
    if (error) throw error;
    return res.json({ data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal mengambil data izin.' });
  }
});

/** PUT /api/absensi/izin/:id/approve — Setujui/Tolak izin */
router.put('/izin/:id/approve', requireAdmin, async (req, res) => {
  try {
    const { status, catatan } = req.body; // status: 'disetujui' | 'ditolak'
    if (!['disetujui', 'ditolak'].includes(status)) {
      return res.status(400).json({ message: 'Status tidak valid.' });
    }

    const { data, error } = await supabase
      .from('izin')
      .update({ status, catatan_admin: catatan || null, approved_by: req.user.id, updated_at: new Date().toISOString() })
      .eq('id', req.params.id)
      .select()
      .single();

    if (error) throw error;
    return res.json({ message: `Izin berhasil ${status}.`, data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal memperbarui status izin.' });
  }
});

module.exports = router;
