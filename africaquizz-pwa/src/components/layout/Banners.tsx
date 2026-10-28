import { useState } from "react";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useOnline } from "../../hooks/useCommon";
import { useAuth } from "../../context/AuthContext";

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
