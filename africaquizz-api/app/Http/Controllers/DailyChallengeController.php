<?php

namespace App\Http\Controllers;

use App\Models\DailyChallenge;
use App\Models\DailyChallengeQuestion;
use App\Models\Question;
use App\Models\QuizSession;
use App\Models\QuizSessionQuestion;
use App\Models\UserDailyResult;
use App\Models\User;
use Illuminate\Http\Request;

class DailyChallengeController extends Controller
{
    // Récupérer le défi du jour
    public function show(Request $request)
    {
        $today = now()->toDateString();
        $challenge = DailyChallenge::where('challenge_date', $today)->first();

        if (!$challenge) {
            return response()->json([
                'data' => ['status' => 'not_yet', 'date' => $today]
            ]);
        }

        // Vérifier si le joueur a déjà joué
        $user = $request->user('sanctum');
        $hasPlayed = false;
        $myResult = null;

        if ($user) {
            $result = UserDailyResult::where('user_id', $user->id)
                ->where('challenge_date', $today)
                ->first();
            if ($result) {
                $hasPlayed = true;
                $myResult = ['score' => $result->score, 'correct' => $result->correct_answers];
            }
        }

        $status = $hasPlayed ? 'played' : ($challenge->closes_at->isPast() ? 'closed' : 'available');

        return response()->json([
            'data' => [
                'date' => $today,
                'status' => $status,
                'closes_at' => $challenge->closes_at->toIso8601String(),
                'question_count' => $challenge->questions()->count(),
                'my_result' => $myResult,
            ]
        ]);
    }

    // Démarrer le défi du jour
    public function start(Request $request)
    {
        $today = now()->toDateString();
        $challenge = DailyChallenge::where('challenge_date', $today)->firstOrFail();
        $user = $request->user('sanctum');

        // Vérifier que l'utilisateur n'a pas déjà joué
        if ($user) {
            $alreadyPlayed = UserDailyResult::where('user_id', $user->id)
                ->where('challenge_date', $today)
                ->exists();
            if ($alreadyPlayed) {
                return response()->json(['message' => 'Vous avez déjà joué le défi du jour.'], 422);
            }
        }

        // Créer une session de quiz
        $session = QuizSession::create([
            'user_id' => $user?->id,
            'guest_session_id' => null,
            'mode' => 'daily',
            'status' => 'active',
            'score' => 0,
            'xp_earned' => 0,
            'expires_at' => now()->addHours(24),
        ]);

        // Associer les questions
        $questions = $challenge->questions()->orderBy('position')->get();
        $locale = $request->input('locale', 'fr');
        $questionData = [];

        foreach ($questions as $dcq) {
            QuizSessionQuestion::create([
                'quiz_session_id' => $session->id,
                'question_id' => $dcq->question_id,
                'position' => $dcq->position,
                'served_at' => now(),
            ]);

            $question = $dcq->question;
            $translation = $question->translations()->where('locale', $locale)->first();
            $options = $question->options()->get()->map(function ($opt) use ($locale) {
                $optTrans = $opt->translations()->where('locale', $locale)->first();
                return ['key' => $opt->option_key, 'label' => $optTrans ? $optTrans->text : ''];
            });

            $questionData[] = [
                'id' => $question->id,
                'position' => $dcq->position,
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
                'mode' => 'daily',
                'date' => $today,
                'server_time' => now()->toIso8601String(),
                'expires_at' => $session->expires_at->toIso8601String(),
                'questions' => $questionData,
            ]
        ], 201);
    }
}