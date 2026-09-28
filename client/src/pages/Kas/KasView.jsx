// client/src/pages/Kas/KasView.jsx
import React, { useState, useEffect } from 'react';
import {
  Wallet,
  Plus,
  ArrowUpRight,
  ArrowDownRight,
  Trash2,
  Edit2,
  X,
} from 'lucide-react';
import api from '../../api/client';
import { useAuth } from '../../contexts/AuthContext';

export default function KasView() {
  const { user } = useAuth();
  const isBendahara = ['bendahara', 'admin', 'developer'].includes(user?.role);

  const [data, setData] = useState([]);
  const [total, setTotal] = useState(0);
  const [summary, setSummary] = useState({
    kas_kecil_total: 0,
    kas_besar_total: 0,
    total_kas: 0,
  });
  const [loading, setLoading] = useState(true);

  const [activeTab, setActiveTab] = useState('all'); // 'all', 'kas_kecil', 'kas_besar'
  const [bulan, setBulan] = useState('');
  const [tahun, setTahun] = useState(new Date().getFullYear().toString());

  const [showModal, setShowModal] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [selectedItem, setSelectedItem] = useState(null);

  const [formData, setFormData] = useState({
    jenis_kas: 'kas_kecil',
    tipe: 'pengeluaran',
    kategori: '',
    jumlah: '',
    keterangan: '',
    tanggal: new Date().toISOString().split('T')[0],
  });

  const fetchData = async () => {
    setLoading(true);
    try {
      const params = {};
      if (activeTab !== 'all') params.jenis_kas = activeTab;
      if (bulan) params.bulan = bulan;
      if (tahun) params.tahun = tahun;

      const [resKas, resSum] = await Promise.all([
        api.get('/keuangan/kas', { params }),
        api.get('/keuangan/kas/summary'),
      ]);

      setData(resKas.data.data || []);
      setTotal(resKas.data.total || 0);
      setSummary(resSum.data || {});
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [activeTab, bulan, tahun]);

  const handleOpenAdd = () => {
    setIsEditing(false);
    setSelectedItem(null);
    setFormData({
      jenis_kas: activeTab !== 'all' ? activeTab : 'kas_kecil',
      tipe: 'pengeluaran',
      kategori: '',
      jumlah: '',
      keterangan: '',
      tanggal: new Date().toISOString().split('T')[0],
    });
    setShowModal(true);
  };

  const handleOpenEdit = (item) => {
    setIsEditing(true);
    setSelectedItem(item);
    setFormData({
      jenis_kas: item.jenis_kas,
      tipe: item.tipe,
      kategori: item.kategori,
      jumlah: item.jumlah,
      keterangan: item.keterangan || '',
      tanggal: item.tanggal ? item.tanggal.substring(0, 10) : '',
    });
    setShowModal(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      if (isEditing) {
        await api.put(`/keuangan/kas/${selectedItem.id}`, formData);
      } else {
        await api.post('/keuangan/kas', formData);
      }
      setShowModal(false);
      fetchData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menyimpan transaksi kas.');
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Hapus catatan transaksi kas ini?')) return;
    try {
      await api.delete(`/keuangan/kas/${id}`);
      fetchData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menghapus transaksi.');
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
            Buku Kas & Keuangan
          </h1>
          <p style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>
            Pencatatan kas kecil operasional & kas besar instansi
          </p>
        </div>

        {isBendahara && (
          <button onClick={handleOpenAdd} className="btn btn-primary">
            <Plus size={18} />
            Catat Transaksi Kas
          </button>
        )}
      </div>

      {/* Summary Cards */}
      <div className="grid-cols-4" style={{ marginBottom: '24px' }}>
        <div className="card" style={{
          background: 'linear-gradient(135deg, var(--primary) 0%, #312e81 100%)',
          color: '#ffffff',
        }}>
          <div style={{ fontSize: '0.75rem', opacity: 0.85 }}>Total Saldo Gabungan</div>
          <div style={{ fontSize: '1.625rem', fontWeight: 800, marginTop: '4px' }}>
            Rp {(summary.total_kas || 0).toLocaleString('id-ID')}
          </div>
        </div>

        <div className="card">
          <div style={{ fontSize: '0.75rem', fontWeight: 600, color: 'var(--text-muted)' }}>Saldo Kas Kecil</div>
          <div style={{ fontSize: '1.5rem', fontWeight: 800, color: 'var(--secondary)', marginTop: '4px' }}>
            Rp {(summary.kas_kecil_total || 0).toLocaleString('id-ID')}
          </div>
        </div>

        <div className="card">
          <div style={{ fontSize: '0.75rem', fontWeight: 600, color: 'var(--text-muted)' }}>Saldo Kas Besar</div>
          <div style={{ fontSize: '1.5rem', fontWeight: 800, color: 'var(--purple)', marginTop: '4px' }}>
            Rp {(summary.kas_besar_total || 0).toLocaleString('id-ID')}
          </div>
        </div>

        <div className="card">
          <div style={{ fontSize: '0.75rem', fontWeight: 600, color: 'var(--text-muted)' }}>Total Transaksi</div>
          <div style={{ fontSize: '1.5rem', fontWeight: 800, color: 'var(--text-main)', marginTop: '4px' }}>
            {total} Record
          </div>
        </div>
      </div>

      {/* Tabs & Period Filter */}
      <div className="card" style={{ padding: '16px', marginBottom: '20px' }}>
        <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', justifyContent: 'space-between', gap: '12px' }}>
          {/* Tabs */}
          <div style={{ display: 'flex', gap: '6px', background: 'var(--bg-subtle)', padding: '4px', borderRadius: 'var(--radius-md)' }}>
            <button
              onClick={() => setActiveTab('all')}
              className={`btn btn-sm ${activeTab === 'all' ? 'btn-primary' : 'btn-secondary'}`}
              style={{ border: 'none' }}
            >
              Semua Kas
            </button>
            <button
              onClick={() => setActiveTab('kas_kecil')}
              className={`btn btn-sm ${activeTab === 'kas_kecil' ? 'btn-primary' : 'btn-secondary'}`}
              style={{ border: 'none' }}
            >
              Kas Kecil
            </button>
            <button
              onClick={() => setActiveTab('kas_besar')}
              className={`btn btn-sm ${activeTab === 'kas_besar' ? 'btn-primary' : 'btn-secondary'}`}
              style={{ border: 'none' }}
            >
              Kas Besar
            </button>
          </div>

          {/* Period Filter */}
          <div style={{ display: 'flex', gap: '8px' }}>
            <select className="select" style={{ width: '130px' }} value={bulan} onChange={(e) => setBulan(e.target.value)}>
              <option value="">Semua Bulan</option>
              {[
                { v: '1', l: 'Januari' }, { v: '2', l: 'Februari' }, { v: '3', l: 'Maret' },
                { v: '4', l: 'April' }, { v: '5', l: 'Mei' }, { v: '6', l: 'Juni' },
                { v: '7', l: 'Juli' }, { v: '8', l: 'Agustus' }, { v: '9', l: 'September' },
                { v: '10', l: 'Oktober' }, { v: '11', l: 'November' }, { v: '12', l: 'Desember' },
              ].map((m) => (
                <option key={m.v} value={m.v}>{m.l}</option>
              ))}
            </select>

            <select className="select" style={{ width: '110px' }} value={tahun} onChange={(e) => setTahun(e.target.value)}>
              <option value="2026">2026</option>
              <option value="2025">2025</option>
            </select>
          </div>
        </div>
      </div>

      {/* Ledger Table */}
      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Jenis Kas</th>
              <th>Kategori & Keterangan</th>
              <th>Tipe</th>
              <th style={{ textAlign: 'right' }}>Jumlah (Rp)</th>
              <th style={{ textAlign: 'center' }}>Aksi</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr>
                <td colSpan="6" style={{ textAlign: 'center', padding: '32px', color: 'var(--text-muted)' }}>
                  Memuat data transaksi...
                </td>
              </tr>
            ) : data.length === 0 ? (
              <tr>
                <td colSpan="6" style={{ textAlign: 'center', padding: '32px', color: 'var(--text-muted)' }}>
                  Belum ada transaksi kas pada periode ini.
                </td>
              </tr>
            ) : (
              data.map((row) => (
                <tr key={row.id}>
                  <td style={{ fontWeight: 600 }}>
                    {row.tanggal ? new Date(row.tanggal).toLocaleDateString('id-ID') : '-'}
                  </td>
                  <td>
                    <span className={`badge ${row.jenis_kas === 'kas_kecil' ? 'badge-primary' : 'badge-neutral'}`}>
                      {row.jenis_kas === 'kas_kecil' ? 'Kas Kecil' : 'Kas Besar'}
                    </span>
                  </td>
                  <div>
                    <td style={{ fontWeight: 500 }}>
                      <div>{row.kategori}</div>
                      <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{row.keterangan || '-'}</div>
                    </td>
                  </div>
                  <td>
                    <span className={`badge ${row.tipe === 'pemasukan' ? 'badge-success' : 'badge-danger'}`} style={{ textTransform: 'capitalize' }}>
                      {row.tipe === 'pemasukan' ? <ArrowUpRight size={12} /> : <ArrowDownRight size={12} />}
                      {row.tipe}
                    </span>
                  </td>
                  <td style={{
                    textAlign: 'right',
                    fontWeight: 700,
                    fontFamily: 'var(--font-mono)',
                    color: row.tipe === 'pemasukan' ? 'var(--success)' : 'var(--danger)',
                  }}>
                    {row.tipe === 'pemasukan' ? '+ ' : '- '}
                    Rp {parseFloat(row.jumlah || 0).toLocaleString('id-ID')}
                  </td>
                  <td style={{ textAlign: 'center' }}>
                    {isBendahara ? (
                      <div style={{ display: 'inline-flex', gap: '6px' }}>
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
                {isEditing ? 'Edit Transaksi Kas' : 'Catat Transaksi Baru'}
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
                    <label className="form-label">Jenis Kas *</label>
                    <select
                      className="select"
                      value={formData.jenis_kas}
                      onChange={(e) => setFormData({ ...formData, jenis_kas: e.target.value })}
                    >
                      <option value="kas_kecil">Kas Kecil (Operasional)</option>
                      <option value="kas_besar">Kas Besar (Bank/Instansi)</option>
                    </select>
                  </div>
                  <div className="form-group">
                    <label className="form-label">Tipe Transaksi *</label>
                    <select
                      className="select"
                      value={formData.tipe}
                      onChange={(e) => setFormData({ ...formData, tipe: e.target.value })}
                    >
                      <option value="pengeluaran">Pengeluaran</option>
                      <option value="pemasukan">Pemasukan</option>
                    </select>
                  </div>
                  <div className="form-group">
                    <label className="form-label">Kategori *</label>
                    <input
                      type="text"
                      className="input"
                      placeholder="Contoh: Konsumsi rapat, ATK, Listrik"
                      value={formData.kategori}
                      onChange={(e) => setFormData({ ...formData, kategori: e.target.value })}
                      required
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Nominal (Rp) *</label>
                    <input
                      type="number"
                      step="any"
                      className="input"
                      placeholder="0"
                      value={formData.jumlah}
                      onChange={(e) => setFormData({ ...formData, jumlah: e.target.value })}
                      required
                    />
                  </div>
                </div>

                <div className="form-group">
                  <label className="form-label">Tanggal Transaksi *</label>
                  <input
                    type="date"
                    className="input"
                    value={formData.tanggal}
                    onChange={(e) => setFormData({ ...formData, tanggal: e.target.value })}
                    required
                  />
                </div>

                <div className="form-group">
                  <label className="form-label">Keterangan / Uraian</label>
                  <textarea
                    className="textarea"
                    rows="2"
                    placeholder="Rincian catatan transaksi..."
                    value={formData.keterangan}
                    onChange={(e) => setFormData({ ...formData, keterangan: e.target.value })}
                  />
                </div>
              </div>
              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" onClick={() => setShowModal(false)}>
                  Batal
                </button>
                <button type="submit" className="btn btn-primary">
                  {isEditing ? 'Perbarui Transaksi' : 'Simpan Transaksi'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
