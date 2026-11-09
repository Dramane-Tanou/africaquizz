<?php
namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Carbon\Carbon;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class WebPushService
{
    private WebPush $webPush;

    public function __construct()
    {
        $auth = [
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => env('VAPID_PUBLIC_KEY'),
                'privateKey' => env('VAPID_PRIVATE_KEY'),
            ],
        ];
        $this->webPush = new WebPush($auth);
    }

    public function sendToUser(string $userId, string $title, string $body, array $data = []): array
    {
        $user = User::with('profile')->find($userId);
        if ($user && self::isQuietHours($user)) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 'quiet_hours'];
        }

        $subs = PushSubscription::where('user_id', $userId)->get();
        $sent = 0; $failed = 0;
        $payload = json_encode(['title' => $title, 'body' => $body, 'data' => $data]);

        foreach ($subs as $sub) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding,
                ]);
                $this->webPush->queueNotification($subscription, $payload);
            } catch (\Exception $e) {
                $failed++;
            }
        }

        foreach ($this->webPush->flush() as $report) {
            if ($report->isSuccess()) { $sent++; }
            else {
                $failed++;
                // Supprimer les endpoints expirés
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                }
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Heures calmes : pas de notification aux mineurs entre 22 h et 6 h, heure locale du joueur.
     */
    public static function isQuietHours(User $user, ?\DateTimeInterface $at = null): bool
    {
        if (!$user->is_minor) {
            return false;
        }
        $hour = (int) Carbon::instance($at ?? now())
            ->setTimezone($user->profile?->timezone ?: 'UTC')
            ->format('G');

        return $hour >= 22 || $hour < 6;
    }

    public function subscribe(array $data, ?string $userId, ?string $guestId): PushSubscription
    {
        return PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $userId,
                'guest_session_id' => $guestId,
                'auth_token' => $data['keys']['auth'],
                'public_key' => $data['keys']['p256dh'],
                'content_encoding' => $data['contentEncoding'] ?? 'aesgcm',
                'user_agent' => request()->userAgent(),
                'last_used_at' => now(),
            ]
        );
    }
}