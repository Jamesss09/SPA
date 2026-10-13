import { useNavigate } from "react-router-dom";
import { useAuth, type SessionUser } from "../auth/AuthContext";

const DEMO: SessionUser[] = [
  { name: "Admin Principal", role: "admin" },
  { name: "Maria Santos", role: "faculty" },
  { name: "John Enquig", role: "student" },
];

export default function Login() {
  const { login } = useAuth();
  const nav = useNavigate();
  const enter = (u: SessionUser) => {
    login(u); // Phase 2: replace with POST /api/login + GET /api/me (Sanctum)
    nav(u.role === "admin" ? "/admin" : u.role === "faculty" ? "/faculty" : "/student");
  };
  return (
    <div className="min-h-screen flex items-center justify-center p-6">
      <div className="bg-white rounded shadow p-8 w-full max-w-md">
        <h1 className="text-xl font-bold">San Pascual Academy</h1>
        <p className="text-sm text-gray-500 mb-4">Student Portal — Phase 1 shell (mock session)</p>
        <div className="grid gap-2">
          {DEMO.map((u) => (
            <button key={u.role} onClick={() => enter(u)} className="bg-blue-600 text-white py-2 rounded">
              Continue as {u.role} ({u.name})
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}
