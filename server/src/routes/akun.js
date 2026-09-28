// server/src/routes/akun.js — Manajemen User (Admin & Pegawai)
const express = require('express');
const router = express.Router();
const bcrypt = require('bcryptjs');
const { supabase } = require('../config/supabase');
const { verifyToken, requireAdmin, requireDeveloper } = require('../middleware/auth');

router.use(verifyToken);

/** GET /api/akun — List semua user (Admin only) */
router.get('/', requireAdmin, async (req, res) => {
  try {
    const { data, error } = await supabase
      .from('users')
      .select('id, username, nama_lengkap, role, is_active, pegawai_id, created_at, pegawai(nama, nip)')
      .order('created_at', { ascending: false });
    if (error) throw error;
    return res.json({ data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal mengambil data akun.' });
  }
});

/** GET /api/akun/:id */
router.get('/:id', requireAdmin, async (req, res) => {
  try {
    const { data, error } = await supabase
      .from('users')
      .select('id, username, nama_lengkap, role, is_active, pegawai_id, created_at, pegawai(nama, nip)')
      .eq('id', req.params.id)
      .single();
    if (error || !data) return res.status(404).json({ message: 'User tidak ditemukan.' });
    return res.json({ data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal mengambil data akun.' });
  }
});

/** POST /api/akun — Buat user baru */
router.post('/', requireAdmin, async (req, res) => {
  try {
    const { username, password, nama_lengkap, role, pegawai_id } = req.body;

    if (!username || !password || !nama_lengkap || !role) {
      return res.status(400).json({ message: 'Username, password, nama lengkap, dan role wajib diisi.' });
    }
    if (password.length < 6) {
      return res.status(400).json({ message: 'Password minimal 6 karakter.' });
    }

    const validRoles = ['admin', 'bendahara', 'pegawai', 'developer'];
    if (!validRoles.includes(role)) {
      return res.status(400).json({ message: 'Role tidak valid.' });
    }

    // Cek duplikat username
    const { data: existing } = await supabase.from('users').select('id').eq('username', username.trim()).single();
    if (existing) return res.status(400).json({ message: 'Username sudah digunakan.' });

    const hashedPwd = await bcrypt.hash(password, 12);

    const { data, error } = await supabase.from('users').insert([{
      username: username.trim(),
      password: hashedPwd,
      nama_lengkap: nama_lengkap.trim(),
      role,
      is_active: true,
      pegawai_id: pegawai_id || null,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    }]).select('id, username, nama_lengkap, role, is_active, pegawai_id, created_at').single();

    if (error) throw error;
    return res.status(201).json({ message: 'Akun berhasil dibuat.', data });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Gagal membuat akun.' });
  }
});

/** PUT /api/akun/:id — Update data user */
router.put('/:id', requireAdmin, async (req, res) => {
  try {
    const { username, nama_lengkap, role, is_active, pegawai_id, password } = req.body;

    const updateData = {
      username: username?.trim(),
      nama_lengkap: nama_lengkap?.trim(),
      role,
      is_active,
      pegawai_id: pegawai_id || null,
      updated_at: new Date().toISOString(),
    };

    // Jika ada password baru, hash ulang
    if (password && password.trim().length >= 6) {
      updateData.password = await bcrypt.hash(password, 12);
    }

    const { data, error } = await supabase
      .from('users')
      .update(updateData)
      .eq('id', req.params.id)
      .select('id, username, nama_lengkap, role, is_active, pegawai_id, updated_at')
      .single();

    if (error) throw error;
    return res.json({ message: 'Akun berhasil diperbarui.', data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal memperbarui akun.' });
  }
});

/** DELETE /api/akun/:id */
router.delete('/:id', requireAdmin, async (req, res) => {
  try {
    // Tidak boleh hapus akun sendiri
    if (req.params.id === String(req.user.id)) {
      return res.status(400).json({ message: 'Tidak dapat menghapus akun yang sedang digunakan.' });
    }
    const { error } = await supabase.from('users').delete().eq('id', req.params.id);
    if (error) throw error;
    return res.json({ message: 'Akun berhasil dihapus.' });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal menghapus akun.' });
  }
});

/** PUT /api/akun/:id/toggle-active — Aktifkan/Nonaktifkan user */
router.put('/:id/toggle-active', requireAdmin, async (req, res) => {
  try {
    const { data: current } = await supabase.from('users').select('is_active').eq('id', req.params.id).single();
    const newStatus = !current?.is_active;

    const { data, error } = await supabase
      .from('users')
      .update({ is_active: newStatus, updated_at: new Date().toISOString() })
      .eq('id', req.params.id)
      .select('id, username, is_active')
      .single();

    if (error) throw error;
    return res.json({ message: `Akun berhasil ${newStatus ? 'diaktifkan' : 'dinonaktifkan'}.`, data });
  } catch (err) {
    return res.status(500).json({ message: 'Gagal mengubah status akun.' });
  }
});

module.exports = router;
