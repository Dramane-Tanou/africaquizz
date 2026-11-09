import { useEffect, useRef, useState } from "react";
import { useTranslation } from "react-i18next";

// Google Identity Services : bouton officiel « Continuer avec Google ».
// Il renvoie un ID token (JWT) que l'API Laravel vérifie auprès de Google.
const GSI_SRC = "https://accounts.google.com/gsi/client";
export const GOOGLE_CLIENT_ID = import.meta.env.VITE_GOOGLE_CLIENT_ID ?? "";

interface GsiButtonOptions {
  type?: "standard" | "icon";
  theme?: "outline" | "filled_blue" | "filled_black";
  size?: "large" | "medium" | "small";
  text?: "signin_with" | "signup_with" | "continue_with";
  shape?: "rectangular" | "pill";
  width?: number;
  locale?: string;
}

declare global {
  interface Window {
    google?: {
      accounts: {
        id: {
          initialize: (config: { client_id: string; callback: (res: { credential: string }) => void; ux_mode?: "popup" }) => void;
          renderButton: (parent: HTMLElement, options: GsiButtonOptions) => void;
        };
      };
    };
  }
}

let gsiPromise: Promise<void> | null = null;
function loadGsi(): Promise<void> {
  if (window.google?.accounts?.id) return Promise.resolve();
  gsiPromise ??= new Promise<void>((resolve, reject) => {
    const s = document.createElement("script");
    s.src = GSI_SRC;
    s.async = true;
    s.defer = true;
    s.onload = () => resolve();
    s.onerror = () => {
      gsiPromise = null;
      reject(new Error("gsi"));
    };
    document.head.appendChild(s);
  });
  return gsiPromise;
}

export function GoogleButton({ onCredential, text = "continue_with" }: { onCredential: (credential: string) => void; text?: GsiButtonOptions["text"] }) {
  const { t, i18n } = useTranslation();
  const ref = useRef<HTMLDivElement>(null);
  const cb = useRef(onCredential);
  cb.current = onCredential;
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    if (!GOOGLE_CLIENT_ID) return;
    let cancelled = false;
    loadGsi()
      .then(() => {
        if (cancelled || !ref.current || !window.google) return;
        window.google.accounts.id.initialize({
          client_id: GOOGLE_CLIENT_ID,
          callback: (res) => cb.current(res.credential),
          ux_mode: "popup",
        });
        window.google.accounts.id.renderButton(ref.current, {
          type: "standard",
          theme: "outline",
          size: "large",
          shape: "pill",
          text,
          width: Math.min(ref.current.offsetWidth || 320, 400),
          locale: i18n.language,
        });
      })
      .catch(() => !cancelled && setFailed(true));
    return () => {
      cancelled = true;
    };
  }, [text, i18n.language]);

  // Sans ID client configuré, le bouton n'est pas proposé
  if (!GOOGLE_CLIENT_ID) return null;

  return (
    <div className="space-y-4">
      {failed ? (
        <p className="text-center text-sm text-ink-2">{t("auth.googleUnavailable")}</p>
      ) : (
        <div ref={ref} className="flex min-h-[44px] w-full justify-center" />
      )}
      <div className="flex items-center gap-3 text-xs font-semibold uppercase text-ink-2" aria-hidden>
        <span className="h-px flex-1 bg-line" />
        {t("auth.or")}
        <span className="h-px flex-1 bg-line" />
      </div>
    </div>
  );
}
