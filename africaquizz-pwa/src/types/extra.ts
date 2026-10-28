// Types complémentaires, déduits des contrôleurs Laravel (non présents dans api-types.ts).
import type { Difficulty, Profile, Question, QuestionOption, User } from "./api";

export type Locale = "fr" | "en";
export type PlayMode = "classic" | "rapid" | "daily" | "revision" | "onboarding";

export interface MeProgress extends User {
  profile?: Profile;
}

export interface GuestResponse {
  data: { token: string; expires_at: string };
}

export interface DailyChallengeResponse {
  data: {
    date: string;
    status: "available" | "played" | "closed" | "not_yet";
    closes_at?: string;
    question_count?: number;
    my_result?: { score: number; correct: number } | null;
  };
}

/** Session renvoyée par /quiz/sessions, /daily-challenge/start ou /review/sessions. */
export interface PlaySession {
  id: string;
  mode: string;
  server_time?: string;
  expires_at?: string;
  date?: string;
  questions: PlayQuestion[];
}

export interface PlayQuestion extends Omit<Question, "difficulty" | "options"> {
  difficulty?: Difficulty;
  options: QuestionOption[];
  explanation?: string;
}

export interface RevisionQueueResponse {
  data: {
    due_count: number;
    items: { revision_id: string; question_id: string; level: number; interval_days: number; next_review_at: string }[];
  };
}

export interface RevisionAnswerResponse {
  data: { correct: boolean; correct_answer: string | null; xp_gained: number; next_review_at: string | null };
}

export interface ExportResponse {
  data: {
    user: { id: string; name: string; email: string; date_of_birth: string | null; created_at: string };
    profile: Profile | null;
    streak: {
      current_streak: number;
      longest_streak: number;
      freezes_available: number;
      last_active_date: string | null;
    } | null;
    badges: { id: string; badge_id: string; tier: string; earned_at: string; badge?: { code: string } }[];
    daily_results: { challenge_date: string; score: number; correct_answers: number }[];
    quiz_sessions: {
      id: string;
      mode: string;
      status: string;
      score: number;
      xp_earned: number;
      created_at: string;
      answers?: { is_correct: boolean }[];
    }[];
  };
  exported_at: string;
}
