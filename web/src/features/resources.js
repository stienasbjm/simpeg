const text = (name, label, extra = {}) => ({ name, label, type: "text", ...extra });
const date = (name, label, extra = {}) => ({ name, label, type: "date", ...extra });
const money = (name, label) => ({ name, label, type: "number", step: "1000" });
const file = (name, label) => ({ name, label, type: "file" });
const rankOptions = [
  "I/a - Juru Muda",
  "I/b - Juru Muda Tingkat I",
  "I/c - Juru",
  "I/d - Juru Tingkat I",
  "II/a - Pengatur Muda",
  "II/b - Pengatur Muda Tingkat I",
  "II/c - Pengatur",
  "II/d - Pengatur Tingkat I",
  "III/a - Penata Muda",
  "III/b - Penata Muda Tingkat I",
  "III/c - Penata",
  "III/d - Penata Tingkat I",
  "IV/a - Pembina",
  "IV/b - Pembina Tingkat I",
  "IV/c - Pembina Utama Muda",
  "IV/d - Pembina Utama Madya",
  "IV/e - Pembina Utama",
  "Non-PNS / Belum Ada",
];

export const isLecturer = (status) =>
  String(status || "")
    .toLowerCase()
    .startsWith("dosen");

export function retirementAge(employee) {
  if (employee.status_kepegawaian === "Tenaga Kependidikan") return 58;
  if (isLecturer(employee.status_kepegawaian)) {
    const academicRank = String(employee.jabatan_fungsional || "").toLowerCase();
    return academicRank.includes("profesor") || academicRank.includes("guru besar") ? 70 : 65;
  }
  return null;
}

export function getRetirementDate(employee) {
  const age = retirementAge(employee);
  if (!age || !employee.tanggal_lahir) return "";
  const [year, month, day] = employee.tanggal_lahir.split("-").map(Number);
  const retirementYear = year + age;
  const retirementDay = Math.min(day, new Date(retirementYear, month, 0).getDate());
  return `${retirementYear}-${String(month).padStart(2, "0")}-${String(retirementDay).padStart(2, "0")}`;
}

export const resources = {
  pegawai: {
    table: "pegawai",
    title: "Data Pegawai",
    icon: "bi-people-fill",
    color: "purple",
    order: "nama",
    roles: ["admin", "developer"],
    search: ["nama", "nip", "status_kepegawaian", "kepangkatan", "pangkat_golongan", "jabatan_fungsional"],
    columns: [
      ["nama", "Nama Pegawai"],
      ["nip", "NIP/NIK"],
      ["status_kepegawaian", "Status Kepegawaian"],
      ["nidn_nuptk", "NIDN/NUPTK"],
      ["pangkat_golongan", "Pangkat / Golongan"],
      ["tmt_pangkat", "TMT Pangkat"],
      ["masa_pangkat", "Masa Pangkat"],
      ["jabatan_fungsional", "Jabatan Akademik Dosen"],
      ["tanggal_masuk_kerja", "Mulai Kerja"],
      ["masa_kerja", "Masa Kerja"],
      ["tanggal_pensiun", "Tanggal Pensiun"],
      ["status_pensiun", "Pemantauan Pensiun"],
    ],
    fields: [
      text("nama", "Nama Lengkap", { required: true }),
      text("nip", "NIP/NIK"),
      text("tempat_lahir", "Tempat Lahir"),
      date("tanggal_lahir", "Tanggal Lahir"),
      { name: "status_kepegawaian", label: "Status Kepegawaian", type: "select", options: ["Dosen", "Tenaga Kependidikan"], required: true },
      text("nidn_nuptk", "NIDN / NUPTK"),
      { name: "pangkat_golongan", label: "Pangkat / Golongan", type: "select", options: rankOptions },
      date("tmt_pangkat", "TMT Pangkat"),
      { name: "jabatan_fungsional", label: "Jabatan Akademik Dosen", type: "select", options: ["Tenaga Pendidik", "Asisten Ahli", "Lektor", "Lektor Kepala", "Guru Besar/Profesor"], required: true },
      date("tmt_jabatan", "TMT Jabatan"),
      { name: "sk_inpassing_2025", label: "SK Inpassing 2025", type: "checkbox" },
      { name: "ijazah", label: "Pendidikan / Ijazah", type: "select", options: ["S2 (Magister)", "S3 (Doktor)"] },
      date("tanggal_masuk_kerja", "Tanggal Masuk Kerja"),
      { name: "tanggal_pensiun", label: "Perkiraan Tanggal Pensiun", type: "computed-date" },
      file("foto", "Foto Pegawai"),
      file("file_ijazah", "File Ijazah"),
      file("file_kepangkatan", "File Kepangkatan"),
      file("file_jabatan_fungsional", "File Jabatan Fungsional"),
    ],
  },
  surat_masuk: {
    table: "surat_masuk",
    title: "Surat Masuk",
    icon: "bi-inbox-fill",
    color: "blue",
    order: "tanggal_diterima",
    descending: true,
    roles: ["admin", "developer"],
    search: ["nomor_surat", "pengirim", "perihal"],
    columns: [
      ["nomor_surat", "Nomor Surat"],
      ["tanggal_surat", "Tanggal Surat"],
      ["tanggal_diterima", "Tanggal Diterima"],
      ["pengirim", "Pengirim"],
      ["perihal", "Perihal"],
      ["file_url", "Tautan Dokumen"],
    ],
    fields: [
      text("nomor_surat", "Nomor Surat", { required: true }),
      date("tanggal_surat", "Tanggal Surat", { required: true }),
      date("tanggal_diterima", "Tanggal Diterima", { required: true }),
      text("pengirim", "Pengirim", { required: true }),
      text("perihal", "Perihal", { required: true }),
      text("file_url", "Tautan Dokumen (URL)", { type: "url", placeholder: "https://..." }),
    ],
  },
  surat_keluar: {
    table: "surat_keluar",
    title: "Surat Keluar",
    icon: "bi-send-fill",
    color: "green",
    order: "tanggal_surat",
    descending: true,
    roles: ["admin", "developer"],
    search: ["nomor_surat", "tujuan", "perihal"],
    columns: [
      ["nomor_surat", "Nomor Surat"],
      ["tanggal_surat", "Tanggal Surat"],
      ["tujuan", "Tujuan"],
      ["perihal", "Perihal"],
      ["file_url", "Tautan Dokumen"],
    ],
    fields: [
      text("nomor_surat", "Nomor Surat", { required: true }),
      date("tanggal_surat", "Tanggal Surat", { required: true }),
      text("tujuan", "Tujuan", { required: true }),
      text("perihal", "Perihal", { required: true }),
      text("file_url", "Tautan Dokumen (URL)", { type: "url", placeholder: "https://..." }),
    ],
  },
  sk: {
    table: "surat_keputusan",
    title: "Surat Keputusan (SK)",
    icon: "bi-file-earmark-text-fill",
    color: "amber",
    order: "tanggal_sk",
    descending: true,
    roles: ["admin", "developer"],
    search: ["nomor_sk", "tentang"],
    columns: [
      ["nomor_sk", "Nomor SK"],
      ["tanggal_sk", "Tanggal SK"],
      ["tentang", "Tentang"],
      ["file_url", "Tautan Dokumen"],
    ],
    fields: [
      text("nomor_sk", "Nomor SK", { required: true }),
      date("tanggal_sk", "Tanggal SK", { required: true }),
      text("tentang", "Tentang", { required: true }),
      text("file_url", "Tautan Dokumen (URL)", { type: "url", placeholder: "https://..." }),
    ],
  },
  absensi: {
    table: "absensi",
    select: "*, pegawai(nama, nip)",
    title: "Absensi Pegawai",
    icon: "bi-calendar2-check-fill",
    color: "blue",
    order: "tanggal",
    descending: true,
    roles: ["admin", "developer", "bendahara"],
    search: ["pegawai_id", "tanggal", "status", "keterangan", "pegawai.nama", "pegawai.nip"],
    columns: [
      ["pegawai_id", "Nama Pegawai"],
      ["tanggal", "Tanggal"],
      ["jam_masuk", "Jam Masuk"],
      ["jam_keluar", "Jam Keluar"],
      ["status", "Status"],
      ["keterangan", "Keterangan"],
    ],
    fields: [
      { name: "pegawai_id", label: "Pegawai", type: "relation", relation: { table: "pegawai", label: "nama", secondary: "nip" }, required: true },
      date("tanggal", "Tanggal", { required: true }),
      { name: "jam_masuk", label: "Jam Masuk", type: "time" },
      { name: "jam_keluar", label: "Jam Keluar", type: "time" },
      { name: "status", label: "Status", type: "select", options: ["hadir", "tidak_hadir", "izin", "sakit", "cuti"] },
      text("keterangan", "Keterangan"),
    ],
  },
  pengajuan_izin: {
    table: "pengajuan_izin",
    title: "Pengajuan Izin",
    icon: "bi-clipboard-check-fill",
    color: "amber",
    order: "created_at",
    descending: true,
    roles: ["admin", "developer"],
    search: ["pegawai_id", "tanggal", "jenis", "status", "keterangan"],
    columns: [
      ["pegawai_id", "ID Pegawai"],
      ["tanggal", "Tanggal"],
      ["jenis", "Jenis"],
      ["status", "Status"],
      ["keterangan", "Keterangan"],
    ],
    fields: [
      text("pegawai_id", "ID Pegawai", { required: true }),
      date("tanggal", "Tanggal", { required: true }),
      { name: "jenis", label: "Jenis", type: "select", options: ["izin", "sakit", "dinas"] },
      text("keterangan", "Keterangan", { required: true }),
      file("file_bukti", "File Bukti"),
    ],
  },
  kas: {
    table: "kas_transaksi",
    title: "Arus Kas (Kecil & Besar)",
    icon: "bi-wallet2",
    color: "green",
    order: "tanggal",
    descending: true,
    roles: ["developer", "bendahara"],
    search: ["jenis_kas", "tipe", "kategori", "keterangan"],
    columns: [
      ["tanggal", "Tanggal"],
      ["jenis_kas", "Jenis Kas"],
      ["tipe", "Tipe"],
      ["kategori", "Kategori"],
      ["jumlah", "Jumlah"],
    ],
    fields: [
      date("tanggal", "Tanggal", { required: true }),
      { name: "jenis_kas", label: "Jenis Kas", type: "select", options: ["kas_kecil", "kas_besar"] },
      { name: "tipe", label: "Tipe", type: "select", options: ["pemasukan", "pengeluaran"] },
      text("kategori", "Kategori", { required: true }),
      text("keterangan", "Keterangan"),
      money("jumlah", "Jumlah (Rp)"),
    ],
  },
  gaji: {
    table: "gaji",
    title: "Penggajian & Slip Gaji",
    icon: "bi-cash-stack",
    color: "amber",
    order: "tahun",
    descending: true,
    roles: ["developer", "bendahara"],
    search: ["pegawai_id", "bulan", "tahun", "catatan"],
    columns: [
      ["pegawai_id", "ID Pegawai"],
      ["bulan", "Bulan"],
      ["tahun", "Tahun"],
      ["gaji_pokok", "Gaji Pokok"],
      ["total_gaji", "Total Gaji"],
    ],
    fields: [
      text("pegawai_id", "ID Pegawai", { required: true }),
      { name: "bulan", label: "Bulan", type: "number", min: 1, max: 12, required: true },
      { name: "tahun", label: "Tahun", type: "number", required: true },
      money("gaji_pokok", "Gaji Pokok"),
      money("tunj_fungsional", "Tunjangan Fungsional"),
      money("tunj_struktural", "Tunjangan Struktural"),
      money("tunj_kesejahteraan", "Tunjangan Kesejahteraan"),
      money("tunj_kawin", "Tunjangan Kawin"),
      money("tunj_anak", "Tunjangan Anak"),
      money("tunj_beras", "Tunjangan Beras"),
      money("tunjangan_makan", "Tunjangan Makan"),
      money("tunjangan_lain", "Tunjangan Lain"),
      money("pot_bpjs_tk", "Potongan BPJS TK"),
      money("pot_bpjs_kes", "Potongan BPJS Kesehatan"),
      money("pot_koperasi", "Potongan Koperasi"),
      money("potongan", "Potongan Lain"),
      money("total_gaji", "Total Gaji"),
      text("catatan", "Catatan"),
    ],
  },
};

export const formatValue = (value, field = "") => {
  if (value === null || value === undefined || value === "") return "—";
  if (field.includes("tanggal") || field === "tanggal_sk") return new Intl.DateTimeFormat("id-ID").format(new Date(`${value}T00:00:00`));
  if (
    [
      "jumlah",
      "gaji_pokok",
      "total_gaji",
      "tunj_kawin",
      "tunj_anak",
      "tunj_beras",
      "tunj_fungsional",
      "tunj_struktural",
      "tunj_kesejahteraan",
      "tunjangan_makan",
      "tunjangan_lain",
      "pot_bpjs_tk",
      "pot_bpjs_kes",
      "pot_koperasi",
      "potongan",
    ].includes(field)
  )
    return `Rp ${Number(value).toLocaleString("id-ID")}`;
  return String(value);
};
