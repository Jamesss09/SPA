import { useCallback, useEffect, useState } from "react";
import { api } from "../../lib/api";
import { errMsg } from "./CrudPage";

interface AdminUser {
  id: number;
  name: string;
  username: string;
  email?: string | null;
  role: string;
  status: string;
  must_change_password: boolean;
}

export default function Users() {
  const [rows, setRows] = useState<AdminUser[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [fRole, setFRole] = useState("");
  const [fStatus, setFStatus] = useState("");
  const [search, setSearch] = useState("");
  const [form, setForm] = useState<Record<string, string>>({ name: "", username: "", password: "", role: "faculty" });
  const [editing, setEditing] = useState<number | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params: Record<string, string> = {};
      if (fRole) params.role = fRole;
      if (fStatus) params.status = fStatus;
      if (search) params.search = search;
      const { data } = await api.get("/users", { params });
      setRows(data.data ?? []);
      setTotal(data.total ?? 0);
    } catch (e) {
      setError(errMsg(e, "Load failed."));
    } finally {
      setLoading(false);
    }
  }, [fRole, fStatus, search]);

  useEffect(() => {
    void load();
  }, [load]);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setMsg(null);
    try {
      const payload = Object.fromEntries(Object.entries(form).filter(([, v]) => v !== ""));
      if (editing) {
        await api.patch(`/users/${editing}`, payload);
        setMsg("Saved.");
      } else {
        await api.post("/users", payload);
        setMsg("Created.");
      }
      setEditing(null);
      setForm({ name: "", username: "", password: "", role: "faculty" });
      await load();
    } catch (err) {
      setMsg(errMsg(err, "Save failed."));
    }
  };

  const toggleStatus = async (u: AdminUser) => {
    try {
      await api.patch(`/users/${u.id}`, { status: u.status === "active" ? "deactivated" : "active" });
      await load();
    } catch (err) {
      setMsg(errMsg(err, "Status change failed."));
    }
  };

  const resetPw = async (u: AdminUser) => {
    if (!confirm(`Reset password for ${u.username}? They must change it on next login.`)) return;
    try {
      const { data } = await api.post(`/users/${u.id}/reset-password`);
      setMsg(`Temporary password for ${u.username}: ${data.temporary_password} — hand it over out of band.`);
      await load();
    } catch (err) {
      setMsg(errMsg(err, "Reset failed."));
    }
  };

  return (
    <>
      <h2 className="text-xl font-bold mb-3">Users ({total})</h2>
      <div className="flex gap-2 mb-3">
        <select value={fRole} onChange={(e) => setFRole(e.target.value)} className="border rounded px-2 py-1 text-sm">
          <option value="">Role: all</option><option value="admin">admin</option><option value="faculty">faculty</option><option value="student">student</option>
        </select>
        <select value={fStatus} onChange={(e) => setFStatus(e.target.value)} className="border rounded px-2 py-1 text-sm">
          <option value="">Status: all</option><option value="active">active</option><option value="deactivated">deactivated</option>
        </select>
        <input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search name/username" className="border rounded px-2 py-1 text-sm" />
      </div>
      {loading && <p className="text-sm text-gray-500">Loading…</p>}
      {error && <p className="text-sm text-red-700">{error}</p>}
      {!loading && !error && (
        <div className="overflow-x-auto bg-white rounded shadow mb-4">
          <table className="min-w-full text-sm">
            <thead><tr className="bg-gray-50"><th className="text-left px-3 py-2">Name</th><th className="text-left px-3 py-2">Username</th><th className="text-left px-3 py-2">Role</th><th className="text-left px-3 py-2">Status</th><th className="text-left px-3 py-2">Must change PW</th><th className="px-3 py-2" /></tr></thead>
            <tbody>
              {rows.map((u) => (
                <tr key={u.id} className="border-t">
                  <td className="px-3 py-2">{u.name}</td>
                  <td className="px-3 py-2">{u.username}</td>
                  <td className="px-3 py-2">{u.role}</td>
                  <td className="px-3 py-2">{u.status}</td>
                  <td className="px-3 py-2">{u.must_change_password ? "yes" : "no"}</td>
                  <td className="px-3 py-2 whitespace-nowrap">
                    <button onClick={() => { setEditing(u.id); setForm({ name: u.name, username: u.username, password: "", role: u.role }); }} className="text-blue-700 underline text-xs">Edit</button>
                    <button onClick={() => void toggleStatus(u)} className="text-orange-700 underline text-xs ml-2">{u.status === "active" ? "Deactivate" : "Activate"}</button>
                    <button onClick={() => void resetPw(u)} className="text-purple-700 underline text-xs ml-2">Reset PW</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      <form onSubmit={submit} className="bg-white rounded shadow p-4 max-w-2xl">
        <h3 className="font-semibold mb-2">{editing ? `Edit #${editing}` : "Create faculty/admin (students come from Phase 4 import)"}</h3>
        {msg && <p className="text-sm text-gray-700 mb-2 break-all">{msg}</p>}
        <div className="grid md:grid-cols-2 gap-2">
          <label className="text-sm">Name *<input value={form.name ?? ""} required={!editing} onChange={(e) => setForm({ ...form, name: e.target.value })} className="w-full border rounded px-2 py-1.5 mt-0.5" /></label>
          <label className="text-sm">Username *<input value={form.username ?? ""} required={!editing} onChange={(e) => setForm({ ...form, username: e.target.value })} className="w-full border rounded px-2 py-1.5 mt-0.5" /></label>
          <label className="text-sm">Password {editing ? "(leave blank to keep)" : "(min 8) *"}<input type="password" value={form.password ?? ""} required={!editing} onChange={(e) => setForm({ ...form, password: e.target.value })} className="w-full border rounded px-2 py-1.5 mt-0.5" /></label>
          <label className="text-sm">Role
            <select value={form.role ?? "faculty"} onChange={(e) => setForm({ ...form, role: e.target.value })} className="w-full border rounded px-2 py-1.5 mt-0.5">
              <option value="faculty">faculty</option><option value="admin">admin</option>
            </select>
          </label>
        </div>
        <div className="mt-3 flex gap-2">
          <button className="bg-blue-600 text-white px-4 py-1.5 rounded text-sm">{editing ? "Save" : "Create"}</button>
          {editing && <button type="button" onClick={() => { setEditing(null); setForm({ name: "", username: "", password: "", role: "faculty" }); }} className="border px-4 py-1.5 rounded text-sm">Cancel</button>}
        </div>
      </form>
    </>
  );
}
