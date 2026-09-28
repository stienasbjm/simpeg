// server/src/middleware/auth.js
const jwt = require('jsonwebtoken');

/**
 * Middleware: Verifikasi JWT token dari header Authorization
 * Format: Authorization: Bearer <token>
 */
function verifyToken(req, res, next) {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1]; // Bearer TOKEN

  if (!token) {
    return res.status(401).json({ message: 'Token tidak ditemukan. Silakan login.' });
  }

  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    req.user = decoded; // { id, username, nama_lengkap, role, pegawai_id }
    next();
  } catch (err) {
    if (err.name === 'TokenExpiredError') {
      return res.status(401).json({ message: 'Sesi telah berakhir. Silakan login kembali.' });
    }
    return res.status(403).json({ message: 'Token tidak valid.' });
  }
}

/**
 * Middleware: Cek apakah user adalah admin atau developer
 */
function requireAdmin(req, res, next) {
  if (!req.user) return res.status(401).json({ message: 'Tidak terautentikasi.' });
  const { role } = req.user;
  if (role === 'admin' || role === 'developer') {
    return next();
  }
  return res.status(403).json({ message: 'Akses ditolak. Diperlukan hak Admin.' });
}

/**
 * Middleware: Cek apakah user adalah bendahara atau developer
 */
function requireBendahara(req, res, next) {
  if (!req.user) return res.status(401).json({ message: 'Tidak terautentikasi.' });
  const { role } = req.user;
  if (role === 'bendahara' || role === 'developer') {
    return next();
  }
  return res.status(403).json({ message: 'Akses ditolak. Diperlukan hak Bendahara.' });
}

/**
 * Middleware: Cek apakah user adalah developer
 */
function requireDeveloper(req, res, next) {
  if (!req.user) return res.status(401).json({ message: 'Tidak terautentikasi.' });
  if (req.user.role === 'developer') {
    return next();
  }
  return res.status(403).json({ message: 'Akses ditolak. Diperlukan hak Developer.' });
}

/**
 * Middleware: Cek apakah user sudah login (role apapun)
 */
function requireLogin(req, res, next) {
  if (!req.user) return res.status(401).json({ message: 'Tidak terautentikasi.' });
  return next();
}

module.exports = { verifyToken, requireAdmin, requireBendahara, requireDeveloper, requireLogin };
