<?php

namespace App\Http\Controllers;

use App\Models\QuizSession;
use App\Models\QuizAnswer;
use App\Models\Question;
use App\Models\QuizSessionQuestion;
use App\Models\RevisionQueue;
use App\Models\Streak;
use App\Models\Badge;
use App\Models\UserBadge;
use App\Models\UserDailyResult;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public const MIN_COUNTRY_QUESTIONS = 5;
    // En dessous, le pourcentage de bonnes réponses n'est pas significatif : on ne l'affiche pas
    public const SOCIAL_PROOF_MIN_ANSWERS = 20;

    public function start(Request $request)
    {
        $validated = $request->validate([
            'mode' => 'required|string|in:classic,rapid,daily',
            'locale' => 'nullable|string|in:fr,en',
            // Mode « Mon pays » : questions rattachées à ce pays (code ISO-2).
            'country' => 'nullable|string|size:2|exists:countries,iso2',
        ]);

        $locale = $validated['locale'] ?? 'fr';
        $mode = $validated['mode'];
        $country = isset($validated['country']) ? strtoupper($validated['country']) : null;
        $questionCount = ($mode === 'rapid') ? 20 : 10;

        $questions = Question::where('status', 'PUBLIEE')
            ->when($country, fn ($query) => $query->whereHas('countries', fn ($q) => $q->where('iso2', $country)))
            ->inRandomOrder()
            ->limit($questionCount)
            ->get();

        if ($country && $questions->count() < self::MIN_COUNTRY_QUESTIONS) {
            return response()->json(['message' => 'Pas encore assez de questions sur ce pays.'], 404);
        }

        if ($questions->isEmpty()) {
            return response()->json(['message' => 'Aucune question disponible.'], 404);
        }

        $session = QuizSession::create([
            'user_id' => $request->user('sanctum')?->id,
            'guest_session_id' => null,
            'mode' => $mode,
            'status' => 'active',
            'score' => 0,
            'xp_earned' => 0,
            'expires_at' => now()->addMinutes(30),
        ]);

        $questionData = [];
        foreach ($questions as $index => $question) {
            QuizSessionQuestion::create([
                'quiz_session_id' => $session->id,
                'question_id' => $question->id,
                'position' => $index + 1,
                'served_at' => now(),
            ]);

            $translation = $question->translations()->where('locale', $locale)->first();
            $options = $question->options()->get()->map(function ($opt) use ($locale) {
                $optTrans = $opt->translations()->where('locale', $locale)->first();
                return ['key' => $opt->option_key, 'label' => $optTrans ? $optTrans->text : ''];
            });

            $questionData[] = [
                'id' => $question->id,
                'position' => $index + 1,
                'type' => $question->type,
                'text' => $translation ? $translation->text : '',
                'time_limit_s' => $question->time_limit_s,
                'difficulty' => $question->difficulty,
                'options' => $options,
            ];
        }

        return response()->json([
            'data' => [
                'id' => $session->id,
                'mode' => $session->mode,
                'server_time' => now()->toIso8601String(),
                'expires_at' => $session->expires_at->toIso8601String(),
                'questions' => $questionData,
            ]
        ], 201);
    }

    public function answer(Request $request, $sessionId)
    {
        $validated = $request->validate([
            'question_id' => 'required|uuid',
            'answer' => 'required|string',
            'client_elapsed_ms' => 'required|integer|min:0',
            'rtt_ms' => 'nullable|integer|min:0',
        ]);

        $session = QuizSession::findOrFail($sessionId);

        if ($session->status !== 'active') {
            return response()->json(['message' => 'Session terminée ou expirée.'], 422);
        }

        QuizSessionQuestion::where('quiz_session_id', $session->id)
            ->where('question_id', $validated['question_id'])
            ->firstOrFail();

        $existingAnswer = QuizAnswer::where('quiz_session_id', $session->id)
            ->where('question_id', $validated['question_id'])
            ->first();

        if ($existingAnswer) {
            return response()->json(['message' => 'Déjà répondu à cette question.'], 422);
        }

        $question = Question::findOrFail($validated['question_id']);
        $correctOption = $question->options()->where('is_correct', true)->first();
        $isCorrect = ($correctOption && $correctOption->option_key === $validated['answer']);

        $timeLimit = $question->time_limit_s;
        $rtt = $validated['rtt_ms'] ?? 0;
        $tolerance = min($rtt, 2000);
        $tEff = max($validated['client_elapsed_ms'], ($timeLimit * 1000) - $tolerance);
        $tEff = min($tEff, $timeLimit * 1000);

        $speedBonus = 0;
        $scoreGained = 0;
        $xpGained = 0;
        $comboStreak = 0;

        if ($isCorrect) {
            $speedBonus = (int) round(50 * max(0, 1 - ($tEff / ($timeLimit * 1000))));

            $previousAnswers = QuizAnswer::where('quiz_session_id', $session->id)
                ->orderBy('created_at', 'desc')
                ->get();
            $streak = 0;
            foreach ($previousAnswers as $ans) {
                if ($ans->is_correct) $streak++;
                else break;
            }
            $newStreak = $streak + 1;
            $combo = min(0.30, 0.05 * max(0, $newStreak - 2));
            $comboStreak = $newStreak;

            $scoreGained = (int) round((100 + $speedBonus) * (1 + $combo));

            $mult = match($question->difficulty) {
                'decouverte' => 1.0,
                'connaisseur' => 1.25,
                'expert' => 1.5,
                'legende' => 2.0,
                default => 1.0,
            };
            $xpGained = (int) round((100 + $speedBonus) * $mult * (1 + $combo));
        }

        QuizAnswer::create([
            'quiz_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_given' => $validated['answer'],
            'is_correct' => $isCorrect,
            'client_elapsed_ms' => $validated['client_elapsed_ms'],
            'rtt_ms' => $rtt,
            'speed_bonus' => $speedBonus,
            'score_gained' => $scoreGained,
            'xp_gained' => $xpGained,
            'combo_streak' => $comboStreak,
        ]);

        $session->increment('score', $scoreGained);
        $session->increment('xp_earned', $xpGained);

        // Question ratée (ou temps écoulé) : elle rejoint la file de révision espacée du joueur
        if (!$isCorrect && $session->user_id) {
            RevisionQueue::enqueue($session->user, $question->id);
        }

        $locale = $request->input('locale', 'fr');
        $translation = $question->translations()->where('locale', $locale)->first();

        // Norme sociale : part réelle des joueurs qui ont trouvé cette question
        $stats = QuizAnswer::where('question_id', $question->id)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_correct THEN 1 ELSE 0 END) as correct')
            ->first();

        return response()->json([
            'data' => [
                'correct' => $isCorrect,
                'correct_answer' => $correctOption ? $correctOption->option_key : null,
                'speed_bonus' => $speedBonus,
                'score_gained' => $scoreGained,
                'xp_gained' => $xpGained,
                'combo' => [
                    'streak' => $comboStreak,
                    'bonus_pct' => $comboStreak >= 3 ? min(30, 5 * ($comboStreak - 2)) : 0,
                ],
                'explanation' => $translation ? $translation->explanation : '',
                'key_takeaway' => $translation ? $translation->key_takeaway : '',
                'social_proof' => $stats->total >= self::SOCIAL_PROOF_MIN_ANSWERS ? [
                    'correct_pct' => (int) round(100 * $stats->correct / $stats->total),
                    'answers' => (int) $stats->total,
                ] : null,
                'session' => [
                    'score' => $session->fresh()->score,
                    'xp' => $session->fresh()->xp_earned,
                ],
            ]
        ]);
    }

    public function finish(Request $request, $sessionId)
    {
        $session = QuizSession::findOrFail($sessionId);

        if ($session->status !== 'active') {
            return response()->json(['message' => 'Session déjà terminée.'], 422);
        }

        $totalQuestions = $session->sessionQuestions()->count();
        $correctAnswers = $session->answers()->where('is_correct', true)->count();

        $completionBonus = 20;
        $perfectBonus = ($correctAnswers === $totalQuestions && $totalQuestions > 0) ? 50 : 0;
        $totalXp = $session->xp_earned + $completionBonus + $perfectBonus;

        $session->update([
            'status' => 'finished',
            'xp_earned' => $totalXp,
        ]);

        $streakData = null;
        $badgesEarned = [];
        // Notif push si user connecté
        if ($session->user_id && $totalXp > 0) {
            try {
                app(\App\Services\WebPushService::class)->sendToUser(
                    $session->user_id,
                    'Africa Quizz 🎯',
                    "Partie terminée : +{$totalXp} XP !",
                    ['type' => 'quiz_finished', 'session_id' => $session->id]
                );
            } catch (\Exception $e) { /* silent */ }
        }


        if ($session->user_id) {
            $user = $session->user;
            if ($user) {
                // 1. Créditer l'XP
                if ($user->profile) {
                    $user->profile->addXp($totalXp, 'quiz_completion', $session->id);
                }

                // 2. Mettre à jour la série
                $streak = $user->streak ?: Streak::create(['user_id' => $user->id])->refresh();
                $streakData = $streak->updateOnPlay($user->profile?->timezone ?? 'UTC');

                // 3. Badge "Premier quiz"
                $partiesCount = $user->xpTransactions()->where('reason', 'quiz_completion')->count();
                if ($partiesCount === 1) {
                    $firstQuizBadge = Badge::where('code', 'first_quiz')->first();
                    if ($firstQuizBadge) {
                        $existing = UserBadge::where('user_id', $user->id)
                            ->where('badge_id', $firstQuizBadge->id)
                            ->where('tier', 'bronze')
                            ->exists();
                        if (!$existing) {
                            UserBadge::create([
                                'user_id' => $user->id,
                                'badge_id' => $firstQuizBadge->id,
                                'tier' => 'bronze',
                                'earned_at' => now(),
                            ]);
                            $badgesEarned[] = ['code' => 'first_quiz', 'tier' => 'bronze'];
                        }
                    }
                }

                // 4. Si c'est le défi du jour, enregistrer le résultat
                if ($session->mode === 'daily') {
                    $today = now()->toDateString();
                    $alreadyRecorded = UserDailyResult::where('user_id', $user->id)
                        ->where('challenge_date', $today)
                        ->exists();
                    if (!$alreadyRecorded) {
                        UserDailyResult::create([
                            'user_id' => $user->id,
                            'quiz_session_id' => $session->id,
                            'challenge_date' => $today,
                            'score' => $session->score,
                            'correct_answers' => $correctAnswers,
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'data' => [
                'score' => $session->score,
                'correct' => $correctAnswers,
                'total' => $totalQuestions,
                'xp_total_gained' => $totalXp,
                'bonuses' => [
                    ['type' => 'completion', 'xp' => $completionBonus],
                    ['type' => 'perfect', 'xp' => $perfectBonus],
                ],
                'streak' => $streakData,
                'badges_earned' => $badgesEarned,
            ]
        ]);
    }
}