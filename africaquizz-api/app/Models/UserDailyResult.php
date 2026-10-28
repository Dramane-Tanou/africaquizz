<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class UserDailyResult extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['user_id', 'guest_session_id', 'quiz_session_id', 'challenge_date', 'score', 'correct_answers'];
    protected $casts = ['challenge_date' => 'date'];
    public function user() { return $this->belongsTo(User::class); }
    public function quizSession() { return $this->belongsTo(QuizSession::class); }
}