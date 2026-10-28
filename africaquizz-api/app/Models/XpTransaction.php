<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XpTransaction extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['user_id', 'quiz_session_id', 'amount', 'reason'];
    
    public function user() { return $this->belongsTo(User::class); }
    public function quizSession() { return $this->belongsTo(QuizSession::class); }
}