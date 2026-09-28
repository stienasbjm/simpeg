// client/src/pages/Gaji/GajiView.jsx
import React, { useState, useEffect } from 'react';
import {
  CreditCard,
  Edit2,
  FileText,
  Printer,
  X,
  CheckCircle2,
} from 'lucide-react';
import api from '../../api/client';
import { useAuth } from '../../contexts/AuthContext';

export default function GajiView() {
  const { user } = useAuth();
  const isBendahara = ['bendahara', 'admin', 'developer'].includes(user?.role);

  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);

  const [bulan, setBulan] = useState((new Date().getMonth() + 1).toString());
  const [tahun, setTahun] = useState(new Date().getFullYear().toString());

  // Modal Input
  const [showInputModal, setShowInputModal] = useState(false);
  const [selectedPegawai, setSelectedPegawai] = useState(null);
  const [formGaji, setFormGaji] = useState({
    gaji_pokok: '',
    tunjangan_jabatan: '',
    tunjangan_keluarga: '',
    tunjangan_makan: '',
    tunjangan_transport: '',
    tunjangan_lain: '',
    potongan_bpjs: '',
    potongan_pajak: '',
    potongan_lain: '',
    keterangan: '',
  });

  // Modal Slip
  const [showSlipModal, setShowSlipModal] = useState(false);
  const [slipData, setSlipData] = useState(null);

  const fetchData = async () => {
    setLoading(true);
    try {
      const res = await api.get('/keuangan/gaji', { params: { bulan, tahun } });
      setData(res.data.data || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [bulan, tahun]);

  const handleOpenInput = (peg) => {
    setSelectedPegawai(peg);
    const existing = peg.gaji;
    setFormGaji({
      gaji_pokok: existing?.gaji_pokok || '3500000',
      tunjangan_jabatan: existing?.tunjangan_jabatan || '500000',
      tunjangan_keluarga: existing?.tunjangan_keluarga || '200000',
      tunjangan_makan: existing?.tunjangan_makan || '400000',
      tunjangan_transport: existing?.tunjangan_transport || '300000',
      tunjangan_lain: existing?.tunjangan_lain || '0',
      potongan_bpjs: existing?.potongan_bpjs || '150000',
      potongan_pajak: existing?.potongan_pajak || '100000',
      potongan_lain: existing?.potongan_lain || '0',
      keterangan: existing?.keterangan || '',
    });
    setShowInputModal(true);
  };

  const handleOpenSlip = (peg) => {
    setSlipData(peg);
    setShowSlipModal(true);
  };

  const handleSubmitGaji = async (e) => {
    e.preventDefault();
    try {
      await api.post('/keuangan/gaji', {
        ...formGaji,
        pegawai_id: selectedPegawai.id,
        bulan: parseInt(bulan),
        tahun: parseInt(tahun),
      });
      setShowInputModal(false);
      fetchData();
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menyimpan data gaji.');
    }
  };

  // Kalkulasi total
  const gp = parseFloat(formGaji.gaji_pokok) || 0;
  const tj = (parseFloat(formGaji.tunjangan_jabatan) || 0) +
             (parseFloat(formGaji.tunjangan_keluarga) || 0) +
             (parseFloat(formGaji.tunjangan_makan) || 0) +
             (parseFloat(formGaji.tunjangan_transport) || 0) +
             (parseFloat(formGaji.tunjangan_lain) || 0);
  const pt = (parseFloat(formGaji.potongan_bpjs) || 0) +
             (parseFloat(formGaji.potongan_pajak) || 0) +
             (parseFloat(formGaji.potongan_lain) || 0);
  const thp = gp + tj - pt;

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
            Penggajian Pegawai
          </h1>
          <p style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>
            Rincian gaji pokok, tunjangan, potongan, dan cetak slip gaji
          </p>
        </div>

        {/* Filter Bulan & Tahun */}
        <div style={{ display: 'flex', gap: '8px' }}>
          <select className="select" style={{ width: '140px' }} value={bulan} onChange={(e) => setBulan(e.target.value)}>
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

      {/* Table Gaji */}
      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Pegawai</th>
              <th>Jabatan & Unit Kerja</th>
              <th style={{ textAlign: 'right' }}>Gaji Pokok</th>
              <th style={{ textAlign: 'right' }}>Tunjangan</th>
              <th style={{ textAlign: 'right' }}>Potongan</th>
              <th style={{ textAlign: 'right' }}>Total Gaji (THP)</th>
              <th style={{ textAlign: 'center' }}>Aksi</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr>
                <td colSpan="7" style={{ textAlign: 'center', padding: '32px', color: 'var(--text-muted)' }}>
                  Memuat data gaji...
                </td>
              </tr>
            ) : data.length === 0 ? (
              <tr>
                <td colSpan="7" style={{ textAlign: 'center', padding: '32px', color: 'var(--text-muted)' }}>
                  Tidak ada data pegawai.
                </td>
              </tr>
            ) : (
              data.map((row) => {
                const g = row.gaji;
                return (
                  <tr key={row.id}>
                    <td>
                      <div style={{ fontWeight: 600 }}>{row.nama}</div>
                      <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', fontFamily: 'var(--font-mono)' }}>
                        NIP: {row.nip || '-'}
                      </div>
                    </td>
                    <td>
                      <div>{row.jabatan || '-'}</div>
                      <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{row.unit_kerja || '-'}</div>
                    </td>
                    <td style={{ textAlign: 'right', fontFamily: 'var(--font-mono)' }}>
                      {g ? `Rp ${parseFloat(g.gaji_pokok || 0).toLocaleString('id-ID')}` : '-'}
                    </td>
                    <td style={{ textAlign: 'right', fontFamily: 'var(--font-mono)', color: 'var(--success)' }}>
                      {g ? `+ Rp ${parseFloat(g.total_tunjangan || 0).toLocaleString('id-ID')}` : '-'}
                    </td>
                    <td style={{ textAlign: 'right', fontFamily: 'var(--font-mono)', color: 'var(--danger)' }}>
                      {g ? `- Rp ${parseFloat(g.total_potongan || 0).toLocaleString('id-ID')}` : '-'}
                    </td>
                    <td style={{ textAlign: 'right', fontFamily: 'var(--font-mono)', fontWeight: 700 }}>
                      {g ? `Rp ${parseFloat(g.total_gaji || 0).toLocaleString('id-ID')}` : (
                        <span className="badge badge-neutral">Belum dihitung</span>
                      )}
                    </td>
                    <td style={{ textAlign: 'center' }}>
                      <div style={{ display: 'inline-flex', gap: '6px' }}>
                        {isBendahara && (
                          <button
                            onClick={() => handleOpenInput(row)}
                            className="btn btn-secondary btn-sm"
                            title="Input / Edit Komponen Gaji"
                          >
                            <Edit2 size={15} />
                            {g ? 'Edit' : 'Hitung'}
                          </button>
                        )}
                        {g && (
                          <button
                            onClick={() => handleOpenSlip(row)}
                            className="btn btn-secondary btn-sm"
                            title="Lihat Slip Gaji"
                          >
                            <FileText size={15} />
                            Slip
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>

      {/* MODAL INPUT / EDIT KOMPONEN GAJI */}
      {showInputModal && selectedPegawai && (
        <div className="modal-overlay">
          <div className="modal-content" style={{ maxWidth: '680px' }}>
            <div className="modal-header">
              <div>
                <h3 style={{ fontSize: '1.125rem', fontWeight: 700 }}>
                  Rincian Gaji: {selectedPegawai.nama}
                </h3>
                <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>
                  Periode Bulan {bulan} / {tahun}
                </div>
              </div>
              <button
                onClick={() => setShowInputModal(false)}
                style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--text-muted)' }}
              >
                <X size={20} />
              </button>
            </div>
            <form onSubmit={handleSubmitGaji}>
              <div className="modal-body">
                <div className="form-group">
                  <label className="form-label">Gaji Pokok (Rp) *</label>
                  <input
                    type="number"
                    className="input"
                    value={formGaji.gaji_pokok}
                    onChange={(e) => setFormGaji({ ...formGaji, gaji_pokok: e.target.value })}
                    required
                  />
                </div>

                <div style={{ fontSize: '0.8125rem', fontWeight: 700, color: 'var(--success)', margin: '14px 0 8px 0' }}>
                  (+) TUNJANGAN & PENDAPATAN LAIN
                </div>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
                  <div className="form-group">
                    <label className="form-label">Tunjangan Jabatan</label>
                    <input
                      type="number"
                      className="input"
                      value={formGaji.tunjangan_jabatan}
                      onChange={(e) => setFormGaji({ ...formGaji, tunjangan_jabatan: e.target.value })}
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Tunjangan Keluarga</label>
                    <input
                      type="number"
                      className="input"
                      value={formGaji.tunjangan_keluarga}
                      onChange={(e) => setFormGaji({ ...formGaji, tunjangan_keluarga: e.target.value })}
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Tunjangan Uang Makan</label>
                    <input
                      type="number"
                      className="input"
                      value={formGaji.tunjangan_makan}
                      onChange={(e) => setFormGaji({ ...formGaji, tunjangan_makan: e.target.value })}
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Tunjangan Transport</label>
                    <input
                      type="number"
                      className="input"
                      value={formGaji.tunjangan_transport}
                      onChange={(e) => setFormGaji({ ...formGaji, tunjangan_transport: e.target.value })}
                    />
                  </div>
                </div>

                <div style={{ fontSize: '0.8125rem', fontWeight: 700, color: 'var(--danger)', margin: '14px 0 8px 0' }}>
                  (-) POTONGAN
                </div>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
                  <div className="form-group">
                    <label className="form-label">Potongan BPJS Kesehatan/TK</label>
                    <input
                      type="number"
                      className="input"
                      value={formGaji.potongan_bpjs}
                      onChange={(e) => setFormGaji({ ...formGaji, potongan_bpjs: e.target.value })}
                    />
                  </div>
                  <div className="form-group">
                    <label className="form-label">Potongan Pajak PPh 21</label>
                    <input
                      type="number"
                      className="input"
                      value={formGaji.potongan_pajak}
                      onChange={(e) => setFormGaji({ ...formGaji, potongan_pajak: e.target.value })}
                    />
                  </div>
                </div>

                {/* Ringkasan Perhitungan */}
                <div style={{
                  padding: '14px 18px',
                  borderRadius: 'var(--radius-md)',
                  background: 'var(--bg-subtle)',
                  marginTop: '16px',
                  border: '1px solid var(--border)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                }}>
                  <div>
                    <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>Take Home Pay (THP)</div>
                    <div style={{ fontSize: '1.25rem', fontWeight: 800, color: 'var(--primary)' }}>
                      Rp {thp.toLocaleString('id-ID')}
                    </div>
                  </div>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', textAlign: 'right' }}>
                    Tunjangan: +Rp {tj.toLocaleString('id-ID')}<br />
                    Potongan: -Rp {pt.toLocaleString('id-ID')}
                  </div>
                </div>
              </div>

              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" onClick={() => setShowInputModal(false)}>
                  Batal
                </button>
                <button type="submit" className="btn btn-primary">
                  Simpan Penggajian
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL CETAK SLIP GAJI */}
      {showSlipModal && slipData && (
        <div className="modal-overlay">
          <div className="modal-content" style={{ maxWidth: '600px' }}>
            <div className="modal-header">
              <h3 style={{ fontSize: '1.125rem', fontWeight: 700 }}>
                Slip Gaji Elektronik
              </h3>
              <button
                onClick={() => setShowSlipModal(false)}
                style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--text-muted)' }}
              >
                <X size={20} />
              </button>
            </div>

            <div className="modal-body" id="print-slip" style={{ padding: '28px' }}>
              <div style={{ textAlign: 'center', borderBottom: '2px solid var(--border)', paddingBottom: '16px', marginBottom: '20px' }}>
                <h4 style={{ fontSize: '1.25rem', fontWeight: 800 }}>SLIP GAJI PEGAWAI</h4>
                <div style={{ fontSize: '0.875rem', color: 'var(--text-muted)' }}>
                  Periode: Bulan {bulan} Tahun {tahun}
                </div>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px', marginBottom: '20px', fontSize: '0.875rem' }}>
                <div><strong>Nama:</strong> {slipData.nama}</div>
                <div><strong>NIP:</strong> {slipData.nip || '-'}</div>
                <div><strong>Jabatan:</strong> {slipData.jabatan || '-'}</div>
                <div><strong>Unit Kerja:</strong> {slipData.unit_kerja || '-'}</div>
              </div>

              <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '0.875rem', marginBottom: '20px' }}>
                <tbody>
                  <tr style={{ borderBottom: '1px solid var(--border)' }}>
                    <td style={{ padding: '8px 0' }}>Gaji Pokok</td>
                    <td style={{ textAlign: 'right', fontWeight: 600 }}>
                      Rp {parseFloat(slipData.gaji?.gaji_pokok || 0).toLocaleString('id-ID')}
                    </td>
                  </tr>
                  <tr style={{ borderBottom: '1px solid var(--border)' }}>
                    <td style={{ padding: '8px 0', color: 'var(--success)' }}>Total Tunjangan (+)</td>
                    <td style={{ textAlign: 'right', fontWeight: 600, color: 'var(--success)' }}>
                      Rp {parseFloat(slipData.gaji?.total_tunjangan || 0).toLocaleString('id-ID')}
                    </td>
                  </tr>
                  <tr style={{ borderBottom: '1px solid var(--border)' }}>
                    <td style={{ padding: '8px 0', color: 'var(--danger)' }}>Total Potongan (-)</td>
                    <td style={{ textAlign: 'right', fontWeight: 600, color: 'var(--danger)' }}>
                      Rp {parseFloat(slipData.gaji?.total_potongan || 0).toLocaleString('id-ID')}
                    </td>
                  </tr>
                  <tr style={{ background: 'var(--bg-subtle)' }}>
                    <td style={{ padding: '12px 8px', fontWeight: 800 }}>Penerimaan Bersih (THP)</td>
                    <td style={{ padding: '12px 8px', textAlign: 'right', fontWeight: 800, fontSize: '1.125rem', color: 'var(--primary)' }}>
                      Rp {parseFloat(slipData.gaji?.total_gaji || 0).toLocaleString('id-ID')}
                    </td>
                  </tr>
                </tbody>
              </table>

              <div style={{ fontSize: '0.75rem', color: 'var(--text-light)', textAlign: 'center', marginTop: '20px' }}>
                Dokumen ini diterbitkan secara otomatis oleh SIMPEG Cloud.
              </div>
            </div>

            <div className="modal-footer">
              <button className="btn btn-secondary" onClick={() => setShowSlipModal(false)}>
                Tutup
              </button>
              <button className="btn btn-primary" onClick={() => window.print()}>
                <Printer size={16} />
                Cetak Slip
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
