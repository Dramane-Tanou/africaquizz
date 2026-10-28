import { useSearchParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { leaderboardApi } from "../lib/endpoints";
import { flag } from "../lib/game";
import { useAuth } from "../context/AuthContext";
import { useErrorMessage } from "../hooks/useCommon";
import { Alert, Button, Card, PageTitle, Segmented, Skeleton, cx } from "../components/ui/primitives";
import { Avatar } from "../components/ui/Brand";
import type { LeaderboardPeriod, LeaderboardScope } from "../types/api";

const SCOPES: LeaderboardScope[] = ["daily", "global", "country", "friends"];
const PERIODS: LeaderboardPeriod[] = ["week", "month", "all", "day"];

export default function Leaderboard() {
  const { t } = useTranslation();
  const { isAuthenticated, user } = useAuth();
  const errMsg = useErrorMessage();
  const [params, setParams] = useSearchParams();
  const scope = (SCOPES.includes(params.get("scope") as LeaderboardScope) ? params.get("scope") : "global") as LeaderboardScope;
  const period = (PERIODS.includes(params.get("period") as LeaderboardPeriod) ? params.get("period") : "week") as LeaderboardPeriod;

  const q = useQuery({
    queryKey: ["leaderboard", scope, period, isAuthenticated],
    queryFn: () => leaderboardApi.get(scope, scope === "daily" ? "day" : period),
    placeholderData: keepPreviousData,
    staleTime: 30_000,
  });

  const update = (next: Partial<{ scope: string; period: string }>) => {
    const p = new URLSearchParams(params);
    Object.entries(next).forEach(([k, v]) => v && p.set(k, v));
    setParams(p, { replace: true });
  };

  const entries = q.data?.data.entries ?? [];
  const me = q.data?.data.me;
  const unit = scope === "daily" ? t("leaderboard.pts") : t("leaderboard.xp");
  const myPseudo = user?.profile?.pseudo;

  let emptyText = t("leaderboard.empty");
  if (scope === "friends") emptyText = t("leaderboard.friendsSoon");
  else if (scope === "country" && !user?.profile?.country_id) emptyText = t("leaderboard.noCountry");

  return (
    <div className="space-y-4">
      <PageTitle>🏆 {t("leaderboard.title")}</PageTitle>
      <Segmented label={t("leaderboard.title")} value={scope} onChange={(v) => update({ scope: v })} options={SCOPES.map((s) => ({ value: s, label: t(`leaderboard.scope_${s}`) }))} />
      {scope !== "daily" && scope !== "friends" && (
        <Segmented label={t("leaderboard.period")} value={period} onChange={(v) => update({ period: v })} options={PERIODS.map((p) => ({ value: p, label: t(`leaderboard.period_${p}`) }))} />
      )}

      {isAuthenticated && scope !== "friends" && (
        <Card className="flex items-center justify-between bg-brand-gold/20 py-3">
          <span className="font-bold">{t("leaderboard.me")}</span>
          <span className="font-title text-xl font-bold">
            {me ? (
              <>
                #{me.rank} · {me.value.toLocaleString()} {unit}
              </>
            ) : (
              <span className="text-base font-semibold text-ink-2">{t("leaderboard.notRanked")}</span>
            )}
          </span>
        </Card>
      )}

      {q.isLoading ? (
        <div className="space-y-2">
          {Array.from({ length: 6 }, (_, i) => (
            <Skeleton key={i} className="h-16" />
          ))}
        </div>
      ) : q.isError ? (
        <Alert tone="danger" action={<Button variant="secondary" className="min-h-[40px] px-3 py-1 text-sm" onClick={() => void q.refetch()}>{t("app.retry")}</Button>}>
          {errMsg(q.error)}
        </Alert>
      ) : entries.length === 0 ? (
        <Card className="text-center">
          <p className="text-4xl" aria-hidden>
            🌍
          </p>
          <p className="mt-2 font-semibold">{emptyText}</p>
          {scope !== "friends" && <p className="mt-1 text-sm text-ink-2">{t("leaderboard.eligibility")}</p>}
        </Card>
      ) : (
        <ol className={cx("space-y-2 transition-opacity", q.isFetching && "opacity-60")}>
          {entries.map((e) => {
            const mine = !!myPseudo && e.user.pseudo === myPseudo;
            return (
              <li
                key={`${e.rank}-${e.user.pseudo}`}
                className={cx(
                  "flex items-center gap-3 rounded-bubble border-2 px-3 py-2",
                  mine ? "border-black bg-brand-gold/30 dark:border-brand-gold" : "border-line bg-surface",
                )}
              >
                <span
                  className={cx(
                    "grid h-9 w-9 shrink-0 place-items-center rounded-full font-title font-bold",
                    e.rank === 1 ? "bg-brand-gold text-black" : e.rank === 2 ? "bg-[#C0C0C0] text-black" : e.rank === 3 ? "bg-[#CD7F32] text-black" : "text-ink-2",
                  )}
                >
                  {e.rank}
                </span>
                <Avatar index={hash(e.user.pseudo)} size={40} initial={e.user.pseudo} label={e.user.pseudo} />
                <span className="min-w-0 flex-1">
                  <span className="block truncate font-bold">
                    {e.user.pseudo} {flag(e.user.country)}
                  </span>
                  <span className="block text-xs text-ink-2">
                    {t(`ranks.${e.user.rank}`, { defaultValue: e.user.rank })}
                    {e.detail?.correct_answers != null && ` · ${e.detail.correct_answers}/10`}
                  </span>
                </span>
                <span className="font-title text-lg font-bold">
                  {e.value.toLocaleString()} <span className="text-xs text-ink-2">{unit}</span>
                </span>
              </li>
            );
          })}
        </ol>
      )}
      {q.data?.meta?.eligible_players != null && (
        <p className="text-center text-xs text-ink-2">{t("leaderboard.players", { count: q.data.meta.eligible_players })}</p>
      )}
    </div>
  );
}

function hash(s: string) {
  let h = 0;
  for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) | 0;
  return Math.abs(h);
}
