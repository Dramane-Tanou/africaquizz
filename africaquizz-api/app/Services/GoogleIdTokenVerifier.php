<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Vérifie un ID token émis par Google Identity Services.
 * La signature est contrôlée par Google via l'endpoint tokeninfo ; on vérifie
 * ensuite l'audience, l'émetteur, l'expiration et la vérification de l'e-mail.
 */
class GoogleIdTokenVerifier
{
    private const TOKENINFO_URL = 'https://oauth2.googleapis.com/tokeninfo';
    private const ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    /**
     * @return array{sub: string, email: string, name: ?string, picture: ?string}|null
     */
    public function verify(string $idToken): ?array
    {
        $clientId = config('services.google.client_id');
        if (!$clientId) {
            return null;
        }

        try {
            $response = Http::timeout(5)->get(self::TOKENINFO_URL, ['id_token' => $idToken]);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }

        if (!$response->ok()) {
            return null;
        }

        $claims = $response->json();

        if (($claims['aud'] ?? null) !== $clientId
            || !in_array($claims['iss'] ?? null, self::ISSUERS, true)
            || (int) ($claims['exp'] ?? 0) < now()->timestamp
            || !in_array($claims['email_verified'] ?? null, [true, 'true'], true)
            || empty($claims['sub'])
            || empty($claims['email'])) {
            return null;
        }

        return [
            'sub' => (string) $claims['sub'],
            'email' => strtolower($claims['email']),
            'name' => $claims['name'] ?? $claims['given_name'] ?? null,
            'picture' => $claims['picture'] ?? null,
        ];
    }
}
