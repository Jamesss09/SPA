import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../auth/AuthContext";

function dashboardFor(role: string) {
  return role === "admin" ? "/admin" : role === "faculty" ? "/faculty" : "/student";
}

export default function Login() {
  const { user, loading, login } = useAuth();
  const nav = useNavigate();
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  if (!loading && user) {
    nav(user.must_change_password ? "/change-password" : dashboardFor(user.role), { replace: true });
    return null;
  }

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setBusy(true);
    try {
      const u = await login(username.trim(), password);
      nav(u.must_change_password ? "/change-password" : dashboardFor(u.role), { replace: true });
    } catch (err: unknown) {
      const status = (err as { response?: { status?: number; data?: { message?: string; errors?: Record<string, string[]> } } })?.response;
      if (status?.status === 403) {
        setError(status.data?.message ?? "Account is deactivated.");
      } else if (status?.status === 429) {
        setError("Too many attempts. Try again in a minute.");
      } else if (status?.data?.errors) {
        setError(Object.values(status.data.errors).flat().join(" "));
      } else {
        setError("Login failed. Check your credentials and try again.");
      }
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center p-6">
      <form onSubmit={submit} className="bg-white rounded shadow p-8 w-full max-w-md">
        <h1 className="text-xl font-bold">San Pascual Academy</h1>
        <p className="text-sm text-gray-500 mb-4">Student Portal — shared login</p>
        {error && <p className="text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2 mb-3">{error}</p>}
        <label className="text-sm font-semibold">Username</label>
        <input
          value={username} onChange={(e) => setUsername(e.target.value)}
          placeholder="e.g. Enquig.john" autoComplete="username"
          className="w-full border rounded px-3 py-2 mt-1 mb-3"
        />
        <label className="text-sm font-semibold">Password</label>
        <input
          type="password" value={password} onChange={(e) => setPassword(e.target.value)}
          autoComplete="current-password"
          className="w-full border rounded px-3 py-2 mt-1 mb-4"
        />
        <button type="submit" disabled={busy || loading} className="w-full bg-blue-600 text-white py-2 rounded disabled:opacity-50">
          {busy ? "Signing in…" : "Sign in"}
        </button>
      </form>
    </div>
  );
}
