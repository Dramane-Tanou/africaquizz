import { useCallback, useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { useQuery } from "@tanstack/react-query";
import { normalizeError } from "../lib/api";
import { authApi } from "../lib/endpoints";
import { readJSON } from "../lib/storage";
import { useAuth } from "../context/AuthContext";

export function useOnline() {
  const [online, setOnline] = useState(() => navigator.onLine);
  useEffect(() => {
    const on = () => setOnline(true);
    const off = () => setOnline(false);
    window.addEventListener("online", on);
    window.addEventListener("offline", off);
    return () => {
      window.removeEventListener("online", on);
      window.removeEventListener("offline", off);
    };
  }, []);
  return online;
}

/** Message d'erreur lisible et traduit à partir de n'importe quelle erreur. */
export function useErrorMessage() {
  const { t } = useTranslation();
  return useCallback((err: unknown, fallback?: string): string => {
    const n = normalizeError(err);
    switch (n.kind) {
      case "network":
        return t("errors.network");
      case "rate_limit":
        return n.retryAfter ? t("errors.rate_limit_s", { s: n.retryAfter }) : t("errors.rate_limit");
      case "server":
        return t("errors.server");
      case "unauthorized":
        return n.message || t("errors.unauthorized");
      case "not_found":
        return n.message || fallback || t("errors.not_found");
      default:
        return n.message || fallback || t("errors.generic");
    }
  }, [t]);
}

export const STATS_KEY = ["me", "export"] as const;
const BADGE_CACHE = "aq.badges";

/** Statistiques du joueur, calculées à partir de l'export RGPD (seul endpoint qui expose série et badges). */
export function useMyStats() {
  const { isAuthenticated } = useAuth();
  return useQuery({
    queryKey: STATS_KEY,
    enabled: isAuthenticated,
    staleTime: 2 * 60_000,
    queryFn: async () => {
      const res = await authApi.exportData();
      const d = res.data;
      const finished = (d.quiz_sessions ?? []).filter((s) => s.status === "finished");
      const answers = (d.quiz_sessions ?? []).flatMap((s) => s.answers ?? []);
      const correct = answers.filter((a) => a.is_correct).length;
      const localCodes = readJSON<string[]>(BADGE_CACHE, []);
      const serverCodes = (d.badges ?? []).map((b) => b.badge?.code).filter(Boolean) as string[];
      return {
        createdAt: d.user?.created_at,
        streak: {
          current: d.streak?.current_streak ?? 0,
          longest: d.streak?.longest_streak ?? 0,
          freezes: d.streak?.freezes_available ?? 0,
        },
        games: finished.length,
        accuracy: answers.length ? Math.round((correct / answers.length) * 100) : null,
        bestScore: finished.reduce((m, s) => Math.max(m, s.score ?? 0), 0),
        dailyPlayed: (d.daily_results ?? []).length,
        badgeCount: (d.badges ?? []).length,
        badgeCodes: Array.from(new Set([...serverCodes, ...localCodes])),
      };
    },
  });
}

export { BADGE_CACHE };
