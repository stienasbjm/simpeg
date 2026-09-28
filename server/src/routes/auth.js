// server/src/routes/auth.js
const express = require('express');
const router = express.Router();
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { supabase } = require('../config/supabase');
const { verifyToken } = require('../middleware/auth');

/**
 * POST /api/auth/login
 * Login dengan username + password
 */
router.post('/login', async (req, res) => {
  try {
    const { username, password } = req.body;

    if (!username || !password) {
      return res.status(400).json({ message: 'Username dan password wajib diisi.' });
    }

    // Cari user di tabel users
    const { data: user, error } = await supabase
      .from('users')
      .select('id, username, password, nama_lengkap, role, pegawai_id, is_active')
      .eq('username', username.trim())
      .single();

    if (error || !user) {
      return res.status(401).json({ message: 'Username atau password salah.' });
    }

    if (!user.is_active) {
      return res.status(403).json({ message: 'Akun ini dinonaktifkan. Hubungi administrator.' });
    }

    // Verifikasi password (bcrypt)
    const isMatch = await bcrypt.compare(password, user.password);
    if (!isMatch) {
      return res.status(401).json({ message: 'Username atau password salah.' });
    }

    // Buat JWT token
    const tokenPayload = {
      id: user.id,
      username: user.username,
      nama_lengkap: user.nama_lengkap,
      role: user.role,
      pegawai_id: user.pegawai_id,
    };

    const token = jwt.sign(tokenPayload, process.env.JWT_SECRET, {
      expiresIn: process.env.JWT_EXPIRES_IN || '8h',
    });

    return res.json({
      message: 'Login berhasil.',
      token,
      user: {
        id: user.id,
        username: user.username,
        nama_lengkap: user.nama_lengkap,
        role: user.role,
        pegawai_id: user.pegawai_id,
      },
    });
  } catch (err) {
    console.error('Login error:', err);
    return res.status(500).json({ message: 'Terjadi kesalahan server.' });
  }
});

/**
 * GET /api/auth/me
 * Ambil info user yang sedang login
 */
router.get('/me', verifyToken, async (req, res) => {
  try {
    const { data: user, error } = await supabase
      .from('users')
      .select('id, username, nama_lengkap, role, pegawai_id, created_at')
      .eq('id', req.user.id)
      .single();

    if (error || !user) {
      return res.status(404).json({ message: 'User tidak ditemukan.' });
    }

    return res.json({ user });
  } catch (err) {
    console.error('Get me error:', err);
    return res.status(500).json({ message: 'Terjadi kesalahan server.' });
  }
});

/**
 * POST /api/auth/logout
 * Logout (client-side: hapus token)
 */
router.post('/logout', verifyToken, (req, res) => {
  // JWT bersifat stateless, logout dilakukan di client dengan menghapus token
  return res.json({ message: 'Logout berhasil.' });
});

/**
 * PUT /api/auth/change-password
 * Ganti password user yang sedang login
 */
router.put('/change-password', verifyToken, async (req, res) => {
  try {
    const { password_lama, password_baru, konfirmasi_password } = req.body;

    if (!password_lama || !password_baru || !konfirmasi_password) {
      return res.status(400).json({ message: 'Semua field password wajib diisi.' });
    }

    if (password_baru !== konfirmasi_password) {
      return res.status(400).json({ message: 'Konfirmasi password tidak cocok.' });
    }

    if (password_baru.length < 6) {
      return res.status(400).json({ message: 'Password baru minimal 6 karakter.' });
    }

    // Ambil password lama dari DB
    const { data: user, error } = await supabase
      .from('users')
      .select('password')
      .eq('id', req.user.id)
      .single();

    if (error || !user) {
      return res.status(404).json({ message: 'User tidak ditemukan.' });
    }

    const isMatch = await bcrypt.compare(password_lama, user.password);
    if (!isMatch) {
      return res.status(401).json({ message: 'Password lama tidak benar.' });
    }

    const hashedNew = await bcrypt.hash(password_baru, 12);

    const { error: updateError } = await supabase
      .from('users')
      .update({ password: hashedNew, updated_at: new Date().toISOString() })
      .eq('id', req.user.id);

    if (updateError) throw updateError;

    return res.json({ message: 'Password berhasil diubah.' });
  } catch (err) {
    console.error('Change password error:', err);
    return res.status(500).json({ message: 'Terjadi kesalahan server.' });
  }
});

module.exports = router;
