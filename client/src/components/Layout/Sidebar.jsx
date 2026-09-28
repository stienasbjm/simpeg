// client/src/components/Layout/Sidebar.jsx
import React from 'react';
import { NavLink } from 'react-router-dom';
import {
  LayoutDashboard,
  Users,
  Inbox,
  Send,
  FileCheck,
  CalendarCheck,
  Wallet,
  CreditCard,
  UserCog,
  LogOut,
  Building2,
} from 'lucide-react';
import { useAuth } from '../../contexts/AuthContext';

export default function Sidebar({ isOpen, onClose }) {
  const { user, logout } = useAuth();
  const role = user?.role || 'pegawai';

  const menuItems = [
    { label: 'Dashboard', path: '/', icon: LayoutDashboard, roles: ['admin', 'developer', 'bendahara', 'pegawai'] },
    { label: 'Data Pegawai', path: '/pegawai', icon: Users, roles: ['admin', 'developer', 'pegawai'] },
    { label: 'Surat Masuk', path: '/surat-masuk', icon: Inbox, roles: ['admin', 'developer', 'pegawai'] },
    { label: 'Surat Keluar', path: '/surat-keluar', icon: Send, roles: ['admin', 'developer', 'pegawai'] },
    { label: 'Surat Keputusan (SK)', path: '/sk', icon: FileCheck, roles: ['admin', 'developer', 'pegawai'] },
    { label: 'Absensi', path: '/absensi', icon: CalendarCheck, roles: ['admin', 'developer', 'pegawai', 'bendahara'] },
    { label: 'Buku Kas', path: '/kas', icon: Wallet, roles: ['admin', 'developer', 'bendahara'] },
    { label: 'Penggajian', path: '/gaji', icon: CreditCard, roles: ['admin', 'developer', 'bendahara'] },
    { label: 'Manajemen Akun', path: '/akun', icon: UserCog, roles: ['admin', 'developer'] },
  ];

  const allowedItems = menuItems.filter((item) => item.roles.includes(role));

  return (
    <aside className={`sidebar ${isOpen ? 'open' : ''}`}>
      {/* Brand Header */}
      <div style={{
        height: 'var(--topbar-height)',
        display: 'flex',
        alignItems: 'center',
        gap: '12px',
        padding: '0 24px',
        borderBottom: '1px solid var(--border)',
      }}>
        <div style={{
          width: '38px',
          height: '38px',
          borderRadius: '10px',
          background: 'linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          color: '#ffffff',
          boxShadow: '0 4px 10px var(--primary-glow)',
        }}>
          <Building2 size={22} />
        </div>
        <div>
          <div style={{ fontWeight: 800, fontSize: '1.125rem', letterSpacing: '-0.02em', color: 'var(--text-main)' }}>
            SIMPEG
          </div>
          <div style={{ fontSize: '0.72rem', color: 'var(--text-muted)', fontWeight: 500 }}>
            Arsip Digital Cloud
          </div>
        </div>
      </div>

      {/* Navigation Links */}
      <nav style={{ flex: 1, padding: '16px 12px', overflowY: 'auto' }}>
        <div style={{
          fontSize: '0.6875rem',
          fontWeight: 700,
          textTransform: 'uppercase',
          letterSpacing: '0.05em',
          color: 'var(--text-light)',
          padding: '0 12px 10px 12px',
        }}>
          Menu Utama
        </div>

        <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
          {allowedItems.map((item) => {
            const Icon = item.icon;
            return (
              <NavLink
                key={item.path}
                to={item.path}
                onClick={onClose}
                style={({ isActive }) => ({
                  display: 'flex',
                  alignItems: 'center',
                  gap: '12px',
                  padding: '10px 14px',
                  borderRadius: 'var(--radius-md)',
                  color: isActive ? 'var(--primary)' : 'var(--text-muted)',
                  background: isActive ? 'var(--primary-light)' : 'transparent',
                  fontWeight: isActive ? 600 : 500,
                  fontSize: '0.875rem',
                  textDecoration: 'none',
                  transition: 'all 0.15s ease',
                })}
              >
                <Icon size={18} />
                <span>{item.label}</span>
              </NavLink>
            );
          })}
        </div>
      </nav>

      {/* User profile & Logout */}
      <div style={{
        padding: '16px',
        borderTop: '1px solid var(--border)',
        background: 'var(--bg-subtle)',
      }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', overflow: 'hidden' }}>
            <div style={{
              width: '36px',
              height: '36px',
              borderRadius: '50%',
              background: 'var(--primary-light)',
              color: 'var(--primary)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              fontWeight: 700,
              fontSize: '0.875rem',
              flexShrink: 0,
            }}>
              {user?.nama_lengkap?.charAt(0) || 'U'}
            </div>
            <div style={{ overflow: 'hidden' }}>
              <div style={{
                fontSize: '0.875rem',
                fontWeight: 600,
                whiteSpace: 'nowrap',
                overflow: 'hidden',
                textOverflow: 'ellipsis',
              }}>
                {user?.nama_lengkap || user?.username}
              </div>
              <div style={{
                fontSize: '0.72rem',
                textTransform: 'capitalize',
                color: 'var(--text-muted)',
              }}>
                {user?.role}
              </div>
            </div>
          </div>

          <button
            onClick={logout}
            title="Keluar"
            style={{
              background: 'none',
              border: 'none',
              color: 'var(--text-muted)',
              cursor: 'pointer',
              padding: '6px',
              borderRadius: 'var(--radius-sm)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            <LogOut size={18} />
          </button>
        </div>
      </div>
    </aside>
  );
}
