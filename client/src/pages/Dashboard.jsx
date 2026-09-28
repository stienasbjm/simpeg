// client/src/pages/Dashboard.jsx
import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  Users,
  Inbox,
  Send,
  FileCheck,
  AlertTriangle,
  Wallet,
  ArrowUpRight,
  ArrowDownRight,
  TrendingUp,
  Clock,
} from 'lucide-react';
import api from '../api/client';
import { useAuth } from '../contexts/AuthContext';

export default function Dashboard() {
  const { user } = useAuth();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchDashboard = async () => {
      try {
        const res = await api.get('/dashboard');
        setData(res.data);
      } catch (err) {
        console.error('Failed to load dashboard data:', err);
      } finally {
        setLoading(false);
      }
    };
    fetchDashboard();
  }, []);

  const stats = data?.stats || {
    total_pegawai: 0,
    total_surat_masuk: 0,
    total_surat_keluar: 0,
    total_sk: 0,
  };

  const kasSummary = data?.kas_summary;
  const pangkatAlerts = data?.pangkat_alerts || [];

  return (
    <div>
      {/* Welcome Header */}
      <div style={{
        marginBottom: '28px',
        display: 'flex',
        flexWrap: 'wrap',
        alignItems: 'center',
        justifyContent: 'space-between',
        gap: '16px',
      }}>
        <div>
          <h1 style={{ fontSize: '1.75rem', fontWeight: 800, letterSpacing: '-0.02em', color: 'var(--text-main)' }}>
            Selamat Datang, {user?.nama_lengkap || user?.username}! 👋
          </h1>
          <p style={{ color: 'var(--text-muted)', marginTop: '4px', fontSize: '0.9375rem' }}>
            Portal Sistem Informasi Kepegawaian & Arsip Terpadu
          </p>
        </div>
        <div style={{
          display: 'flex',
          alignItems: 'center',
          gap: '8px',
          background: 'var(--bg-surface)',
          padding: '8px 16px',
          borderRadius: 'var(--radius-full)',
          border: '1px solid var(--border)',
          fontSize: '0.8125rem',
          color: 'var(--text-muted)',
          fontWeight: 600,
        }}>
          <Clock size={16} color="var(--primary)" />
          {new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
        </div>
      </div>

      {/* Primary Statistics Grid */}
      <div className="grid-cols-4" style={{ marginBottom: '28px' }}>
        {/* Card Pegawai */}
        <div className="card" style={{ display: 'flex', alignItems: 'center', gap: '18px' }}>
          <div style={{
            width: '52px',
            height: '52px',
            borderRadius: '14px',
            background: 'var(--primary-light)',
            color: 'var(--primary)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
          }}>
            <Users size={26} />
          </div>
          <div>
            <div style={{ fontSize: '0.8125rem', fontWeight: 600, color: 'var(--text-muted)' }}>
              Total Pegawai
            </div>
            <div style={{ fontSize: '1.75rem', fontWeight: 800, color: 'var(--text-main)', marginTop: '2px' }}>
              {loading ? '...' : stats.total_pegawai}
            </div>
          </div>
        </div>

        {/* Card Surat Masuk */}
        <div className="card" style={{ display: 'flex', alignItems: 'center', gap: '18px' }}>
          <div style={{
            width: '52px',
            height: '52px',
            borderRadius: '14px',
            background: 'rgba(14, 165, 233, 0.15)',
            color: 'var(--secondary)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
          }}>
            <Inbox size={26} />
          </div>
          <div>
            <div style={{ fontSize: '0.8125rem', fontWeight: 600, color: 'var(--text-muted)' }}>
              Surat Masuk
            </div>
            <div style={{ fontSize: '1.75rem', fontWeight: 800, color: 'var(--text-main)', marginTop: '2px' }}>
              {loading ? '...' : stats.total_surat_masuk}
            </div>
          </div>
        </div>

        {/* Card Surat Keluar */}
        <div className="card" style={{ display: 'flex', alignItems: 'center', gap: '18px' }}>
          <div style={{
            width: '52px',
            height: '52px',
            borderRadius: '14px',
            background: 'var(--success-light)',
            color: 'var(--success)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
          }}>
            <Send size={26} />
          </div>
          <div>
            <div style={{ fontSize: '0.8125rem', fontWeight: 600, color: 'var(--text-muted)' }}>
              Surat Keluar
            </div>
            <div style={{ fontSize: '1.75rem', fontWeight: 800, color: 'var(--text-main)', marginTop: '2px' }}>
              {loading ? '...' : stats.total_surat_keluar}
            </div>
          </div>
        </div>

        {/* Card Surat Keputusan (SK) */}
        <div className="card" style={{ display: 'flex', alignItems: 'center', gap: '18px' }}>
          <div style={{
            width: '52px',
            height: '52px',
            borderRadius: '14px',
            background: 'rgba(139, 92, 246, 0.15)',
            color: 'var(--purple)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
          }}>
            <FileCheck size={26} />
          </div>
          <div>
            <div style={{ fontSize: '0.8125rem', fontWeight: 600, color: 'var(--text-muted)' }}>
              Surat Keputusan (SK)
            </div>
            <div style={{ fontSize: '1.75rem', fontWeight: 800, color: 'var(--text-main)', marginTop: '2px' }}>
              {loading ? '...' : stats.total_sk}
            </div>
          </div>
        </div>
      </div>

      {/* Row 2: Keuangan Summary & Kenaikan Pangkat Alert */}
      <div className="grid-cols-2" style={{ marginBottom: '28px' }}>
        {/* Kenaikan Pangkat Notice */}
        <div className="card">
          <div style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            marginBottom: '16px',
          }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
              <div style={{
                width: '32px',
                height: '32px',
                borderRadius: '8px',
                background: 'var(--warning-light)',
                color: 'var(--warning)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
              }}>
                <AlertTriangle size={18} />
              </div>
              <h2 style={{ fontSize: '1rem', fontWeight: 700 }}>
                Pengingat Kenaikan Pangkat (TMT &gt; 2 Thn)
              </h2>
            </div>
            <span className="badge badge-warning">
              {pangkatAlerts.length} Pegawai
            </span>
          </div>

          {pangkatAlerts.length === 0 ? (
            <p style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>
              Tidak ada pegawai yang masa pangkatnya mendekati atau melewati 2 tahun.
            </p>
          ) : (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
              {pangkatAlerts.map((peg) => (
                <div
                  key={peg.id}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    padding: '10px 14px',
                    borderRadius: 'var(--radius-md)',
                    background: 'var(--bg-subtle)',
                    border: '1px solid var(--border)',
                  }}
                >
                  <div>
                    <div style={{ fontWeight: 600, fontSize: '0.875rem' }}>{peg.nama}</div>
                    <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>
                      NIP: {peg.nip || '-'} • Gol: {peg.pangkat_golongan || '-'}
                    </div>
                  </div>
                  <span className="badge badge-warning">
                    {Math.floor(peg.masa_bulan / 12)} Thn {peg.masa_bulan % 12} Bln
                  </span>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Keuangan Card */}
        <div className="card">
          <div style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            marginBottom: '16px',
          }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
              <div style={{
                width: '32px',
                height: '32px',
                borderRadius: '8px',
                background: 'var(--success-light)',
                color: 'var(--success)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
              }}>
                <Wallet size={18} />
              </div>
              <h2 style={{ fontSize: '1rem', fontWeight: 700 }}>
                Ringkasan Saldo Kas
              </h2>
            </div>
            <Link to="/kas" className="btn btn-secondary btn-sm" style={{ textDecoration: 'none' }}>
              Buka Kas →
            </Link>
          </div>

          <div style={{
            padding: '20px',
            borderRadius: 'var(--radius-md)',
            background: 'linear-gradient(135deg, var(--primary) 0%, #312e81 100%)',
            color: '#ffffff',
            marginBottom: '16px',
          }}>
            <div style={{ fontSize: '0.8125rem', opacity: 0.85 }}>Total Saldo Gabungan</div>
            <div style={{ fontSize: '2rem', fontWeight: 800, marginTop: '4px' }}>
              Rp {(kasSummary?.total_kas || 0).toLocaleString('id-ID')}
            </div>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
            <div style={{
              padding: '12px',
              borderRadius: 'var(--radius-md)',
              background: 'var(--bg-subtle)',
              border: '1px solid var(--border)',
            }}>
              <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>Kas Kecil</div>
              <div style={{ fontSize: '1.125rem', fontWeight: 700, marginTop: '2px' }}>
                Rp {(kasSummary?.kas_kecil_total || 0).toLocaleString('id-ID')}
              </div>
            </div>
            <div style={{
              padding: '12px',
              borderRadius: 'var(--radius-md)',
              background: 'var(--bg-subtle)',
              border: '1px solid var(--border)',
            }}>
              <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>Kas Besar</div>
              <div style={{ fontSize: '1.125rem', fontWeight: 700, marginTop: '2px' }}>
                Rp {(kasSummary?.kas_besar_total || 0).toLocaleString('id-ID')}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
