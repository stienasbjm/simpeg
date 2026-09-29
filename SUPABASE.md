# Supabase dan GitHub Pages

Frontend SIMPEG memakai React/Vite dan Supabase JavaScript client. GitHub Pages hanya menyajikan file statis; PHP dan server Node lama tidak dijalankan.

## Setup database

Untuk project Supabase baru saja, jalankan `supabase/schema.sql` melalui SQL Editor. Jangan jalankan ulang file schema pada database yang tabelnya sudah ada.

Lalu jalankan berurutan:

1. `supabase/migrations/202609290001_static_app_security.sql`
2. `supabase/migrations/202609290002_auth_profile_provisioning.sql`

Migration pertama memasang RLS, Storage privat, dan RPC aplikasi. Kolom `profiles.pegawai_id` bertipe `BIGINT`, sama dengan `pegawai.id`. Migration kedua membuat profil role `pegawai` untuk akun Auth lama yang belum tertaut, dan menyiapkan trigger untuk akun baru. Migration itu tidak memberi role admin/developer secara otomatis.

Jika akun Auth sudah dibuat, jalankan query verifikasi berikut di SQL Editor:

```sql
SELECT auth_user.id, auth_user.email, profile.role, profile.pegawai_id
FROM auth.users AS auth_user
LEFT JOIN public.profiles AS profile ON profile.id = auth_user.id
WHERE lower(auth_user.email) = lower('email-anda@example.com');
```

Jika `role` bernilai `NULL`, pastikan migration kedua berhasil, lalu jalankan kembali migration kedua dan keluar/masuk kembali ke aplikasi. Untuk menetapkan super-admin pertama, lakukan secara eksplisit:

```sql
UPDATE public.profiles
SET role = 'developer'
WHERE email = 'email-anda@example.com';
```

Ganti alamat contoh dengan email yang tepat dari **Authentication > Users**. Data MySQL dan file lokal tidak dimigrasikan otomatis.

## Browser keys

Gunakan hanya:

- `VITE_SUPABASE_URL`: Project URL
- `VITE_SUPABASE_PUBLISHABLE_KEY`: publishable key dari Supabase Dashboard

Publishable key memang terlihat di bundle browser; keamanan data harus ditegakkan oleh RLS. Jangan pernah memasukkan `SUPABASE_SECRET_KEY`, service-role key, atau password database ke source frontend, `.env.local` untuk frontend, maupun variabel build `VITE_*`.

**Rotasi `SUPABASE_SECRET_KEY` yang sempat dibagikan di chat sekarang juga.** Jangan gunakan key tersebut di GitHub Pages. Edge Function memakai secret runtime Supabase yang tersedia di server.

## GitHub Pages

1. Di GitHub repository buka **Settings > Secrets and variables > Actions > Variables**.
2. Tambahkan `VITE_SUPABASE_URL` dan `VITE_SUPABASE_PUBLISHABLE_KEY` memakai nilai dari Supabase Dashboard.
3. Di **Settings > Pages**, pilih **GitHub Actions** sebagai source.
4. Push ke branch `main`; workflow `.github/workflows/pages.yml` membangun `dist` dan menerbitkannya.

## Edge Function

Manajemen akun memakai `supabase/functions/manage-account/index.ts`. Deploy dengan Supabase CLI:

```sh
supabase login
supabase link --project-ref ploutblidwenynluiudl
supabase functions deploy manage-account
```

Jangan mengirim atau menyimpan service-role/secret key di GitHub Pages. Simpan hanya pada runtime tepercaya Supabase.

## Pengembangan lokal

Salin `VITE_SUPABASE_URL` dan `VITE_SUPABASE_PUBLISHABLE_KEY` dari `.env.example` ke `.env.local`, lalu jalankan:

```sh
npm ci
npm run dev
```
