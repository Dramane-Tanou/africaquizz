<?php
namespace App\Services;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Support\Facades\DB;

class LeagueService
{
    // Crée les ligues de la semaine en cours et assigne les joueurs (5 par tier)
    public function createCurrentWeekLeagues(): array
    {
        $weekKey = now()->format('Y-\WW');
        if (League::where('week_key', $weekKey)->exists()) {
            return ['status' => 'already_exists', 'week_key' => $weekKey];
        }

        $startsAt = now()->startOfWeek();
        $endsAt = now()->endOfWeek();

        // Pour chaque tier, créer les ligues
        $created = [];
        foreach (League::TIERS as $tier) {
            // Récupérer les joueurs éligibles (3 parties min)
            $eligibleUserIds = DB::table('xp_transactions')
                ->where('reason', 'quiz_completion')
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) >= 3')
                ->pluck('user_id')
                ->toArray();

            // Grouper par 50
            $chunks = array_chunk($eligibleUserIds, 50);
            foreach ($chunks as $chunk) {
                $league = League::create([
                    'week_key' => $weekKey,
                    'tier' => $tier,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ]);
                foreach ($chunk as $uid) {
                    LeagueMember::create(['league_id' => $league->id, 'user_id' => $uid, 'xp_earned' => 0]);
                }
                $created[] = ['tier' => $tier, 'members' => count($chunk), 'league_id' => $league->id];
            }
        }

        return ['status' => 'created', 'week_key' => $weekKey, 'leagues' => $created];
    }

    // Met à jour l'XP des membres à partir des transactions de la semaine
    public function refreshCurrentWeekXp(): int
    {
        $weekKey = now()->format('Y-\WW');
        $leagues = League::where('week_key', $weekKey)->get();
        $updated = 0;

        foreach ($leagues as $league) {
            $members = LeagueMember::where('league_id', $league->id)->get();
            foreach ($members as $m) {
                $xp = XpTransaction::where('user_id', $m->user_id)
                    ->where('reason', 'quiz_completion')
                    ->where('created_at', '>=', $league->starts_at)
                    ->where('created_at', '<=', $league->ends_at)
                    ->sum('amount') ?? 0;
                $m->update(['xp_earned' => $xp]);
                $updated++;
            }
        }

        return $updated;
    }

    // Clôture la semaine : calcule les rangs, promotions et relégations
    public function closeCurrentWeek(): array
    {
        $weekKey = now()->format('Y-\WW');
        $leagues = League::where('week_key', $weekKey)->get();
        $promoted = 0; $relegated = 0;

        foreach ($leagues as $league) {
            $members = LeagueMember::where('league_id', $league->id)
                ->orderByDesc('xp_earned')
                ->get();
            
            foreach ($members as $i => $m) {
                $rank = $i + 1;
                $isPromoted = $rank <= League::PROMOTION_COUNT && $league->tier !== 'diamant';
                $isRelegated = $rank > (count($members) - League::RELEGATION_COUNT) && $league->tier !== 'bronze';
                
                $m->update(['final_rank' => $rank, 'promoted' => $isPromoted, 'relegated' => $isRelegated]);
                if ($isPromoted) $promoted++;
                if ($isRelegated) $relegated++;
            }
        }

        return ['week_key' => $weekKey, 'promoted' => $promoted, 'relegated' => $relegated];
    }
}