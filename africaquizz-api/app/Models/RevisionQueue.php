<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevisionQueue extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'revision_queue';
    protected $fillable = ['user_id','question_id','interval_days','level','consecutive_success','next_review_at','last_reviewed_at'];
    protected $casts = ['next_review_at' => 'datetime', 'last_reviewed_at' => 'datetime'];

    const INTERVALS = [1, 3, 7, 21];

    public function user() { return $this->belongsTo(User::class); }
    public function question() { return $this->belongsTo(Question::class); }

    public static function enqueue(User $user, string $questionId): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id, 'question_id' => $questionId],
            ['interval_days' => 1, 'level' => 0, 'next_review_at' => now()->addDay()]
        );
    }

    public function advance(): void
    {
        $newLevel = min(3, $this->level + 1);
        $newInterval = self::INTERVALS[$newLevel];
        $this->update([
            'level' => $newLevel,
            'interval_days' => $newInterval,
            'consecutive_success' => $this->consecutive_success + 1,
            'next_review_at' => now()->addDays($newInterval),
            'last_reviewed_at' => now(),
        ]);
    }

    public function reset(): void
    {
        $this->update([
            'level' => 0,
            'interval_days' => 1,
            'consecutive_success' => 0,
            'next_review_at' => now()->addDay(),
            'last_reviewed_at' => now(),
        ]);
    }
}