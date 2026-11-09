<?php

namespace Tests\Unit;

use App\Models\Profile;
use App\Models\User;
use App\Services\WebPushService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class QuietHoursTest extends TestCase
{
    private function user(bool $minor, string $timezone = 'Africa/Ouagadougou'): User
    {
        $user = new User(['is_minor' => $minor]);
        $user->setRelation('profile', new Profile(['timezone' => $timezone]));

        return $user;
    }

    public function test_minors_get_no_notification_at_night_in_their_timezone(): void
    {
        $this->assertTrue(WebPushService::isQuietHours($this->user(true), Carbon::parse('2026-11-09 23:30', 'UTC')));
        $this->assertTrue(WebPushService::isQuietHours($this->user(true), Carbon::parse('2026-11-09 05:59', 'UTC')));
        // 21 h UTC = 22 h à Paris : heures calmes pour un joueur de la diaspora
        $this->assertTrue(WebPushService::isQuietHours($this->user(true, 'Europe/Paris'), Carbon::parse('2026-11-09 21:00', 'UTC')));
    }

    public function test_minors_get_notifications_during_the_day(): void
    {
        $this->assertFalse(WebPushService::isQuietHours($this->user(true), Carbon::parse('2026-11-09 06:00', 'UTC')));
        $this->assertFalse(WebPushService::isQuietHours($this->user(true), Carbon::parse('2026-11-09 18:00', 'UTC')));
    }

    public function test_adults_are_not_restricted(): void
    {
        $this->assertFalse(WebPushService::isQuietHours($this->user(false), Carbon::parse('2026-11-09 23:30', 'UTC')));
    }
}
