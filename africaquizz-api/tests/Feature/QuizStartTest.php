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
}
