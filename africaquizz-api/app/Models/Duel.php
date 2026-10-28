<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Duel extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = [
        'status','player1_id','player2_id','question_ids','player1_score','player2_score',
        'player1_correct','player2_correct','player1_played_at','player2_played_at','winner_id','expires_at',
    ];
    protected $casts = [
        'question_ids' => 'array',
        'player1_played_at' => 'datetime',
        'player2_played_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function player1() { return $this->belongsTo(User::class, 'player1_id'); }
    public function player2() { return $this->belongsTo(User::class, 'player2_id'); }
    public function winner() { return $this->belongsTo(User::class, 'winner_id'); }
}