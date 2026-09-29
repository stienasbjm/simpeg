// server/server.js — Entry point aplikasi backend SIMPEG
require('dotenv').config();
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');

const authRoutes      = require('./src/routes/auth');
const pegawaiRoutes   = require('./src/routes/pegawai');
const suratMasukRoutes = require('./src/routes/suratMasuk');
const suratKeluarRoutes = require('./src/routes/suratKeluar');
const skRoutes        = require('./src/routes/sk');
const absensiRoutes   = require('./src/routes/absensi');
const keuanganRoutes  = require('./src/routes/keuangan');
const akunRoutes      = require('./src/routes/akun');
const dashboardRoutes = require('./src/routes/dashboard');

const app = express();
const PORT = process.env.PORT || 5000;

// ─── Security Middleware ──────────────────────────────────────────────────────
app.use(helmet());

// CORS: Izinkan request dari frontend
app.use(cors({
  origin: [
    process.env.CLIENT_URL || 'https://stienasbjm.github.io/simpeg/',
    'http://localhost:3000',
    /\.onrender\.com$/,   // Render.com domains
    /\.vercel\.app$/,     // Vercel domains
  ],
  credentials: true,
  methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization'],
}));

// Rate limiting — anti brute force
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 menit
  max: 200,
  message: { message: 'Terlalu banyak permintaan. Coba lagi dalam beberapa menit.' },
  standardHeaders: true,
  legacyHeaders: false,
});

const authLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 20, // Lebih ketat untuk login
  message: { message: 'Terlalu banyak percobaan login. Coba lagi dalam 15 menit.' },
});

app.use(limiter);

// ─── Body Parser ──────────────────────────────────────────────────────────────
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// ─── Health Check ────────────────────────────────────────────────────────────
app.get('/health', (req, res) => {
  res.json({
    status: 'OK',
    app: 'SIMPEG API v2.0',
    timestamp: new Date().toISOString(),
    environment: process.env.NODE_ENV || 'development',
  });
});

// ─── API Routes ───────────────────────────────────────────────────────────────
app.use('/api/auth',        authLimiter, authRoutes);
app.use('/api/dashboard',   dashboardRoutes);
app.use('/api/pegawai',     pegawaiRoutes);
app.use('/api/surat-masuk', suratMasukRoutes);
app.use('/api/surat-keluar', suratKeluarRoutes);
app.use('/api/sk',          skRoutes);
app.use('/api/absensi',     absensiRoutes);
app.use('/api/keuangan',    keuanganRoutes);
app.use('/api/akun',        akunRoutes);

// ─── 404 Handler ─────────────────────────────────────────────────────────────
app.use('*', (req, res) => {
  res.status(404).json({ message: `Route ${req.method} ${req.originalUrl} tidak ditemukan.` });
});

// ─── Global Error Handler ────────────────────────────────────────────────────
app.use((err, req, res, next) => {
  console.error('❌ Unhandled Error:', err);
  if (err.code === 'LIMIT_FILE_SIZE') {
    return res.status(413).json({ message: 'File terlalu besar. Maksimal 10MB.' });
  }
  res.status(500).json({ message: 'Terjadi kesalahan pada server.' });
});

// ─── Start Server ────────────────────────────────────────────────────────────
app.listen(PORT, () => {
  console.log(`\n🚀 SIMPEG API Server berjalan di http://localhost:${PORT}`);
  console.log(`📊 Environment: ${process.env.NODE_ENV || 'development'}`);
  console.log(`🗄️  Supabase URL: ${process.env.SUPABASE_URL || '(tidak dikonfigurasi)'}\n`);
});

module.exports = app;
