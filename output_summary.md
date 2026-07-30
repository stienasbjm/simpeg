Saya telah menyelesaikan pembangunan aplikasi arsip digital sesuai dengan semua permintaan Anda.

**Ringkasan Fitur yang Telah Diimplementasikan:**
*   **Struktur Proyek:** Folder terorganisir dengan baik untuk konfigurasi, publik, sumber kode, dan template.
*   **Database:** Skema database lengkap (`users`, `pegawai`, `surat_masuk`, `surat_keluar`, `surat_keputusan`) telah dibuat dalam file `database.sql`.
*   **Tata Letak Modern & Responsif:** Menggunakan Bootstrap 5 untuk desain yang modern, responsif, dan mudah digunakan di berbagai perangkat.
*   **Mode Gelap (Dark Mode):** Fungsionalitas mode gelap yang dapat diaktifkan/dinonaktifkan oleh pengguna, dengan preferensi yang disimpan.
*   **Sistem Autentikasi:** Halaman login dan proses logout yang berfungsi penuh, serta perlindungan rute untuk memastikan hanya pengguna yang terautentikasi yang dapat mengakses halaman aplikasi.
*   **Dashboard:** Halaman dashboard yang menampilkan ringkasan data secara dinamis dari setiap modul.
*   **Modul CRUD Lengkap:**
    *   **Data Pegawai:** Fungsi Tambah, Lihat Daftar, Edit, Hapus, dan Lihat Detail, termasuk kemampuan upload file (Ijazah, SK Kepangkatan, SK Jabatan Fungsional).
    *   **Surat Masuk:** Fungsi Tambah, Lihat Daftar, Edit, Hapus, dan Lihat Detail, termasuk kemampuan upload file surat.
    *   **Surat Keluar:** Fungsi Tambah, Lihat Daftar, Edit, Hapus, dan Lihat Detail, termasuk kemampuan upload file surat.
    *   **Surat Keputusan (SK):** Fungsi Tambah, Lihat Daftar, Edit, Hapus, dan Lihat Detail, termasuk kemampuan upload file SK.

**Informasi Database dan Login Admin:**

**1. Penyiapan Database:**
   Anda perlu mengimpor file `database.sql` yang saya sediakan ke dalam server MySQL/MariaDB Anda. File ini akan membuat database bernama `arsip_digital` dan semua tabel yang diperlukan.

**2. Akun Admin:**
   Setelah Anda mengimpor `database.sql`, sebuah akun admin default akan tersedia.
   *   **Username:** `admin`
   *   **Password:** `admin` (Password ini sudah di-hash dalam database, jadi cukup masukkan 'admin' saat login).

**Langkah-langkah untuk Menjalankan Aplikasi:**

1.  **Impor Database:** Buka alat manajemen database Anda (misalnya phpMyAdmin, HeidiSQL, atau MySQL Workbench), buat database baru bernama `arsip_digital`, lalu impor file `database.sql` ke dalamnya.
2.  **Konfigurasi Koneksi Database:** Pastikan file `config/database.php` memiliki pengaturan koneksi yang benar untuk lingkungan Anda (misalnya, `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`). Secara default, saya mengaturnya untuk `localhost`, `root`, dan password kosong (`''`), yang umum untuk lingkungan pengembangan seperti Laragon.
3.  **Tempatkan File:** Pastikan semua file proyek yang telah saya buat berada di direktori web server Anda (misalnya, `c:\laragon\www\e_arsip`).
4.  **Akses Aplikasi:** Buka browser Anda dan navigasikan ke URL aplikasi Anda (misalnya, `http://localhost/e_arsip/public/index.php`). Anda akan diarahkan ke halaman login.
5.  **Login:** Gunakan kredensial admin yang telah saya berikan di atas.
6.  **Eksplorasi:** Jelajahi berbagai modul dan fungsionalitas aplikasi.

Aplikasi telah selesai dibangun dan siap untuk digunakan setelah penyiapan database.
