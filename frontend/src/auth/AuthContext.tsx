import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from "react";
import { api, csrf, fetchMe } from "../lib/api";

export type Role = "admin" | "faculty" | "student";
export interface SessionUser {
  id: number;
  name: string;
  username: string;
  role: Role;
  status: string;
  must_change_password: boolean;
}

interface AuthCtxValue {
  user: SessionUser | null;
  loading: boolean;
  login: (username: string, password: string) => Promise<SessionUser>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
}

const AuthCtx = createContext<AuthCtxValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<SessionUser | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    try {
      const data = await fetchMe();
      setUser(data.user);
    } catch {
      setUser(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void refresh(); // restore session on boot (cookie may already exist)
  }, [refresh]);

  const login = useCallback(async (username: string, password: string) => {
    await csrf();
    await api.post("/login", { username, password });
    const data = await fetchMe();
    setUser(data.user);
    return data.user as SessionUser;
  }, []);

  const logout = useCallback(async () => {
    try {
      await api.post("/logout");
    } finally {
      setUser(null);
    }
  }, []);

  return (
    <AuthCtx.Provider value={{ user, loading, login, logout, refresh }}>
      {children}
    </AuthCtx.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthCtx);
  if (!ctx) throw new Error("useAuth outside provider");
  return ctx;
}
