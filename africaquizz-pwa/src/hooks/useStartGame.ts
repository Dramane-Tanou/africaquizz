import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { dailyApi, quizApi, revisionApi } from "../lib/endpoints";
import { createGame } from "../lib/gameStore";
import { useSettings } from "../context/SettingsContext";
import { useErrorMessage } from "./useCommon";
import type { PlayMode } from "../types/extra";

/** Démarre une partie côté serveur, mémorise la session et ouvre l'écran de jeu. */
export function useStartGame() {
  const navigate = useNavigate();
  const { settings } = useSettings();
  const errMsg = useErrorMessage();
  const { t } = useTranslation();
  const [loading, setLoading] = useState<PlayMode | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function start(mode: PlayMode) {
    setLoading(mode);
    setError(null);
    try {
      const locale = settings.locale;
      const session =
        mode === "daily"
          ? await dailyApi.start(locale)
          : mode === "revision"
            ? await revisionApi.start(locale)
            : await quizApi.start(mode === "rapid" ? "rapid" : "classic", locale);
      if (!session.questions?.length) throw new Error("empty");
      createGame(session, mode);
      navigate(`/jouer/${session.id}`);
    } catch (e) {
      setError(e instanceof Error && e.message === "empty" ? t("quiz.noQuestions") : errMsg(e, t("quiz.noQuestions")));
    } finally {
      setLoading(null);
    }
  }

  return { start, loading, error, clearError: () => setError(null) };
}
