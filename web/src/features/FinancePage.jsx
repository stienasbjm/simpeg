import { useEffect, useMemo, useState } from "react";
import { supabase } from "../supabase.js";

const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
const money = (value) => `Rp ${Number(value || 0).toLocaleString("id-ID")}`;
const currentPeriod = () => ({ month: new Date().getMonth() + 1, year: new Date().getFullYear() });

function downloadCsv(name, rows) {
  const escape = (value) => `"${String(value ?? "").replaceAll('"', '""')}"`;
  const content = `\uFEFF${rows.map((row) => row.map(escape).join(";")).join("\r\n")}`;
  const url = URL.createObjectURL(new Blob([content], { type: "text/csv;charset=utf-8" }));
  const link = document.createElement("a");
  link.href = url;
  link.download = name;
  link.click();
  URL.revokeObjectURL(url);
}

function PageHeading({ title, icon, subtitle, action }) {
  return (
    <div className="e-page-header">
      <div>
        <div className="e-breadcrumb">
          <i className="bi bi-house-fill" />
          <a href="#/dashboard">Beranda</a>
          <span className="e-breadcrumb-sep">/</span>
          <span>Keuangan</span>
          <span className="e-breadcrumb-sep">/</span>
          <span>{title}</span>
        </div>
        <h1 className="e-page-title">
          <div className="title-icon">
            <i className={`bi ${icon}`} />
          </div>
          {title}
        </h1>
        <p className="e-page-sub">{subtitle}</p>
      </div>
      {action}
    </div>
  );
}

function PeriodFilter({ period, setPeriod, onSubmit }) {
  return (
    <form className="e-card mb-3" onSubmit={onSubmit}>
      <div className="e-card-body d-flex flex-wrap align-items-end gap-3">
        <div>
          <label className="e-label">Bulan</label>
          <select className="e-select" value={period.month} onChange={(event) => setPeriod({ ...period, month: Number(event.target.value) })}>
            {monthNames.map((month, index) => (
              <option value={index + 1} key={month}>
                {month}
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="e-label">Tahun</label>
          <input className="e-input" type="number" min="2020" max="2100" value={period.year} onChange={(event) => setPeriod({ ...period, year: Number(event.target.value) })} />
        </div>
        <button className="e-btn e-btn-primary">
          <i className="bi bi-funnel-fill" />
          Terapkan
        </button>
      </div>
    </form>
  );
}

function CashPage() {
  const [period, setPeriod] = useState(currentPeriod);
  const [rows, setRows] = useState([]);
  const [allRows, setAllRows] = useState([]);
  const [payrollTotal, setPayrollTotal] = useState(0);
  const [allPayrollTotal, setAllPayrollTotal] = useState(0);
  const [cashType, setCashType] = useState("");
  const [search, setSearch] = useState("");
  const [editing, setEditing] = useState(null);
  const [showSignatories, setShowSignatories] = useState(false);
  const [signatories, setSignatories] = useState({});
  const [error, setError] = useState("");
  const [refresh, setRefresh] = useState(0);

  useEffect(() => {
    let active = true;
    async function load() {
      const fromDate = `${period.year}-${String(period.month).padStart(2, "0")}-01`;
      const nextDate = period.month === 12 ? `${period.year + 1}-01-01` : `${period.year}-${String(period.month + 1).padStart(2, "0")}-01`;
      const [allResult, periodResult, payrollResult, settingsResult] = await Promise.all([
        supabase.from("kas_transaksi").select("*"),
        supabase.from("kas_transaksi").select("*").gte("tanggal", fromDate).lt("tanggal", nextDate).order("tanggal", { ascending: false }),
        supabase.from("gaji").select("bulan, tahun, total_gaji").gt("total_gaji", 0),
        supabase.from("pengaturan_ttd").select("key_name,key_value"),
      ]);
      if (!active) return;
      const failure = allResult.error || periodResult.error || payrollResult.error || settingsResult.error;
      if (failure) {
        setError(failure.message);
        return;
      }
      setAllRows(allResult.data || []);
      setSignatories(Object.fromEntries((settingsResult.data || []).map((row) => [row.key_name, row.key_value])));
      setAllPayrollTotal((payrollResult.data || []).reduce((sum, row) => sum + Number(row.total_gaji || 0), 0));
      const matchingPayroll = (payrollResult.data || []).filter((row) => row.bulan === period.month && row.tahun === period.year);
      const total = matchingPayroll.reduce((sum, row) => sum + Number(row.total_gaji || 0), 0);
      setPayrollTotal(total);
      const combined = [...(periodResult.data || [])];
      if (total)
        combined.push({
          id: `payroll-${period.month}-${period.year}`,
          tanggal: `${period.year}-${String(period.month).padStart(2, "0")}-28`,
          jenis_kas: "kas_besar",
          tipe: "pengeluaran",
          kategori: "Total Gaji & Tunjangan Pegawai",
          keterangan: `Pengeluaran gaji periode ${monthNames[period.month - 1]} ${period.year}`,
          jumlah: total,
          isPayroll: true,
        });
      setRows(combined);
    }
    load();
    return () => {
      active = false;
    };
  }, [period, refresh]);

  const summary = useMemo(() => {
    const sums = { kas_kecil_masuk: 0, kas_kecil_keluar: 0, kas_besar_masuk: 0, kas_besar_keluar: 0 };
    allRows.forEach((row) => {
      const key = `${row.jenis_kas}_${row.tipe}`;
      if (key in sums) sums[key] += Number(row.jumlah || 0);
    });
    sums.kas_besar_keluar += allPayrollTotal;
    return {
      kas_kecil_masuk: sums.kas_kecil_masuk,
      kas_kecil_keluar: sums.kas_kecil_keluar,
      kas_kecil_total: sums.kas_kecil_masuk - sums.kas_kecil_keluar,
      kas_besar_masuk: sums.kas_besar_masuk,
      kas_besar_keluar: sums.kas_besar_keluar,
      kas_besar_total: sums.kas_besar_masuk - sums.kas_besar_keluar,
    };
  }, [allRows, allPayrollTotal]);

  const visibleRows = rows.filter((row) => (!cashType || row.jenis_kas === cashType) && `${row.kategori} ${row.keterangan} ${row.tipe} ${row.jenis_kas}`.toLowerCase().includes(search.toLowerCase()));
  const periodSubmit = (event) => {
    event.preventDefault();
    setRefresh((value) => value + 1);
  };

  async function saveCash(event) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const payload = { tanggal: form.get("tanggal"), jenis_kas: form.get("jenis_kas"), tipe: form.get("tipe"), kategori: form.get("kategori"), keterangan: form.get("keterangan") || null, jumlah: Number(form.get("jumlah")) };
    const result = editing?.id ? await supabase.from("kas_transaksi").update(payload).eq("id", editing.id) : await supabase.from("kas_transaksi").insert(payload);
    if (result.error) setError(result.error.message);
    else {
      setEditing(null);
      setRefresh((value) => value + 1);
    }
  }

  async function deleteCash(row) {
    if (row.isPayroll || !window.confirm("Hapus transaksi kas ini?")) return;
    const { error: deleteError } = await supabase.from("kas_transaksi").delete().eq("id", row.id);
    if (deleteError) setError(deleteError.message);
    else setRefresh((value) => value + 1);
  }

  async function saveSignatories(event) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const entries = [...form.entries()].map(([key_name, key_value]) => ({ key_name, key_value: String(key_value).trim() }));
    const { error: settingError } = await supabase.from("pengaturan_ttd").upsert(entries, { onConflict: "key_name" });
    if (settingError) setError(settingError.message);
    else {
      setSignatories(Object.fromEntries(entries.map((row) => [row.key_name, row.key_value])));
      setShowSignatories(false);
    }
  }

  const totals = useMemo(
    () =>
      rows.reduce(
        (acc, row) => {
          const key = row.tipe === "pemasukan" ? "income" : "expense";
          acc[key] += Number(row.jumlah || 0);
          return acc;
        },
        { income: 0, expense: 0 },
      ),
    [rows],
  );

  return (
    <>
      <PageHeading
        title="Manajemen Arus Kas"
        icon="bi-wallet2"
        subtitle={`Pencatatan kas kecil & besar periode ${monthNames[period.month - 1]} ${period.year}.`}
        action={
          <div className="d-flex flex-wrap gap-2">
            <button className="e-btn e-btn-ghost" onClick={() => setShowSignatories(!showSignatories)}>
              <i className="bi bi-pen-fill" />
              Atur Penandatangan
            </button>
            <button className="e-btn e-btn-primary" onClick={() => setEditing({})}>
              <i className="bi bi-plus-lg" />
              Tambah Transaksi Kas
            </button>
          </div>
        }
      />
      {error && (
        <div className="e-notice danger">
          <i className="bi bi-exclamation-triangle-fill" />
          {error}
        </div>
      )}
      {editing && (
        <form className="e-card mb-3" onSubmit={saveCash}>
          <div className="e-card-header">
            <div className="e-card-title">{editing.id ? "Edit Transaksi" : "Transaksi Kas Baru"}</div>
            <button type="button" className="e-act-btn" onClick={() => setEditing(null)}>
              <i className="bi bi-x-lg" />
            </button>
          </div>
          <div className="e-card-body">
            <div className="row g-3">
              <div className="col-md-4">
                <label className="e-label">Tanggal</label>
                <input className="e-input" name="tanggal" type="date" defaultValue={editing.tanggal || new Date().toISOString().slice(0, 10)} required />
              </div>
              <div className="col-md-4">
                <label className="e-label">Jenis Kas</label>
                <select className="e-input" name="jenis_kas" defaultValue={editing.jenis_kas || "kas_kecil"}>
                  <option value="kas_kecil">Kas Kecil</option>
                  <option value="kas_besar">Kas Besar</option>
                </select>
              </div>
              <div className="col-md-4">
                <label className="e-label">Tipe</label>
                <select className="e-input" name="tipe" defaultValue={editing.tipe || "pemasukan"}>
                  <option value="pemasukan">Pemasukan</option>
                  <option value="pengeluaran">Pengeluaran</option>
                </select>
              </div>
              <div className="col-md-6">
                <label className="e-label">Kategori</label>
                <input className="e-input" name="kategori" defaultValue={editing.kategori || ""} required />
              </div>
              <div className="col-md-6">
                <label className="e-label">Jumlah (Rp)</label>
                <input className="e-input" name="jumlah" type="number" min="1" step="1" defaultValue={editing.jumlah || ""} required />
              </div>
              <div className="col-12">
                <label className="e-label">Keterangan</label>
                <input className="e-input" name="keterangan" defaultValue={editing.keterangan || ""} />
              </div>
            </div>
          </div>
          <div className="e-card-footer d-flex gap-2">
            <button className="e-btn e-btn-primary">
              <i className="bi bi-save" />
              Simpan
            </button>
            <button type="button" className="e-btn e-btn-ghost" onClick={() => setEditing(null)}>
              Batal
            </button>
          </div>
        </form>
      )}
      {showSignatories && (
        <form className="e-card mb-3" onSubmit={saveSignatories}>
          <div className="e-card-header">
            <div className="e-card-title">Pengaturan Penandatangan</div>
            <button type="button" className="e-act-btn" onClick={() => setShowSignatories(false)}>
              <i className="bi bi-x-lg" />
            </button>
          </div>
          <div className="e-card-body">
            <div className="row g-3">
              {[
                ["ttd_bendahara_nama", "Nama Bendahara"],
                ["ttd_bendahara_nip", "NIP Bendahara"],
                ["ttd_bendahara_jabatan", "Jabatan Bendahara"],
                ["ttd_pimpinan_nama", "Nama Pimpinan"],
                ["ttd_pimpinan_nip", "NIP Pimpinan"],
                ["ttd_pimpinan_jabatan", "Jabatan Pimpinan"],
                ["kota_terbit", "Kota Terbit"],
              ].map(([name, label]) => (
                <div className="col-md-6" key={name}>
                  <label className="e-label">{label}</label>
                  <input className="e-input" name={name} defaultValue={signatories[name] || ""} required />
                </div>
              ))}
            </div>
          </div>
          <div className="e-card-footer">
            <button className="e-btn e-btn-primary">
              <i className="bi bi-save" />
              Simpan Penandatangan
            </button>
          </div>
        </form>
      )}
      <div className="row g-3 mb-3">
        {[
          ["Saldo Kas Kecil", summary.kas_kecil_total, "blue", summary.kas_kecil_masuk, summary.kas_kecil_keluar],
          ["Saldo Kas Besar", summary.kas_besar_total, "green", summary.kas_besar_masuk, summary.kas_besar_keluar],
          ["Total Periode Ini", totals.income - totals.expense, "purple", totals.income, totals.expense],
        ].map(([title, balance, color, income, expense]) => (
          <div className="col-md-4" key={title}>
            <div className="e-card">
              <div className="e-card-body">
                <div className="e-card-title">{title}</div>
                <div className={`e-stat-num text-${color}`}>{money(balance)}</div>
                <small>
                  Masuk: {money(income)} · Keluar: {money(expense)}
                </small>
              </div>
            </div>
          </div>
        ))}
      </div>
      <PeriodFilter period={period} setPeriod={setPeriod} onSubmit={periodSubmit} />
      <div className="e-table-wrap">
        <div className="d-flex flex-wrap gap-2 align-items-end p-3" style={{ background: "var(--bg-muted)" }}>
          <div className="e-card-title me-auto">
            <i className="bi bi-receipt" />
            Riwayat Transaksi Kas <span className="e-badge blue">{visibleRows.length} transaksi</span>
          </div>
          <div>
            <label className="e-label">Jenis Kas</label>
            <select className="e-select" value={cashType} onChange={(event) => setCashType(event.target.value)}>
              <option value="">Semua Kas</option>
              <option value="kas_kecil">Kas Kecil</option>
              <option value="kas_besar">Kas Besar</option>
            </select>
          </div>
          <div className="e-search">
            <i className="bi bi-search e-search-icon" />
            <input className="e-input" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Cari transaksi..." />
          </div>
          <button
            className="e-btn e-btn-ghost"
            onClick={() =>
              downloadCsv(`Laporan_Kas_${period.year}-${period.month}.csv`, [
                ["Tanggal", "Jenis Kas", "Tipe", "Kategori", "Keterangan", "Jumlah"],
                ...visibleRows.map((row) => [row.tanggal, row.jenis_kas, row.tipe, row.kategori, row.keterangan, row.jumlah]),
              ])
            }
          >
            <i className="bi bi-file-earmark-spreadsheet" />
            Export CSV
          </button>
          <button className="e-btn e-btn-ghost" onClick={() => window.print()}>
            <i className="bi bi-printer-fill" />
            Cetak
          </button>
        </div>
        <div style={{ overflowX: "auto" }}>
          <table className="e-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Tanggal</th>
                <th>Jenis Kas</th>
                <th>Tipe</th>
                <th>Kategori</th>
                <th>Keterangan</th>
                <th style={{ textAlign: "right" }}>Nominal</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              {visibleRows.map((row, index) => (
                <tr key={row.id}>
                  <td>{index + 1}</td>
                  <td>{row.tanggal}</td>
                  <td>{row.jenis_kas === "kas_kecil" ? "Kas Kecil" : "Kas Besar"}</td>
                  <td>
                    <span className={`e-badge ${row.tipe === "pemasukan" ? "green" : "red"}`}>{row.tipe}</span>
                  </td>
                  <td>{row.kategori}</td>
                  <td>{row.keterangan || "—"}</td>
                  <td style={{ textAlign: "right", fontWeight: 700 }}>{money(row.jumlah)}</td>
                  <td>
                    {row.isPayroll ? (
                      "—"
                    ) : (
                      <div className="d-flex gap-2">
                        <button className="e-act-btn edit" title="Edit" onClick={() => setEditing(row)}>
                          <i className="bi bi-pencil-fill" />
                        </button>
                        <button className="e-act-btn del" title="Hapus" onClick={() => deleteCash(row)}>
                          <i className="bi bi-trash3-fill" />
                        </button>
                      </div>
                    )}
                  </td>
                </tr>
              ))}
              {!visibleRows.length && (
                <tr>
                  <td colSpan="8">
                    <div className="e-empty">Belum ada transaksi pada periode ini.</div>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
      <div className="e-card-footer mt-3">
        <span>
          Total pengeluaran gaji periode ini: <strong>{money(payrollTotal)}</strong>
        </span>
      </div>
    </>
  );
}

const payrollFields = [
  ["gaji_pokok", "Gaji Pokok"],
  ["tunj_fungsional", "Tunjangan Fungsional"],
  ["tunj_struktural", "Tunjangan Struktural"],
  ["tunj_kesejahteraan", "Tunjangan Kesejahteraan"],
  ["tunj_beras", "Tunjangan Beras"],
  ["tunjangan_makan", "Uang Makan"],
  ["tunjangan_lain", "Tunjangan Lain"],
  ["pot_bpjs_tk", "Potongan BPJS TK"],
  ["pot_bpjs_kes", "Potongan BPJS Kesehatan"],
  ["pot_koperasi", "Potongan Koperasi"],
  ["potongan", "Potongan Lain"],
];
const defaultPay = (row = {}) => ({
  gaji_pokok: 0,
  tunj_fungsional: 0,
  tunj_struktural: 0,
  tunj_kesejahteraan: 0,
  status_kawin: 0,
  tunj_beras: 200000,
  jumlah_anak: 0,
  tunjangan_makan: 0,
  tunjangan_lain: 0,
  pot_bpjs_tk: 0,
  pot_bpjs_kes: 0,
  pot_koperasi: 20000,
  potongan: 0,
  catatan: "",
  ...row,
});

function payrollTotals(form) {
  const base = Number(form.gaji_pokok || 0);
  const allowances =
    Number(form.tunj_fungsional || 0) +
    Number(form.tunj_struktural || 0) +
    Number(form.tunj_kesejahteraan || 0) +
    Number(form.tunj_beras || 0) +
    Number(form.tunjangan_makan || 0) +
    Number(form.tunjangan_lain || 0) +
    (form.status_kawin ? base * 0.1 : 0) +
    Number(form.jumlah_anak || 0) * 0.02 * base;
  const deductions = Number(form.pot_bpjs_tk || 0) + Number(form.pot_bpjs_kes || 0) + Number(form.pot_koperasi || 0) + Number(form.potongan || 0);
  const bpjsTk = form.pot_bpjs_tk == null ? base * 0.02 : Number(form.pot_bpjs_tk || 0);
  const bpjsKes = form.pot_bpjs_kes == null ? base * 0.05 : Number(form.pot_bpjs_kes || 0);
  const totalDeductions = bpjsTk + bpjsKes + Number(form.pot_koperasi || 0) + Number(form.potongan || 0);
  return { allowances, deductions: totalDeductions, total: base + allowances - totalDeductions };
  const payload = {
    pegawai_id: editing.person.id,
    bulan: period.month,
    tahun: period.year,
    ...form,
    status_kawin: Number(form.status_kawin),
    tunj_kawin: Number(form.gaji_pokok || 0) * (form.status_kawin ? 0.1 : 0),
    tunj_anak: Number(form.gaji_pokok || 0) * Number(form.jumlah_anak || 0) * 0.02,
    pot_bpjs_tk: form.pot_bpjs_tk == null ? Number(form.gaji_pokok || 0) * 0.02 : Number(form.pot_bpjs_tk),
    pot_bpjs_kes: form.pot_bpjs_kes == null ? Number(form.gaji_pokok || 0) * 0.05 : Number(form.pot_bpjs_kes),
    total_gaji: totals.total,
  };
}

function PayrollPage() {
  const [period, setPeriod] = useState(currentPeriod);
  const [staff, setStaff] = useState([]);
  const [payroll, setPayroll] = useState([]);
  const [attendance, setAttendance] = useState([]);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(defaultPay());
  const [error, setError] = useState("");
  const [refresh, setRefresh] = useState(0);

  useEffect(() => {
    let active = true;
    async function load() {
      const fromDate = `${period.year}-${String(period.month).padStart(2, "0")}-01`;
      const nextDate = period.month === 12 ? `${period.year + 1}-01-01` : `${period.year}-${String(period.month + 1).padStart(2, "0")}-01`;
      const [staffResult, payrollResult, attendanceResult] = await Promise.all([
        supabase.from("pegawai").select("id,nama,nip,kepangkatan,status_kepegawaian,jabatan_fungsional,ijazah").order("nama"),
        supabase.from("gaji").select("*").eq("bulan", period.month).eq("tahun", period.year),
        supabase.from("absensi").select("pegawai_id,status").gte("tanggal", fromDate).lt("tanggal", nextDate).eq("status", "hadir"),
      ]);
      if (!active) return;
      const failure = staffResult.error || payrollResult.error || attendanceResult.error;
      if (failure) setError(failure.message);
      else {
        setStaff(staffResult.data || []);
        setPayroll(payrollResult.data || []);
        setAttendance(attendanceResult.data || []);
      }
    }
    load();
    return () => {
      active = false;
    };
  }, [period, refresh]);

  function startEdit(person, row) {
    setEditing({ person, row });
    setForm(defaultPay(row || {}));
  }

  async function savePayroll(event) {
    event.preventDefault();
    const totals = payrollTotals(form);
    const payload = {
      pegawai_id: editing.person.id,
      bulan: period.month,
      tahun: period.year,
      ...form,
      status_kawin: Number(form.status_kawin),
      tunj_kawin: Number(form.gaji_pokok || 0) * (form.status_kawin ? 0.1 : 0),
      tunj_anak: Number(form.gaji_pokok || 0) * Number(form.jumlah_anak || 0) * 0.02,
      pot_bpjs_tk: Number(form.pot_bpjs_tk || 0),
      pot_bpjs_kes: Number(form.pot_bpjs_kes || 0),
      total_gaji: totals.total,
    };
    const { error: saveError } = await supabase.from("gaji").upsert(payload, { onConflict: "pegawai_id,bulan,tahun" });
    if (saveError) setError(saveError.message);
    else {
      setEditing(null);
      setRefresh((value) => value + 1);
    }
  }

  async function deletePayroll(row) {
    if (!window.confirm("Hapus data gaji periode ini?")) return;
    const { error: deleteError } = await supabase.from("gaji").delete().eq("id", row.id);
    if (deleteError) setError(deleteError.message);
    else setRefresh((value) => value + 1);
  }

  const payrollByStaff = new Map(payroll.map((row) => [row.pegawai_id, row]));
  const attendanceByStaff = new Map();
  attendance.forEach((row) => attendanceByStaff.set(row.pegawai_id, (attendanceByStaff.get(row.pegawai_id) || 0) + 1));
  const totals = payroll.reduce((sum, row) => sum + Number(row.total_gaji || 0), 0);

  return (
    <>
      <PageHeading
        title="Penggajian & Slip Gaji"
        icon="bi-cash-stack"
        subtitle={`Kelola gaji pegawai periode ${monthNames[period.month - 1]} ${period.year}.`}
        action={
          <button className="e-btn e-btn-ghost" onClick={() => (window.location.hash = `/slip_gaji_print?all=1&bulan=${period.month}&tahun=${period.year}`)}>
            <i className="bi bi-printer-fill" />
            Cetak Semua Slip
          </button>
        }
      />
      {error && (
        <div className="e-notice danger">
          <i className="bi bi-exclamation-triangle-fill" />
          {error}
        </div>
      )}
      <div className="e-card mb-3">
        <div className="e-card-body d-flex align-items-end flex-wrap gap-3">
          <div>
            <label className="e-label">Bulan</label>
            <select className="e-select" value={period.month} onChange={(event) => setPeriod({ ...period, month: Number(event.target.value) })}>
              {monthNames.map((month, index) => (
                <option value={index + 1} key={month}>
                  {month}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="e-label">Tahun</label>
            <input className="e-input" type="number" value={period.year} onChange={(event) => setPeriod({ ...period, year: Number(event.target.value) })} />
          </div>
          <div className="e-stat-num">Anggaran gaji: {money(totals)}</div>
        </div>
      </div>
      {editing && (
        <form className="e-card mb-3" onSubmit={savePayroll}>
          <div className="e-card-header">
            <div className="e-card-title">Rincian Gaji · {editing.person.nama}</div>
            <button className="e-act-btn" type="button" onClick={() => setEditing(null)}>
              <i className="bi bi-x-lg" />
            </button>
          </div>
          <div className="e-card-body">
            <div className="e-notice info mb-3">
              Kehadiran: <strong>{attendanceByStaff.get(editing.person.id) || 0} hari</strong>. Uang makan dapat disinkronkan menjadi jumlah hadir × Rp 20.000.
            </div>
            <div className="row g-3">
              <div className="col-md-6">
                <label className="e-label">Gaji Pokok</label>
                <input className="e-input" type="number" value={form.gaji_pokok} onChange={(event) => setForm({ ...form, gaji_pokok: Number(event.target.value) })} />
              </div>
              <div className="col-md-6">
                <label className="e-label">Status Perkawinan</label>
                <select className="e-input" value={form.status_kawin} onChange={(event) => setForm({ ...form, status_kawin: Number(event.target.value) })}>
                  <option value="0">Belum Kawin / Tanpa Tunjangan</option>
                  <option value="1">Kawin / Tunjangan 10%</option>
                </select>
              </div>
              <div className="col-md-6">
                <label className="e-label">Jumlah Anak</label>
                <input className="e-input" type="number" min="0" max="10" value={form.jumlah_anak} onChange={(event) => setForm({ ...form, jumlah_anak: Number(event.target.value) })} />
              </div>
              {payrollFields.map(([key, label]) => (
                <div className="col-md-6" key={key}>
                  <label className="e-label">{label} (Rp)</label>
                  <input className="e-input" type="number" min="0" step="1000" value={form[key] ?? ""} onChange={(event) => setForm({ ...form, [key]: Number(event.target.value) })} />
                </div>
              ))}
              <div className="col-12">
                <label className="e-label">Catatan</label>
                <input className="e-input" value={form.catatan || ""} onChange={(event) => setForm({ ...form, catatan: event.target.value })} />
              </div>
            </div>
            <div className="e-card mt-3">
              <div className="e-card-body d-flex flex-wrap gap-4">
                <span>
                  Penerimaan: <strong>{money(payrollTotals(form).allowances + Number(form.gaji_pokok || 0))}</strong>
                </span>
                <span>
                  Potongan: <strong>{money(payrollTotals(form).deductions)}</strong>
                </span>
                <span>
                  Total bersih: <strong>{money(payrollTotals(form).total)}</strong>
                </span>
              </div>
            </div>
          </div>
          <div className="e-card-footer d-flex gap-2">
            <button className="e-btn e-btn-primary">
              <i className="bi bi-save" />
              Simpan Gaji
            </button>
            <button type="button" className="e-btn e-btn-ghost" onClick={() => setForm({ ...form, tunjangan_makan: (attendanceByStaff.get(editing.person.id) || 0) * 20000 })}>
              <i className="bi bi-arrow-repeat" />
              Sync Uang Makan Absen
            </button>
            <button type="button" className="e-btn e-btn-ghost" onClick={() => setEditing(null)}>
              Batal
            </button>
          </div>
        </form>
      )}
      <div className="e-table-wrap">
        <div style={{ overflowX: "auto" }}>
          <table className="e-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Nama Pegawai</th>
                <th>NIP</th>
                <th>Status</th>
                <th>Hadir</th>
                <th>Gaji Pokok</th>
                <th>Total Gaji</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              {staff.map((person, index) => {
                const row = payrollByStaff.get(person.id);
                return (
                  <tr key={person.id}>
                    <td>{index + 1}</td>
                    <td>{person.nama}</td>
                    <td>{person.nip || "—"}</td>
                    <td>{person.status_kepegawaian}</td>
                    <td>{attendanceByStaff.get(person.id) || 0} hari</td>
                    <td>{money(row?.gaji_pokok)}</td>
                    <td>
                      <strong>{row ? money(row.total_gaji) : "Belum diinput"}</strong>
                    </td>
                    <td>
                      <div className="d-flex gap-2">
                        <button className="e-act-btn edit" title="Edit/Input" onClick={() => startEdit(person, row)}>
                          <i className="bi bi-pencil-fill" />
                        </button>
                        {row && (
                          <>
                            <a className="e-act-btn view" title="Cetak Slip" href={`#/slip_gaji_print?id=${row.id}&bulan=${period.month}&tahun=${period.year}`}>
                              <i className="bi bi-printer-fill" />
                            </a>
                            <button className="e-act-btn del" title="Hapus" onClick={() => deletePayroll(row)}>
                              <i className="bi bi-trash3-fill" />
                            </button>
                          </>
                        )}
                      </div>
                    </td>
                  </tr>
                );
              })}
              {!staff.length && (
                <tr>
                  <td colSpan="8">
                    <div className="e-empty">Tidak ada data pegawai untuk periode ini.</div>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </>
  );
}

export default function FinancePage({ route }) {
  return route === "kas" ? <CashPage /> : <PayrollPage />;
}
