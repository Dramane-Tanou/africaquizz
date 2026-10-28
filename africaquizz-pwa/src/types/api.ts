// Africa Quizz API - Types TypeScript v1.1.0

export interface User {
  id: string;
  name: string;
  email: string;
  email_verified_at: string | null;
  date_of_birth: string | null;
  age: number | null;
  is_minor: boolean;
  is_banned: boolean;
  is_anonymized: boolean;
  created_at: string;
  updated_at: string;
  profile?: Profile;
}

export interface Profile {
  id: string;
  user_id: string;
  pseudo: string;
  avatar: string;
  country_id: string | null;
  birth_year: number | null;
  timezone: string;
  locale: 'fr' | 'en';
  is_protected: boolean;
  total_xp: number;
  level: number;
  rank: 'curieux' | 'explorateur' | 'initie' | 'erudit' | 'maitre_du_savoir';
}

export interface Country {
  id: string;
  iso2: string;
  iso3: string;
}

export interface RegisterRequest {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  date_of_birth: string;
  timezone?: string;
  locale?: 'fr' | 'en';
  parental_email?: string;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface AuthResponse {
  data: {
    user: User;
    token: string;
    requires_email_verification?: boolean;
    requires_parental_consent?: boolean;
  };
}

export type Difficulty = 'decouverte' | 'connaisseur' | 'expert' | 'legende';
export type QuizMode = 'classic' | 'rapid' | 'daily' | 'revision';

export interface QuestionOption {
  key: 'A' | 'B' | 'C' | 'D';
  label: string;
}

export interface Question {
  id: string;
  position: number;
  type: 'mcq' | 'true_false' | 'image' | 'matching' | 'ordering' | 'rapid_fire';
  text: string;
  time_limit_s: number;
  difficulty: Difficulty;
  options: QuestionOption[];
  image?: {
    url: string;
    alt: string;
    credit: string;
    blurhash?: string;
  };
}

export interface QuizSession {
  id: string;
  mode: QuizMode;
  server_time: string;
  expires_at: string;
  questions: Question[];
}

export interface AnswerRequest {
  question_id: string;
  answer: 'A' | 'B' | 'C' | 'D' | string;
  client_elapsed_ms: number;
  rtt_ms?: number;
  locale?: 'fr' | 'en';
}

export interface AnswerResponse {
  data: {
    correct: boolean;
    correct_answer: string;
    speed_bonus: number;
    score_gained: number;
    xp_gained: number;
    combo: { streak: number; bonus_pct: number };
    explanation: string;
    key_takeaway: string;
    session: { score: number; xp: number };
  };
}

export interface FinishResponse {
  data: {
    score: number;
    correct: number;
    total: number;
    xp_total_gained: number;
    bonuses: { type: string; xp: number }[];
    streak: { current: number; longest: number; freezes: number } | null;
    badges_earned: { code: string; tier: string }[];
  };
}

export interface DailyChallenge {
  date: string;
  status: 'available' | 'played' | 'closed' | 'not_yet';
  closes_at: string;
  question_count: number;
  my_result: { score: number; correct: number } | null;
}

export type LeaderboardScope = 'daily' | 'global' | 'country' | 'friends';
export type LeaderboardPeriod = 'day' | 'week' | 'month' | 'all';

export interface LeaderboardEntry {
  rank: number;
  value: number;
  user: {
    pseudo: string;
    country: string | null;
    avatar: string;
    rank: string;
  };
  detail?: { correct_answers?: number };
}

export interface Leaderboard {
  data: {
    entries: LeaderboardEntry[];
    me: { rank: number; value: number } | null;
  };
  meta: {
    scope: LeaderboardScope;
    period: LeaderboardPeriod;
    period_key: string;
    updated_at: string;
    eligible_players: number;
  };
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

export type ApiResponse<T> = {
  data: T;
  meta?: Record<string, unknown>;
};