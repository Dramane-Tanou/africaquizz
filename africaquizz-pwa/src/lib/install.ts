import { useSyncExternalStore } from "react";

// Installation de la PWA. Le navigateur n'émet `beforeinstallprompt` qu'une fois, souvent avant
// que l'écran concerné soit monté : on l'intercepte dès le chargement du module (importé par main.tsx).

interface BeforeInstallPromptEvent extends Event {
  prompt: () => Promise<void>;
  userChoice: Promise<{ outcome: "accepted" | "dismissed" }>;
}

export type Platform = "ios" | "android" | "desktop";

let deferred: BeforeInstallPromptEvent | null = null;
let installed = false;
const listeners = new Set<() => void>();
const emit = () => listeners.forEach((l) => l());

function standalone(): boolean {
  return (
    window.matchMedia?.("(display-mode: standalone)").matches ||
    (navigator as Navigator & { standalone?: boolean }).standalone === true
  );
}

if (typeof window !== "undefined") {
  installed = standalone();
  window.addEventListener("beforeinstallprompt", (e) => {
    e.preventDefault();
    deferred = e as BeforeInstallPromptEvent;
    emit();
  });
  window.addEventListener("appinstalled", () => {
    deferred = null;
    installed = true;
    emit();
  });
}

export function detectPlatform(): Platform {
  const ua = navigator.userAgent;
  // iPadOS 13+ se présente comme un Mac : on le reconnaît à l'écran tactile.
  if (/iphone|ipad|ipod/i.test(ua) || (/macintosh/i.test(ua) && navigator.maxTouchPoints > 1)) return "ios";
  if (/android/i.test(ua)) return "android";
  return "desktop";
}

/** Safari est le seul navigateur iOS qui propose « Sur l'écran d'accueil » de façon fiable. */
export function isIosSafari(): boolean {
  return detectPlatform() === "ios" && !/crios|fxios|edgios|opios/i.test(navigator.userAgent);
}

function subscribe(l: () => void) {
  listeners.add(l);
  return () => listeners.delete(l);
}

const snapshot = () => (installed ? "installed" : deferred ? "prompt" : "manual");

export function useInstall() {
  const state = useSyncExternalStore(subscribe, snapshot, () => "manual" as const);
  return {
    installed: state === "installed",
    canPrompt: state === "prompt",
    platform: detectPlatform(),
    /** Ouvre la fenêtre native ; renvoie true si l'utilisateur a accepté. */
    async prompt(): Promise<boolean> {
      const evt = deferred;
      if (!evt) return false;
      deferred = null;
      await evt.prompt();
      const { outcome } = await evt.userChoice;
      if (outcome === "accepted") installed = true;
      emit();
      return outcome === "accepted";
    },
  };
}
