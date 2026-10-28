<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizSession extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['user_id', 'guest_session_id', 'mode', 'status', 'score', 'xp_earned', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function guestSession() { return $this->belongsTo(GuestSession::class); }
    public function sessionQuestions() { return $this->hasMany(QuizSessionQuestion::class); }
    public function answers() { return $this->hasMany(QuizAnswer::class); }
}