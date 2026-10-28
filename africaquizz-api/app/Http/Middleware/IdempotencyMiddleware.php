<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\IdempotencyKey;

class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('Idempotency-Key');
        if (!$key) {
            return $next($request);
        }

        if (strlen($key) > 255) {
            return response()->json(['message' => 'Idempotency-Key trop long (max 255).'], 400);
        }

        // Chercher une clé existante
        $existing = IdempotencyKey::where('key', $key)->first();

        if ($existing) {
            // Vérifier l'expiration
            if ($existing->expires_at < now()) {
                $existing->delete();
            } else {
                // Retourner la réponse mise en cache
                return response()->json($existing->response_body, $existing->response_status);
            }
        }

        // Nouvelle requête : on la traite
        $response = $next($request);

        // Si succès (2xx), on stocke
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            try {
                IdempotencyKey::create([
                    'key' => $key,
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'user_id' => $request->user()?->id,
                    'response_body' => json_decode($response->getContent(), true),
                    'response_status' => $response->getStatusCode(),
                    'expires_at' => now()->addHours(24),
                ]);
            } catch (\Exception $e) {
                // Ignore errors (clé déjà créée entre-temps)
            }
        }

        return $response;
    }
}