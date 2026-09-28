// server/src/routes/sk.js — Surat Keputusan
const express = require('express');
const router = express.Router();
const { supabase } = require('../config/supabase');
const { verifyToken, requireAdmin } = require('../middleware/auth');
const upload = require('../middleware/upload');

router.use(verifyToken);

router.get('/', async (req, res) => {
  try {
    const { search, tahun, limit = 100, offset = 0 } = req.query;
    let query = supabase.from('surat_keputusan').select('*, pegawai(nama, nip)', { count: 'exact' }).order('tanggal_sk', { ascending: false });
    if (search) query = query.or(`nomor_sk.ilike.%${search}%,perihal.ilike.%${search}%`);
    if (tahun) query = query.gte('tanggal_sk', `${tahun}-01-01`).lte('tanggal_sk', `${tahun}-12-31`);
    query = query.range(parseInt(offset), parseInt(offset) + parseInt(limit) - 1);
    const { data, error, count } = await query;
    if (error) throw error;
    return res.json({ data, total: count });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal mengambil data SK.' });
  }
});

router.get('/:id', async (req, res) => {
  try {
    const { data, error } = await supabase.from('surat_keputusan').select('*, pegawai(nama, nip)').eq('id', req.params.id).single();
    if (error || !data) return res.status(404).json({ message: 'SK tidak ditemukan.' });
    return res.json({ data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal mengambil detail SK.' });
  }
});

router.post('/', requireAdmin, upload.single('file_sk'), async (req, res) => {
  try {
    const body = req.body;
    let file_url = null;
    if (req.file) {
      const ext = req.file.originalname.split('.').pop();
      const fileName = `sk_${Date.now()}.${ext}`;
      const { error: uploadErr } = await supabase.storage.from('surat-keputusan').upload(fileName, req.file.buffer, { contentType: req.file.mimetype });
      if (!uploadErr) {
        const { data: pub } = supabase.storage.from('surat-keputusan').getPublicUrl(fileName);
        file_url = pub.publicUrl;
      }
    }
    const { data, error } = await supabase.from('surat_keputusan').insert([{
      nomor_sk: body.nomor_sk?.trim(),
      tanggal_sk: body.tanggal_sk,
      pegawai_id: body.pegawai_id || null,
      perihal: body.perihal?.trim(),
      keterangan: body.keterangan?.trim() || null,
      file_url,
      created_by: req.user.id,
      created_at: new Date().toISOString(),
    }]).select('*, pegawai(nama, nip)').single();
    if (error) throw error;
    return res.status(201).json({ message: 'SK berhasil ditambahkan.', data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal menambah SK.' });
  }
});

router.put('/:id', requireAdmin, upload.single('file_sk'), async (req, res) => {
  try {
    const body = req.body;
    let file_url = body.file_url_existing || undefined;
    if (req.file) {
      const ext = req.file.originalname.split('.').pop();
      const fileName = `sk_${Date.now()}.${ext}`;
      const { error: uploadErr } = await supabase.storage.from('surat-keputusan').upload(fileName, req.file.buffer, { contentType: req.file.mimetype });
      if (!uploadErr) {
        const { data: pub } = supabase.storage.from('surat-keputusan').getPublicUrl(fileName);
        file_url = pub.publicUrl;
      }
    }
    const updateData = {
      nomor_sk: body.nomor_sk?.trim(),
      tanggal_sk: body.tanggal_sk,
      pegawai_id: body.pegawai_id || null,
      perihal: body.perihal?.trim(),
      keterangan: body.keterangan?.trim() || null,
      updated_at: new Date().toISOString(),
    };
    if (file_url !== undefined) updateData.file_url = file_url;
    const { data, error } = await supabase.from('surat_keputusan').update(updateData).eq('id', req.params.id).select('*, pegawai(nama, nip)').single();
    if (error) throw error;
    return res.json({ message: 'SK berhasil diperbarui.', data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal memperbarui SK.' });
  }
});

router.delete('/:id', requireAdmin, async (req, res) => {
  try {
    const { error } = await supabase.from('surat_keputusan').delete().eq('id', req.params.id);
    if (error) throw error;
    return res.json({ message: 'SK berhasil dihapus.' });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal menghapus SK.' });
  }
});

module.exports = router;
