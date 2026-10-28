<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminUser;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function __construct()
    {
        if (!request()->user('admin')?->hasRole('super_admin') && !request()->user('admin')?->hasRole('admin')) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $query = User::with('profile')->where('is_anonymized', false);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('email', 'ilike', "%$s%")
                  ->orWhere('name', 'ilike', "%$s%");
            });
        }
        if ($request->filled('is_banned')) {
            $query->where('is_banned', filter_var($request->is_banned, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('is_minor')) {
            $query->where('is_minor', filter_var($request->is_minor, FILTER_VALIDATE_BOOLEAN));
        }

        $users = $query->orderBy('created_at', 'desc')->limit(100)->get();

        return response()->json([
            'data' => [
                'data' => $users->map(fn($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'age' => $u->age,
                    'is_minor' => $u->is_minor,
                    'is_banned' => $u->is_banned,
                    'email_verified' => $u->isEmailVerified(),
                    'pseudo' => $u->profile?->pseudo,
                    'total_xp' => $u->profile?->total_xp,
                    'rank' => $u->profile?->rank,
                    'created_at' => $u->created_at->toIso8601String(),
                ]),
                'total' => $users->count(),
            ]
        ]);
    }

    public function show($id)
    {
        $user = User::with(['profile', 'consents', 'streak', 'badges.badge'])
            ->findOrFail($id);

        return response()->json(['data' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'pseudo' => 'nullable|string|max:50',
            'total_xp' => 'nullable|integer|min:0',
            'rank' => 'nullable|in:curieux,explorateur,initie,erudit,maitre_du_savoir',
        ]);

        if (isset($validated['name'])) $user->name = $validated['name'];
        if (isset($validated['email'])) $user->email = $validated['email'];
        $user->save();

        if (isset($validated['pseudo']) || isset($validated['total_xp']) || isset($validated['rank'])) {
            $profileUpdate = [];
            if (isset($validated['pseudo'])) $profileUpdate['pseudo'] = $validated['pseudo'];
            if (isset($validated['total_xp'])) {
                $profileUpdate['total_xp'] = $validated['total_xp'];
                $profileUpdate['level'] = (int) floor(sqrt($validated['total_xp'] / 120)) + 1;
            }
            if (isset($validated['rank'])) $profileUpdate['rank'] = $validated['rank'];
            $user->profile?->update($profileUpdate);
        }

        return response()->json(['data' => $user->fresh('profile')]);
    }

    public function ban(Request $request, $id)
    {
        $validated = $request->validate(['reason' => 'required|string|max:500']);
        $user = User::findOrFail($id);
        $user->update([
            'is_banned' => true,
            'ban_reason' => $validated['reason'],
            'banned_at' => now(),
        ]);
        $user->tokens()->delete();
        return response()->json(['data' => ['banned' => true]]);
    }

    public function unban($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_banned' => false, 'ban_reason' => null, 'banned_at' => null]);
        return response()->json(['data' => ['unbanned' => true]]);
    }

    public function forceVerifyEmail($id)
    {
        $user = User::findOrFail($id);
        $user->update(['email_verified_at' => now()]);
        return response()->json(['data' => ['verified' => true]]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->tokens()->delete();
        $user->anonymize();
        return response()->json(['message' => 'Utilisateur anonymisé (RGPD).']);
    }

    // === ADMIN USERS MANAGEMENT ===
    public function listAdmins()
    {
        $admins = AdminUser::with('role')->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $admins]);
    }

    public function createAdmin(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admin_users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:redacteur,relecteur,editeur,admin,super_admin',
        ]);

        $role = \App\Models\Role::where('name', $validated['role'])->firstOrFail();

        $admin = AdminUser::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Hash::make($validated['password']),
            'role_id' => $role->id,
        ]);

        return response()->json(['data' => $admin->load('role')], 201);
    }

    public function updateAdmin(Request $request, $id)
    {
        $admin = AdminUser::findOrFail($id);
        $validated = $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|email|unique:admin_users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'role' => 'nullable|in:redacteur,relecteur,editeur,admin,super_admin',
        ]);

        if (isset($validated['name'])) $admin->name = $validated['name'];
        if (isset($validated['email'])) $admin->email = $validated['email'];
        if (isset($validated['password'])) $admin->password = \Hash::make($validated['password']);
        if (isset($validated['role'])) {
            $role = \App\Models\Role::where('name', $validated['role'])->firstOrFail();
            $admin->role_id = $role->id;
        }
        $admin->save();

        return response()->json(['data' => $admin->fresh('role')]);
    }

    public function deleteAdmin($id)
    {
        $admin = AdminUser::findOrFail($id);
        if ($admin->id === request()->user('admin')->id) {
            return response()->json(['message' => 'Impossible de se supprimer soi-même.'], 422);
        }
        $admin->tokens()->delete();
        $admin->delete();
        return response()->json(['message' => 'Admin supprimé.']);
    }

    // === IMPERSONATION (superadmin peut se connecter en tant que user) ===
    public function impersonate($id)
    {
        if (!request()->user('admin')->hasRole('super_admin')) {
            return response()->json(['message' => 'Seul un SuperAdmin peut impersonate.'], 403);
        }

        $user = User::findOrFail($id);
        if ($user->is_banned || $user->is_anonymized) {
            return response()->json(['message' => 'Impossible d\'impersonate ce compte.'], 422);
        }

        $token = $user->createToken('impersonation_' . request()->user('admin')->id)->plainTextToken;

        // Log
        \App\Models\AuditLog::create([
            'actor_type' => 'admin_user',
            'actor_id' => request()->user('admin')->id,
            'action' => 'user.impersonate',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $user->load('profile'),
                'warning' => 'Ce token impersonate est loggé. Utilise-le uniquement pour du support.',
            ]
        ]);
    }
}