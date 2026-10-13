import { Navigate } from "react-router-dom";
import { useAuth, type Role } from "../auth/AuthContext";

export default function ProtectedRoute({ role, children }: { role: Role; children: React.ReactNode }) {
  const { user } = useAuth();
  if (!user) return <Navigate to="/login" replace />;
  if (user.role !== role) return <Navigate to="/login" replace />;
  return <>{children}</>;
}
