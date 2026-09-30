# SIMPEG - STIE Nasional Banjarmasin

Aplikasi web kepegawaian (SIMPEG) berbasis React + Vite dan Supabase (Auth, Database PostgreSQL via PostgREST, dan Storage), dirancang khusus untuk di-deploy secara statis di **GitHub Pages**.

## Pengembangan Lokal

1. Salin `.env.example` ke `.env.local` (atau gunakan `.env` yang sudah disiapkan):
   ```sh
   VITE_SUPABASE_URL=https://ploutblidwenynluiudl.supabase.co
   VITE_SUPABASE_PUBLISHABLE_KEY=sb_publishable_vFXBocNtcHsH973-IThzgQ_Busq65uT
   ```
2. Jalankan aplikasi:
   ```sh
   npm ci
   npm run dev
   ```
3. Buka browser pada alamat yang ditampilkan (misalnya `http://localhost:5173/`).

## Setup Supabase

Jalankan script SQL berikut secara berurutan di **Supabase Dashboard > SQL Editor**:

1. `supabase/schema.sql` (membuat tabel-tabel utama: pegawai, surat, absensi, kas, gaji, dll.)
2. `supabase/migrations/202609290001_static_app_security.sql` (membuat tabel profiles, RLS security policies, RPC functions, dan private storage bucket `simpeg-private`)
3. `supabase/migrations/202609290002_auth_profile_provisioning.sql` (membuat trigger otomatis profil saat user baru mendaftar)
4. Buat user pertama di **Authentication > Users** (misal: `admin@stienas-ypb.ac.id`), lalu jadikan developer (super-admin) dengan SQL:
   ```sql
   UPDATE public.profiles
   SET role = 'developer'
   WHERE email = 'admin@stienas-ypb.ac.id';
   ```

Lihat detail lengkap di [SUPABASE.md](SUPABASE.md).

## Deploy ke GitHub Pages

1. Pastikan repository di GitHub sudah memiliki remote yang benar (`origin`).
2. Di GitHub repository, buka **Settings > Pages**:
   - Di bagian **Build and deployment > Source**, pilih **GitHub Actions**.
3. (Opsional, sudah ada nilai bawaan) Di **Settings > Secrets and variables > Actions > Variables**, tambahkan:
   - `VITE_SUPABASE_URL`
   - `VITE_SUPABASE_PUBLISHABLE_KEY`
4. Lakukan `git push origin main`. Workflow GitHub Actions `.github/workflows/pages.yml` akan secara otomatis melakukan build dan mempublikasikan situs ke GitHub Pages.
