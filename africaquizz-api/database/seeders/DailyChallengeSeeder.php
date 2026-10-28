<?php
namespace Database\Seeders;
use App\Models\DailyChallenge;
use App\Models\DailyChallengeQuestion;
use App\Models\Question;
use Illuminate\Database\Seeder;
class DailyChallengeSeeder extends Seeder
{
    public function run(): void
    {
        $today = now()->toDateString();
        $existing = DailyChallenge::where('challenge_date', $today)->first();
        if ($existing) {
            $this->command->info('Défi du jour déjà existant.');
            return;
        }

        $challenge = DailyChallenge::create([
            'challenge_date' => $today,
            'closes_at' => now()->addDay()->startOfDay()->addHours(8),
        ]);

        $questions = Question::where('status', 'PUBLIEE')->limit(10)->get();
        foreach ($questions as $i => $q) {
            DailyChallengeQuestion::create([
                'daily_challenge_id' => $challenge->id,
                'question_id' => $q->id,
                'position' => $i + 1,
            ]);
        }
        $this->command->info("Défi du jour créé avec " . $questions->count() . " questions.");
    }
}