// client/src/pages/Pegawai/PegawaiList.jsx
import React, { useState, useEffect } from 'react';
import {
  Users,
  Search,
  Plus,
  Filter,
  Eye,
  Edit2,
  Trash2,
  Upload,
  X,
  FileText,
  Download,
} from 'lucide-react';
import api from '../../api/client';
import { useAuth } from '../../contexts/AuthContext';

export default function PegawaiList() {
  const { user } = useAuth();
  const isAdmin = ['admin', 'developer'].includes(user?.role);

  const [pegawai, setPegawai] = useState([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');

  // Modal states
  const [showFormModal, setShowFormModal] = useState(false);
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [selectedPegawai, setSelectedPegawai] = useState(null);
  const [dokumenList, setDokumenList] = useState([]);

  // Form states
  const [formData, setFormData] = useState({
    nip: '',
    nama: '',
    tempat_lahir: '',
    tanggal_lahir: '',
    jenis_kelamin: 'Laki-laki',
    agama: 'Islam',
    alamat: '',
    no_hp: '',
    email: '',
    status_kepegawaian: 'PNS',
    jabatan: '',
    pangkat_golongan: '',
    tmt_pangkat: '',
    unit_kerja: '',
  });
  const [formPhoto, setFormPhoto] = useState(null);
  const [isEditing, setIsEditing] = useState(false);

  // Upload document state
  const [jenisDokumen, setJenisDokumen] = useState('Ijazah Terakhir');
  const [docFile, setDocFile] = useState(null);
  const [docKeterangan, setDocKeterangan] = useState('');
  const [uploadingDoc, setUploadingDoc] = useState(false);

  const fetchPegawai = async () => {
    setLoading(true);
    try {
      const params = {};
      if (search) params.search = search;
      if (statusFilter) params.status = statusFilter;

      const res = await api.get('/pegawai', { params });
      setPegawai(res.data.data || []);
      setTotal(res.data.total || 0);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    const delayDebounce = setTimeout(() => {
      fetchPegawai();
    }, 300);
    return () => clearTimeout(delayDebounce);
  }, [search, statusFilter]);

  const handleOpenAdd = () => {
    setIsEditing(false);
    setFormData({
      nip: '',
      nama: '',
      tempat_lahir: '',
      tanggal_lahir: '',
      jenis_kelamin: 'Laki-laki',
      agama: 'Islam',
      alamat: '',
      no_hp: '',
      email: '',
      status_kepegawaian: 'PNS',
      jabatan: '',
      pangkat_golongan: '',
      tmt_pangkat: '',
      unit_kerja: '',
    });
    setFormPhoto(null);
    setShowFormModal(true);
  };

  const handleOpenEdit = (peg) => {
    setIsEditing(true);
    setSelectedPegawai(peg);
    setFormData({
      nip: peg.nip || '',
      nama: peg.nama || '',
      tempat_lahir: peg.tempat_lahir || '',
      tanggal_lahir: peg.tanggal_lahir ? peg.tanggal_lahir.substring(0, 10) : '',
      jenis_kelamin: peg.jenis_kelamin || 'Laki-laki',
      agama: peg.agama || 'Islam',
      alamat: peg.alamat || '',
      no_hp: peg.no_hp || '',
      email: peg.email || '',
      status_kepegawaian: peg.status_kepegawaian || 'PNS',
      jabatan: peg.jabatan || '',
      pangkat_golongan: peg.pangkat_golongan || '',
      tmt_pangkat: peg.tmt_pangkat ? peg.tmt_pangkat.substring(0, 10) : '',
      unit_kerja: peg.unit_kerja || '',
    });
    setFormPhoto(null);
    setShowFormModal(true);
  };

  const handleOpenDetail = async (peg) => {
    setSelectedPegawai(peg);
    setShowDetailModal(true);
    try {
      const res = await api.get(`/pegawai/${peg.id}`);
      setSelectedPegawai(res.data.data);
      setDokumenList(res.data.dokumen || []);
    } catch (err) {
      console.error(err);
    }
  };

  const handleSubmitForm = async (e) => {
    e.preventDefault();
    const data = new FormData();
    Object.keys(formData).forEach((key) => {
      if (formData[key]) data.append(key, formData[key]);
    });
    if (formPhoto) {
      data.append('foto', formPhoto);
    }

    try {
      if (isEditing) {
        await api.put(`/pegawai/${selectedPegawai.id}`, data, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      } else {
        await api.post('/pegawai', data, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      }
      setShowFormModal(false);
      fetchPegawai();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menyimpan data pegawai.');
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Apakah Anda yakin ingin menghapus data pegawai ini?')) return;
    try {
      await api.delete(`/pegawai/${id}`);
      fetchPegawai();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menghapus pegawai.');
    }
  };

  const handleUploadDoc = async (e) => {
    e.preventDefault();
    if (!docFile) return alert('Pilih file dokumen terlebih dahulu.');

    const fd = new FormData();
    fd.append('file', docFile);
    fd.append('jenis_dokumen', jenisDokumen);
    fd.append('keterangan', docKeterangan);

    setUploadingDoc(true);
    try {
      await api.post(`/pegawai/${selectedPegawai.id}/dokumen`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      // Refresh documents
      const res = await api.get(`/pegawai/${selectedPegawai.id}`);
      setDokumenList(res.data.dokumen || []);
      setDocFile(null);
      setDocKeterangan('');
      alert('Dokumen berhasil diunggah.');
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal mengunggah dokumen.');
    } finally {
      setUploadingDoc(false);
    }
  };

  const handleDeleteDoc = async (docId) => {
    if (!window.confirm('Hapus dokumen ini?')) return;
    try {
      await api.delete(`/pegawai/${selectedPegawai.id}/dokumen/${docId}`);
      setDokumenList(dokumenList.filter((d) => d.id !== docId));
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menghapus dokumen.');
    }
  };

  return (
    <div>
      {/* Title & Action Bar */}
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
            Data Pegawai ({total})
          </h1>
          <p style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>
            Kelola profil kepegawaian dan arsip berkas digital
          </p>
        </div>

        {isAdmin && (
          <button onClick={handleOpenAdd} className="btn btn-primary">
            <Plus size={18} />
            Tambah Pegawai
          </button>
        )}
      </div>

      {/* Filter & Search Bar */}
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
              placeholder="Cari nama, NIP, atau jabatan..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>

          <div style={{ width: '200px' }}>
            <select
              className="select"
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
            >
              <option value="">Semua Status</option>
              <option value="PNS">PNS</option>
              <option value="PPPK">PPPK</option>
              <option value="Honorer">Honorer</option>
              <option value="Kontrak">Kontrak</option>
            </select>
          </div>
        </div>
      </div>

      {/* Table Pegawai */}
      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Pegawai</th>
              <th>NIP</th>
              <th>Jabatan & Unit Kerja</th>
              <th>Pangkat/Golongan</th>
              <th>Status</th>
              <th style={{ textAlign: 'center' }}>Aksi</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr>
                <td colSpan="6" style={{ textAlign: 'center', padding: '32px', color: 'var(--text-muted)' }}>
                  Memuat data pegawai...
                </td>
              </tr>
            ) : pegawai.length === 0 ? (
              <tr>
                <td colSpan="6" style={{ textAlign: 'center', padding: '32px', color: 'var(--text-muted)' }}>
                  Tidak ada data pegawai ditemukan.
                </td>
              </tr>
            ) : (
              pegawai.map((row) => (
                <tr key={row.id}>
                  <td>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                      <div style={{
                        width: '38px',
                        height: '38px',
                        borderRadius: '50%',
                        background: 'var(--primary-light)',
                        color: 'var(--primary)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        fontWeight: 700,
                        overflow: 'hidden',
                        flexShrink: 0,
                      }}>
                        {row.foto_url ? (
                          <img src={row.foto_url} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                        ) : (
                          row.nama?.charAt(0) || 'P'
                        )}
                      </div>
                      <div>
                        <div style={{ fontWeight: 600 }}>{row.nama}</div>
                        <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{row.email || '-'}</div>
                      </div>
                    </div>
                  </td>
                  <td style={{ fontFamily: 'var(--font-mono)', fontSize: '0.8125rem' }}>
                    {row.nip || '-'}
                  </td>
                  <td>
                    <div style={{ fontWeight: 500 }}>{row.jabatan || '-'}</div>
                    <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{row.unit_kerja || '-'}</div>
                  </td>
                  <td>{row.pangkat_golongan || '-'}</td>
                  <td>
                    <span className={`badge ${
                      row.status_kepegawaian === 'PNS' ? 'badge-primary' : 'badge-neutral'
                    }`}>
                      {row.status_kepegawaian || '-'}
                    </span>
                  </td>
                  <td style={{ textAlign: 'center' }}>
                    <div style={{ display: 'inline-flex', gap: '6px' }}>
                      <button
                        onClick={() => handleOpenDetail(row)}
                        className="btn btn-secondary btn-sm"
                        title="Lihat Detail & Berkas"
                      >
                        <Eye size={15} />
                      </button>
                      {isAdmin && (
                        <>
                          <button
                            onClick={() => handleOpenEdit(row)}
                            className="btn btn-secondary btn-sm"
                            title="Edit"
                          >
                            <Edit2 size={15} />
                          </button>
                          <button
                            onClick={() => handleDelete(row.id)}
                            className="btn btn-secondary btn-sm"
                            style={{ color: 'var(--danger)' }}
                            title="Hapus"
                          >
                            <Trash2 size={15} />
                          </button>
                        </>
                      )}
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* MODAL: Tambah / Edit Pegawai */}
      {showFormModal && (
        <div className="modal-overlay">
          <div className="modal-content">
            <div className="modal-header">
              <h3 style={{ fontSize: '1.125rem', fontWeight: 700 }}>
                {isEditing ? 'Edit Data Pegawai' : 'Tambah Pegawai Baru'}
              </h3>
              <button
                onClick={() => setShowFormModal(false)}
                style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--text-muted)' }}
              >
                <X size={20} />
              </button>
            </div>
            <form onSubmit={handleSubmitForm}>
              <div className="modal-body">
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
                  <div className="form-group">
                    <label className="form-label">Nama Lengkap *</label>
                    <input
                      type="text"
                      className="input"
                      value={formData.nama}
                      onChange={(e) => setFormData({ ...formData, nama: e.target.value })}
                      required
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">NIP</label>
                    <input
                      type="text"
                      className="input"
                      value={formData.nip}
                      onChange={(e) => setFormData({ ...formData, nip: e.target.value })}
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Tempat Lahir</label>
                    <input
                      type="text"
                      className="input"
                      value={formData.tempat_lahir}
                      onChange={(e) => setFormData({ ...formData, tempat_lahir: e.target.value })}
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Tanggal Lahir</label>
                    <input
                      type="date"
                      className="input"
                      value={formData.tanggal_lahir}
                      onChange={(e) => setFormData({ ...formData, tanggal_lahir: e.target.value })}
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Jenis Kelamin</label>
                    <select
                      className="select"
                      value={formData.jenis_kelamin}
                      onChange={(e) => setFormData({ ...formData, jenis_kelamin: e.target.value })}
                    >
                      <option value="Laki-laki">Laki-laki</option>
                      <option value="Perempuan">Perempuan</option>
                    </select>
                  </div>
                  <div className="form-group">
                    <label className="form-label">Status Kepegawaian</label>
                    <select
                      className="select"
                      value={formData.status_kepegawaian}
                      onChange={(e) => setFormData({ ...formData, status_kepegawaian: e.target.value })}
                    >
                      <option value="PNS">PNS</option>
                      <option value="PPPK">PPPK</option>
                      <option value="Honorer">Honorer</option>
                      <option value="Kontrak">Kontrak</option>
                    </select>
                  </div>
                  <div className="form-group">
                    <label className="form-label">Jabatan</label>
                    <input
                      type="text"
                      className="input"
                      value={formData.jabatan}
                      onChange={(e) => setFormData({ ...formData, jabatan: e.target.value })}
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Unit Kerja</label>
                    <input
                      type="text"
                      className="input"
                      value={formData.unit_kerja}
                      onChange={(e) => setFormData({ ...formData, unit_kerja: e.target.value })}
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Pangkat / Golongan</label>
                    <input
                      type="text"
                      className="input"
                      placeholder="Contoh: Penata Muda / III/a"
                      value={formData.pangkat_golongan}
                      onChange={(e) => setFormData({ ...formData, pangkat_golongan: e.target.value })}
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">TMT Pangkat</label>
                    <input
                      type="date"
                      className="input"
                      value={formData.tmt_pangkat}
                      onChange={(e) => setFormData({ ...formData, tmt_pangkat: e.target.value })}
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Nomor HP</label>
                    <input
                      type="text"
                      className="input"
                      value={formData.no_hp}
                      onChange={(e) => setFormData({ ...formData, no_hp: e.target.value })}
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Email</label>
                    <input
                      type="email"
                      className="input"
                      value={formData.email}
                      onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    />
                  </div>
                </div>

                <div className="form-group" style={{ marginTop: '10px' }}>
                  <label className="form-label">Foto Profil (JPG/PNG)</label>
                  <input
                    type="file"
                    className="input"
                    accept="image/*"
                    onChange={(e) => setFormPhoto(e.target.files[0])}
                  />
                </div>
              </div>
              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" onClick={() => setShowFormModal(false)}>
                  Batal
                </button>
                <button type="submit" className="btn btn-primary">
                  {isEditing ? 'Perbarui Pegawai' : 'Simpan Pegawai'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: Detail Pegawai & Arsip Dokumen */}
      {showDetailModal && selectedPegawai && (
        <div className="modal-overlay">
          <div className="modal-content" style={{ maxWidth: '750px' }}>
            <div className="modal-header">
              <h3 style={{ fontSize: '1.125rem', fontWeight: 700 }}>
                Arsip & Profil: {selectedPegawai.nama}
              </h3>
              <button
                onClick={() => setShowDetailModal(false)}
                style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--text-muted)' }}
              >
                <X size={20} />
              </button>
            </div>

            <div className="modal-body">
              {/* Profil Overview Header */}
              <div style={{
                display: 'flex',
                gap: '16px',
                alignItems: 'center',
                paddingBottom: '20px',
                borderBottom: '1px solid var(--border)',
                marginBottom: '20px',
              }}>
                <div style={{
                  width: '64px',
                  height: '64px',
                  borderRadius: '50%',
                  background: 'var(--primary-light)',
                  color: 'var(--primary)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  fontWeight: 800,
                  fontSize: '1.5rem',
                  overflow: 'hidden',
                  flexShrink: 0,
                }}>
                  {selectedPegawai.foto_url ? (
                    <img src={selectedPegawai.foto_url} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                  ) : (
                    selectedPegawai.nama?.charAt(0) || 'P'
                  )}
                </div>
                <div>
                  <h4 style={{ fontSize: '1.125rem', fontWeight: 700 }}>{selectedPegawai.nama}</h4>
                  <div style={{ fontSize: '0.8125rem', color: 'var(--text-muted)' }}>
                    NIP: {selectedPegawai.nip || '-'} • Jabatan: {selectedPegawai.jabatan || '-'}
                  </div>
                  <div style={{ fontSize: '0.8125rem', color: 'var(--text-muted)', marginTop: '2px' }}>
                    Unit Kerja: {selectedPegawai.unit_kerja || '-'} • Gol: {selectedPegawai.pangkat_golongan || '-'}
                  </div>
                </div>
              </div>

              {/* Arsip Berkas Digital Section */}
              <h5 style={{ fontSize: '0.9375rem', fontWeight: 700, marginBottom: '12px' }}>
                📁 Dokumen & Berkas Digital Terlampir
              </h5>

              {/* Form Upload Dokumen */}
              {isAdmin && (
                <form onSubmit={handleUploadDoc} style={{
                  background: 'var(--bg-subtle)',
                  padding: '14px',
                  borderRadius: 'var(--radius-md)',
                  marginBottom: '16px',
                  border: '1px solid var(--border)',
                }}>
                  <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr auto', gap: '10px', alignItems: 'end' }}>
                    <div>
                      <label className="form-label" style={{ fontSize: '0.75rem' }}>Jenis Dokumen</label>
                      <select
                        className="select"
                        style={{ padding: '6px 10px', fontSize: '0.8125rem' }}
                        value={jenisDokumen}
                        onChange={(e) => setJenisDokumen(e.target.value)}
                      >
                        <option value="Ijazah Terakhir">Ijazah Terakhir</option>
                        <option value="SK CPNS/PNS">SK CPNS / PNS</option>
                        <option value="SK Kenaikan Pangkat">SK Kenaikan Pangkat</option>
                        <option value="SK Jabatan Fungsional">SK Jabatan Fungsional</option>
                        <option value="Kartu Keluarga & KTP">Kartu Keluarga & KTP</option>
                        <option value="Dokumen Lainnya">Dokumen Lainnya</option>
                      </select>
                    </div>

                    <div>
                      <label className="form-label" style={{ fontSize: '0.75rem' }}>Pilih File (PDF/Gambar)</label>
                      <input
                        type="file"
                        className="input"
                        style={{ padding: '4px 8px', fontSize: '0.8125rem' }}
                        onChange={(e) => setDocFile(e.target.files[0])}
                        required
                      />
                    </div>

                    <button
                      type="submit"
                      className="btn btn-primary btn-sm"
                      disabled={uploadingDoc}
                      style={{ height: '36px' }}
                    >
                      <Upload size={14} />
                      {uploadingDoc ? 'Mengunggah...' : 'Upload'}
                    </button>
                  </div>
                </form>
              )}

              {/* List Dokumen */}
              {dokumenList.length === 0 ? (
                <p style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>
                  Belum ada dokumen digital yang diunggah untuk pegawai ini.
                </p>
              ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                  {dokumenList.map((doc) => (
                    <div
                      key={doc.id}
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        padding: '10px 14px',
                        borderRadius: 'var(--radius-md)',
                        border: '1px solid var(--border)',
                        background: 'var(--bg-surface)',
                      }}
                    >
                      <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <FileText size={18} color="var(--primary)" />
                        <div>
                          <div style={{ fontWeight: 600, fontSize: '0.875rem' }}>{doc.jenis_dokumen}</div>
                          <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{doc.nama_file}</div>
                        </div>
                      </div>

                      <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                        <a
                          href={doc.file_url}
                          target="_blank"
                          rel="noreferrer"
                          className="btn btn-secondary btn-sm"
                          style={{ textDecoration: 'none' }}
                        >
                          <Download size={14} />
                          Lihat / Download
                        </a>
                        {isAdmin && (
                          <button
                            onClick={() => handleDeleteDoc(doc.id)}
                            className="btn btn-secondary btn-sm"
                            style={{ color: 'var(--danger)' }}
                          >
                            <Trash2 size={14} />
                          </button>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div className="modal-footer">
              <button className="btn btn-secondary" onClick={() => setShowDetailModal(false)}>
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
