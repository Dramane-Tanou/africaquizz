<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuizSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Données des incitations de l'accueil : série, objectif du jour et place dans le pays.
 * Tout est calculé à partir de l'activité réelle du joueur (pas de faux compteurs).
 */
class EngagementController extends Controller
{
    public const DAILY_GOAL = 3;

    public function show(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile?->load('country');
        $timezone = $profile?->timezone ?: 'UTC';
        $today = now()->setTimezone($timezone);

        $streak = $user->streak;
        $playedToday = $streak?->last_active_date?->toDateString() === $today->toDateString();

        $gamesToday = QuizSession::where('user_id', $user->id)
            ->where('status', 'finished')
            ->where('updated_at', '>=', $today->copy()->startOfDay()->utc())
            ->count();

        return response()->json([
            'data' => [
                'streak' => [
                    'current' => $streak?->current_streak ?? 0,
                    'longest' => $streak?->longest_streak ?? 0,
                    'freezes' => $streak?->freezes_available ?? 0,
                    'played_today' => $playedToday,
                ],
                'daily_goal' => [
                    'target' => self::DAILY_GOAL,
                    'done' => min($gamesToday, self::DAILY_GOAL),
                    'games_today' => $gamesToday,
                ],
                'country' => $profile?->country ? $this->countryStanding($user->id, $profile->country) : null,
            ],
        ]);
    }

    private function countryStanding(string $userId, $country): array
    {
        $weekly = DB::table('xp_transactions as xt')
            ->join('profiles as p', 'p.user_id', '=', 'xt.user_id')
            ->where('p.country_id', $country->id)
            ->where('xt.reason', 'quiz_completion')
            ->where('xt.created_at', '>=', now()->subDays(7))
            ->groupBy('xt.user_id', 'p.pseudo')
            ->orderByDesc('xp')
            ->get(['xt.user_id', 'p.pseudo', DB::raw('SUM(xt.amount) as xp')]);

        $myIndex = $weekly->search(fn ($row) => $row->user_id === $userId);
        $myXp = $myIndex === false ? 0 : (int) $weekly[$myIndex]->xp;
        $ahead = $myIndex === false ? $weekly->last() : ($myIndex > 0 ? $weekly[$myIndex - 1] : null);

        return [
            'iso2' => $country->iso2,
            'question_count' => Question::where('status', 'PUBLIEE')
                ->whereHas('countries', fn ($q) => $q->where('countries.id', $country->id))
                ->count(),
            'week' => [
                'rank' => $myIndex === false ? null : $myIndex + 1,
                'players' => $weekly->count(),
                'xp' => $myXp,
                'next' => $ahead ? [
                    'pseudo' => $ahead->pseudo,
                    'gap_xp' => max(1, (int) $ahead->xp - $myXp + 1),
                ] : null,
            ],
        ];
    }
}
