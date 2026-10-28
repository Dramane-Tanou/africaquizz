<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'nullable|in:daily,global,country,friends',
            'period' => 'nullable|in:day,week,month,all',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $scope = $validated['scope'] ?? 'global';
        $period = $validated['period'] ?? 'all';
        $limit = $validated['limit'] ?? 50;
        $user = $request->user('sanctum');

        // Scope "friends" : pas encore disponible (v1.1)
        if ($scope === 'friends') {
            return response()->json([
                'data' => ['entries' => [], 'me' => null],
                'meta' => [
                    'scope' => $scope,
                    'period' => $period,
                    'message' => 'Le scope "friends" sera disponible en v1.1',
                ]
            ]);
        }

        // Récupérer les IDs éligibles (min 3 parties complétées)
        $eligibleIds = DB::table('xp_transactions')
            ->where('reason', 'quiz_completion')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 3')
            ->pluck('user_id')
            ->toArray();

        $entries = $this->buildEntries($scope, $period, $eligibleIds, $limit, $user);
        $me = $user ? $this->computeMyRank($scope, $period, $user) : null;

        return response()->json([
            'data' => [
                'entries' => $entries,
                'me' => $me,
            ],
            'meta' => [
                'scope' => $scope,
                'period' => $period,
                'period_key' => $this->periodKey($period),
                'updated_at' => now()->toIso8601String(),
                'eligible_players' => count($eligibleIds),
            ]
        ]);
    }

    private function buildEntries(string $scope, string $period, array $eligibleIds, int $limit, $user): array
    {
        if (empty($eligibleIds)) {
            return [];
        }

        // === SCOPE DAILY ===
        if ($scope === 'daily') {
            $today = now()->toDateString();
            $rows = DB::table('user_daily_results as udr')
                ->join('profiles as p', 'p.user_id', '=', 'udr.user_id')
                ->leftJoin('countries as c', 'c.id', '=', 'p.country_id')
                ->where('udr.challenge_date', $today)
                ->whereIn('udr.user_id', $eligibleIds)
                ->orderByDesc('udr.score')
                ->limit($limit)
                ->get([
                    'udr.user_id', 'p.pseudo', 'p.avatar', 'p.rank as user_rank',
                    'c.iso2 as country', 'udr.score as value', 'udr.correct_answers'
                ]);

            return $rows->values()->map(function($r, $i) {
                return [
                    'rank' => $i + 1,
                    'value' => (int) $r->value,
                    'user' => [
                        'pseudo' => $r->pseudo,
                        'country' => $r->country,
                        'avatar' => $r->avatar,
                        'rank' => $r->user_rank,
                    ],
                    'detail' => ['correct_answers' => $r->correct_answers],
                ];
            })->all();
        }

        // === SCOPE GLOBAL / COUNTRY ===
        $countryFilter = null;
        if ($scope === 'country' && $user && $user->profile && $user->profile->country_id) {
            $countryFilter = $user->profile->country_id;
        } elseif ($scope === 'country') {
            // Pas de pays renseigné, retourner vide
            return [];
        }

        if ($period === 'all') {
            // All-time : on utilise profiles.total_xp (déjà agrégé)
            $q = DB::table('profiles as p')
                ->leftJoin('countries as c', 'c.id', '=', 'p.country_id')
                ->whereIn('p.user_id', $eligibleIds)
                ->where('p.total_xp', '>', 0)
                ->orderByDesc('p.total_xp');

            if ($countryFilter) {
                $q->where('p.country_id', $countryFilter);
            }

            $rows = $q->limit($limit)->get([
                'p.user_id', 'p.pseudo', 'p.avatar', 'p.rank as user_rank',
                'c.iso2 as country', 'p.total_xp as value'
            ]);
        } else {
            // Période : on agrège xp_transactions
            $days = match($period) {
                'day' => 1,
                'week' => 7,
                'month' => 30,
            };

            $q = DB::table('xp_transactions as xt')
                ->join('profiles as p', 'p.user_id', '=', 'xt.user_id')
                ->leftJoin('countries as c', 'c.id', '=', 'p.country_id')
                ->whereIn('xt.user_id', $eligibleIds)
                ->where('xt.reason', 'quiz_completion')
                ->where('xt.created_at', '>=', now()->subDays($days))
                ->groupBy('p.user_id', 'p.pseudo', 'p.avatar', 'p.rank', 'c.iso2')
                ->select([
                    'p.user_id', 'p.pseudo', 'p.avatar', 'p.rank as user_rank',
                    'c.iso2 as country',
                    DB::raw('SUM(xt.amount) as value'),
                ])
                ->orderByDesc('value');

            if ($countryFilter) {
                $q->where('p.country_id', $countryFilter);
            }

            $rows = $q->limit($limit)->get();
        }

        return $rows->values()->map(function($r, $i) {
            return [
                'rank' => $i + 1,
                'value' => (int) $r->value,
                'user' => [
                    'pseudo' => $r->pseudo,
                    'country' => $r->country,
                    'avatar' => $r->avatar,
                    'rank' => $r->user_rank,
                ],
            ];
        })->all();
    }

    private function computeMyRank(string $scope, string $period, $user): ?array
    {
        // SCOPE DAILY
        if ($scope === 'daily') {
            $today = now()->toDateString();
            $myRow = DB::table('user_daily_results')
                ->where('user_id', $user->id)
                ->where('challenge_date', $today)
                ->first();
            if (!$myRow) return null;

            $myRank = 1 + DB::table('user_daily_results')
                ->where('challenge_date', $today)
                ->where('score', '>', $myRow->score)
                ->count();

            return ['rank' => $myRank, 'value' => (int) $myRow->score];
        }

        $profile = $user->profile;
        if (!$profile) return null;

        $countryFilter = ($scope === 'country' && $profile->country_id) ? $profile->country_id : null;
        if ($scope === 'country' && !$countryFilter) return null;

        if ($period === 'all') {
            $myValue = (int) $profile->total_xp;
            $q = DB::table('profiles')->where('total_xp', '>', $myValue);
            if ($countryFilter) {
                $q->where('country_id', $countryFilter);
            }
            $myRank = 1 + $q->count();
            return ['rank' => $myRank, 'value' => $myValue];
        }

        $days = match($period) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
        };

        $myValue = (int) DB::table('xp_transactions')
            ->where('user_id', $user->id)
            ->where('reason', 'quiz_completion')
            ->where('created_at', '>=', now()->subDays($days))
            ->sum('amount');

        // Compter combien de joueurs ont strictement plus que moi
        $subQuery = DB::table('xp_transactions')
            ->select('user_id', DB::raw('SUM(amount) as total'))
            ->where('reason', 'quiz_completion')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('user_id')
            ->havingRaw('SUM(amount) > ?', [$myValue]);

        if ($countryFilter) {
            $subQuery->whereIn('user_id', function($q) use ($countryFilter) {
                $q->select('user_id')->from('profiles')->where('country_id', $countryFilter);
            });
        }

        $myRank = 1 + $subQuery->get()->count();

        return ['rank' => $myRank, 'value' => $myValue];
    }

    private function periodKey(string $period): string
    {
        return match($period) {
            'day' => now()->format('Y-m-d'),
            'week' => now()->format('Y-\WW'),
            'month' => now()->format('Y-m'),
            'all' => 'all-time',
        };
    }
}