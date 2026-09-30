import { createClient } from "@supabase/supabase-js";

const DEFAULT_SUPABASE_URL = "https://ploutblidwenynluiudl.supabase.co";
const DEFAULT_SUPABASE_KEY = "sb_publishable_vFXBocNtcHsH973-IThzgQ_Busq65uT";

const url = import.meta.env.VITE_SUPABASE_URL || DEFAULT_SUPABASE_URL;
const publishableKey = import.meta.env.VITE_SUPABASE_PUBLISHABLE_KEY || DEFAULT_SUPABASE_KEY;

export const supabaseConfigured = Boolean(url && publishableKey);
export const supabase = supabaseConfigured
  ? createClient(url, publishableKey, {
      auth: {
        autoRefreshToken: true,
        persistSession: true,
        detectSessionInUrl: true,
      },
    })
  : null;

