<?php

namespace Tests\Feature;

use App\Models\Streak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StreakTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Africa/Ouagadougou';

    private function streak(int $current, string $lastActive, int $freezes = 0): Streak
    {
        $user = User::factory()->create();

        return Streak::create([
            'user_id' => $user->id,
            'current_streak' => $current,
            'longest_streak' => $current,
            'last_active_date' => $lastActive,
            'freezes_available' => $freezes,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-09 10:00', self::TZ));
    }

    public function test_playing_the_next_day_extends_the_streak(): void
    {
        $result = $this->streak(6, '2026-11-08')->updateOnPlay(self::TZ);

        $this->assertSame(7, $result['current']);
        $this->assertTrue($result['is_new_day']);
    }

    public function test_playing_twice_the_same_day_keeps_the_streak(): void
    {
        $result = $this->streak(6, '2026-11-09')->updateOnPlay(self::TZ);

        $this->assertSame(6, $result['current']);
        $this->assertFalse($result['is_new_day']);
    }

    public function test_a_freeze_covers_one_missed_day(): void
    {
        $streak = $this->streak(6, '2026-11-07', 1);
        $result = $streak->updateOnPlay(self::TZ);

        $this->assertSame(7, $result['current']);
        $this->assertSame(1, $result['freezes_used']);
    }

    public function test_the_streak_restarts_after_a_longer_break(): void
    {
        $result = $this->streak(6, '2026-11-05', 1)->updateOnPlay(self::TZ);

        $this->assertSame(1, $result['current']);
    }

    public function test_the_day_follows_the_player_timezone(): void
    {
        // 23 h 30 UTC le 9 = 0 h 30 le 10 à Paris : c'est le lendemain pour ce joueur
        Carbon::setTestNow(Carbon::parse('2026-11-09 23:30', 'UTC'));
        $result = $this->streak(3, '2026-11-09')->updateOnPlay('Europe/Paris');

        $this->assertSame(4, $result['current']);
    }
}
