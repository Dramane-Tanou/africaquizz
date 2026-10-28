<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class UserBadge extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['user_id', 'badge_id', 'tier', 'earned_at'];
    protected $casts = ['earned_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
    public function badge() { return $this->belongsTo(Badge::class); }
}