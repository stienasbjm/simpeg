// client/src/pages/SuratMasuk/SuratMasukList.jsx
import React, { useState, useEffect } from 'react';
import {
  Inbox,
  Search,
  Plus,
  Edit2,
  Trash2,
  Download,
  X,
  FileText,
} from 'lucide-react';
import api from '../../api/client';
import { useAuth } from '../../contexts/AuthContext';

export default function SuratMasukList() {
  const { user } = useAuth();
  const isAdmin = ['admin', 'developer'].includes(user?.role);

  const [data, setData] = useState([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [tahun, setTahun] = useState(new Date().getFullYear().toString());

  const [showModal, setShowModal] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [selectedItem, setSelectedItem] = useState(null);

  const [formData, setFormData] = useState({
    nomor_surat: '',
    tanggal_surat: '',
    tanggal_terima: '',
    pengirim: '',
    perihal: '',
    disposisi: '',
    keterangan: '',
  });
  const [fileSurat, setFileSurat] = useState(null);

  const fetchData = async () => {
    setLoading(true);
    try {
      const params = {};
      if (search) params.search = search;
      if (tahun) params.tahun = tahun;

      const res = await api.get('/surat-masuk', { params });
      setData(res.data.data || []);
      setTotal(res.data.total || 0);
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
      nomor_surat: '',
      tanggal_surat: new Date().toISOString().split('T')[0],
      tanggal_terima: new Date().toISOString().split('T')[0],
      pengirim: '',
      perihal: '',
      disposisi: '',
      keterangan: '',
    });
    setFileSurat(null);
    setShowModal(true);
  };

  const handleOpenEdit = (item) => {
    setIsEditing(true);
    setSelectedItem(item);
    setFormData({
      nomor_surat: item.nomor_surat || '',
      tanggal_surat: item.tanggal_surat ? item.tanggal_surat.substring(0, 10) : '',
      tanggal_terima: item.tanggal_terima ? item.tanggal_terima.substring(0, 10) : '',
      pengirim: item.pengirim || '',
      perihal: item.perihal || '',
      disposisi: item.disposisi || '',
      keterangan: item.keterangan || '',
    });
    setFileSurat(null);
    setShowModal(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const fd = new FormData();
    Object.keys(formData).forEach((k) => {
      if (formData[k]) fd.append(k, formData[k]);
    });
    if (fileSurat) {
      fd.append('file_surat', fileSurat);
    }

    try {
      if (isEditing) {
        await api.put(`/surat-masuk/${selectedItem.id}`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      } else {
        await api.post('/surat-masuk', fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      }
      setShowModal(false);
      fetchData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menyimpan surat masuk.');
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Hapus arsip surat masuk ini?')) return;
    try {
      await api.delete(`/surat-masuk/${id}`);
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
            Arsip Surat Masuk ({total})
          </h1>
          <p style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>
            Pencatatan dan arsip digital surat masuk
          </p>
        </div>

        {isAdmin && (
          <button onClick={handleOpenAdd} className="btn btn-primary">
            <Plus size={18} />
            Catat Surat Masuk
          </button>
        )}
      </div>

      {/* Filter Card */}
      <div className="card" style={{ padding: '16px', marginBottom: '20px' }}>
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: '12px' }}>
          <div style={{ flex: '1 1 280px', position: 'relative' }}>
            <Search size={18} style={{
              position: 'absolute',
              left: '12px',
              top: '50%',
              transform: 'translateY(-50)',
              color: 'var(--text-light)',
            }} />
            <input
              type="text"
              className="input"
              style={{ paddingLeft: '38px' }}
              placeholder="Cari nomor surat, pengirim, atau perihal..."
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

      {/* Data Table */}
      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Nomor & Tanggal</th>
              <th>Pengirim</th>
              <th>Perihal</th>
              <th>Disposisi</th>
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
                  Belum ada catatan surat masuk.
                </td>
              </tr>
            ) : (
              data.map((item) => (
                <tr key={item.id}>
                  <td>
                    <div style={{ fontWeight: 600, fontFamily: 'var(--font-mono)', fontSize: '0.8125rem' }}>
                      {item.nomor_surat}
                    </div>
                    <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginTop: '2px' }}>
                      Tgl: {item.tanggal_surat ? new Date(item.tanggal_surat).toLocaleDateString('id-ID') : '-'}
                    </div>
                  </td>
                  <td style={{ fontWeight: 500 }}>{item.pengirim}</td>
                  <td>{item.perihal}</td>
                  <td>
                    {item.disposisi ? (
                      <span className="badge badge-neutral">{item.disposisi}</span>
                    ) : (
                      <span style={{ color: 'var(--text-light)', fontSize: '0.75rem' }}>-</span>
                    )}
                  </td>
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
                        File
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

      {/* Modal Add / Edit */}
      {showModal && (
        <div className="modal-overlay">
          <div className="modal-content">
            <div className="modal-header">
              <h3 style={{ fontSize: '1.125rem', fontWeight: 700 }}>
                {isEditing ? 'Edit Surat Masuk' : 'Tambah Catatan Surat Masuk'}
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
                    <label className="form-label">Nomor Surat *</label>
                    <input
                      type="text"
                      className="input"
                      value={formData.nomor_surat}
                      onChange={(e) => setFormData({ ...formData, nomor_surat: e.target.value })}
                      required
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Pengirim *</label>
                    <input
                      type="text"
                      className="input"
                      value={formData.pengirim}
                      onChange={(e) => setFormData({ ...formData, pengirim: e.target.value })}
                      required
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Tanggal Surat *</label>
                    <input
                      type="date"
                      className="input"
                      value={formData.tanggal_surat}
                      onChange={(e) => setFormData({ ...formData, tanggal_surat: e.target.value })}
                      required
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Tanggal Diterima</label>
                    <input
                      type="date"
                      className="input"
                      value={formData.tanggal_terima}
                      onChange={(e) => setFormData({ ...formData, tanggal_terima: e.target.value })}
                    />
                  </div>
                </div>

                <div className="form-group">
                  <label className="form-label">Perihal *</label>
                  <textarea
                    className="textarea"
                    rows="2"
                    value={formData.perihal}
                    onChange={(e) => setFormData({ ...formData, perihal: e.target.value })}
                    required
                  />
                </div>

                <div className="form-group">
                  <label className="form-label">Disposisi</label>
                  <input
                    type="text"
                    className="input"
                    placeholder="Instruksi pimpinan..."
                    value={formData.disposisi}
                    onChange={(e) => setFormData({ ...formData, disposisi: e.target.value })}
                  />
                </div>

                <div className="form-group">
                  <label className="form-label">Unggah Berkas Surat (PDF/Gambar)</label>
                  <input
                    type="file"
                    className="input"
                    onChange={(e) => setFileSurat(e.target.files[0])}
                  />
                </div>
              </div>

              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" onClick={() => setShowModal(false)}>
                  Batal
                </button>
                <button type="submit" className="btn btn-primary">
                  {isEditing ? 'Perbarui Surat' : 'Simpan Surat'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
