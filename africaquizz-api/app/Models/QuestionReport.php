<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionReport extends Model
{
    use HasFactory, HasUuids;

    const REASONS = [
        'wrong_answer' => 'Réponse incorrecte',
        'ambiguous' => 'Question ambiguë',
        'outdated' => 'Information obsolète',
        'offensive' => 'Contenu offensant',
        'typo' => 'Faute de frappe / orthographe',
        'image' => 'Problème d\'image',
    ];

    const THRESHOLD_AUTO_REVIEW = 3;

    protected $fillable = [
        'question_id', 'user_id', 'guest_session_id',
        'reason', 'comment', 'status',
        'reviewed_by_admin_id', 'reviewed_at', 'resolution_note',
        'xp_compensation_granted', 'xp_compensation_amount',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'xp_compensation_granted' => 'boolean',
    ];

    public function question() { return $this->belongsTo(Question::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function guestSession() { return $this->belongsTo(GuestSession::class); }
    public function reviewedBy() { return $this->belongsTo(AdminUser::class, 'reviewed_by_admin_id'); }

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}