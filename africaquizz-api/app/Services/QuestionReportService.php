<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuestionReportService
{
    public function submitReport(
        Question $question,
        string $reason,
        ?string $comment,
        ?User $user,
        ?string $guestSessionId
    ): array {
        // Validations
        if (!array_key_exists($reason, QuestionReport::REASONS)) {
            throw new \Exception("Motif invalide : $reason");
        }
        if (!$user && !$guestSessionId) {
            throw new \Exception("Il faut être connecté ou avoir une session invité.");
        }

        // Anti-doublon : 1 signalement par user/guest par question
        $already = QuestionReport::where('question_id', $question->id)
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->when(!$user && $guestSessionId, fn($q) => $q->where('guest_session_id', $guestSessionId))
            ->first();

        if ($already) {
            throw new \Exception("Vous avez déjà signalé cette question.");
        }

        return DB::transaction(function () use ($question, $reason, $comment, $user, $guestSessionId) {
            // Créer le signalement
            $report = QuestionReport::create([
                'question_id' => $question->id,
                'user_id' => $user?->id,
                'guest_session_id' => $guestSessionId,
                'reason' => $reason,
                'comment' => $comment,
                'status' => 'pending',
            ]);

            // Incrémenter le compteur
            $question->increment('pending_reports_count');
            $question->update(['last_reported_at' => now()]);

            // Vérifier le seuil : 3 signalements distincts (par user_id, pas par count total)
            $distinctReporters = QuestionReport::where('question_id', $question->id)
                ->where('status', 'pending')
                ->where(function ($q) {
                    $q->whereNotNull('user_id')->orWhereNotNull('guest_session_id');
                })
                ->select('user_id', 'guest_session_id')
                ->distinct()
                ->get()
                ->count();

            $autoFlagged = false;
            if ($distinctReporters >= QuestionReport::THRESHOLD_AUTO_REVIEW && $question->status === 'PUBLIEE') {
                // Bascule automatique en A_REVISER
                $before = ['status' => $question->status];
                $question->update(['status' => 'A_REVISER']);
                $autoFlagged = true;

                // Audit log
                AuditLog::create([
                    'actor_type' => 'system',
                    'actor_id' => null,
                    'action' => 'question.auto_flagged',
                    'entity_type' => 'question',
                    'entity_id' => $question->id,
                    'before' => $before,
                    'after' => ['status' => 'A_REVISER', 'reason' => '3 signalements distincts'],
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                ]);
            }

            return [
                'report' => $report,
                'pending_count' => $question->fresh()->pending_reports_count,
                'distinct_reporters' => $distinctReporters,
                'auto_flagged' => $autoFlagged,
            ];
        });
    }

    public function resolveReport(
        AdminUser $admin,
        QuestionReport $report,
        string $decision,
        ?string $note,
        bool $grantXpCompensation
    ): array {
        if (!in_array($decision, ['resolved', 'dismissed'])) {
            throw new \Exception("Décision invalide : $decision");
        }
        if ($report->status !== 'pending') {
            throw new \Exception("Ce signalement a déjà été traité.");
        }

        return DB::transaction(function () use ($admin, $report, $decision, $note, $grantXpCompensation) {
            $question = $report->question;
            $before = ['status' => $report->status];

            $report->update([
                'status' => $decision,
                'reviewed_by_admin_id' => $admin->id,
                'reviewed_at' => now(),
                'resolution_note' => $note,
            ]);

            // Décrémenter le compteur de signalements en attente
            if ($question->pending_reports_count > 0) {
                $question->decrement('pending_reports_count');
            }

            // Si l'erreur est confirmée (resolved) et la question était PUBLIEE
            // on la passe en A_REVISER pour correction
            if ($decision === 'resolved' && $question->status === 'PUBLIEE') {
                $question->update(['status' => 'A_REVISER']);
            }

            // Compensation XP si demandée
            $compensationInfo = null;
            if ($grantXpCompensation && $decision === 'resolved') {
                $compensationInfo = $this->grantXpCompensation($report, $admin);
            }

            // Audit log
            AuditLog::create([
                'actor_type' => 'admin_user',
                'actor_id' => $admin->id,
                'action' => 'question.report.' . $decision,
                'entity_type' => 'question_report',
                'entity_id' => $report->id,
                'before' => $before,
                'after' => ['status' => $decision, 'note' => $note, 'xp_compensation' => $compensationInfo],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return ['report' => $report->fresh(), 'compensation' => $compensationInfo];
        });
    }

    private function grantXpCompensation(QuestionReport $report, AdminUser $admin): array
    {
        // Compensation : 150 XP par question signalée (montant forfaitaire)
        $amount = 150;

        // Trouver tous les joueurs qui ont répondu à cette question (via quiz_answers)
        $affectedUserIds = DB::table('quiz_answers')
            ->join('quiz_sessions', 'quiz_sessions.id', '=', 'quiz_answers.quiz_session_id')
            ->where('quiz_answers.question_id', $report->question_id)
            ->whereNotNull('quiz_sessions.user_id')
            ->where('quiz_answers.is_correct', false) // seulement ceux qui ont eu faux
            ->distinct()
            ->pluck('quiz_sessions.user_id')
            ->toArray();

        if (empty($affectedUserIds)) {
            return ['granted' => false, 'count' => 0, 'reason' => 'Aucun joueur affecté trouvé'];
        }

        // Créditer 150 XP à chaque joueur affecté via xp_transactions
        $now = now();
        $transactions = [];
        foreach ($affectedUserIds as $userId) {
            $transactions[] = [
                'user_id' => $userId,
                'quiz_session_id' => null,
                'amount' => $amount,
                'reason' => 'correction',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('xp_transactions')->insert($transactions);

        // Mettre à jour les profils (total_xp + level + rank)
        foreach ($affectedUserIds as $userId) {
            $profile = DB::table('profiles')->where('user_id', $userId)->first();
            if ($profile) {
                $newTotal = $profile->total_xp + $amount;
                $newLevel = (int) floor(sqrt($newTotal / 120)) + 1;
                $newRank = $this->computeRank($newTotal);
                DB::table('profiles')->where('user_id', $userId)->update([
                    'total_xp' => $newTotal,
                    'level' => $newLevel,
                    'rank' => $newRank,
                ]);
            }
        }

        $report->update([
            'xp_compensation_granted' => true,
            'xp_compensation_amount' => $amount,
        ]);

        return ['granted' => true, 'count' => count($affectedUserIds), 'per_user' => $amount];
    }

    private function computeRank(int $xp): string
    {
        return match(true) {
            $xp >= 150000 => 'maitre_du_savoir',
            $xp >= 50000 => 'erudit',
            $xp >= 15000 => 'initie',
            $xp >= 3000 => 'explorateur',
            default => 'curieux',
        };
    }
}