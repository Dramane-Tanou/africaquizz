import { useCallback, useEffect, useRef, useState } from "react";
import { Navigate, useNavigate, useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useQueryClient } from "@tanstack/react-query";
import { quizApi, revisionApi } from "../../lib/endpoints";
import { normalizeError } from "../../lib/api";
import {
  RAPID_MAX_ERRORS,
  RAPID_TOTAL_MS,
  dropGame,
  isExpired,
  loadGame,
  saveGame,
  type AnswerRecord,
  type GameState,
} from "../../lib/gameStore";
import { readJSON, writeJSON } from "../../lib/storage";
import { BADGE_CACHE, useErrorMessage } from "../../hooks/useCommon";
import { useSettings } from "../../context/SettingsContext";
import { Alert, Button, ButtonLink, FullPageSpinner, cx } from "../../components/ui/primitives";
import { FeedbackPanel, OptionButton, TimerBar, optionState } from "../../components/quiz/QuizParts";
import { OfflineBanner } from "../../components/layout/Banners";

type Phase = "question" | "submitting" | "feedback" | "finishing" | "error";

const TIMEOUT_ANSWER = "TIMEOUT";

export default function Play() {
  const { id = "" } = useParams();
  const { t } = useTranslation();
  const navigate = useNavigate();
  const qc = useQueryClient();
  const errMsg = useErrorMessage();
  const { settings } = useSettings();

  const [game, setGame] = useState<GameState | null>(() => loadGame(id));
  const [phase, setPhase] = useState<Phase>(() => {
    const g = loadGame(id);
    return g && g.answers.length > g.index ? "feedback" : "question";
  });
  const [pending, setPending] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [now, setNow] = useState(() => Date.now());
  const questionStart = useRef<number>(performance.now());
  const questionStartEpoch = useRef<number>(Date.now());
  const submitting = useRef(false);
  const finishing = useRef(false);

  const mode = game?.mode;
  const isRapid = mode === "rapid";
  const isRevision = mode === "revision";
  const question = game ? game.session.questions[game.index] : undefined;
  const record: AnswerRecord | undefined = game?.answers[game.index];
  const total = game?.limit ?? 0;
  const errors = game ? game.answers.filter((a) => !a.correct).length : 0;

  const persist = useCallback((g: GameState) => {
    saveGame(g);
    setGame(g);
  }, []);

  // ---------- Fin de partie ----------
  const finishGame = useCallback(
    async (g: GameState) => {
      if (finishing.current) return;
      finishing.current = true;
      if (g.ended) return navigate(`/resultats/${g.session.id}`, { replace: true });
      // Onboarding et révision : pas d'appel /finish (invité ou endpoint inexistant).
      if (g.mode === "onboarding" || g.mode === "revision") {
        persist({ ...g, ended: true });
        return navigate(`/resultats/${g.session.id}`, { replace: true });
      }
      setPhase("finishing");
      try {
        const res = await quizApi.finish(g.session.id);
        if (res.badges_earned?.length) {
          const known = readJSON<string[]>(BADGE_CACHE, []);
          writeJSON(BADGE_CACHE, Array.from(new Set([...known, ...res.badges_earned.map((b) => b.code)])));
        }
        persist({ ...g, finish: res, ended: true });
        void qc.invalidateQueries({ queryKey: ["me"] });
        void qc.invalidateQueries({ queryKey: ["daily"] });
        void qc.invalidateQueries({ queryKey: ["leaderboard"] });
        navigate(`/resultats/${g.session.id}`, { replace: true });
      } catch (e) {
        // Session déjà terminée (ex. double clic) : on affiche quand même les résultats locaux.
        if (normalizeError(e).kind === "validation") {
          persist({ ...g, ended: true });
          return navigate(`/resultats/${g.session.id}`, { replace: true });
        }
        setError(errMsg(e));
        setPhase("error");
        finishing.current = false;
      }
    },
    [errMsg, navigate, persist, qc],
  );

  // ---------- Envoi d'une réponse ----------
  const submit = useCallback(
    async (answer: string) => {
      if (!game || !question || submitting.current || record) return;
      submitting.current = true;
      const timedOut = answer === TIMEOUT_ANSWER;
      setPending(timedOut ? null : answer);
      setPhase("submitting");
      setError(null);
      const elapsed = performance.now() - questionStart.current;
      try {
        let rec: AnswerRecord;
        if (game.mode === "revision") {
          const r = await revisionApi.answer(game.session.id, question.id, answer);
          rec = {
            questionId: question.id,
            given: timedOut ? null : answer,
            correct: r.correct,
            correctAnswer: r.correct_answer,
            scoreGained: 0,
            xpGained: r.xp_gained,
            speedBonus: 0,
            comboStreak: 0,
            comboPct: 0,
            explanation: question.explanation ?? "",
            takeaway: "",
          };
        } else {
          const r = await quizApi.answer(game.session.id, question.id, answer, elapsed, settings.locale);
          rec = {
            questionId: question.id,
            given: timedOut ? null : answer,
            correct: r.correct,
            correctAnswer: r.correct_answer,
            scoreGained: r.score_gained,
            xpGained: r.xp_gained,
            speedBonus: r.speed_bonus,
            comboStreak: r.combo?.streak ?? 0,
            comboPct: r.combo?.bonus_pct ?? 0,
            explanation: r.explanation ?? "",
            takeaway: r.key_takeaway ?? "",
          };
        }
        if (settings.vibrate && "vibrate" in navigator) navigator.vibrate?.(rec.correct ? 30 : [40, 60, 40]);
        const answers = [...game.answers];
        answers[game.index] = rec;
        const next: GameState = {
          ...game,
          answers,
          score: game.score + rec.scoreGained,
          xp: game.xp + rec.xpGained,
        };
        persist(next);
        setPhase("feedback");
      } catch (e) {
        setError(`${t("quiz.answerError")} ${errMsg(e)}`);
        setPhase("error");
      } finally {
        submitting.current = false;
      }
    },
    [errMsg, game, persist, question, record, settings.locale, settings.vibrate, t],
  );

  // ---------- Question suivante ----------
  const goNext = useCallback(() => {
    if (!game) return;
    const nextIndex = game.index + 1;
    const errorsNow = game.answers.filter((a) => !a.correct).length;
    const rapidDone = game.mode === "rapid" && (errorsNow >= RAPID_MAX_ERRORS || Date.now() - game.startedAt >= RAPID_TOTAL_MS);
    if (nextIndex >= game.limit || rapidDone) {
      void finishGame(game);
      return;
    }
    persist({ ...game, index: nextIndex });
    setPending(null);
    setPhase("question");
  }, [finishGame, game, persist]);

  // Réinitialise le chrono à chaque nouvelle question.
  useEffect(() => {
    if (phase === "question") {
      questionStart.current = performance.now();
      questionStartEpoch.current = Date.now();
    }
  }, [phase, game?.index]);

  // Horloge d'affichage (10 fois par seconde).
  useEffect(() => {
    if (phase !== "question" && !(isRapid && phase !== "finishing")) return;
    const iv = window.setInterval(() => setNow(Date.now()), 100);
    return () => window.clearInterval(iv);
  }, [phase, isRapid]);

  // Temps écoulé : question (modes chronométrés) ou partie (mode rapide).
  const limitMs = (question?.time_limit_s ?? 20) * 1000;
  const questionRemaining = Math.max(0, limitMs - (now - questionStartEpoch.current));
  const rapidRemaining = game ? Math.max(0, RAPID_TOTAL_MS - (now - game.startedAt)) : 0;

  useEffect(() => {
    if (!game || game.ended) return;
    if (isRapid) {
      if (rapidRemaining <= 0 && phase !== "finishing" && phase !== "submitting" && phase !== "error") void finishGame(game);
      return;
    }
    if (!isRevision && phase === "question" && questionRemaining <= 0) void submit(TIMEOUT_ANSWER);
  }, [finishGame, game, isRapid, isRevision, phase, questionRemaining, rapidRemaining, submit]);

  // Mode rapide : enchaînement automatique après un court feedback.
  const goNextRef = useRef(goNext);
  goNextRef.current = goNext;
  useEffect(() => {
    if (!isRapid || phase !== "feedback") return;
    const to = window.setTimeout(() => goNextRef.current(), 900);
    return () => window.clearTimeout(to);
  }, [isRapid, phase, game?.index]);

  // Raccourcis clavier : 1-4 ou A-D pour répondre, Entrée pour continuer.
  useEffect(() => {
    function onKey(e: KeyboardEvent) {
      if (!question || e.metaKey || e.ctrlKey || e.altKey) return;
      if (phase === "question") {
        const k = e.key.toUpperCase();
        const byNum = /^[1-4]$/.test(k) ? question.options[Number(k) - 1]?.key : undefined;
        const byKey = question.options.find((o) => o.key === k)?.key;
        const choice = byNum ?? byKey;
        if (choice) void submit(choice);
      } else if (phase === "feedback" && e.key === "Enter" && !isRapid) {
        e.preventDefault();
        goNext();
      }
    }
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [goNext, isRapid, phase, question, submit]);

  // ---------- Rendus ----------
  if (!game) {
    return (
      <Centered>
        <Alert tone="warning">{t("errors.not_found")}</Alert>
        <ButtonLink to="/" block>
          {t("app.goHome")}
        </ButtonLink>
      </Centered>
    );
  }

  if (game.ended) return <Navigate to={`/resultats/${game.session.id}`} replace />;

  if (isExpired(game)) {
    return (
      <Centered>
        <Alert tone="warning">{t("errors.sessionExpired")}</Alert>
        <Button
          block
          onClick={() => {
            dropGame(game.session.id);
            navigate("/", { replace: true });
          }}
        >
          {t("app.goHome")}
        </Button>
      </Centered>
    );
  }

  if (phase === "finishing") return <FullPageSpinner label={t("quiz.finishing")} />;
  if (!question) return <FullPageSpinner />;

  const quit = () => {
    if (window.confirm(t("quiz.quitConfirm"))) {
      dropGame(game.session.id);
      navigate("/", { replace: true });
    }
  };

  const answered = !!record;
  const isLast = game.index + 1 >= game.limit;

  return (
    <div className="mx-auto flex min-h-[100dvh] max-w-xl flex-col">
      <OfflineBanner />
      <header className="sticky top-0 z-10 space-y-3 bg-bg/95 px-4 pb-3 pt-4 backdrop-blur">
        <div className="flex items-center justify-between gap-3">
          <button onClick={quit} className="min-h-[44px] rounded-xl px-2 font-bold text-ink-2 hover:bg-ink/5" aria-label={t("quiz.quit")}>
            ✕ <span className="sr-only">{t("quiz.quit")}</span>
          </button>
          <p className="font-title text-lg font-semibold" aria-live="polite">
            {isRapid ? `${Math.ceil(rapidRemaining / 1000)} s` : t("quiz.question", { n: game.index + 1, total })}
          </p>
          <p className="rounded-full border-2 border-black bg-brand-gold px-3 py-1 font-title text-sm font-bold text-black dark:border-line">
            {isRevision ? `${game.xp} XP` : `${game.score} pts`}
          </p>
        </div>
        {isRapid ? (
          <div className="flex items-center gap-3">
            <TimerBar remainingMs={rapidRemaining} totalMs={RAPID_TOTAL_MS} hidden={settings.hideTimer} />
            <span className="shrink-0 text-sm font-bold text-ink-2" aria-label={t("quiz.errorsLeft", { count: RAPID_MAX_ERRORS - errors })}>
              {Array.from({ length: RAPID_MAX_ERRORS }, (_, i) => (
                <span key={i} aria-hidden className={i < RAPID_MAX_ERRORS - errors ? "" : "opacity-25 grayscale"}>
                  ❤️
                </span>
              ))}
            </span>
          </div>
        ) : !isRevision ? (
          <TimerBar remainingMs={answered ? 0 : questionRemaining} totalMs={limitMs} hidden={settings.hideTimer || answered} />
        ) : null}
        <div className="flex gap-1" aria-hidden>
          {Array.from({ length: total }, (_, i) => {
            const a = game.answers[i];
            return (
              <span
                key={i}
                className={cx(
                  "h-1.5 flex-1 rounded-full",
                  a ? (a.correct ? "bg-success" : "bg-danger") : i === game.index ? "bg-ink/40" : "bg-ink/10",
                )}
              />
            );
          })}
        </div>
      </header>

      <main className="flex flex-1 flex-col gap-4 px-4 pb-8 pt-2">
        <div key={question.id} className="animate-pop space-y-4 motion-reduce:animate-none">
          {question.difficulty && (
            <span className="inline-block rounded-full bg-ink/5 px-3 py-1 text-xs font-bold uppercase tracking-wide text-ink-2">
              {t(`difficulty.${question.difficulty}`)}
            </span>
          )}
          {question.image?.url && (
            <figure>
              <img src={question.image.url} alt={question.image.alt} loading="lazy" className="max-h-56 w-full rounded-bubble border-2 border-black object-cover" />
              {question.image.credit && <figcaption className="mt-1 text-xs text-ink-2">© {question.image.credit}</figcaption>}
            </figure>
          )}
          <h1 className="font-title text-2xl font-semibold leading-snug text-ink" tabIndex={-1}>
            {question.text}
          </h1>
          <div className="grid gap-3" role="group" aria-label={question.text}>
            {question.options.map((o, i) => (
              <OptionButton
                key={o.key}
                index={i}
                option={o}
                state={optionState(o.key, record, pending)}
                disabled={phase !== "question"}
                onClick={() => void submit(o.key)}
              />
            ))}
          </div>
        </div>

        {phase === "error" && error && (
          <Alert
            tone="danger"
            action={
              <Button
                variant="secondary"
                className="min-h-[40px] px-3 py-1 text-sm"
                onClick={() => {
                  if (record) void finishGame(game);
                  else void submit(pending ?? TIMEOUT_ANSWER);
                }}
              >
                {t("app.retry")}
              </Button>
            }
          >
            {error}
          </Alert>
        )}

        {phase === "feedback" && record && (
          <FeedbackPanel record={record} options={question.options} compact={isRapid}>
            {!isRapid && (
              <Button block onClick={goNext} autoFocus>
                {isLast ? t("quiz.seeResults") : t("quiz.next")} →
              </Button>
            )}
          </FeedbackPanel>
        )}
      </main>
    </div>
  );
}

function Centered({ children }: { children: React.ReactNode }) {
  return <div className="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center gap-4 px-4">{children}</div>;
}
