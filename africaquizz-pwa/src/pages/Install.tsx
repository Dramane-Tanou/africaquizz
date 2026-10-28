import { useState } from "react";
import { useTranslation } from "react-i18next";
import { isIosSafari, useInstall } from "../lib/install";
import { Alert, Button, ButtonLink, Card } from "../components/ui/primitives";
import { LogoMark } from "../components/ui/Brand";
import { OfflineBanner } from "../components/layout/Banners";

// Page d'installation sur téléphone : bouton natif quand le navigateur le permet, sinon pas-à-pas
// selon la plateforme (iOS n'émet jamais `beforeinstallprompt`).
export default function Install() {
  const { t } = useTranslation();
  const { installed, canPrompt, platform, prompt } = useInstall();
  const [declined, setDeclined] = useState(false);

  const perks = [
    { icon: "⚡", text: t("install.perkFast") },
    { icon: "📱", text: t("install.perkFullscreen") },
    { icon: "🪶", text: t("install.perkLight") },
  ];

  return (
    <div className="pattern-bg min-h-[100dvh]">
      <OfflineBanner />
      <div className="mx-auto flex min-h-[100dvh] max-w-md flex-col gap-6 px-6 py-10">
        <header className="flex flex-col items-center text-center">
          <LogoMark size={96} className="animate-pop motion-reduce:animate-none" />
          <h1 className="mt-5 font-title text-4xl font-bold leading-tight">{t("install.title")}</h1>
          <p className="mt-2 text-ink-2">{t("install.intro")}</p>
        </header>

        <ul className="space-y-2">
          {perks.map((p) => (
            <li key={p.icon} className="flex items-center gap-3 rounded-2xl border-2 border-black bg-surface px-4 py-3 font-semibold dark:border-line">
              <span className="text-2xl" aria-hidden>
                {p.icon}
              </span>
              {p.text}
            </li>
          ))}
        </ul>

        <Card className="space-y-4">
          {installed ? (
            <>
              <p className="font-title text-xl font-semibold">✅ {t("install.already")}</p>
              <ButtonLink to="/" block>
                {t("install.play")} →
              </ButtonLink>
            </>
          ) : canPrompt ? (
            <>
              <Button variant="green" block onClick={() => void prompt().then((ok) => setDeclined(!ok))}>
                📲 {t("install.cta")}
              </Button>
              {declined && <p className="text-center text-sm text-ink-2">{t("install.declined")}</p>}
            </>
          ) : platform === "ios" ? (
            isIosSafari() ? (
              <Steps title={t("install.iosTitle")} steps={[t("install.iosStep1"), t("install.iosStep2"), t("install.iosStep3")]} />
            ) : (
              <Alert tone="warning">{t("install.iosNotSafari")}</Alert>
            )
          ) : platform === "android" ? (
            <Steps title={t("install.androidTitle")} steps={[t("install.androidStep1"), t("install.androidStep2"), t("install.androidStep3")]} />
          ) : (
            <DesktopHint />
          )}
        </Card>

        {!installed && (
          <ButtonLink to="/" variant="ghost" block>
            {t("install.later")}
          </ButtonLink>
        )}
      </div>
    </div>
  );
}

function Steps({ title, steps }: { title: string; steps: string[] }) {
  return (
    <div>
      <h2 className="mb-3 font-title text-xl font-semibold">{title}</h2>
      <ol className="space-y-3">
        {steps.map((s, i) => (
          <li key={i} className="flex items-start gap-3">
            <span className="grid h-8 w-8 shrink-0 place-items-center rounded-full border-2 border-black bg-brand-gold font-title font-bold text-black">
              {i + 1}
            </span>
            <span className="pt-1">{s}</span>
          </li>
        ))}
      </ol>
    </div>
  );
}

function DesktopHint() {
  const { t } = useTranslation();
  const [copied, setCopied] = useState(false);
  const url = `${window.location.origin}/installer`;
  return (
    <div className="space-y-3">
      <h2 className="font-title text-xl font-semibold">{t("install.desktopTitle")}</h2>
      <p className="text-ink-2">{t("install.desktopText")}</p>
      <p className="break-all rounded-2xl bg-bg px-4 py-3 text-center font-bold">{url}</p>
      {navigator.clipboard && (
        <Button variant="secondary" block onClick={() => void navigator.clipboard.writeText(url).then(() => setCopied(true))}>
          {copied ? `✓ ${t("install.copied")}` : t("install.copy")}
        </Button>
      )}
    </div>
  );
}
