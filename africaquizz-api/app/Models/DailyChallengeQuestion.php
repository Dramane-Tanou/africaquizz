<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class DailyChallengeQuestion extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['daily_challenge_id', 'question_id', 'position'];
    public function challenge() { return $this->belongsTo(DailyChallenge::class, 'daily_challenge_id'); }
    public function question() { return $this->belongsTo(Question::class); }
}