import { useState } from "react";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useOnline } from "../../hooks/useCommon";
import { useAuth } from "../../context/AuthContext";
import { useInstall } from "../../lib/install";
import { readJSON, writeJSON } from "../../lib/storage";

const INSTALL_DISMISSED_KEY = "aq.installBannerDismissed";

export function OfflineBanner() {
  const { t } = useTranslation();
  const online = useOnline();
  if (online) return null;
  return (
    <div role="status" className="bg-warning-soft px-4 py-2 text-center text-sm font-bold text-warning">
      {t("app.offline")}
    </div>
  );
}

export function VerifyBanner() {
  const { t } = useTranslation();
  const { user } = useAuth();
  const [hidden, setHidden] = useState(false);
  if (!user || user.email_verified_at || hidden) return null;
  return (
    <div className="flex items-center gap-3 bg-info-soft px-4 py-2 text-sm font-semibold text-info">
      <Link to="/verifier-email" className="flex-1 underline-offset-2 hover:underline">
        {t("auth.verifyBanner")}
      </Link>
      <button onClick={() => setHidden(true)} aria-label={t("app.close")} className="grid h-8 w-8 place-items-center rounded-full hover:bg-info/10">
        ✕
      </button>
    </div>
  );
}

/** Invite à installer l'app, uniquement sur mobile et tant qu'elle n'est ni installée ni refusée. */
export function InstallBanner() {
  const { t } = useTranslation();
  const { installed, platform } = useInstall();
  const [hidden, setHidden] = useState(() => readJSON(INSTALL_DISMISSED_KEY, false));
  if (installed || hidden || platform === "desktop") return null;
  return (
    <div className="flex items-center gap-3 bg-success-soft px-4 py-2 text-sm font-semibold text-success">
      <Link to="/installer" className="flex-1 underline-offset-2 hover:underline">
        📲 {t("install.banner")}
      </Link>
      <button
        onClick={() => {
          writeJSON(INSTALL_DISMISSED_KEY, true);
          setHidden(true);
        }}
        aria-label={t("app.close")}
        className="grid h-8 w-8 place-items-center rounded-full hover:bg-success/10"
      >
        ✕
      </button>
    </div>
  );
}
