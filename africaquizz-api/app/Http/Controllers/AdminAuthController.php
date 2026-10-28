<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    // Ãƒâ€°tape 1 : Login avec email/password (retourne un token temporaire si 2FA activÃƒÂ©)
    public function login(Request $request, TwoFactorService $twoFactor)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = AdminUser::where('email', $validated['email'])->first();

        if (!$admin || !Hash::check($validated['password'], $admin->password)) {
            throw ValidationException::withMessages(['email' => 'Identifiants incorrects.']);
        }

        // === BYPASS 2FA EN DÉVELOPPEMENT (jamais en production) ===
        if (
            app()->environment(['local', 'testing'])
            && filter_var(config('auth.admin_2fa_bypass', false), FILTER_VALIDATE_BOOLEAN)
        ) {
            $admin->update(['last_login_at' => now()]);
            $token = $admin->createToken('admin_token')->plainTextToken;
            return response()->json([
                'data' => [
                    'admin' => $admin->load('role'),
                    'token' => $token,
                    'warning' => '2FA bypass activé (dev uniquement)',
                ]
            ]);
        }

        // Si 2FA pas encore configuré ou pas confirmé → forcer setup
        if (!$admin->two_factor_confirmed_at) {
            $tempToken = $admin->createToken('2fa_setup', ['2fa:setup'])->plainTextToken;
            return response()->json([
                'data' => [
                    'requires_2fa_setup' => true,
                    'temp_token' => $tempToken,
                    'admin' => ['id' => $admin->id, 'email' => $admin->email],
                    'message' => '2FA obligatoire. Utilise /admin/auth/2fa/setup avec ce token.',
                ]
            ]);
        }

        // 2FA activé → demander le code
        $tempToken = $admin->createToken('2fa_pending', ['2fa:verify'])->plainTextToken;
        return response()->json([
            'data' => [
                'requires_2fa_code' => true,
                'temp_token' => $tempToken,
                'admin' => ['id' => $admin->id, 'email' => $admin->email],
                'message' => 'Code 2FA requis. Utilise /admin/auth/2fa/verify.',
            ]
        ]);
    }
    public function setup2fa(Request $request, TwoFactorService $twoFactor)
    {
        $admin = $request->user();
        if (!$admin->currentAccessToken() || !$admin->currentAccessToken()->can('2fa:setup')) {
            return response()->json(['message' => 'Token de setup invalide.'], 403);
        }

        $secret = $twoFactor->generateSecret();
        $admin->update(['two_factor_secret' => encrypt($secret)]);

        $qrUrl = $twoFactor->getQrCodeUrl($admin, $secret);

        return response()->json([
            'data' => [
                'secret' => $secret,
                'qr_url' => $qrUrl,
                'manual_entry' => $secret,
                'message' => 'Scanne le QR code, puis envoie un code valide ÃƒÂ  /admin/auth/2fa/confirm.',
            ]
        ]);
    }

    // Ãƒâ€°tape 2b : Confirmer 2FA (l'admin envoie un code gÃƒÂ©nÃƒÂ©rÃƒÂ© par son app)
    public function confirm2fa(Request $request, TwoFactorService $twoFactor)
    {
        $validated = $request->validate(['code' => 'required|string|size:6']);
        $admin = $request->user();

        if (!$admin->currentAccessToken() || !$admin->currentAccessToken()->can('2fa:setup')) {
            return response()->json(['message' => 'Token invalide.'], 403);
        }

        $secret = decrypt($admin->two_factor_secret);
        if (!$twoFactor->verifyCode($secret, $validated['code'])) {
            return response()->json(['message' => 'Code incorrect.'], 422);
        }

        $recoveryCodes = $twoFactor->generateRecoveryCodes();
        $admin->update([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        // RÃƒÂ©voquer le token setup
        $admin->currentAccessToken()->delete();

        return response()->json([
            'data' => [
                'two_factor_confirmed' => true,
                'recovery_codes' => $recoveryCodes,
                'message' => '2FA activÃƒÂ©. Conserve ces codes de secours en lieu sÃƒÂ»r.',
            ]
        ]);
    }

    // Ãƒâ€°tape 2c : VÃƒÂ©rifier le code 2FA au login
    public function verify2fa(Request $request, TwoFactorService $twoFactor)
    {
        $validated = $request->validate(['code' => 'required|string|size:6']);
        $admin = $request->user();

        if (!$admin->currentAccessToken() || !$admin->currentAccessToken()->can('2fa:verify')) {
            return response()->json(['message' => 'Token invalide.'], 403);
        }

        $secret = decrypt($admin->two_factor_secret);
        if (!$twoFactor->verifyCode($secret, $validated['code'])) {
            return response()->json(['message' => 'Code 2FA incorrect.'], 422);
        }

        // RÃƒÂ©voquer le token temporaire
        $admin->currentAccessToken()->delete();

        // CrÃƒÂ©er le token final
        $admin->update(['last_login_at' => now()]);
        $finalToken = $admin->createToken('admin_token')->plainTextToken;

        return response()->json([
            'data' => [
                'admin' => $admin->load('role'),
                'token' => $finalToken,
            ]
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $request->user('admin')->load('role')]);
    }

    public function logout(Request $request)
    {
        $request->user('admin')->currentAccessToken()->delete();
        return response()->json(['message' => 'DÃƒÂ©connectÃƒÂ©.']);
    }
}