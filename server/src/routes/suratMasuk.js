// server/src/routes/suratMasuk.js
const express = require('express');
const router = express.Router();
const { supabase } = require('../config/supabase');
const { verifyToken, requireAdmin } = require('../middleware/auth');
const upload = require('../middleware/upload');

router.use(verifyToken);

/** GET /api/surat-masuk */
router.get('/', async (req, res) => {
  try {
    const { search, tahun, limit = 100, offset = 0 } = req.query;
    let query = supabase
      .from('surat_masuk')
      .select('*', { count: 'exact' })
      .order('tanggal_surat', { ascending: false });

    if (search) {
      query = query.or(`nomor_surat.ilike.%${search}%,perihal.ilike.%${search}%,pengirim.ilike.%${search}%`);
    }
    if (tahun) {
      query = query.gte('tanggal_surat', `${tahun}-01-01`).lte('tanggal_surat', `${tahun}-12-31`);
    }
    query = query.range(parseInt(offset), parseInt(offset) + parseInt(limit) - 1);

    const { data, error, count } = await query;
    if (error) throw error;
    return res.json({ data, total: count });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Gagal mengambil data surat masuk.' });
  }
});

/** GET /api/surat-masuk/:id */
router.get('/:id', async (req, res) => {
  try {
    const { data, error } = await supabase
      .from('surat_masuk')
      .select('*')
      .eq('id', req.params.id)
      .single();
    if (error || !data) return res.status(404).json({ message: 'Surat masuk tidak ditemukan.' });
    return res.json({ data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal mengambil detail surat masuk.' });
  }
});

/** POST /api/surat-masuk */
router.post('/', requireAdmin, upload.single('file_surat'), async (req, res) => {
  try {
    const body = req.body;
    let file_url = null;

    if (req.file) {
      const ext = req.file.originalname.split('.').pop();
      const fileName = `sm_${Date.now()}.${ext}`;
      const { error: uploadErr } = await supabase.storage
        .from('surat-masuk')
        .upload(fileName, req.file.buffer, { contentType: req.file.mimetype });
      if (!uploadErr) {
        const { data: pub } = supabase.storage.from('surat-masuk').getPublicUrl(fileName);
        file_url = pub.publicUrl;
      }
    }

    const { data, error } = await supabase.from('surat_masuk').insert([{
      nomor_surat: body.nomor_surat?.trim(),
      tanggal_surat: body.tanggal_surat,
      tanggal_terima: body.tanggal_terima,
      pengirim: body.pengirim?.trim(),
      perihal: body.perihal?.trim(),
      keterangan: body.keterangan?.trim() || null,
      file_url,
      created_by: req.user.id,
      created_at: new Date().toISOString(),
    }]).select().single();

    if (error) throw error;
    return res.status(201).json({ message: 'Surat masuk berhasil ditambahkan.', data });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Gagal menambah surat masuk.' });
  }
});

/** PUT /api/surat-masuk/:id */
router.put('/:id', requireAdmin, upload.single('file_surat'), async (req, res) => {
  try {
    const body = req.body;
    let file_url = body.file_url_existing || undefined;

    if (req.file) {
      const ext = req.file.originalname.split('.').pop();
      const fileName = `sm_${Date.now()}.${ext}`;
      const { error: uploadErr } = await supabase.storage
        .from('surat-masuk')
        .upload(fileName, req.file.buffer, { contentType: req.file.mimetype });
      if (!uploadErr) {
        const { data: pub } = supabase.storage.from('surat-masuk').getPublicUrl(fileName);
        file_url = pub.publicUrl;
      }
    }

    const updateData = {
      nomor_surat: body.nomor_surat?.trim(),
      tanggal_surat: body.tanggal_surat,
      tanggal_terima: body.tanggal_terima,
      pengirim: body.pengirim?.trim(),
      perihal: body.perihal?.trim(),
      keterangan: body.keterangan?.trim() || null,
      updated_at: new Date().toISOString(),
    };
    if (file_url !== undefined) updateData.file_url = file_url;

    const { data, error } = await supabase.from('surat_masuk').update(updateData).eq('id', req.params.id).select().single();
    if (error) throw error;
    return res.json({ message: 'Surat masuk berhasil diperbarui.', data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal memperbarui surat masuk.' });
  }
});

/** DELETE /api/surat-masuk/:id */
router.delete('/:id', requireAdmin, async (req, res) => {
  try {
    const { error } = await supabase.from('surat_masuk').delete().eq('id', req.params.id);
    if (error) throw error;
    return res.json({ message: 'Surat masuk berhasil dihapus.' });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal menghapus surat masuk.' });
  }
});

module.exports = router;
