import EmployeePortal from "./features/EmployeePortal.jsx";
import AccountPage from "./features/AccountPage.jsx";
import FinancePage from "./features/FinancePage.jsx";
import FinancePrint from "./features/FinancePrint.jsx";
import { resources } from "./features/resources.js";
import { useEffect, useState } from "react";
import { supabase, supabaseConfigured } from "./supabase.js";
import logoUrl from "../../public/images/logo.png";
import ResourcePage from "./features/ResourcePage.jsx";

const navigation = [
  { label: "Dashboard", route: "dashboard", icon: "bi-grid-1x2-fill", roles: ["admin", "developer", "bendahara"] },
  { label: "Data Pegawai", route: "pegawai", icon: "bi-people-fill", roles: ["admin", "developer"] },
  { label: "Absensi Pegawai", route: "absensi", icon: "bi-calendar2-check-fill", roles: ["admin", "developer", "bendahara"] },
  { label: "Pengajuan Izin", route: "pengajuan_izin", icon: "bi-clipboard-check-fill", roles: ["admin", "developer"] },
  { label: "Surat Masuk", route: "surat_masuk", icon: "bi-inbox-fill", roles: ["admin", "developer"] },
  { label: "Surat Keluar", route: "surat_keluar", icon: "bi-send-fill", roles: ["admin", "developer"] },
  { label: "Surat Keputusan (SK)", route: "sk", icon: "bi-file-earmark-text-fill", roles: ["admin", "developer"] },
  { label: "Arus Kas", route: "kas", icon: "bi-wallet2", roles: ["developer", "bendahara"] },
  { label: "Penggajian", route: "gaji", icon: "bi-cash-stack", roles: ["developer", "bendahara"] },
  { label: "Akun Pegawai", route: "akun_pegawai", icon: "bi-person-lock", roles: ["admin", "developer"] },
  { label: "Akun Admin & Dev", route: "akun_admin", icon: "bi-shield-lock-fill", roles: ["developer"] },
];

function routeName() {
  return window.location.hash.replace(/^#\/?/, "").split("?")[0] || "dashboard";
}

function go(route) {
  window.location.hash = `/${route}`;
}

function Login({ onSignedIn }) {
  const [identifier, setIdentifier] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  async function submit(event) {
    event.preventDefault();
    setBusy(true);
    setError("");

    let email = identifier.trim();
    if (!email.includes("@")) {
      try {
        const { data: lookedUpEmail, error: rpcError } = await supabase.rpc("get_auth_email", { p_username: email });
        if (!rpcError && lookedUpEmail) {
          email = lookedUpEmail;
        } else {
          setBusy(false);
          setError(`Username "${identifier.trim()}" tidak ditemukan. Silakan gunakan email yang terdaftar.`);
          return;
        }
      } catch {
        // Fallback to direct sign in attempt
      }
    }

    const { data, error: authError } = await supabase.auth.signInWithPassword({
      email,
      password,
    });
    setBusy(false);
    if (authError) {
      setError("Login gagal. Pastikan email/username dan password sudah benar.");
      return;
    }
    onSignedIn(data.session);
  }

  function toggleTheme() {
    const next = document.documentElement.dataset.bsTheme === "dark" ? "light" : "dark";
    document.documentElement.dataset.bsTheme = next;
    localStorage.setItem("earsip-theme", next);
  }

  useEffect(() => {
    document.documentElement.dataset.bsTheme = localStorage.getItem("earsip-theme") || "light";
  }, []);

  return (
    <div className="e-login-split-wrapper">
      <section className="e-login-hero-side">
        <div className="e-hero-content">
          <img src={logoUrl} alt="Logo STIENAS" style={{ height: 76, width: "auto", marginBottom: "1.5rem" }} />
          <h1 className="e-hero-title">
            Empowering
            <br />
            Educational
            <br />
            Excellence
          </h1>
          <p className="e-hero-desc">Access the centralized administrative hub for STIE Nasional Banjarmasin. Secure, efficient, and streamlined personnel management.</p>
        </div>
      </section>
      <section className="e-login-form-side">
        <div className="e-login-theme-toggle">
          <button id="loginThemeBtn" type="button" className="e-theme-btn-login" title="Ganti Mode Tampilan" onClick={toggleTheme}>
            <i className={`bi ${document.documentElement.dataset.bsTheme === "dark" ? "bi-sun-fill" : "bi-moon-fill"}`} />
          </button>
        </div>
        <div className="e-login-card-container">
          <div className="e-login-card">
            <div className="e-card-header">
              <div className="e-card-brand" style={{ display: "flex", alignItems: "center", gap: ".85rem" }}>
                <img src={logoUrl} alt="Logo STIENAS" style={{ height: 48, width: "auto" }} />
                <div className="e-card-brand-text">
                  <div className="e-card-app-name">SIMPEG</div>
                  <p className="e-card-app-sub">STIE Nasional Banjarmasin</p>
                </div>
              </div>
              <div className="e-card-divider" />
              <h2 className="e-card-title">Welcome Back</h2>
              <p className="e-card-sub">Masuk ke akun administratif Anda.</p>
            </div>
            {error && (
              <div className="e-login-error">
                <i className="bi bi-exclamation-circle-fill" />
                <span>{error}</span>
              </div>
            )}
            <form onSubmit={submit} autoComplete="on">
              <div className="e-form-group">
                <div className="e-input-wrapper">
                  <input className="e-input-field" type="text" value={identifier} onChange={(event) => setIdentifier(event.target.value)} placeholder="Email atau Username" required autoComplete="username" />
                </div>
              </div>
              <div className="e-form-group">
                <div className="e-input-wrapper">
                  <input className="e-input-field" type={showPassword ? "text" : "password"} value={password} onChange={(event) => setPassword(event.target.value)} placeholder="Password" required autoComplete="current-password" />
                  <button type="button" className="e-pwd-toggle-btn" title="Lihat Password" onClick={() => setShowPassword(!showPassword)}>
                    <i className={`bi ${showPassword ? "bi-eye-slash" : "bi-eye"}`} />
                  </button>
                </div>
              </div>
              <div className="e-form-options">
                <span className="e-remember-check">
                  <span>Gunakan akun Supabase Auth</span>
                </span>
                <a href="mailto:info@stienas-ypb.ac.id" className="e-link-accent">
                  Lupa Password?
                </a>
              </div>
              <button className="e-login-btn" type="submit" disabled={busy}>
                <span>{busy ? "Memeriksa…" : "Sign In"}</span>
                <i className="bi bi-arrow-right" />
              </button>
            </form>
            <div className="e-card-support-footer">
              Having trouble?{" "}
              <a href="mailto:info@stienas-ypb.ac.id" className="e-link-accent">
                Contact IT Support
              </a>
            </div>
          </div>
        </div>
        <div className="e-login-footer-outside">
          <p className="e-footer-copy">© {new Date().getFullYear()} STIE Nasional Banjarmasin. All Rights Reserved.</p>
        </div>
      </section>
    </div>
  );
}

function Stat({ title, value, icon, color, route }) {
  return (
    <div className="col-6 col-xl-3">
      <div className={`e-stat s-${color}`}>
        <div className={`e-stat-icon ${color}`}>
          <i className={`bi ${icon}`} />
        </div>
        <div className="e-stat-num">{value ?? "—"}</div>
        <div className="e-stat-lbl">{title}</div>
        <a className="e-stat-link" href={`#/${route}`}>
          Lihat data <i className="bi bi-arrow-right" />
        </a>
      </div>
    </div>
  );
}

function Dashboard({ profile }) {
  const [counts, setCounts] = useState(null);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    async function load() {
      if (profile.role === "bendahara") {
        const [cashResult, payrollResult] = await Promise.all([supabase.from("kas_transaksi").select("jenis_kas,tipe,jumlah"), supabase.from("gaji").select("bulan,tahun,total_gaji").gt("total_gaji", 0)]);
        if (!active) return;
        if (cashResult.error || payrollResult.error) {
          setError("Ringkasan keuangan belum dapat diakses. Pastikan migrasi Supabase dan kebijakan RLS sudah diterapkan.");
          return;
        }
        const balance = { kas_kecil_masuk: 0, kas_kecil_keluar: 0, kas_besar_masuk: 0, kas_besar_keluar: 0 };
        (cashResult.data || []).forEach((row) => {
          const key = `${row.jenis_kas}_${row.tipe}`;
          if (key in balance) balance[key] += Number(row.jumlah || 0);
        });
        const payroll = payrollResult.data || [];
        const payrollAll = payroll.reduce((sum, row) => sum + Number(row.total_gaji || 0), 0);
        const thisPeriod = payroll.filter((row) => row.bulan === new Date().getMonth() + 1 && row.tahun === new Date().getFullYear()).reduce((sum, row) => sum + Number(row.total_gaji || 0), 0);
        const small = balance.kas_kecil_masuk - balance.kas_kecil_keluar;
        const large = balance.kas_besar_masuk - balance.kas_besar_keluar - payrollAll;
        setCounts([small, large, small + large, thisPeriod].map((amount) => `Rp ${amount.toLocaleString("id-ID")}`));
      } else {
        const tables = ["surat_masuk", "surat_keluar", "surat_keputusan", "pegawai"];
        const results = await Promise.all(tables.map((table) => supabase.from(table).select("id", { count: "exact", head: true })));
        if (!active) return;
        const failed = results.find((result) => result.error);
        if (failed) {
          setError("Data dashboard belum dapat diakses. Pastikan skema sudah dipasang dan kebijakan RLS Supabase sudah dikonfigurasi.");
          return;
        }
        setCounts(results.map((result) => result.count ?? 0));
      }
    }
    load();
    return () => {
      active = false;
    };
  }, [profile.role]);

  const greetingHour = new Date().getHours();
  const greeting = greetingHour < 12 ? "Selamat pagi" : greetingHour < 17 ? "Selamat siang" : "Selamat malam";
  const items =
    profile.role === "bendahara"
      ? [
          ["Saldo Kas Kecil", "bi-wallet2", "blue", "kas"],
          ["Saldo Kas Besar", "bi-bank", "green", "kas"],
          ["Total Seluruh Kas", "bi-cash-coin", "purple", "kas"],
          [`Gaji ${new Intl.DateTimeFormat("id-ID", { month: "long" }).format(new Date())}`, "bi-cash-stack", "amber", "gaji"],
        ]
      : [
          ["Surat Masuk", "bi-inbox-fill", "blue", "surat_masuk"],
          ["Surat Keluar", "bi-send-fill", "green", "surat_keluar"],
          ["Surat Keputusan", "bi-file-earmark-text-fill", "amber", "sk"],
          ["Data Pegawai", "bi-people-fill", "purple", "pegawai"],
        ];

  return (
    <>
      <div className="e-page-header">
        <div>
          <div className="e-breadcrumb">
            <i className="bi bi-house-fill" />
            <span>Beranda</span>
            <span className="e-breadcrumb-sep">/</span>
            <span>Dashboard</span>
          </div>
          <h1 className="e-page-title">
            <div className="title-icon">
              <i className="bi bi-grid-1x2-fill" />
            </div>
            Dashboard
          </h1>
          <p className="e-page-sub">
            {greeting}, <strong>{profile?.nama_lengkap || profile?.username || "Pengguna"}</strong>. Berikut ringkasan sistem hari ini.
          </p>
        </div>
        <div className="e-date-badge">
          <i className="bi bi-calendar3" />
          {new Intl.DateTimeFormat("id-ID", { dateStyle: "long" }).format(new Date())}
        </div>
      </div>
      {error && (
        <div className="e-notice danger">
          <i className="bi bi-exclamation-triangle-fill" />
          <span>{error}</span>
        </div>
      )}
      <div className="row g-3 mb-4">
        {items.map(([title, icon, color, route], index) => (
          <Stat key={route} title={title} icon={icon} color={color} route={route} value={counts?.[index]} />
        ))}
      </div>
      <div className="e-card">
        <div className="e-card-header">
          <div className="e-card-title">
            <i className="bi bi-lightning-charge-fill" />
            Akses Cepat
          </div>
        </div>
        <div className="e-card-body">
          <div className="d-flex flex-wrap gap-2">
            {items.map(([title, icon, , route]) => (
              <a className="e-btn e-btn-ghost" href={`#/${route}`} key={route}>
                <i className={`bi ${icon}`} />
                {title}
              </a>
            ))}
          </div>
        </div>
      </div>
    </>
  );
}

function App() {
  const [session, setSession] = useState(null);
  const [profile, setProfile] = useState(null);
  const [route, setRoute] = useState(routeName());
  const [loading, setLoading] = useState(true);
  const [profileError, setProfileError] = useState("");
  const [profileRetry, setProfileRetry] = useState(0);

  useEffect(() => {
    document.documentElement.dataset.bsTheme = localStorage.getItem("earsip-theme") || "light";
    function onHashChange() {
      setRoute(routeName());
    }
    window.addEventListener("hashchange", onHashChange);
    if (!supabaseConfigured) {
      setLoading(false);
      return () => window.removeEventListener("hashchange", onHashChange);
    }
    supabase.auth.getSession().then(({ data }) => {
      setSession(data.session);
      setLoading(false);
    });
    const { data: listener } = supabase.auth.onAuthStateChange((_event, nextSession) => {
      setSession(nextSession);
      if (!nextSession) setProfile(null);
    });
    return () => {
      window.removeEventListener("hashchange", onHashChange);
      listener.subscription.unsubscribe();
    };
  }, []);

  useEffect(() => {
    if (!session) return;
    let active = true;
    supabase
      .from("profiles")
      .select("id, username, nama_lengkap, role, pegawai_id")
      .eq("id", session.user.id)
      .single()
      .then(({ data, error }) => {
        if (!active) return;
        if (error) {
          if (error.code === "PGRST116") {
            setProfileError("Akun Auth ditemukan, tetapi baris profil aplikasi belum ada. Jalankan migration 202609290002_auth_profile_provisioning.sql di Supabase SQL Editor, lalu keluar dan masuk kembali.");
          } else if (error.code === "42P01" || error.code === "PGRST205") {
            setProfileError("Tabel profiles belum tersedia. Jalankan supabase/schema.sql, lalu migration 202609290001_static_app_security.sql dan 202609290002_auth_profile_provisioning.sql secara berurutan.");
          } else {
            setProfileError(`Profil belum dapat dibaca (kode ${error.code || "tidak diketahui"}). Periksa migration RLS Supabase; bila baru diperbaiki, coba muat ulang profil.`);
          }
        } else {
          setProfile(data);
          setProfileError("");
        }
      });
    return () => {
      active = false;
    };
  }, [session, profileRetry]);

  async function logout() {
    await supabase.auth.signOut();
    go("login");
  }

  if (!supabaseConfigured)
    return (
      <div className="e-empty">
        <h1>Konfigurasi Supabase belum tersedia</h1>
        <p>Tambahkan VITE_SUPABASE_URL dan VITE_SUPABASE_PUBLISHABLE_KEY ke environment build.</p>
      </div>
    );
  if (loading) return <div className="e-empty">Memuat SIMPEG…</div>;
  if (!session) return <Login onSignedIn={setSession} />;
  if (profileError)
    return (
      <div className="e-empty">
        <h1>Akun belum terhubung</h1>
        <p>{profileError}</p>
        <button className="e-btn e-btn-primary" onClick={() => setProfileRetry((value) => value + 1)}>
          <i className="bi bi-arrow-clockwise" />
          Coba lagi
        </button>
        <button className="e-btn e-btn-ghost" onClick={logout}>
          Keluar
        </button>
      </div>
    );
  if (!profile) return <div className="e-empty">Memuat profil…</div>;

  const role = profile.role || "pegawai";
  if (role === "pegawai") return <EmployeePortal profile={profile} route={route} onLogout={logout} />;

  const allowedNavigation = navigation.filter((item) => item.roles.includes(role));
  const archiveRoutes = ["surat_masuk", "surat_keluar", "sk"];
  const financeRoutes = ["kas", "gaji"];
  const linkFor = (item, submenu = false) => (
    <a className={`${submenu ? "e-submenu-link" : "e-nav-link"} ${route === item.route ? "is-active" : ""}`} href={`#/${item.route}`} key={item.route}>
      {submenu ? (
        <i className={`bi ${item.icon}`} />
      ) : (
        <div className="nav-icon">
          <i className={`bi ${item.icon}`} />
        </div>
      )}
      <span>{item.label}</span>
    </a>
  );
  const groupOpen = (routes) => routes.includes(route);
  return (
    <>
      <header className="e-topbar">
        <button className="e-menu-btn" id="menuBtn" type="button" onClick={() => document.getElementById("sidebar")?.classList.toggle("is-open")}>
          <i className="bi bi-list" />
        </button>
        <a className="e-brand" href="#/dashboard" style={{ display: "flex", alignItems: "center", gap: ".65rem", textDecoration: "none" }}>
          <img src={logoUrl} alt="Logo STIENAS" style={{ height: 38, width: "auto" }} />
          <div>
            <div className="e-brand-name">SIMPEG</div>
            <div className="e-brand-tag">STIE Nasional Banjarmasin</div>
          </div>
        </a>
        <div className="e-topbar-search d-none d-md-flex">
          <i className="bi bi-search" />
          <span>Cari data pegawai, kas & arsip…</span>
          <span style={{ marginLeft: "auto", opacity: 0.6 }}>⌘K</span>
        </div>
        <div className="e-topbar-right">
          <button
            className="e-theme-btn"
            type="button"
            title="Toggle dark mode"
            onClick={() => {
              const next = document.documentElement.dataset.bsTheme === "dark" ? "light" : "dark";
              document.documentElement.dataset.bsTheme = next;
              localStorage.setItem("earsip-theme", next);
            }}
          >
            <i className={`bi ${document.documentElement.dataset.bsTheme === "dark" ? "bi-sun" : "bi-moon"}`} />
          </button>
          <div className="e-user-chip">
            <div className="e-avatar">{(profile.nama_lengkap || profile.username || "P").slice(0, 1).toUpperCase()}</div>
            <div>
              <div className="e-user-name">{profile.nama_lengkap || profile.username}</div>
              <div className="e-user-role">@{profile.username}</div>
            </div>
          </div>
          <button className="e-btn-logout-topbar" title="Keluar dari Sistem" onClick={logout}>
            <i className="bi bi-box-arrow-right" />
            <span className="d-none d-sm-inline">Logout</span>
          </button>
        </div>
      </header>
      <div className="e-overlay" id="overlay" onClick={() => document.getElementById("sidebar")?.classList.remove("is-open")} />
      <nav className="e-sidebar" id="sidebar">
        <div className="e-sidebar-body">
          <div className="e-sidebar-user">
            <div className="e-avatar-lg">{(profile.nama_lengkap || profile.username || "P").slice(0, 1).toUpperCase()}</div>
            <div style={{ minWidth: 0 }}>
              <div className="e-sidebar-user-name">{profile.nama_lengkap || profile.username}</div>
              <div className="e-sidebar-user-role">{role}</div>
            </div>
          </div>
          <div className="e-nav-label">Menu Utama</div>
          <ul className="e-nav">
            {allowedNavigation
              .filter((item) => ["dashboard", "pegawai", "absensi", "pengajuan_izin"].includes(item.route))
              .map((item) => (
                <li key={item.route}>{linkFor(item)}</li>
              ))}
            {allowedNavigation.some((item) => archiveRoutes.includes(item.route)) && (
              <li className={`e-nav-dropdown ${groupOpen(archiveRoutes) ? "is-open" : ""}`}>
                <button className={`e-nav-link e-nav-toggle ${groupOpen(archiveRoutes) ? "is-active" : ""}`} type="button" onClick={(event) => event.currentTarget.closest(".e-nav-dropdown")?.classList.toggle("is-open")}>
                  <span>
                    <span className="nav-icon">
                      <i className="bi bi-archive-fill" />
                    </span>
                    Arsip
                  </span>
                  <i className="bi bi-chevron-right arrow" />
                </button>
                <ul className="e-submenu">
                  {allowedNavigation
                    .filter((item) => archiveRoutes.includes(item.route))
                    .map((item) => (
                      <li key={item.route}>{linkFor(item, true)}</li>
                    ))}
                </ul>
              </li>
            )}
            {allowedNavigation.some((item) => financeRoutes.includes(item.route)) && (
              <li className={`e-nav-dropdown ${groupOpen(financeRoutes) ? "is-open" : ""}`}>
                <button className={`e-nav-link e-nav-toggle ${groupOpen(financeRoutes) ? "is-active" : ""}`} type="button" onClick={(event) => event.currentTarget.closest(".e-nav-dropdown")?.classList.toggle("is-open")}>
                  <span>
                    <span className="nav-icon">
                      <i className="bi bi-cash-coin" />
                    </span>
                    Keuangan
                  </span>
                  <i className="bi bi-chevron-right arrow" />
                </button>
                <ul className="e-submenu">
                  {allowedNavigation
                    .filter((item) => financeRoutes.includes(item.route))
                    .map((item) => (
                      <li key={item.route}>{linkFor(item, true)}</li>
                    ))}
                </ul>
              </li>
            )}
            {allowedNavigation
              .filter((item) => ["akun_pegawai", "akun_admin"].includes(item.route))
              .map((item) => (
                <li key={item.route}>{linkFor(item)}</li>
              ))}
          </ul>
        </div>
      </nav>
      <main className="e-main">
        {route === "dashboard" ? (
          <Dashboard profile={profile} />
        ) : route === "akun_pegawai" || route === "akun_admin" ? (
          <AccountPage route={route} profile={profile} />
        ) : route === "kas" || route === "gaji" ? (
          <FinancePage route={route} />
        ) : route === "kas_print" || route === "slip_gaji_print" ? (
          <FinancePrint route={route} profile={profile} />
        ) : resources[route] ? (
          <ResourcePage profile={profile} />
        ) : (
          <div className="e-empty">
            <h1>{allowedNavigation.find((item) => item.route === route)?.label || "Halaman tidak ditemukan"}</h1>
            <p>Halaman atau fitur ini belum tersedia.</p>
          </div>
        )}
      </main>
    </>
  );
}

export default App;
