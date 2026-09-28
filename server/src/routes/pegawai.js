// server/src/routes/pegawai.js
const express = require('express');
const router = express.Router();
const { supabase } = require('../config/supabase');
const { verifyToken, requireAdmin } = require('../middleware/auth');
const upload = require('../middleware/upload');

// Semua route pegawai memerlukan login
router.use(verifyToken);

/**
 * GET /api/pegawai
 * Ambil semua data pegawai (dengan filter & search)
 */
router.get('/', async (req, res) => {
  try {
    const { search, status, kategori, limit = 100, offset = 0 } = req.query;

    let query = supabase
      .from('pegawai')
      .select(`
        id, nip, nama, tempat_lahir, tanggal_lahir, jenis_kelamin,
        agama, status_perkawinan, alamat, no_hp, email,
        tanggal_masuk, status_kepegawaian, jabatan, jabatan_fungsional,
        pangkat_golongan, tmt_pangkat, unit_kerja, pendidikan_terakhir,
        foto_url, created_at, updated_at
      `, { count: 'exact' })
      .order('nama', { ascending: true });

    if (search) {
      query = query.or(`nama.ilike.%${search}%,nip.ilike.%${search}%,jabatan.ilike.%${search}%`);
    }
    if (status) {
      query = query.eq('status_kepegawaian', status);
    }
    if (kategori) {
      query = query.eq('kategori', kategori);
    }

    query = query.range(parseInt(offset), parseInt(offset) + parseInt(limit) - 1);

    const { data, error, count } = await query;
    if (error) throw error;

    return res.json({ data, total: count });
  } catch (err) {
    console.error('Get pegawai error:', err);
    return res.status(500).json({ message: 'Gagal mengambil data pegawai.' });
  }
});

/**
 * GET /api/pegawai/:id
 * Ambil detail satu pegawai lengkap
 */
router.get('/:id', async (req, res) => {
  try {
    const { data, error } = await supabase
      .from('pegawai')
      .select('*')
      .eq('id', req.params.id)
      .single();

    if (error || !data) {
      return res.status(404).json({ message: 'Data pegawai tidak ditemukan.' });
    }

    // Ambil dokumen terkait
    const { data: dokumen } = await supabase
      .from('pegawai_dokumen')
      .select('*')
      .eq('pegawai_id', req.params.id)
      .order('created_at', { ascending: false });

    return res.json({ data, dokumen: dokumen || [] });
  } catch (err) {
    console.error('Get pegawai detail error:', err);
    return res.status(500).json({ message: 'Gagal mengambil detail pegawai.' });
  }
});

/**
 * POST /api/pegawai
 * Tambah pegawai baru (Admin only)
 */
router.post('/', requireAdmin, upload.single('foto'), async (req, res) => {
  try {
    const body = req.body;
    let foto_url = null;

    // Upload foto ke Supabase Storage jika ada
    if (req.file) {
      const ext = req.file.originalname.split('.').pop();
      const fileName = `foto_${Date.now()}.${ext}`;
      const { data: uploadData, error: uploadErr } = await supabase.storage
        .from('dokumen-pegawai')
        .upload(`foto/${fileName}`, req.file.buffer, {
          contentType: req.file.mimetype,
          upsert: false,
        });

      if (!uploadErr) {
        const { data: publicUrl } = supabase.storage
          .from('dokumen-pegawai')
          .getPublicUrl(`foto/${fileName}`);
        foto_url = publicUrl.publicUrl;
      }
    }

    const pegawaiData = {
      nip: body.nip?.trim() || null,
      nama: body.nama?.trim(),
      tempat_lahir: body.tempat_lahir?.trim() || null,
      tanggal_lahir: body.tanggal_lahir || null,
      jenis_kelamin: body.jenis_kelamin || null,
      agama: body.agama || null,
      status_perkawinan: body.status_perkawinan || null,
      alamat: body.alamat?.trim() || null,
      no_hp: body.no_hp?.trim() || null,
      email: body.email?.trim() || null,
      tanggal_masuk: body.tanggal_masuk || null,
      status_kepegawaian: body.status_kepegawaian || null,
      kategori: body.kategori || null,
      jabatan: body.jabatan?.trim() || null,
      jabatan_fungsional: body.jabatan_fungsional?.trim() || null,
      pangkat_golongan: body.pangkat_golongan || null,
      tmt_pangkat: body.tmt_pangkat || null,
      unit_kerja: body.unit_kerja?.trim() || null,
      pendidikan_terakhir: body.pendidikan_terakhir || null,
      universitas: body.universitas?.trim() || null,
      jurusan: body.jurusan?.trim() || null,
      tahun_lulus: body.tahun_lulus ? parseInt(body.tahun_lulus) : null,
      foto_url,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    };

    if (!pegawaiData.nama) {
      return res.status(400).json({ message: 'Nama pegawai wajib diisi.' });
    }

    const { data, error } = await supabase
      .from('pegawai')
      .insert([pegawaiData])
      .select()
      .single();

    if (error) throw error;

    return res.status(201).json({ message: 'Pegawai berhasil ditambahkan.', data });
  } catch (err) {
    console.error('Create pegawai error:', err);
    return res.status(500).json({ message: 'Gagal menambah pegawai.' });
  }
});

/**
 * PUT /api/pegawai/:id
 * Update data pegawai (Admin only)
 */
router.put('/:id', requireAdmin, upload.single('foto'), async (req, res) => {
  try {
    const body = req.body;
    let foto_url = body.foto_url_existing || undefined; // Pakai foto lama jika tidak ada upload baru

    // Upload foto baru jika ada
    if (req.file) {
      const ext = req.file.originalname.split('.').pop();
      const fileName = `foto_${Date.now()}.${ext}`;
      const { data: uploadData, error: uploadErr } = await supabase.storage
        .from('dokumen-pegawai')
        .upload(`foto/${fileName}`, req.file.buffer, {
          contentType: req.file.mimetype,
          upsert: false,
        });

      if (!uploadErr) {
        const { data: publicUrl } = supabase.storage
          .from('dokumen-pegawai')
          .getPublicUrl(`foto/${fileName}`);
        foto_url = publicUrl.publicUrl;
      }
    }

    const updateData = {
      nip: body.nip?.trim() || null,
      nama: body.nama?.trim(),
      tempat_lahir: body.tempat_lahir?.trim() || null,
      tanggal_lahir: body.tanggal_lahir || null,
      jenis_kelamin: body.jenis_kelamin || null,
      agama: body.agama || null,
      status_perkawinan: body.status_perkawinan || null,
      alamat: body.alamat?.trim() || null,
      no_hp: body.no_hp?.trim() || null,
      email: body.email?.trim() || null,
      tanggal_masuk: body.tanggal_masuk || null,
      status_kepegawaian: body.status_kepegawaian || null,
      kategori: body.kategori || null,
      jabatan: body.jabatan?.trim() || null,
      jabatan_fungsional: body.jabatan_fungsional?.trim() || null,
      pangkat_golongan: body.pangkat_golongan || null,
      tmt_pangkat: body.tmt_pangkat || null,
      unit_kerja: body.unit_kerja?.trim() || null,
      pendidikan_terakhir: body.pendidikan_terakhir || null,
      universitas: body.universitas?.trim() || null,
      jurusan: body.jurusan?.trim() || null,
      tahun_lulus: body.tahun_lulus ? parseInt(body.tahun_lulus) : null,
      updated_at: new Date().toISOString(),
    };

    if (foto_url !== undefined) {
      updateData.foto_url = foto_url;
    }

    if (!updateData.nama) {
      return res.status(400).json({ message: 'Nama pegawai wajib diisi.' });
    }

    const { data, error } = await supabase
      .from('pegawai')
      .update(updateData)
      .eq('id', req.params.id)
      .select()
      .single();

    if (error) throw error;

    return res.json({ message: 'Data pegawai berhasil diperbarui.', data });
  } catch (err) {
    console.error('Update pegawai error:', err);
    return res.status(500).json({ message: 'Gagal memperbarui data pegawai.' });
  }
});

/**
 * DELETE /api/pegawai/:id
 * Hapus pegawai (Admin only)
 */
router.delete('/:id', requireAdmin, async (req, res) => {
  try {
    // Hapus dokumen terkait terlebih dahulu
    await supabase.from('pegawai_dokumen').delete().eq('pegawai_id', req.params.id);

    const { error } = await supabase
      .from('pegawai')
      .delete()
      .eq('id', req.params.id);

    if (error) throw error;

    return res.json({ message: 'Data pegawai berhasil dihapus.' });
  } catch (err) {
    console.error('Delete pegawai error:', err);
    return res.status(500).json({ message: 'Gagal menghapus data pegawai.' });
  }
});

/**
 * POST /api/pegawai/:id/dokumen
 * Upload dokumen untuk pegawai (ijazah, SK, dll.)
 */
router.post('/:id/dokumen', requireAdmin, upload.single('file'), async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ message: 'File wajib diunggah.' });
    }

    const { jenis_dokumen, keterangan } = req.body;
    const pegawaiId = req.params.id;
    const ext = req.file.originalname.split('.').pop();
    const fileName = `${jenis_dokumen}_${pegawaiId}_${Date.now()}.${ext}`;
    const storagePath = `dokumen/${pegawaiId}/${fileName}`;

    const { error: uploadErr } = await supabase.storage
      .from('dokumen-pegawai')
      .upload(storagePath, req.file.buffer, {
        contentType: req.file.mimetype,
        upsert: false,
      });

    if (uploadErr) throw uploadErr;

    const { data: publicUrl } = supabase.storage
      .from('dokumen-pegawai')
      .getPublicUrl(storagePath);

    const { data, error } = await supabase
      .from('pegawai_dokumen')
      .insert([{
        pegawai_id: pegawaiId,
        jenis_dokumen,
        nama_file: req.file.originalname,
        file_url: publicUrl.publicUrl,
        keterangan: keterangan || null,
        uploaded_by: req.user.id,
        created_at: new Date().toISOString(),
      }])
      .select()
      .single();

    if (error) throw error;

    return res.status(201).json({ message: 'Dokumen berhasil diunggah.', data });
  } catch (err) {
    console.error('Upload dokumen error:', err);
    return res.status(500).json({ message: 'Gagal mengunggah dokumen.' });
  }
});

/**
 * DELETE /api/pegawai/:id/dokumen/:dokumenId
 * Hapus dokumen pegawai
 */
router.delete('/:id/dokumen/:dokumenId', requireAdmin, async (req, res) => {
  try {
    const { data: dok } = await supabase
      .from('pegawai_dokumen')
      .select('file_url')
      .eq('id', req.params.dokumenId)
      .single();

    // Hapus dari storage jika ada
    if (dok?.file_url) {
      const path = dok.file_url.split('/storage/v1/object/public/dokumen-pegawai/')[1];
      if (path) {
        await supabase.storage.from('dokumen-pegawai').remove([path]);
      }
    }

    const { error } = await supabase
      .from('pegawai_dokumen')
      .delete()
      .eq('id', req.params.dokumenId);

    if (error) throw error;

    return res.json({ message: 'Dokumen berhasil dihapus.' });
  } catch (err) {
    console.error('Delete dokumen error:', err);
    return res.status(500).json({ message: 'Gagal menghapus dokumen.' });
  }
});

module.exports = router;
