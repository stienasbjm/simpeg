import { useEffect, useState } from "react";
import { supabase } from "../supabase.js";
import { confirmAction, showAlert } from "../alerts.js";

export default function AccountPage({ route, profile }) {
  const employeeAccounts = route === "akun_pegawai";
  const [accounts, setAccounts] = useState([]);
  const [staff, setStaff] = useState([]);
  const [editing, setEditing] = useState(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const role = profile.role;

  async function load() {
    const [accountsResult, staffResult] = await Promise.all([
      supabase.from("profiles").select("id, email, username, nama_lengkap, role, pegawai_id, created_at").order("username"),
      employeeAccounts ? supabase.from("pegawai").select("id, nama, nip, status_kepegawaian").order("nama") : Promise.resolve({ data: [] }),
    ]);
    if (accountsResult.error) setError(accountsResult.error.message);
    else setAccounts((accountsResult.data || []).filter((account) => (employeeAccounts ? account.role === "pegawai" : account.role !== "pegawai")));
    if (staffResult.error) setError(staffResult.error.message);
    else setStaff(staffResult.data || []);
  }

  useEffect(() => {
    load();
  }, [route]);

  async function save(event) {
    event.preventDefault();
    setBusy(true);
    setError("");
    const form = new FormData(event.currentTarget);
    const input = {
      action: editing ? "update" : "create",
      id: editing?.id,
      email: form.get("email"),
      username: form.get("username"),
      nama_lengkap: form.get("nama_lengkap"),
      role: employeeAccounts ? "pegawai" : form.get("role"),
      pegawai_id: employeeAccounts ? Number(form.get("pegawai_id")) || null : null,
      password: form.get("password") || undefined,
    };
    const { data, error: invokeError } = await supabase.functions.invoke("manage-account", { body: input });
    setBusy(false);
    if (invokeError || data?.error) {
      const message = data?.error || invokeError.message;
      setError(message);
      await showAlert("Akun gagal disimpan", `${message}. Pastikan Edge Function manage-account telah di-deploy.`, "error");
      return;
    }
    setNotice(editing ? "Akun berhasil diperbarui." : "Akun berhasil dibuat.");
    setEditing(null);
    await load();
  }

  async function remove(account) {
    if (account.id === profile.id) {
      await showAlert("Tidak dapat menghapus akun", "Akun yang sedang digunakan tidak dapat dihapus.", "warning");
      return;
    }
    if (!(await confirmAction("Hapus akun?", `Akun ${account.username} akan dihapus permanen.`, "Ya, hapus"))) return;
    const { data, error: invokeError } = await supabase.functions.invoke("manage-account", { body: { action: "delete", id: account.id } });
    if (invokeError || data?.error) {
      const message = data?.error || invokeError.message;
      setError(message);
      await showAlert("Akun belum terhapus", `${message}. Periksa deployment Edge Function manage-account.`, "error");
    } else {
      setNotice("Akun berhasil dihapus.");
      await load();
      await showAlert("Akun dihapus", `Akun ${account.username} berhasil dihapus.`, "success");
    }
  }

  const title = employeeAccounts ? "Akun Pegawai" : "Akun Admin & Dev";
  const roleLabel = (value) => (value === "bendahara" ? "Keuangan (Bendahara)" : value);
  return (
    <>
      <div className="e-page-header">
        <div>
          <div className="e-breadcrumb">
            <i className="bi bi-house-fill" />
            <a href="#/dashboard">Beranda</a>
            <span className="e-breadcrumb-sep">/</span>
            <span>{title}</span>
          </div>
          <h1 className="e-page-title">
            <div className="title-icon">
              <i className={`bi ${employeeAccounts ? "bi-person-lock" : "bi-shield-lock-fill"}`} />
            </div>
            {title}
          </h1>
          <p className="e-page-sub">Akun menggunakan Supabase Auth; password tidak disimpan pada tabel profil.</p>
        </div>
        <div className="d-flex gap-2">
          <button className="e-btn e-btn-ghost print-hidden" type="button" onClick={() => window.print()}>
            <i className="bi bi-printer-fill" />
            Cetak
          </button>
          <button className="e-btn e-btn-primary print-hidden" type="button" onClick={() => setEditing({})}>
            <i className="bi bi-person-plus-fill" />
            Tambah Akun
          </button>
        </div>
      </div>
      {error && (
        <div className="e-notice danger">
          <i className="bi bi-exclamation-triangle-fill" />
          {error}
        </div>
      )}
      {notice && (
        <div className="e-notice success">
          <i className="bi bi-check-circle-fill" />
          {notice}
        </div>
      )}
      {editing && (
        <form className="e-card mb-4" onSubmit={save}>
          <div className="e-card-header">
            <div className="e-card-title">{editing.id ? "Edit Akun" : "Buat Akun"}</div>
            <button type="button" className="e-act-btn" title="Tutup" onClick={() => setEditing(null)}>
              <i className="bi bi-x-lg" />
            </button>
          </div>
          <div className="e-card-body">
            <div className="row g-3">
              <div className="col-md-6">
                <label className="e-label">Username</label>
                <input className="e-input" name="username" defaultValue={editing.username || ""} required />
              </div>
              <div className="col-md-6">
                <label className="e-label">Email Auth</label>
                <input className="e-input" name="email" type="email" defaultValue={editing.email || ""} required />
              </div>
              <div className="col-md-6">
                <label className="e-label">Nama Lengkap</label>
                <input className="e-input" name="nama_lengkap" defaultValue={editing.nama_lengkap || ""} required />
              </div>
              {employeeAccounts ? (
                <div className="col-md-6">
                  <label className="e-label">Pegawai</label>
                  <select className="e-input" name="pegawai_id" defaultValue={editing.pegawai_id || ""} required>
                    <option value="">Pilih pegawai</option>
                    {!staff.length && (
                      <option value="" disabled>
                        Belum ada data pegawai
                      </option>
                    )}
                    {staff.map((person) => (
                      <option value={person.id} key={person.id}>
                        {person.nama} ({person.nip || "Tanpa NIP"}) · {person.status_kepegawaian || "Jenis belum ditentukan"}
                      </option>
                    ))}
                  </select>
                  {!staff.length && (
                    <small className="text-muted d-block mt-2">
                      Belum ada data untuk dipilih. <a href="#/pegawai_add">Tambahkan data pegawai</a> terlebih dahulu.
                    </small>
                  )}
                </div>
              ) : (
                <div className="col-md-6">
                  <label className="e-label">Role</label>
                  <select className="e-input" name="role" defaultValue={editing.role || "admin"}>
                    {(role === "developer" ? ["admin", "developer", "bendahara"] : ["admin"]).map((item) => (
                      <option key={item} value={item}>
                        {roleLabel(item)}
                      </option>
                    ))}
                  </select>
                </div>
              )}
              <div className="col-md-6">
                <label className="e-label">{editing.id ? "Password Baru (opsional)" : "Password Awal"}</label>
                <input className="e-input" name="password" type="password" minLength="8" required={!editing.id} autoComplete="new-password" />
              </div>
            </div>
          </div>
          <div className="e-card-footer e-account-form-footer d-flex gap-2">
            <button className="e-btn e-btn-primary" disabled={busy}>
              <i className="bi bi-save" />
              {busy ? "Menyimpan…" : "Simpan"}
            </button>
            <button className="e-btn e-btn-ghost" type="button" onClick={() => setEditing(null)}>
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
                <th>Username</th>
                <th>Nama Lengkap</th>
                <th>Role</th>
                {employeeAccounts && <th>Pegawai</th>}
                <th style={{ textAlign: "right" }}>Aksi</th>
              </tr>
            </thead>
            <tbody>
              {accounts.map((account, index) => (
                <tr key={account.id}>
                  <td>{index + 1}</td>
                  <td>{account.username}</td>
                  <td>{account.nama_lengkap}</td>
                  <td>
                    <span className="e-badge indigo">{roleLabel(account.role)}</span>
                  </td>
                  {employeeAccounts && (
                    <td>
                      {(() => {
                        const person = staff.find((item) => item.id === account.pegawai_id);
                        return person ? `${person.nama} · ${person.status_kepegawaian || "Jenis belum ditentukan"}` : account.pegawai_id || "—";
                      })()}
                    </td>
                  )}
                  <td>
                    <div className="d-flex gap-2 justify-content-end">
                      <button className="e-act-btn edit" title="Edit" onClick={() => setEditing(account)}>
                        <i className="bi bi-pencil-fill" />
                      </button>
                      <button className="e-act-btn del" title="Hapus" disabled={account.id === profile.id} onClick={() => remove(account)}>
                        <i className="bi bi-trash3-fill" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
              {!accounts.length && (
                <tr>
                  <td colSpan={employeeAccounts ? 6 : 5}>
                    <div className="e-empty">Belum ada akun.</div>
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
