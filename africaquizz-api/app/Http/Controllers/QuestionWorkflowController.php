<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\AuditLog;
use App\Services\QuestionWorkflowService;
use Illuminate\Http\Request;

class QuestionWorkflowController extends Controller
{
    public function __construct(private QuestionWorkflowService $workflow) {}

    public function index(Request $request)
    {
        $query = Question::with(['translations', 'category'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $statuses = $request->input('status');
            $statuses = is_array($statuses) ? $statuses : explode(',', (string) $statuses);
            $query->whereIn('status', array_filter($statuses));
        }
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $questions = $query->limit(100)->get();

        return response()->json([
            'data' => [
                'data' => $questions,
                'total' => $questions->count(),
                'per_page' => 100,
            ]
        ]);
    }

    public function show($id)
    {
        $question = Question::with([
            'translations', 'options.translations', 'sources', 'countries', 'category'
        ])->findOrFail($id);

        return response()->json(['data' => $question]);
    }

    public function transition(Request $request, $id)
    {
        $validated = $request->validate([
            'target_status' => 'required|in:A_VERIFIER,VALIDEE,PUBLIEE,A_REVISER,ARCHIVEE,BROUILLON',
            'checklist' => 'nullable|array',
            'comment' => 'nullable|string|max:500',
        ]);

        $question = Question::findOrFail($id);
        $admin = $request->user('admin');

        try {
            $result = $this->workflow->transition(
                $admin,
                $question,
                $validated['target_status'],
                $validated['checklist'] ?? null,
                $validated['comment'] ?? null
            );
            return response()->json(['data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function versions($id)
    {
        $versions = QuestionVersion::where('question_id', $id)
            ->orderBy('version_number', 'desc')
            ->get();
        return response()->json(['data' => $versions]);
    }

    public function auditLogs(Request $request)
    {
        if (!$request->user('admin')->canManageUsers()) {
            return response()->json(['message' => 'Accès réservé aux administrateurs.'], 403);
        }

        $query = AuditLog::orderBy('created_at', 'desc');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        $logs = $query->limit(100)->get();

        return response()->json([
            'data' => [
                'data' => $logs,
                'total' => $logs->count(),
            ]
        ]);
    }
}