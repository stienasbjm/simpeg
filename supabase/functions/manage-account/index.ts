import { createClient } from "https://esm.sh/@supabase/supabase-js@2";

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Headers": "authorization, x-client-info, apikey, content-type",
  "Access-Control-Allow-Methods": "POST, OPTIONS",
};

Deno.serve(async (request) => {
  if (request.method === "OPTIONS") return new Response("ok", { headers: corsHeaders });
  if (request.method !== "POST") return Response.json({ error: "Method not allowed" }, { status: 405, headers: corsHeaders });

  const authorization = request.headers.get("Authorization");
  if (!authorization?.startsWith("Bearer ")) return Response.json({ error: "Unauthorized" }, { status: 401, headers: corsHeaders });

  const url = Deno.env.get("SUPABASE_URL")!;
  const anonKey = Deno.env.get("SUPABASE_ANON_KEY")!;
  const serviceKey = Deno.env.get("SUPABASE_SERVICE_ROLE_KEY")!;
  const callerClient = createClient(url, anonKey, { global: { headers: { Authorization: authorization } } });
  const adminClient = createClient(url, serviceKey, { auth: { autoRefreshToken: false, persistSession: false } });

  const { data: callerData, error: callerError } = await callerClient.auth.getUser();
  if (callerError || !callerData.user) return Response.json({ error: "Unauthorized" }, { status: 401, headers: corsHeaders });

  const { data: callerProfile, error: profileError } = await adminClient.from("profiles").select("id, role").eq("id", callerData.user.id).single();
  if (profileError || !callerProfile || !["admin", "developer"].includes(callerProfile.role)) {
    return Response.json({ error: "Akses ditolak." }, { status: 403, headers: corsHeaders });
  }

  try {
    const input = await request.json();
    const allowedRoles = callerProfile.role === "developer" ? ["admin", "developer", "bendahara", "pegawai"] : ["pegawai"];
    if (input.action === "create") {
      if (!input.email || !input.password || !input.username || !input.nama_lengkap || !allowedRoles.includes(input.role)) {
        return Response.json({ error: "Data akun tidak lengkap atau role tidak diizinkan." }, { status: 400, headers: corsHeaders });
      }
      if (input.role === "pegawai" && !input.pegawai_id) return Response.json({ error: "Pilih data pegawai untuk akun pegawai." }, { status: 400, headers: corsHeaders });
      const { data: authData, error: authError } = await adminClient.auth.admin.createUser({
        email: input.email,
        password: input.password,
        email_confirm: true,
        user_metadata: { username: input.username },
      });
      if (authError || !authData.user) throw authError || new Error("Gagal membuat user Auth.");
      const { error: profileError } = await adminClient.from("profiles").upsert(
        {
          id: authData.user.id,
          email: input.email,
          username: input.username,
          nama_lengkap: input.nama_lengkap,
          role: input.role,
          pegawai_id: input.role === "pegawai" ? input.pegawai_id : null,
        },
        { onConflict: "id" },
      );
      if (profileError) {
        await adminClient.auth.admin.deleteUser(authData.user.id);
        throw profileError;
      }
      return Response.json({ id: authData.user.id }, { headers: corsHeaders });
    }

    if (input.action === "update") {
      if (!input.id || !allowedRoles.includes(input.role)) return Response.json({ error: "Data akun tidak valid." }, { status: 400, headers: corsHeaders });
      const { data: target, error: targetError } = await adminClient.from("profiles").select("role").eq("id", input.id).single();
      if (targetError || !target || (callerProfile.role !== "developer" && target.role !== "pegawai")) {
        return Response.json({ error: "Tidak boleh mengubah akun ini." }, { status: 403, headers: corsHeaders });
      }
      const authUpdates: Record<string, string> = {};
      if (input.email) authUpdates.email = input.email;
      if (input.password) authUpdates.password = input.password;
      if (Object.keys(authUpdates).length) {
        const { error: authError } = await adminClient.auth.admin.updateUserById(input.id, authUpdates);
        if (authError) throw authError;
      }
      const { error: updateError } = await adminClient
        .from("profiles")
        .update({
          username: input.username,
          email: input.email,
          nama_lengkap: input.nama_lengkap,
          role: input.role,
          pegawai_id: input.role === "pegawai" ? input.pegawai_id : null,
        })
        .eq("id", input.id);
      if (updateError) throw updateError;
      return Response.json({ updated: true }, { headers: corsHeaders });
    }

    if (input.action === "delete") {
      if (!input.id || input.id === callerProfile.id) return Response.json({ error: "Akun aktif tidak dapat dihapus." }, { status: 400, headers: corsHeaders });
      const { data: target, error: targetError } = await adminClient.from("profiles").select("role").eq("id", input.id).single();
      if (targetError || !target || (callerProfile.role !== "developer" && target.role !== "pegawai")) {
        return Response.json({ error: "Tidak boleh menghapus akun ini." }, { status: 403, headers: corsHeaders });
      }
      const { error } = await adminClient.auth.admin.deleteUser(input.id);
      if (error) throw error;
      return Response.json({ deleted: true }, { headers: corsHeaders });
    }

    return Response.json({ error: "Aksi tidak dikenal." }, { status: 400, headers: corsHeaders });
  } catch (error) {
    return Response.json({ error: error.message || "Operasi akun gagal." }, { status: 400, headers: corsHeaders });
  }
});
