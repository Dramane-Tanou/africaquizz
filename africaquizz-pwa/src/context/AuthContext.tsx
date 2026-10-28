import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from "react";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { getToken, setToken, setUnauthorizedHandler } from "../lib/api";
import { authApi } from "../lib/endpoints";
import { readJSON, writeJSON } from "../lib/storage";
import type { LoginRequest, RegisterRequest } from "../types/api";
import type { MeProgress } from "../types/extra";

interface AuthCtx {
  token: string | null;
  user: MeProgress | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  onboarded: boolean;
  markOnboarded: () => void;
  login: (body: LoginRequest) => Promise<void>;
  register: (body: RegisterRequest) => Promise<{ requiresParental: boolean }>;
  logout: () => Promise<void>;
  refresh: () => void;
}

const AuthContext = createContext<AuthCtx | null>(null);
export const ME_KEY = ["me"] as const;
const ONBOARDED_KEY = "aq.onboarded";

export function AuthProvider({ children }: { children: ReactNode }) {
  const qc = useQueryClient();
  const [token, setTok] = useState<string | null>(() => getToken());
  const [onboarded, setOnboarded] = useState<boolean>(() => readJSON(ONBOARDED_KEY, false));

  const me = useQuery({
    queryKey: ME_KEY,
    queryFn: authApi.me,
    enabled: !!token,
    staleTime: 60_000,
    retry: 1,
  });

  const clear = useCallback(() => {
    setToken(null);
    setTok(null);
    qc.removeQueries();
  }, [qc]);

  useEffect(() => {
    setUnauthorizedHandler(clear);
  }, [clear]);

  const accept = useCallback(
    (tok: string, user: MeProgress) => {
      setToken(tok);
      setTok(tok);
      qc.setQueryData(ME_KEY, user);
      writeJSON(ONBOARDED_KEY, true);
      setOnboarded(true);
    },
    [qc],
  );

  const login = useCallback(
    async (body: LoginRequest) => {
      const res = await authApi.login(body);
      accept(res.token, res.user as MeProgress);
    },
    [accept],
  );

  const register = useCallback(
    async (body: RegisterRequest) => {
      const res = await authApi.register(body);
      accept(res.token, res.user as MeProgress);
      return { requiresParental: !!res.requires_parental_consent };
    },
    [accept],
  );

  const logout = useCallback(async () => {
    try {
      await authApi.logout();
    } catch {
      /* le jeton est supprimé côté client dans tous les cas */
    }
    clear();
  }, [clear]);

  const markOnboarded = useCallback(() => {
    writeJSON(ONBOARDED_KEY, true);
    setOnboarded(true);
  }, []);

  const value = useMemo<AuthCtx>(
    () => ({
      token,
      user: me.data ?? null,
      isAuthenticated: !!token,
      isLoading: !!token && me.isLoading,
      onboarded,
      markOnboarded,
      login,
      register,
      logout,
      refresh: () => void qc.invalidateQueries({ queryKey: ME_KEY }),
    }),
    [token, me.data, me.isLoading, onboarded, markOnboarded, login, register, logout, qc],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth doit être utilisé dans AuthProvider");
  return ctx;
}
