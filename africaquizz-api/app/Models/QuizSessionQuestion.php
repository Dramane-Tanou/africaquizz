<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizSessionQuestion extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['quiz_session_id', 'question_id', 'position', 'served_at'];
    protected $casts = ['served_at' => 'datetime'];
    public function session() { return $this->belongsTo(QuizSession::class); }
    public function question() { return $this->belongsTo(Question::class); }
}