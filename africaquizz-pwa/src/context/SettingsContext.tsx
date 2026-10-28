import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from "react";
import i18n from "../i18n";
import { readJSON, writeJSON } from "../lib/storage";
import type { Locale } from "../types/extra";

export type Theme = "system" | "light" | "dark";

export interface Settings {
  locale: Locale;
  theme: Theme;
  hideTimer: boolean;
  vibrate: boolean;
  avatar: number; // index d'avatar prédéfini, conservé sur l'appareil
}

const KEY = "aq.settings";

const defaults: Settings = {
  locale: (i18n.language as Locale) === "en" ? "en" : "fr",
  theme: "system",
  hideTimer: false,
  vibrate: true,
  avatar: 0,
};

interface Ctx {
  settings: Settings;
  update: (patch: Partial<Settings>) => void;
}

const SettingsContext = createContext<Ctx | null>(null);

export function SettingsProvider({ children }: { children: ReactNode }) {
  const [settings, setSettings] = useState<Settings>(() => ({ ...defaults, ...readJSON<Partial<Settings>>(KEY, {}) }));

  const update = useCallback((patch: Partial<Settings>) => {
    setSettings((s) => {
      const next = { ...s, ...patch };
      writeJSON(KEY, next);
      return next;
    });
  }, []);

  // Langue de l'interface = langue du contenu demandé à l'API.
  useEffect(() => {
    if (i18n.language !== settings.locale) void i18n.changeLanguage(settings.locale);
  }, [settings.locale]);

  // Thème clair / sombre / système.
  useEffect(() => {
    const mq = window.matchMedia("(prefers-color-scheme: dark)");
    const apply = () => {
      const dark = settings.theme === "dark" || (settings.theme === "system" && mq.matches);
      document.documentElement.classList.toggle("dark", dark);
      document.querySelector('meta[name="theme-color"]')?.setAttribute("content", dark ? "#0B0F1A" : "#FFBB02");
    };
    apply();
    mq.addEventListener("change", apply);
    return () => mq.removeEventListener("change", apply);
  }, [settings.theme]);

  const value = useMemo(() => ({ settings, update }), [settings, update]);
  return <SettingsContext.Provider value={value}>{children}</SettingsContext.Provider>;
}

export function useSettings() {
  const ctx = useContext(SettingsContext);
  if (!ctx) throw new Error("useSettings doit être utilisé dans SettingsProvider");
  return ctx;
}
