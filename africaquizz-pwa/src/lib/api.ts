import axios, { AxiosError, type AxiosRequestConfig } from "axios";
import { readJSON, removeKey, writeJSON } from "./storage";

const BASE = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/$/, "") || "http://127.0.0.1:8000";

export const TOKEN_KEY = "aq.token";

export const api = axios.create({
  baseURL: `${BASE}/api/v1`,
  headers: { "Content-Type": "application/json", Accept: "application/json" },
  withCredentials: true,
  timeout: 15000,
});

export function getToken(): string | null {
  return readJSON<string | null>(TOKEN_KEY, null);
}
export function setToken(token: string | null) {
  if (token) writeJSON(TOKEN_KEY, token);
  else removeKey(TOKEN_KEY);
}

/** Dernier aller-retour mesuré, transmis au serveur (rtt_ms) pour un calcul de temps équitable. */
let lastRtt = 0;
export const getLastRtt = () => lastRtt;

type Timed = AxiosRequestConfig & { metadata?: { start: number } };

api.interceptors.request.use((config) => {
  const token = getToken();
  if (token) config.headers.Authorization = `Bearer ${token}`;
  (config as Timed).metadata = { start: performance.now() };
  return config;
});

let onUnauthorized: (() => void) | null = null;
export function setUnauthorizedHandler(fn: () => void) {
  onUnauthorized = fn;
}

api.interceptors.response.use(
  (res) => {
    const start = (res.config as Timed).metadata?.start;
    if (start) lastRtt = Math.round(performance.now() - start);
    return res;
  },
  (error: AxiosError) => {
    if (error.response?.status === 401 && getToken()) onUnauthorized?.();
    return Promise.reject(error);
  },
);

export type ErrorKind = "network" | "rate_limit" | "unauthorized" | "validation" | "not_found" | "server" | "other";

export interface NormalizedError {
  kind: ErrorKind;
  status?: number;
  message: string;
  fieldErrors?: Record<string, string[]>;
  retryAfter?: number;
}

/** Transforme une erreur axios en objet simple ; `message` vient du serveur quand il est lisible. */
export function normalizeError(err: unknown): NormalizedError {
  if (!axios.isAxiosError(err)) return { kind: "other", message: String(err) };
  const res = err.response;
  if (!res) return { kind: "network", message: "" };
  const body = (res.data ?? {}) as { message?: string; errors?: Record<string, string[]> };
  // Certains messages du backend sont mal encodés (« terminÃƒ… ») : on ne les affiche pas.
  const serverMsg = body.message && !/Ã|Â/.test(body.message) ? body.message : "";
  const status = res.status;
  if (status === 429) {
    const ra = Number(res.headers["retry-after"]);
    return { kind: "rate_limit", status, message: serverMsg, retryAfter: Number.isFinite(ra) ? ra : undefined };
  }
  if (status === 401) return { kind: "unauthorized", status, message: serverMsg };
  if (status === 422) return { kind: "validation", status, message: serverMsg, fieldErrors: body.errors };
  if (status === 404) return { kind: "not_found", status, message: serverMsg };
  if (status >= 500) return { kind: "server", status, message: "" };
  return { kind: "other", status, message: serverMsg };
}

/** Réessaie automatiquement les erreurs réseau (pas les erreurs métier). */
export async function withRetry<T>(fn: () => Promise<T>, retries = 2, delayMs = 800): Promise<T> {
  let attempt = 0;
  for (;;) {
    try {
      return await fn();
    } catch (e) {
      const n = normalizeError(e);
      if (attempt >= retries || (n.kind !== "network" && n.kind !== "server")) throw e;
      attempt++;
      await new Promise((r) => setTimeout(r, delayMs * attempt));
    }
  }
}
