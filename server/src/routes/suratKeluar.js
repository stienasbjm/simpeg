// server/src/routes/suratKeluar.js
const express = require('express');
const router = express.Router();
const { supabase } = require('../config/supabase');
const { verifyToken, requireAdmin } = require('../middleware/auth');
const upload = require('../middleware/upload');

router.use(verifyToken);

router.get('/', async (req, res) => {
  try {
    const { search, tahun, limit = 100, offset = 0 } = req.query;
    let query = supabase.from('surat_keluar').select('*', { count: 'exact' }).order('tanggal_surat', { ascending: false });
    if (search) query = query.or(`nomor_surat.ilike.%${search}%,perihal.ilike.%${search}%,tujuan.ilike.%${search}%`);
    if (tahun) query = query.gte('tanggal_surat', `${tahun}-01-01`).lte('tanggal_surat', `${tahun}-12-31`);
    query = query.range(parseInt(offset), parseInt(offset) + parseInt(limit) - 1);
    const { data, error, count } = await query;
    if (error) throw error;
    return res.json({ data, total: count });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal mengambil data surat keluar.' });
  }
});

router.get('/:id', async (req, res) => {
  try {
    const { data, error } = await supabase.from('surat_keluar').select('*').eq('id', req.params.id).single();
    if (error || !data) return res.status(404).json({ message: 'Surat keluar tidak ditemukan.' });
    return res.json({ data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal mengambil detail surat keluar.' });
  }
});

router.post('/', requireAdmin, upload.single('file_surat'), async (req, res) => {
  try {
    const body = req.body;
    let file_url = null;
    if (req.file) {
      const ext = req.file.originalname.split('.').pop();
      const fileName = `sk_${Date.now()}.${ext}`;
      const { error: uploadErr } = await supabase.storage.from('surat-keluar').upload(fileName, req.file.buffer, { contentType: req.file.mimetype });
      if (!uploadErr) {
        const { data: pub } = supabase.storage.from('surat-keluar').getPublicUrl(fileName);
        file_url = pub.publicUrl;
      }
    }
    const { data, error } = await supabase.from('surat_keluar').insert([{
      nomor_surat: body.nomor_surat?.trim(),
      tanggal_surat: body.tanggal_surat,
      tujuan: body.tujuan?.trim(),
      perihal: body.perihal?.trim(),
      keterangan: body.keterangan?.trim() || null,
      file_url,
      created_by: req.user.id,
      created_at: new Date().toISOString(),
    }]).select().single();
    if (error) throw error;
    return res.status(201).json({ message: 'Surat keluar berhasil ditambahkan.', data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal menambah surat keluar.' });
  }
});

router.put('/:id', requireAdmin, upload.single('file_surat'), async (req, res) => {
  try {
    const body = req.body;
    let file_url = body.file_url_existing || undefined;
    if (req.file) {
      const ext = req.file.originalname.split('.').pop();
      const fileName = `sk_${Date.now()}.${ext}`;
      const { error: uploadErr } = await supabase.storage.from('surat-keluar').upload(fileName, req.file.buffer, { contentType: req.file.mimetype });
      if (!uploadErr) {
        const { data: pub } = supabase.storage.from('surat-keluar').getPublicUrl(fileName);
        file_url = pub.publicUrl;
      }
    }
    const updateData = {
      nomor_surat: body.nomor_surat?.trim(),
      tanggal_surat: body.tanggal_surat,
      tujuan: body.tujuan?.trim(),
      perihal: body.perihal?.trim(),
      keterangan: body.keterangan?.trim() || null,
      updated_at: new Date().toISOString(),
    };
    if (file_url !== undefined) updateData.file_url = file_url;
    const { data, error } = await supabase.from('surat_keluar').update(updateData).eq('id', req.params.id).select().single();
    if (error) throw error;
    return res.json({ message: 'Surat keluar berhasil diperbarui.', data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal memperbarui surat keluar.' });
  }
});

router.delete('/:id', requireAdmin, async (req, res) => {
  try {
    const { error } = await supabase.from('surat_keluar').delete().eq('id', req.params.id);
    if (error) throw error;
    return res.json({ message: 'Surat keluar berhasil dihapus.' });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal menghapus surat keluar.' });
  }
});

module.exports = router;
