import { createContext, useContext, useState, type ReactNode } from "react";

export type Role = "admin" | "faculty" | "student";
export interface SessionUser { name: string; role: Role }

const AuthCtx = createContext<{
  user: SessionUser | null;
  login: (u: SessionUser) => void;
  logout: () => void;
} | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  // Prototype session only — Phase 2 wires this to Laravel Sanctum GET /me.
  const [user, setUser] = useState<SessionUser | null>(null);
  return (
    <AuthCtx.Provider value={{ user, login: setUser, logout: () => setUser(null) }}>
      {children}
    </AuthCtx.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthCtx);
  if (!ctx) throw new Error("useAuth outside provider");
  return ctx;
}
