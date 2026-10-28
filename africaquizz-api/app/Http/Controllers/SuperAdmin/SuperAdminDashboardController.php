<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminDashboardController extends Controller
{
    public function __construct()
    {
        if (!request()->user('admin')?->hasRole('super_admin') && !request()->user('admin')?->hasRole('admin')) {
            abort(403, 'Accès SuperAdmin requis');
        }
    }

    // KPIs globaux
    public function overview(Request $request)
    {
        $now = now();

        return response()->json([
            'data' => [
                'users' => [
                    'total' => DB::table('users')->where('is_anonymized', false)->count(),
                    'verified' => DB::table('users')->whereNotNull('email_verified_at')->count(),
                    'banned' => DB::table('users')->where('is_banned', true)->count(),
                    'new_today' => DB::table('users')->whereDate('created_at', $now->toDateString())->count(),
                    'new_7d' => DB::table('users')->where('created_at', '>=', $now->subDays(7))->count(),
                    'active_7d' => DB::table('users')->where('updated_at', '>=', $now->subDays(7))->count(),
                ],
                'questions' => [
                    'total' => DB::table('questions')->count(),
                    'by_status' => DB::table('questions')
                        ->select('status', DB::raw('count(*) as count'))
                        ->groupBy('status')
                        ->pluck('count', 'status'),
                    'by_difficulty' => DB::table('questions')
                        ->select('difficulty', DB::raw('count(*) as count'))
                        ->groupBy('difficulty')
                        ->pluck('count', 'difficulty'),
                    'pending_review' => DB::table('questions')->where('status', 'A_VERIFIER')->count(),
                    'flagged' => DB::table('questions')->where('status', 'A_REVISER')->count(),
                ],
                'content' => [
                    'categories' => DB::table('categories')->count(),
                    'countries' => DB::table('countries')->count(),
                    'sources' => DB::table('question_sources')->count(),
                ],
                'activity' => [
                    'quiz_sessions_total' => DB::table('quiz_sessions')->count(),
                    'quiz_sessions_today' => DB::table('quiz_sessions')->whereDate('created_at', $now->toDateString())->count(),
                    'quiz_answers_total' => DB::table('quiz_answers')->count(),
                    'daily_challenges_active' => DB::table('daily_challenges')->where('challenge_date', $now->toDateString())->count(),
                ],
                'moderation' => [
                    'reports_pending' => DB::table('question_reports')->where('status', 'pending')->count(),
                    'reports_resolved' => DB::table('question_reports')->where('status', 'resolved')->count(),
                    'reports_dismissed' => DB::table('question_reports')->where('status', 'dismissed')->count(),
                ],
                'engagement' => [
                    'total_xp_granted' => DB::table('xp_transactions')->sum('amount') ?? 0,
                    'badges_awarded' => DB::table('user_badges')->count(),
                    'streaks_active' => DB::table('streaks')->where('current_streak', '>', 0)->count(),
                ],
                'system' => [
                    'super_admins' => DB::table('admin_users')
                        ->join('roles', 'roles.id', '=', 'admin_users.role_id')
                        ->where('roles.name', 'super_admin')->count(),
                    'admins_total' => DB::table('admin_users')->count(),
                    'audit_logs_total' => DB::table('audit_logs')->count(),
                    'audit_logs_today' => DB::table('audit_logs')->whereDate('created_at', $now->toDateString())->count(),
                ],
                'generated_at' => $now->toIso8601String(),
            ]
        ]);
    }

    // Statistiques temps réel (30 derniers jours)
    public function realtime(Request $request)
    {
        $days = (int) ($request->input('days', 30));
        $since = now()->subDays($days);

        // Utilisateurs inscrits par jour
        $usersByDay = DB::table('users')
            ->where('created_at', '>=', $since)
            ->select(DB::raw("DATE(created_at) as date, COUNT(*) as count"))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Sessions quiz par jour
        $sessionsByDay = DB::table('quiz_sessions')
            ->where('created_at', '>=', $since)
            ->select(DB::raw("DATE(created_at) as date, COUNT(*) as count, SUM(score) as total_score"))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Réponses par jour
        $answersByDay = DB::table('quiz_answers')
            ->where('created_at', '>=', $since)
            ->select(DB::raw("DATE(created_at) as date, COUNT(*) as count, SUM(CASE WHEN is_correct THEN 1 ELSE 0 END) as correct_count"))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'data' => [
                'users_by_day' => $usersByDay,
                'sessions_by_day' => $sessionsByDay,
                'answers_by_day' => $answersByDay,
                'period_days' => $days,
            ]
        ]);
    }

    // Top joueurs par XP
    public function topPlayers(Request $request)
    {
        $limit = (int) ($request->input('limit', 20));

        $players = DB::table('profiles')
            ->where('total_xp', '>', 0)
            ->orderBy('total_xp', 'desc')
            ->limit($limit)
            ->select('user_id', 'pseudo', 'total_xp', 'level', 'rank')
            ->get();

        return response()->json(['data' => $players]);
    }

    // Top questions les plus signalées
    public function topReportedQuestions(Request $request)
    {
        $limit = (int) ($request->input('limit', 20));

        $questions = DB::table('questions')
            ->where('pending_reports_count', '>', 0)
            ->orderBy('pending_reports_count', 'desc')
            ->limit($limit)
            ->select('id', 'external_id', 'status', 'pending_reports_count', 'last_reported_at')
            ->get();

        return response()->json(['data' => $questions]);
    }

    // Activité récente (audit)
    public function recentActivity(Request $request)
    {
        $limit = (int) ($request->input('limit', 50));

        $logs = DB::table('audit_logs')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $logs]);
    }
}