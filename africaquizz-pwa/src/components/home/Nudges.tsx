// Incitations à revenir jouer, calculées sur l'activité réelle du joueur.
// Ton positif uniquement : pas de culpabilisation, pas de fausse urgence, et un point d'arrêt
// clair une fois l'objectif du jour atteint.
import { useQuery } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { useAuth } from "../../context/AuthContext";
import { engagementApi } from "../../lib/endpoints";
import { levelProgress } from "../../lib/game";
import type { PlayMode } from "../../types/extra";
import { Button, Card, ProgressBar, cx } from "../ui/primitives";
import { Flag, countryName } from "../ui/Flag";

export const ENGAGEMENT_KEY = ["me", "engagement"] as const;
const MIN_COUNTRY_QUESTIONS = 5;

export function useEngagement() {
  const { isAuthenticated } = useAuth();
  return useQuery({ queryKey: ENGAGEMENT_KEY, queryFn: engagementApi.get, enabled: isAuthenticated, staleTime: 30_000 });
}

/** Série + objectif du jour, en une carte. */
export function StreakGoalCard() {
  const { t } = useTranslation();
  const q = useEngagement();
  if (!q.data) return null;
  const { streak, daily_goal: goal } = q.data;
  const goalReached = goal.done >= goal.target;
  // Un gel de série est offert tous les 7 jours (Streak::updateOnPlay).
  const daysToFreeze = 7 - (streak.current % 7);

  let streakText: string;
  if (streak.played_today) streakText = t("nudge.streakSafe", { count: streak.current });
  else if (streak.current > 0) streakText = t("nudge.streakNext", { count: streak.current, next: streak.current + 1 });
  else streakText = t("nudge.streakStart");

  return (
    <Card className="space-y-4">
      <div className="flex items-start gap-3">
        <span className={cx("grid h-12 w-12 shrink-0 place-items-center rounded-2xl border-2 border-black text-2xl", streak.played_today ? "bg-success/20" : "bg-brand-red/20")} aria-hidden>
          {streak.played_today ? "✅" : "🔥"}
        </span>
        <div>
          <p className="font-title text-lg font-semibold leading-tight">{streakText}</p>
          {streak.current > 0 && (
            <p className="mt-1 text-sm text-ink-2">{t("nudge.freezeHint", { count: daysToFreeze })}</p>
          )}
        </div>
      </div>
      <div>
        <div className="flex items-center justify-between text-sm font-bold">
          <span>🎯 {t("nudge.goalTitle")}</span>
          <span>
            {goal.done}/{goal.target}
          </span>
        </div>
        <ProgressBar value={(goal.done / goal.target) * 100} color={goalReached ? "bg-success" : "bg-brand-gold"} className="mt-2" label={t("nudge.goalTitle")} />
        <p className="mt-2 text-sm text-ink-2">
          {goalReached ? t("nudge.goalDone") : t("nudge.goalLeft", { count: goal.target - goal.done })}
        </p>
      </div>
    </Card>
  );
}

/** Carte « Spécial mon pays » : questions sur le pays du joueur et sa place dans le classement national. */
export function CountryCard({ onPlay, loading }: { onPlay: (mode: PlayMode, country?: string) => void; loading: boolean }) {
  const { t, i18n } = useTranslation();
  const q = useEngagement();
  const country = q.data?.country;
  if (!country) return null;
  const name = countryName(country.iso2, i18n.resolvedLanguage ?? "fr");
  const playable = country.question_count >= MIN_COUNTRY_QUESTIONS;
  const { week } = country;

  return (
    <section className="relative overflow-hidden rounded-bubble border-2 border-black bg-brand-green p-5 text-white shadow-flat">
      <div className="flex items-center gap-3">
        <Flag iso2={country.iso2} size={32} label={name} className="ring-2 ring-white" />
        <div className="min-w-0">
          <p className="text-xs font-bold uppercase tracking-widest opacity-90">{t("nudge.countryKicker")}</p>
          <h2 className="truncate font-title text-2xl font-bold">{t("nudge.countryTitle", { country: name })}</h2>
        </div>
      </div>
      <p className="mt-3 font-semibold">
        {playable ? t("nudge.countryQuestions", { count: country.question_count, country: name }) : t("nudge.countrySoon", { country: name })}
      </p>
      <p className="mt-2 rounded-2xl bg-black/20 px-3 py-2 text-sm font-semibold">
        {week.rank
          ? week.next
            ? t("nudge.countryRankNext", { rank: week.rank, players: week.players, country: name, xp: week.next.gap_xp, pseudo: week.next.pseudo })
            : t("nudge.countryRankFirst", { country: name })
          : t("nudge.countryJoin", { country: name })}
      </p>
      {playable && (
        <Button variant="dark" block className="mt-4" loading={loading} onClick={() => onPlay("country", country.iso2)}>
          {t("nudge.countryPlay")} →
        </Button>
      )}
    </section>
  );
}

/** Rappels affichés sur l'écran de résultats. */
export function ResultNudges() {
  const { t } = useTranslation();
  const { user } = useAuth();
  const q = useEngagement();
  if (!q.data || !user?.profile) return null;
  const lp = levelProgress(user.profile.total_xp);
  const goal = q.data.daily_goal;
  const left = goal.target - goal.done;

  return (
    <Card className="space-y-2">
      <p className="font-semibold">⬆️ {t("nudge.levelLeft", { xp: (lp.needed - lp.current).toLocaleString(), level: lp.level + 1 })}</p>
      <p className="font-semibold">{left > 0 ? `🎯 ${t("nudge.goalLeft", { count: left })}` : `🎉 ${t("nudge.goalDone")}`}</p>
    </Card>
  );
}
