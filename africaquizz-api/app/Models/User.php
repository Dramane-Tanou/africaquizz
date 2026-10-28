<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'name', 'email', 'password', 'date_of_birth', 'age', 'is_minor',
        'parental_consent_required', 'parental_consent_token', 'parental_consent_confirmed_at',
        'parental_email', 'email_verified_at', 'is_banned', 'ban_reason', 'banned_at', 'is_anonymized',
    ];

    protected $hidden = ['password', 'remember_token', 'parental_consent_token'];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'parental_consent_confirmed_at' => 'datetime',
        'banned_at' => 'datetime',
        'date_of_birth' => 'date',
        'is_minor' => 'boolean',
        'is_banned' => 'boolean',
        'is_anonymized' => 'boolean',
        'parental_consent_required' => 'boolean',
        'password' => 'hashed',
    ];

    // Relations
    public function profile() { return $this->hasOne(Profile::class); }
    public function consents() { return $this->hasMany(Consent::class); }
    public function xpTransactions() { return $this->hasMany(XpTransaction::class); }
    public function streak() { return $this->hasOne(Streak::class); }
    public function badges() { return $this->hasMany(UserBadge::class); }
    public function dailyResults() { return $this->hasMany(UserDailyResult::class); }
    public function reports() { return $this->hasMany(QuestionReport::class); }
    public function emailVerificationTokens() { return $this->hasMany(EmailVerificationToken::class); }
    public function passwordResetTokens() { return $this->hasMany(PasswordResetToken::class); }

    // Helpers
    public function isEmailVerified(): bool { return !is_null($this->email_verified_at); }
    public function hasParentalConsent(): bool {
        if (!$this->parental_consent_required) return true;
        return !is_null($this->parental_consent_confirmed_at);
    }
    public function isFullyActivated(): bool {
        return $this->isEmailVerified() && $this->hasParentalConsent() && !$this->is_banned;
    }

    // Calcule l'âge et le statut mineur
    public function computeAgeStatus(): void {
        if (!$this->date_of_birth) return;
        $this->age = $this->date_of_birth->age;
        $this->is_minor = $this->age < 18;
        $this->parental_consent_required = $this->age >= 13 && $this->age < 15;
    }

    // Anonymisation RGPD
    public function anonymize(): void {
        $this->update([
            'name' => 'Anonyme',
            'email' => 'deleted_' . $this->id . '@anonymized.local',
            'password' => bcrypt(\Str::random(64)),
            'is_anonymized' => true,
            'parental_email' => null,
        ]);
        if ($this->profile) {
            $this->profile->update([
                'pseudo' => 'Joueur_' . substr($this->id, 0, 8),
                'avatar' => 'default',
            ]);
        }
    }
}