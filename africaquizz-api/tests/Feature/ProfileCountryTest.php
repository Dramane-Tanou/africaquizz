<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileCountryTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_directory_contains_the_complete_iso_catalogue(): void
    {
        $this->seed(\Database\Seeders\CountrySeeder::class);

        $this->getJson('/api/v1/countries')
            ->assertOk()
            ->assertJsonCount(250, 'data');
    }

    public function test_authenticated_user_can_set_and_clear_their_country(): void
    {
        $user = User::factory()->create();
        $profile = $user->profile()->create(['pseudo' => 'country-test']);
        $region = Region::create(['name' => 'Test', 'slug' => 'test']);
        $country = Country::create([
            'region_id' => $region->id,
            'iso2' => 'XX',
            'iso3' => 'XXX',
            'slug' => 'test-country',
            'status' => 'member',
        ]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me/profile', ['country_id' => $country->id])
            ->assertOk()
            ->assertJsonPath('data.country_id', $country->id);

        $this->patchJson('/api/v1/me/profile', ['country_id' => null])
            ->assertOk()
            ->assertJsonPath('data.country_id', null);

        $this->assertDatabaseHas('profiles', ['id' => $profile->id, 'country_id' => null]);
    }
}
