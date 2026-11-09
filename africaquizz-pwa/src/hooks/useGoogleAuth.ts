import { useCallback, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../context/AuthContext";
import { useSettings } from "../context/SettingsContext";
import { normalizeError } from "../lib/api";
import { useErrorMessage } from "./useCommon";

/** État transmis à l'écran de fin d'inscription Google. */
export interface GoogleCompleteState {
  credential: string;
  name: string | null;
  email: string;
}

/** Traite l'ID token renvoyé par le bouton Google (écrans Connexion et Inscription). */
export function useGoogleAuth(from = "/") {
  const { t } = useTranslation();
  const { googleSignIn } = useAuth();
  const { settings } = useSettings();
  const navigate = useNavigate();
  const errMsg = useErrorMessage();
  const [error, setError] = useState<string | null>(null);

  const onCredential = useCallback(
    async (credential: string) => {
      setError(null);
      try {
        const res = await googleSignIn({
          credential,
          timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
          locale: settings.locale,
        });
        if (res.status === "needs_profile") {
          const state: GoogleCompleteState = { credential, name: res.name, email: res.email };
          navigate("/inscription/google", { state });
        } else {
          navigate(from, { replace: true });
        }
      } catch (err) {
        const n = normalizeError(err);
        if (n.status === 401) setError(t("auth.googleFailed"));
        else if (n.status === 403) setError(t("auth.banned"));
        else setError(errMsg(err));
      }
    },
    [googleSignIn, settings.locale, navigate, from, t, errMsg],
  );

  return { onCredential, error };
}
