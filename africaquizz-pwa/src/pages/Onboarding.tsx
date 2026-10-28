import { useTranslation } from "react-i18next";
import { useAuth } from "../context/AuthContext";
import { useStartGame } from "../hooks/useStartGame";
import { Alert, Button, ButtonLink } from "../components/ui/primitives";
import { LogoMark } from "../components/ui/Brand";
import { OfflineBanner } from "../components/layout/Banners";
import { useInstall } from "../lib/install";

export default function Onboarding() {
  const { t } = useTranslation();
  const { markOnboarded } = useAuth();
  const { start, loading, error } = useStartGame();
  const { installed } = useInstall();

  return (
    <div className="pattern-bg min-h-[100dvh]">
      <OfflineBanner />
      <div className="mx-auto flex min-h-[100dvh] max-w-md flex-col justify-between px-6 py-10">
        <div className="flex flex-1 flex-col items-center justify-center text-center">
          <LogoMark size={112} className="animate-pop motion-reduce:animate-none" />
          <h1 className="mt-6 font-title text-5xl font-bold leading-tight">
            Africa <span className="text-brand-red">Q</span>
            <span className="text-brand-gold-dark">ui</span>
            <span className="text-brand-green-dark">zz</span>
          </h1>
          <p className="mt-2 font-title text-xl text-ink-2">{t("app.tagline")}</p>
          <div className="mt-10 rounded-bubble border-2 border-black bg-surface p-5 text-left shadow-flat dark:border-line">
            <h2 className="font-title text-2xl font-semibold">{t("onboarding.title")}</h2>
            <p className="mt-1 text-ink-2">{t("onboarding.intro")}</p>
          </div>
        </div>
        <div className="mt-8 space-y-3">
          {error && <Alert tone="danger">{error}</Alert>}
          <Button
            block
            loading={loading === "onboarding"}
            onClick={() => {
              markOnboarded();
              void start("onboarding");
            }}
          >
            {t("onboarding.start")} →
          </Button>
          <ButtonLink to="/connexion" variant="secondary" block>
            {t("onboarding.login")}
          </ButtonLink>
          {!installed && (
            <ButtonLink to="/installer" variant="ghost" block>
              📲 {t("install.cta")}
            </ButtonLink>
          )}
        </div>
      </div>
    </div>
  );
}
