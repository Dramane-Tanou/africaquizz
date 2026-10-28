import { Link, useLocation } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useQuery } from "@tanstack/react-query";
import { useAuth } from "../context/AuthContext";
import { useSettings } from "../context/SettingsContext";
import { dailyApi } from "../lib/endpoints";
import { levelProgress } from "../lib/game";
import { useErrorMessage, useMyStats } from "../hooks/useCommon";
import { useStartGame } from "../hooks/useStartGame";
import { Alert, Button, ButtonLink, ProgressBar, Skeleton, cx } from "../components/ui/primitives";
import { Avatar } from "../components/ui/Brand";

export default function Home() {
  const { t } = useTranslation();
  const { user, isAuthenticated } = useAuth();
  const { settings } = useSettings();
  const stats = useMyStats();
  const { start, loading, error } = useStartGame();
  const errMsg = useErrorMessage();
  const daily = useQuery({ queryKey: ["daily", isAuthenticated], queryFn: dailyApi.status, staleTime: 60_000 });

  const profile = user?.profile;
  const xp = profile?.total_xp ?? 0;
  const lp = levelProgress(xp);
  const pseudo = profile?.pseudo ?? user?.name;
  const welcome = useLocation().state as { welcome?: boolean; parental?: boolean } | null;

  return (
    <div className="space-y-5">
      {/* En-tête joueur */}
      <section className="flex items-center gap-4">
        <Avatar index={settings.avatar} size={60} initial={pseudo} label={pseudo} />
        <div className="min-w-0 flex-1">
          <h1 className="truncate font-title text-2xl font-bold">{pseudo ? t("home.hello", { name: pseudo }) : t("home.helloGuest")}</h1>
          {isAuthenticated && profile ? (
            <>
              <p className="text-sm font-bold text-ink-2">
                {t(`ranks.${profile.rank}`)} · {t("home.level", { level: lp.level })}
              </p>
              <ProgressBar value={lp.pct} className="mt-2" label={t("home.xpToNext", { xp: lp.needed - lp.current, level: lp.level + 1 })} />
              <p className="mt-1 text-xs text-ink-2">{t("home.xpToNext", { xp: lp.needed - lp.current, level: lp.level + 1 })}</p>
            </>
          ) : (
            <p className="text-sm text-ink-2">{t("home.guestCta")}</p>
          )}
        </div>
        {isAuthenticated && (
          <div className="flex flex-col items-center rounded-2xl border-2 border-black bg-surface px-3 py-2 shadow-flat-sm dark:border-line" title={t("home.streak")}>
            <span className="text-2xl" aria-hidden>
              🔥
            </span>
            <span className="font-title text-lg font-bold leading-none">{stats.data?.streak.current ?? "–"}</span>
            <span className="sr-only">{t("home.streakDays", { count: stats.data?.streak.current ?? 0 })}</span>
          </div>
        )}
      </section>

      {welcome?.welcome && pseudo && <Alert tone="success">{t("auth.welcome", { name: pseudo })}</Alert>}
      {welcome?.parental && <Alert tone="info">{t("auth.parentalSent")}</Alert>}

      {!isAuthenticated && (
        <div className="grid grid-cols-2 gap-3">
          <ButtonLink to="/inscription">{t("auth.register")}</ButtonLink>
          <ButtonLink to="/connexion" variant="secondary">
            {t("auth.login")}
          </ButtonLink>
        </div>
      )}

      {/* Défi du jour */}
      <section className="relative overflow-hidden rounded-bubble border-2 border-black bg-brand-gold p-5 text-black shadow-flat">
        <div className="pattern-bg pointer-events-none absolute inset-0 opacity-40" aria-hidden />
        <div className="relative">
          <Link to="/defi-du-jour" className="inline-flex min-h-[32px] items-center text-xs font-bold uppercase tracking-widest hover:underline">
            📅 {t("home.dailyTitle")} ›
          </Link>
          {daily.isLoading ? (
            <Skeleton className="mt-3 h-12" />
          ) : daily.isError ? (
            <p className="mt-2 font-semibold">{errMsg(daily.error)}</p>
          ) : (
            <DailyBody status={daily.data!.status} result={daily.data!.my_result} />
          )}
          {daily.data?.status === "available" && (
            <Button variant="dark" className="mt-4" block onClick={() => void start("daily")} loading={loading === "daily"}>
              {t("home.play")} →
            </Button>
          )}
          {daily.data && daily.data.status !== "available" && daily.data.status !== "not_yet" && (
            <Link to="/classements?scope=daily" className="mt-3 inline-block min-h-[44px] py-2 font-bold underline underline-offset-4">
              {t("home.seeRanking")} →
            </Link>
          )}
        </div>
      </section>

      {error && <Alert tone="danger">{error}</Alert>}

      {/* Modes de jeu */}
      <section>
        <h2 className="mb-3 font-title text-xl font-semibold">{t("home.modesTitle")}</h2>
        <div className="grid gap-3">
          <ModeCard
            icon="🎯"
            color="bg-brand-green"
            title={t("home.classic")}
            desc={t("home.classicDesc")}
            loading={loading === "classic"}
            onClick={() => void start("classic")}
          />
          <ModeCard
            icon="⚡"
            color="bg-brand-red"
            title={t("home.rapid")}
            desc={t("home.rapidDesc")}
            loading={loading === "rapid"}
            onClick={() => void start("rapid")}
          />
          <ModeCard icon="🔁" color="bg-info" title={t("home.revision")} desc={t("home.revisionDesc")} to="/revision" />
        </div>
      </section>
    </div>
  );
}

function DailyBody({ status, result }: { status: string; result?: { score: number; correct: number } | null }) {
  const { t } = useTranslation();
  if (status === "available") return <p className="mt-2 font-title text-xl font-semibold">{t("home.dailyAvailable")}</p>;
  if (status === "played" && result)
    return <p className="mt-2 font-title text-xl font-semibold">{t("home.dailyPlayed", { correct: result.correct, score: result.score })}</p>;
  if (status === "closed") return <p className="mt-2 font-title text-xl font-semibold">{t("home.dailyClosed")}</p>;
  return <p className="mt-2 font-title text-xl font-semibold">{t("home.dailyNotYet")}</p>;
}

function ModeCard({
  icon,
  color,
  title,
  desc,
  onClick,
  to,
  loading,
}: {
  icon: string;
  color: string;
  title: string;
  desc: string;
  onClick?: () => void;
  to?: string;
  loading?: boolean;
}) {
  const inner = (
    <>
      <span className={cx("grid h-14 w-14 shrink-0 place-items-center rounded-2xl border-2 border-black text-2xl", color)} aria-hidden>
        {icon}
      </span>
      <span className="min-w-0 flex-1">
        <span className="block font-title text-xl font-semibold">{title}</span>
        <span className="block text-sm text-ink-2">{desc}</span>
      </span>
      <span aria-hidden className="text-2xl text-ink-2">
        {loading ? "…" : "›"}
      </span>
    </>
  );
  const cls =
    "flex w-full items-center gap-4 rounded-bubble border-2 border-black bg-surface p-4 text-left shadow-flat transition-transform duration-150 hover:-translate-y-0.5 active:translate-x-[2px] active:translate-y-[2px] active:shadow-flat-sm disabled:opacity-60 dark:border-line";
  return to ? (
    <Link to={to} className={cls}>
      {inner}
    </Link>
  ) : (
    <button type="button" onClick={onClick} disabled={loading} className={cls} aria-busy={loading || undefined}>
      {inner}
    </button>
  );
}
