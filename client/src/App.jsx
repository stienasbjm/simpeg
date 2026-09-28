// client/src/App.jsx
import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './contexts/AuthContext';

import Layout from './components/Layout/Layout';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import PegawaiList from './pages/Pegawai/PegawaiList';
import SuratMasukList from './pages/SuratMasuk/SuratMasukList';
import SuratKeluarList from './pages/SuratKeluar/SuratKeluarList';
import SKList from './pages/SK/SKList';
import AbsensiView from './pages/Absensi/AbsensiView';
import KasView from './pages/Kas/KasView';
import GajiView from './pages/Gaji/GajiView';
import AkunList from './pages/Akun/AkunList';

function ProtectedRoute({ children }) {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return (
      <div style={{
        height: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: 'var(--bg-main)',
        color: 'var(--text-muted)',
        fontWeight: 600,
      }}>
        Memuat aplikasi SIMPEG...
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return children;
}

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />

      <Route
        path="/"
        element={
          <ProtectedRoute>
            <Layout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Dashboard />} />
        <Route path="pegawai" element={<PegawaiList />} />
        <Route path="surat-masuk" element={<SuratMasukList />} />
        <Route path="surat-keluar" element={<SuratKeluarList />} />
        <Route path="sk" element={<SKList />} />
        <Route path="absensi" element={<AbsensiView />} />
        <Route path="kas" element={<KasView />} />
        <Route path="gaji" element={<GajiView />} />
        <Route path="akun" element={<AkunList />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
