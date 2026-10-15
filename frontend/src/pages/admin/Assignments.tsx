import { useEffect, useState } from "react";
import { api } from "../../lib/api";
import { errMsg } from "./CrudPage";
import { useOptions } from "./SetupPages";

interface Assignment {
  id: number;
  status: string;
  faculty?: { user?: { name: string } };
  subject?: { subject_code: string; subject_name: string };
  section?: { name: string };
}

export default function Assignments() {
  const [rows, setRows] = useState<Assignment[]>([]);
  const [loading, setLoading] = useState(true);
  const [msg, setMsg] = useState<string | null>(null);
  const [form, setForm] = useState({ faculty_user_id: "", subject_id: "", section_id: "" });
  const [faculty, setFaculty] = useState<{ value: number; label: string }[]>([]);
  const subjects = useOptions("/subjects", "id", "subject_name");
  const [sections, setSections] = useState<{ value: number; label: string }[]>([]);

  const load = async () => {
    setLoading(true);
    try {
      const { data } = await api.get("/teaching-assignments", { params: { per_page: 100 } });
      setRows(data.data ?? []);
    } catch (e) {
      setMsg(errMsg(e, "Load failed."));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
    api.get("/users", { params: { role: "faculty", status: "active", per_page: 100 } })
      .then(({ data }) => setFaculty((data.data ?? []).map((u: { id: number; name: string }) => ({ value: u.id, label: u.name }))))
      .catch(() => undefined);
    api.get("/sections", { params: { per_page: 100 } })
      .then(({ data }) => setSections((data.data ?? []).map((s: { id: number; name: string }) => ({ value: s.id, label: s.name }))))
      .catch(() => undefined);
  }, []);

  const create = async (e: React.FormEvent) => {
    e.preventDefault();
    setMsg(null);
    try {
      await api.post("/teaching-assignments", { ...form, faculty_user_id: Number(form.faculty_user_id), subject_id: Number(form.subject_id), section_id: Number(form.section_id) });
      setForm({ faculty_user_id: "", subject_id: "", section_id: "" });
      setMsg("Assigned.");
      await load();
    } catch (err) {
      setMsg(errMsg(err, "Assign failed."));
    }
  };

  const remove = async (id: number) => {
    if (!confirm("Delete this assignment?")) return;
    try {
      await api.delete(`/teaching-assignments/${id}`);
      await load();
    } catch (err) {
      setMsg(errMsg(err, "Delete blocked (assignment is in use)."));
    }
  };

  const sel = (label: string, key: keyof typeof form, opts: { value: number | string; label: string }[]) => (
    <label className="text-sm">{label} *
      <select required value={form[key]} onChange={(e) => setForm({ ...form, [key]: e.target.value })} className="w-full border rounded px-2 py-1.5 mt-0.5">
        <option value="">—</option>
        {opts.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
      </select>
    </label>
  );

  return (
    <>
      <h2 className="text-xl font-bold mb-3">Teaching assignments ({rows.length})</h2>
      {msg && <p className="text-sm text-gray-700 mb-2">{msg}</p>}
      {loading ? <p className="text-sm text-gray-500">Loading…</p> : (
        <div className="overflow-x-auto bg-white rounded shadow mb-4">
          <table className="min-w-full text-sm">
            <thead><tr className="bg-gray-50"><th className="text-left px-3 py-2">Faculty</th><th className="text-left px-3 py-2">Subject</th><th className="text-left px-3 py-2">Section</th><th className="text-left px-3 py-2">Status</th><th className="px-3 py-2" /></tr></thead>
            <tbody>
              {rows.map((r) => (
                <tr key={r.id} className="border-t">
                  <td className="px-3 py-2">{r.faculty?.user?.name ?? "—"}</td>
                  <td className="px-3 py-2">{r.subject ? `${r.subject.subject_code} · ${r.subject.subject_name}` : "—"}</td>
                  <td className="px-3 py-2">{r.section?.name ?? "—"}</td>
                  <td className="px-3 py-2">{r.status}</td>
                  <td className="px-3 py-2"><button onClick={() => void remove(r.id)} className="text-red-700 underline text-xs">Delete</button></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      <form onSubmit={create} className="bg-white rounded shadow p-4 max-w-2xl">
        <h3 className="font-semibold mb-2">New assignment (year derives from the section)</h3>
        <div className="grid md:grid-cols-3 gap-2">
          {sel("Faculty", "faculty_user_id", faculty)}
          {sel("Subject", "subject_id", subjects)}
          {sel("Section", "section_id", sections)}
        </div>
        <button className="mt-3 bg-blue-600 text-white px-4 py-1.5 rounded text-sm">Assign</button>
      </form>
    </>
  );
}
