<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'docs/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        // Domaines de production, séparés par des virgules (ex. https://app.africaquizz.com)
        ...array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')))),
    ],
    // En local uniquement : autorise la PWA ouverte via l'IP du PC (réseau Wi-Fi, téléphone de test).
    'allowed_origins_patterns' => env('APP_ENV') === 'local' ? [
        '#^http://(10(\.\d{1,3}){3}|192\.168(\.\d{1,3}){2}|172\.(1[6-9]|2\d|3[01])(\.\d{1,3}){2}):(5173|3000|3001)$#',
    ] : [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];