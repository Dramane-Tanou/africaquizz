// Accès sûr au stockage du navigateur (peut être indisponible en navigation privée).

export function readJSON<T>(key: string, fallback: T, store: Storage | null = safeLocal()): T {
  try {
    const raw = store?.getItem(key);
    return raw ? (JSON.parse(raw) as T) : fallback;
  } catch {
    return fallback;
  }
}

export function writeJSON(key: string, value: unknown, store: Storage | null = safeLocal()): void {
  try {
    store?.setItem(key, JSON.stringify(value));
  } catch {
    /* stockage plein ou bloqué : on ignore */
  }
}

export function removeKey(key: string, store: Storage | null = safeLocal()): void {
  try {
    store?.removeItem(key);
  } catch {
    /* ignore */
  }
}

export function safeLocal(): Storage | null {
  try {
    return window.localStorage;
  } catch {
    return null;
  }
}

export function safeSession(): Storage | null {
  try {
    return window.sessionStorage;
  } catch {
    return null;
  }
}

/** UUID v4 — crypto.randomUUID n'existe pas hors contexte sécurisé (ex. http://192.168.x.x). */
export function uuid(): string {
  if (typeof crypto !== "undefined" && typeof crypto.randomUUID === "function" && window.isSecureContext) {
    return crypto.randomUUID();
  }
  const b = new Uint8Array(16);
  crypto.getRandomValues(b);
  b[6] = (b[6] & 0x0f) | 0x40;
  b[8] = (b[8] & 0x3f) | 0x80;
  const h = Array.from(b, (x) => x.toString(16).padStart(2, "0")).join("");
  return `${h.slice(0, 8)}-${h.slice(8, 12)}-${h.slice(12, 16)}-${h.slice(16, 20)}-${h.slice(20)}`;
}
