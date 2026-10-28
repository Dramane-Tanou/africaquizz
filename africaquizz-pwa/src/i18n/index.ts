import i18n from "i18next";
import { initReactI18next } from "react-i18next";
import fr from "./fr";
import en from "./en";
import { readJSON } from "../lib/storage";

function initialLanguage(): "fr" | "en" {
  const saved = readJSON<{ locale?: "fr" | "en" }>("aq.settings", {}).locale;
  if (saved) return saved;
  return navigator.language?.toLowerCase().startsWith("en") ? "en" : "fr";
}

i18n.use(initReactI18next).init({
  resources: { fr: { translation: fr }, en: { translation: en } },
  lng: initialLanguage(),
  fallbackLng: "fr",
  interpolation: { escapeValue: false },
  returnNull: false,
});

i18n.on("languageChanged", (lng) => {
  document.documentElement.lang = lng;
});
document.documentElement.lang = i18n.language;

export default i18n;
