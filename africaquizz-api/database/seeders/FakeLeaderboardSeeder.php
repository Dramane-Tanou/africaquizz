<?php
namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile;
use App\Models\Country;
use App\Models\XpTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FakeLeaderboardSeeder extends Seeder
{
    public function run(): void
    {
        $bf = Country::where('iso2', 'BF')->first();
        $sn = Country::where('iso2', 'SN')->first();
        $ci = Country::where('iso2', 'CI')->first();

        $players = [
            ['name' => 'Fatou Diallo', 'pseudo' => 'fatou_d', 'email' => 'fatou@test.com', 'xp_total' => 4500, 'country_id' => $sn?->id, 'parts' => 12],
            ['name' => 'Kwame Mensah', 'pseudo' => 'kwame_m', 'email' => 'kwame@test.com', 'xp_total' => 3200, 'country_id' => null, 'parts' => 8],
            ['name' => 'Aminata Traoré', 'pseudo' => 'amina_t', 'email' => 'amina@test.com', 'xp_total' => 2100, 'country_id' => $bf?->id, 'parts' => 6],
            ['name' => 'Ibrahim Koné', 'pseudo' => 'ibrahim_k', 'email' => 'ibrahim@test.com', 'xp_total' => 1800, 'country_id' => $ci?->id, 'parts' => 5],
            ['name' => 'Ngozi Okonkwo', 'pseudo' => 'ngozi_o', 'email' => 'ngozi@test.com', 'xp_total' => 900, 'country_id' => null, 'parts' => 3],
        ];

        foreach ($players as $p) {
            $user = User::create([
                'name' => $p['name'],
                'email' => $p['email'],
                'password' => Hash::make('password123'),
            ]);

            Profile::create([
                'user_id' => $user->id,
                'pseudo' => $p['pseudo'],
                'avatar' => 'default',
                'country_id' => $p['country_id'],
                'timezone' => 'UTC',
                'locale' => 'fr',
                'total_xp' => $p['xp_total'],
                'level' => (int) floor(sqrt($p['xp_total'] / 120)) + 1,
                'rank' => $p['xp_total'] >= 3000 ? 'explorateur' : 'curieux',
            ]);

            // Créer les transactions pour l'éligibilité
            $perPart = (int) round($p['xp_total'] / $p['parts']);
            for ($i = 0; $i < $p['parts']; $i++) {
                XpTransaction::create([
                    'user_id' => $user->id,
                    'quiz_session_id' => null,
                    'amount' => $perPart,
                    'reason' => 'quiz_completion',
                    'created_at' => now()->subDays($p['parts'] - $i),
                ]);
            }

            $this->command->info("Joueur créé : {$p['pseudo']} ({$p['xp_total']} XP)");
        }

        $this->command->info('FakeLeaderboardSeeder terminé : 5 joueurs prêts pour les classements.');
    }
}