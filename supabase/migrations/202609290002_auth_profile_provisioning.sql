-- Ensure every Supabase Auth user has an application profile.
-- New profiles receive the least-privileged role; privileged roles are assigned
-- explicitly by a trusted project owner through the Supabase SQL Editor.


ALTER TABLE public.profiles
    ALTER COLUMN pegawai_id TYPE BIGINT USING pegawai_id::BIGINT;

CREATE OR REPLACE FUNCTION public.provision_auth_profile()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = ''
AS $$
DECLARE
    safe_username TEXT;
    safe_name TEXT;
    linked_employee_id BIGINT;
BEGIN
    safe_username := NULLIF(btrim(NEW.raw_user_meta_data ->> 'username'), '');
    safe_name := COALESCE(
        NULLIF(btrim(NEW.raw_user_meta_data ->> 'nama_lengkap'), ''),
        NULLIF(btrim(NEW.raw_user_meta_data ->> 'full_name'), ''),
        NULLIF(split_part(COALESCE(NEW.email, ''), '@', 1), ''),
        'Pengguna'
    );

    IF safe_username IS NULL OR EXISTS (
        SELECT 1 FROM public.profiles AS existing
        WHERE existing.username = safe_username AND existing.id <> NEW.id
    ) THEN
        safe_username := 'user_' || replace(NEW.id::TEXT, '-', '');
    END IF;

    SELECT min(employee.id)
    INTO linked_employee_id
    FROM public.pegawai AS employee
    WHERE NEW.email IS NOT NULL
      AND lower(employee.email) = lower(NEW.email)
    HAVING count(*) = 1;

    INSERT INTO public.profiles (id, email, username, nama_lengkap, role, pegawai_id)
    VALUES (NEW.id, NEW.email, safe_username, safe_name, 'pegawai', linked_employee_id)
    ON CONFLICT (id) DO NOTHING;

    RETURN NEW;
END;
$$;

REVOKE ALL ON FUNCTION public.provision_auth_profile() FROM PUBLIC, anon, authenticated;

DROP TRIGGER IF EXISTS on_auth_user_created_provision_profile ON auth.users;
CREATE TRIGGER on_auth_user_created_provision_profile
    AFTER INSERT ON auth.users
    FOR EACH ROW EXECUTE FUNCTION public.provision_auth_profile();

-- Backfill accounts created before the trigger was installed. Existing profiles,
-- including explicitly assigned roles, are never overwritten.
DO $$
DECLARE
    auth_user RECORD;
    safe_username TEXT;
    safe_name TEXT;
    linked_employee_id BIGINT;
BEGIN
    FOR auth_user IN
        SELECT account.id, account.email, account.raw_user_meta_data
        FROM auth.users AS account
        LEFT JOIN public.profiles AS profile ON profile.id = account.id
        WHERE profile.id IS NULL
    LOOP
        safe_username := NULLIF(btrim(auth_user.raw_user_meta_data ->> 'username'), '');
        safe_name := COALESCE(
            NULLIF(btrim(auth_user.raw_user_meta_data ->> 'nama_lengkap'), ''),
            NULLIF(btrim(auth_user.raw_user_meta_data ->> 'full_name'), ''),
            NULLIF(split_part(COALESCE(auth_user.email, ''), '@', 1), ''),
            'Pengguna'
        );

        IF safe_username IS NULL OR EXISTS (
            SELECT 1 FROM public.profiles AS existing
            WHERE existing.username = safe_username AND existing.id <> auth_user.id
        ) THEN
            safe_username := 'user_' || replace(auth_user.id::TEXT, '-', '');
        END IF;

        SELECT min(employee.id)
        INTO linked_employee_id
        FROM public.pegawai AS employee
        WHERE auth_user.email IS NOT NULL
          AND lower(employee.email) = lower(auth_user.email)
        HAVING count(*) = 1;

        INSERT INTO public.profiles (id, email, username, nama_lengkap, role, pegawai_id)
        VALUES (
            auth_user.id,
            auth_user.email,
            safe_username,
            safe_name,
            'pegawai',
            linked_employee_id
        )
        ON CONFLICT (id) DO NOTHING;
    END LOOP;
END;
$$;
