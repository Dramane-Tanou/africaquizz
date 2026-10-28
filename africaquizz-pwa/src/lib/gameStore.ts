// État d'une partie en cours, conservé dans sessionStorage pour survivre à un rafraîchissement.
import { readJSON, removeKey, safeSession, writeJSON } from "./storage";
import type { FinishResponse } from "../types/api";
import type { PlayMode, PlaySession } from "../types/extra";

export interface AnswerRecord {
  questionId: string;
  given: string | null; // null = temps écoulé
  correct: boolean;
  correctAnswer: string | null;
  scoreGained: number;
  xpGained: number;
  speedBonus: number;
  comboStreak: number;
  comboPct: number;
  explanation: string;
  takeaway: string;
}

export interface GameState {
  session: PlaySession;
  mode: PlayMode;
  /** Nombre de questions jouées dans ce mode (5 pour l'onboarding). */
  limit: number;
  index: number;
  answers: AnswerRecord[];
  score: number;
  xp: number;
  startedAt: number; // epoch ms, sert au chrono global du mode rapide
  finish?: FinishResponse["data"];
  ended?: boolean;
}

export const RAPID_TOTAL_MS = 60_000;
export const RAPID_MAX_ERRORS = 3;
export const ONBOARDING_QUESTIONS = 5;

const key = (id: string) => `aq.game.${id}`;

export function createGame(session: PlaySession, mode: PlayMode): GameState {
  const total = session.questions.length;
  const game: GameState = {
    session,
    mode,
    limit: mode === "onboarding" ? Math.min(ONBOARDING_QUESTIONS, total) : total,
    index: 0,
    answers: [],
    score: 0,
    xp: 0,
    startedAt: Date.now(),
  };
  saveGame(game);
  return game;
}

export function loadGame(id: string): GameState | null {
  return readJSON<GameState | null>(key(id), null, safeSession());
}

export function saveGame(game: GameState) {
  writeJSON(key(game.session.id), game, safeSession());
}

export function dropGame(id: string) {
  removeKey(key(id), safeSession());
}

export function isExpired(game: GameState): boolean {
  const exp = game.session.expires_at ? Date.parse(game.session.expires_at) : NaN;
  return Number.isFinite(exp) && Date.now() > exp && !game.ended;
}
