<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAnswer extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['quiz_session_id', 'question_id', 'answer_given', 'is_correct', 'client_elapsed_ms', 'rtt_ms', 'speed_bonus', 'score_gained', 'xp_gained', 'combo_streak'];
    public function session() { return $this->belongsTo(QuizSession::class); }
    public function question() { return $this->belongsTo(Question::class); }
}