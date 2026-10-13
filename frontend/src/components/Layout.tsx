import { NavLink, useNavigate } from "react-router-dom";
import { useAuth, type Role } from "../auth/AuthContext";

const NAV: Record<Role, [string, string][]> = {
  admin: [
    ["/admin", "Overview"],
    ["/admin?s=users", "Users"],
    ["/admin?s=imports", "Imports"],
    ["/admin?s=schedules", "Schedules"],
    ["/admin?s=grades", "Grades"],
  ],
  faculty: [
    ["/faculty", "My classes"],
    ["/faculty?s=grades", "Grades"],
    ["/faculty?s=schedules", "Schedules"],
  ],
  student: [
    ["/student", "Overview"],
    ["/student?s=schedule", "Schedule"],
    ["/student?s=grades", "Grades"],
  ],
};

export default function Layout({ children }: { children: React.ReactNode }) {
  const { user, logout } = useAuth();
  const nav = useNavigate();
  if (!user) return <>{children}</>;
  return (
    <div className="min-h-screen flex bg-gray-100">
      <aside className="w-56 bg-white shadow p-4">
        <strong>SPA Portal</strong>
        <div className="text-xs text-gray-500 mb-3">A.Y. 2026–2027 · {user.role}</div>
        <nav className="space-y-1">
          {NAV[user.role].map(([to, label]) => (
            <NavLink key={to} to={to} className="block px-3 py-2 rounded hover:bg-gray-100">
              {label}
            </NavLink>
          ))}
        </nav>
        <button
          onClick={() => { logout(); nav("/login"); }}
          className="mt-6 text-sm text-red-600 underline"
        >
          Logout ({user.name})
        </button>
      </aside>
      <main className="flex-1 p-6 max-w-5xl">{children}</main>
    </div>
  );
}
