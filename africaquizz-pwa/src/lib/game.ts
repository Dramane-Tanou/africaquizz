// Règles d'affichage de la progression (miroir des formules du serveur, chapitre 8.3).
import type { Profile } from "../types/api";

export const RANKS: { code: Profile["rank"]; minXp: number }[] = [
  { code: "curieux", minXp: 0 },
  { code: "explorateur", minXp: 3000 },
  { code: "initie", minXp: 15000 },
  { code: "erudit", minXp: 50000 },
  { code: "maitre_du_savoir", minXp: 150000 },
];

/** Niveau = ⌊√(XP / 120)⌋ + 1 */
export function levelFromXp(xp: number): number {
  return Math.floor(Math.sqrt(Math.max(0, xp) / 120)) + 1;
}

/** XP requis pour atteindre un niveau donné. */
export function xpForLevel(level: number): number {
  return 120 * Math.pow(level - 1, 2);
}

export function levelProgress(xp: number) {
  const level = levelFromXp(xp);
  const start = xpForLevel(level);
  const end = xpForLevel(level + 1);
  return { level, current: xp - start, needed: end - start, pct: Math.min(100, ((xp - start) / (end - start)) * 100) };
}

export function nextRank(xp: number) {
  return RANKS.find((r) => r.minXp > xp) ?? null;
}

export function rankFromXp(xp: number): Profile["rank"] {
  let code: Profile["rank"] = "curieux";
  for (const r of RANKS) if (xp >= r.minXp) code = r.code;
  return code;
}

/** Drapeau emoji depuis un code ISO-2 (ex. « BF » → 🇧🇫). */
export function flag(iso2?: string | null): string {
  if (!iso2 || iso2.length !== 2) return "";
  const base = 0x1f1e6;
  return String.fromCodePoint(...iso2.toUpperCase().split("").map((c) => base + c.charCodeAt(0) - 65));
}

export const BADGES = [
  { code: "first_quiz", icon: "🎯" },
  { code: "regular", icon: "🔥" },
  { code: "explorer", icon: "🧭" },
  { code: "specialist", icon: "🎓" },
  { code: "flawless", icon: "💎" },
  { code: "daily_champion", icon: "🏆" },
  { code: "record_holder", icon: "⚡" },
  { code: "ambassador", icon: "🤝" },
] as const;

export function ageFromDob(dob: string): number {
  const d = new Date(dob);
  if (Number.isNaN(d.getTime())) return NaN;
  const now = new Date();
  let age = now.getFullYear() - d.getFullYear();
  const m = now.getMonth() - d.getMonth();
  if (m < 0 || (m === 0 && now.getDate() < d.getDate())) age--;
  return age;
}
