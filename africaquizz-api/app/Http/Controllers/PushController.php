<?php
namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function subscribe(Request $request, WebPushService $service)
    {
        $validated = $request->validate([
            'endpoint' => 'required|url',
            'keys.auth' => 'required|string',
            'keys.p256dh' => 'required|string',
            'contentEncoding' => 'nullable|string',
            'guest_session_id' => 'nullable|uuid',
        ]);

        $user = $request->user('sanctum');
        $guestId = $validated['guest_session_id'] ?? null;

        if (!$user && !$guestId) {
            return response()->json(['message' => 'Connexion ou session invité requise.'], 422);
        }

        $sub = $service->subscribe($validated, $user?->id, $guestId);
        return response()->json(['data' => ['id' => $sub->id, 'subscribed' => true]], 201);
    }

    public function unsubscribe(Request $request)
    {
        $validated = $request->validate(['endpoint' => 'required|url']);
        PushSubscription::where('endpoint', $validated['endpoint'])->delete();
        return response()->json(['message' => 'Désabonné.']);
    }

    // Test : envoyer une notif à soi-même
    public function test(Request $request, WebPushService $service)
    {
        $user = $request->user('sanctum');
        if (!$user) return response()->json(['message' => 'Connexion requise.'], 401);
        $result = $service->sendToUser($user->id, 'Africa Quizz', 'Test de notification !', ['type' => 'test']);
        return response()->json(['data' => $result]);
    }

    // VAPID public key (pour le frontend)
    public function vapidKey()
    {
        return response()->json(['public_key' => env('VAPID_PUBLIC_KEY')]);
    }
}