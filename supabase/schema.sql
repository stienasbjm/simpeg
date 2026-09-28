-- =============================================================================
-- SIMPEG (Sistem Informasi Manajemen Pegawai) - Supabase Database Schema
-- Run this script in Supabase SQL Editor: https://supabase.com/dashboard/project/_/sql
-- =============================================================================

-- Enable extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- =============================================================================
-- 1. TABEL PEGAWAI
-- =============================================================================
CREATE TABLE IF NOT EXISTS pegawai (
    id BIGSERIAL PRIMARY KEY,
    nip VARCHAR(50) UNIQUE,
    nama VARCHAR(150) NOT NULL,
    tempat_lahir VARCHAR(100),
    tanggal_lahir DATE,
    jenis_kelamin VARCHAR(20),
    agama VARCHAR(30),
    status_perkawinan VARCHAR(30),
    alamat TEXT,
    no_hp VARCHAR(30),
    email VARCHAR(100),
    tanggal_masuk DATE,
    status_kepegawaian VARCHAR(50),
    kategori VARCHAR(50),
    jabatan VARCHAR(100),
    jabatan_fungsional VARCHAR(100),
    pangkat_golongan VARCHAR(50),
    tmt_pangkat DATE,
    unit_kerja VARCHAR(100),
    pendidikan_terakhir VARCHAR(50),
    universitas VARCHAR(150),
    jurusan VARCHAR(100),
    tahun_lulus INTEGER,
    foto_url TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- =============================================================================
-- 2. TABEL USERS (Authentication & Role Management)
-- =============================================================================
CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(150) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'pegawai', -- 'developer', 'admin', 'bendahara', 'pegawai'
    pegawai_id BIGINT REFERENCES pegawai(id) ON DELETE SET NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- =============================================================================
-- 3. TABEL PEGAWAI_DOKUMEN (Berkas Digital Pegawai)
-- =============================================================================
CREATE TABLE IF NOT EXISTS pegawai_dokumen (
    id BIGSERIAL PRIMARY KEY,
    pegawai_id BIGINT NOT NULL REFERENCES pegawai(id) ON DELETE CASCADE,
    jenis_dokumen VARCHAR(100) NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    file_url TEXT NOT NULL,
    keterangan TEXT,
    uploaded_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- =============================================================================
-- 4. TABEL SURAT_MASUK
-- =============================================================================
CREATE TABLE IF NOT EXISTS surat_masuk (
    id BIGSERIAL PRIMARY KEY,
    nomor_surat VARCHAR(100) NOT NULL,
    tanggal_surat DATE NOT NULL,
    tanggal_terima DATE,
    pengirim VARCHAR(150) NOT NULL,
    perihal TEXT NOT NULL,
    disposisi TEXT,
    keterangan TEXT,
    file_url TEXT,
    created_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- =============================================================================
-- 5. TABEL SURAT_KELUAR
-- =============================================================================
CREATE TABLE IF NOT EXISTS surat_keluar (
    id BIGSERIAL PRIMARY KEY,
    nomor_surat VARCHAR(100) NOT NULL,
    tanggal_surat DATE NOT NULL,
    tujuan VARCHAR(150) NOT NULL,
    perihal TEXT NOT NULL,
    keterangan TEXT,
    file_url TEXT,
    created_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- =============================================================================
-- 6. TABEL SURAT_KEPUTUSAN (SK)
-- =============================================================================
CREATE TABLE IF NOT EXISTS surat_keputusan (
    id BIGSERIAL PRIMARY KEY,
    nomor_sk VARCHAR(100) NOT NULL,
    tanggal_sk DATE NOT NULL,
    pegawai_id BIGINT REFERENCES pegawai(id) ON DELETE SET NULL,
    perihal TEXT NOT NULL,
    keterangan TEXT,
    file_url TEXT,
    created_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- =============================================================================
-- 7. TABEL ABSENSI
-- =============================================================================
CREATE TABLE IF NOT EXISTS absensi (
    id BIGSERIAL PRIMARY KEY,
    pegawai_id BIGINT NOT NULL REFERENCES pegawai(id) ON DELETE CASCADE,
    tanggal DATE NOT NULL,
    jam_masuk TIME,
    jam_keluar TIME,
    status VARCHAR(20) NOT NULL DEFAULT 'hadir', -- 'hadir', 'izin', 'sakit', 'alpha', 'cuti'
    keterangan TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(pegawai_id, tanggal)
);

-- =============================================================================
-- 8. TABEL KAS_TRANSAKSI (Kas Kecil & Kas Besar)
-- =============================================================================
CREATE TABLE IF NOT EXISTS kas_transaksi (
    id BIGSERIAL PRIMARY KEY,
    jenis_kas VARCHAR(20) NOT NULL, -- 'kas_kecil', 'kas_besar'
    tipe VARCHAR(20) NOT NULL,      -- 'pemasukan', 'pengeluaran'
    kategori VARCHAR(100) NOT NULL,
    jumlah NUMERIC(15, 2) NOT NULL DEFAULT 0,
    keterangan TEXT,
    tanggal DATE NOT NULL,
    created_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- =============================================================================
-- 9. TABEL GAJI
-- =============================================================================
CREATE TABLE IF NOT EXISTS gaji (
    id BIGSERIAL PRIMARY KEY,
    pegawai_id BIGINT NOT NULL REFERENCES pegawai(id) ON DELETE CASCADE,
    bulan INTEGER NOT NULL,
    tahun INTEGER NOT NULL,
    gaji_pokok NUMERIC(15, 2) DEFAULT 0,
    tunjangan_jabatan NUMERIC(15, 2) DEFAULT 0,
    tunjangan_keluarga NUMERIC(15, 2) DEFAULT 0,
    tunjangan_makan NUMERIC(15, 2) DEFAULT 0,
    tunjangan_transport NUMERIC(15, 2) DEFAULT 0,
    tunjangan_lain NUMERIC(15, 2) DEFAULT 0,
    total_tunjangan NUMERIC(15, 2) DEFAULT 0,
    potongan_bpjs NUMERIC(15, 2) DEFAULT 0,
    potongan_pajak NUMERIC(15, 2) DEFAULT 0,
    potongan_lain NUMERIC(15, 2) DEFAULT 0,
    total_potongan NUMERIC(15, 2) DEFAULT 0,
    total_gaji NUMERIC(15, 2) DEFAULT 0,
    status_pembayaran VARCHAR(20) DEFAULT 'draft', -- 'draft', 'dibayar'
    tanggal_bayar DATE,
    keterangan TEXT,
    created_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(pegawai_id, bulan, tahun)
);

-- =============================================================================
-- 10. RPC: GET_KAS_SUMMARY
-- =============================================================================
CREATE OR REPLACE FUNCTION get_kas_summary()
RETURNS TABLE (
  kas_kecil_masuk NUMERIC,
  kas_kecil_keluar NUMERIC,
  kas_kecil_saldo NUMERIC,
  kas_besar_masuk NUMERIC,
  kas_besar_keluar NUMERIC,
  kas_besar_saldo NUMERIC,
  total_saldo NUMERIC
) AS $$
BEGIN
  RETURN QUERY
  SELECT
    COALESCE(SUM(CASE WHEN jenis_kas = 'kas_kecil' AND tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) AS kas_kecil_masuk,
    COALESCE(SUM(CASE WHEN jenis_kas = 'kas_kecil' AND tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0) AS kas_kecil_keluar,
    COALESCE(SUM(CASE WHEN jenis_kas = 'kas_kecil' AND tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) -
    COALESCE(SUM(CASE WHEN jenis_kas = 'kas_kecil' AND tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0) AS kas_kecil_saldo,
    COALESCE(SUM(CASE WHEN jenis_kas = 'kas_besar' AND tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) AS kas_besar_masuk,
    COALESCE(SUM(CASE WHEN jenis_kas = 'kas_besar' AND tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0) AS kas_besar_keluar,
    COALESCE(SUM(CASE WHEN jenis_kas = 'kas_besar' AND tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) -
    COALESCE(SUM(CASE WHEN jenis_kas = 'kas_besar' AND tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0) AS kas_besar_saldo,
    (COALESCE(SUM(CASE WHEN tipe = 'pemasukan' THEN jumlah ELSE 0 END), 0) -
     COALESCE(SUM(CASE WHEN tipe = 'pengeluaran' THEN jumlah ELSE 0 END), 0)) AS total_saldo
  FROM kas_transaksi;
END;
$$ LANGUAGE plpgsql;

-- =============================================================================
-- 11. INDEXES UNTUK PERFORMA
-- =============================================================================
CREATE INDEX IF NOT EXISTS idx_pegawai_nip ON pegawai(nip);
CREATE INDEX IF NOT EXISTS idx_pegawai_nama ON pegawai(nama);
CREATE INDEX IF NOT EXISTS idx_surat_masuk_tgl ON surat_masuk(tanggal_surat);
CREATE INDEX IF NOT EXISTS idx_surat_keluar_tgl ON surat_keluar(tanggal_surat);
CREATE INDEX IF NOT EXISTS idx_sk_tgl ON surat_keputusan(tanggal_sk);
CREATE INDEX IF NOT EXISTS idx_absensi_pegawai_tgl ON absensi(pegawai_id, tanggal);
CREATE INDEX IF NOT EXISTS idx_kas_tanggal ON kas_transaksi(tanggal);
CREATE INDEX IF NOT EXISTS idx_gaji_periode ON gaji(bulan, tahun);

-- =============================================================================
-- 12. DEFAULT SEED USERS & DATA CONTOH
-- Password untuk semua akun default: admin123
-- Hash bcrypt (10 rounds): $2a$10$vI8aWBnW3fID.ZQ4/zo1G.q1lR5e0u8G.l51T.tU2B8j3QW0F1QfG
-- Password 'admin': $2a$10$wT8e1W4z4VjU4Psq7rFz.e/P8v1b5/qjUe5i5Wc8mC9g1D8G0bIqm
-- =============================================================================

-- Seed Pegawai Awal
INSERT INTO pegawai (nip, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, jabatan, pangkat_golongan, tmt_pangkat, unit_kerja, status_kepegawaian)
VALUES 
('198501012010011001', 'Dr. Ir. Ahmad Dahlan, M.Kom', 'Jakarta', '1985-01-01', 'Laki-laki', 'Islam', 'Kepala Sub Bagian Kepegawaian', 'Pembina / IV/a', '2022-04-01', 'Bagian Tata Usaha', 'PNS'),
('199002152015022002', 'Siti Rahmawati, S.E.', 'Bandung', '1990-02-15', 'Perempuan', 'Islam', 'Bendahara Pengeluaran', 'Penata Muda / III/a', '2023-10-01', 'Bagian Keuangan', 'PNS'),
('199208202020121003', 'Budi Santoso, S.Kom', 'Surabaya', '1992-08-20', 'Laki-laki', 'Islam', 'Pranata Komputer', 'Penata Muda Tk. I / III/b', '2021-04-01', 'Pusat TI', 'PPPK')
ON CONFLICT (nip) DO NOTHING;

-- Seed Users Default
-- admin -> password: admin
-- bendahara -> password: admin
-- developer -> password: admin
INSERT INTO users (username, password, nama_lengkap, role, is_active)
VALUES
('admin', '$2a$10$U2rJzFzY5GZz8j8qJ.OqUOHyEfxkRvyNf0mY0/uG93I0iA9m1bY52', 'Administrator Utama', 'admin', TRUE),
('developer', '$2a$10$U2rJzFzY5GZz8j8qJ.OqUOHyEfxkRvyNf0mY0/uG93I0iA9m1bY52', 'Developer System', 'developer', TRUE),
('bendahara', '$2a$10$U2rJzFzY5GZz8j8qJ.OqUOHyEfxkRvyNf0mY0/uG93I0iA9m1bY52', 'Siti Rahmawati (Bendahara)', 'bendahara', TRUE)
ON CONFLICT (username) DO NOTHING;

-- Storage Buckets Configuration Note:
-- Create 4 public/private buckets in Supabase Storage dashboard:
-- 1. dokumen-pegawai (Public / Private)
-- 2. surat-masuk (Public / Private)
-- 3. surat-keluar (Public / Private)
-- 4. surat-keputusan (Public / Private)
