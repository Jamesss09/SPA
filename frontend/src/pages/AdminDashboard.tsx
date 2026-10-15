import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../lib/api";

const LINKS: [string, string][] = [
  ["/admin/school-years", "School years"],
  ["/admin/grading-periods", "Grading periods"],
  ["/admin/grade-levels", "Grade levels"],
  ["/admin/sections", "Sections"],
  ["/admin/rooms", "Rooms"],
  ["/admin/subjects", "Subjects"],
  ["/admin/users", "Users"],
];

export default function AdminDashboard() {
  const [counts, setCounts] = useState<Record<string, number | string>>({});
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    (async () => {
      try {
        const [years, sections, subjects, users] = await Promise.all([
          api.get("/school-years"),
          api.get("/sections"),
          api.get("/subjects"),
          api.get("/users"),
        ]);
        setCounts({
          "School years": years.data.total ?? 0,
          Sections: sections.data.total ?? 0,
          Subjects: subjects.data.total ?? 0,
          Users: users.data.total ?? 0,
        });
      } catch {
        setError("Could not load counts — is the API running?");
      }
    })();
  }, []);

  return (
    <>
      <h2 className="text-xl font-bold mb-3">Admin overview</h2>
      {error && <p className="text-sm text-red-700 mb-3">{error}</p>}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        {Object.entries(counts).map(([k, v]) => (
          <div key={k} className="bg-white rounded shadow p-4">
            <div className="text-sm text-gray-500">{k}</div>
            <div className="text-2xl font-bold">{v}</div>
          </div>
        ))}
      </div>
      <div className="grid md:grid-cols-2 gap-3">
        {LINKS.map(([to, label]) => (
          <Link key={to} to={to} className="bg-white rounded shadow p-4 hover:bg-gray-50">
            <strong>{label}</strong>
            <span className="text-sm text-gray-500"> → manage</span>
          </Link>
        ))}
      </div>
    </>
  );
}
