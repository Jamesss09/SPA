import { useEffect, useState } from "react";
import { api } from "../../lib/api";
import { errMsg } from "./CrudPage";
import { useOptions } from "./SetupPages";

interface Section {
  id: number;
  name: string;
  school_year_id: number;
  grade_level_id: number;
  adviser_faculty_id?: number | null;
  room_id?: number | null;
}

export default function Sections() {
  const years = useOptions("/school-years", "id", "name");
  const levels = useOptions("/grade-levels", "id", "name");
  const rooms = useOptions("/rooms", "id", "room_name");
  const [faculty, setFaculty] = useState<{ value: number; label: string }[]>([]);
  const [rows, setRows] = useState<Section[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [fYear, setFYear] = useState("");
  const [form, setForm] = useState<Record<string, string>>({ school_year_id: "", grade_level_id: "", name: "", adviser_faculty_id: "", room_id: "" });
  const [editing, setEditing] = useState<number | null>(null);

  useEffect(() => {
    api.get("/users", { params: { role: "faculty", status: "active", per_page: 100 } })
      .then(({ data }) => setFaculty((data.data ?? []).map((u: { id: number; name: string }) => ({ value: u.id, label: u.name }))))
      .catch(() => undefined);
  }, []);

  const load = async () => {
    setLoading(true);
    setError(null);
    try {
      const { data } = await api.get("/sections", { params: fYear ? { school_year_id: fYear } : {} });
      setRows(data.data ?? []);
    } catch (e) {
      setError(errMsg(e, "Load failed."));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [fYear]);

  const clean = (f: Record<string, string>) =>
    Object.fromEntries(Object.entries(f).filter(([, v]) => v !== ""));

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setMsg(null);
    try {
      if (editing) {
        await api.patch(`/sections/${editing}`, clean(form));
        setMsg("Saved.");
      } else {
        await api.post("/sections", clean(form));
        setMsg("Created.");
      }
      setEditing(null);
      await load();
    } catch (err) {
      setMsg(errMsg(err, "Save failed."));
    }
  };

  const remove = async (id: number) => {
    if (!confirm("Delete this section?")) return;
    try {
      await api.delete(`/sections/${id}`);
      await load();
    } catch (err) {
      setMsg(errMsg(err, "Delete blocked (section is in use)."));
    }
  };

  const sel = (label: string, key: string, opts: { value: string | number; label: string }[]) => (
    <label className="text-sm">{label}
      <select value={form[key] ?? ""} onChange={(e) => setForm({ ...form, [key]: e.target.value })} className="w-full border rounded px-2 py-1.5 mt-0.5">
        <option value="">—</option>
        {opts.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
      </select>
    </label>
  );

  return (
    <>
      <h2 className="text-xl font-bold mb-3">Sections ({rows.length})</h2>
      <select value={fYear} onChange={(e) => setFYear(e.target.value)} className="border rounded px-2 py-1 text-sm mb-3">
        <option value="">Year: all</option>
        {years.map((y) => <option key={y.value} value={y.value}>{y.label}</option>)}
      </select>
      {loading && <p className="text-sm text-gray-500">Loading…</p>}
      {error && <p className="text-sm text-red-700">{error}</p>}
      {!loading && !error && (
        <div className="overflow-x-auto bg-white rounded shadow mb-4">
          <table className="min-w-full text-sm">
            <thead><tr className="bg-gray-50"><th className="text-left px-3 py-2">Name</th><th className="text-left px-3 py-2">Year</th><th className="text-left px-3 py-2">Level</th><th className="px-3 py-2" /></tr></thead>
            <tbody>
              {rows.map((r) => (
                <tr key={r.id} className="border-t">
                  <td className="px-3 py-2">{r.name}</td>
                  <td className="px-3 py-2">{r.school_year_id}</td>
                  <td className="px-3 py-2">{r.grade_level_id}</td>
                  <td className="px-3 py-2 whitespace-nowrap">
                    <button onClick={() => { setEditing(r.id); setForm({ school_year_id: String(r.school_year_id), grade_level_id: String(r.grade_level_id), name: r.name, adviser_faculty_id: r.adviser_faculty_id ? String(r.adviser_faculty_id) : "", room_id: r.room_id ? String(r.room_id) : "" }); }} className="text-blue-700 underline text-xs">Edit</button>
                    <button onClick={() => void remove(r.id)} className="text-red-700 underline text-xs ml-2">Delete</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      <form onSubmit={submit} className="bg-white rounded shadow p-4 max-w-2xl">
        <h3 className="font-semibold mb-2">{editing ? `Edit #${editing}` : "Create"}</h3>
        {msg && <p className="text-sm text-gray-700 mb-2">{msg}</p>}
        <div className="grid md:grid-cols-2 gap-2">
          {sel("School year *", "school_year_id", years)}
          {sel("Grade level *", "grade_level_id", levels)}
          <label className="text-sm">Name *
            <input value={form.name ?? ""} required onChange={(e) => setForm({ ...form, name: e.target.value })} className="w-full border rounded px-2 py-1.5 mt-0.5" />
          </label>
          {sel("Adviser (active faculty)", "adviser_faculty_id", faculty)}
          {sel("Room", "room_id", rooms)}
        </div>
        <div className="mt-3 flex gap-2">
          <button className="bg-blue-600 text-white px-4 py-1.5 rounded text-sm">{editing ? "Save" : "Create"}</button>
          {editing && <button type="button" onClick={() => setEditing(null)} className="border px-4 py-1.5 rounded text-sm">Cancel</button>}
        </div>
      </form>
    </>
  );
}
