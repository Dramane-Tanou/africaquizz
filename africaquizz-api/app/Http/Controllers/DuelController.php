<?php
namespace App\Http\Controllers;

use App\Models\Duel;
use App\Models\DuelQuestion;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\Request;

class DuelController extends Controller
{
    // Créer un duel (invitation)
    public function create(Request $request)
    {
        $validated = $request->validate([
            'opponent_email' => 'nullable|email',
            'opponent_pseudo' => 'nullable|string',
        ]);

        $user = $request->user();
        $opponent = null;
        if (!empty($validated['opponent_email'])) {
            $opponent = User::where('email', $validated['opponent_email'])->first();
        }

        $questions = Question::where('status', 'PUBLIEE')->inRandomOrder()->limit(5)->get();
        if ($questions->count() < 5) {
            return response()->json(['message' => 'Pas assez de questions.'], 422);
        }

        $duel = Duel::create([
            'status' => $opponent ? 'active' : 'pending',
            'player1_id' => $user->id,
            'player2_id' => $opponent?->id,
            'question_ids' => $questions->pluck('id')->toArray(),
            'expires_at' => now()->addHours(48),
        ]);

        foreach ($questions as $i => $q) {
            DuelQuestion::create(['duel_id' => $duel->id, 'question_id' => $q->id, 'position' => $i + 1]);
        }

        return response()->json([
            'data' => [
                'duel_id' => $duel->id,
                'status' => $duel->status,
                'opponent_found' => (bool)$opponent,
                'expires_at' => $duel->expires_at->toIso8601String(),
            ]
        ], 201);
    }

    // Rejoindre un duel (si player2 pas encore défini)
    public function join(Request $request, $id)
    {
        $duel = Duel::findOrFail($id);
        $user = $request->user();

        if ($duel->player2_id === $user->id) {
            return response()->json(['message' => 'Déjà participant.'], 422);
        }
        if ($duel->player2_id && $duel->player2_id !== $user->id) {
            return response()->json(['message' => 'Duel complet.'], 422);
        }
        if ($duel->expires_at < now()) {
            return response()->json(['message' => 'Duel expiré.'], 410);
        }

        $duel->update(['player2_id' => $user->id, 'status' => 'active']);
        return response()->json(['data' => ['joined' => true]]);
    }

    // Jouer un duel (submit all answers)
    public function play(Request $request, $id)
    {
        $duel = Duel::findOrFail($id);
        $user = $request->user();

        if ($duel->expires_at < now()) {
            return response()->json(['message' => 'Duel expiré.'], 410);
        }

        $isPlayer1 = $duel->player1_id === $user->id;
        $isPlayer2 = $duel->player2_id === $user->id;
        if (!$isPlayer1 && !$isPlayer2) {
            return response()->json(['message' => 'Vous ne participez pas à ce duel.'], 403);
        }
        if ($isPlayer1 && $duel->player1_played_at) {
            return response()->json(['message' => 'Vous avez déjà joué.'], 422);
        }
        if ($isPlayer2 && $duel->player2_played_at) {
            return response()->json(['message' => 'Vous avez déjà joué.'], 422);
        }

        $validated = $request->validate([
            'answers' => 'required|array|size:5',
            'answers.*.question_id' => 'required|uuid',
            'answers.*.answer' => 'required|string',
        ]);

        $questions = Question::whereIn('id', $duel->question_ids)->get()->keyBy('id');
        $score = 0; $correct = 0;
        foreach ($validated['answers'] as $a) {
            $q = $questions->get($a['question_id']);
            if (!$q) continue;
            $c = $q->options()->where('is_correct', true)->first();
            if ($c && $c->option_key === $a['answer']) { $score += 100; $correct++; }
        }

        if ($isPlayer1) {
            $duel->update(['player1_score' => $score, 'player1_correct' => $correct, 'player1_played_at' => now()]);
        } else {
            $duel->update(['player2_score' => $score, 'player2_correct' => $correct, 'player2_played_at' => now()]);
        }

        // Si les 2 ont joué → finaliser
        $duel->refresh();
        if ($duel->player1_played_at && $duel->player2_played_at) {
            $winner = null;
            if ($duel->player1_score > $duel->player2_score) $winner = $duel->player1_id;
            elseif ($duel->player2_score > $duel->player1_score) $winner = $duel->player2_id;
            $duel->update(['status' => 'finished', 'winner_id' => $winner]);
        }

        return response()->json([
            'data' => [
                'your_score' => $score,
                'your_correct' => $correct,
                'duel_status' => $duel->status,
                'winner_id' => $duel->winner_id,
            ]
        ]);
    }

    // Mes duels
    public function index(Request $request)
    {
        $user = $request->user();
        $duels = Duel::where('player1_id', $user->id)
            ->orWhere('player2_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
        return response()->json(['data' => $duels]);
    }
}