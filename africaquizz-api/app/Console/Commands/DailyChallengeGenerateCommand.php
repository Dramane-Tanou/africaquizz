<?php
namespace App\Console\Commands;

use App\Models\DailyChallenge;
use App\Models\DailyChallengeQuestion;
use App\Models\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DailyChallengeGenerateCommand extends Command
{
    public const QUESTIONS = 10;
    // Une question ne revient pas dans le défi du jour avant ce délai
    public const NO_REPEAT_DAYS = 60;

    protected $signature = 'daily:generate {--date= : Date du défi (AAAA-MM-JJ), aujourd\'hui par défaut}';
    protected $description = 'Crée le défi du jour : 10 questions publiées, variées, non utilisées récemment';

    public function handle()
    {
        $date = $this->option('date') ?: now()->toDateString();

        if (DailyChallenge::whereDate('challenge_date', $date)->exists()) {
            $this->info("Défi du $date déjà existant.");
            return self::SUCCESS;
        }

        $recent = DailyChallengeQuestion::whereHas('challenge', fn ($q) => $q
            ->whereDate('challenge_date', '>=', now()->parse($date)->subDays(self::NO_REPEAT_DAYS)->toDateString()))
            ->pluck('question_id');

        // Une question par catégorie d'abord, pour un défi varié, puis complément au hasard
        $pool = Question::where('status', 'PUBLIEE')->whereNotIn('id', $recent)->inRandomOrder()->get(['id', 'category_id']);
        if ($pool->count() < self::QUESTIONS) {
            $pool = Question::where('status', 'PUBLIEE')->inRandomOrder()->get(['id', 'category_id']);
        }
        $picked = $pool->unique('category_id')->take(self::QUESTIONS);
        $picked = $picked->concat($pool->diff($picked)->take(self::QUESTIONS - $picked->count()))->shuffle();

        if ($picked->count() < self::QUESTIONS) {
            $this->error('Pas assez de questions publiées pour créer le défi du jour.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($date, $picked) {
            $challenge = DailyChallenge::create([
                'challenge_date' => $date,
                'closes_at' => now()->parse($date)->addDay()->startOfDay(),
            ]);
            foreach ($picked->values() as $i => $question) {
                DailyChallengeQuestion::create([
                    'daily_challenge_id' => $challenge->id,
                    'question_id' => $question->id,
                    'position' => $i + 1,
                ]);
            }
        });

        $this->info("Défi du $date créé avec " . $picked->count() . ' questions.');
        return self::SUCCESS;
    }
}
