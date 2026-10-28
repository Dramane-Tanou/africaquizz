<?php

namespace App\Http\Controllers;

use App\Models\QuestionReport;
use App\Services\QuestionReportService;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function __construct(private QuestionReportService $service) {}

    public function index(Request $request)
    {
        $query = QuestionReport::with(['question.translations', 'user.profile'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }

        $reports = $query->limit(100)->get();

        return response()->json([
            'data' => [
                'data' => $reports->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'question_id' => $r->question_id,
                        'reason' => $r->reason,
                        'reason_label' => $r->reason_label,
                        'comment' => $r->comment,
                        'status' => $r->status,
                        'created_at' => $r->created_at->toIso8601String(),
                        'reporter' => $r->user ? ['pseudo' => $r->user->profile?->pseudo] : ['guest' => true],
                        'question_excerpt' => $r->question?->translations->firstWhere('locale', 'fr')?->text,
                    ];
                }),
                'total' => $reports->count(),
            ]
        ]);
    }

    public function show($id)
    {
        $report = QuestionReport::with([
            'question.translations', 'question.options.translations',
            'question.sources', 'user.profile', 'reviewedBy'
        ])->findOrFail($id);

        return response()->json(['data' => $report]);
    }

    // ✅ Version tolérante : accepte decision OU status, et booléens string
    public function resolve(Request $request, $id)
    {
        // Accepte "decision" ou "status" comme nom de champ
        $decision = $request->input('decision') ?? $request->input('status');

        if (!$decision) {
            return response()->json([
                'message' => 'Le champ "decision" est requis.',
                'debug' => [
                    'received_keys' => array_keys($request->all()),
                    'received_body' => $request->all(),
                ]
            ], 422);
        }

        if (!in_array($decision, ['resolved', 'dismissed'])) {
            return response()->json([
                'message' => 'La décision doit être "resolved" ou "dismissed".',
            ], 422);
        }

        // Accepte plusieurs formats pour le booléen
        $grantXp = filter_var(
            $request->input('grant_xp_compensation', false),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? false;

        $note = $request->input('note');
        if (is_array($note)) {
            $note = json_encode($note);
        }

        $report = QuestionReport::findOrFail($id);
        $admin = $request->user('admin');

        try {
            $result = $this->service->resolveReport($admin, $report, $decision, $note, $grantXp);

            return response()->json([
                'data' => [
                    'report_id' => $result['report']->id,
                    'status' => $result['report']->status,
                    'compensation' => $result['compensation'],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}