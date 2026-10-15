import { Navigate } from "react-router-dom";
import { useAuth, type Role } from "../auth/AuthContext";

function dashboardFor(role: string) {
  return role === "admin" ? "/admin" : role === "faculty" ? "/faculty" : "/student";
}

export default function ProtectedRoute({ role, children }: { role: Role; children: React.ReactNode }) {
  const { user, loading } = useAuth();
  if (loading) return <p className="p-6 text-sm text-gray-500">Checking session…</p>;
  if (!user) return <Navigate to="/login" replace />;
  if (user.must_change_password) return <Navigate to="/change-password" replace />;
  if (user.role !== role) return <Navigate to={dashboardFor(user.role)} replace />;
  return <>{children}</>;
}
