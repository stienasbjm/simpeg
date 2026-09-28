// client/src/components/Layout/Navbar.jsx
import React from 'react';
import { Menu, Moon, Sun, Shield } from 'lucide-react';
import { useAuth } from '../../contexts/AuthContext';

export default function Navbar({ onToggleSidebar }) {
  const { user, theme, toggleTheme } = useAuth();

  return (
    <header className="topbar">
      <div style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
        <button
          onClick={onToggleSidebar}
          style={{
            background: 'none',
            border: 'none',
            color: 'var(--text-muted)',
            cursor: 'pointer',
            padding: '6px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
          }}
          aria-label="Toggle Navigation"
        >
          <Menu size={22} />
        </button>

        <span style={{ fontSize: '0.875rem', fontWeight: 600, color: 'var(--text-muted)' }}>
          Sistem Informasi Manajemen Pegawai & Arsip Digital
        </span>
      </div>

      <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
        {/* Role Badge */}
        <div className="badge badge-primary" style={{ textTransform: 'uppercase', padding: '4px 10px' }}>
          <Shield size={12} />
          {user?.role}
        </div>

        {/* Theme Toggle Button */}
        <button
          onClick={toggleTheme}
          className="btn btn-secondary btn-icon"
          title={`Ganti ke mode ${theme === 'light' ? 'gelap' : 'terang'}`}
          aria-label="Toggle Theme"
        >
          {theme === 'light' ? <Moon size={18} /> : <Sun size={18} />}
        </button>
      </div>
    </header>
  );
}
