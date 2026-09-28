// client/src/pages/Akun/AkunList.jsx
import React, { useState, useEffect } from 'react';
import {
  UserCog,
  Plus,
  Shield,
  Edit2,
  Trash2,
  KeyRound,
  X,
  CheckCircle,
  XCircle,
} from 'lucide-react';
import api from '../../api/client';
import { useAuth } from '../../contexts/AuthContext';

export default function AkunList() {
  const { user } = useAuth();
  const [data, setData] = useState([]);
  const [pegawaiList, setPegawaiList] = useState([]);
  const [loading, setLoading] = useState(true);

  const [showModal, setShowModal] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [selectedUser, setSelectedUser] = useState(null);

  const [formData, setFormData] = useState({
    username: '',
    password: '',
    nama_lengkap: '',
    role: 'pegawai',
    pegawai_id: '',
    is_active: true,
  });

  const fetchData = async () => {
    setLoading(true);
    try {
      const [resUsers, resPeg] = await Promise.all([
        api.get('/akun'),
        api.get('/pegawai?limit=500'),
      ]);
      setData(resUsers.data.data || []);
      setPegawaiList(resPeg.data.data || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  const handleOpenAdd = () => {
    setIsEditing(false);
    setSelectedUser(null);
    setFormData({
      username: '',
      password: '',
      nama_lengkap: '',
      role: 'pegawai',
      pegawai_id: '',
      is_active: true,
    });
    setShowModal(true);
  };

  const handleOpenEdit = (item) => {
    setIsEditing(true);
    setSelectedUser(item);
    setFormData({
      username: item.username || '',
      password: '', // Kosongkan jika tidak ganti
      nama_lengkap: item.nama_lengkap || '',
      role: item.role || 'pegawai',
      pegawai_id: item.pegawai_id || '',
      is_active: item.is_active,
    });
    setShowModal(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      if (isEditing) {
        await api.put(`/akun/${selectedUser.id}`, formData);
      } else {
        await api.post('/akun', formData);
      }
      setShowModal(false);
      fetchData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menyimpan akun.');
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Hapus akun pengguna ini?')) return;
    try {
      await api.delete(`/akun/${id}`);
      fetchData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menghapus user.');
    }
  };

  return (
    <div>
      <div style={{
        display: 'flex',
        flexWrap: 'wrap',
        alignItems: 'center',
        justifyContent: 'space-between',
        gap: '16px',
        marginBottom: '24px',
      }}>
        <div>
          <h1 style={{ fontSize: '1.5rem', fontWeight: 800, color: 'var(--text-main)' }}>
            Manajemen Akun Pengguna
          </h1>
          <p style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>
            Kelola hak akses pengguna, role sistem, dan otentikasi login
          </p>
        </div>

        <button onClick={handleOpenAdd} className="btn btn-primary">
          <Plus size={18} />
          Buat Akun Baru
        </button>
      </div>

      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Nama Lengkap & Username</th>
              <th>Role Sistem</th>
              <th>Terkait Pegawai</th>
              <th>Status</th>
              <th>Dibuat Pada</th>
              <th style={{ textAlign: 'center' }}>Aksi</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr>
                <td colSpan="6" style={{ textAlign: 'center', padding: '32px', color: 'var(--text-muted)' }}>
                  Memuat data akun...
                </td>
              </tr>
            ) : data.length === 0 ? (
              <tr>
                <td colSpan="6" style={{ textAlign: 'center', padding: '32px', color: 'var(--text-muted)' }}>
                  Tidak ada data user.
                </td>
              </tr>
            ) : (
              data.map((item) => (
                <tr key={item.id}>
                  <td>
                    <div style={{ fontWeight: 600 }}>{item.nama_lengkap}</div>
                    <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', fontFamily: 'var(--font-mono)' }}>
                      @{item.username}
                    </div>
                  </td>
                  <td>
                    <span className={`badge ${
                      item.role === 'admin' ? 'badge-primary' :
                      item.role === 'developer' ? 'badge-warning' :
                      item.role === 'bendahara' ? 'badge-success' : 'badge-neutral'
                    }`} style={{ textTransform: 'uppercase' }}>
                      {item.role}
                    </span>
                  </td>
                  <td>
                    {item.pegawai ? (
                      <div>
                        <div>{item.pegawai.nama}</div>
                        <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>NIP: {item.pegawai.nip || '-'}</div>
                      </div>
                    ) : (
                      <span style={{ color: 'var(--text-light)', fontSize: '0.75rem' }}>Tidak terhubung</span>
                    )}
                  </td>
                  <td>
                    {item.is_active ? (
                      <span className="badge badge-success">
                        <CheckCircle size={12} /> Aktif
                      </span>
                    ) : (
                      <span className="badge badge-danger">
                        <XCircle size={12} /> Nonaktif
                      </span>
                    )}
                  </td>
                  <td style={{ fontSize: '0.8125rem', color: 'var(--text-muted)' }}>
                    {item.created_at ? new Date(item.created_at).toLocaleDateString('id-ID') : '-'}
                  </td>
                  <td style={{ textAlign: 'center' }}>
                    <div style={{ display: 'inline-flex', gap: '6px' }}>
                      <button
                        onClick={() => handleOpenEdit(item)}
                        className="btn btn-secondary btn-sm"
                        title="Edit User"
                      >
                        <Edit2 size={15} />
                      </button>
                      {item.username !== user?.username && (
                        <button
                          onClick={() => handleDelete(item.id)}
                          className="btn btn-secondary btn-sm"
                          style={{ color: 'var(--danger)' }}
                          title="Hapus User"
                        >
                          <Trash2 size={15} />
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {showModal && (
        <div className="modal-overlay">
          <div className="modal-content">
            <div className="modal-header">
              <h3 style={{ fontSize: '1.125rem', fontWeight: 700 }}>
                {isEditing ? 'Edit Akun Pengguna' : 'Tambah Akun Pengguna Baru'}
              </h3>
              <button
                onClick={() => setShowModal(false)}
                style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--text-muted)' }}
              >
                <X size={20} />
              </button>
            </div>
            <form onSubmit={handleSubmit}>
              <div className="modal-body">
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
                  <div className="form-group">
                    <label className="form-label">Username *</label>
                    <input
                      type="text"
                      className="input"
                      value={formData.username}
                      onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                      required
                      disabled={isEditing}
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">
                      {isEditing ? 'Ganti Password (kosongkan bila tidak ganti)' : 'Password *'}
                    </label>
                    <input
                      type="password"
                      className="input"
                      placeholder={isEditing ? 'Biarkan kosong...' : 'Minimal 6 karakter'}
                      value={formData.password}
                      onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                      required={!isEditing}
                    />
                  </div>
                </div>

                <div className="form-group">
                  <label className="form-label">Nama Lengkap *</label>
                  <input
                    type="text"
                    className="input"
                    value={formData.nama_lengkap}
                    onChange={(e) => setFormData({ ...formData, nama_lengkap: e.target.value })}
                    required
                  />
                </div>

                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
                  <div className="form-group">
                    <label className="form-label">Role Akses *</label>
                    <select
                      className="select"
                      value={formData.role}
                      onChange={(e) => setFormData({ ...formData, role: e.target.value })}
                    >
                      <option value="admin">Admin</option>
                      <option value="developer">Developer</option>
                      <option value="bendahara">Bendahara</option>
                      <option value="pegawai">Pegawai</option>
                    </select>
                  </div>

                  <div className="form-group">
                    <label className="form-label">Status Akun</label>
                    <select
                      className="select"
                      value={formData.is_active ? '1' : '0'}
                      onChange={(e) => setFormData({ ...formData, is_active: e.target.value === '1' })}
                    >
                      <option value="1">Aktif</option>
                      <option value="0">Nonaktif</option>
                    </select>
                  </div>
                </div>

                <div className="form-group">
                  <label className="form-label">Tautkan ke Profil Pegawai (Opsional)</label>
                  <select
                    className="select"
                    value={formData.pegawai_id}
                    onChange={(e) => setFormData({ ...formData, pegawai_id: e.target.value })}
                  >
                    <option value="">-- Tidak Ditautkan --</option>
                    {pegawaiList.map((p) => (
                      <option key={p.id} value={p.id}>
                        {p.nama} ({p.nip || 'Non-NIP'})
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" onClick={() => setShowModal(false)}>
                  Batal
                </button>
                <button type="submit" className="btn btn-primary">
                  {isEditing ? 'Perbarui Akun' : 'Buat Akun'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
