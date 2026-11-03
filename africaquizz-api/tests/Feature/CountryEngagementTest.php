<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Question;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CountryEngagementTest extends TestCase
{
    use RefreshDatabase;

    private function burkina(): Country
    {
        $region = Region::create(['name' => "Afrique de l'Ouest", 'slug' => 'afrique-de-louest']);

        return Country::create([
            'region_id' => $region->id,
            'iso2' => 'BF',
            'iso3' => 'BFA',
            'slug' => 'burkina-faso',
            'status' => 'member',
        ]);
    }

    private function registration(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Awa',
            'email' => 'awa@example.com',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'date_of_birth' => '2000-05-12',
            'locale' => 'fr',
        ], $overrides);
    }

    public function test_registration_requires_a_country(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registration())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('country_id');
    }

    public function test_registration_stores_the_chosen_country(): void
    {
        $country = $this->burkina();

        $this->postJson('/api/v1/auth/register', $this->registration(['country_id' => $country->id]))
            ->assertCreated()
            ->assertJsonPath('data.user.profile.country_id', $country->id)
            ->assertJsonPath('data.user.profile.country.iso2', 'BF');
    }

    public function test_country_directory_flags_african_countries(): void
    {
        $this->burkina();

        $this->getJson('/api/v1/countries')
            ->assertOk()
            ->assertJsonPath('data.0.iso2', 'BF')
            ->assertJsonPath('data.0.is_african', true);
    }

    public function test_country_quiz_only_serves_questions_about_that_country(): void
    {
        $country = $this->burkina();
        $this->seed(\Database\Seeders\DemoQuestionsSeeder::class);
        $tagged = Question::orderBy('external_id')->get();
        $tagged->each(fn (Question $question) => $question->countries()->attach($country->id));
        $other = Question::create([
            'category_id' => $tagged->first()->category_id,
            'type' => 'mcq',
            'difficulty' => 'decouverte',
            'time_limit_s' => 20,
            'status' => 'PUBLIEE',
            'external_id' => 'other-country',
        ]);

        $response = $this->postJson('/api/v1/quiz/sessions', ['mode' => 'classic', 'locale' => 'fr', 'country' => 'BF'])
            ->assertCreated();

        $ids = collect($response->json('data.questions'))->pluck('id');
        $this->assertCount($tagged->count(), $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_country_quiz_needs_enough_questions(): void
    {
        $this->burkina();
        $this->seed(\Database\Seeders\DemoQuestionsSeeder::class);

        $this->postJson('/api/v1/quiz/sessions', ['mode' => 'classic', 'country' => 'BF'])
            ->assertNotFound();
    }

    public function test_engagement_reports_streak_goal_and_country(): void
    {
        $country = $this->burkina();
        $user = User::factory()->create();
        $user->profile()->create(['pseudo' => 'awa', 'country_id' => $country->id]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me/engagement')
            ->assertOk()
            ->assertJsonPath('data.streak.current', 0)
            ->assertJsonPath('data.streak.played_today', false)
            ->assertJsonPath('data.daily_goal.target', 3)
            ->assertJsonPath('data.daily_goal.done', 0)
            ->assertJsonPath('data.country.iso2', 'BF')
            ->assertJsonPath('data.country.week.rank', null);
    }
}
