import { useEffect, useState } from "react";
import { api } from "../../lib/api";
import { errMsg } from "./CrudPage";
import { useOptions } from "./SetupPages";

interface PreviewRow {
  row: number;
  last_name: string;
  first_name: string;
  student_number?: string;
  username?: string;
  status: string;
  detail: string;
}

const badge = (s: string) => {
  const c = { ok: "bg-green-100 text-green-800", matched: "bg-blue-100 text-blue-800", duplicate: "bg-yellow-100 text-yellow-800", error: "bg-red-100 text-red-800" }[s] ?? "bg-gray-100";
  return <span className={`px-2 py-0.5 rounded text-xs font-semibold ${c}`}>{s}</span>;
};

// Shared import widget: section picker + upload + preview + commit.
// Used by admin (/admin/imports, any section) and faculty (own sections only —
 // the API still enforces scope; the picker here just lists sections).
export default function ImportWidget({ facultyOnly = false }: { facultyOnly?: boolean }) {
  const years = useOptions("/school-years", "id", "name");
  const [year, setYear] = useState("");
  const [sections, setSections] = useState<{ value: number; label: string }[]>([]);
  const [section, setSection] = useState("");
  const [file, setFile] = useState<File | null>(null);
  const [preview, setPreview] = useState<{ rows: PreviewRow[]; total: number; created: number; matched: number; duplicates: number; errors: number } | null>(null);
  const [result, setResult] = useState<Record<string, unknown> | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (!year) {
      setSections([]);
      return;
    }
    api.get("/sections", { params: { school_year_id: year, per_page: 100 } })
      .then((res) => {
        const list = res.data.data ?? [];
        setSections(list.map((s: { id: number; name: string }) => ({ value: s.id, label: s.name })));
      })
      .catch(() => undefined);
  }, [year]);

  const doPreview = async () => {
    if (!section || !file) {
      setMsg("Pick a section and a file first.");
      return;
    }
    setBusy(true);
    setMsg(null);
    setResult(null);
    try {
      const fd = new FormData();
      fd.append("section_id", section);
      fd.append("file", file);
      const { data } = await api.post("/enrollments/import/preview", fd);
      setPreview(data);
    } catch (e) {
      setMsg(errMsg(e, "Preview failed."));
    } finally {
      setBusy(false);
    }
  };

  const doCommit = async () => {
    if (!preview) return;
    if (!confirm(`Commit ${preview.created + preview.matched} rows to the section?`)) return;
    setBusy(true);
    try {
      const { data } = await api.post("/enrollments/import/commit", { section_id: Number(section), rows: preview.rows });
      setResult(data);
      setPreview(null);
    } catch (e) {
      setMsg(errMsg(e, "Commit failed."));
    } finally {
      setBusy(false);
    }
  };

  return (
    <div>
      <div className="bg-white rounded shadow p-4 mb-4 max-w-3xl">
        <div className="grid md:grid-cols-3 gap-2">
          <label className="text-sm">School year
            <select value={year} onChange={(e) => setYear(e.target.value)} className="w-full border rounded px-2 py-1.5 mt-0.5">
              <option value="">—</option>
              {years.map((y) => <option key={y.value} value={y.value}>{y.label}</option>)}
            </select>
          </label>
          <label className="text-sm">Section *
            <select value={section} onChange={(e) => setSection(e.target.value)} className="w-full border rounded px-2 py-1.5 mt-0.5">
              <option value="">—</option>
              {sections.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
            </select>
          </label>
          <label className="text-sm">File (.csv / .xlsx) *
            <input type="file" accept=".csv,.txt,.xlsx,.xls" onChange={(e) => setFile(e.target.files?.[0] ?? null)} className="w-full text-sm mt-1" />
          </label>
        </div>
        <p className="text-xs text-gray-500 mt-2">Columns: last_name*, first_name*, middle_name, birth_date (Y-m-d), contact_number, student_number (optional). {facultyOnly && "You may only import into your assigned sections."}</p>
        {msg && <p className="text-sm text-red-700 mt-2">{msg}</p>}
        <button onClick={doPreview} disabled={busy} className="mt-3 bg-blue-600 text-white px-4 py-1.5 rounded text-sm disabled:opacity-50">{busy ? "Working…" : "Preview"}</button>
      </div>

      {preview && (
        <div className="bg-white rounded shadow p-4 mb-4">
          <div className="flex items-center gap-3 mb-2">
            <strong>Preview</strong>
            <span className="text-sm text-gray-600">new {preview.created} · matched {preview.matched} · dup {preview.duplicates} · errors {preview.errors}</span>
            <button onClick={doCommit} disabled={busy} className="ml-auto bg-green-600 text-white px-4 py-1.5 rounded text-sm disabled:opacity-50">Commit valid rows</button>
          </div>
          <div className="overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead><tr className="bg-gray-50"><th className="text-left px-2 py-1">Row</th><th className="text-left px-2 py-1">Name</th><th className="text-left px-2 py-1">Student #</th><th className="text-left px-2 py-1">Username</th><th className="text-left px-2 py-1">Result</th><th className="text-left px-2 py-1">Detail</th></tr></thead>
              <tbody>
                {preview.rows.map((r) => (
                  <tr key={r.row} className="border-t">
                    <td className="px-2 py-1">{r.row}</td>
                    <td className="px-2 py-1">{r.first_name} {r.last_name}</td>
                    <td className="px-2 py-1">{r.student_number ?? "—"}</td>
                    <td className="px-2 py-1">{r.username ?? "—"}</td>
                    <td className="px-2 py-1">{badge(r.status)}</td>
                    <td className="px-2 py-1 text-xs">{r.detail}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {result && (
        <div className="bg-white rounded shadow p-4">
          <strong>Committed</strong>
          <p className="text-sm text-gray-700">Created {String(result.created)} · matched {String(result.matched)} · batch #{String(result.import_batch_id)}</p>
        </div>
      )}
    </div>
  );
}

export function AdminImports() {
  return (
    <>
      <h2 className="text-xl font-bold mb-3">Imports / enrollment</h2>
      <ImportWidget />
    </>
  );
}
