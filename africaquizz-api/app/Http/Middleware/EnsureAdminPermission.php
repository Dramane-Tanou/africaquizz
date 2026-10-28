<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;

class EnsureAdminPermission
{
    private const PERMISSIONS = [
        'content.read' => ['super_admin', 'admin', 'relecteur', 'editeur'],
        'content.workflow' => ['super_admin', 'admin', 'relecteur', 'editeur'],
        'content.manage' => ['super_admin', 'admin', 'editeur'],
        'content.review' => ['super_admin', 'admin', 'relecteur'],
        'admin.manage' => ['super_admin', 'admin'],
        'super.manage' => ['super_admin'],
    ];

    public function handle(Request $request, Closure $next, string $permission)
    {
        $admin = $request->user('admin');
        $roles = self::PERMISSIONS[$permission] ?? [];

        if (!$admin instanceof AdminUser || !$admin->hasAnyRole($roles)) {
            return response()->json(['message' => 'Permission administrateur insuffisante.'], 403);
        }

        return $next($request);
    }
}
