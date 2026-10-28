<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\GuestSession;
use App\Models\EmailVerificationToken;
use App\Models\PasswordResetToken;
use App\Models\Consent;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        // Filtre d'âge
        $dob = \Carbon\Carbon::parse($request->date_of_birth);
        $age = $dob->age;

        if ($age < 13) {
            return response()->json([
                'message' => 'Inscription non autorisée. Un âge minimum est requis pour utiliser Africa Quizz.',
                'error_code' => 'AGE_TOO_YOUNG',
            ], 403);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'date_of_birth' => $request->date_of_birth,
        ]);
        $user->computeAgeStatus();
        $user->save();

        // Profil
        $user->profile()->create([
            'pseudo' => $request->name . rand(1000, 9999),
            'timezone' => $request->timezone ?? 'UTC',
            'locale' => $request->locale ?? 'fr',
            'is_protected' => $user->is_minor,
        ]);

        // Consentements obligatoires
        Consent::create([
            'user_id' => $user->id,
            'document_type' => 'terms',
            'version' => '1.0',
            'accepted_at' => now(),
        ]);
        Consent::create([
            'user_id' => $user->id,
            'document_type' => 'privacy',
            'version' => '1.0',
            'accepted_at' => now(),
        ]);

        // Si 13-15 ans : demande de consentement parental
        $needsParental = $user->parental_consent_required;
        if ($needsParental) {
            $user->update([
                'parental_email' => $request->parental_email,
                'parental_consent_token' => Str::random(64),
            ]);
        }

        // Token de vérification email
        $this->sendVerificationEmail($user);

        // Token Sanctum (mais fonctionnalités limitées tant que non vérifié)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user->load('profile'),
                'token' => $token,
                'requires_email_verification' => true,
                'requires_parental_consent' => $needsParental,
                'parental_email_sent_to' => $needsParental ? $user->parental_email : null,
            ]
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        if ($user->is_banned) {
            return response()->json([
                'message' => 'Compte suspendu.',
                'reason' => $user->ban_reason,
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user->load('profile'),
                'token' => $token,
                'requires_email_verification' => !$user->isEmailVerified(),
                'requires_parental_consent' => $user->parental_consent_required && !$user->hasParentalConsent(),
            ]
        ]);
    }

    public function guest()
    {
        $session = GuestSession::create([
            'token' => (string) Str::uuid(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'last_active_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'token' => $session->token,
                'expires_at' => now()->addDays(30),
            ]
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté.']);
    }

    // === VÉRIFICATION EMAIL ===
    public function sendVerificationEmail(User $user): void
    {
        $token = Str::random(64);
        EmailVerificationToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => now()->addHours(24),
        ]);
        // En prod : Mail::to($user->email)->send(new VerifyEmailMail($token));
        // Pour MVP : on log le token
        \Log::info("Email verification token for {$user->email}: {$token}");
    }

    public function verifyEmail(Request $request)
    {
        $validated = $request->validate(['token' => 'required|string']);

        $tokenRecord = EmailVerificationToken::where('token', $validated['token'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$tokenRecord) {
            return response()->json(['message' => 'Token invalide ou expiré.'], 422);
        }

        $user = $tokenRecord->user;
        $user->update(['email_verified_at' => now()]);
        $tokenRecord->update(['used_at' => now()]);

        return response()->json([
            'data' => ['verified' => true, 'message' => 'Email vérifié avec succès.']
        ]);
    }

    public function resendVerification(Request $request)
    {
        $user = $request->user();
        if ($user->isEmailVerified()) {
            return response()->json(['message' => 'Email déjà vérifié.'], 422);
        }

        // Invalider les anciens tokens
        EmailVerificationToken::where('user_id', $user->id)->whereNull('used_at')->delete();
        $this->sendVerificationEmail($user);

        return response()->json(['message' => 'Email de vérification renvoyé.']);
    }

    // === MOT DE PASSE OUBLIÉ ===
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate(['email' => 'required|email']);

        $user = User::where('email', $validated['email'])->first();

        // Réponse générique (anti-énumération)
        if ($user) {
            PasswordResetToken::where('user_id', $user->id)->whereNull('used_at')->delete();
            $token = Str::random(64);
            PasswordResetToken::create([
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => now()->addMinutes(60),
            ]);
            // En prod : Mail::to($user->email)->send(new ResetPasswordMail($token));
            \Log::info("Password reset token for {$user->email}: {$token}");
        }

        return response()->json([
            'message' => 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $tokenRecord = PasswordResetToken::where('token', $validated['token'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$tokenRecord || $tokenRecord->user->email !== $validated['email']) {
            return response()->json(['message' => 'Token invalide ou expiré.'], 422);
        }

        $tokenRecord->user->update(['password' => Hash::make($validated['password'])]);
        $tokenRecord->update(['used_at' => now()]);
        $tokenRecord->user->tokens()->delete();

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }

    // === CONSENTEMENT PARENTAL ===
    public function confirmParentalConsent(Request $request)
    {
        $validated = $request->validate(['token' => 'required|string']);

        $user = User::where('parental_consent_token', $validated['token'])->first();

        if (!$user) {
            return response()->json(['message' => 'Token invalide.'], 422);
        }

        $user->update([
            'parental_consent_confirmed_at' => now(),
            'parental_consent_token' => null,
        ]);

        Consent::create([
            'user_id' => $user->id,
            'document_type' => 'parental_consent',
            'version' => '1.0',
            'accepted_at' => now(),
        ]);

        return response()->json(['data' => ['confirmed' => true]]);
    }

    // === RGPD ===
    public function exportData(Request $request)
    {
        $user = $request->user();
        $data = [
            'user' => $user->only(['id', 'name', 'email', 'date_of_birth', 'created_at']),
            'profile' => $user->profile,
            'consents' => $user->consents,
            'xp_transactions' => $user->xpTransactions,
            'streak' => $user->streak,
            'badges' => $user->badges,
            'daily_results' => $user->dailyResults,
            'quiz_sessions' => \App\Models\QuizSession::where('user_id', $user->id)->with('answers')->get(),
        ];

        return response()->json([
            'data' => $data,
            'exported_at' => now()->toIso8601String(),
            'format_version' => '1.0',
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|string',
            'confirmation' => 'required|in:SUPPRIMER,DELETE',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Mot de passe incorrect.'], 422);
        }

        // Anonymisation (pas de hard delete pour préserver les stats agrégées)
        $user->tokens()->delete();
        $user->anonymize();

        return response()->json([
            'message' => 'Compte anonymisé. Vos données personnelles ont été supprimées.',
        ]);
    }
}