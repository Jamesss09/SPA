import { useEffect, useState } from "react";
import { api } from "../../lib/api";
import { errMsg } from "../admin/CrudPage";
import ImportWidget from "../admin/Imports";

interface Assignment {
  id: number;
  subject?: { subject_code: string; subject_name: string };
  section?: { id: number; name: string };
}

interface Student {
  enrollment_id: number;
  name: string;
  username: string;
  student_number: string;
  status: string;
}

export default function Classes() {
  const [rows, setRows] = useState<Assignment[]>([]);
  const [open, setOpen] = useState<number | null>(null);
  const [students, setStudents] = useState<Student[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api.get("/teaching-assignments")
      .then(({ data }) => setRows(data.data ?? []))
      .catch((e) => setError(errMsg(e, "Load failed.")));
  }, []);

  const show = async (a: Assignment) => {
    setOpen(a.id);
    try {
      const { data } = await api.get(`/teaching-assignments/${a.id}/students`);
      setStudents(data);
    } catch (e) {
      setError(errMsg(e, "Load failed."));
    }
  };

  return (
    <>
      <h2 className="text-xl font-bold mb-3">My classes</h2>
      {error && <p className="text-sm text-red-700 mb-2">{error}</p>}
      <div className="grid md:grid-cols-2 gap-3 mb-4">
        {rows.map((a) => (
          <div key={a.id} className="bg-white rounded shadow p-4">
            <strong>{a.subject ? `${a.subject.subject_code} · ${a.subject.subject_name}` : "Assignment"}</strong>
            <p className="text-sm text-gray-500">{a.section?.name}</p>
            <button onClick={() => void show(a)} className="text-blue-700 underline text-sm mt-1">View class list</button>
          </div>
        ))}
        {!rows.length && <p className="text-sm text-gray-500">No classes assigned yet.</p>}
      </div>

      {open && (
        <div className="bg-white rounded shadow p-4 mb-4">
          <h3 className="font-semibold mb-2">Class list</h3>
          <table className="min-w-full text-sm">
            <thead><tr className="bg-gray-50"><th className="text-left px-2 py-1">Name</th><th className="text-left px-2 py-1">Username</th><th className="text-left px-2 py-1">Student #</th></tr></thead>
            <tbody>
              {students.map((s) => (
                <tr key={s.enrollment_id} className="border-t">
                  <td className="px-2 py-1">{s.name}</td>
                  <td className="px-2 py-1">{s.username}</td>
                  <td className="px-2 py-1">{s.student_number}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <h3 className="font-semibold mb-2">Import student list (assigned sections only)</h3>
      <ImportWidget facultyOnly />
    </>
  );
}
