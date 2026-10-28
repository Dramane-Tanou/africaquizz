<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Rate limit global API : 60 req/min par IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Auth (login/register) : 5 tentatives/min par IP+email
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip() . '|' . $request->input('email'));
        });

        // Réponses quiz : 5/sec par session
        RateLimiter::for('quiz-answer', function (Request $request) {
            return Limit::perSecond(5)->by($request->user()?->id ?: $request->ip());
        });

        // Mot de passe oublié : 3/heure par email
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perHour(3)->by($request->input('email'));
        });
    }
}