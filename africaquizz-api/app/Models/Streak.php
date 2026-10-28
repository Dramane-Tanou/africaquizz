<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Streak extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['user_id', 'current_streak', 'longest_streak', 'last_active_date', 'freezes_available'];
    protected $casts = ['last_active_date' => 'date'];
    public function user() { return $this->belongsTo(User::class); }

    public function updateOnPlay(string $timezone = 'UTC'): array
    {
        $today = now()->setTimezone($timezone)->toDateString();
        $lastDate = $this->last_active_date?->toDateString();
        $freezesUsed = 0;

        if ($lastDate === null) {
            $this->current_streak = 1;
        } elseif ($lastDate === $today) {
            return [
                'current' => $this->current_streak,
                'longest' => $this->longest_streak,
                'freezes' => $this->freezes_available,
                'is_new_day' => false,
            ];
        } else {
            $daysDiff = now()->setTimezone($timezone)->startOfDay()
                ->diffInDays(\Carbon\Carbon::parse($lastDate)->startOfDay());

            if ($daysDiff === 1) {
                $this->current_streak++;
            } elseif ($daysDiff === 2 && $this->freezes_available > 0) {
                $this->current_streak++;
                $this->freezes_available--;
                $freezesUsed = 1;
            } else {
                $this->current_streak = 1;
            }
        }

        if ($this->current_streak > $this->longest_streak) {
            $this->longest_streak = $this->current_streak;
        }
        if ($this->current_streak > 0 && $this->current_streak % 7 === 0) {
            $this->freezes_available = min(2, $this->freezes_available + 1);
        }

        $this->last_active_date = $today;
        $this->save();

        return [
            'current' => $this->current_streak,
            'longest' => $this->longest_streak,
            'freezes' => $this->freezes_available,
            'freezes_used' => $freezesUsed,
            'is_new_day' => true,
        ];
    }
}