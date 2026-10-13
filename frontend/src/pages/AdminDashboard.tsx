export default function AdminDashboard() {
  return (
    <>
      <h2 className="text-xl font-bold mb-3">Admin overview</h2>
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
        {[["Students", "95"], ["Faculty", "12"], ["Pending schedules", "1"], ["Grade batches", "3"]].map(([k, v]) => (
          <div key={k} className="bg-white rounded shadow p-4">
            <div className="text-sm text-gray-500">{k}</div>
            <div className="text-2xl font-bold">{v}</div>
          </div>
        ))}
      </div>
      <p className="text-sm text-gray-500 mt-3">Phase 1 shell — CRUD, imports, approvals land in Phases 3–6.</p>
    </>
  );
}
