// server/src/config/supabase.js
const { createClient } = require('@supabase/supabase-js');

const supabaseUrl = process.env.SUPABASE_URL;
const supabaseAnonKey = process.env.SUPABASE_ANON_KEY || process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY || process.env.SUPABASE_PUBLISHABLE_KEY;
const supabaseServiceKey = process.env.SUPABASE_SERVICE_KEY || supabaseAnonKey;

if (!supabaseUrl || !supabaseAnonKey) {
  console.error('❌ SUPABASE_URL dan SUPABASE_ANON_KEY harus diisi di file .env');
  process.exit(1);
}

// Client dengan service role key (atau anon key jika service role belum diset)
const supabase = createClient(supabaseUrl, supabaseServiceKey, {
  auth: {
    autoRefreshToken: false,
    persistSession: false,
  },
});

// Client dengan anon key
const supabaseAnon = createClient(supabaseUrl, supabaseAnonKey);

module.exports = { supabase, supabaseAnon };

