<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Question;
use App\Models\QuizAnswer;
use App\Models\QuizSession;
use App\Models\Region;
use App\Models\RevisionQueue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NudgeDataTest extends TestCase
{
    use RefreshDatabase;

    private function country(string $iso2, string $iso3): Country
    {
        $region = Region::firstOrCreate(['slug' => 'afrique-de-louest'], ['name' => "Afrique de l'Ouest"]);

        return Country::create(['region_id' => $region->id, 'iso2' => $iso2, 'iso3' => $iso3, 'slug' => strtolower($iso3), 'status' => 'member']);
    }

    private function answers(Question $question, int $correct, int $wrong, ?User $user = null): void
    {
        foreach ([[true, $correct], [false, $wrong]] as [$isCorrect, $count]) {
            for ($i = 0; $i < $count; $i++) {
                $session = QuizSession::create(['user_id' => $user?->id, 'mode' => 'classic', 'status' => 'finished', 'expires_at' => now()]);
                QuizAnswer::create(['quiz_session_id' => $session->id, 'question_id' => $question->id, 'answer_given' => 'A', 'is_correct' => $isCorrect]);
            }
        }
    }

    /** Démarre une partie et répond à sa première question ; renvoie [réponse, question]. */
    private function answerFirst(): array
    {
        $start = $this->postJson('/api/v1/quiz/sessions', ['mode' => 'classic', 'locale' => 'fr'])->assertCreated();
        $questionId = $start->json('data.questions.0.id');

        $response = $this->withHeader('Idempotency-Key', (string) \Illuminate\Support\Str::uuid())
            ->postJson("/api/v1/quiz/sessions/{$start->json('data.id')}/answers", [
                'question_id' => $questionId,
                'answer' => 'A',
                'client_elapsed_ms' => 3000,
                'locale' => 'fr',
            ])->assertOk();

        return [$response, $questionId];
    }

    public function test_social_proof_is_hidden_until_enough_players_answered(): void
    {
        $this->seed(\Database\Seeders\DemoQuestionsSeeder::class);
        Question::all()->each(fn (Question $q) => $this->answers($q, 5, 5));

        [$response] = $this->answerFirst();
        $this->assertNull($response->json('data.social_proof'));
    }

    public function test_social_proof_reports_the_real_success_rate(): void
    {
        $this->seed(\Database\Seeders\DemoQuestionsSeeder::class);
        Question::all()->each(fn (Question $q) => $this->answers($q, 6, 33));

        [$response] = $this->answerFirst();
        // Le pourcentage inclut la réponse qui vient d'être donnée (40e réponse)
        $expected = (int) round(100 * (6 + ($response->json('data.correct') ? 1 : 0)) / 40);
        $response->assertJsonPath('data.social_proof.answers', 40)
            ->assertJsonPath('data.social_proof.correct_pct', $expected);
    }

    public function test_engagement_reports_discovered_countries_and_due_reviews(): void
    {
        $bf = $this->country('BF', 'BFA');
        $sn = $this->country('SN', 'SEN');
        $this->seed(\Database\Seeders\DemoQuestionsSeeder::class);
        [$q1, $q2, $q3] = Question::orderBy('external_id')->take(3)->get()->all();
        $q1->countries()->attach($bf->id);
        $q2->countries()->attach($sn->id);
        $q3->countries()->attach($sn->id);

        $user = User::factory()->create();
        $user->profile()->create(['pseudo' => 'awa', 'country_id' => $bf->id]);
        $this->answers($q1, 1, 0, $user);   // Burkina trouvé
        $this->answers($q2, 0, 1, $user);   // Sénégal raté : pas encore exploré
        RevisionQueue::create(['user_id' => $user->id, 'question_id' => $q2->id, 'next_review_at' => now()->subHour()]);
        RevisionQueue::create(['user_id' => $user->id, 'question_id' => $q3->id, 'next_review_at' => now()->addDay()]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me/engagement')
            ->assertOk()
            ->assertJsonPath('data.discovery.discovered', 1)
            ->assertJsonPath('data.discovery.available', 2)
            ->assertJsonPath('data.revision_due', 1);
    }

    public function test_a_missed_question_joins_the_player_review_queue(): void
    {
        $this->seed(\Database\Seeders\DemoQuestionsSeeder::class);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $start = $this->postJson('/api/v1/quiz/sessions', ['mode' => 'classic', 'locale' => 'fr'])->assertCreated();
        $question = Question::with('options')->find($start->json('data.questions.0.id'));
        $wrong = $question->options->firstWhere('is_correct', false)->option_key;

        $this->withHeader('Idempotency-Key', (string) \Illuminate\Support\Str::uuid())
            ->postJson("/api/v1/quiz/sessions/{$start->json('data.id')}/answers", [
                'question_id' => $question->id, 'answer' => $wrong, 'client_elapsed_ms' => 3000,
            ])->assertOk()->assertJsonPath('data.correct', false);

        $this->assertDatabaseHas('revision_queue', ['user_id' => $user->id, 'question_id' => $question->id, 'level' => 0]);
    }
}
