<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionReport;
use App\Services\QuestionReportService;
use Illuminate\Http\Request;

class QuestionReportController extends Controller
{
    public function __construct(private QuestionReportService $service) {}

    public function store(Request $request, $questionId)
    {
        $validated = $request->validate([
            'reason' => 'required|in:' . implode(',', array_keys(QuestionReport::REASONS)),
            'comment' => 'nullable|string|max:1000',
            'guest_session_id' => 'nullable|uuid',
        ]);

        $question = Question::findOrFail($questionId);
        $user = $request->user('sanctum');
        $guestSessionId = $validated['guest_session_id'] ?? null;

        try {
            $result = $this->service->submitReport(
                $question,
                $validated['reason'],
                $validated['comment'] ?? null,
                $user,
                $guestSessionId
            );

            return response()->json([
                'data' => [
                    'report_id' => $result['report']->id,
                    'pending_count' => $result['pending_count'],
                    'distinct_reporters' => $result['distinct_reporters'],
                    'auto_flagged' => $result['auto_flagged'],
                    'message' => $result['auto_flagged']
                        ? 'Merci ! La question est passée en révision automatique.'
                        : 'Merci pour votre signalement.',
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}