<?php
namespace App\Http\Controllers;

use App\Models\FriendChallenge;
use App\Models\Question;
use App\Models\GuestSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FriendChallengeController extends Controller
{
    // Créer un défi
    public function create(Request $request)
    {
        $user = $request->user('sanctum');
        if (!$user) return response()->json(['message' => 'Connexion requise.'], 401);

        $questions = Question::where('status', 'PUBLIEE')
            ->inRandomOrder()->limit(5)->get();

        if ($questions->count() < 5) {
            return response()->json(['message' => 'Pas assez de questions disponibles.'], 422);
        }

        $challenge = FriendChallenge::create([
            'creator_user_id' => $user->id,
            'creator_pseudo' => $user->profile?->pseudo ?? $user->name,
            'code' => FriendChallenge::generateCode(),
            'question_ids' => $questions->pluck('id')->toArray(),
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'data' => [
                'code' => $challenge->code,
                'share_url' => config('app.url') . '/defi/' . $challenge->code,
                'questions_count' => 5,
                'expires_at' => $challenge->expires_at->toIso8601String(),
            ]
        ], 201);
    }

    // Voir un défi par code (public, avant de jouer)
    public function show($code)
    {
        $challenge = FriendChallenge::where('code', strtoupper($code))->firstOrFail();

        if ($challenge->expires_at < now()) {
            return response()->json(['message' => 'Défi expiré.'], 410);
        }

        return response()->json([
            'data' => [
                'code' => $challenge->code,
                'creator_pseudo' => $challenge->creator_pseudo,
                'creator_score' => $challenge->creator_score,
                'creator_correct' => $challenge->creator_correct,
                'opponent_pseudo' => $challenge->opponent_pseudo,
                'opponent_score' => $challenge->opponent_score,
                'already_played' => !is_null($challenge->opponent_played_at),
                'expires_at' => $challenge->expires_at->toIso8601String(),
            ]
        ]);
    }

    // Jouer un défi (invité OU connecté)
    public function play(Request $request, $code)
    {
        $challenge = FriendChallenge::where('code', strtoupper($code))->firstOrFail();

        if ($challenge->expires_at < now()) {
            return response()->json(['message' => 'Défi expiré.'], 410);
        }
        if ($challenge->opponent_played_at) {
            return response()->json(['message' => 'Défi déjà joué.'], 422);
        }

        $validated = $request->validate([
            'pseudo' => 'required|string|max:50',
            'answers' => 'required|array|size:5',
            'answers.*.question_id' => 'required|uuid',
            'answers.*.answer' => 'required|string',
        ]);

        $questions = Question::whereIn('id', $challenge->question_ids)->get()->keyBy('id');
        $score = 0;
        $correctCount = 0;

        foreach ($validated['answers'] as $a) {
            $q = $questions->get($a['question_id']);
            if (!$q) continue;
            $correct = $q->options()->where('is_correct', true)->first();
            if ($correct && $correct->option_key === $a['answer']) {
                $score += 100;
                $correctCount++;
            }
        }

        $user = $request->user('sanctum');
        $challenge->update([
            'opponent_user_id' => $user?->id,
            'opponent_pseudo' => $validated['pseudo'],
            'opponent_score' => $score,
            'opponent_correct' => $correctCount,
            'opponent_played_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'your_score' => $score,
                'your_correct' => $correctCount,
                'creator_score' => $challenge->creator_score,
                'creator_correct' => $challenge->creator_correct,
                'you_win' => $score > $challenge->creator_score,
                'tie' => $score === $challenge->creator_score,
            ]
        ]);
    }
}