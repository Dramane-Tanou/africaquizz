import { useState } from "react";
import { useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { loadGame } from "../../lib/gameStore";
import { BADGES } from "../../lib/game";
import { useAuth } from "../../context/AuthContext";
import { useStartGame } from "../../hooks/useStartGame";
import { Alert, Button, ButtonLink, Card } from "../../components/ui/primitives";
import { LogoMark } from "../../components/ui/Brand";

export default function Result() {
  const { id = "" } = useParams();
  const { t } = useTranslation();
  const { isAuthenticated } = useAuth();
  const { start, loading, error } = useStartGame();
  const [copied, setCopied] = useState(false);
  const game = loadGame(id);

  if (!game) {
    return (
      <div className="mx-auto max-w-md space-y-4 px-4 py-16">
        <Alert tone="warning">{t("errors.not_found")}</Alert>
        <ButtonLink to="/" block>
          {t("app.goHome")}
        </ButtonLink>
      </div>
    );
  }

  const answered = game.answers.filter(Boolean);
  const correct = game.finish?.correct ?? answered.filter((a) => a.correct).length;
  const total = game.mode === "rapid" ? answered.length : game.finish?.total ?? game.limit;
  const score = game.finish?.score ?? game.score;
  const xp = game.finish?.xp_total_gained ?? game.xp;
  const ratio = total ? correct / total : 0;
  const headline = ratio === 1 ? t("result.perfect") : ratio >= 0.7 ? t("result.great") : ratio >= 0.4 ? t("result.good") : t("result.keepGoing");
  const bonuses = (game.finish?.bonuses ?? []).filter((b) => b.xp > 0);
  const badges = game.finish?.badges_earned ?? [];
  const streak = game.finish?.streak;

  // ----- Onboarding : invitation à créer un compte -----
  if (game.mode === "onboarding") {
    return (
      <div className="mx-auto flex min-h-[100dvh] max-w-md flex-col justify-center gap-6 px-4 py-10 text-center">
        <LogoMark size={72} className="mx-auto" />
        <div>
          <h1 className="font-title text-4xl font-bold">{t("onboarding.resultTitle")}</h1>
          <p className="mt-2 text-lg">{t("onboarding.resultText", { correct, total })}</p>
          <p className="mt-1 text-ink-2">{t("onboarding.learned")}</p>
        </div>
        <ScoreDots answers={answered.map((a) => a.correct)} />
        <Card className="text-left">
          <p className="font-semibold">{t("auth.saveProgress")}</p>
        </Card>
        <div className="space-y-3">
          <ButtonLink to="/inscription" block>
            {t("onboarding.saveCta")}
          </ButtonLink>
          <ButtonLink to="/" variant="ghost" block>
            {t("onboarding.skip")}
          </ButtonLink>
        </div>
      </div>
    );
  }

  const share = async () => {
    const text = t("result.shareText", { correct, total, score });
    const url = window.location.origin;
    try {
      if (navigator.share) await navigator.share({ title: "Africa Quizz", text, url });
      else {
        await navigator.clipboard.writeText(`${text} ${url}`);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
      }
    } catch {
      /* partage annulé */
    }
  };

  const replayMode = game.mode === "rapid" ? "rapid" : "classic";

  return (
    <div className="mx-auto max-w-md space-y-5 px-4 py-8">
      <header className="text-center">
        <p className="text-sm font-bold uppercase tracking-wide text-ink-2">{game.mode === "revision" ? t("result.revisionDone") : t("result.title")}</p>
        <h1 className="mt-1 font-title text-4xl font-bold">{headline}</h1>
      </header>

      <Card className="text-center">
        <div className={`grid divide-x-2 divide-line ${game.mode === "revision" ? "grid-cols-2" : "grid-cols-3"}`}>
          {game.mode !== "revision" && <Stat label={t("result.score")} value={score} />}
          <Stat label={t("result.correct")} value={`${correct}/${total}`} />
          <Stat label={t("result.xp")} value={`+${xp}`} accent />
        </div>
        <ScoreDots answers={answered.map((a) => a.correct)} className="mt-5" />
      </Card>

      {bonuses.length > 0 && (
        <Card>
          <h2 className="font-title text-lg font-semibold">{t("result.bonuses")}</h2>
          <ul className="mt-2 space-y-1">
            {bonuses.map((b) => (
              <li key={b.type} className="flex justify-between font-semibold">
                <span>{t(`result.bonus_${b.type}`, { defaultValue: b.type })}</span>
                <span className="text-success">+{b.xp} XP</span>
              </li>
            ))}
          </ul>
        </Card>
      )}

      {streak && (
        <Alert tone="warning">
          🔥 {t("result.streak", { count: streak.current, longest: streak.longest })}
        </Alert>
      )}

      {badges.length > 0 && (
        <Card>
          <h2 className="font-title text-lg font-semibold">{t("result.newBadges")}</h2>
          <ul className="mt-3 flex flex-wrap gap-3">
            {badges.map((b) => (
              <li key={`${b.code}-${b.tier}`} className="flex items-center gap-2 rounded-2xl bg-brand-gold/20 px-3 py-2 font-bold">
                <span aria-hidden className="text-2xl">
                  {BADGES.find((x) => x.code === b.code)?.icon ?? "🏅"}
                </span>
                {t(`badges.${b.code}`, { defaultValue: b.code })} · {t(`tiers.${b.tier}`, { defaultValue: b.tier })}
              </li>
            ))}
          </ul>
        </Card>
      )}

      {!isAuthenticated && <Alert tone="info">{t("result.guestNote")}</Alert>}
      {error && <Alert tone="danger">{error}</Alert>}

      <div className="space-y-3">
        {game.mode !== "daily" && game.mode !== "revision" && (
          <Button block onClick={() => void start(replayMode)} loading={loading === replayMode}>
            {t("result.replay")}
          </Button>
        )}
        {game.mode === "daily" && (
          <ButtonLink to="/classements?scope=daily" block>
            {t("home.seeRanking")}
          </ButtonLink>
        )}
        {game.mode !== "revision" && (
          <Button block variant="secondary" onClick={() => void share()}>
            {copied ? t("result.copied") : t("result.share")}
          </Button>
        )}
        <ButtonLink to="/" block variant="ghost">
          {t("result.home")}
        </ButtonLink>
      </div>
    </div>
  );
}

function Stat({ label, value, accent }: { label: string; value: string | number; accent?: boolean }) {
  return (
    <div className="px-2">
      <p className={accent ? "font-title text-3xl font-bold text-success" : "font-title text-3xl font-bold"}>{value}</p>
      <p className="text-xs font-bold text-ink-2">{label}</p>
    </div>
  );
}

function ScoreDots({ answers, className }: { answers: boolean[]; className?: string }) {
  return (
    <div className={`flex flex-wrap justify-center gap-1.5 ${className ?? ""}`} aria-hidden>
      {answers.map((ok, i) => (
        <span
          key={i}
          className={`grid h-7 w-7 place-items-center rounded-full border-2 border-black text-xs font-bold text-white dark:border-line ${ok ? "bg-success" : "bg-danger"}`}
        >
          {ok ? "✓" : "✕"}
        </span>
      ))}
    </div>
  );
}
