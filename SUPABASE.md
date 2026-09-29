# Deploy SIMPEG Statis

Frontend baru menggunakan React/Vite dan Supabase JavaScript client. GitHub Pages hanya menjalankan file statis; folder PHP lama tidak dijalankan oleh Pages. Desain memakai `public/css/style.css` dan aset gambar yang sudah ada.

## Siapkan Supabase

1. Pada project Supabase yang masih kosong, jalankan `supabase/schema.sql` melalui SQL Editor.
2. Jalankan `supabase/migrations/202609290001_static_app_security.sql` setelah skema selesai dibuat. Migrasi ini memasang RLS, profil Auth, Storage privat, dan RPC untuk absensi, izin, serta pembaruan profil.
3. Buat user pertama di **Authentication > Users**. Lalu tautkan user itu sebagai developer melalui SQL Editor, ganti email dan username pada query:

```sql
INSERT INTO public.profiles (id, email, username, nama_lengkap, role)
SELECT id, email, 'developer', 'Developer', 'developer'
FROM auth.users
WHERE email = 'email-developer-anda@example.com';
```

Password/hash dari tabel `users` PHP lama tidak dipakai oleh Supabase Auth. Buat ulang akun melalui menu manajemen akun setelah developer pertama tersedia. Data MySQL dan berkas lokal juga tidak berpindah otomatis; migrasikan data pegawai terlebih dahulu sebelum membuat profil akun pegawai.

## Deploy Edge Function

Menu manajemen akun memakai `supabase/functions/manage-account/index.ts`. Deploy dengan Supabase CLI:

```sh
supabase login
supabase link --project-ref ploutblidwenynluiudl
supabase functions deploy manage-account
```

Function memeriksa JWT dan role pemanggil. `SUPABASE_SERVICE_ROLE_KEY` hanya digunakan oleh runtime Edge Function; jangan tambahkan key tersebut ke `.env`, GitHub Variables, source frontend, atau artifact Pages.

## Konfigurasi GitHub Pages

1. Di repository GitHub, buka **Settings > Secrets and variables > Actions > Variables**. Tambahkan `VITE_SUPABASE_URL` berisi Project URL dan `VITE_SUPABASE_ANON_KEY` berisi public anon/publishable key dari Supabase Dashboard.
2. Buka **Settings > Pages**, pilih source **GitHub Actions**.
3. Push ke branch `main`. Workflow `.github/workflows/pages.yml` menjalankan `npm ci`, build Vite, lalu menerbitkan `dist`.

Anon key memang terlihat di browser dan bukan password database. Keamanan data bergantung pada RLS dari langkah pertama. Jangan pernah memakai `service_role` key di browser.

## Pengembangan Lokal

Salin baris `VITE_SUPABASE_URL` dan `VITE_SUPABASE_ANON_KEY` dari `.env.example` ke `.env.local`, lalu jalankan:

```sh
npm install
npm run dev
```

Hash routing (`#/dashboard`) dipakai agar route bekerja pada GitHub Pages tanpa server PHP atau rewrite Apache. Build yang diterbitkan berada pada `dist/`.
