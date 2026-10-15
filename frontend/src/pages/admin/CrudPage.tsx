import { useCallback, useEffect, useState } from "react";
import { api } from "../../lib/api";

export interface FieldDef {
  key: string;
  label: string;
  type?: "text" | "number" | "date" | "select";
  options?: { value: string | number; label: string }[];
  required?: boolean;
}

export interface FilterDef {
  key: string;
  label: string;
  options: { value: string | number; label: string }[];
}

export function errMsg(err: unknown, fallback: string): string {
  const d = (err as { response?: { status?: number; data?: { message?: string; errors?: Record<string, string[]> } } })?.response;
  if (d?.status === 403) return "Forbidden — admin only.";
  if (d?.data?.errors) return Object.values(d.data.errors).flat().join(" ");
  return d?.data?.message ?? fallback;
}

export function useCrudList(path: string, query = "") {
  const [items, setItems] = useState<Record<string, unknown>[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const { data } = await api.get(`${path}${query}`);
      const rows = Array.isArray(data) ? data : (data.data ?? []);
      setItems(rows);
      setTotal(data.total ?? rows.length);
    } catch (e) {
      setError(errMsg(e, "Load failed."));
    } finally {
      setLoading(false);
    }
  }, [path, query]);

  useEffect(() => {
    void load();
  }, [load]);

  return { items, total, loading, error, load, setItems };
}

// Generic admin CRUD page: list + create + inline edit + delete.
export default function CrudPage({
  title, path, columns, fields, filters, query = "",
}: {
  title: string;
  path: string;
  columns: { key: string; label: string; render?: (row: Record<string, unknown>) => React.ReactNode }[];
  fields: FieldDef[];
  filters?: FilterDef[];
  query?: string;
}) {
  const [form, setForm] = useState<Record<string, string>>({});
  const [editing, setEditing] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);
  const [msg, setMsg] = useState<string | null>(null);
  const [fVals, setFVals] = useState<Record<string, string>>({});

  const q = query + Object.entries(fVals).filter(([, v]) => v).map(([k, v]) => `&${k}=${v}`).join("");
  const list = useCrudList(path, q);
  const { total } = list;

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setMsg(null);
    try {
      if (editing) {
        await api.patch(`${path}/${editing}`, form);
        setMsg("Saved.");
      } else {
        await api.post(path, form);
        setMsg("Created.");
      }
      setForm({});
      setEditing(null);
      await list.load();
    } catch (err) {
      setMsg(errMsg(err, "Save failed."));
    } finally {
      setBusy(false);
    }
  };

  const remove = async (id: number) => {
    if (!confirm("Delete this record?")) return;
    try {
      await api.delete(`${path}/${id}`);
      await list.load();
    } catch (err) {
      setMsg(errMsg(err, "Delete blocked (record is in use)."));
    }
  };

  const val = (r: Record<string, unknown>, key: string) => {
    const v = r[key];
    return v === null || v === undefined ? "—" : String(v);
  };

  return (
    <>
      <h2 className="text-xl font-bold mb-3">{title} <span className="text-sm font-normal text-gray-500">({list.total || total})</span></h2>
      {filters && (
        <div className="flex gap-2 mb-3">
          {filters.map((f) => (
            <select key={f.key} value={fVals[f.key] ?? ""} onChange={(e) => setFVals({ ...fVals, [f.key]: e.target.value })} className="border rounded px-2 py-1 text-sm">
              <option value="">{f.label}: all</option>
              {f.options.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
            </select>
          ))}
        </div>
      )}
      {list.loading && <p className="text-sm text-gray-500">Loading…</p>}
      {list.error && <p className="text-sm text-red-700">{list.error}</p>}
      {!list.loading && !list.error && (
        <div className="overflow-x-auto bg-white rounded shadow mb-4">
          <table className="min-w-full text-sm">
            <thead><tr className="bg-gray-50">{columns.map((c) => <th key={c.key} className="text-left px-3 py-2 font-semibold">{c.label}</th>)}<th className="px-3 py-2" /></tr></thead>
            <tbody>
              {list.items.map((r) => (
                <tr key={Number(r.id)} className="border-t">
                  {columns.map((c) => <td key={c.key} className="px-3 py-2">{c.render ? c.render(r) : val(r, c.key)}</td>)}
                  <td className="px-3 py-2 whitespace-nowrap">
                    <button onClick={() => { setEditing(Number(r.id)); setForm(Object.fromEntries(Object.entries(r).filter(([, v]) => v !== null && typeof v !== "object").map(([k, v]) => [k, String(v)]))); }} className="text-blue-700 underline text-xs">Edit</button>
                    <button onClick={() => void remove(Number(r.id))} className="text-red-700 underline text-xs ml-2">Delete</button>
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
          {fields.map((f) => (
            <label key={f.key} className="text-sm">
              {f.label}{f.required ? " *" : ""}
              {f.type === "select" ? (
                <select value={form[f.key] ?? ""} onChange={(e) => setForm({ ...form, [f.key]: e.target.value })} className="w-full border rounded px-2 py-1.5 mt-0.5">
                  <option value="">—</option>
                  {(f.options ?? []).map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                </select>
              ) : (
                <input type={f.type ?? "text"} required={f.required} value={form[f.key] ?? ""} onChange={(e) => setForm({ ...form, [f.key]: e.target.value })} className="w-full border rounded px-2 py-1.5 mt-0.5" />
              )}
            </label>
          ))}
        </div>
        <div className="mt-3 flex gap-2">
          <button disabled={busy} className="bg-blue-600 text-white px-4 py-1.5 rounded text-sm disabled:opacity-50">{busy ? "Saving…" : editing ? "Save" : "Create"}</button>
          {editing && <button type="button" onClick={() => { setEditing(null); setForm({}); }} className="border px-4 py-1.5 rounded text-sm">Cancel</button>}
        </div>
      </form>
    </>
  );
}
