# SIMPEG

Aplikasi statis React/Vite untuk GitHub Pages dengan Supabase Auth, PostgreSQL API, dan Storage. UI memakai stylesheet lama pada `public/css/style.css` dan aset merek di `public/images/`.

## Jalankan lokal

```sh
npm ci
npm run dev
```

Atur `VITE_SUPABASE_URL` dan `VITE_SUPABASE_ANON_KEY` pada `.env.local`.

## Siapkan Supabase

1. Jalankan `supabase/schema.sql` pada project baru.
2. Jalankan `supabase/migrations/202609290001_static_app_security.sql`.
3. Buat user pertama dari Supabase Dashboard > Authentication > Users.
4. Tautkan user pertama sebagai role `developer` memakai query di [SUPABASE.md](SUPABASE.md). Role `developer` adalah super-admin aplikasi.
5. Deploy Edge Function `manage-account` mengikuti panduan Supabase.

Tidak ada kredensial default. Buat password sendiri di Supabase Auth. Jangan gunakan password/API service-role key dalam source, issue, atau chat.

## GitHub Pages

Tambahkan repository Actions Variables `VITE_SUPABASE_URL` dan `VITE_SUPABASE_ANON_KEY`, lalu atur **Settings > Pages > Source: GitHub Actions**. Push ke `main` akan menjalankan `.github/workflows/pages.yml`.

Supabase anon key bersifat public dan dibundel ke browser; akses data wajib dibatasi oleh RLS. `service_role` hanya boleh berada di Supabase Edge Function. Folder `public/uploads/` lokal diabaikan Git untuk mencegah foto/dokumen pegawai terpublikasi.

Build statis tersedia di `dist/` setelah `npm run build`.
