<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FriendChallenge extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = [
        'creator_user_id','creator_guest_id','code','creator_pseudo','question_ids',
        'creator_score','creator_correct','opponent_user_id','opponent_guest_id',
        'opponent_pseudo','opponent_score','opponent_correct','opponent_played_at','expires_at',
    ];
    protected $casts = ['question_ids' => 'array', 'opponent_played_at' => 'datetime', 'expires_at' => 'datetime'];

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8));
        } while (self::where('code', $code)->exists());
        return $code;
    }

    public function questions() { return Question::whereIn('id', $this->question_ids)->get(); }
}