<?php

namespace Tests\Feature;

use App\Models\DailyChallenge;
use App\Models\DailyChallengeQuestion;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyChallengeGenerateTest extends TestCase
{
    use RefreshDatabase;

    private function publishedQuestions(int $count): void
    {
        $this->seed(\Database\Seeders\DemoQuestionsSeeder::class);
        $template = Question::first();
        for ($i = Question::count(); $i < $count; $i++) {
            Question::create([
                'category_id' => $template->category_id,
                'type' => 'mcq',
                'difficulty' => 'decouverte',
                'time_limit_s' => 20,
                'status' => 'PUBLIEE',
                'external_id' => "extra-$i",
            ]);
        }
    }

    public function test_it_creates_one_challenge_of_ten_distinct_questions(): void
    {
        $this->publishedQuestions(30);

        $this->artisan('daily:generate')->assertSuccessful();
        $this->artisan('daily:generate')->assertSuccessful(); // rejouable sans doublon

        $this->assertSame(1, DailyChallenge::count());
        $ids = DailyChallengeQuestion::pluck('question_id');
        $this->assertCount(10, $ids);
        $this->assertCount(10, $ids->unique());
    }

    public function test_questions_are_not_repeated_on_the_next_days(): void
    {
        $this->publishedQuestions(30);

        $this->artisan('daily:generate', ['--date' => now()->toDateString()])->assertSuccessful();
        $this->artisan('daily:generate', ['--date' => now()->addDay()->toDateString()])->assertSuccessful();

        $this->assertCount(20, DailyChallengeQuestion::pluck('question_id')->unique());
    }

    public function test_the_api_creates_the_challenge_when_the_scheduler_has_not_run(): void
    {
        $this->publishedQuestions(30);

        $this->getJson('/api/v1/daily-challenge')
            ->assertOk()
            ->assertJsonPath('data.status', 'available');
    }
}
