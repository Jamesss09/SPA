import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../auth/AuthContext";
import { api } from "../lib/api";

export default function ChangePassword() {
  const { user, refresh, logout } = useAuth();
  const nav = useNavigate();
  const [current, setCurrent] = useState("");
  const [next, setNext] = useState("");
  const [confirm, setConfirm] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setBusy(true);
    try {
      await api.post("/password/change", {
        current_password: current,
        password: next,
        password_confirmation: confirm,
      });
      await refresh();
      if (user) {
        nav(user.role === "admin" ? "/admin" : user.role === "faculty" ? "/faculty" : "/student", { replace: true });
      } else {
        nav("/login", { replace: true });
      }
    } catch (err: unknown) {
      const data = (err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response?.data;
      setError(data?.errors ? Object.values(data.errors).flat().join(" ") : (data?.message ?? "Password change failed."));
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center p-6 bg-gray-100">
      <form onSubmit={submit} className="bg-white rounded shadow p-8 w-full max-w-md">
        <h1 className="text-xl font-bold">Change password</h1>
        <p className="text-sm text-gray-500 mb-4">
          {user?.must_change_password ? "Your account requires a password change before continuing." : "Update your password."}
        </p>
        {error && <p className="text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2 mb-3">{error}</p>}
        <label className="text-sm font-semibold">Current password</label>
        <input type="password" value={current} onChange={(e) => setCurrent(e.target.value)} autoComplete="current-password" className="w-full border rounded px-3 py-2 mt-1 mb-3" />
        <label className="text-sm font-semibold">New password (min 8)</label>
        <input type="password" value={next} onChange={(e) => setNext(e.target.value)} autoComplete="new-password" className="w-full border rounded px-3 py-2 mt-1 mb-3" />
        <label className="text-sm font-semibold">Confirm new password</label>
        <input type="password" value={confirm} onChange={(e) => setConfirm(e.target.value)} autoComplete="new-password" className="w-full border rounded px-3 py-2 mt-1 mb-4" />
        <button type="submit" disabled={busy} className="w-full bg-blue-600 text-white py-2 rounded disabled:opacity-50">
          {busy ? "Saving…" : "Save new password"}
        </button>
        <button type="button" onClick={() => { void logout().then(() => nav("/login", { replace: true })); }} className="mt-3 text-sm text-red-600 underline">
          Logout
        </button>
      </form>
    </div>
  );
}
