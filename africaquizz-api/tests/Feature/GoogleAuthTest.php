<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT_ID = 'test-client.apps.googleusercontent.com';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => self::CLIENT_ID]);
    }

    private function fakeGoogle(array $overrides = [], int $status = 200): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(array_merge([
                'iss' => 'https://accounts.google.com',
                'aud' => self::CLIENT_ID,
                'sub' => '1234567890',
                'email' => 'Awa@Gmail.com',
                'email_verified' => 'true',
                'name' => 'Awa Ouedraogo',
                'exp' => (string) now()->addHour()->timestamp,
            ], $overrides), $status),
        ]);
    }

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

    public function test_new_google_user_is_asked_for_age_and_country(): void
    {
        $this->fakeGoogle();

        $this->postJson('/api/v1/auth/google', ['credential' => 'id-token'])
            ->assertOk()
            ->assertJsonPath('data.requires_profile', true)
            ->assertJsonPath('data.google.email', 'awa@gmail.com')
            ->assertJsonMissingPath('data.token');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_new_google_user_gets_a_verified_account(): void
    {
        $this->fakeGoogle();
        $country = $this->burkina();

        $this->postJson('/api/v1/auth/google', [
            'credential' => 'id-token',
            'date_of_birth' => '2000-05-12',
            'country_id' => $country->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'awa@gmail.com')
            ->assertJsonPath('data.user.has_password', false)
            ->assertJsonPath('data.user.profile.country.iso2', 'BF')
            ->assertJsonPath('data.requires_email_verification', false)
            ->assertJsonStructure(['data' => ['token']]);

        $user = User::firstWhere('email', 'awa@gmail.com');
        $this->assertSame('1234567890', $user->google_id);
        $this->assertTrue($user->isEmailVerified());
        $this->assertNull($user->password);
    }

    public function test_google_sign_up_enforces_minimum_age(): void
    {
        $this->fakeGoogle();
        $country = $this->burkina();

        $this->postJson('/api/v1/auth/google', [
            'credential' => 'id-token',
            'date_of_birth' => now()->subYears(12)->toDateString(),
            'country_id' => $country->id,
        ])->assertForbidden()->assertJsonPath('error_code', 'AGE_TOO_YOUNG');
    }

    public function test_google_sign_up_requires_parental_email_between_13_and_15(): void
    {
        $this->fakeGoogle();
        $country = $this->burkina();

        $this->postJson('/api/v1/auth/google', [
            'credential' => 'id-token',
            'date_of_birth' => now()->subYears(14)->toDateString(),
            'country_id' => $country->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('parental_email');
    }

    public function test_existing_email_account_is_linked_and_signed_in(): void
    {
        $this->fakeGoogle();
        $user = User::create(['name' => 'Awa', 'email' => 'awa@gmail.com', 'password' => Hash::make('motdepasse123')]);

        $this->postJson('/api/v1/auth/google', ['credential' => 'id-token'])
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['token']]);

        $user->refresh();
        $this->assertSame('1234567890', $user->google_id);
        $this->assertTrue($user->isEmailVerified());
    }

    public function test_linking_an_unverified_account_drops_its_password_and_sessions(): void
    {
        $this->fakeGoogle();
        // Compte créé par un tiers avec l'adresse du joueur, jamais vérifié
        $squatter = User::create(['name' => 'Intrus', 'email' => 'awa@gmail.com', 'password' => Hash::make('motdepasse123')]);
        $squatter->createToken('auth_token');

        $this->postJson('/api/v1/auth/google', ['credential' => 'id-token'])
            ->assertOk()
            ->assertJsonPath('data.user.has_password', false);

        $squatter->refresh();
        $this->assertNull($squatter->password);
        $this->assertSame(1, $squatter->tokens()->count()); // seul le jeton qui vient d'être émis
    }

    public function test_linking_a_verified_account_keeps_its_password(): void
    {
        $this->fakeGoogle();
        $user = User::create(['name' => 'Awa', 'email' => 'awa@gmail.com', 'password' => Hash::make('motdepasse123'), 'email_verified_at' => now()]);

        $this->postJson('/api/v1/auth/google', ['credential' => 'id-token'])
            ->assertOk()
            ->assertJsonPath('data.user.has_password', true);

        $this->assertTrue(Hash::check('motdepasse123', $user->fresh()->password));
    }

    public function test_banned_account_cannot_sign_in_with_google(): void
    {
        $this->fakeGoogle();
        User::create(['name' => 'Awa', 'email' => 'awa@gmail.com', 'password' => null, 'google_id' => '1234567890', 'is_banned' => true]);

        $this->postJson('/api/v1/auth/google', ['credential' => 'id-token'])->assertForbidden();
    }

    public function test_token_for_another_app_is_rejected(): void
    {
        $this->fakeGoogle(['aud' => 'other-app.apps.googleusercontent.com']);

        $this->postJson('/api/v1/auth/google', ['credential' => 'id-token'])
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'GOOGLE_TOKEN_INVALID');
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->fakeGoogle(['error' => 'invalid_token'], 400);

        $this->postJson('/api/v1/auth/google', ['credential' => 'forged'])->assertUnauthorized();
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->fakeGoogle(['email_verified' => 'false']);

        $this->postJson('/api/v1/auth/google', ['credential' => 'id-token'])->assertUnauthorized();
    }

    public function test_google_account_can_be_deleted_without_password(): void
    {
        $user = User::create(['name' => 'Awa', 'email' => 'awa@gmail.com', 'password' => null, 'google_id' => '1234567890']);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/me', ['confirmation' => 'SUPPRIMER'])->assertOk();

        $user->refresh();
        $this->assertTrue($user->is_anonymized);
        $this->assertNull($user->google_id);
    }

    public function test_password_account_still_needs_its_password_to_be_deleted(): void
    {
        $user = User::create(['name' => 'Awa', 'email' => 'awa@gmail.com', 'password' => Hash::make('motdepasse123')]);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/me', ['confirmation' => 'SUPPRIMER'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }
}
