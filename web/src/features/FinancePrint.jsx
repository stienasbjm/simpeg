import { useEffect, useState } from "react";
import logoUrl from "../../../public/images/logo.png";
import { supabase } from "../supabase.js";

const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
const rupiah = (value) => `Rp ${Number(value || 0).toLocaleString("id-ID")}`;
const routeParams = () => {
  const [, query = ""] = window.location.hash.slice(1).split("?");
  return new URLSearchParams(query);
};

function PrintButton({ onPrint }) {
  return (
    <div className="print-toolbar">
      <button className="print-button" onClick={onPrint}>
        <i className="bi bi-printer-fill" />
        Cetak / Simpan PDF
      </button>
      <a className="print-button secondary" href="#/gaji">
        Kembali
      </a>
    </div>
  );
}

function CashReport({ period, type, rows, error }) {
  const income = rows.filter((row) => row.tipe === "pemasukan").reduce((sum, row) => sum + Number(row.jumlah || 0), 0);
  const expense = rows.filter((row) => row.tipe === "pengeluaran").reduce((sum, row) => sum + Number(row.jumlah || 0), 0);
  return (
    <>
      <PrintButton onPrint={() => window.print()} />
      {error ? (
        <p>{error}</p>
      ) : (
        <main className="report-sheet">
          <header>
            <img src={logoUrl} alt="Logo STIENAS" />
            <div>
              <h1>LAPORAN ARUS KAS PEGAWAI & INSTANSI</h1>
              <p>
                {months[period.month - 1]} {period.year} · {type || "Semua Kas"}
              </p>
            </div>
          </header>
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Tanggal</th>
                <th>Jenis Kas</th>
                <th>Kategori / Akun</th>
                <th>Keterangan</th>
                <th>Pemasukan</th>
                <th>Pengeluaran</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row, index) => (
                <tr key={row.id}>
                  <td>{index + 1}</td>
                  <td>{row.tanggal}</td>
                  <td>{row.jenis_kas}</td>
                  <td>{row.kategori}</td>
                  <td>{row.keterangan || "—"}</td>
                  <td>{row.tipe === "pemasukan" ? rupiah(row.jumlah) : "—"}</td>
                  <td>{row.tipe === "pengeluaran" ? rupiah(row.jumlah) : "—"}</td>
                </tr>
              ))}
            </tbody>
            <tfoot>
              <tr>
                <th colSpan="5">TOTAL PERIODE</th>
                <th>{rupiah(income)}</th>
                <th>{rupiah(expense)}</th>
              </tr>
              <tr>
                <th colSpan="5">ARUS KAS NETTO</th>
                <th colSpan="2">{rupiah(income - expense)}</th>
              </tr>
            </tfoot>
          </table>
        </main>
      )}
    </>
  );
}

function PayrollSlip({ records, period, signatories, error }) {
  return (
    <>
      <PrintButton onPrint={() => window.print()} />
      {error ? (
        <p>{error}</p>
      ) : !records.length ? (
        <div className="print-empty">Belum ada data slip gaji untuk periode ini.</div>
      ) : (
        records.map((record) => {
          const employee = Array.isArray(record.pegawai) ? record.pegawai[0] : record.pegawai;
          const incomeLines = [
            ["Gaji Pokok", record.gaji_pokok],
            ["Tunjangan Fungsional", record.tunj_fungsional],
            ["Tunjangan Struktural", record.tunj_struktural],
            ["Tunjangan Kesejahteraan", record.tunj_kesejahteraan],
            ["Tunjangan Kawin", record.tunj_kawin],
            ["Tunjangan Anak", record.tunj_anak],
            ["Tunjangan Beras", record.tunj_beras],
            ["Uang Makan", record.tunjangan_makan],
            ["Tunjangan Lain", record.tunjangan_lain],
          ].filter(([, amount]) => Number(amount) > 0);
          const deductionLines = [
            ["BPJS Ketenagakerjaan", record.pot_bpjs_tk],
            ["BPJS Kesehatan", record.pot_bpjs_kes],
            ["Koperasi", record.pot_koperasi],
            ["Potongan Lain", record.potongan],
          ].filter(([, amount]) => Number(amount) > 0);
          return (
            <article className="slip-sheet" key={record.id}>
              <header>
                <img src={logoUrl} alt="Logo STIENAS" />
                <div>
                  <h1>STIE NASIONAL BANJARMASIN</h1>
                  <p>Sistem Informasi Manajemen Kepegawaian (SIMPEG)</p>
                </div>
                <span className="slip-label">SLIP GAJI PEGAWAI</span>
              </header>
              <div className="slip-meta">
                <div>
                  <small>Nama Pegawai</small>
                  <strong>{employee?.nama || "—"}</strong>
                </div>
                <div>
                  <small>NIP / ID</small>
                  <strong>{employee?.nip || "—"}</strong>
                </div>
                <div>
                  <small>Jabatan / Pangkat</small>
                  <strong>
                    {employee?.kepangkatan || "—"} {employee?.jabatan_fungsional ? `(${employee.jabatan_fungsional})` : ""}
                  </strong>
                </div>
                <div>
                  <small>Periode Penggajian</small>
                  <strong>
                    {months[period.month - 1]} {period.year}
                  </strong>
                </div>
              </div>
              <table>
                <thead>
                  <tr>
                    <th>Rincian Penghasilan & Potongan</th>
                    <th>Nominal (Rp)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr className="section-row">
                    <td colSpan="2">A. PENERIMAAN / TUNJANGAN</td>
                  </tr>
                  {incomeLines.map(([label, amount]) => (
                    <tr key={label}>
                      <td>{label}</td>
                      <td>{rupiah(amount)}</td>
                    </tr>
                  ))}
                  <tr className="subtotal">
                    <td>Subtotal Penerimaan</td>
                    <td>{rupiah(incomeLines.reduce((sum, [, amount]) => sum + Number(amount || 0), 0))}</td>
                  </tr>
                  <tr className="section-row">
                    <td colSpan="2">B. POTONGAN GAJI</td>
                  </tr>
                  {deductionLines.map(([label, amount]) => (
                    <tr key={label}>
                      <td>{label}</td>
                      <td>{rupiah(amount)}</td>
                    </tr>
                  ))}
                  <tr className="subtotal">
                    <td>Subtotal Potongan</td>
                    <td>{rupiah(deductionLines.reduce((sum, [, amount]) => sum + Number(amount || 0), 0))}</td>
                  </tr>
                </tbody>
              </table>
              <div className="take-home">
                <strong>GAJI BERSIH DITERIMA</strong>
                <b>{rupiah(record.total_gaji)}</b>
              </div>
              <div className="signatures">
                <div>
                  <span>{signatories.ttd_bendahara_jabatan || "Bendahara"}</span>
                  <div className="signature-space" />
                  <strong>{signatories.ttd_bendahara_nama || "____________________"}</strong>
                  <small>NIP. {signatories.ttd_bendahara_nip || "—"}</small>
                </div>
                <div>
                  <span>{signatories.ttd_pimpinan_jabatan || "Pimpinan"}</span>
                  <div className="signature-space" />
                  <strong>{signatories.ttd_pimpinan_nama || "____________________"}</strong>
                  <small>NIP. {signatories.ttd_pimpinan_nip || "—"}</small>
                </div>
              </div>
              <footer>
                {signatories.kota_terbit || "Banjarmasin"}, {new Intl.DateTimeFormat("id-ID", { dateStyle: "long" }).format(new Date())}
              </footer>
            </article>
          );
        })
      )}
    </>
  );
}

export default function FinancePrint({ route, profile }) {
  const params = routeParams();
  const period = { month: Number(params.get("bulan")) || new Date().getMonth() + 1, year: Number(params.get("tahun")) || new Date().getFullYear() };
  const [rows, setRows] = useState([]);
  const [signatories, setSignatories] = useState({});
  const [error, setError] = useState("");
  const cashType = params.get("jenis_kas") || "";
  const isCash = route === "kas_print";

  useEffect(() => {
    let active = true;
    async function load() {
      const fromDate = `${period.year}-${String(period.month).padStart(2, "0")}-01`;
      const nextDate = period.month === 12 ? `${period.year + 1}-01-01` : `${period.year}-${String(period.month + 1).padStart(2, "0")}-01`;
      if (isCash) {
        const { data, error: queryError } = await supabase.from("kas_transaksi").select("*").gte("tanggal", fromDate).lt("tanggal", nextDate).order("tanggal", { ascending: true });
        if (!active) return;
        if (queryError) setError(queryError.message);
        else setRows((data || []).filter((row) => !cashType || row.jenis_kas === cashType));
        return;
      }
      const id = params.get("id");
      let query = supabase.from("gaji").select("*, pegawai(id,nama,nip,kepangkatan,jabatan_fungsional)").eq("bulan", period.month).eq("tahun", period.year);
      if (id) query = query.eq("id", id);
      const [payResult, settingResult] = await Promise.all([query, supabase.from("pengaturan_ttd").select("key_name,key_value")]);
      if (!active) return;
      if (payResult.error) setError(payResult.error.message);
      else setRows(payResult.data || []);
      const settings = {};
      (settingResult.data || []).forEach((row) => {
        settings[row.key_name] = row.key_value;
      });
      setSignatories(settings);
    }
    load();
    return () => {
      active = false;
    };
  }, [route, profile.id]);

  return <div className="print-page">{isCash ? <CashReport period={period} type={cashType} rows={rows} error={error} /> : <PayrollSlip period={period} records={rows} signatories={signatories} error={error} />}</div>;
}
