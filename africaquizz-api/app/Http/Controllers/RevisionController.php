<?php
namespace App\Http\Controllers;

use App\Models\RevisionQueue;
use App\Models\Question;
use App\Models\QuizSession;
use App\Models\QuizSessionQuestion;
use App\Models\QuizAnswer;
use Illuminate\Http\Request;

class RevisionController extends Controller
{
    // File de révision
    public function queue(Request $request)
    {
        $user = $request->user();
        $due = RevisionQueue::with(['question.translations', 'question.options.translations'])
            ->where('user_id', $user->id)
            ->where('next_review_at', '<=', now())
            ->orderBy('next_review_at')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => [
                'due_count' => $due->count(),
                'items' => $due->map(fn($r) => [
                    'revision_id' => $r->id,
                    'question_id' => $r->question_id,
                    'level' => $r->level,
                    'interval_days' => $r->interval_days,
                    'next_review_at' => $r->next_review_at->toIso8601String(),
                ]),
            ]
        ]);
    }

    // Démarrer une session de révision
    public function start(Request $request)
    {
        $user = $request->user();
        $items = RevisionQueue::where('user_id', $user->id)
            ->where('next_review_at', '<=', now())
            ->orderBy('next_review_at')
            ->limit(20)
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['message' => 'Aucune question à réviser.'], 404);
        }

        $session = QuizSession::create([
            'user_id' => $user->id,
            'mode' => 'revision',
            'status' => 'active',
            'expires_at' => now()->addMinutes(30),
        ]);

        $questionData = [];
        $locale = $request->input('locale', 'fr');
        foreach ($items as $i => $item) {
            QuizSessionQuestion::create([
                'quiz_session_id' => $session->id,
                'question_id' => $item->question_id,
                'position' => $i + 1,
                'served_at' => now(),
            ]);
            $q = $item->question;
            $t = $q->translations()->where('locale', $locale)->first();
            $opts = $q->options()->get()->map(function($o) use ($locale) {
                $ot = $o->translations()->where('locale', $locale)->first();
                return ['key' => $o->option_key, 'label' => $ot?->text ?? ''];
            });
            $questionData[] = [
                'id' => $q->id,
                'position' => $i + 1,
                'text' => $t?->text ?? '',
                'explanation' => $t?->explanation ?? '',
                'type' => $q->type,
                'time_limit_s' => $q->time_limit_s,
                'options' => $opts,
            ];
        }

        return response()->json([
            'data' => [
                'id' => $session->id,
                'mode' => 'revision',
                'questions' => $questionData,
            ]
        ], 201);
    }

    // Soumettre une réponse en révision (impacte la file SRS)
    public function answer(Request $request, $sessionId)
    {
        $validated = $request->validate([
            'question_id' => 'required|uuid',
            'answer' => 'required|string',
        ]);

        $session = QuizSession::where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $q = Question::findOrFail($validated['question_id']);
        $correct = $q->options()->where('is_correct', true)->first();
        $isCorrect = $correct && $correct->option_key === $validated['answer'];

        // Enregistrer la réponse
        QuizAnswer::create([
            'quiz_session_id' => $session->id,
            'question_id' => $q->id,
            'answer_given' => $validated['answer'],
            'is_correct' => $isCorrect,
            'client_elapsed_ms' => 0,
            'rtt_ms' => 0,
            'score_gained' => 0,
            'xp_gained' => $isCorrect ? 50 : 0,
        ]);

        // Mettre à jour la file SRS
        $revision = RevisionQueue::where('user_id', $request->user()->id)
            ->where('question_id', $q->id)->first();
        if ($revision) {
            if ($isCorrect) $revision->advance();
            else $revision->reset();
        }

        return response()->json([
            'data' => [
                'correct' => $isCorrect,
                'correct_answer' => $correct?->option_key,
                'xp_gained' => $isCorrect ? 50 : 0,
                'next_review_at' => $revision?->next_review_at?->toIso8601String(),
            ]
        ]);
    }
}