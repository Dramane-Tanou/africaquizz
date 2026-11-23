<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizStartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_start_a_quiz_with_local_demo_questions(): void
    {
        $this->seed(\Database\Seeders\DemoQuestionsSeeder::class);

        $response = $this->postJson('/api/v1/quiz/sessions', [
            'mode' => 'classic',
            'locale' => 'fr',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.mode', 'classic');

        $questions = $response->json('data.questions');
        $this->assertCount(5, $questions);
        $this->assertNotEmpty($questions[0]['text']);
        $this->assertCount(4, $questions[0]['options']);
    }

    public function test_quiz_start_rejects_an_unsupported_mode(): void
    {
        $this->postJson('/api/v1/quiz/sessions', [
            'mode' => 'unknown',
            'locale' => 'fr',
        ])->assertUnprocessable();
    }

    public function test_classic_mode_returns_exactly_10_questions_when_enough_available(): void
    {
        // Crée 15 questions publiées (assez pour dépasser la limite de 10)
        $category = \App\Models\Category::firstOrCreate(['slug' => 'test-limit'], ['name_fr' => 'Test', 'name_en' => 'Test']);
        for ($i = 1; $i <= 15; $i++) {
            $q = \App\Models\Question::create([
                'external_id' => "test-limit-$i",
                'category_id' => $category->id,
                'type' => 'mcq',
                'difficulty' => 'decouverte',
                'status' => 'PUBLIEE',
                'time_limit_s' => 20,
            ]);
            \App\Models\QuestionTranslation::create([
                'question_id' => $q->id, 'locale' => 'fr',
                'text' => "Question limite $i", 'explanation' => '', 'key_takeaway' => '',
            ]);
            \App\Models\QuestionTranslation::create([
                'question_id' => $q->id, 'locale' => 'en',
                'text' => "Limit question $i", 'explanation' => '', 'key_takeaway' => '',
            ]);
            foreach (['A','B','C','D'] as $key) {
                $opt = \App\Models\QuestionOption::create([
                    'question_id' => $q->id, 'option_key' => $key, 'is_correct' => ($key === 'A'),
                ]);
                \App\Models\OptionTranslation::create(['option_id' => $opt->id, 'locale' => 'fr', 'text' => "Option $key"]);
                \App\Models\OptionTranslation::create(['option_id' => $opt->id, 'locale' => 'en', 'text' => "Option $key"]);
            }
        }

        $response = $this->postJson('/api/v1/quiz/sessions', ['mode' => 'classic', 'locale' => 'fr']);
        $response->assertCreated();
        $this->assertCount(10, $response->json('data.questions'), 'classic doit renvoyer exactement 10 questions');
    }

    public function test_rapid_mode_returns_exactly_20_questions(): void
    {
        $category = \App\Models\Category::firstOrCreate(['slug' => 'test-limit'], ['name_fr' => 'Test', 'name_en' => 'Test']);
        for ($i = 1; $i <= 25; $i++) {
            $q = \App\Models\Question::create([
                'external_id' => "test-rapid-$i",
                'category_id' => $category->id,
                'type' => 'mcq',
                'difficulty' => 'decouverte',
                'status' => 'PUBLIEE',
                'time_limit_s' => 20,
            ]);
            \App\Models\QuestionTranslation::create(['question_id' => $q->id, 'locale' => 'fr', 'text' => "Rapid $i", 'explanation' => '', 'key_takeaway' => '']);
            \App\Models\QuestionTranslation::create(['question_id' => $q->id, 'locale' => 'en', 'text' => "Rapid $i", 'explanation' => '', 'key_takeaway' => '']);
            foreach (['A','B','C','D'] as $key) {
                $opt = \App\Models\QuestionOption::create(['question_id' => $q->id, 'option_key' => $key, 'is_correct' => ($key === 'A')]);
                \App\Models\OptionTranslation::create(['option_id' => $opt->id, 'locale' => 'fr', 'text' => "Opt $key"]);
                \App\Models\OptionTranslation::create(['option_id' => $opt->id, 'locale' => 'en', 'text' => "Opt $key"]);
            }
        }

        $response = $this->postJson('/api/v1/quiz/sessions', ['mode' => 'rapid', 'locale' => 'fr']);
        $response->assertCreated();
        $this->assertCount(20, $response->json('data.questions'), 'rapid doit renvoyer exactement 20 questions');
    }
}