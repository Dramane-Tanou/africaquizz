<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id', 'pseudo', 'avatar', 'country_id', 'birth_year', 'timezone', 'locale', 'is_protected',
        'total_xp', 'level', 'rank'
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function country() { return $this->belongsTo(Country::class); }

    // Méthode pour ajouter de l'XP et recalculer le niveau et le rang
    public function addXp(int $amount, string $reason = 'quiz_completion', ?string $quizSessionId = null)
    {
        // 1. Enregistrement de la transaction (append-only)
        $this->user->xpTransactions()->create([
            'quiz_session_id' => $quizSessionId,
            'amount' => $amount,
            'reason' => $reason,
        ]);

        // 2. Mise à jour du total d'XP
        $this->total_xp += $amount;
        
        // 3. Calcul du niveau (Formule du chapitre 8.3 : Niveau = ⌊√(XP / 120)⌋ + 1)
        $this->level = (int) floor(sqrt($this->total_xp / 120)) + 1;

        // 4. Calcul du rang (Chapitre 8.3)
        $this->rank = match(true) {
            $this->total_xp >= 150000 => 'maitre_du_savoir',
            $this->total_xp >= 50000  => 'erudit',
            $this->total_xp >= 15000  => 'initie',
            $this->total_xp >= 3000   => 'explorateur',
            default                   => 'curieux',
        };

        $this->save();
    }
}