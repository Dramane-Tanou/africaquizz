// Incitations à revenir jouer, calculées sur l'activité réelle du joueur.
// Ton positif uniquement : pas de culpabilisation, pas de fausse urgence, et un point d'arrêt
// clair une fois l'objectif du jour atteint.
import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { useAuth } from "../../context/AuthContext";
import { useSettings } from "../../context/SettingsContext";
import { engagementApi } from "../../lib/endpoints";
import { levelProgress } from "../../lib/game";
import { readJSON, safeSession, writeJSON } from "../../lib/storage";
import type { PlayMode } from "../../types/extra";
import { Button, ButtonLink, Card, ProgressBar, cx } from "../ui/primitives";
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

/**
 * Nudge de facilitation : une seule action proposée, la plus utile du moment
 * (défi du jour, puis révisions dues, puis objectif du jour). Une fois tout fait, la carte le dit
 * et ne pousse plus à jouer.
 */
export function NextStepCard({ dailyAvailable, onPlay, loading }: { dailyAvailable: boolean; onPlay: (mode: PlayMode) => void; loading: boolean }) {
  const { t } = useTranslation();
  const q = useEngagement();
  if (!q.data) return null;
  const { daily_goal: goal, revision_due: due } = q.data;
  const left = goal.target - goal.done;

  let text: string;
  let action: React.ReactNode = null;
  if (dailyAvailable) {
    text = t("nudge.nextDaily");
    action = <Button block onClick={() => onPlay("daily")} loading={loading}>{t("nudge.nextPlayDaily")} →</Button>;
  } else if (due > 0) {
    text = t("nudge.nextRevision", { count: due });
    action = <ButtonLink to="/revision" block>{t("nudge.nextPlayRevision")} →</ButtonLink>;
  } else if (left > 0) {
    text = t("nudge.nextGoal", { count: left });
    action = <Button block onClick={() => onPlay("classic")} loading={loading}>{t("nudge.nextPlayGame")} →</Button>;
  } else {
    text = t("nudge.nextDone");
  }

  return (
    <Card className="space-y-3">
      <p className="text-xs font-bold uppercase tracking-widest text-ink-2">👉 {t("nudge.nextKicker")}</p>
      <p className="font-title text-lg font-semibold leading-tight">{text}</p>
      {action}
    </Card>
  );
}

/** Investissement (modèle HOOK) : la collection de pays explorés grandit avec les bonnes réponses. */
export function DiscoveryCard() {
  const { t } = useTranslation();
  const q = useEngagement();
  const d = q.data?.discovery;
  if (!d || d.available === 0) return null;
  const done = d.discovered >= d.available;

  return (
    <Card className="space-y-2">
      <div className="flex items-center justify-between font-bold">
        <span>🌍 {t("nudge.discoveryTitle")}</span>
        <span>{d.discovered}/{d.available}</span>
      </div>
      <ProgressBar value={(d.discovered / d.available) * 100} color="bg-brand-green" label={t("nudge.discoveryTitle")} />
      <p className="text-sm text-ink-2">
        {t("nudge.discoveryText", { discovered: d.discovered, available: d.available })} {done ? t("nudge.discoveryAll") : t("nudge.discoveryNext")}
      </p>
    </Card>
  );
}

// Session de jeu continue : remise à zéro après 20 minutes sans partie terminée.
const PLAY_RUN_KEY = "aq.playRun";
const RUN_GAP_MS = 20 * 60_000;
const BREAK_AFTER_GAMES = 5;
const BREAK_AFTER_MS = 30 * 60_000;

interface PlayRun {
  startedAt: number;
  lastAt: number;
  games: string[];
}

/** Compte la partie terminée dans la session de jeu en cours et renvoie cette session. */
function trackRun(gameId: string, gameStartedAt: number): PlayRun {
  const now = Date.now();
  const store = safeSession();
  let run = readJSON<PlayRun | null>(PLAY_RUN_KEY, null, store);
  if (!run || now - run.lastAt > RUN_GAP_MS) run = { startedAt: gameStartedAt, lastAt: now, games: [] };
  if (!run.games.includes(gameId)) run = { ...run, lastAt: now, games: [...run.games, gameId] };
  writeJSON(PLAY_RUN_KEY, run, store);
  return run;
}

/**
 * Friction bienveillante : après une longue session, on propose une pause (désactivable dans les
 * paramètres). Le joueur reste libre de continuer.
 */
export function BreakNudge({ gameId, startedAt }: { gameId: string; startedAt: number }) {
  const { t } = useTranslation();
  const { settings } = useSettings();
  const navigate = useNavigate();
  const [run, setRun] = useState<PlayRun | null>(null);
  const [dismissed, setDismissed] = useState(false);

  useEffect(() => setRun(trackRun(gameId, startedAt)), [gameId, startedAt]);

  if (!settings.breakReminders || !run || dismissed) return null;
  const minutes = Math.round((run.lastAt - run.startedAt) / 60_000);
  const hour = new Date().getHours();
  const night = hour >= 22 || hour < 6;
  const long = run.games.length >= BREAK_AFTER_GAMES || run.lastAt - run.startedAt >= BREAK_AFTER_MS;
  if (!long && !(night && run.games.length >= 2)) return null;

  return (
    <Card className="space-y-3 border-info">
      <p className="font-title text-lg font-semibold">☕ {t("nudge.breakTitle")}</p>
      <p className="text-sm">
        {t("nudge.breakText", { count: run.games.length, min: Math.max(1, minutes) })} {night && t("nudge.breakNight")}
      </p>
      <div className="grid grid-cols-2 gap-3">
        <Button variant="secondary" onClick={() => navigate("/", { replace: true })}>
          {t("nudge.breakStop")}
        </Button>
        <Button variant="ghost" onClick={() => setDismissed(true)}>
          {t("nudge.breakContinue")}
        </Button>
      </div>
    </Card>
  );
}
