<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $admin = $request->user('admin');
        if (!$admin) {
            return response()->json(['message' => 'Authentification administrateur requise.'], 401);
        }
        if (!$admin instanceof \App\Models\AdminUser) {
            return response()->json(['message' => 'Accès réservé au back-office.'], 403);
        }
        $request->setUserResolver(fn() => $admin);
        return $next($request);
    }
}