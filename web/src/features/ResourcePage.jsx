import { useEffect, useMemo, useState } from "react";
import { supabase } from "../supabase.js";
import { confirmAction, promptText, showAlert } from "../alerts.js";
import { formatValue, getRetirementDate, isLecturer, resources } from "./resources.js";

function durationSince(value) {
  if (!value) return "—";
  const start = new Date(`${value}T00:00:00`);
  const today = new Date();
  let months = (today.getFullYear() - start.getFullYear()) * 12 + today.getMonth() - start.getMonth();
  if (today.getDate() < start.getDate()) months -= 1;
  if (months < 0) return "Belum dimulai";
  const years = Math.floor(months / 12);
  const remainingMonths = months % 12;
  return [years ? `${years} th` : "", remainingMonths ? `${remainingMonths} bln` : ""].filter(Boolean).join(" ") || "0 bln";
}

function retirementStatus(value) {
  if (!value) return { label: "Data lahir/status belum lengkap", color: "" };
  const retirementDate = new Date(`${value}T00:00:00`);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const days = Math.ceil((retirementDate - today) / 86400000);
  if (days < 0) return { label: "Lewat tanggal pensiun", color: "red" };
  if (days <= 730) return { label: "Mendekati (≤ 2 tahun)", color: "amber" };
  return { label: `${Math.ceil(days / 365)} tahun lagi`, color: "green" };
}

function resourceValue(row, key) {
  if (key === "masa_kerja") return durationSince(row.tanggal_masuk_kerja);
  if (key === "masa_pangkat") return durationSince(row.tmt_pangkat);
  if (key === "tanggal_pensiun") return getRetirementDate(row);
  if (key === "status_pensiun") return retirementStatus(getRetirementDate(row)).label;
  if (key === "pegawai_id" && row.pegawai) {
    const employee = Array.isArray(row.pegawai) ? row.pegawai[0] : row.pegawai;
    if (employee?.nama) return employee.nama;
  }
  if (key.includes(".")) return key.split(".").reduce((value, part) => value?.[part], row) ?? "";
  return row[key] ?? "";
}

function externalUrl(value) {
  try {
    const url = new URL(value);
    return ["http:", "https:"].includes(url.protocol) ? url.href : null;
  } catch {
    return null;
  }
}

function exportRows(resource, rows) {
  const quote = (value) => `"${String(value ?? "").replaceAll('"', '""')}"`;
  const lines = [resource.columns.map(([, label]) => label), ...rows.map((row) => resource.columns.map(([key]) => resourceValue(row, key)))];
  const csv = lines.map((line) => line.map(quote).join(";")).join("\r\n");
  const url = URL.createObjectURL(new Blob([`\uFEFF${csv}`], { type: "text/csv;charset=utf-8" }));
  const link = document.createElement("a");
  link.href = url;
  link.download = `${resource.table}.csv`;
  link.click();
  URL.revokeObjectURL(url);
}

const routeInfo = () => {
  const [route, query = ""] = window.location.hash.slice(1).split("?");
  const params = new URLSearchParams(query);
  const match = route.match(/^\/?(.+?)(?:_(add|edit|detail))?$/);
  return { base: match?.[1] || "dashboard", mode: match?.[2] || "list", id: params.get("id") };
};

function PageHeader({ resource, action, onPrint }) {
  return (
    <div className="e-page-header">
      <div>
        <div className="e-breadcrumb">
          <i className="bi bi-house-fill" />
          <a href="#/dashboard">Beranda</a>
          <span className="e-breadcrumb-sep">/</span>
          <span>{resource.title}</span>
        </div>
        <h1 className="e-page-title">
          <div className="title-icon">
            <i className={`bi ${resource.icon}`} />
          </div>
          {action ? `${action} ${resource.title}` : resource.title}
        </h1>
        <p className="e-page-sub">Kelola data {resource.title.toLowerCase()} instansi.</p>
      </div>
      {!action && (
        <div className="d-flex gap-2">
          <button className="e-btn e-btn-ghost print-hidden" type="button" onClick={onPrint}>
            <i className="bi bi-printer-fill" />
            Cetak
          </button>
          <a className="e-btn e-btn-primary print-hidden" href={`#/${resource.route}_add`}>
            <i className="bi bi-plus-lg" />
            Tambah Data
          </a>
        </div>
      )}
    </div>
  );
}

function ResourceForm({ resource, mode, id, onDone }) {
  const [values, setValues] = useState({});
  const [files, setFiles] = useState({});
  const [relationOptions, setRelationOptions] = useState({});
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    if (mode !== "edit" && mode !== "detail") return;
    let active = true;
    supabase
      .from(resource.table)
      .select("*")
      .eq("id", id)
      .single()
      .then(({ data, error: queryError }) => {
        if (!active) return;
        if (queryError) setError(queryError.message);
        else setValues(data || {});
      });
    return () => {
      active = false;
    };
  }, [resource, mode, id]);

  useEffect(() => {
    const relationFields = resource.fields.filter((field) => field.type === "relation");
    if (!relationFields.length) return;
    let active = true;
    Promise.all(
      relationFields.map(async (field) => {
        const { data, error: relationError } = await supabase.from(field.relation.table).select("id,nama,nip").order(field.relation.label);
        return [field.name, data || [], relationError];
      }),
    ).then((results) => {
      if (!active) return;
      setRelationOptions(Object.fromEntries(results.map(([name, options]) => [name, options])));
      const failed = results.find(([, , relationError]) => relationError);
      if (failed) setError(failed[2].message);
    });
    return () => {
      active = false;
    };
  }, [resource]);

  async function save(event) {
    event.preventDefault();
    setBusy(true);
    setError("");
    const payload = {};
    for (const field of resource.fields) {
      if (field.type === "file" || field.type === "computed-date") continue;
      const value = values[field.name];
      if (field.type === "checkbox") payload[field.name] = value ? 1 : 0;
      else if (field.type === "number" || field.type === "relation") payload[field.name] = value === "" || value == null ? null : Number(value);
      else payload[field.name] = value === "" ? null : value;
    }
    if (resource.table === "pegawai") {
      payload.tanggal_pensiun = getRetirementDate(values) || null;
      if (values.status_kepegawaian === "Tenaga Kependidikan") {
        payload.jabatan_fungsional = null;
        payload.nidn_nuptk = null;
      }
    }

    const uploaded = [];
    for (const [fieldName, selectedFile] of Object.entries(files)) {
      if (!selectedFile) continue;
      const path = `records/${resource.table}/${crypto.randomUUID()}-${selectedFile.name.replace(/[^a-zA-Z0-9._-]/g, "_")}`;
      const { error: uploadError } = await supabase.storage.from("simpeg-private").upload(path, selectedFile, { upsert: false });
      if (uploadError) {
        await Promise.all(uploaded.map((item) => supabase.storage.from("simpeg-private").remove([item.path])));
        setBusy(false);
        setError(uploadError.message);
        return;
      }
      uploaded.push({ fieldName, path });
      payload[fieldName] = path;
    }

    const result = mode === "edit" ? await supabase.from(resource.table).update(payload).eq("id", id) : await supabase.from(resource.table).insert(payload);
    if (result.error) {
      await Promise.all(uploaded.map((item) => supabase.storage.from("simpeg-private").remove([item.path])));
      setError(result.error.message);
      setBusy(false);
      return;
    }
    setBusy(false);
    onDone();
  }

  async function signedUrl(path) {
    const { data } = await supabase.storage.from("simpeg-private").createSignedUrl(path, 60);
    return data?.signedUrl;
  }

  return (
    <>
      <PageHeader resource={{ ...resource, route: resource.route }} action={mode === "detail" ? "Detail" : mode === "edit" ? "Edit" : "Tambah"} />
      {error && (
        <div className="e-notice danger">
          <i className="bi bi-exclamation-triangle-fill" />
          <span>{error}</span>
        </div>
      )}
      {mode === "detail" ? (
        <div className="e-card">
          <div className="e-card-body">
            <dl className="row mb-0">
              {resource.fields
                .filter((field) => field.type !== "file" && (!["jabatan_fungsional", "nidn_nuptk"].includes(field.name) || isLecturer(values.status_kepegawaian)))
                .map((field) => (
                  <div className="col-md-6 mb-3" key={field.name}>
                    <dt>{field.label}</dt>
                    <dd>
                      {field.type === "computed-date" ? (
                        formatValue(resourceValue(values, field.name), field.name)
                      ) : field.type === "relation" ? (
                        relationOptions[field.name]?.find((option) => String(option.id) === String(values[field.name]))?.[field.relation.label] || formatValue(values[field.name], field.name)
                      ) : field.name === "file_url" && externalUrl(values[field.name]) ? (
                        <a href={externalUrl(values[field.name])} target="_blank" rel="noreferrer">
                          Buka tautan dokumen
                        </a>
                      ) : (
                        formatValue(values[field.name], field.name)
                      )}
                    </dd>
                  </div>
                ))}
              {resource.fields
                .filter((field) => field.type === "file" && values[field.name])
                .map((field) => (
                  <div className="col-md-6 mb-3" key={field.name}>
                    <dt>{field.label}</dt>
                    <dd>
                      <button
                        className="e-btn e-btn-ghost"
                        onClick={async () => {
                          const url = await signedUrl(values[field.name]);
                          if (url) window.open(url, "_blank", "noopener");
                        }}
                      >
                        <i className="bi bi-download" />
                        Buka file
                      </button>
                    </dd>
                  </div>
                ))}
            </dl>
            <a className="e-btn e-btn-ghost" href={`#/${resource.route}`}>
              <i className="bi bi-arrow-left" />
              Kembali
            </a>
          </div>
        </div>
      ) : (
        <form className="e-card" onSubmit={save}>
          <div className="e-card-body">
            <div className="row g-3">
              {resource.fields
                .filter((field) => !["jabatan_fungsional", "nidn_nuptk"].includes(field.name) || isLecturer(values.status_kepegawaian))
                .map((field) => (
                  <div className={field.type === "textarea" ? "col-12" : "col-md-6"} key={field.name}>
                    <label className="e-label" htmlFor={field.name}>
                      {field.label}
                      {field.required && <span className="req"> *</span>}
                    </label>
                    {field.type === "file" ? (
                      <input className="e-input" type="file" id={field.name} accept=".pdf,.jpg,.jpeg,.png,.webp" onChange={(event) => setFiles({ ...files, [field.name]: event.target.files?.[0] })} />
                    ) : field.type === "computed-date" ? (
                      <>
                        <input className="e-input" type="date" id={field.name} value={getRetirementDate(values)} readOnly />
                        <small className="text-muted">Dihitung otomatis dari tanggal lahir dan status/jabatan.</small>
                      </>
                    ) : field.type === "relation" ? (
                      <select className="e-select" id={field.name} value={values[field.name] ?? ""} required={field.required} onChange={(event) => setValues({ ...values, [field.name]: event.target.value })}>
                        <option value="">Pilih {field.label}</option>
                        {(relationOptions[field.name] || []).map((option) => (
                          <option key={option.id} value={option.id}>
                            {[option[field.relation.label], option[field.relation.secondary]].filter(Boolean).join(" · ")}
                          </option>
                        ))}
                      </select>
                    ) : field.type === "select" ? (
                      <select
                        className="e-select"
                        id={field.name}
                        value={values[field.name] ?? ""}
                        required={field.required}
                        onChange={(event) => {
                          const value = event.target.value;
                          setValues((current) => ({
                            ...current,
                            [field.name]: value,
                            ...(field.name === "status_kepegawaian" && value === "Tenaga Kependidikan" ? { jabatan_fungsional: "", nidn_nuptk: "" } : {}),
                          }));
                        }}
                      >
                        <option value="">Pilih {field.label}</option>
                        {values[field.name] && !field.options.includes(values[field.name]) && <option value={values[field.name]}>{values[field.name]} (data lama)</option>}
                        {field.options.map((option) => (
                          <option key={option} value={option}>
                            {option}
                          </option>
                        ))}
                      </select>
                    ) : field.type === "checkbox" ? (
                      <div className="form-check mt-2">
                        <input className="form-check-input" type="checkbox" id={field.name} checked={Boolean(values[field.name])} onChange={(event) => setValues({ ...values, [field.name]: event.target.checked })} />
                        <label className="form-check-label" htmlFor={field.name}>
                          {field.label}
                        </label>
                      </div>
                    ) : (
                      <input
                        className="e-input"
                        type={field.type}
                        id={field.name}
                        step={field.step}
                        min={field.min}
                        max={field.max}
                        required={field.required}
                        placeholder={field.placeholder}
                        value={values[field.name] ?? ""}
                        onChange={(event) => setValues({ ...values, [field.name]: event.target.value })}
                      />
                    )}
                  </div>
                ))}
            </div>
          </div>
          <div className="e-card-footer e-resource-form-footer d-flex gap-2">
            <button className="e-btn e-btn-primary" type="submit" disabled={busy}>
              <i className="bi bi-save" />
              {busy ? "Menyimpan…" : "Simpan"}
            </button>
            <a className="e-btn e-btn-ghost" href={`#/${resource.route}`}>
              Batal
            </a>
          </div>
        </form>
      )}
    </>
  );
}

export default function ResourcePage({ profile }) {
  const info = routeInfo();
  const resource = resources[info.base];
  const [rows, setRows] = useState([]);
  const [filter, setFilter] = useState("");
  const [busy, setBusy] = useState(true);
  const [error, setError] = useState("");
  const [refresh, setRefresh] = useState(0);

  useEffect(() => {
    if (!resource || info.mode !== "list") return;
    let active = true;
    resource.route = info.base;
    setBusy(true);
    supabase
      .from(resource.table)
      .select(resource.select || "*")
      .order(resource.order, { ascending: !resource.descending })
      .limit(300)
      .then(({ data, error: queryError }) => {
        if (!active) return;
        setRows(data || []);
        setError(queryError?.message || "");
        setBusy(false);
      });
    return () => {
      active = false;
    };
  }, [resource, info.base, info.mode, refresh]);

  const visibleRows = useMemo(() => rows.filter((row) => resource?.search.some((key) => String(resourceValue(row, key)).toLowerCase().includes(filter.toLowerCase()))), [rows, resource, filter]);

  if (!resource)
    return (
      <div className="e-empty">
        <h1>Halaman tidak ditemukan</h1>
        <a href="#/dashboard" className="e-btn e-btn-ghost">
          Kembali ke dashboard
        </a>
      </div>
    );
  if (!resource.roles.includes(profile.role))
    return (
      <div className="e-notice danger">
        <i className="bi bi-shield-lock-fill" />
        Akses tidak tersedia untuk role akun ini.
      </div>
    );
  resource.route = info.base;
  if (info.mode !== "list") return <ResourceForm resource={resource} mode={info.mode} id={info.id} onDone={() => (window.location.hash = `/${info.base}`)} />;

  async function remove(row) {
    if (!(await confirmAction("Hapus data?", `Data ${resource.title.toLowerCase()} ini akan dihapus permanen.`, "Ya, hapus"))) return;
    const { error: deleteError } = await supabase.from(resource.table).delete().eq("id", row.id);
    if (deleteError) {
      setError(deleteError.message);
      await showAlert("Data gagal dihapus", deleteError.message, "error");
    } else setRefresh((value) => value + 1);
  }

  async function review(row, status) {
    const note = status === "ditolak" ? await promptText("Tolak pengajuan", "Catatan penolakan:", "Tolak") : "";
    if (status === "ditolak" && note === null) return;
    const { error: reviewError } = await supabase.rpc("review_leave", { p_request_id: row.id, p_status: status, p_note: note || "" });
    if (reviewError) {
      setError(reviewError.message);
      await showAlert("Status gagal diperbarui", reviewError.message, "error");
    } else setRefresh((value) => value + 1);
  }

  return (
    <>
      <PageHeader resource={resource} onPrint={() => window.print()} />
      {error && (
        <div className="e-notice danger">
          <i className="bi bi-exclamation-triangle-fill" />
          <span>{error}</span>
        </div>
      )}
      <div className="e-table-wrap">
        <div className="d-flex justify-content-between align-items-center flex-wrap gap-3 p-3" style={{ background: "var(--bg-muted)", borderBottom: "1px solid var(--border-light)" }}>
          <div className="e-card-title">
            <i className={`bi ${resource.icon}`} />
            Daftar {resource.title}
            <span className={`e-badge ${resource.color}`}>{visibleRows.length}</span>
          </div>
          <div className="e-search print-hidden">
            <i className="bi bi-search e-search-icon" />
            <input className="e-input" type="search" placeholder={`Cari ${resource.title.toLowerCase()}…`} value={filter} onChange={(event) => setFilter(event.target.value)} />
          </div>
          <button className="e-btn e-btn-ghost print-hidden" type="button" onClick={() => exportRows(resource, visibleRows)}>
            <i className="bi bi-file-earmark-spreadsheet" />
            Export CSV
          </button>
        </div>
        <div style={{ overflowX: "auto" }}>
          <table className="e-table">
            <thead>
              <tr>
                <th style={{ width: 44 }}>#</th>
                {resource.columns.map(([key, label]) => (
                  <th key={key}>{label}</th>
                ))}
                <th style={{ textAlign: "right" }}>Aksi</th>
              </tr>
            </thead>
            <tbody>
              {busy ? (
                <tr>
                  <td colSpan={resource.columns.length + 2}>
                    <div className="e-empty">Memuat data…</div>
                  </td>
                </tr>
              ) : visibleRows.length === 0 ? (
                <tr>
                  <td colSpan={resource.columns.length + 2}>
                    <div className="e-empty">
                      <i className="bi bi-inbox" />
                      <p>Belum ada data.</p>
                    </div>
                  </td>
                </tr>
              ) : (
                visibleRows.map((row, index) => (
                  <tr key={row.id ?? row.key_name}>
                    <td>{index + 1}</td>
                    {resource.columns.map(([key]) => (
                      <td key={key}>
                        {key === "status_pensiun" ? (
                          <span className={`e-badge ${retirementStatus(row.tanggal_pensiun).color}`}>{resourceValue(row, key)}</span>
                        ) : key === "file_url" && externalUrl(row[key]) ? (
                          <a href={externalUrl(row[key])} target="_blank" rel="noreferrer" aria-label="Buka tautan dokumen">
                            Buka tautan
                          </a>
                        ) : (
                          formatValue(resourceValue(row, key), key)
                        )}
                      </td>
                    ))}
                    <td>
                      <div className="d-flex gap-2 justify-content-end">
                        <a className="e-act-btn view" href={`#/${info.base}_detail?id=${row.id}`} title="Detail">
                          <i className="bi bi-eye-fill" />
                        </a>
                        <a className="e-act-btn edit" href={`#/${info.base}_edit?id=${row.id}`} title="Edit">
                          <i className="bi bi-pencil-fill" />
                        </a>
                        {info.base === "pengajuan_izin" && row.status === "menunggu" && (
                          <>
                            <button className="e-act-btn edit" title="Setujui" onClick={() => review(row, "disetujui")}>
                              <i className="bi bi-check-lg" />
                            </button>
                            <button className="e-act-btn del" title="Tolak" onClick={() => review(row, "ditolak")}>
                              <i className="bi bi-x-lg" />
                            </button>
                          </>
                        )}
                        <button className="e-act-btn del" title="Hapus" onClick={() => remove(row)}>
                          <i className="bi bi-trash3-fill" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </>
  );
}
