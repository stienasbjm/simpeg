import { useEffect, useState } from "react";
import logoUrl from "../../../public/images/logo.png";
import { supabase } from "../supabase.js";

const localDate = () => new Intl.DateTimeFormat("en-CA", { timeZone: "Asia/Makassar" }).format(new Date());
const monthStart = () => `${localDate().slice(0, 7)}-01`;
const timeText = (value) => (value ? String(value).slice(0, 5) : "—");
const statusText = { hadir: "Hadir", tidak_hadir: "Tidak Hadir", izin: "Izin", sakit: "Sakit", cuti: "Cuti" };
const statusColor = { hadir: "green", tidak_hadir: "red", izin: "blue", sakit: "amber", cuti: "purple" };

export default function EmployeePortal({ profile, route, onLogout }) {
  const [employee, setEmployee] = useState(null);
  const [today, setToday] = useState(null);
  const [attendance, setAttendance] = useState([]);
  const [leave, setLeave] = useState([]);
  const [clock, setClock] = useState(new Date());
  const [notice, setNotice] = useState(null);
  const [busy, setBusy] = useState(false);
  const [avatarUrl, setAvatarUrl] = useState("");

  async function loadPortal() {
    const { data: employeeData, error: employeeError } = await supabase.from("pegawai").select("*").eq("id", profile.pegawai_id).single();
    if (employeeError) {
      setNotice({ type: "danger", text: employeeError.message });
      return;
    }
    setEmployee(employeeData);
    const [todayResult, attendanceResult, leaveResult] = await Promise.all([
      supabase.from("absensi").select("*").eq("pegawai_id", employeeData.id).eq("tanggal", localDate()).maybeSingle(),
      supabase.from("absensi").select("*").eq("pegawai_id", employeeData.id).gte("tanggal", monthStart()).order("tanggal", { ascending: false }),
      supabase.from("pengajuan_izin").select("*").eq("pegawai_id", employeeData.id).order("created_at", { ascending: false }).limit(10),
    ]);
    setToday(todayResult.data || null);
    setAttendance(attendanceResult.data || []);
    setLeave(leaveResult.data || []);
    if (employeeData.foto) {
      const { data } = await supabase.storage.from("simpeg-private").createSignedUrl(employeeData.foto, 60 * 30);
      setAvatarUrl(data?.signedUrl || "");
    }
  }

  useEffect(() => {
    loadPortal();
    const timer = window.setInterval(() => setClock(new Date()), 1000);
    return () => window.clearInterval(timer);
  }, [profile.pegawai_id]);

  async function recordAttendance(action) {
    setBusy(true);
    const { error } = await supabase.rpc("record_attendance", { p_action: action });
    setBusy(false);
    if (error) setNotice({ type: "danger", text: error.message });
    else {
      setNotice({ type: "success", text: action === "masuk" ? "Absen masuk berhasil dicatat." : "Absen pulang berhasil dicatat." });
      await loadPortal();
    }
  }

  async function submitLeave(event) {
    event.preventDefault();
    setBusy(true);
    setNotice(null);
    const form = new FormData(event.currentTarget);
    const file = form.get("file_bukti");
    let filePath = null;
    if (file instanceof File && file.size) {
      const allowed = ["image/jpeg", "image/png", "image/webp", "application/pdf"];
      if (!allowed.includes(file.type) || file.size > 5 * 1024 * 1024) {
        setBusy(false);
        setNotice({ type: "danger", text: "Lampiran harus JPG, PNG, WebP, atau PDF dengan ukuran maksimal 5 MB." });
        return;
      }
      filePath = `${employee.id}/izin/${crypto.randomUUID()}-${file.name.replace(/[^a-zA-Z0-9._-]/g, "_")}`;
      const { error: uploadError } = await supabase.storage.from("simpeg-private").upload(filePath, file);
      if (uploadError) {
        setBusy(false);
        setNotice({ type: "danger", text: uploadError.message });
        return;
      }
    }
    const { error } = await supabase.from("pengajuan_izin").insert({
      pegawai_id: employee.id,
      tanggal: form.get("tanggal"),
      jenis: form.get("jenis"),
      keterangan: form.get("keterangan"),
      file_bukti: filePath,
      status: "menunggu",
    });
    if (error && filePath) await supabase.storage.from("simpeg-private").remove([filePath]);
    setBusy(false);
    if (error) setNotice({ type: "danger", text: error.message });
    else {
      event.currentTarget.reset();
      setNotice({ type: "success", text: "Pengajuan berhasil dikirim dan menunggu persetujuan admin." });
      await loadPortal();
    }
  }

  async function saveProfile(event) {
    event.preventDefault();
    setBusy(true);
    setNotice(null);
    const form = new FormData(event.currentTarget);
    const password = String(form.get("password_baru") || "");
    const confirmation = String(form.get("password_konfirmasi") || "");
    if (password && (password.length < 6 || password !== confirmation)) {
      setBusy(false);
      setNotice({ type: "danger", text: "Password minimal 6 karakter dan konfirmasi harus sama." });
      return;
    }
    let photoPath = employee.foto;
    const photo = form.get("foto");
    let uploadedPath = null;
    if (photo instanceof File && photo.size) {
      if (!["image/jpeg", "image/png", "image/webp"].includes(photo.type) || photo.size > 5 * 1024 * 1024) {
        setBusy(false);
        setNotice({ type: "danger", text: "Foto harus JPG, PNG, atau WebP dengan ukuran maksimal 5 MB." });
        return;
      }
      uploadedPath = `${employee.id}/foto/${crypto.randomUUID()}-${photo.name.replace(/[^a-zA-Z0-9._-]/g, "_")}`;
      const { error: uploadError } = await supabase.storage.from("simpeg-private").upload(uploadedPath, photo);
      if (uploadError) {
        setBusy(false);
        setNotice({ type: "danger", text: uploadError.message });
        return;
      }
      photoPath = uploadedPath;
    }
    const { error: updateError } = await supabase.rpc("update_employee_profile", {
      p_nama: form.get("nama"),
      p_tempat_lahir: form.get("tempat_lahir"),
      p_tanggal_lahir: form.get("tanggal_lahir"),
      p_ijazah: form.get("ijazah"),
      p_foto: photoPath,
    });
    let passwordError = null;
    if (!updateError && password) ({ error: passwordError } = await supabase.auth.updateUser({ password }));
    if ((updateError || passwordError) && uploadedPath) await supabase.storage.from("simpeg-private").remove([uploadedPath]);
    setBusy(false);
    const error = updateError || passwordError;
    if (error) setNotice({ type: "danger", text: error.message });
    else {
      setNotice({ type: "success", text: "Profil berhasil diperbarui." });
      await loadPortal();
    }
  }

  function toggleTheme() {
    const next = document.documentElement.dataset.bsTheme === "dark" ? "light" : "dark";
    document.documentElement.dataset.bsTheme = next;
    localStorage.setItem("earsip-theme", next);
  }

  if (!employee)
    return (
      <div className="portal-body">
        <div className="portal-content">
          <div className="e-empty">{notice?.text || "Memuat data pegawai…"}</div>
        </div>
      </div>
    );
  const hasCheckedIn = Boolean(today?.jam_masuk);
  const hasCheckedOut = Boolean(today?.jam_keluar);
  const isProfile = route === "profil";

  return (
    <>
      <header className="portal-topbar">
        <img src={logoUrl} alt="Logo STIENAS" style={{ height: 34, width: "auto", objectFit: "contain" }} />
        <div>
          <div className="e-brand-name" style={{ fontSize: ".85rem", fontWeight: 900 }}>
            Portal Pegawai
          </div>
          <div className="e-brand-tag" style={{ fontSize: ".68rem", opacity: 0.8 }}>
            STIE Nasional Banjarmasin
          </div>
        </div>
        <nav className="portal-nav">
          <a href="#/portal" className={`portal-nav-link ${!isProfile ? "active" : ""}`}>
            <i className="bi bi-calendar2-check" />
            <span className="d-none d-sm-inline">Absensi</span>
          </a>
          <a href="#/profil" className={`portal-nav-link ${isProfile ? "active" : ""}`}>
            <i className="bi bi-person-circle" />
            <span className="d-none d-sm-inline">Profil</span>
          </a>
          <button className="e-theme-btn" type="button" title="Toggle dark mode" onClick={toggleTheme}>
            <i className={`bi ${document.documentElement.dataset.bsTheme === "dark" ? "bi-sun" : "bi-moon"}`} />
          </button>
          <button className="e-btn-logout-topbar" onClick={onLogout}>
            <i className="bi bi-box-arrow-right" />
            <span className="d-none d-sm-inline">Logout</span>
          </button>
        </nav>
      </header>
      <div className="portal-body">
        <div className="portal-content">
          {notice && (
            <div className={`e-notice ${notice.type}`}>
              <i className={`bi ${notice.type === "success" ? "bi-check-circle-fill" : "bi-exclamation-triangle-fill"}`} />
              <span>{notice.text}</span>
            </div>
          )}
          {isProfile ? (
            <>
              <div className="e-page-header">
                <div>
                  <div className="e-breadcrumb">
                    <a href="#/portal">Portal</a>
                    <span className="e-breadcrumb-sep">/</span>
                    <span>Profil</span>
                  </div>
                  <h1 className="e-page-title">
                    <div className="title-icon">
                      <i className="bi bi-person-circle" />
                    </div>
                    Profil Saya
                  </h1>
                  <p className="e-page-sub">Perbarui data diri dan keamanan akun.</p>
                </div>
              </div>
              <form className="e-card" onSubmit={saveProfile}>
                <div className="e-card-body">
                  <div className="e-form-section">
                    <i className="bi bi-person-badge" />
                    Data Diri
                  </div>
                  {avatarUrl && <img src={avatarUrl} alt="Foto profil" style={{ width: 76, height: 76, objectFit: "cover", borderRadius: "50%", marginBottom: "1rem" }} />}
                  <div className="row g-3">
                    <div className="col-md-8">
                      <label className="e-label">Nama Lengkap</label>
                      <input className="e-input" name="nama" defaultValue={employee.nama || ""} required />
                    </div>
                    <div className="col-md-4">
                      <label className="e-label">NIP</label>
                      <input className="e-input" value={employee.nip || ""} disabled />
                    </div>
                    <div className="col-md-6">
                      <label className="e-label">Tempat Lahir</label>
                      <input className="e-input" name="tempat_lahir" defaultValue={employee.tempat_lahir || ""} required />
                    </div>
                    <div className="col-md-6">
                      <label className="e-label">Tanggal Lahir</label>
                      <input className="e-input" name="tanggal_lahir" type="date" defaultValue={employee.tanggal_lahir || ""} required />
                    </div>
                    <div className="col-12">
                      <label className="e-label">Ijazah Terakhir</label>
                      <input className="e-input" name="ijazah" defaultValue={employee.ijazah || ""} required />
                    </div>
                    <div className="col-12">
                      <label className="e-label">Foto Profil (maks. 5 MB)</label>
                      <input className="e-input" name="foto" type="file" accept="image/jpeg,image/png,image/webp" />
                    </div>
                  </div>
                  <div className="e-form-section mt-4">
                    <i className="bi bi-lock" />
                    Ganti Password
                  </div>
                  <div className="row g-3">
                    <div className="col-md-6">
                      <label className="e-label">Password Baru</label>
                      <input className="e-input" name="password_baru" type="password" minLength="6" />
                    </div>
                    <div className="col-md-6">
                      <label className="e-label">Konfirmasi Password</label>
                      <input className="e-input" name="password_konfirmasi" type="password" minLength="6" />
                    </div>
                  </div>
                </div>
                <div className="e-card-footer">
                  <button className="e-btn e-btn-primary" disabled={busy}>
                    <i className="bi bi-check-lg" />
                    {busy ? "Menyimpan…" : "Simpan Perubahan"}
                  </button>
                </div>
              </form>
            </>
          ) : (
            <>
              <div style={{ textAlign: "center", marginBottom: "2rem" }}>
                <div style={{ fontSize: ".78rem", fontWeight: 700, color: "var(--text-faint)", textTransform: "uppercase" }}>{new Intl.DateTimeFormat("id-ID", { dateStyle: "full", timeZone: "Asia/Makassar" }).format(clock)}</div>
                <div style={{ fontSize: "3rem", fontWeight: 900, color: "var(--text-primary)", lineHeight: 1.1, margin: ".5rem 0" }}>{new Intl.DateTimeFormat("id-ID", { timeStyle: "medium", timeZone: "Asia/Makassar" }).format(clock)}</div>
                <div style={{ fontSize: ".82rem", color: "var(--text-muted)" }}>Waktu Sistem</div>
              </div>
              <div className="e-card" style={{ marginBottom: "1.5rem" }}>
                <div className="e-card-body" style={{ padding: "2rem" }}>
                  <div style={{ textAlign: "center", marginBottom: "1.5rem" }}>
                    {avatarUrl ? (
                      <img src={avatarUrl} alt="Foto" style={{ width: 64, height: 64, borderRadius: "50%", objectFit: "cover", margin: "0 auto .875rem" }} />
                    ) : (
                      <div
                        style={{
                          width: 64,
                          height: 64,
                          borderRadius: "50%",
                          background: "linear-gradient(135deg,#6366f1,#a78bfa)",
                          color: "#fff",
                          display: "grid",
                          placeItems: "center",
                          fontSize: "1.5rem",
                          fontWeight: 900,
                          margin: "0 auto .875rem",
                        }}
                      >
                        {employee.nama?.slice(0, 1).toUpperCase()}
                      </div>
                    )}
                    <div style={{ fontWeight: 800, fontSize: "1.05rem" }}>{employee.nama}</div>
                    <div style={{ fontSize: ".8rem", color: "var(--text-muted)", marginTop: ".2rem" }}>{employee.kepangkatan}</div>
                  </div>
                  {hasCheckedIn && hasCheckedOut ? (
                    <div style={{ textAlign: "center", padding: "1.5rem", background: "rgba(16,185,129,.06)", border: "1px solid rgba(16,185,129,.2)", borderRadius: "var(--r-lg)" }}>
                      <i className="bi bi-check-circle-fill" style={{ fontSize: "2rem", color: "var(--green)" }} />
                      <div style={{ fontWeight: 800, color: "var(--green)" }}>Absensi Hari Ini Lengkap</div>
                      <div>
                        Masuk: <strong>{timeText(today.jam_masuk)}</strong> · Pulang: <strong>{timeText(today.jam_keluar)}</strong>
                      </div>
                    </div>
                  ) : (
                    <div className="d-grid" style={{ gridTemplateColumns: "1fr 1fr", gap: "1rem" }}>
                      <button className="e-btn e-btn-primary" disabled={busy || hasCheckedIn} onClick={() => recordAttendance("masuk")}>
                        <i className="bi bi-box-arrow-in-right" />
                        Absen Masuk {hasCheckedIn && timeText(today.jam_masuk)}
                      </button>
                      <button className="e-btn" style={{ background: "linear-gradient(135deg,#10b981,#059669)", color: "#fff" }} disabled={busy || !hasCheckedIn || hasCheckedOut} onClick={() => recordAttendance("pulang")}>
                        <i className="bi bi-box-arrow-right" />
                        Absen Pulang {hasCheckedOut && timeText(today.jam_keluar)}
                      </button>
                    </div>
                  )}
                </div>
              </div>
              <div className="e-card">
                <div className="e-card-header">
                  <div className="e-card-title">
                    <i className="bi bi-calendar3" />
                    Riwayat Absensi Bulan Ini
                  </div>
                  <span className="e-badge indigo">{attendance.length} hari</span>
                </div>
                <div style={{ overflowX: "auto" }}>
                  <table className="e-table">
                    <thead>
                      <tr>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Keterangan</th>
                      </tr>
                    </thead>
                    <tbody>
                      {attendance.length ? (
                        attendance.map((item) => (
                          <tr key={item.id}>
                            <td>{new Intl.DateTimeFormat("id-ID", { day: "2-digit", month: "short" }).format(new Date(`${item.tanggal}T00:00:00`))}</td>
                            <td>
                              <span className={`e-badge ${statusColor[item.status] || "indigo"}`}>{statusText[item.status] || item.status}</span>
                            </td>
                            <td>{timeText(item.jam_masuk)}</td>
                            <td>{timeText(item.jam_keluar)}</td>
                            <td>{item.keterangan || "—"}</td>
                          </tr>
                        ))
                      ) : (
                        <tr>
                          <td colSpan="5">
                            <div className="e-empty">
                              <p>Belum ada catatan absensi bulan ini.</p>
                            </div>
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
              <div className="e-card" style={{ marginTop: "1.5rem" }}>
                <div className="e-card-header">
                  <div className="e-card-title">
                    <i className="bi bi-file-earmark-medical" />
                    Pengajuan Izin / Sakit / Dinas
                  </div>
                </div>
                <div className="e-card-body">
                  <form onSubmit={submitLeave}>
                    <div className="row g-3">
                      <div className="col-md-6">
                        <label className="e-label">Jenis Pengajuan</label>
                        <select className="e-input" name="jenis" required>
                          <option value="izin">Izin</option>
                          <option value="sakit">Sakit</option>
                          <option value="dinas">Dinas Luar</option>
                        </select>
                      </div>
                      <div className="col-md-6">
                        <label className="e-label">Tanggal</label>
                        <input className="e-input" name="tanggal" type="date" defaultValue={localDate()} required />
                      </div>
                      <div className="col-12">
                        <label className="e-label">Keterangan / Alasan</label>
                        <textarea className="e-input" name="keterangan" rows="3" required />
                      </div>
                      <div className="col-12">
                        <label className="e-label">Lampiran Bukti (opsional, maks. 5 MB)</label>
                        <input className="e-input" name="file_bukti" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" />
                      </div>
                      <div className="col-12">
                        <button className="e-btn e-btn-primary" disabled={busy}>
                          <i className="bi bi-send-fill" />
                          Kirim Pengajuan
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
              <div className="e-card" style={{ marginTop: "1.5rem" }}>
                <div className="e-card-header">
                  <div className="e-card-title">
                    <i className="bi bi-clock-history" />
                    Riwayat Pengajuan
                  </div>
                </div>
                <div style={{ overflowX: "auto" }}>
                  <table className="e-table">
                    <thead>
                      <tr>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th>Catatan Admin</th>
                      </tr>
                    </thead>
                    <tbody>
                      {leave.length ? (
                        leave.map((item) => (
                          <tr key={item.id}>
                            <td>{item.tanggal}</td>
                            <td>{item.jenis}</td>
                            <td>
                              <span className={`e-badge ${item.status === "disetujui" ? "green" : item.status === "ditolak" ? "red" : "indigo"}`}>{item.status}</span>
                            </td>
                            <td>{item.keterangan}</td>
                            <td>{item.catatan_admin || "—"}</td>
                          </tr>
                        ))
                      ) : (
                        <tr>
                          <td colSpan="5">
                            <div className="e-empty">
                              <p>Belum ada pengajuan.</p>
                            </div>
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
            </>
          )}
        </div>
      </div>
    </>
  );
}
