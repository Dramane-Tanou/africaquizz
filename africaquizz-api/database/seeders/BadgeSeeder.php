<?php
namespace Database\Seeders;
use App\Models\Badge;
use Illuminate\Database\Seeder;
class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            ['code' => 'first_quiz', 'name_fr' => 'Premier quiz', 'name_en' => 'First quiz', 'tier' => 'bronze', 'rule_params' => ['parties' => 1]],
            ['code' => 'regular', 'name_fr' => 'Régulier', 'name_en' => 'Regular', 'tier' => 'bronze', 'rule_params' => ['streak_days' => 7]],
            ['code' => 'explorer', 'name_fr' => 'Explorateur de pays', 'name_en' => 'Country Explorer', 'tier' => 'argent', 'rule_params' => ['countries' => 10, 'questions_per_country' => 5]],
            ['code' => 'specialist', 'name_fr' => 'Spécialiste', 'name_en' => 'Specialist', 'tier' => 'argent', 'rule_params' => ['correct_in_category' => 100, 'accuracy' => 0.75]],
            ['code' => 'flawless', 'name_fr' => 'Sans faute', 'name_en' => 'Flawless', 'tier' => 'argent', 'rule_params' => ['consecutive_correct' => 10]],
            ['code' => 'daily_champion', 'name_fr' => 'Défi réussi', 'name_en' => 'Daily Champion', 'tier' => 'or', 'rule_params' => ['daily_correct' => 7]],
            ['code' => 'record_holder', 'name_fr' => 'Record', 'name_en' => 'Record Holder', 'tier' => 'or', 'rule_params' => ['parties' => 5]],
            ['code' => 'ambassador', 'name_fr' => 'Ambassadeur', 'name_en' => 'Ambassador', 'tier' => 'or', 'rule_params' => ['invited_friends' => 3]],
        ];
        foreach ($badges as $b) {
            Badge::create($b);
        }
        $this->command->info('BadgeSeeder : 8 badges créés.');
    }
}