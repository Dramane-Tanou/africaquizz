<?php
namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueMember;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function current(Request $request)
    {
        $user = $request->user();
        $weekKey = now()->format('Y-\WW');
        
        $member = LeagueMember::where('user_id', $user->id)
            ->whereHas('league', fn($q) => $q->where('week_key', $weekKey))
            ->first();

        if (!$member) {
            return response()->json(['data' => ['in_league' => false, 'message' => 'Vous n\'êtes pas encore dans une ligue cette semaine.']]);
        }

        $league = $member->league;
        $members = LeagueMember::where('league_id', $league->id)
            ->orderByDesc('xp_earned')
            ->with('user.profile')
            ->get()
            ->map(function($m, $i) {
                return [
                    'rank' => $i + 1,
                    'pseudo' => $m->user->profile?->pseudo ?? 'Anonyme',
                    'xp_earned' => $m->xp_earned,
                    'is_me' => $m->user_id === auth()->id(),
                ];
            });

        return response()->json([
            'data' => [
                'in_league' => true,
                'week_key' => $weekKey,
                'tier' => $league->tier,
                'my_rank' => $members->firstWhere('is_me', true)['rank'] ?? null,
                'members' => $members,
                'promotion_zone' => \App\Models\League::PROMOTION_COUNT,
                'relegation_zone' => \App\Models\League::RELEGATION_COUNT,
            ]
        ]);
    }

    public function history(Request $request)
    {
        $history = LeagueMember::where('user_id', $request->user()->id)
            ->with('league')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
        return response()->json(['data' => $history]);
    }
}