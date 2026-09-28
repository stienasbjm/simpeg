// client/src/pages/Absensi/AbsensiView.jsx
import React, { useState, useEffect } from 'react';
import {
  CalendarCheck,
  Plus,
  Filter,
  CheckCircle2,
  Clock,
  AlertCircle,
  X,
} from 'lucide-react';
import api from '../../api/client';
import { useAuth } from '../../contexts/AuthContext';

export default function AbsensiView() {
  const { user } = useAuth();
  const isAdmin = ['admin', 'developer'].includes(user?.role);

  const [data, setData] = useState([]);
  const [pegawaiList, setPegawaiList] = useState([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);

  const [bulan, setBulan] = useState((new Date().getMonth() + 1).toString());
  const [tahun, setTahun] = useState(new Date().getFullYear().toString());
  const [selectedPegawaiId, setSelectedPegawaiId] = useState('');

  const [showModal, setShowModal] = useState(false);
  const [formData, setFormData] = useState({
    pegawai_id: '',
    tanggal: new Date().toISOString().split('T')[0],
    jam_masuk: '08:00',
    jam_keluar: '16:00',
    status: 'hadir',
    keterangan: '',
  });

  const fetchData = async () => {
    setLoading(true);
    try {
      const params = { bulan, tahun };
      if (selectedPegawaiId) params.pegawai_id = selectedPegawaiId;

      const [resAbs, resPeg] = await Promise.all([
        api.get('/absensi', { params }),
        api.get('/pegawai?limit=500'),
      ]);
      setData(resAbs.data.data || []);
      setTotal(resAbs.data.total || 0);
      setPegawaiList(resPeg.data.data || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [bulan, tahun, selectedPegawaiId]);

  const handleOpenAdd = () => {
    setFormData({
      pegawai_id: pegawaiList[0]?.id || '',
      tanggal: new Date().toISOString().split('T')[0],
      jam_masuk: '08:00',
      jam_keluar: '16:00',
      status: 'hadir',
      keterangan: '',
    });
    setShowModal(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      await api.post('/absensi', formData);
      setShowModal(false);
      fetchData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menyimpan absensi.');
    }
  };

  // Status counts
  const hadirCount = data.filter((d) => d.status === 'hadir').length;
  const izinCount = data.filter((d) => d.status === 'izin').length;
  const sakitCount = data.filter((d) => d.status === 'sakit').length;
  const cutiCount = data.filter((d) => d.status === 'cuti').length;
  const alphaCount = data.filter((d) => d.status === 'alpha').length;

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
            Presensi & Absensi Pegawai
          </h1>
          <p style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>
            Rekapitulasi dan pencatatan presensi harian pegawai
          </p>
        </div>

        {isAdmin && (
          <button onClick={handleOpenAdd} className="btn btn-primary">
            <Plus size={18} />
            Catat Kehadiran
          </button>
        )}
      </div>

      {/* Summary Badges */}
      <div className="grid-cols-4" style={{ marginBottom: '20px' }}>
        <div className="card" style={{ padding: '16px' }}>
          <div style={{ fontSize: '0.75rem', fontWeight: 600, color: 'var(--text-muted)' }}>Hadir</div>
          <div style={{ fontSize: '1.5rem', fontWeight: 800, color: 'var(--success)', marginTop: '4px' }}>
            {hadirCount}
          </div>
        </div>
        <div className="card" style={{ padding: '16px' }}>
          <div style={{ fontSize: '0.75rem', fontWeight: 600, color: 'var(--text-muted)' }}>Izin & Cuti</div>
          <div style={{ fontSize: '1.5rem', fontWeight: 800, color: 'var(--secondary)', marginTop: '4px' }}>
            {izinCount + cutiCount}
          </div>
        </div>
        <div className="card" style={{ padding: '16px' }}>
          <div style={{ fontSize: '0.75rem', fontWeight: 600, color: 'var(--text-muted)' }}>Sakit</div>
          <div style={{ fontSize: '1.5rem', fontWeight: 800, color: 'var(--warning)', marginTop: '4px' }}>
            {sakitCount}
          </div>
        </div>
        <div className="card" style={{ padding: '16px' }}>
          <div style={{ fontSize: '0.75rem', fontWeight: 600, color: 'var(--text-muted)' }}>Tanpa Keterangan (Alpha)</div>
          <div style={{ fontSize: '1.5rem', fontWeight: 800, color: 'var(--danger)', marginTop: '4px' }}>
            {alphaCount}
          </div>
        </div>
      </div>

      {/* Filter Bar */}
      <div className="card" style={{ padding: '16px', marginBottom: '20px' }}>
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: '12px' }}>
          <div style={{ width: '150px' }}>
            <select className="select" value={bulan} onChange={(e) => setBulan(e.target.value)}>
              {[
                { v: '1', l: 'Januari' }, { v: '2', l: 'Februari' }, { v: '3', l: 'Maret' },
                { v: '4', l: 'April' }, { v: '5', l: 'Mei' }, { v: '6', l: 'Juni' },
                { v: '7', l: 'Juli' }, { v: '8', l: 'Agustus' }, { v: '9', l: 'September' },
                { v: '10', l: 'Oktober' }, { v: '11', l: 'November' }, { v: '12', l: 'Desember' },
              ].map((m) => (
                <option key={m.v} value={m.v}>{m.l}</option>
              ))}
            </select>
          </div>

          <div style={{ width: '130px' }}>
            <select className="select" value={tahun} onChange={(e) => setTahun(e.target.value)}>
              <option value="2026">2026</option>
              <option value="2025">2025</option>
            </select>
          </div>

          <div style={{ flex: '1 1 240px' }}>
            <select
              className="select"
              value={selectedPegawaiId}
              onChange={(e) => setSelectedPegawaiId(e.target.value)}
            >
              <option value="">Semua Pegawai</option>
              {pegawaiList.map((p) => (
                <option key={p.id} value={p.id}>
                  {p.nama} ({p.nip || 'Non-NIP'})
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Table Presensi */}
      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Pegawai</th>
              <th>Jam Masuk - Keluar</th>
              <th>Status</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr>
                <td colSpan="5" style={{ textAlign: 'center', padding: '32px', color: 'var(--text-muted)' }}>
                  Memuat data presensi...
                </td>
              </tr>
            ) : data.length === 0 ? (
              <tr>
                <td colSpan="5" style={{ textAlign: 'center', padding: '32px', color: 'var(--text-muted)' }}>
                  Tidak ada catatan presensi pada periode ini.
                </td>
              </tr>
            ) : (
              data.map((row) => (
                <tr key={row.id}>
                  <td style={{ fontWeight: 600 }}>
                    {row.tanggal ? new Date(row.tanggal).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' }) : '-'}
                  </td>
                  <td>
                    <div style={{ fontWeight: 600 }}>{row.pegawai?.nama || 'Pegawai'}</div>
                    <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{row.pegawai?.unit_kerja || '-'}</div>
                  </td>
                  <td style={{ fontFamily: 'var(--font-mono)', fontSize: '0.8125rem' }}>
                    {row.jam_masuk || '--:--'} - {row.jam_keluar || '--:--'}
                  </td>
                  <td>
                    <span className={`badge ${
                      row.status === 'hadir' ? 'badge-success' :
                      row.status === 'sakit' ? 'badge-warning' :
                      row.status === 'alpha' ? 'badge-danger' : 'badge-primary'
                    }`} style={{ textTransform: 'capitalize' }}>
                      {row.status}
                    </span>
                  </td>
                  <td>{row.keterangan || '-'}</td>
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
              <h3 style={{ fontSize: '1.125rem', fontWeight: 700 }}>Catat Presensi Harian</h3>
              <button
                onClick={() => setShowModal(false)}
                style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--text-muted)' }}
              >
                <X size={20} />
              </button>
            </div>
            <form onSubmit={handleSubmit}>
              <div className="modal-body">
                <div className="form-group">
                  <label className="form-label">Pilih Pegawai *</label>
                  <select
                    className="select"
                    value={formData.pegawai_id}
                    onChange={(e) => setFormData({ ...formData, pegawai_id: e.target.value })}
                    required
                  >
                    <option value="">-- Pilih Pegawai --</option>
                    {pegawaiList.map((p) => (
                      <option key={p.id} value={p.id}>
                        {p.nama} ({p.nip || 'Non-NIP'})
                      </option>
                    ))}
                  </select>
                </div>

                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
                  <div className="form-group">
                    <label className="form-label">Tanggal Presensi *</label>
                    <input
                      type="date"
                      className="input"
                      value={formData.tanggal}
                      onChange={(e) => setFormData({ ...formData, tanggal: e.target.value })}
                      required
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Status Kehadiran *</label>
                    <select
                      className="select"
                      value={formData.status}
                      onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                    >
                      <option value="hadir">Hadir</option>
                      <option value="izin">Izin</option>
                      <option value="sakit">Sakit</option>
                      <option value="cuti">Cuti</option>
                      <option value="alpha">Alpha (Tanpa Keterangan)</option>
                    </select>
                  </div>
                  <div className="form-group">
                    <label className="form-label">Jam Masuk</label>
                    <input
                      type="time"
                      className="input"
                      value={formData.jam_masuk}
                      onChange={(e) => setFormData({ ...formData, jam_masuk: e.target.value })}
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Jam Keluar</label>
                    <input
                      type="time"
                      className="input"
                      value={formData.jam_keluar}
                      onChange={(e) => setFormData({ ...formData, jam_keluar: e.target.value })}
                    />
                  </div>
                </div>

                <div className="form-group">
                  <label className="form-label">Keterangan Tambahan</label>
                  <input
                    type="text"
                    className="input"
                    placeholder="Contoh: Sakit flu, tugas luar kota..."
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
                  Simpan Presensi
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
