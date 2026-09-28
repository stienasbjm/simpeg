// client/src/pages/SK/SKList.jsx
import React, { useState, useEffect } from 'react';
import {
  FileCheck,
  Search,
  Plus,
  Edit2,
  Trash2,
  Download,
  X,
} from 'lucide-react';
import api from '../../api/client';
import { useAuth } from '../../contexts/AuthContext';

export default function SKList() {
  const { user } = useAuth();
  const isAdmin = ['admin', 'developer'].includes(user?.role);

  const [data, setData] = useState([]);
  const [pegawaiList, setPegawaiList] = useState([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [tahun, setTahun] = useState(new Date().getFullYear().toString());

  const [showModal, setShowModal] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [selectedItem, setSelectedItem] = useState(null);

  const [formData, setFormData] = useState({
    nomor_sk: '',
    tanggal_sk: '',
    pegawai_id: '',
    perihal: '',
    keterangan: '',
  });
  const [fileSK, setFileSK] = useState(null);

  const fetchData = async () => {
    setLoading(true);
    try {
      const params = {};
      if (search) params.search = search;
      if (tahun) params.tahun = tahun;

      const [resSK, resPeg] = await Promise.all([
        api.get('/sk', { params }),
        api.get('/pegawai?limit=500'),
      ]);
      setData(resSK.data.data || []);
      setTotal(resSK.data.total || 0);
      setPegawaiList(resPeg.data.data || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    const timer = setTimeout(fetchData, 300);
    return () => clearTimeout(timer);
  }, [search, tahun]);

  const handleOpenAdd = () => {
    setIsEditing(false);
    setSelectedItem(null);
    setFormData({
      nomor_sk: '',
      tanggal_sk: new Date().toISOString().split('T')[0],
      pegawai_id: '',
      perihal: '',
      keterangan: '',
    });
    setFileSK(null);
    setShowModal(true);
  };

  const handleOpenEdit = (item) => {
    setIsEditing(true);
    setSelectedItem(item);
    setFormData({
      nomor_sk: item.nomor_sk || '',
      tanggal_sk: item.tanggal_sk ? item.tanggal_sk.substring(0, 10) : '',
      pegawai_id: item.pegawai_id || '',
      perihal: item.perihal || '',
      keterangan: item.keterangan || '',
    });
    setFileSK(null);
    setShowModal(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const fd = new FormData();
    Object.keys(formData).forEach((k) => {
      if (formData[k]) fd.append(k, formData[k]);
    });
    if (fileSK) {
      fd.append('file_sk', fileSK);
    }

    try {
      if (isEditing) {
        await api.put(`/sk/${selectedItem.id}`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      } else {
        await api.post('/sk', fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      }
      setShowModal(false);
      fetchData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menyimpan Surat Keputusan.');
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Hapus arsip Surat Keputusan ini?')) return;
    try {
      await api.delete(`/sk/${id}`);
      fetchData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menghapus data.');
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
            Surat Keputusan / SK ({total})
          </h1>
          <p style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>
            Arsip dokumen SK Pengangkatan, Kenaikan Pangkat, Mutasi & Jabatan
          </p>
        </div>

        {isAdmin && (
          <button onClick={handleOpenAdd} className="btn btn-primary">
            <Plus size={18} />
            Tambah SK Baru
          </button>
        )}
      </div>

      <div className="card" style={{ padding: '16px', marginBottom: '20px' }}>
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: '12px' }}>
          <div style={{ flex: '1 1 280px', position: 'relative' }}>
            <Search size={18} style={{
              position: 'absolute',
              left: '12px',
              top: '50%',
              transform: 'translateY(-50%)',
              color: 'var(--text-light)',
            }} />
            <input
              type="text"
              className="input"
              style={{ paddingLeft: '38px' }}
              placeholder="Cari nomor SK atau perihal..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>

          <div style={{ width: '160px' }}>
            <select
              className="select"
              value={tahun}
              onChange={(e) => setTahun(e.target.value)}
            >
              <option value="">Semua Tahun</option>
              <option value="2026">2026</option>
              <option value="2025">2025</option>
              <option value="2024">2024</option>
            </select>
          </div>
        </div>
      </div>

      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Nomor & Tanggal SK</th>
              <th>Pegawai Terkait</th>
              <th>Perihal SK</th>
              <th>Keterangan</th>
              <th>Berkas</th>
              <th style={{ textAlign: 'center' }}>Aksi</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr>
                <td colSpan="6" style={{ textAlign: 'center', padding: '32px', color: 'var(--text-muted)' }}>
                  Memuat data...
                </td>
              </tr>
            ) : data.length === 0 ? (
              <tr>
                <td colSpan="6" style={{ textAlign: 'center', padding: '32px', color: 'var(--text-muted)' }}>
                  Belum ada dokumen Surat Keputusan.
                </td>
              </tr>
            ) : (
              data.map((item) => (
                <tr key={item.id}>
                  <td>
                    <div style={{ fontWeight: 600, fontFamily: 'var(--font-mono)', fontSize: '0.8125rem' }}>
                      {item.nomor_sk}
                    </div>
                    <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginTop: '2px' }}>
                      Tgl: {item.tanggal_sk ? new Date(item.tanggal_sk).toLocaleDateString('id-ID') : '-'}
                    </div>
                  </td>
                  <td>
                    {item.pegawai ? (
                      <div>
                        <div style={{ fontWeight: 600 }}>{item.pegawai.nama}</div>
                        <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>NIP: {item.pegawai.nip || '-'}</div>
                      </div>
                    ) : (
                      <span style={{ color: 'var(--text-light)', fontSize: '0.8125rem' }}>Umum / Instansi</span>
                    )}
                  </td>
                  <td style={{ fontWeight: 500 }}>{item.perihal}</td>
                  <td>{item.keterangan || '-'}</td>
                  <td>
                    {item.file_url ? (
                      <a
                        href={item.file_url}
                        target="_blank"
                        rel="noreferrer"
                        className="btn btn-secondary btn-sm"
                        style={{ textDecoration: 'none' }}
                      >
                        <Download size={14} />
                        File SK
                      </a>
                    ) : (
                      <span style={{ color: 'var(--text-light)', fontSize: '0.75rem' }}>Tidak ada</span>
                    )}
                  </td>
                  <td style={{ textAlign: 'center' }}>
                    {isAdmin ? (
                      <div style={{ display: 'inline-flex', gap: '6px' }}>
                        <button
                          onClick={() => handleOpenEdit(item)}
                          className="btn btn-secondary btn-sm"
                          title="Edit"
                        >
                          <Edit2 size={15} />
                        </button>
                        <button
                          onClick={() => handleDelete(item.id)}
                          className="btn btn-secondary btn-sm"
                          style={{ color: 'var(--danger)' }}
                          title="Hapus"
                        >
                          <Trash2 size={15} />
                        </button>
                      </div>
                    ) : (
                      '-'
                    )}
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
                {isEditing ? 'Edit Surat Keputusan' : 'Tambah SK Baru'}
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
                    <label className="form-label">Nomor SK *</label>
                    <input
                      type="text"
                      className="input"
                      value={formData.nomor_sk}
                      onChange={(e) => setFormData({ ...formData, nomor_sk: e.target.value })}
                      required
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Tanggal SK *</label>
                    <input
                      type="date"
                      className="input"
                      value={formData.tanggal_sk}
                      onChange={(e) => setFormData({ ...formData, tanggal_sk: e.target.value })}
                      required
                    />
                  </div>
                </div>

                <div className="form-group">
                  <label className="form-label">Pegawai Terkait (Opsional)</label>
                  <select
                    className="select"
                    value={formData.pegawai_id}
                    onChange={(e) => setFormData({ ...formData, pegawai_id: e.target.value })}
                  >
                    <option value="">-- Pilih Pegawai (Bila SK Perorangan) --</option>
                    {pegawaiList.map((p) => (
                      <option key={p.id} value={p.id}>
                        {p.nama} ({p.nip || 'Non-NIP'})
                      </option>
                    ))}
                  </select>
                </div>

                <div className="form-group">
                  <label className="form-label">Perihal SK *</label>
                  <textarea
                    className="textarea"
                    rows="2"
                    value={formData.perihal}
                    onChange={(e) => setFormData({ ...formData, perihal: e.target.value })}
                    required
                  />
                </div>

                <div className="form-group">
                  <label className="form-label">Keterangan Tambahan</label>
                  <input
                    type="text"
                    className="input"
                    value={formData.keterangan}
                    onChange={(e) => setFormData({ ...formData, keterangan: e.target.value })}
                  />
                </div>

                <div className="form-group">
                  <label className="form-label">Unggah Salinan SK (PDF/Gambar)</label>
                  <input
                    type="file"
                    className="input"
                    onChange={(e) => setFileSK(e.target.files[0])}
                  />
                </div>
              </div>

              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" onClick={() => setShowModal(false)}>
                  Batal
                </button>
                <button type="submit" className="btn btn-primary">
                  {isEditing ? 'Perbarui SK' : 'Simpan SK'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
