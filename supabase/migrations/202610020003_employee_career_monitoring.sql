ALTER TABLE public.pegawai
    ADD COLUMN IF NOT EXISTS tanggal_pensiun DATE;

ALTER TABLE public.pegawai
    ALTER COLUMN status_kepegawaian SET DEFAULT 'Dosen';

COMMENT ON COLUMN public.pegawai.tanggal_pensiun IS
    'Tanggal pensiun resmi pegawai sesuai ketentuan kepegawaian yang berlaku.';