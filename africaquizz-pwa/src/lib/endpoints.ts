// Tous les appels à l'API Laravel, typés. Une seule source de vérité pour les URL.
import { api, getLastRtt, withRetry } from "./api";
import { uuid } from "./storage";
import type {
  AnswerResponse,
  AuthResponse,
  Country,
  FinishResponse,
  Leaderboard,
  LeaderboardPeriod,
  LeaderboardScope,
  LoginRequest,
  Profile,
  RegisterRequest,
} from "../types/api";
import type {
  DailyChallengeResponse,
  ExportResponse,
  GuestResponse,
  Locale,
  MeProgress,
  PlaySession,
  RevisionAnswerResponse,
  RevisionQueueResponse,
} from "../types/extra";

// ---------- Auth ----------
export const authApi = {
  login: (body: LoginRequest) => api.post<AuthResponse>("/auth/login", body).then((r) => r.data.data),
  register: (body: RegisterRequest) => api.post<AuthResponse>("/auth/register", body).then((r) => r.data.data),
  guest: () => api.post<GuestResponse>("/auth/guest").then((r) => r.data.data),
  logout: () => api.post("/auth/logout"),
  me: () => api.get<{ data: MeProgress }>("/me/progress").then((r) => r.data.data),
  verifyEmail: (token: string) => api.post("/auth/verify-email", { token }),
  resendVerification: () => api.post<{ message: string }>("/auth/resend-verification"),
  forgotPassword: (email: string) => api.post<{ message: string }>("/auth/forgot-password", { email }),
  resetPassword: (body: { token: string; email: string; password: string; password_confirmation: string }) =>
    api.post<{ message: string }>("/auth/reset-password", body),
  exportData: () => api.get<ExportResponse>("/me/export").then((r) => r.data),
  deleteAccount: (password: string) =>
    api.delete<{ message: string }>("/me", { data: { password, confirmation: "SUPPRIMER" } }),
  updateProfile: (body: { country_id: string | null }) =>
    api.patch<{ data: Profile }>("/me/profile", body).then((r) => r.data.data),
};

export const countriesApi = {
  list: () => api.get<{ data: Country[] }>("/countries").then((r) => r.data.data),
};

// ---------- Quiz ----------
export const quizApi = {
  start: (mode: "classic" | "rapid", locale: Locale) =>
    api
      .post<{ data: PlaySession }>("/quiz/sessions", { mode, locale }, { headers: { "Idempotency-Key": uuid() } })
      .then((r) => r.data.data),

  /** La même clé d'idempotence est réutilisée pour les nouvelles tentatives réseau. */
  answer: (sessionId: string, questionId: string, answer: string, elapsedMs: number, locale: Locale) => {
    const key = uuid();
    return withRetry(() =>
      api
        .post<AnswerResponse>(
          `/quiz/sessions/${sessionId}/answers`,
          {
            question_id: questionId,
            answer,
            client_elapsed_ms: Math.max(0, Math.round(elapsedMs)),
            rtt_ms: getLastRtt(),
            locale,
          },
          { headers: { "Idempotency-Key": key } },
        )
        .then((r) => r.data.data),
    );
  },

  finish: (sessionId: string) => {
    const key = uuid();
    return withRetry(() =>
      api
        .post<FinishResponse>(`/quiz/sessions/${sessionId}/finish`, {}, { headers: { "Idempotency-Key": key } })
        .then((r) => r.data.data),
    );
  },
};

// ---------- Défi du jour ----------
export const dailyApi = {
  status: () => api.get<DailyChallengeResponse>("/daily-challenge").then((r) => r.data.data),
  start: (locale: Locale) =>
    api
      .post<{ data: PlaySession }>("/daily-challenge/start", { locale }, { headers: { "Idempotency-Key": uuid() } })
      .then((r) => r.data.data),
};

// ---------- Classements ----------
export const leaderboardApi = {
  get: (scope: LeaderboardScope, period: LeaderboardPeriod, limit = 50) =>
    api
      .get<Leaderboard & { meta: { message?: string } }>("/leaderboards", { params: { scope, period, limit } })
      .then((r) => r.data),
};

// ---------- Révision ----------
export const revisionApi = {
  queue: () => api.get<RevisionQueueResponse>("/review/queue").then((r) => r.data.data),
  start: (locale: Locale) => api.post<{ data: PlaySession }>("/review/sessions", { locale }).then((r) => r.data.data),
  answer: (sessionId: string, questionId: string, answer: string) =>
    withRetry(() =>
      api
        .post<RevisionAnswerResponse>(`/review/sessions/${sessionId}/answers`, { question_id: questionId, answer })
        .then((r) => r.data.data),
    ),
};
