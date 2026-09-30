CREATE TABLE IF NOT EXISTS public.profiles (
    id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    email TEXT NOT NULL UNIQUE,
    username TEXT NOT NULL UNIQUE,
    nama_lengkap TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('admin', 'developer', 'bendahara', 'pegawai')),
    pegawai_id BIGINT UNIQUE REFERENCES public.pegawai(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE public.profiles ADD COLUMN IF NOT EXISTS email TEXT;
UPDATE public.profiles AS app_profile
SET email = auth_user.email
FROM auth.users AS auth_user
WHERE app_profile.id = auth_user.id AND app_profile.email IS NULL;
ALTER TABLE public.profiles ALTER COLUMN email SET NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS profiles_email_key ON public.profiles (email);

CREATE OR REPLACE FUNCTION public.current_app_role()
RETURNS TEXT
LANGUAGE SQL
STABLE
SECURITY DEFINER
SET search_path = ''
AS $$
    SELECT role FROM public.profiles WHERE id = (SELECT auth.uid())
$$;

CREATE OR REPLACE FUNCTION public.current_pegawai_id()
RETURNS BIGINT
LANGUAGE SQL
STABLE
SECURITY DEFINER
SET search_path = ''
AS $$
    SELECT pegawai_id FROM public.profiles WHERE id = (SELECT auth.uid())
$$;

CREATE OR REPLACE FUNCTION public.get_auth_email(p_username TEXT)
RETURNS TEXT
LANGUAGE SQL
STABLE
SECURITY DEFINER
SET search_path = ''
AS $$
    SELECT email FROM public.profiles WHERE lower(username) = lower(p_username) LIMIT 1;
$$;

REVOKE ALL ON FUNCTION public.current_app_role() FROM PUBLIC;
REVOKE ALL ON FUNCTION public.current_pegawai_id() FROM PUBLIC;
REVOKE ALL ON FUNCTION public.get_auth_email(TEXT) FROM PUBLIC;
GRANT EXECUTE ON FUNCTION public.current_app_role() TO authenticated;
GRANT EXECUTE ON FUNCTION public.current_pegawai_id() TO authenticated;
GRANT EXECUTE ON FUNCTION public.get_auth_email(TEXT) TO anon, authenticated;

ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pegawai_dokumen ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pegawai ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.absensi ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pengajuan_izin ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.gaji ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.kas_transaksi ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pengaturan_ttd ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.surat_masuk ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.surat_keluar ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.surat_keputusan ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS profiles_read_self_or_developer ON public.profiles;
CREATE POLICY profiles_read_self_or_developer ON public.profiles
    FOR SELECT TO authenticated
    USING (id = (SELECT auth.uid()) OR public.current_app_role() IN ('admin', 'developer'));

DROP POLICY IF EXISTS users_no_client_access ON public.users;
CREATE POLICY users_no_client_access ON public.users
    FOR ALL TO anon, authenticated USING (false) WITH CHECK (false);

DROP POLICY IF EXISTS employee_documents_read_own_or_admin ON public.pegawai_dokumen;
CREATE POLICY employee_documents_read_own_or_admin ON public.pegawai_dokumen
    FOR SELECT TO authenticated
    USING (
        public.current_app_role() IN ('admin', 'developer')
        OR pegawai_id = public.current_pegawai_id()
    );
DROP POLICY IF EXISTS employee_documents_manage_admin ON public.pegawai_dokumen;
CREATE POLICY employee_documents_manage_admin ON public.pegawai_dokumen
    FOR ALL TO authenticated
    USING (public.current_app_role() IN ('admin', 'developer'))
    WITH CHECK (public.current_app_role() IN ('admin', 'developer'));

DROP POLICY IF EXISTS pegawai_read_roles ON public.pegawai;
CREATE POLICY pegawai_read_roles ON public.pegawai
    FOR SELECT TO authenticated
    USING (
        public.current_app_role() IN ('admin', 'developer', 'bendahara')
        OR id = public.current_pegawai_id()
    );
DROP POLICY IF EXISTS pegawai_manage_admin ON public.pegawai;
CREATE POLICY pegawai_manage_admin ON public.pegawai
    FOR ALL TO authenticated
    USING (public.current_app_role() IN ('admin', 'developer'))
    WITH CHECK (public.current_app_role() IN ('admin', 'developer'));

DROP POLICY IF EXISTS absensi_read_roles ON public.absensi;
CREATE POLICY absensi_read_roles ON public.absensi
    FOR SELECT TO authenticated
    USING (
        public.current_app_role() IN ('admin', 'developer', 'bendahara')
        OR pegawai_id = public.current_pegawai_id()
    );
DROP POLICY IF EXISTS absensi_manage_admin ON public.absensi;
CREATE POLICY absensi_manage_admin ON public.absensi
    FOR ALL TO authenticated
    USING (public.current_app_role() IN ('admin', 'developer'))
    WITH CHECK (public.current_app_role() IN ('admin', 'developer'));

DROP POLICY IF EXISTS izin_read_own_or_admin ON public.pengajuan_izin;
CREATE POLICY izin_read_own_or_admin ON public.pengajuan_izin
    FOR SELECT TO authenticated
    USING (
        pegawai_id = public.current_pegawai_id()
        OR public.current_app_role() IN ('admin', 'developer')
    );
DROP POLICY IF EXISTS izin_insert_own ON public.pengajuan_izin;
CREATE POLICY izin_insert_own ON public.pengajuan_izin
    FOR INSERT TO authenticated
    WITH CHECK (pegawai_id = public.current_pegawai_id() AND status = 'menunggu');
DROP POLICY IF EXISTS izin_manage_admin ON public.pengajuan_izin;
CREATE POLICY izin_manage_admin ON public.pengajuan_izin
    FOR ALL TO authenticated
    USING (public.current_app_role() IN ('admin', 'developer'))
    WITH CHECK (public.current_app_role() IN ('admin', 'developer'));

DROP POLICY IF EXISTS gaji_read_own_or_finance ON public.gaji;
CREATE POLICY gaji_read_own_or_finance ON public.gaji
    FOR SELECT TO authenticated
    USING (
        pegawai_id = public.current_pegawai_id()
        OR public.current_app_role() IN ('developer', 'bendahara')
    );
DROP POLICY IF EXISTS gaji_manage_finance ON public.gaji;
CREATE POLICY gaji_manage_finance ON public.gaji
    FOR ALL TO authenticated
    USING (public.current_app_role() IN ('developer', 'bendahara'))
    WITH CHECK (public.current_app_role() IN ('developer', 'bendahara'));

DROP POLICY IF EXISTS kas_manage_finance ON public.kas_transaksi;
CREATE POLICY kas_manage_finance ON public.kas_transaksi
    FOR ALL TO authenticated
    USING (public.current_app_role() IN ('developer', 'bendahara'))
    WITH CHECK (public.current_app_role() IN ('developer', 'bendahara'));

DROP POLICY IF EXISTS ttd_manage_finance ON public.pengaturan_ttd;
CREATE POLICY ttd_manage_finance ON public.pengaturan_ttd
    FOR ALL TO authenticated
    USING (public.current_app_role() IN ('developer', 'bendahara'))
    WITH CHECK (public.current_app_role() IN ('developer', 'bendahara'));

DO $$
DECLARE table_name TEXT;
BEGIN
    FOREACH table_name IN ARRAY ARRAY['surat_masuk', 'surat_keluar', 'surat_keputusan'] LOOP
        EXECUTE format('DROP POLICY IF EXISTS archive_admin_access ON public.%I', table_name);
        EXECUTE format(
            'CREATE POLICY archive_admin_access ON public.%I FOR ALL TO authenticated USING (public.current_app_role() IN (''admin'', ''developer'')) WITH CHECK (public.current_app_role() IN (''admin'', ''developer''))',
            table_name
        );
    END LOOP;
END
$$;

CREATE OR REPLACE FUNCTION public.update_employee_profile(
    p_nama TEXT,
    p_tempat_lahir TEXT,
    p_tanggal_lahir DATE,
    p_ijazah TEXT,
    p_foto TEXT
)
RETURNS VOID
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = ''
AS $$
DECLARE employee_id INTEGER := public.current_pegawai_id();
BEGIN
    IF employee_id IS NULL OR public.current_app_role() <> 'pegawai' THEN
        RAISE EXCEPTION 'Akun pegawai tidak valid';
    END IF;
    UPDATE public.pegawai
    SET nama = p_nama,
        tempat_lahir = p_tempat_lahir,
        tanggal_lahir = p_tanggal_lahir,
        ijazah = p_ijazah,
        foto = p_foto
    WHERE id = employee_id;
END;
$$;

CREATE OR REPLACE FUNCTION public.record_attendance(p_action TEXT)
RETURNS VOID
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = ''
AS $$
DECLARE
    employee_id BIGINT := public.current_pegawai_id();
    local_today DATE := (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Makassar')::DATE;
    local_time TIME := (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Makassar')::TIME;
BEGIN
    IF employee_id IS NULL OR public.current_app_role() <> 'pegawai' THEN
        RAISE EXCEPTION 'Akun pegawai tidak valid';
    END IF;
    IF p_action = 'masuk' THEN
        INSERT INTO public.absensi (pegawai_id, tanggal, jam_masuk, status)
        VALUES (employee_id, local_today, local_time, 'hadir')
        ON CONFLICT (pegawai_id, tanggal) DO NOTHING;
        IF NOT FOUND THEN RAISE EXCEPTION 'Absen masuk hari ini sudah tercatat'; END IF;
    ELSIF p_action = 'pulang' THEN
        UPDATE public.absensi
        SET jam_keluar = local_time
        WHERE pegawai_id = employee_id AND tanggal = local_today AND jam_masuk IS NOT NULL AND jam_keluar IS NULL;
        IF NOT FOUND THEN RAISE EXCEPTION 'Absen masuk belum tercatat atau absen pulang sudah dilakukan'; END IF;
    ELSE
        RAISE EXCEPTION 'Aksi absensi tidak valid';
    END IF;
END;
$$;

CREATE OR REPLACE FUNCTION public.review_leave(p_request_id BIGINT, p_status TEXT, p_note TEXT DEFAULT '')
RETURNS VOID
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = ''
AS $$
DECLARE request_row public.pengajuan_izin%ROWTYPE;
DECLARE attendance_status TEXT;
BEGIN
    IF public.current_app_role() NOT IN ('admin', 'developer') THEN
        RAISE EXCEPTION 'Tidak memiliki hak untuk meninjau izin';
    END IF;
    IF p_status NOT IN ('disetujui', 'ditolak') THEN
        RAISE EXCEPTION 'Status izin tidak valid';
    END IF;
    UPDATE public.pengajuan_izin
    SET status = p_status, catatan_admin = p_note
    WHERE id = p_request_id
    RETURNING * INTO request_row;
    IF NOT FOUND THEN RAISE EXCEPTION 'Pengajuan izin tidak ditemukan'; END IF;
    IF p_status = 'disetujui' THEN
        attendance_status := CASE WHEN request_row.jenis = 'sakit' THEN 'sakit' ELSE 'izin' END;
        INSERT INTO public.absensi (pegawai_id, tanggal, status, keterangan)
        VALUES (request_row.pegawai_id, request_row.tanggal, attendance_status, upper(request_row.jenis) || ': ' || coalesce(request_row.keterangan, ''))
        ON CONFLICT (pegawai_id, tanggal) DO UPDATE
        SET status = EXCLUDED.status, keterangan = EXCLUDED.keterangan;
    END IF;
END;
$$;

REVOKE ALL ON FUNCTION public.record_attendance(TEXT) FROM PUBLIC;
REVOKE ALL ON FUNCTION public.update_employee_profile(TEXT, TEXT, DATE, TEXT, TEXT) FROM PUBLIC;
REVOKE ALL ON FUNCTION public.review_leave(BIGINT, TEXT, TEXT) FROM PUBLIC;
GRANT EXECUTE ON FUNCTION public.record_attendance(TEXT) TO authenticated;
GRANT EXECUTE ON FUNCTION public.update_employee_profile(TEXT, TEXT, DATE, TEXT, TEXT) TO authenticated;
GRANT EXECUTE ON FUNCTION public.review_leave(BIGINT, TEXT, TEXT) TO authenticated;

INSERT INTO storage.buckets (id, name, public, file_size_limit)
VALUES ('simpeg-private', 'simpeg-private', false, 10485760)
ON CONFLICT (id) DO UPDATE SET public = false, file_size_limit = 10485760;

DROP POLICY IF EXISTS simpeg_storage_read_roles ON storage.objects;
CREATE POLICY simpeg_storage_read_roles ON storage.objects
    FOR SELECT TO authenticated
    USING (
        bucket_id = 'simpeg-private'
        AND (
            public.current_app_role() IN ('admin', 'developer')
            OR (storage.foldername(name))[1] = public.current_pegawai_id()::TEXT
        )
    );
DROP POLICY IF EXISTS simpeg_storage_write_admin ON storage.objects;
CREATE POLICY simpeg_storage_write_admin ON storage.objects
    FOR INSERT TO authenticated
    WITH CHECK (
        bucket_id = 'simpeg-private'
        AND (
            public.current_app_role() IN ('admin', 'developer')
            OR (storage.foldername(name))[1] = public.current_pegawai_id()::TEXT
        )
    );
DROP POLICY IF EXISTS simpeg_storage_update_admin ON storage.objects;
CREATE POLICY simpeg_storage_update_admin ON storage.objects
    FOR UPDATE TO authenticated
    USING (bucket_id = 'simpeg-private' AND public.current_app_role() IN ('admin', 'developer'))
    WITH CHECK (
        bucket_id = 'simpeg-private'
        AND (
            public.current_app_role() IN ('admin', 'developer')
            OR (storage.foldername(name))[1] = public.current_pegawai_id()::TEXT
        )
    );
DROP POLICY IF EXISTS simpeg_storage_delete_admin ON storage.objects;
CREATE POLICY simpeg_storage_delete_admin ON storage.objects
    FOR DELETE TO authenticated
    USING (bucket_id = 'simpeg-private' AND public.current_app_role() IN ('admin', 'developer'));