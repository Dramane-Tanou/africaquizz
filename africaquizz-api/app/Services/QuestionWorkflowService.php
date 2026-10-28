<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\QuestionReview;
use App\Models\AuditLog;
use App\Models\AdminUser;
use Illuminate\Support\Facades\DB;

class QuestionWorkflowService
{
    const TRANSITIONS = [
        'BROUILLON' => ['A_VERIFIER'],
        'A_VERIFIER' => ['VALIDEE', 'BROUILLON'],
        'VALIDEE' => ['PUBLIEE', 'A_VERIFIER'],
        'PUBLIEE' => ['A_REVISER', 'ARCHIVEE'],
        'A_REVISER' => ['VALIDEE', 'ARCHIVEE'],
        'ARCHIVEE' => [],
    ];

    const CHECKLIST_KEYS = [
        'fact_exact',
        'sources_ok',
        'single_correct_answer',
        'distractors_plausible',
        'statement_clear',
        'explanation_concise',
        'neutral_respectful',
        'difficulty_consistent',
        'english_reviewed',
        'image_licensed',
    ];

    public function validateChecklist(array $checklist): array
    {
        $missing = [];
        foreach (self::CHECKLIST_KEYS as $key) {
            if (empty($checklist[$key])) {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    public function canTransition(AdminUser $admin, Question $question, string $targetStatus): array
    {
        $current = $question->status;

        // Vérifier la transition autorisée
        if (!in_array($targetStatus, self::TRANSITIONS[$current] ?? [])) {
            return ['ok' => false, 'reason' => "Transition impossible : $current → $targetStatus"];
        }

        // Règles par rôle
        switch ($targetStatus) {
            case 'A_VERIFIER':
                // Rédacteur+ peut soumettre
                if (!$admin->canCreate()) {
                    return ['ok' => false, 'reason' => 'Rôle insuffisant pour soumettre une question.'];
                }
                break;

            case 'VALIDEE':
                // Relecteur+ peut valider
                if (!$admin->canValidate()) {
                    return ['ok' => false, 'reason' => 'Seul un relecteur peut valider.'];
                }
                // Règle des 4 yeux : le créateur ne peut pas valider sa propre question
                if ($question->created_by_id && $question->created_by_id === $admin->id) {
                    return ['ok' => false, 'reason' => 'Règle des 4 yeux : un créateur ne peut pas valider sa propre question.'];
                }
                // Double validation pour is_sensitive
                if ($question->is_sensitive) {
                    $approvals = QuestionReview::where('question_id', $question->id)
                        ->where('decision', 'approved')
                        ->where('reviewer_id', '!=', $admin->id)
                        ->count();
                    if ($approvals < 1) {
                        return ['ok' => false, 'reason' => 'Question sensible : nécessite une double validation (déjà validée par un autre relecteur).'];
                    }
                }
                break;

            case 'PUBLIEE':
                // Éditeur+ peut publier
                if (!$admin->canPublish()) {
                    return ['ok' => false, 'reason' => 'Seul un éditeur peut publier.'];
                }
                break;

            case 'A_REVISER':
            case 'ARCHIVEE':
                if (!$admin->canPublish()) {
                    return ['ok' => false, 'reason' => 'Seul un éditeur peut réviser/archiver.'];
                }
                break;

            case 'BROUILLON':
                // Retour en brouillon : autorisé pour relecteur+ (rejet)
                if (!$admin->canValidate()) {
                    return ['ok' => false, 'reason' => 'Rôle insuffisant.'];
                }
                break;
        }

        return ['ok' => true];
    }

    public function transition(
        AdminUser $admin,
        Question $question,
        string $targetStatus,
        ?array $checklist = null,
        ?string $comment = null
    ): array {
        // Vérifier transition
        $check = $this->canTransition($admin, $question, $targetStatus);
        if (!$check['ok']) {
            throw new \Exception($check['reason']);
        }

        // Pour VALIDEE : checklist obligatoire
        if ($targetStatus === 'VALIDEE') {
            if (!$checklist) {
                throw new \Exception('Checklist obligatoire pour valider une question.');
            }
            $missing = $this->validateChecklist($checklist);
            if (!empty($missing)) {
                throw new \Exception('Checklist incomplète : ' . implode(', ', $missing));
            }
        }

        return DB::transaction(function () use ($admin, $question, $targetStatus, $checklist, $comment) {
            $before = $question->only(['status', 'is_sensitive', 'external_id']);

            // Mise à jour du statut
            $question->update([
                'status' => $targetStatus,
                'created_by_type' => 'admin_user',
                'created_by_id' => $question->created_by_id ?? $admin->id,
            ]);

            // Enregistrer la review si transition vers VALIDEE ou BROUILLON (rejet)
            if (in_array($targetStatus, ['VALIDEE', 'BROUILLON']) && $checklist) {
                QuestionReview::create([
                    'question_id' => $question->id,
                    'reviewer_type' => 'admin_user',
                    'reviewer_id' => $admin->id,
                    'checklist' => $checklist,
                    'decision' => $targetStatus === 'VALIDEE' ? 'approved' : 'rejected',
                    'comment' => $comment,
                ]);
            }

            // Snapshot version à chaque VALIDEE ou PUBLIEE
            if (in_array($targetStatus, ['VALIDEE', 'PUBLIEE'])) {
                $this->createVersionSnapshot($question, $admin);
            }

            // Audit log
            AuditLog::create([
                'actor_type' => 'admin_user',
                'actor_id' => $admin->id,
                'action' => 'question.transition',
                'entity_type' => 'question',
                'entity_id' => $question->id,
                'before' => $before,
                'after' => ['status' => $targetStatus, 'checklist' => $checklist, 'comment' => $comment],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return ['question' => $question->fresh(), 'new_status' => $targetStatus];
        });
    }

    private function createVersionSnapshot(Question $question, AdminUser $admin): void
    {
        $lastVersion = QuestionVersion::where('question_id', $question->id)->max('version_number') ?? 0;

        // Construire le snapshot complet
        $snapshot = [
            'question' => $question->only(['id', 'type', 'difficulty', 'time_limit_s', 'status', 'external_id', 'category_id']),
            'translations' => $question->translations()->get(['locale', 'text', 'explanation', 'key_takeaway'])->toArray(),
            'options' => $question->options()->get()->map(function ($opt) {
                return [
                    'key' => $opt->option_key,
                    'is_correct' => $opt->is_correct,
                    'translations' => $opt->translations()->get(['locale', 'text'])->toArray(),
                ];
            })->toArray(),
            'sources' => $question->sources()->get(['source_type', 'title', 'url'])->toArray(),
            'countries' => $question->countries()->pluck('iso2')->toArray(),
        ];

        QuestionVersion::create([
            'question_id' => $question->id,
            'version_number' => $lastVersion + 1,
            'snapshot' => $snapshot,
            'created_by_type' => 'admin_user',
            'created_by_id' => $admin->id,
            'created_at' => now(),
        ]);
    }
}