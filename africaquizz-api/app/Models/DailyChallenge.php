<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class DailyChallenge extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['challenge_date', 'closes_at'];
    protected $casts = ['challenge_date' => 'date', 'closes_at' => 'datetime'];
    public function questions() { return $this->hasMany(DailyChallengeQuestion::class); }
}